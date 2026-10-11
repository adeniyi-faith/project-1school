import { useEffect, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { EmptyState, PageHeader, Panel, Pill } from '@/components/app/kit';
import { ArrowLeft, CheckCircle2, PenLine } from 'lucide-react';

interface Props {
    test: { id: number; title: string; audience: 'students' | 'applicants' };
    questions: { id: number; text: string; marks: number; topic: string | null; answers: number; waiting: number }[];
    question: { id: number; text: string; marks: number; marking_guide: string | null; image: string | null } | null;
    answers: { attempt_id: number; name: string; number: string | null; text: string; marks: number | null; comment: string | null }[];
}

type Entry = { marks: string; comment: string };

/** Marking typed (theory) answers: one question at a time, with the marking guide beside every student's answer */
export default function CbtMarking({ test, questions, question, answers }: Props) {
    const url = `/school/cbt/tests/${test.id}`;
    const [hideNames, setHideNames] = useState(false);
    const fromServer = () => Object.fromEntries(answers.map(a => [a.attempt_id, { marks: a.marks != null ? String(a.marks) : '', comment: a.comment ?? '' }]));
    const [entries, setEntries] = useState<Record<number, Entry>>(fromServer);
    // Another question opened, or marks just saved: start from what the server has
    useEffect(() => setEntries(fromServer()), [answers]); // eslint-disable-line react-hooks/exhaustive-deps
    const [saving, setSaving] = useState(false);
    const [problem, setProblem] = useState<string | null>(null);

    const changed = answers.filter(a => {
        const e = entries[a.attempt_id];
        return e && e.marks !== '' && (e.marks !== (a.marks != null ? String(a.marks) : '') || e.comment !== (a.comment ?? ''));
    });
    const waiting = questions.reduce((n, q) => n + q.waiting, 0);
    const set = (id: number, patch: Partial<Entry>) => setEntries(e => ({ ...e, [id]: { ...e[id], ...patch } }));

    function save() {
        if (!question || changed.length === 0) return;
        const max = question.marks;
        const bad = changed.find(a => { const m = parseFloat(entries[a.attempt_id].marks); return Number.isNaN(m) || m < 0 || m > max; });
        if (bad) {
            setProblem(`Give between 0 and ${max} marks${hideNames ? '' : ` (check ${bad.name})`}.`);
            return;
        }
        setProblem(null);
        setSaving(true);
        router.post(`${url}/marking`, {
            question_id: question.id,
            marks: Object.fromEntries(changed.map(a => [a.attempt_id, { marks: entries[a.attempt_id].marks, comment: entries[a.attempt_id].comment || null }])),
        }, {
            preserveScroll: true,
            onError: errs => setProblem(Object.values(errs)[0] ?? 'Some marks were not saved.'),
            onFinish: () => setSaving(false),
        });
    }

    return (
        <AppLayout breadcrumbs={[{ label: 'CBT' }, { label: 'Tests', href: '/school/cbt/tests' }, { label: test.title, href: url }, { label: 'Mark theory answers' }]}>
            <div className="mx-auto max-w-6xl space-y-6 pb-24 md:pb-0">
                <Link href={url} className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800 dark:hover:text-slate-200"><ArrowLeft className="size-4" /> Back to the test</Link>
                <PageHeader title="Mark theory answers" description={`${test.title} · ${waiting ? `${waiting} answer${waiting === 1 ? '' : 's'} still to mark` : 'Everything is marked'}`} />

                {questions.length === 0 ? (
                    <Panel><EmptyState icon={PenLine} title="Nothing to mark yet" text="Theory answers show here once students hand in." /></Panel>
                ) : (
                    <div className="grid gap-6 lg:grid-cols-[16rem_1fr]">
                        <nav aria-label="Theory questions" className="space-y-1.5">
                            {questions.map((q, i) => (
                                <Link key={q.id} href={`${url}/marking?question=${q.id}`} preserveScroll
                                    className={`block rounded-lg border px-3 py-2 text-sm ${q.id === question?.id ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10' : 'border-slate-200 bg-white hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.03]'}`}>
                                    <p className="font-medium text-slate-900 dark:text-white">Theory {i + 1} <span className="font-normal text-slate-500">· {q.marks} marks</span></p>
                                    <p className="line-clamp-2 text-xs text-slate-500">{q.text}</p>
                                    <p className="mt-1 text-xs">{q.waiting ? <span className="text-amber-700 dark:text-amber-400">{q.waiting} of {q.answers} to mark</span> : <span className="text-emerald-700 dark:text-emerald-400">All {q.answers} marked</span>}</p>
                                </Link>
                            ))}
                        </nav>

                        {question && (
                            <div className="space-y-4">
                                <Panel>
                                    <p className="whitespace-pre-line font-medium text-slate-900 dark:text-white">{question.text}</p>
                                    {question.image && <img src={question.image} alt="" className="mt-3 max-h-56 rounded border border-slate-200 dark:border-white/10" />}
                                    <div className="mt-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">
                                        <p className="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Marking guide · out of {question.marks}</p>
                                        <p className="whitespace-pre-line">{question.marking_guide || 'No guide was written for this question.'}</p>
                                    </div>
                                </Panel>

                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <label className="flex items-center gap-2 text-sm"><Checkbox checked={hideNames} onCheckedChange={v => setHideNames(!!v)} /> Hide names while marking</label>
                                    <Button onClick={save} disabled={saving || changed.length === 0} className="bg-indigo-600 text-white hover:bg-indigo-700">
                                        <CheckCircle2 className="size-4" /> Save {changed.length || ''} mark{changed.length === 1 ? '' : 's'}
                                    </Button>
                                </div>
                                {problem && <p className="text-sm text-red-600">{problem}</p>}

                                <ol className="space-y-3">
                                    {answers.map((a, i) => {
                                        const e = entries[a.attempt_id] ?? { marks: '', comment: '' };
                                        const blank = a.text.trim() === '';
                                        return (
                                            <li key={a.attempt_id}>
                                                <Panel>
                                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                                        <p className="text-sm font-medium text-slate-900 dark:text-white">
                                                            {hideNames ? `Answer ${i + 1}` : a.name}
                                                            {!hideNames && a.number && <span className="ml-1.5 font-normal text-slate-500">{a.number}</span>}
                                                        </p>
                                                        {a.marks == null ? <Pill tone="warn">Not marked</Pill> : <Pill tone="good">{a.marks} / {question.marks}</Pill>}
                                                    </div>
                                                    {blank
                                                        ? <p className="mt-2 text-sm italic text-slate-500">No answer was typed, so this got 0.</p>
                                                        : <p className="mt-2 whitespace-pre-wrap rounded-lg bg-slate-50 px-3 py-2 text-sm leading-relaxed text-slate-800 dark:bg-white/[0.04] dark:text-slate-200">{a.text}</p>}
                                                    <div className="mt-3 flex flex-wrap items-center gap-2">
                                                        <label className="flex items-center gap-2 text-sm">
                                                            Marks
                                                            <Input type="number" min={0} max={question.marks} step={0.5} value={e.marks} aria-label={`Marks for ${hideNames ? `answer ${i + 1}` : a.name}`}
                                                                onChange={ev => set(a.attempt_id, { marks: ev.target.value })} className="w-20" />
                                                            <span className="text-slate-500">/ {question.marks}</span>
                                                        </label>
                                                        <Input value={e.comment} onChange={ev => set(a.attempt_id, { comment: ev.target.value })} maxLength={500} placeholder="Comment (optional, for your records)" className="min-w-48 flex-1" />
                                                    </div>
                                                </Panel>
                                            </li>
                                        );
                                    })}
                                </ol>

                                {answers.length > 3 && (
                                    <div className="flex justify-end">
                                        <Button onClick={save} disabled={saving || changed.length === 0} className="bg-indigo-600 text-white hover:bg-indigo-700">
                                            <CheckCircle2 className="size-4" /> Save {changed.length || ''} mark{changed.length === 1 ? '' : 's'}
                                        </Button>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
