import { useCallback, useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { clock } from '@/components/cbt/CbtParts';
import { ChevronLeft, ChevronRight, Flag, Wifi, WifiOff } from 'lucide-react';
import type { CbtPaperQuestion } from '@/Types';

interface Props {
    test: { id: number; title: string; sections: string[]; max_page_leaves: number | null };
    base: string;
    person: { name: string; number: string | null };
    questions: CbtPaperQuestion[];
    answers: Record<string, number>;
    flags: number[];
    secondsLeft: number;
    pageLeaves: number;
}

interface Pending { question_id: number; option: number | null }

const LETTERS = 'ABCDEF';

/** The exam screen: one question at a time, a numbered grid, a server-kept timer, and every answer saved as it is clicked */
export default function CbtExam({ base, test, person, questions, answers: saved, flags: savedFlags, secondsLeft, pageLeaves: savedLeaves }: Props) {
    // Per person, so on a shared exam computer one applicant's unsent answers never reach the next
    const pendingKey = `cbt-pending-${test.id}-${person.number ?? ''}`;
    const [index, setIndex] = useState(0);
    const [answers, setAnswers] = useState<Record<number, number>>(() => {
        const start: Record<number, number> = {};
        Object.entries(saved).forEach(([k, v]) => (start[Number(k)] = v));
        // Answers clicked while offline before a reload
        try {
            const kept: Pending[] = JSON.parse(localStorage.getItem(pendingKey) ?? '[]');
            kept.forEach(p => (p.option === null ? delete start[p.question_id] : (start[p.question_id] = p.option)));
        } catch { /* storage unavailable */ }
        return start;
    });
    const [flags, setFlags] = useState<number[]>(savedFlags);
    const [left, setLeft] = useState(secondsLeft);
    const [online, setOnline] = useState(true);
    const [leaves, setLeaves] = useState(savedLeaves);
    const [warning, setWarning] = useState(false);
    const [confirming, setConfirming] = useState(false);
    const pending = useRef<Pending[]>([]);
    const deadline = useRef(Date.now() + secondsLeft * 1000);
    const finishing = useRef(false);
    const sending = useRef(false);

    const q = questions[index];
    const unanswered = questions.filter(x => answers[x.id] === undefined).length;

    const finish = useCallback(() => {
        if (finishing.current) return;
        finishing.current = true;
        router.post(`${base}/submit`);
    }, [base]);

    const keepPending = () => {
        try { localStorage.setItem(pendingKey, JSON.stringify(pending.current)); } catch { /* storage unavailable */ }
    };

    // Send waiting answers one by one; keep them if the network is down and try again
    const flush = useCallback(async () => {
        if (sending.current) return;
        sending.current = true;
        try {
            await sendAll();
        } finally {
            sending.current = false;
        }
    }, [base, finish]); // eslint-disable-line react-hooks/exhaustive-deps

    const sendAll = async () => {
        while (pending.current.length) {
            const next = pending.current[0];
            try {
                const { data } = await axios.post(`${base}/answer`, next);
                // A newer answer to the same question may have replaced this one while it was sending
                if (pending.current[0] === next) pending.current.shift();
                keepPending();
                setOnline(true);
                deadline.current = Date.now() + data.seconds_left * 1000;
                if (data.finished) return finish();
            } catch (e) {
                if (axios.isAxiosError(e) && e.response?.status === 422) {
                    // Time is up on the server
                    pending.current = [];
                    keepPending();
                    return finish();
                }
                setOnline(false);
                return;
            }
        }
    };

    function choose(option: number) {
        if (!q || finishing.current) return;
        const value = answers[q.id] === option ? null : option;
        setAnswers(a => {
            const copy = { ...a };
            if (value === null) delete copy[q.id];
            else copy[q.id] = value;
            return copy;
        });
        pending.current = [...pending.current.filter(p => p.question_id !== q.id), { question_id: q.id, option: value }];
        keepPending();
        flush();
    }

    function toggleFlag() {
        if (!q) return;
        const on = !flags.includes(q.id);
        setFlags(f => (on ? [...f, q.id] : f.filter(id => id !== q.id)));
        axios.post(`${base}/flag`, { question_id: q.id, on }).catch(() => undefined);
    }

    // The clock: counts down here, and checks with the server every minute
    useEffect(() => {
        const tick = setInterval(() => {
            const s = Math.max(0, Math.round((deadline.current - Date.now()) / 1000));
            setLeft(s);
            if (s <= 0) {
                clearInterval(tick);
                flush().finally(finish);
            }
        }, 1000);
        const sync = setInterval(() => {
            axios.post(`${base}/ping`).then(({ data }) => {
                setOnline(true);
                deadline.current = Date.now() + data.seconds_left * 1000;
                if (data.finished) finish();
                flush();
            }).catch(() => setOnline(false));
        }, 60000);
        return () => { clearInterval(tick); clearInterval(sync); };
    }, [base, flush, finish]);

    // Retry waiting answers when the connection comes back
    useEffect(() => {
        const back = () => { setOnline(true); flush(); };
        window.addEventListener('online', back);
        if (pending.current.length === 0) {
            try { pending.current = JSON.parse(localStorage.getItem(pendingKey) ?? '[]'); } catch { /* storage unavailable */ }
            flush();
        }
        return () => window.removeEventListener('online', back);
    }, [flush, pendingKey]);

    // Leaving the page is counted on the server
    useEffect(() => {
        const onHide = () => {
            if (document.visibilityState !== 'hidden' || finishing.current) return;
            axios.post(`${base}/left`).then(({ data }) => {
                setLeaves(data.page_leaves);
                if (data.finished) finish();
            }).catch(() => undefined);
            setWarning(true);
        };
        document.addEventListener('visibilitychange', onHide);
        return () => document.removeEventListener('visibilitychange', onHide);
    }, [base, finish]);

    // Keyboard: A–F choose, N or → next, P or ← previous, F flag
    useEffect(() => {
        const onKey = (e: KeyboardEvent) => {
            if (confirming || warning || e.ctrlKey || e.metaKey || e.altKey) return;
            const key = e.key.toUpperCase();
            const i = LETTERS.indexOf(key);
            if (key === 'F') {
                toggleFlag();
            } else if (i >= 0 && q && i < q.options.length) {
                choose(q.options[i].value);
            } else if (key === 'N' || e.key === 'ArrowRight') {
                setIndex(n => Math.min(questions.length - 1, n + 1));
            } else if (key === 'P' || e.key === 'ArrowLeft') {
                setIndex(n => Math.max(0, n - 1));
            }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    });

    const low = left <= 300;

    return (
        <div className="flex min-h-screen select-none flex-col bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100" onContextMenu={e => e.preventDefault()} onCopy={e => e.preventDefault()}>
            <Head title={test.title} />
            <header className="sticky top-0 z-10 border-b border-slate-200 bg-white px-4 py-2.5 dark:border-white/10 dark:bg-slate-900">
                <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3">
                    <div className="min-w-0">
                        <p className="truncate font-semibold">{test.title}</p>
                        <p className="text-xs text-slate-500">{person.name}{person.number ? ` · ${person.number}` : ''}</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <span title={online ? 'Connected: answers are saving' : 'No connection: answers will save when it returns'} className={online ? 'text-emerald-600' : 'text-red-600'}>
                            {online ? <Wifi className="size-5" /> : <WifiOff className="size-5" />}
                        </span>
                        <span aria-live="polite" className={`rounded-lg px-3 py-1 font-mono text-lg font-semibold tabular-nums ${low ? 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300' : 'bg-slate-100 dark:bg-white/10'}`}>
                            {clock(left)}
                        </span>
                        <Button onClick={() => setConfirming(true)} className="bg-indigo-600 text-white hover:bg-indigo-700">Hand in</Button>
                    </div>
                </div>
                {!online && <p className="mx-auto mt-1 max-w-5xl text-xs text-red-600">No connection. Keep answering: your answers are kept on this computer and saved when the connection returns. The time keeps running.</p>}
            </header>

            <main className="mx-auto grid w-full max-w-5xl flex-1 gap-6 p-4 lg:grid-cols-[1fr_16rem]">
                {q && (
                    <section className="space-y-5">
                        <div className="flex flex-wrap items-center justify-between gap-2 text-sm text-slate-500">
                            <span>Question {index + 1} of {questions.length}{test.sections.length > 1 ? ` · ${test.sections[q.section] ?? ''}` : ''}</span>
                            <button type="button" onClick={toggleFlag} className={`inline-flex items-center gap-1.5 rounded-md px-2 py-1 ${flags.includes(q.id) ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300' : 'hover:bg-slate-100 dark:hover:bg-white/10'}`}>
                                <Flag className="size-4" /> {flags.includes(q.id) ? 'Marked for review' : 'Mark for review'}
                            </button>
                        </div>
                        <p className="whitespace-pre-line text-lg leading-relaxed">{q.text}</p>
                        {q.image && <img src={q.image} alt="" className="max-h-72 rounded-lg border border-slate-200 bg-white dark:border-white/10" draggable={false} />}
                        <div className="space-y-2.5" role="radiogroup" aria-label={`Question ${index + 1}`}>
                            {q.options.map((o, i) => {
                                const on = answers[q.id] === o.value;
                                return (
                                    <button key={o.value} type="button" role="radio" aria-checked={on} onClick={() => choose(o.value)}
                                        className={`flex w-full items-start gap-3 rounded-xl border-2 p-3.5 text-left transition-colors ${on ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-500/15' : 'border-slate-200 bg-white hover:border-slate-300 dark:border-white/10 dark:bg-slate-900'}`}>
                                        <span className={`flex size-7 shrink-0 items-center justify-center rounded-full text-sm font-semibold ${on ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300'}`}>{LETTERS[i]}</span>
                                        <span className="pt-0.5">{o.text}</span>
                                    </button>
                                );
                            })}
                        </div>
                        <div className="flex justify-between gap-2 pt-2">
                            <Button variant="outline" disabled={index === 0} onClick={() => setIndex(index - 1)}><ChevronLeft className="size-4" /> Previous</Button>
                            {index < questions.length - 1
                                ? <Button onClick={() => setIndex(index + 1)} className="bg-indigo-600 text-white hover:bg-indigo-700">Next <ChevronRight className="size-4" /></Button>
                                : <Button onClick={() => setConfirming(true)} className="bg-indigo-600 text-white hover:bg-indigo-700">Finish</Button>}
                        </div>
                        <p className="hidden text-xs text-slate-400 sm:block">Keys: A–E choose · N next · P previous · F mark for review</p>
                    </section>
                )}

                <aside className="space-y-3">
                    <p className="text-sm font-medium">{questions.length - unanswered} of {questions.length} answered</p>
                    <div className="grid grid-cols-8 gap-1.5 lg:grid-cols-5">
                        {questions.map((x, i) => {
                            const done = answers[x.id] !== undefined;
                            const flagged = flags.includes(x.id);
                            return (
                                <button key={x.id} type="button" onClick={() => setIndex(i)} aria-label={`Question ${i + 1}${done ? ', answered' : ''}${flagged ? ', marked for review' : ''}`}
                                    className={`h-9 rounded-md text-sm font-medium tabular-nums ${done ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 dark:bg-slate-900 dark:text-slate-200'} ${flagged ? 'ring-2 ring-amber-500' : 'border border-slate-200 dark:border-white/10'} ${i === index ? 'outline outline-2 outline-offset-1 outline-slate-900 dark:outline-white' : ''}`}>
                                    {i + 1}
                                </button>
                            );
                        })}
                    </div>
                    <div className="space-y-1 text-xs text-slate-500">
                        <p><span className="mr-1.5 inline-block size-3 rounded-sm bg-indigo-600 align-middle" /> Answered</p>
                        <p><span className="mr-1.5 inline-block size-3 rounded-sm border border-slate-300 align-middle" /> Not answered</p>
                        <p><span className="mr-1.5 inline-block size-3 rounded-sm ring-2 ring-amber-500 align-middle" /> Marked for review</p>
                    </div>
                </aside>
            </main>

            <Dialog open={confirming} onOpenChange={setConfirming}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Hand in your test?</DialogTitle></DialogHeader>
                    <div className="space-y-2 text-sm">
                        {unanswered > 0
                            ? <p className="text-amber-700 dark:text-amber-400">You have not answered {unanswered} question{unanswered === 1 ? '' : 's'}.</p>
                            : <p>You have answered every question.</p>}
                        {flags.length > 0 && <p>{flags.length} question{flags.length === 1 ? ' is' : 's are'} marked for review.</p>}
                        <p>After you hand in, you cannot change your answers.</p>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setConfirming(false)}>Go back</Button>
                        <Button onClick={() => flush().finally(finish)} className="bg-indigo-600 text-white hover:bg-indigo-700">Hand in now</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={warning} onOpenChange={setWarning}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>You left the test page</DialogTitle></DialogHeader>
                    <p className="text-sm">
                        This has been recorded{leaves ? ` (${leaves} time${leaves === 1 ? '' : 's'})` : ''} and your teacher can see it.
                        {test.max_page_leaves ? ` After ${test.max_page_leaves} times your test is handed in by itself.` : ''} The timer did not stop.
                    </p>
                    <DialogFooter><Button onClick={() => setWarning(false)} className="bg-indigo-600 text-white hover:bg-indigo-700">Continue the test</Button></DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
