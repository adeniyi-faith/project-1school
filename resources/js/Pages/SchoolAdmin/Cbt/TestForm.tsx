import { Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageHeader, Panel } from '@/components/app/kit';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import type { CbtDifficulty } from '@/Types';

interface Section { title: string; cbt_bank_id: string; topic: string; difficulty: string; question_count: string }

interface Props {
    test: null | {
        id: number; title: string; class_id: number | null; subject_id: number | null; term_id: number | null; instructions: string | null;
        duration_minutes: number; opens_at: string; closes_at: string | null; shuffle_questions: boolean; shuffle_options: boolean; show_score: boolean;
        access_code: string | null; max_page_leaves: number | null; pass_mark: number | null; started: boolean;
        sections: { title: string; cbt_bank_id: number; topic: string | null; difficulty: CbtDifficulty | null; question_count: number }[];
    };
    banks: { id: number; name: string; counts: { topic: string | null; difficulty: CbtDifficulty }[] }[];
    classes: { id: number; name: string }[];
    subjects: { id: number; name: string; class_id: number }[];
    terms: { id: number; name: string; is_current: boolean }[];
}

interface TestForm {
    title: string; class_id: string; subject_id: string; term_id: string; instructions: string; duration_minutes: string; opens_at: string; closes_at: string;
    shuffle_questions: boolean; shuffle_options: boolean; show_score: boolean; access_code: string; max_page_leaves: string; pass_mark: string; sections: Section[];
}

const ANY = 'any';

/** A moment as the "YYYY-MM-DDTHH:mm" a date-time box shows, in the viewer's own time */
function toLocalInput(iso: string | null): string {
    if (!iso) return '';
    const d = new Date(iso);
    return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
}

/** The box's local time sent as an exact moment, so the server (on UTC) stores the right time */
function fromLocalInput(value: string): string | null {
    return value ? new Date(value).toISOString() : null;
}

function nextHour(): string {
    const d = new Date(Date.now() + 60 * 60 * 1000);
    d.setMinutes(0, 0, 0);
    return toLocalInput(d.toISOString());
}

export default function CbtTestForm({ test, banks, classes, subjects, terms }: Props) {
    const current = terms.find(t => t.is_current);
    const form = useForm<TestForm>({
        title: test?.title ?? '',
        class_id: test?.class_id ? String(test.class_id) : '',
        subject_id: test?.subject_id ? String(test.subject_id) : '',
        term_id: test?.term_id ? String(test.term_id) : current ? String(current.id) : '',
        instructions: test?.instructions ?? 'Answer every question. Click an option to choose it; you can change it until you hand in. The test is handed in by itself when the time is up.',
        duration_minutes: String(test?.duration_minutes ?? 30),
        opens_at: test ? toLocalInput(test.opens_at) : nextHour(),
        closes_at: toLocalInput(test?.closes_at ?? null),
        shuffle_questions: test?.shuffle_questions ?? true,
        shuffle_options: test?.shuffle_options ?? true,
        show_score: test?.show_score ?? true,
        access_code: test?.access_code ?? '',
        max_page_leaves: test?.max_page_leaves ? String(test.max_page_leaves) : '',
        pass_mark: test?.pass_mark != null ? String(test.pass_mark) : '',
        sections: test?.sections.map(s => ({ title: s.title, cbt_bank_id: String(s.cbt_bank_id), topic: s.topic ?? '', difficulty: s.difficulty ?? '', question_count: String(s.question_count) }))
            ?? [{ title: 'Section A', cbt_bank_id: banks[0] ? String(banks[0].id) : '', topic: '', difficulty: '', question_count: '20' }],
    });
    const d = form.data;
    const errors = form.errors as Record<string, string | undefined>;
    const locked = !!test?.started;
    const classItems = classes.map(c => ({ value: String(c.id), label: c.name }));
    const subjectItems = [{ value: '', label: 'No subject' }, ...subjects.filter(s => String(s.class_id) === d.class_id).map(s => ({ value: String(s.id), label: s.name }))];
    const termItems = [{ value: '', label: 'No term' }, ...terms.map(t => ({ value: String(t.id), label: t.name }))];
    const bankItems = banks.map(b => ({ value: String(b.id), label: b.name }));
    const total = d.sections.reduce((n, s) => n + (parseInt(s.question_count) || 0), 0);

    function setSection(i: number, patch: Partial<Section>) {
        form.setData('sections', d.sections.map((s, j) => (j === i ? { ...s, ...patch } : s)));
    }

    function available(s: Section): number {
        const bank = banks.find(b => String(b.id) === s.cbt_bank_id);
        return bank ? bank.counts.filter(c => (!s.topic || c.topic === s.topic) && (!s.difficulty || c.difficulty === s.difficulty)).length : 0;
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.transform(data => ({
            ...data,
            subject_id: data.subject_id || null, term_id: data.term_id || null, opens_at: fromLocalInput(data.opens_at), closes_at: fromLocalInput(data.closes_at),
            access_code: data.access_code.trim() || null, max_page_leaves: data.max_page_leaves || null, pass_mark: data.pass_mark || null,
            sections: data.sections.map(s => ({ ...s, topic: s.topic || null, difficulty: s.difficulty || null })),
        }));
        if (test) form.put(`/school/cbt/tests/${test.id}`);
        else form.post('/school/cbt/tests');
    }

    const field = (key: keyof TestForm, label: string, input: React.ReactNode, hint?: string) => (
        <div className="space-y-1.5">
            <Label htmlFor={key}>{label}</Label>
            {input}
            {hint && <p className="text-xs text-slate-500">{hint}</p>}
            {errors[key] && <p className="text-xs text-red-500">{errors[key]}</p>}
        </div>
    );

    const back = test ? `/school/cbt/tests/${test.id}` : '/school/cbt/tests';

    return (
        <AppLayout breadcrumbs={[{ label: 'CBT' }, { label: 'Tests', href: '/school/cbt/tests' }, { label: test ? 'Edit test' : 'New test' }]}>
            <form onSubmit={submit} className="mx-auto max-w-3xl space-y-6 pb-24 md:pb-0">
                <Link href={back} className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800 dark:hover:text-slate-200"><ArrowLeft className="size-4" /> Back</Link>
                <PageHeader title={test ? 'Edit test' : 'New test'} description="Choose who sits it, when, for how long, and which questions to draw from your question banks." />

                <Panel title="The test">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="sm:col-span-2">{field('title', 'Title', <Input id="title" value={d.title} onChange={e => form.setData('title', e.target.value)} placeholder="e.g. First CA test, Mathematics" maxLength={150} />)}</div>
                        {field('class_id', 'Class', (
                            <Select items={classItems} value={d.class_id} onValueChange={v => form.setData({ ...d, class_id: v ?? '', subject_id: '' })}>
                                <SelectTrigger className="w-full"><SelectValue placeholder="Choose a class" /></SelectTrigger>
                                <SelectContent>{classItems.map(c => <SelectItem key={c.value} value={c.value}>{c.label}</SelectItem>)}</SelectContent>
                            </Select>
                        ))}
                        {field('subject_id', 'Subject', (
                            <Select items={subjectItems} value={d.subject_id} onValueChange={v => form.setData('subject_id', v ?? '')}>
                                <SelectTrigger className="w-full"><SelectValue placeholder="No subject" /></SelectTrigger>
                                <SelectContent>{subjectItems.map(s => <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>)}</SelectContent>
                            </Select>
                        ), 'Needed to send scores into CA on the term results.')}
                        {field('term_id', 'Term', (
                            <Select items={termItems} value={d.term_id} onValueChange={v => form.setData('term_id', v ?? '')}>
                                <SelectTrigger className="w-full"><SelectValue placeholder="No term" /></SelectTrigger>
                                <SelectContent>{termItems.map(t => <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>)}</SelectContent>
                            </Select>
                        ))}
                        {field('duration_minutes', 'Time allowed (minutes)', <Input id="duration_minutes" type="number" min={1} max={600} value={d.duration_minutes} onChange={e => form.setData('duration_minutes', e.target.value)} />)}
                        {field('opens_at', 'Opens', <Input id="opens_at" type="datetime-local" value={d.opens_at} onChange={e => form.setData('opens_at', e.target.value)} />)}
                        {field('closes_at', 'Closes (optional)', <Input id="closes_at" type="datetime-local" value={d.closes_at} onChange={e => form.setData('closes_at', e.target.value)} />,
                            'Nobody can start after this, and anyone still writing is handed in at this time.')}
                        <div className="sm:col-span-2">{field('instructions', 'Instructions shown before starting', <Textarea id="instructions" rows={3} value={d.instructions} onChange={e => form.setData('instructions', e.target.value)} maxLength={3000} />)}</div>
                    </div>
                </Panel>

                <Panel title="Questions" description={locked ? 'People have started this test, so its questions can no longer change.' : 'Each section draws questions at random from a bank. Add sections to mix banks, topics or difficulty, e.g. 10 easy and 10 hard.'}>
                    <div className="space-y-4">
                        {d.sections.map((s, i) => {
                            const bank = banks.find(b => String(b.id) === s.cbt_bank_id);
                            const topics = [...new Set(bank?.counts.map(c => c.topic).filter((t): t is string => !!t) ?? [])].sort();
                            const topicItems = [{ value: ANY, label: 'Any topic' }, ...topics.map(t => ({ value: t, label: t }))];
                            const levelItems = [{ value: ANY, label: 'Any difficulty' }, { value: 'easy', label: 'Easy' }, { value: 'medium', label: 'Medium' }, { value: 'hard', label: 'Hard' }];
                            const have = available(s);
                            const short = (parseInt(s.question_count) || 0) > have;
                            return (
                                <div key={i} className="space-y-3 rounded-lg border border-slate-200 p-3 dark:border-white/10">
                                    <div className="flex items-center gap-2">
                                        <Input aria-label="Section title" value={s.title} disabled={locked} onChange={e => setSection(i, { title: e.target.value })} className="font-medium" maxLength={120} />
                                        {d.sections.length > 1 && !locked && (
                                            <Button type="button" variant="ghost" size="sm" aria-label="Remove section" onClick={() => form.setData('sections', d.sections.filter((_, j) => j !== i))}><Trash2 className="size-4" /></Button>
                                        )}
                                    </div>
                                    <div className="grid gap-3 sm:grid-cols-4">
                                        <div className="sm:col-span-2">
                                            <Select items={bankItems} value={s.cbt_bank_id} disabled={locked} onValueChange={v => setSection(i, { cbt_bank_id: v ?? '', topic: '' })}>
                                                <SelectTrigger className="w-full"><SelectValue placeholder="Question bank" /></SelectTrigger>
                                                <SelectContent>{bankItems.map(b => <SelectItem key={b.value} value={b.value}>{b.label}</SelectItem>)}</SelectContent>
                                            </Select>
                                        </div>
                                        <Select items={topicItems} value={s.topic || ANY} disabled={locked} onValueChange={v => setSection(i, { topic: v && v !== ANY ? v : '' })}>
                                            <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                            <SelectContent>{topicItems.map(t => <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>)}</SelectContent>
                                        </Select>
                                        <Select items={levelItems} value={s.difficulty || ANY} disabled={locked} onValueChange={v => setSection(i, { difficulty: v && v !== ANY ? v : '' })}>
                                            <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                            <SelectContent>{levelItems.map(t => <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>)}</SelectContent>
                                        </Select>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-2 text-sm">
                                        <span>Draw</span>
                                        <Input aria-label="Number of questions" type="number" min={1} max={500} value={s.question_count} disabled={locked} onChange={e => setSection(i, { question_count: e.target.value })} className="w-20" />
                                        <span className={short ? 'text-red-600' : 'text-slate-500'}>questions, out of {have} that match{short ? '. Add more questions to the bank, or draw fewer.' : ''}</span>
                                    </div>
                                    {Object.entries(errors).filter(([k]) => k.startsWith(`sections.${i}.`)).map(([k, v]) => <p key={k} className="text-xs text-red-500">{v}</p>)}
                                </div>
                            );
                        })}
                        {!locked && (
                            <Button type="button" variant="outline" size="sm" onClick={() => form.setData('sections', [...d.sections, { title: `Section ${String.fromCharCode(65 + d.sections.length)}`, cbt_bank_id: d.sections[0]?.cbt_bank_id ?? '', topic: '', difficulty: '', question_count: '10' }])}>
                                <Plus className="size-4" /> Add a section
                            </Button>
                        )}
                        {errors.sections && <p className="text-xs text-red-500">{errors.sections}</p>}
                        <p className="text-sm text-slate-600 dark:text-slate-300">Each person answers <strong>{total}</strong> questions.</p>
                    </div>
                </Panel>

                <Panel title="Fairness and results">
                    <div className="space-y-3 text-sm">
                        {([
                            ['shuffle_questions', 'Mix the order of questions for each person'],
                            ['shuffle_options', 'Mix the order of the options (A, B, C ...) for each person'],
                            ['show_score', 'Show students their score as soon as they hand in'],
                        ] as const).map(([key, label]) => (
                            <label key={key} className="flex items-center gap-2"><Checkbox checked={d[key]} onCheckedChange={v => form.setData(key, !!v)} /> {label}</label>
                        ))}
                    </div>
                    <div className="mt-4 grid gap-4 sm:grid-cols-3">
                        {field('access_code', 'Start code (optional)', <Input id="access_code" value={d.access_code} onChange={e => form.setData('access_code', e.target.value.toUpperCase())} placeholder="e.g. HALL7" maxLength={12} />,
                            'Write it on the board in the exam hall, so only people in the room can start.')}
                        {field('max_page_leaves', 'Hand in after leaving the page', <Input id="max_page_leaves" type="number" min={1} max={50} value={d.max_page_leaves} onChange={e => form.setData('max_page_leaves', e.target.value)} placeholder="Never" />,
                            'Counts switching to another tab or app. Leave empty to only count it.')}
                        {field('pass_mark', 'Pass mark % (optional)', <Input id="pass_mark" type="number" min={0} max={100} value={d.pass_mark} onChange={e => form.setData('pass_mark', e.target.value)} />)}
                    </div>
                </Panel>

                <div className="flex justify-end gap-2">
                    <Link href={back} className="inline-flex h-9 items-center rounded-lg px-3.5 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/[0.06]">Cancel</Link>
                    <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">{test ? 'Save test' : 'Save as draft'}</Button>
                </div>
            </form>
        </AppLayout>
    );
}
