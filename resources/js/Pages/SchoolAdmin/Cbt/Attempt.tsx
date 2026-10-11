import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageHeader, Panel, Pill } from '@/components/app/kit';
import { ArrowLeft, Check, Flag, PenLine, X } from 'lucide-react';

interface Option { letter: string; text: string; chosen: boolean; correct: boolean }
interface Line {
    number: number; id: number; type: 'objective' | 'theory'; text: string; image: string | null; max: number; flagged: boolean; explanation: string | null;
    options: Option[]; result: 'right' | 'wrong' | 'blank' | null; marks: number | null;
    written: string | null; marking_guide: string | null; comment: string | null;
}
interface Props {
    test: { id: number; title: string; pass_mark: number | null };
    attempt: {
        id: number; name: string; number: string | null; status: 'in_progress' | 'submitted' | 'timed_out'; score: number | null; max_score: number | null; percent: number | null;
        needs_marking: boolean; page_leaves: number; minutes: number | null; started_at: string | null; submitted_at: string | null;
    };
    lines: Line[];
    counts: { right: number; wrong: number; blank: number; theory: number; theory_waiting: number };
    canManage: boolean;
}

const STATUS: Record<string, string> = { in_progress: 'Still writing', submitted: 'Handed in', timed_out: 'Time ran out' };

const when = (iso: string | null) => (iso ? new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' }) : '–');

/** What one student actually did in a test: every question, their answer, the right answer and the marks */
export default function CbtAttempt({ test, attempt, lines, counts, canManage }: Props) {
    const url = `/school/cbt/tests/${test.id}`;
    const passed = attempt.percent != null && test.pass_mark != null ? attempt.percent >= test.pass_mark : null;

    return (
        <AppLayout breadcrumbs={[{ label: 'CBT' }, { label: 'Tests', href: '/school/cbt/tests' }, { label: test.title, href: url }, { label: attempt.name }]}>
            <div className="mx-auto max-w-4xl space-y-6">
                <Link href={url} className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800 dark:hover:text-slate-200"><ArrowLeft className="size-4" /> Back to the test</Link>
                <PageHeader title={attempt.name} description={`${test.title}${attempt.number ? ` · ${attempt.number}` : ''}`} />

                <Panel>
                    <div className="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p className="text-xs text-slate-500">Score</p>
                            <p className="text-2xl font-semibold tabular-nums">
                                {attempt.percent != null ? `${attempt.percent}%` : attempt.needs_marking ? 'To mark' : '–'}
                            </p>
                            <p className="text-xs text-slate-500">{attempt.score ?? 0} of {attempt.max_score ?? 0} marks{attempt.needs_marking ? ' so far' : ''}</p>
                            {passed != null && <Pill tone={passed ? 'good' : 'bad'} className="mt-1">{passed ? 'Passed' : 'Did not pass'}</Pill>}
                        </div>
                        <div>
                            <p className="text-xs text-slate-500">Questions</p>
                            <p className="text-sm">{counts.right} right, {counts.wrong} wrong, {counts.blank} left blank</p>
                            {counts.theory > 0 && <p className="text-sm">{counts.theory} theory{counts.theory_waiting ? `, ${counts.theory_waiting} not marked yet` : ''}</p>}
                        </div>
                        <div>
                            <p className="text-xs text-slate-500">Time</p>
                            <p className="text-sm">{STATUS[attempt.status]}{attempt.minutes != null ? ` after ${attempt.minutes} min` : ''}</p>
                            <p className="text-xs text-slate-500">Started {when(attempt.started_at)}</p>
                        </div>
                        <div>
                            <p className="text-xs text-slate-500">Left the test page</p>
                            <p className={`text-sm ${attempt.page_leaves ? 'font-medium text-amber-700 dark:text-amber-400' : ''}`}>{attempt.page_leaves} time{attempt.page_leaves === 1 ? '' : 's'}</p>
                        </div>
                    </div>
                    {canManage && counts.theory_waiting > 0 && (
                        <div className="border-t border-slate-100 px-5 py-3 dark:border-white/[0.06]">
                            <Link href={`${url}/marking`} className="inline-flex h-8 items-center gap-1.5 rounded-lg bg-indigo-600 px-3 text-sm font-medium text-white hover:bg-indigo-700"><PenLine className="size-4" /> Mark theory answers</Link>
                        </div>
                    )}
                </Panel>

                <ol className="space-y-4">
                    {lines.map(l => (
                        <li key={l.id}>
                            <Panel>
                                <div className="space-y-3 p-5">
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <p className="text-sm font-medium text-slate-500">
                                            Question {l.number}
                                            {l.flagged && <span className="ml-2 inline-flex items-center gap-1 text-amber-700 dark:text-amber-400"><Flag className="size-3.5" /> flagged by the student</span>}
                                        </p>
                                        <div className="flex items-center gap-2">
                                            {l.type === 'objective' && l.result === 'right' && <Pill tone="good">Right</Pill>}
                                            {l.type === 'objective' && l.result === 'wrong' && <Pill tone="bad">Wrong</Pill>}
                                            {l.type === 'objective' && l.result === 'blank' && <Pill tone="neutral">Not answered</Pill>}
                                            <span className="text-sm tabular-nums">{l.marks == null ? 'Not marked' : `${l.marks} of ${l.max}`} mark{l.max === 1 ? '' : 's'}</span>
                                        </div>
                                    </div>
                                    <p className="whitespace-pre-line break-words text-slate-900 dark:text-white">{l.text}</p>
                                    {l.image && <img src={l.image} alt="" className="max-h-56 max-w-full rounded border border-slate-200 dark:border-white/10" />}

                                    {l.type === 'objective' ? (
                                        <ul className="space-y-1.5">
                                            {l.options.map(o => (
                                                <li key={o.letter} className={`flex items-start gap-2 rounded-lg border px-3 py-2 text-sm ${o.correct ? 'border-emerald-400 bg-emerald-50 dark:border-emerald-500/50 dark:bg-emerald-500/10' : o.chosen ? 'border-red-300 bg-red-50 dark:border-red-500/50 dark:bg-red-500/10' : 'border-slate-200 dark:border-white/10'}`}>
                                                    <span className="font-medium">{o.letter}.</span>
                                                    <span className="min-w-0 flex-1 break-words">{o.text}</span>
                                                    {o.chosen && <span className="shrink-0 text-xs text-slate-600 dark:text-slate-300">Their answer</span>}
                                                    {o.correct && <Check className="size-4 shrink-0 text-emerald-600" aria-label="Right answer" />}
                                                    {o.chosen && !o.correct && <X className="size-4 shrink-0 text-red-600" aria-label="Wrong" />}
                                                </li>
                                            ))}
                                        </ul>
                                    ) : (
                                        <div className="space-y-3">
                                            <div>
                                                <p className="mb-1 text-xs text-slate-500">What they wrote</p>
                                                <p className="whitespace-pre-line break-words rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm dark:border-white/10 dark:bg-white/[0.03]">
                                                    {l.written ?? <span className="text-slate-500">Nothing written</span>}
                                                </p>
                                            </div>
                                            {l.marking_guide && (
                                                <div>
                                                    <p className="mb-1 text-xs text-slate-500">Marking guide</p>
                                                    <p className="whitespace-pre-line break-words text-sm text-slate-700 dark:text-slate-300">{l.marking_guide}</p>
                                                </div>
                                            )}
                                            {l.comment && (
                                                <div>
                                                    <p className="mb-1 text-xs text-slate-500">Teacher's comment</p>
                                                    <p className="break-words text-sm">{l.comment}</p>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                    {l.explanation && <p className="break-words text-sm text-slate-600 dark:text-slate-400"><span className="font-medium">Explanation:</span> {l.explanation}</p>}
                                </div>
                            </Panel>
                        </li>
                    ))}
                </ol>
            </div>
        </AppLayout>
    );
}
