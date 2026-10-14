import { useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { EmptyState, PageHeader, Panel, Pill, Segmented, StatStrip } from '@/components/app/kit';
import { Checkbox } from '@/components/ui/checkbox';
import { AttemptPill, CbtStatusPill, examTime } from '@/components/cbt/CbtParts';
import { ArrowLeft, Calculator, Clock, Download, Eye, KeyRound, PenLine, Pencil, PlayCircle, Printer, RotateCcw, Send, StopCircle, Trash2, UserPlus, Users, X } from 'lucide-react';
import type { CbtResultRow, CbtTestRow } from '@/Types';

interface QuestionReport {
    id: number; type: 'objective' | 'theory'; text: string; topic: string | null; options: string[]; correct_option: number | null; seen: number; right: number; percent: number | null; picks: number[];
    /** Theory only */
    marked?: number; average?: number | null; max?: number;
}

interface Props {
    test: CbtTestRow & { instructions: string | null; access_code: string | null; sections: { title: string; bank: string | null; topic: string | null; topics: string[]; difficulty: string | null; question_type: 'objective' | 'theory' | null; count: number }[] };
    problems: string[];
    results: CbtResultRow[];
    summary: { expected: number; finished: number; writing: number; to_mark: number; average: number | null; highest: number | null; lowest: number | null; passed: number | null };
    questionReport: QuestionReport[];
    scoreParts: { id: number; label: string }[];
    canManage: boolean;
    canCopy: boolean;
    applicants: Applicant[];
    examUrl: string;
}

interface Applicant { id: number; student_name: string; class_interested: string | null; guardian_phone: string | null }

const OUTCOME: Record<string, { label: string; tone: 'good' | 'bad' | 'neutral' | 'warn' }> = {
    pending: { label: 'Waiting', tone: 'neutral' }, passed: { label: 'Passed', tone: 'good' }, failed: { label: 'Did not pass', tone: 'bad' }, absent: { label: 'Absent', tone: 'warn' },
};

const LETTERS = 'ABCDEF';

export default function CbtTestPage({ test, problems, results, summary, questionReport, scoreParts, canManage, canCopy, applicants, examUrl }: Props) {
    const [tab, setTab] = useState<'people' | 'questions'>('people');
    const [timeFor, setTimeFor] = useState<CbtResultRow | null>(null);
    const [copying, setCopying] = useState(false);
    const [adding, setAdding] = useState(false);
    const entrance = test.audience === 'applicants';
    const errors = usePage().props.errors as Record<string, string>;
    const statusProblems = Object.entries(errors).filter(([k]) => k.startsWith('status')).map(([, v]) => v);
    const url = `/school/cbt/tests/${test.id}`;
    const setStatus = (status: string) => router.post(`${url}/status`, { status }, { preserveScroll: true });

    const actions = (
        <div className="flex flex-wrap gap-2">
            <a href={`${url}/export`} className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                <Download className="size-4" /> Excel
            </a>
            {entrance && results.length > 0 && (
                <a href={`${url}/slips`} target="_blank" rel="noreferrer" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                    <Printer className="size-4" /> Exam slips
                </a>
            )}
            {canManage && <Link href={`${url}/edit`} className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200"><Pencil className="size-4" /> Edit</Link>}
            {canManage && test.status === 'draft' && <Button onClick={() => setStatus('published')} className="bg-emerald-600 text-white hover:bg-emerald-700"><PlayCircle className="size-4" /> {entrance ? 'Open the exam' : 'Open to students'}</Button>}
            {canManage && test.status === 'published' && <Button variant="outline" onClick={() => confirm('Close this test? Nobody will be able to start it.') && setStatus('closed')}><StopCircle className="size-4" /> Close</Button>}
            {canManage && test.status === 'closed' && <Button variant="outline" onClick={() => setStatus('published')}><PlayCircle className="size-4" /> Open again</Button>}
        </div>
    );

    return (
        <AppLayout breadcrumbs={[{ label: 'CBT' }, { label: 'Tests', href: '/school/cbt/tests' }, { label: test.title }]}>
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <Link href="/school/cbt/tests" className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800 dark:hover:text-slate-200"><ArrowLeft className="size-4" /> All tests</Link>
                <PageHeader title={test.title} description={`${[entrance ? 'Entrance exam' : test.class, test.subject, test.term].filter(Boolean).join(' · ')} · ${test.questions} questions in ${test.duration_minutes} minutes`} actions={actions} />
                <div className="flex flex-wrap gap-2 md:hidden">{actions}</div>

                {(problems.length > 0 || statusProblems.length > 0) && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                        <p className="font-medium">Before this test can be opened:</p>
                        <ul className="mt-1 list-disc pl-5">{(statusProblems.length ? statusProblems : problems).map((p, i) => <li key={i}>{p}</li>)}</ul>
                    </div>
                )}

                <Panel>
                    {entrance && (
                        <p className="mb-4 rounded-lg bg-indigo-50 px-3 py-2 text-sm text-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-200">
                            Applicants log in at <span className="font-mono font-medium">{examUrl}</span> with the exam number and PIN on their slip. Their score and pass or fail go onto their admission record.
                        </p>
                    )}
                    <dl className="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-3">
                        <div><dt className="text-slate-500">Status</dt><dd className="mt-0.5"><CbtStatusPill status={test.status} /></dd></div>
                        <div><dt className="text-slate-500">Opens</dt><dd className="mt-0.5 font-medium">{examTime(test.opens_at)}</dd></div>
                        <div><dt className="text-slate-500">Closes</dt><dd className="mt-0.5 font-medium">{test.closes_at ? examTime(test.closes_at) : 'When you close it'}</dd></div>
                        <div><dt className="text-slate-500">Start code</dt><dd className="mt-0.5 font-mono font-medium">{test.access_code ?? (test.has_code ? 'Set' : 'None')}</dd></div>
                        <div><dt className="text-slate-500">Leaving the page</dt><dd className="mt-0.5 font-medium">{test.max_page_leaves ? `Handed in after ${test.max_page_leaves} times` : 'Counted only'}</dd></div>
                        <div><dt className="text-slate-500">Pass mark</dt><dd className="mt-0.5 font-medium">{test.pass_mark != null ? `${test.pass_mark}%` : 'None'}</dd></div>
                        <div><dt className="text-slate-500">Calculator</dt><dd className="mt-0.5 font-medium">{test.allow_calculator ? <span className="inline-flex items-center gap-1"><Calculator className="size-4" /> On screen</span> : 'None'}</dd></div>
                    </dl>
                    <div className="mt-4 border-t border-slate-100 pt-3 text-sm dark:border-white/[0.06]">
                        <p className="mb-1 text-slate-500">Questions</p>
                        <ul className="space-y-0.5">
                            {test.sections.map((s, i) => (
                                <li key={i}><span className="font-medium">{s.title}:</span> {s.count} from {s.bank ?? 'a deleted bank'}{s.topics.length ? `, ${s.topics.length === 1 ? 'topic' : 'topics'} ${s.topics.join(', ')}` : ''}{s.difficulty ? `, ${s.difficulty}` : ''}{s.question_type ? `, ${s.question_type} only` : ''}</li>
                            ))}
                        </ul>
                    </div>
                    {canManage && test.status === 'draft' && summary.finished + summary.writing === 0 && (
                        <Button variant="ghost" size="sm" className="mt-3 text-red-600" onClick={() => confirm('Delete this test?') && router.delete(url)}><Trash2 className="size-4" /> Delete test</Button>
                    )}
                </Panel>

                <StatStrip items={[
                    { label: 'Handed in', value: `${summary.finished} of ${summary.expected}`, hint: [summary.writing ? `${summary.writing} writing now` : '', summary.to_mark ? `${summary.to_mark} to mark` : ''].filter(Boolean).join(' · ') || undefined },
                    { label: 'Average', value: summary.average != null ? `${summary.average}%` : '–' },
                    { label: 'Highest / lowest', value: summary.highest != null ? `${summary.highest}% / ${summary.lowest}%` : '–' },
                    { label: 'Passed', value: summary.passed != null ? `${summary.passed} of ${summary.finished}` : '–', hint: test.pass_mark != null ? `Pass mark ${test.pass_mark}%` : 'No pass mark set' },
                ]} />

                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Segmented label="Show" value={tab} onChange={setTab} options={[{ value: 'people', label: entrance ? 'Applicants' : 'Students' }, { value: 'questions', label: 'Questions, hardest first' }]} />
                    {canManage && (summary.to_mark > 0 || questionReport.some(q => q.type === 'theory')) && (
                        <Link href={`${url}/marking`} className="inline-flex h-9 items-center gap-2 rounded-lg bg-indigo-600 px-3.5 text-sm font-medium text-white hover:bg-indigo-700">
                            <PenLine className="size-4" /> Mark theory answers{summary.to_mark ? ` (${summary.to_mark} waiting)` : ''}
                        </Link>
                    )}
                    {entrance && canManage && test.status !== 'closed' && <Button variant="outline" onClick={() => setAdding(true)}><UserPlus className="size-4" /> Add applicants</Button>}
                    {canCopy && scoreParts.length > 0 && summary.finished > 0 && (
                        <Button variant="outline" onClick={() => setCopying(true)}><Send className="size-4" /> Send scores to term results</Button>
                    )}
                </div>

                {tab === 'people' ? (
                    <Panel flush>
                        {results.length === 0 ? (
                            <div className="px-5 pb-5">{entrance
                                ? <EmptyState icon={Users} title="No applicants yet" text="Add applicants from your admission inquiries, then print their exam slips." />
                                : <EmptyState icon={Users} title="No students" text="There are no active students in this class." />}</div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="text-left text-xs text-slate-500">
                                        <tr className="border-b border-slate-100 dark:border-white/[0.06]">
                                            <th className="px-5 py-2 font-medium">{entrance ? 'Applicant' : 'Student'}</th>
                                            <th className="px-3 py-2 font-medium">Status</th>
                                            {entrance && <th className="px-3 py-2 font-medium">Outcome</th>}
                                            <th className="px-3 py-2 text-right font-medium">Score</th>
                                            <th className="px-3 py-2 text-right font-medium">Answered</th>
                                            <th className="px-3 py-2 text-right font-medium">Left page</th>
                                            <th className="px-3 py-2 text-right font-medium">Minutes</th>
                                            {canManage && <th className="px-5 py-2" />}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                                        {results.map(r => (
                                            <tr key={r.key}>
                                                <td className="px-5 py-2.5">
                                                    <p className="font-medium text-slate-900 dark:text-white">
                                                        {r.attempt_id ? <Link href={`${url}/attempts/${r.attempt_id}`} className="hover:underline" title="See what they did">{r.name}</Link> : entrance && r.inquiry_id ? <Link href={`/school/admissions/inquiries/${r.inquiry_id}`} className="hover:underline">{r.name}</Link> : r.name}
                                                    </p>
                                                    <p className="text-xs text-slate-500">{entrance ? `Exam no. ${r.admission_no}${r.class_interested ? ` · for ${r.class_interested}` : ''}` : r.admission_no}</p>
                                                </td>
                                                <td className="px-3 py-2.5"><AttemptPill status={r.status} /></td>
                                                {entrance && <td className="px-3 py-2.5">{r.outcome && <Pill tone={OUTCOME[r.outcome].tone}>{OUTCOME[r.outcome].label}</Pill>}</td>}
                                                <td className="px-3 py-2.5 text-right tabular-nums">
                                                    {r.percent != null
                                                        ? <><span className={test.pass_mark != null && r.percent < test.pass_mark ? 'text-red-600' : 'font-medium'}>{r.percent}%</span> <span className="text-xs text-slate-500">({r.score}/{r.max_score})</span></>
                                                        : r.needs_marking ? <span className="text-xs text-amber-700 dark:text-amber-400" title="Marks so far, before the theory answers are marked">To mark · {r.score} so far</span> : '–'}
                                                </td>
                                                <td className="px-3 py-2.5 text-right tabular-nums">{r.questions ? `${r.answered}/${r.questions}` : '–'}</td>
                                                <td className={`px-3 py-2.5 text-right tabular-nums ${r.page_leaves ? 'text-amber-700 dark:text-amber-400' : ''}`}>{r.attempt_id ? r.page_leaves : '–'}</td>
                                                <td className="px-3 py-2.5 text-right tabular-nums">{r.minutes ?? '–'}</td>
                                                {canManage && (
                                                    <td className="whitespace-nowrap px-5 py-2.5 text-right">
                                                        {entrance && r.candidate_id && (
                                                            <a href={`${url}/slips?candidate=${r.candidate_id}`} target="_blank" rel="noreferrer" title="Print this exam slip" className="inline-flex h-8 items-center px-2.5 text-slate-600 hover:text-slate-900 dark:text-slate-300"><Printer className="size-4" /></a>
                                                        )}
                                                        {entrance && r.candidate_id && !r.attempt_id && (
                                                            <>
                                                                <Button variant="ghost" size="sm" title="Make a new PIN (for a lost slip)" onClick={() => confirm(`Make a new PIN for ${r.name}? The old slip stops working, so print a new one.`) && router.post(`${url}/candidates/${r.candidate_id}/pin`, {}, { preserveScroll: true })}><KeyRound className="size-4" /></Button>
                                                                <Button variant="ghost" size="sm" title="Take off this exam" onClick={() => confirm(`Take ${r.name} off this exam?`) && router.delete(`${url}/candidates/${r.candidate_id}`, { preserveScroll: true })}><X className="size-4" /></Button>
                                                            </>
                                                        )}
                                                        {r.attempt_id && (
                                                            <>
                                                                <Link href={`${url}/attempts/${r.attempt_id}`} title="See their answers" className="inline-flex h-8 items-center px-2.5 text-slate-600 hover:text-slate-900 dark:text-slate-300"><Eye className="size-4" /></Link>
                                                                <Button variant="ghost" size="sm" onClick={() => setTimeFor(r)} title="Give more time"><Clock className="size-4" /></Button>
                                                                <Button variant="ghost" size="sm" title="Let them start again" onClick={() => confirm(`Remove ${r.name}'s sitting so they can start again with new questions? Their answers will be lost.`) && router.delete(`${url}/attempts/${r.attempt_id}`, { preserveScroll: true })}>
                                                                    <RotateCcw className="size-4" />
                                                                </Button>
                                                            </>
                                                        )}
                                                    </td>
                                                )}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </Panel>
                ) : (
                    <Panel flush>
                        {questionReport.length === 0 ? (
                            <div className="px-5 pb-5"><EmptyState icon={Users} title="Nothing yet" text="This fills in as people hand in." /></div>
                        ) : (
                            <ol className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                                {questionReport.map(q => (
                                    <li key={q.id} className="space-y-2 px-5 py-3.5 text-sm">
                                        <div className="flex items-start justify-between gap-3">
                                            <p className="whitespace-pre-line font-medium text-slate-900 dark:text-white">{q.type === 'theory' && <Pill tone="info">Theory</Pill>} {q.text}</p>
                                            <span className={`shrink-0 font-semibold tabular-nums ${(q.percent ?? 100) < 40 ? 'text-red-600' : 'text-slate-700 dark:text-slate-200'}`}>
                                                {q.type === 'theory' ? (q.average != null ? `average ${q.average} / ${q.max}` : 'not marked yet') : `${q.percent ?? '–'}% right`}
                                            </span>
                                        </div>
                                        {q.type === 'theory' && <p className="text-xs text-slate-500">{q.marked} of {q.seen} answers marked</p>}
                                        <div className="space-y-1">
                                            {q.options.map((o, i) => {
                                                const share = q.seen ? Math.round((q.picks[i] / q.seen) * 100) : 0;
                                                return (
                                                    <div key={i} className="flex items-center gap-2">
                                                        <span className={`w-28 truncate sm:w-72 ${i === q.correct_option ? 'font-medium text-emerald-700 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-300'}`}>{LETTERS[i]}. {o}</span>
                                                        <div className="h-2 flex-1 rounded bg-slate-100 dark:bg-white/[0.06]">
                                                            <div className={`h-2 rounded ${i === q.correct_option ? 'bg-emerald-500' : 'bg-slate-400'}`} style={{ width: `${share}%` }} />
                                                        </div>
                                                        <span className="w-16 text-right text-xs tabular-nums text-slate-500">{q.picks[i]} ({share}%)</span>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                        <p className="text-xs text-slate-500">{q.seen} people got this question{q.topic ? ` · ${q.topic}` : ''}</p>
                                    </li>
                                ))}
                            </ol>
                        )}
                    </Panel>
                )}
            </div>

            {timeFor && <ExtraTime url={`${url}/attempts/${timeFor.attempt_id}/time`} name={timeFor.name} onClose={() => setTimeFor(null)} />}
            {adding && <AddApplicants url={`${url}/candidates`} applicants={applicants} onClose={() => setAdding(false)} />}
            {copying && <CopyToResults url={`${url}/copy-to-results`} parts={scoreParts} term={test.term} onClose={() => setCopying(false)} />}
        </AppLayout>
    );
}

function ExtraTime({ url, name, onClose }: { url: string; name: string; onClose: () => void }) {
    const form = useForm({ minutes: '10' });
    return (
        <Dialog open onOpenChange={o => !o && onClose()}>
            <DialogContent className="sm:max-w-sm">
                <DialogHeader><DialogTitle>More time for {name}</DialogTitle></DialogHeader>
                <form onSubmit={e => { e.preventDefault(); form.post(url, { preserveScroll: true, onSuccess: onClose }); }} className="space-y-4">
                    <p className="text-sm text-slate-600 dark:text-slate-300">For example after a power cut. If their time already ran out, their test opens again and they carry on from where they stopped.</p>
                    <div className="space-y-1.5">
                        <Label htmlFor="minutes">Extra minutes</Label>
                        <Input id="minutes" type="number" min={1} max={180} value={form.data.minutes} onChange={e => form.setData('minutes', e.target.value)} />
                        {form.errors.minutes && <p className="text-xs text-red-500">{form.errors.minutes}</p>}
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onClose}>Cancel</Button>
                        <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Give time</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function CopyToResults({ url, parts, term, onClose }: { url: string; parts: { id: number; label: string }[]; term: string | null; onClose: () => void }) {
    const form = useForm({ component_id: parts[0] ? String(parts[0].id) : '' });
    const items = parts.map(p => ({ value: String(p.id), label: p.label }));
    return (
        <Dialog open onOpenChange={o => !o && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader><DialogTitle>Send scores to term results</DialogTitle></DialogHeader>
                <form onSubmit={e => { e.preventDefault(); form.post(url, { preserveScroll: true, onSuccess: onClose }); }} className="space-y-4">
                    <p className="text-sm text-slate-600 dark:text-slate-300">
                        Each student's percentage is turned into marks for the score part you choose, on the {term ?? 'chosen'} term results. For example 75% becomes 15 out of 20.
                        Students who did not sit the test are left as they are.
                    </p>
                    <div className="space-y-1.5">
                        <Label>Score part</Label>
                        <Select items={items} value={form.data.component_id} onValueChange={v => form.setData('component_id', v ?? '')}>
                            <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>{items.map(p => <SelectItem key={p.value} value={p.value}>{p.label}</SelectItem>)}</SelectContent>
                        </Select>
                        {form.errors.component_id && <p className="text-xs text-red-500">{form.errors.component_id}</p>}
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onClose}>Cancel</Button>
                        <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Send scores</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function AddApplicants({ url, applicants, onClose }: { url: string; applicants: Applicant[]; onClose: () => void }) {
    const [search, setSearch] = useState('');
    const form = useForm<{ inquiry_ids: number[] }>({ inquiry_ids: [] });
    const chosen = form.data.inquiry_ids;
    const shown = applicants.filter(a => !search || `${a.student_name} ${a.class_interested ?? ''} ${a.guardian_phone ?? ''}`.toLowerCase().includes(search.toLowerCase()));
    const toggle = (id: number, on: boolean) => form.setData('inquiry_ids', on ? [...chosen, id] : chosen.filter(x => x !== id));
    const allShown = shown.length > 0 && shown.every(a => chosen.includes(a.id));
    const errors = form.errors as Record<string, string | undefined>;

    return (
        <Dialog open onOpenChange={o => !o && onClose()}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader><DialogTitle>Add applicants</DialogTitle></DialogHeader>
                <form onSubmit={e => { e.preventDefault(); form.post(url, { preserveScroll: true, onSuccess: onClose }); }} className="space-y-3">
                    <p className="text-sm text-slate-600 dark:text-slate-300">From your admission inquiries that are not yet enrolled or dropped. Each one gets an exam number and a PIN, and an entrance exam line on their admission record.</p>
                    {applicants.length === 0 ? (
                        <p className="rounded-lg bg-slate-100 px-3 py-3 text-sm dark:bg-white/[0.06]">Every open inquiry is already on this exam. Add new applicants under Admissions first.</p>
                    ) : (
                        <>
                            <Input placeholder="Search by name, class or phone" value={search} onChange={e => setSearch(e.target.value)} />
                            <label className="flex items-center gap-2 text-sm font-medium">
                                <Checkbox checked={allShown} onCheckedChange={v => form.setData('inquiry_ids', v ? [...new Set([...chosen, ...shown.map(a => a.id)])] : chosen.filter(id => !shown.some(a => a.id === id)))} />
                                Choose all {search ? 'shown' : ''} ({shown.length})
                            </label>
                            <ul className="max-h-72 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200 dark:divide-white/[0.06] dark:border-white/10">
                                {shown.map(a => (
                                    <li key={a.id}>
                                        <label className="flex cursor-pointer items-center gap-3 px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-white/[0.03]">
                                            <Checkbox checked={chosen.includes(a.id)} onCheckedChange={v => toggle(a.id, !!v)} />
                                            <span className="min-w-0 flex-1"><span className="font-medium">{a.student_name}</span>{a.class_interested && <span className="text-slate-500"> · for {a.class_interested}</span>}</span>
                                            {a.guardian_phone && <span className="text-xs text-slate-500">{a.guardian_phone}</span>}
                                        </label>
                                    </li>
                                ))}
                            </ul>
                        </>
                    )}
                    {Object.values(errors).filter(Boolean).slice(0, 1).map((e, i) => <p key={i} className="text-xs text-red-500">{e}</p>)}
                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onClose}>Cancel</Button>
                        <Button type="submit" disabled={form.processing || chosen.length === 0} className="bg-indigo-600 text-white hover:bg-indigo-700">Add {chosen.length || ''}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
