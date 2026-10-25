import { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { PageHeader, Panel, Pill, Segmented } from '@/components/app/kit';
import { ArrowDown, ArrowLeft, ArrowUp, FileCheck2, Loader2, Plus, Printer, Search, Shuffle, Trash2, X } from 'lucide-react';
import TaughtScope from '@/components/cbt/TaughtScope';
import { inScope } from '@/lib/scope';
import type { CbtQuestionRow, ExamPaperData, LessonChoices } from '@/Types';

type Question = CbtQuestionRow & { deleted?: boolean };

interface Props extends LessonChoices {
    paper: (ExamPaperData & { id: number }) | null;
    initial: ExamPaperData;
    questions: Record<string, Question>;
    banks: { id: number; name: string; subject_id: number | null; questions: number }[];
    canManage: boolean;
    limits: { sections: number; questions: number };
}

const LETTERS = 'ABCDEF';

/** One exam paper: its heading details, its sections, and the questions in each, picked from the question banks */
export default function Paper({ paper, initial, questions, banks, classes, subjects, terms, canManage, limits }: Props) {
    const form = useForm<ExamPaperData>(initial);
    const d = form.data;
    const [known, setKnown] = useState<Record<number, Question>>(() => Object.fromEntries(Object.values(questions).map(q => [q.id, q])));
    const [picking, setPicking] = useState<number | null>(null);
    const errors = form.errors as Record<string, string | undefined>;
    const firstError = errors.sections ?? Object.entries(errors).find(([k]) => k.startsWith('sections.'))?.[1];

    useEffect(() => {
        setKnown(k => ({ ...k, ...Object.fromEntries(Object.values(questions).map(q => [q.id, q])) }));
    }, [questions]);

    const onPaper = useMemo(() => new Set(d.sections.flatMap(s => s.questions)), [d.sections]);
    const totalMarks = d.sections.flatMap(s => s.questions).reduce((sum, id) => sum + (known[id]?.marks ?? 0), 0);

    const classItems = [{ value: '', label: 'No class' }, ...classes.map(c => ({ value: String(c.id), label: c.name }))];
    const subjectItems = [{ value: '', label: 'No subject' }, ...subjects.filter(s => !d.class_id || s.class_ids.length === 0 || s.class_ids.includes(Number(d.class_id))).map(s => ({ value: String(s.id), label: s.name }))];
    const termItems = [{ value: '', label: 'No term' }, ...terms.map(t => ({ value: String(t.id), label: t.name }))];

    function setSection(i: number, patch: Partial<ExamPaperData['sections'][number]>) {
        form.setData('sections', d.sections.map((s, n) => (n === i ? { ...s, ...patch } : s)));
    }

    function moveQuestion(i: number, j: number, by: number) {
        const list = [...d.sections[i].questions];
        const k = j + by;
        if (k < 0 || k >= list.length) return;
        [list[j], list[k]] = [list[k], list[j]];
        setSection(i, { questions: list });
    }

    function save(e?: React.FormEvent) {
        e?.preventDefault();
        if (paper) form.put(`/school/cbt/papers/${paper.id}`, { preserveScroll: true });
        else form.post('/school/cbt/papers');
    }

    function addQuestions(section: number, picked: Question[]) {
        setKnown(k => ({ ...k, ...Object.fromEntries(picked.map(q => [q.id, q])) }));
        setSection(section, { questions: [...d.sections[section].questions, ...picked.map(q => q.id).filter(id => !onPaper.has(id))] });
        setPicking(null);
    }

    const printLinks = paper && (
        <div className="flex flex-wrap gap-2">
            <a href={`/school/cbt/papers/${paper.id}/pdf`} target="_blank" rel="noopener" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                <Printer className="size-4" /> Print paper
            </a>
            <a href={`/school/cbt/papers/${paper.id}/marking-scheme`} target="_blank" rel="noopener" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                <FileCheck2 className="size-4" /> Marking scheme
            </a>
        </div>
    );

    return (
        <AppLayout breadcrumbs={[{ label: 'Learning' }, { label: 'Printed Papers', href: '/school/cbt/papers' }, { label: paper ? d.title || 'Exam paper' : 'New exam paper' }]}>
            <form onSubmit={save} className="mx-auto max-w-4xl space-y-5 pb-28">
                <Link href="/school/cbt/papers" className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800 dark:hover:text-slate-200"><ArrowLeft className="size-4" /> All exam papers</Link>
                <PageHeader title={paper ? 'Exam paper' : 'New exam paper'} description="Pick questions from your question banks into sections. Save, then print the paper and its marking scheme." actions={printLinks || undefined} />
                {printLinks && <div className="flex flex-wrap gap-2 md:hidden">{printLinks}</div>}
                {paper && form.isDirty && <p className="text-sm text-amber-700 dark:text-amber-400">You have changes that are not saved yet. Save before printing, or the print shows the paper as it was.</p>}

                <Panel title="Heading" description="What prints at the top of the paper, under the school's name and logo.">
                    <fieldset disabled={!canManage} className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="title">Title</Label>
                            <Input id="title" value={d.title} onChange={e => form.setData('title', e.target.value)} maxLength={150} placeholder="e.g. First Term Examination" />
                            {errors.title && <p className="text-xs text-red-500">{errors.title}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label>Class</Label>
                            <Select items={classItems} value={d.class_id ? String(d.class_id) : ''} onValueChange={v => form.setData({ ...d, class_id: v ? Number(v) : null })} disabled={!canManage}>
                                <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>{classItems.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Subject</Label>
                            <Select items={subjectItems} value={d.subject_id ? String(d.subject_id) : ''} onValueChange={v => form.setData('subject_id', v ? Number(v) : null)} disabled={!canManage}>
                                <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>{subjectItems.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Term</Label>
                            <Select items={termItems} value={d.term_id ? String(d.term_id) : ''} onValueChange={v => form.setData('term_id', v ? Number(v) : null)} disabled={!canManage}>
                                <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>{termItems.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="duration">Time allowed (minutes)</Label>
                            <Input id="duration" type="number" min={5} max={600} value={d.duration_minutes ?? ''} onChange={e => form.setData('duration_minutes', e.target.value ? Number(e.target.value) : null)} />
                            {errors.duration_minutes && <p className="text-xs text-red-500">{errors.duration_minutes}</p>}
                        </div>
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="instructions">Instructions for the whole paper (optional)</Label>
                            <Textarea id="instructions" rows={2} value={d.instructions} onChange={e => form.setData('instructions', e.target.value)} maxLength={2000}
                                placeholder="e.g. Write your name and admission number at the top. Do not write on the question paper." />
                        </div>
                        <label className="flex items-center gap-2 text-sm sm:col-span-2">
                            <Checkbox checked={d.show_marks} onCheckedChange={v => form.setData('show_marks', !!v)} disabled={!canManage} /> Print the marks for each theory question and the total
                        </label>
                    </fieldset>
                </Panel>

                <Panel title="What the exam covers" description="Tick the topics this exam is set from, usually what has been taught so far. When you add questions you can show only the questions on those topics.">
                    <TaughtScope classId={d.class_id} subjectId={d.subject_id} termId={d.term_id} value={d.scope ?? []} onChange={t => form.setData('scope', t)} disabled={!canManage} />
                </Panel>

                {d.sections.map((s, i) => (
                    <Panel key={i} flush>
                        <div className="space-y-3 px-5 pt-4">
                            <fieldset disabled={!canManage} className="grid gap-2 sm:grid-cols-[1fr_auto]">
                                <Input aria-label={`Heading of section ${i + 1}`} value={s.title} onChange={e => setSection(i, { title: e.target.value })} maxLength={120} className="font-semibold" />
                                {canManage && d.sections.length > 1 && (
                                    <Button type="button" variant="ghost" size="sm" className="text-red-600" onClick={() => (s.questions.length === 0 || confirm('Remove this section and take its questions off the paper?')) && form.setData('sections', d.sections.filter((_, n) => n !== i))}>
                                        <Trash2 className="size-4" /> Remove section
                                    </Button>
                                )}
                                <Input aria-label={`Instructions for section ${i + 1}`} value={s.instructions ?? ''} onChange={e => setSection(i, { instructions: e.target.value })} maxLength={1000} placeholder="Instructions for this section, e.g. Answer all the questions." className="sm:col-span-2" />
                            </fieldset>
                        </div>
                        {s.questions.length === 0 ? (
                            <p className="px-5 py-4 text-sm text-slate-500">No questions in this section yet.</p>
                        ) : (
                            <ol className="mt-3 divide-y divide-slate-100 border-t border-slate-100 dark:divide-white/[0.06] dark:border-white/[0.06]">
                                {s.questions.map((id, j) => {
                                    const q = known[id];
                                    return (
                                        <li key={id} className="flex items-start gap-3 px-5 py-2.5">
                                            <span className="w-6 shrink-0 pt-0.5 text-sm font-semibold tabular-nums text-slate-500">{j + 1}.</span>
                                            <div className="min-w-0 flex-1 space-y-1">
                                                <p className="line-clamp-3 whitespace-pre-line text-sm text-slate-900 dark:text-white">{q?.text ?? 'Question not found'}</p>
                                                {q && q.type !== 'theory' && (
                                                    <p className="truncate text-xs text-slate-500">{q.options.map((o, n) => `${LETTERS[n]}. ${o}`).join('   ')}</p>
                                                )}
                                                <div className="flex flex-wrap gap-1.5">
                                                    {q?.type === 'theory' && <Pill tone="info">Theory</Pill>}
                                                    {q && <Pill tone="neutral">{q.marks} mark{q.marks === 1 ? '' : 's'}</Pill>}
                                                    {q?.topic && <Pill tone="neutral">{q.topic}</Pill>}
                                                    {q?.deleted && <Pill tone="warn">Deleted from its bank</Pill>}
                                                </div>
                                            </div>
                                            {canManage && (
                                                <div className="flex shrink-0">
                                                    <Button type="button" variant="ghost" size="icon" aria-label="Move up" disabled={j === 0} onClick={() => moveQuestion(i, j, -1)}><ArrowUp className="size-4" /></Button>
                                                    <Button type="button" variant="ghost" size="icon" aria-label="Move down" disabled={j === s.questions.length - 1} onClick={() => moveQuestion(i, j, 1)}><ArrowDown className="size-4" /></Button>
                                                    <Button type="button" variant="ghost" size="icon" aria-label="Take off the paper" onClick={() => setSection(i, { questions: s.questions.filter(x => x !== id) })}><X className="size-4" /></Button>
                                                </div>
                                            )}
                                        </li>
                                    );
                                })}
                            </ol>
                        )}
                        {canManage && (
                            <div className="border-t border-slate-100 px-5 py-3 dark:border-white/[0.06]">
                                <Button type="button" variant="outline" size="sm" onClick={() => setPicking(i)} disabled={onPaper.size >= limits.questions}><Plus className="size-4" /> Add questions</Button>
                            </div>
                        )}
                    </Panel>
                ))}

                {canManage && d.sections.length < limits.sections && (
                    <Button type="button" variant="outline" onClick={() => form.setData('sections', [...d.sections, { title: `Section ${String.fromCharCode(65 + d.sections.length)}`, instructions: '', questions: [] }])}>
                        <Plus className="size-4" /> Add a section
                    </Button>
                )}
                {firstError && <p className="text-sm text-red-500">{firstError}</p>}

                {canManage && (
                    <div className="sticky bottom-3 z-20 rounded-xl border border-slate-200 bg-white/95 px-3 py-2 shadow-sm backdrop-blur md:px-4 md:py-3 dark:border-white/10 dark:bg-slate-900/95">
                        <div className="flex items-center justify-between gap-2">
                            <p className="text-sm text-slate-600 dark:text-slate-300">
                                <b className="tabular-nums">{onPaper.size}</b> question{onPaper.size === 1 ? '' : 's'} · <b className="tabular-nums">{totalMarks}</b> marks
                            </p>
                            <div className="flex shrink-0 gap-1">
                                {paper && (
                                    <Button type="button" variant="ghost" className="text-red-600" aria-label="Delete this paper" onClick={() => confirm('Delete this exam paper? The questions stay in their banks.') && router.delete(`/school/cbt/papers/${paper.id}`)}>
                                        <Trash2 className="size-4" /> <span className="hidden sm:inline">Delete</span>
                                    </Button>
                                )}
                                <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">{paper ? 'Save changes' : 'Save paper'}</Button>
                            </div>
                        </div>
                    </div>
                )}
            </form>

            {picking !== null && (
                <QuestionPicker banks={banks} subjectId={d.subject_id} scope={d.scope ?? []} onPaper={onPaper} room={limits.questions - onPaper.size}
                    section={d.sections[picking].title} onAdd={qs => addQuestions(picking, qs)} onClose={() => setPicking(null)} />
            )}
        </AppLayout>
    );
}

/** Choose questions from one bank: tick them, or let the computer pick some at random */
function QuestionPicker({ banks, subjectId, scope, onPaper, room, section, onAdd, onClose }: {
    banks: Props['banks']; subjectId: number | null; scope: string[]; onPaper: Set<number>; room: number; section: string; onAdd: (q: Question[]) => void; onClose: () => void;
}) {
    const [bank, setBank] = useState(() => String(banks.find(b => subjectId && b.subject_id === subjectId)?.id ?? banks[0]?.id ?? ''));
    const [type, setType] = useState<'all' | 'objective' | 'theory'>(() => (/theory|essay|section b/i.test(section) ? 'theory' : /objective|section a/i.test(section) ? 'objective' : 'all'));
    const [list, setList] = useState<Question[]>([]);
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState('');
    const [topic, setTopic] = useState('');
    const [ticked, setTicked] = useState<number[]>([]);
    const [random, setRandom] = useState('10');
    const [onlyScope, setOnlyScope] = useState(scope.length > 0);

    useEffect(() => {
        if (!bank) return;
        setLoading(true);
        axios.get<{ questions: Question[] }>('/school/cbt/papers/questions', { params: { bank, type: type === 'all' ? undefined : type } })
            .then(r => setList(r.data.questions))
            .catch(() => setList([]))
            .finally(() => setLoading(false));
    }, [bank, type]);

    const topics = [...new Set(list.map(q => q.topic).filter((t): t is string => !!t))].sort();
    const shown = list.filter(q => (!topic || q.topic === topic) && (!onlyScope || scope.length === 0 || inScope(q.topic, scope)) && (!search || q.text.toLowerCase().includes(search.toLowerCase())));
    const outside = onlyScope && scope.length > 0 ? list.length - list.filter(q => inScope(q.topic, scope)).length : 0;
    const free = shown.filter(q => !onPaper.has(q.id));
    const bankItems = banks.map(b => ({ value: String(b.id), label: `${b.name} (${b.questions})` }));
    const topicItems = [{ value: '', label: 'All topics' }, ...topics.map(t => ({ value: t, label: t }))];

    function pickRandom() {
        const n = Math.min(Number(random) || 0, free.length, room);
        const pool = [...free];
        for (let i = pool.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [pool[i], pool[j]] = [pool[j], pool[i]];
        }
        setTicked(pool.slice(0, n).map(q => q.id));
    }

    function toggle(id: number, on: boolean) {
        setTicked(t => (on ? (t.length < room ? [...t, id] : t) : t.filter(x => x !== id)));
    }

    return (
        <Dialog open onOpenChange={o => !o && onClose()}>
            <DialogContent className="flex max-h-[92dvh] flex-col sm:max-w-3xl">
                <DialogHeader><DialogTitle>Add questions to “{section}”</DialogTitle></DialogHeader>
                {banks.length === 0 ? (
                    <p className="text-sm text-slate-600">There are no question banks yet. <Link href="/school/cbt/banks" className="text-indigo-600 underline">Create one</Link> and add questions, or ask the AI to write them.</p>
                ) : (
                    <>
                        <div className="grid gap-2 sm:grid-cols-2">
                            <Select items={bankItems} value={bank} onValueChange={v => { if (v) { setBank(v); setTicked([]); setTopic(''); } }}>
                                <SelectTrigger className="w-full" aria-label="Question bank"><SelectValue /></SelectTrigger>
                                <SelectContent>{bankItems.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                            </Select>
                            {topics.length > 0 && (
                                <Select items={topicItems} value={topic} onValueChange={v => setTopic(v ?? '')}>
                                    <SelectTrigger className="w-full" aria-label="Topic"><SelectValue /></SelectTrigger>
                                    <SelectContent>{topicItems.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                                </Select>
                            )}
                            <div className="relative">
                                <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                <Input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search questions" className="pl-9" />
                            </div>
                            <Segmented label="Kind" value={type} onChange={v => { setType(v); setTicked([]); }}
                                options={[{ value: 'all', label: 'All' }, { value: 'objective', label: 'Objective' }, { value: 'theory', label: 'Theory' }]} />
                        </div>
                        {scope.length > 0 && (
                            <label className="flex flex-wrap items-center gap-2 text-sm">
                                <Checkbox checked={onlyScope} onCheckedChange={v => { setOnlyScope(!!v); setTicked([]); }} />
                                Only questions on the {scope.length} topic{scope.length === 1 ? '' : 's'} this exam covers
                                {outside > 0 && <span className="text-xs text-slate-500">({outside} question{outside === 1 ? '' : 's'} outside the scope or without a topic hidden)</span>}
                            </label>
                        )}
                        <div className="flex flex-wrap items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm dark:bg-white/[0.04]">
                            <Shuffle className="size-4 text-slate-500" aria-hidden="true" />
                            Pick
                            <Input type="number" min={1} value={random} onChange={e => setRandom(e.target.value)} className="h-8 w-20" aria-label="How many to pick at random" />
                            at random from the {free.length} shown
                            <Button type="button" size="sm" variant="outline" onClick={pickRandom} disabled={free.length === 0}>Pick</Button>
                        </div>
                        <div className="min-h-40 flex-1 overflow-y-auto rounded-lg border border-slate-200 dark:border-white/10">
                            {loading ? (
                                <p className="flex items-center gap-2 p-4 text-sm text-slate-500"><Loader2 className="size-4 animate-spin" /> Loading questions…</p>
                            ) : shown.length === 0 ? (
                                <p className="p-4 text-sm text-slate-500">No questions here. Try another bank, kind or topic.</p>
                            ) : (
                                <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                                    {shown.map(q => {
                                        const already = onPaper.has(q.id);
                                        return (
                                            <li key={q.id}>
                                                <label className={`flex items-start gap-3 px-3 py-2.5 ${already ? 'opacity-50' : 'cursor-pointer hover:bg-slate-50 dark:hover:bg-white/[0.03]'}`}>
                                                    <Checkbox className="mt-0.5" checked={already || ticked.includes(q.id)} disabled={already} onCheckedChange={v => toggle(q.id, !!v)} />
                                                    <span className="min-w-0 flex-1 space-y-1">
                                                        <span className="line-clamp-3 block whitespace-pre-line text-sm">{q.text}</span>
                                                        <span className="flex flex-wrap gap-1.5">
                                                            {already && <Pill tone="good">On the paper</Pill>}
                                                            {q.type === 'theory' && <Pill tone="info">Theory</Pill>}
                                                            <Pill tone="neutral">{q.difficulty}</Pill>
                                                            {q.topic && <Pill tone="neutral">{q.topic}</Pill>}
                                                            <Pill tone="neutral">{q.marks} mark{q.marks === 1 ? '' : 's'}</Pill>
                                                        </span>
                                                    </span>
                                                </label>
                                            </li>
                                        );
                                    })}
                                </ul>
                            )}
                        </div>
                    </>
                )}
                <DialogFooter>
                    <Button type="button" variant="ghost" onClick={onClose}>Cancel</Button>
                    <Button type="button" disabled={ticked.length === 0} onClick={() => onAdd(list.filter(q => ticked.includes(q.id)))} className="bg-indigo-600 text-white hover:bg-indigo-700">
                        Add {ticked.length} question{ticked.length === 1 ? '' : 's'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
