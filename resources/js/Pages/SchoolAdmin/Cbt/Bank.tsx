import { useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { EmptyState, PageHeader, Panel, Pill, Segmented } from '@/components/app/kit';
import AiQuestionWriter, { type AiWriterInfo } from '@/components/cbt/AiQuestionWriter';
import { ArrowLeft, Download, FileUp, ImagePlus, Pencil, Plus, Search, Settings2, Sparkles, Trash2, X } from 'lucide-react';
import type { CbtDifficulty, CbtQuestionRow, CbtQuestionType } from '@/Types';

interface Props {
    bank: { id: number; name: string; description: string | null; subject_id: number | null; total: number; theory: number };
    questions: { data: CbtQuestionRow[]; total: number; from: number | null; to: number | null; prev_page_url: string | null; next_page_url: string | null };
    topics: string[];
    filters: { search: string; topic: string | null; difficulty: CbtDifficulty | null; type: CbtQuestionType | null };
    subjects: { id: number; name: string }[];
    canManage: boolean;
    maxOptions: number;
    ai: AiWriterInfo;
}

const LETTERS = 'ABCDEF';
const LEVELS: { value: CbtDifficulty; label: string }[] = [{ value: 'easy', label: 'Easy' }, { value: 'medium', label: 'Medium' }, { value: 'hard', label: 'Hard' }];
const LEVEL_TONE: Record<CbtDifficulty, 'good' | 'info' | 'bad'> = { easy: 'good', medium: 'info', hard: 'bad' };

export default function CbtBank({ bank, questions, topics, filters, subjects, canManage, maxOptions, ai }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [editing, setEditing] = useState<CbtQuestionRow | 'new' | null>(null);
    const [importing, setImporting] = useState(false);
    const [settings, setSettings] = useState(false);
    const [writing, setWriting] = useState(false);
    const url = `/school/cbt/banks/${bank.id}`;
    const topicItems = [{ value: 'all', label: 'All topics' }, ...topics.map(t => ({ value: t, label: t }))];

    function apply(next: Partial<Props['filters']>) {
        const f = { ...filters, search, ...next };
        router.get(url, {
            ...(f.search ? { search: f.search } : {}),
            ...(f.topic ? { topic: f.topic } : {}),
            ...(f.difficulty ? { difficulty: f.difficulty } : {}),
            ...(f.type ? { type: f.type } : {}),
        }, { preserveState: true, preserveScroll: true });
    }

    const actions = canManage && (
        <div className="flex flex-wrap gap-2">
            <Button variant="outline" onClick={() => setSettings(true)}><Settings2 className="size-4" /> Bank settings</Button>
            <Button variant="outline" onClick={() => setImporting(true)}><FileUp className="size-4" /> Add many</Button>
            {ai.shown && !ai.reason && <Button variant="outline" onClick={() => setWriting(true)} className="border-violet-200 text-violet-700 hover:bg-violet-50 dark:border-violet-500/30 dark:text-violet-300"><Sparkles className="size-4" /> Write with AI</Button>}
            <Button onClick={() => setEditing('new')} className="bg-indigo-600 text-white hover:bg-indigo-700"><Plus className="size-4" /> Add question</Button>
        </div>
    );

    return (
        <AppLayout breadcrumbs={[{ label: 'Learning' }, { label: 'Question banks', href: '/school/cbt/banks' }, { label: bank.name }]}>
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <Link href="/school/cbt/banks" className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800 dark:hover:text-slate-200"><ArrowLeft className="size-4" /> All question banks</Link>
                <PageHeader title={bank.name} description={`${bank.total} question${bank.total === 1 ? '' : 's'}${bank.theory ? ` (${bank.theory} theory)` : ''}${bank.description ? ` · ${bank.description}` : ''}`} actions={actions || undefined} />
                {actions && <div className="flex flex-wrap gap-2 md:hidden">{actions}</div>}
                {canManage && ai.shown && ai.reason && <p className="text-sm text-slate-500">AI question writer: {ai.reason}</p>}

                <div className="flex flex-wrap items-center gap-2">
                    <form onSubmit={e => { e.preventDefault(); apply({ search }); }} className="relative min-w-52 flex-1">
                        <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                        <Input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search questions" className="pl-9" />
                    </form>
                    {topics.length > 0 && (
                        <div className="w-48">
                            <Select items={topicItems} value={filters.topic ?? 'all'} onValueChange={v => apply({ topic: v && v !== 'all' ? v : null })}>
                                <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>{topicItems.map(t => <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>)}</SelectContent>
                            </Select>
                        </div>
                    )}
                    <Segmented label="Difficulty" value={(filters.difficulty ?? 'all') as CbtDifficulty | 'all'} onChange={v => apply({ difficulty: v === 'all' ? null : v })}
                        options={[{ value: 'all', label: 'All' }, ...LEVELS]} />
                    <Segmented label="Kind" value={(filters.type ?? 'all') as CbtQuestionType | 'all'} onChange={v => apply({ type: v === 'all' ? null : v })}
                        options={[{ value: 'all', label: 'All kinds' }, { value: 'objective', label: 'Objective' }, { value: 'theory', label: 'Theory' }]} />
                </div>

                <Panel flush>
                    {questions.data.length === 0 ? (
                        <div className="px-5 pb-5">
                            <EmptyState icon={Plus} title={bank.total ? 'Nothing matches' : 'No questions yet'}
                                text={bank.total ? 'Try another search or filter.' : 'Add questions one at a time, paste many objective questions at once, or upload the Excel template.'} />
                        </div>
                    ) : (
                        <ol className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {questions.data.map(q => (
                                <li key={q.id} className="space-y-2 px-5 py-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <p className="whitespace-pre-line font-medium text-slate-900 dark:text-white">{q.text}</p>
                                        {canManage && (
                                            <div className="flex shrink-0 gap-1">
                                                <Button variant="ghost" size="sm" aria-label="Edit" onClick={() => setEditing(q)}><Pencil className="size-4" /></Button>
                                                <Button variant="ghost" size="sm" aria-label="Delete" onClick={() => confirm('Delete this question? Tests already handed in keep their marks.') && router.delete(`${url}/questions/${q.id}`, { preserveScroll: true })}>
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                        )}
                                    </div>
                                    {q.image && <img src={q.image} alt="" className="max-h-40 rounded border border-slate-200 dark:border-white/10" />}
                                    {q.type === 'theory' ? (
                                        q.marking_guide && (
                                            <div className="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">
                                                <p className="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Marking guide</p>
                                                <p className="whitespace-pre-line">{q.marking_guide}</p>
                                            </div>
                                        )
                                    ) : <ul className="grid gap-1 text-sm sm:grid-cols-2">
                                        {q.options.map((o, i) => (
                                            <li key={i} className={i === q.correct_option ? 'font-medium text-emerald-700 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-300'}>
                                                {LETTERS[i]}. {o}{i === q.correct_option ? ' ✓' : ''}
                                            </li>
                                        ))}
                                    </ul>}
                                    <div className="flex flex-wrap gap-1.5">
                                        {q.type === 'theory' && <Pill tone="info">Theory</Pill>}
                                        <Pill tone={LEVEL_TONE[q.difficulty]}>{q.difficulty}</Pill>
                                        {q.topic && <Pill tone="neutral">{q.topic}</Pill>}
                                        {q.marks !== 1 && <Pill tone="neutral">{q.marks} marks</Pill>}
                                    </div>
                                </li>
                            ))}
                        </ol>
                    )}
                    {(questions.prev_page_url || questions.next_page_url) && (
                        <div className="flex items-center justify-between px-5 py-3 text-sm text-slate-500">
                            <span>{questions.from}–{questions.to} of {questions.total}</span>
                            <div className="flex gap-2">
                                {questions.prev_page_url && <Button variant="outline" size="sm" onClick={() => router.get(questions.prev_page_url!)}>Previous</Button>}
                                {questions.next_page_url && <Button variant="outline" size="sm" onClick={() => router.get(questions.next_page_url!)}>Next</Button>}
                            </div>
                        </div>
                    )}
                </Panel>
            </div>

            {editing && <QuestionDialog key={editing === 'new' ? 'new' : editing.id} url={url} question={editing === 'new' ? null : editing} topics={topics} maxOptions={maxOptions} onClose={() => setEditing(null)} />}
            {importing && <ImportDialog url={url} topics={topics} onClose={() => setImporting(false)} />}
            {writing && <AiQuestionWriter url={url} info={ai} topics={topics} onClose={() => setWriting(false)} />}
            {settings && <BankSettings bank={bank} subjects={subjects} onClose={() => setSettings(false)} />}
        </AppLayout>
    );
}

interface QuestionForm {
    type: CbtQuestionType; marking_guide: string;
    text: string; options: string[]; correct_option: number; topic: string; difficulty: CbtDifficulty; marks: string; explanation: string;
    image: File | null; remove_image: boolean;
}

function QuestionDialog({ url, question, topics, maxOptions, onClose }: { url: string; question: CbtQuestionRow | null; topics: string[]; maxOptions: number; onClose: () => void }) {
    const form = useForm<QuestionForm>({
        type: question?.type ?? 'objective', marking_guide: question?.marking_guide ?? '',
        text: question?.text ?? '', options: question?.options.length ? question.options : ['', '', '', ''], correct_option: question?.correct_option ?? 0,
        topic: question?.topic ?? '', difficulty: question?.difficulty ?? 'medium', marks: String(question?.marks ?? 1), explanation: question?.explanation ?? '',
        image: null, remove_image: false,
    });
    const errors = form.errors as Record<string, string | undefined>;
    const d = form.data;

    function setOption(i: number, value: string) {
        form.setData('options', d.options.map((o, j) => (j === i ? value : o)));
    }
    function removeOption(i: number) {
        form.setData({ ...d, options: d.options.filter((_, j) => j !== i), correct_option: d.correct_option === i ? 0 : d.correct_option > i ? d.correct_option - 1 : d.correct_option });
    }
    const theory = d.type === 'theory';

    function save(e: React.FormEvent) {
        e.preventDefault();
        form.transform(data => (data.type === 'theory'
            ? { ...data, options: undefined, correct_option: undefined, explanation: undefined }
            : { ...data, marking_guide: undefined }));
        form.post(question ? `${url}/questions/${question.id}` : `${url}/questions`, {
            forceFormData: true, preserveScroll: true,
            onSuccess: () => (question ? onClose() : form.setData({ ...form.data, text: '', options: ['', '', '', ''], correct_option: 0, explanation: '', marking_guide: '', image: null })),
        });
    }

    return (
        <Dialog open onOpenChange={o => !o && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader><DialogTitle>{question ? 'Edit question' : 'Add a question'}</DialogTitle></DialogHeader>
                <form onSubmit={save} className="space-y-4">
                    {!question && (
                        <Segmented label="Kind of question" value={d.type} onChange={v => form.setData('type', v)}
                            options={[{ value: 'objective', label: 'Objective (choose an option)' }, { value: 'theory', label: 'Theory (typed answer)' }]} />
                    )}
                    <div className="space-y-1.5">
                        <Label htmlFor="text">Question</Label>
                        <Textarea id="text" rows={3} value={d.text} onChange={e => form.setData('text', e.target.value)} maxLength={5000} autoFocus />
                        {errors.text && <p className="text-xs text-red-500">{errors.text}</p>}
                    </div>

                    <div className="space-y-1.5">
                        <Label>Picture (optional)</Label>
                        <div className="flex flex-wrap items-center gap-3">
                            {question?.image && !d.remove_image && !d.image && <img src={question.image} alt="" className="h-16 rounded border border-slate-200" />}
                            <label className="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-1.5 text-sm hover:bg-slate-50 dark:border-white/10">
                                <ImagePlus className="size-4" /> {d.image ? d.image.name : 'Choose a picture'}
                                <input type="file" accept="image/png,image/jpeg,image/webp" className="hidden" onChange={e => form.setData('image', e.target.files?.[0] ?? null)} />
                            </label>
                            {question?.image && !d.image && (
                                <label className="flex items-center gap-2 text-sm"><Checkbox checked={d.remove_image} onCheckedChange={v => form.setData('remove_image', !!v)} /> Remove the picture</label>
                            )}
                        </div>
                        {errors.image && <p className="text-xs text-red-500">{errors.image}</p>}
                    </div>

                    {theory ? (
                        <div className="space-y-1.5">
                            <Label htmlFor="guide">Marking guide (for the teacher who marks it)</Label>
                            <Textarea id="guide" rows={5} value={d.marking_guide} onChange={e => form.setData('marking_guide', e.target.value)} maxLength={5000}
                                placeholder={'The points a full answer should make, and the marks for each. For example:\n- Defines photosynthesis (1 mark)\n- Names chlorophyll and sunlight (2 marks)\n- Gives the word equation (2 marks)'} />
                            <p className="text-xs text-slate-500">Students type their answer. You mark it on the test's marking page, with this guide beside each answer.</p>
                            {errors.marking_guide && <p className="text-xs text-red-500">{errors.marking_guide}</p>}
                        </div>
                    ) : (
                    <div className="space-y-2">
                        <Label>Options: tick the right answer</Label>
                        {d.options.map((o, i) => (
                            <div key={i} className="flex items-center gap-2">
                                <input type="radio" name="correct" aria-label={`Option ${LETTERS[i]} is right`} checked={d.correct_option === i} onChange={() => form.setData('correct_option', i)} className="size-4 accent-emerald-600" />
                                <span className="w-5 text-sm font-medium text-slate-500">{LETTERS[i]}.</span>
                                <Input value={o} onChange={e => setOption(i, e.target.value)} maxLength={1000} className={d.correct_option === i ? 'border-emerald-400' : ''} />
                                {d.options.length > 2 && <Button type="button" variant="ghost" size="sm" aria-label="Remove option" onClick={() => removeOption(i)}><X className="size-4" /></Button>}
                            </div>
                        ))}
                        {d.options.length < maxOptions && <Button type="button" variant="ghost" size="sm" onClick={() => form.setData('options', [...d.options, ''])}><Plus className="size-4" /> Add an option</Button>}
                        {(errors.options || errors.correct_option || Object.keys(errors).find(k => k.startsWith('options.'))) && (
                            <p className="text-xs text-red-500">{errors.options ?? errors.correct_option ?? errors[Object.keys(errors).find(k => k.startsWith('options.'))!]}</p>
                        )}
                    </div>
                    )}
                    {errors.type && <p className="text-xs text-red-500">{errors.type}</p>}

                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="topic">Topic</Label>
                            <Input id="topic" list="topics" value={d.topic} onChange={e => form.setData('topic', e.target.value)} placeholder="e.g. Fractions" maxLength={120} />
                            <datalist id="topics">{topics.map(t => <option key={t} value={t} />)}</datalist>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Difficulty</Label>
                            <Select items={LEVELS} value={d.difficulty} onValueChange={v => v && form.setData('difficulty', v as CbtDifficulty)}>
                                <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>{LEVELS.map(l => <SelectItem key={l.value} value={l.value}>{l.label}</SelectItem>)}</SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="marks">Marks</Label>
                            <Input id="marks" type="number" min={0.5} max={100} step={0.5} value={d.marks} onChange={e => form.setData('marks', e.target.value)} />
                            {errors.marks && <p className="text-xs text-red-500">{errors.marks}</p>}
                        </div>
                    </div>
                    {!theory && (
                        <div className="space-y-1.5">
                            <Label htmlFor="explanation">Why this is the answer (optional, for teachers)</Label>
                            <Textarea id="explanation" rows={2} value={d.explanation} onChange={e => form.setData('explanation', e.target.value)} maxLength={2000} />
                        </div>
                    )}
                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onClose}>{question ? 'Cancel' : 'Done'}</Button>
                        <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">{question ? 'Save' : 'Add and write another'}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function ImportDialog({ url, topics, onClose }: { url: string; topics: string[]; onClose: () => void }) {
    const [mode, setMode] = useState<'paste' | 'excel'>('paste');
    const form = useForm<{ pasted: string; file: File | null; topic: string; difficulty: CbtDifficulty }>({ pasted: '', file: null, topic: '', difficulty: 'medium' });
    const errors = usePage().props.errors as Record<string, string>;
    const problems = Object.entries(errors).filter(([k]) => k.startsWith('import')).map(([, v]) => v);

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.transform(d => (mode === 'paste' ? { pasted: d.pasted, topic: d.topic, difficulty: d.difficulty } : { file: d.file }));
        form.post(`${url}/import`, { forceFormData: true, preserveScroll: true, onSuccess: () => onClose() });
    }

    return (
        <Dialog open onOpenChange={o => !o && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader><DialogTitle>Add many questions</DialogTitle></DialogHeader>
                <Segmented label="How" value={mode} onChange={setMode} options={[{ value: 'paste', label: 'Paste text' }, { value: 'excel', label: 'Upload Excel' }]} />
                <form onSubmit={submit} className="space-y-4">
                    {mode === 'paste' ? (
                        <>
                            <div className="rounded-lg bg-slate-50 p-3 text-xs text-slate-600 dark:bg-white/[0.04] dark:text-slate-300">
                                <p className="mb-1 font-medium">For objective questions. Type or paste from Word like this. Put a * after the right answer, and leave an empty line between questions:</p>
                                <pre className="font-mono leading-relaxed">{'1. What is the capital of Nigeria?\nA. Lagos\nB. Abuja *\nC. Kano\n\n2. Water boils at\nA. 50°C\nB. 100°C\nAnswer: B'}</pre>
                            </div>
                            <Textarea rows={12} value={form.data.pasted} onChange={e => form.setData('pasted', e.target.value)} placeholder="Paste your questions here" className="min-h-64 font-mono text-sm" />
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="imp-topic">Topic for all of them (optional)</Label>
                                    <Input id="imp-topic" list="imp-topics" value={form.data.topic} onChange={e => form.setData('topic', e.target.value)} maxLength={120} />
                                    <datalist id="imp-topics">{topics.map(t => <option key={t} value={t} />)}</datalist>
                                </div>
                                <div className="space-y-1.5">
                                    <Label>Difficulty for all of them</Label>
                                    <Select items={LEVELS} value={form.data.difficulty} onValueChange={v => v && form.setData('difficulty', v as CbtDifficulty)}>
                                        <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                        <SelectContent>{LEVELS.map(l => <SelectItem key={l.value} value={l.value}>{l.label}</SelectItem>)}</SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </>
                    ) : (
                        <div className="space-y-3 text-sm">
                            <p>Download the template, fill one question per row (the topic, difficulty and marks columns are optional), then upload it here.</p>
                            <a href="/school/cbt/banks/template" className="inline-flex items-center gap-2 text-indigo-600 hover:underline dark:text-indigo-400"><Download className="size-4" /> Download the Excel template</a>
                            <input type="file" accept=".xlsx" onChange={e => form.setData('file', e.target.files?.[0] ?? null)} className="block text-sm" />
                            {form.errors.file && <p className="text-xs text-red-500">{form.errors.file}</p>}
                        </div>
                    )}
                    {(problems.length > 0 || form.errors.pasted) && (
                        <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
                            <p className="font-medium">Nothing was added yet. Please fix these:</p>
                            <ul className="mt-1 list-disc pl-5">{(problems.length ? problems : [form.errors.pasted]).map((p, i) => <li key={i}>{p}</li>)}</ul>
                        </div>
                    )}
                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onClose}>Cancel</Button>
                        <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Add questions</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function BankSettings({ bank, subjects, onClose }: { bank: Props['bank']; subjects: Props['subjects']; onClose: () => void }) {
    const form = useForm({ name: bank.name, subject_id: bank.subject_id ? String(bank.subject_id) : '', description: bank.description ?? '' });
    const subjectItems = [{ value: '', label: 'No subject' }, ...subjects.map(s => ({ value: String(s.id), label: s.name }))];

    function save(e: React.FormEvent) {
        e.preventDefault();
        form.transform(d => ({ ...d, subject_id: d.subject_id || null }));
        form.put(`/school/cbt/banks/${bank.id}`, { onSuccess: onClose });
    }

    return (
        <Dialog open onOpenChange={o => !o && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader><DialogTitle>Bank settings</DialogTitle></DialogHeader>
                <form onSubmit={save} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="bank-name">Name</Label>
                        <Input id="bank-name" value={form.data.name} onChange={e => form.setData('name', e.target.value)} maxLength={120} />
                        {form.errors.name && <p className="text-xs text-red-500">{form.errors.name}</p>}
                    </div>
                    <div className="space-y-1.5">
                        <Label>Subject</Label>
                        <Select items={subjectItems} value={form.data.subject_id} onValueChange={v => form.setData('subject_id', v ?? '')}>
                            <SelectTrigger className="w-full"><SelectValue placeholder="No subject" /></SelectTrigger>
                            <SelectContent>{subjectItems.map(s => <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>)}</SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="bank-notes">Notes</Label>
                        <Textarea id="bank-notes" rows={2} value={form.data.description} onChange={e => form.setData('description', e.target.value)} maxLength={500} />
                    </div>
                    <DialogFooter className="sm:justify-between">
                        <Button type="button" variant="ghost" className="text-red-600" onClick={() => confirm('Delete this bank and all its questions?') && router.delete(`/school/cbt/banks/${bank.id}`)}>
                            <Trash2 className="size-4" /> Delete bank
                        </Button>
                        <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Save</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
