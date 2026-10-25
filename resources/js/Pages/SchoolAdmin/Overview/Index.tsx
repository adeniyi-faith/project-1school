import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Check } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { EmptyState, PageHeader, Panel, Pill, Segmented } from '@/components/app/kit';
import { naira } from '@/lib/format';

interface Line { subject: string; teacher: string | null; scored: number; expected: number }
interface ClassRow {
    id: number; name: string; students: number; attendance: number | null; fees_owed: number;
    sheet_status: string | null; average: number | null; subjects: number; subjects_done: number; unfinished: Line[];
}
interface TeacherRow {
    name: string; total: number; finished: number;
    lines: { class: string; subject: string; scored: number; expected: number; finished: boolean }[];
}
interface Props {
    terms: { id: number; name: string }[];
    termId: number | null;
    classes: ClassRow[];
    teachers: TeacherRow[];
}

const STATUS_TONE: Record<string, 'neutral' | 'info' | 'good' | 'warn'> = { draft: 'neutral', submitted: 'info', approved: 'info', published: 'good', locked: 'good' };

export default function Overview({ terms, termId, classes, teachers }: Props) {
    const [view, setView] = useState<'classes' | 'teachers'>('classes');
    const [onlyUnfinished, setOnlyUnfinished] = useState(false);

    const unfinishedTeachers = teachers.filter((t) => t.finished < t.total).length;
    const shownClasses = onlyUnfinished ? classes.filter((c) => c.subjects_done < c.subjects) : classes;
    const shownTeachers = onlyUnfinished ? teachers.filter((t) => t.finished < t.total) : teachers;

    return (
        <AppLayout title="Class & Teacher Overview">
            <div className="space-y-6">
                <PageHeader
                    title="Class and teacher overview"
                    description="Who has not finished entering scores this term. Nothing here can be changed; it is a read-only look."
                />

                <div className="flex flex-wrap items-center gap-3">
                    <Segmented label="View" value={view} onChange={setView} options={[
                        { value: 'classes', label: 'Classes', count: classes.length },
                        { value: 'teachers', label: 'Teachers', count: unfinishedTeachers },
                    ]} />
                    {terms.length > 1 && (
                        <select value={termId ?? ''} onChange={(e) => router.get('/school/overview', { term_id: e.target.value }, { preserveState: true })}
                            aria-label="Term" className="h-9 rounded-lg border border-slate-200 bg-white px-2 text-sm dark:border-white/10 dark:bg-slate-900">
                            {terms.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
                        </select>
                    )}
                    <label className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input type="checkbox" checked={onlyUnfinished} onChange={(e) => setOnlyUnfinished(e.target.checked)} className="size-4" />
                        Only show what is not finished
                    </label>
                </div>
                {view === 'teachers' && <p className="-mt-3 text-xs text-slate-500">The number on the Teachers button is how many teachers still have scores to enter.</p>}

                {view === 'classes' && (shownClasses.length === 0
                    ? <EmptyState icon={Check} title="Nothing to show" text={onlyUnfinished ? 'Every class has its scores in.' : 'No classes with students yet.'} />
                    : (
                        <div className="grid gap-4 lg:grid-cols-2">
                            {shownClasses.map((c) => (
                                <Panel key={c.id} title={c.name} description={`${c.students} students`}
                                    action={c.sheet_status ? <Pill tone={STATUS_TONE[c.sheet_status] ?? 'neutral'}>{c.sheet_status}</Pill> : <Pill>no results yet</Pill>}>
                                    <dl className="grid grid-cols-3 gap-3 text-sm">
                                        <div><dt className="text-xs text-slate-500">Present, last 7 days</dt><dd className="font-semibold tabular-nums">{c.attendance !== null ? `${c.attendance}%` : 'Not marked'}</dd></div>
                                        <div><dt className="text-xs text-slate-500">Class average</dt><dd className="font-semibold tabular-nums">{c.average !== null ? c.average : '—'}</dd></div>
                                        <div><dt className="text-xs text-slate-500">Fees owed</dt><dd className="font-semibold tabular-nums">{naira(c.fees_owed)}</dd></div>
                                    </dl>
                                    <p className="mt-3 text-sm text-slate-700 dark:text-slate-200">
                                        Subjects with scores in: <b>{c.subjects_done}</b> of <b>{c.subjects}</b>
                                    </p>
                                    {c.unfinished.length > 0 && (
                                        <ul className="mt-2 divide-y divide-slate-100 text-sm dark:divide-white/[0.06]">
                                            {c.unfinished.map((l) => (
                                                <li key={l.subject} className="flex items-baseline gap-2 py-1.5">
                                                    <span className="min-w-0 flex-1 truncate">{l.subject}<span className="text-slate-500">{l.teacher ? ` · ${l.teacher}` : ' · no teacher chosen'}</span></span>
                                                    <span className="shrink-0 text-xs tabular-nums text-amber-700 dark:text-amber-300">{l.scored} of {l.expected} students</span>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </Panel>
                            ))}
                        </div>
                    ))}

                {view === 'teachers' && (shownTeachers.length === 0
                    ? <EmptyState icon={Check} title="Nothing to show" text={onlyUnfinished ? 'Every teacher has finished entering scores.' : 'No teachers are linked to subjects yet.'} />
                    : (
                        <div className="grid gap-4 lg:grid-cols-2">
                            {shownTeachers.map((t) => (
                                <Panel key={t.name} title={t.name} description={`${t.finished} of ${t.total} subjects finished`}
                                    action={t.finished === t.total ? <Pill tone="good">All done</Pill> : <Pill tone="warn">{t.total - t.finished} to do</Pill>}>
                                    <ul className="divide-y divide-slate-100 text-sm dark:divide-white/[0.06]">
                                        {t.lines.filter((l) => !onlyUnfinished || !l.finished).map((l) => (
                                            <li key={`${l.class}-${l.subject}`} className="flex items-baseline gap-2 py-1.5">
                                                <span className="min-w-0 flex-1 truncate">{l.class} · {l.subject}</span>
                                                {l.finished
                                                    ? <span className="inline-flex items-center gap-1 text-xs text-emerald-700 dark:text-emerald-300"><Check className="size-3.5" /> Done</span>
                                                    : <span className="shrink-0 text-xs tabular-nums text-amber-700 dark:text-amber-300">{l.scored} of {l.expected} students</span>}
                                            </li>
                                        ))}
                                    </ul>
                                </Panel>
                            ))}
                        </div>
                    ))}
            </div>
        </AppLayout>
    );
}
