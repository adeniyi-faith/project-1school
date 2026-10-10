import { useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { EmptyState, PageHeader, Panel, Segmented, StatStrip } from '@/components/app/kit';
import { AttemptPill, CbtStatusPill, examTime } from '@/components/cbt/CbtParts';
import { ArrowLeft, Clock, Download, Pencil, PlayCircle, RotateCcw, Send, StopCircle, Trash2, Users } from 'lucide-react';
import type { CbtResultRow, CbtTestRow } from '@/Types';

interface QuestionReport { id: number; text: string; topic: string | null; options: string[]; correct_option: number; seen: number; right: number; percent: number | null; picks: number[] }

interface Props {
    test: CbtTestRow & { instructions: string | null; access_code: string | null; sections: { title: string; bank: string | null; topic: string | null; difficulty: string | null; count: number }[] };
    problems: string[];
    results: CbtResultRow[];
    summary: { expected: number; finished: number; writing: number; average: number | null; highest: number | null; lowest: number | null; passed: number | null };
    questionReport: QuestionReport[];
    scoreParts: { id: number; label: string }[];
    canManage: boolean;
    canCopy: boolean;
}

const LETTERS = 'ABCDEF';

export default function CbtTestPage({ test, problems, results, summary, questionReport, scoreParts, canManage, canCopy }: Props) {
    const [tab, setTab] = useState<'people' | 'questions'>('people');
    const [timeFor, setTimeFor] = useState<CbtResultRow | null>(null);
    const [copying, setCopying] = useState(false);
    const errors = usePage().props.errors as Record<string, string>;
    const statusProblems = Object.entries(errors).filter(([k]) => k.startsWith('status')).map(([, v]) => v);
    const url = `/school/cbt/tests/${test.id}`;
    const setStatus = (status: string) => router.post(`${url}/status`, { status }, { preserveScroll: true });

    const actions = (
        <div className="flex flex-wrap gap-2">
            <a href={`${url}/export`} className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                <Download className="size-4" /> Excel
            </a>
            {canManage && <Link href={`${url}/edit`} className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200"><Pencil className="size-4" /> Edit</Link>}
            {canManage && test.status === 'draft' && <Button onClick={() => setStatus('published')} className="bg-emerald-600 text-white hover:bg-emerald-700"><PlayCircle className="size-4" /> Open to students</Button>}
            {canManage && test.status === 'published' && <Button variant="outline" onClick={() => confirm('Close this test? Nobody will be able to start it.') && setStatus('closed')}><StopCircle className="size-4" /> Close</Button>}
            {canManage && test.status === 'closed' && <Button variant="outline" onClick={() => setStatus('published')}><PlayCircle className="size-4" /> Open again</Button>}
        </div>
    );

    return (
        <AppLayout breadcrumbs={[{ label: 'CBT' }, { label: 'Tests', href: '/school/cbt/tests' }, { label: test.title }]}>
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <Link href="/school/cbt/tests" className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800 dark:hover:text-slate-200"><ArrowLeft className="size-4" /> All tests</Link>
                <PageHeader title={test.title} description={`${[test.class, test.subject, test.term].filter(Boolean).join(' · ')} · ${test.questions} questions in ${test.duration_minutes} minutes`} actions={actions} />
                <div className="md:hidden">{actions}</div>

                {(problems.length > 0 || statusProblems.length > 0) && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                        <p className="font-medium">Before this test can be opened:</p>
                        <ul className="mt-1 list-disc pl-5">{(statusProblems.length ? statusProblems : problems).map((p, i) => <li key={i}>{p}</li>)}</ul>
                    </div>
                )}

                <Panel>
                    <dl className="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-3">
                        <div><dt className="text-slate-500">Status</dt><dd className="mt-0.5"><CbtStatusPill status={test.status} /></dd></div>
                        <div><dt className="text-slate-500">Opens</dt><dd className="mt-0.5 font-medium">{examTime(test.opens_at)}</dd></div>
                        <div><dt className="text-slate-500">Closes</dt><dd className="mt-0.5 font-medium">{test.closes_at ? examTime(test.closes_at) : 'When you close it'}</dd></div>
                        <div><dt className="text-slate-500">Start code</dt><dd className="mt-0.5 font-mono font-medium">{test.access_code ?? (test.has_code ? 'Set' : 'None')}</dd></div>
                        <div><dt className="text-slate-500">Leaving the page</dt><dd className="mt-0.5 font-medium">{test.max_page_leaves ? `Handed in after ${test.max_page_leaves} times` : 'Counted only'}</dd></div>
                        <div><dt className="text-slate-500">Pass mark</dt><dd className="mt-0.5 font-medium">{test.pass_mark != null ? `${test.pass_mark}%` : 'None'}</dd></div>
                    </dl>
                    <div className="mt-4 border-t border-slate-100 pt-3 text-sm dark:border-white/[0.06]">
                        <p className="mb-1 text-slate-500">Questions</p>
                        <ul className="space-y-0.5">
                            {test.sections.map((s, i) => (
                                <li key={i}><span className="font-medium">{s.title}:</span> {s.count} from {s.bank ?? 'a deleted bank'}{s.topic ? `, topic ${s.topic}` : ''}{s.difficulty ? `, ${s.difficulty}` : ''}</li>
                            ))}
                        </ul>
                    </div>
                    {canManage && test.status === 'draft' && summary.finished + summary.writing === 0 && (
                        <Button variant="ghost" size="sm" className="mt-3 text-red-600" onClick={() => confirm('Delete this test?') && router.delete(url)}><Trash2 className="size-4" /> Delete test</Button>
                    )}
                </Panel>

                <StatStrip items={[
                    { label: 'Handed in', value: `${summary.finished} of ${summary.expected}`, hint: summary.writing ? `${summary.writing} writing now` : undefined },
                    { label: 'Average', value: summary.average != null ? `${summary.average}%` : '–' },
                    { label: 'Highest / lowest', value: summary.highest != null ? `${summary.highest}% / ${summary.lowest}%` : '–' },
                    { label: 'Passed', value: summary.passed != null ? `${summary.passed} of ${summary.finished}` : '–', hint: test.pass_mark != null ? `Pass mark ${test.pass_mark}%` : 'No pass mark set' },
                ]} />

                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Segmented label="Show" value={tab} onChange={setTab} options={[{ value: 'people', label: 'Students' }, { value: 'questions', label: 'Questions, hardest first' }]} />
                    {canCopy && scoreParts.length > 0 && summary.finished > 0 && (
                        <Button variant="outline" onClick={() => setCopying(true)}><Send className="size-4" /> Send scores to term results</Button>
                    )}
                </div>

                {tab === 'people' ? (
                    <Panel flush>
                        {results.length === 0 ? (
                            <div className="px-5 pb-5"><EmptyState icon={Users} title="No students" text="There are no active students in this class." /></div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="text-left text-xs text-slate-500">
                                        <tr className="border-b border-slate-100 dark:border-white/[0.06]">
                                            <th className="px-5 py-2 font-medium">Student</th>
                                            <th className="px-3 py-2 font-medium">Status</th>
                                            <th className="px-3 py-2 text-right font-medium">Score</th>
                                            <th className="px-3 py-2 text-right font-medium">Answered</th>
                                            <th className="px-3 py-2 text-right font-medium">Left page</th>
                                            <th className="px-3 py-2 text-right font-medium">Minutes</th>
                                            {canManage && <th className="px-5 py-2" />}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                                        {results.map(r => (
                                            <tr key={r.student_id}>
                                                <td className="px-5 py-2.5"><p className="font-medium text-slate-900 dark:text-white">{r.name}</p><p className="text-xs text-slate-500">{r.admission_no}</p></td>
                                                <td className="px-3 py-2.5"><AttemptPill status={r.status} /></td>
                                                <td className="px-3 py-2.5 text-right tabular-nums">
                                                    {r.percent != null ? <><span className={test.pass_mark != null && r.percent < test.pass_mark ? 'text-red-600' : 'font-medium'}>{r.percent}%</span> <span className="text-xs text-slate-500">({r.score}/{r.max_score})</span></> : '–'}
                                                </td>
                                                <td className="px-3 py-2.5 text-right tabular-nums">{r.questions ? `${r.answered}/${r.questions}` : '–'}</td>
                                                <td className={`px-3 py-2.5 text-right tabular-nums ${r.page_leaves ? 'text-amber-700 dark:text-amber-400' : ''}`}>{r.attempt_id ? r.page_leaves : '–'}</td>
                                                <td className="px-3 py-2.5 text-right tabular-nums">{r.minutes ?? '–'}</td>
                                                {canManage && (
                                                    <td className="whitespace-nowrap px-5 py-2.5 text-right">
                                                        {r.attempt_id && (
                                                            <>
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
                                            <p className="whitespace-pre-line font-medium text-slate-900 dark:text-white">{q.text}</p>
                                            <span className={`shrink-0 font-semibold tabular-nums ${(q.percent ?? 100) < 40 ? 'text-red-600' : 'text-slate-700 dark:text-slate-200'}`}>{q.percent ?? '–'}% right</span>
                                        </div>
                                        <div className="space-y-1">
                                            {q.options.map((o, i) => {
                                                const share = q.seen ? Math.round((q.picks[i] / q.seen) * 100) : 0;
                                                return (
                                                    <div key={i} className="flex items-center gap-2">
                                                        <span className={`w-48 truncate sm:w-72 ${i === q.correct_option ? 'font-medium text-emerald-700 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-300'}`}>{LETTERS[i]}. {o}</span>
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
