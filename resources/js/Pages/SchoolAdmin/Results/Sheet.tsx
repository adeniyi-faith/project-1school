import { useMemo, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { EmptyState, PageHeader, Panel } from '@/components/app/kit';
import { ResultStatusPill } from '@/components/results/ResultStatus';
import { ordinal } from '@/lib/format';
import { cn } from '@/lib/utils';
import { ArrowLeft, Check, ClipboardCheck, FileText } from 'lucide-react';
import type { AssessmentComponent, BehaviourTrait, PageProps, ResultAction, ResultStatus } from '@/Types';

interface StudentRow { id: number; name: string; admission_no: string | null }
interface SubjectResult { total: number; grade: string | null; remarks: string | null; subject_position: number | null }
interface Summary {
    student_id: number; name: string | null; admission_no: string | null;
    subjects_count: number; total_score: number; average: number; position: number | null; class_size: number;
}

interface Props {
    sheet: {
        id: number; status: ResultStatus; version: number; class_average: number | null; computed_at: string | null;
        term: string; class_name: string | null; steps: Record<'submitted' | 'approved' | 'published' | 'locked', string | null>;
    };
    actions: ResultAction[];
    canPrint: boolean;
    canEnter: boolean;
    tab: 'scores' | 'behaviour' | 'results';
    subjects: { id: number; name: string }[];
    subjectId: number | null;
    components: Required<AssessmentComponent>[];
    students: StudentRow[];
    scores: Record<string, Record<string, number | null>>;
    subjectResults: Record<string, SubjectResult>;
    summaries: Summary[];
    traits: BehaviourTrait[];
    ratings: Record<string, Record<string, number>>;
    ratingLabels: Record<string, string>;
}

const ACTION_LABELS: Record<ResultAction, { label: string; confirm?: string; primary?: boolean }> = {
    submit: { label: 'Submit for approval', confirm: 'Submit these results? Scores cannot be changed after this unless they are sent back.', primary: true },
    approve: { label: 'Approve', primary: true },
    return: { label: 'Send back for changes', confirm: 'Send these results back so scores can be changed?' },
    publish: { label: 'Publish to parents and students', confirm: 'Publish? Parents and students will be able to see these results.', primary: true },
    unpublish: { label: 'Hide from parents again' },
    lock: { label: 'Lock', confirm: 'Lock these results? Only someone who can lock results can unlock them.' },
    unlock: { label: 'Unlock' },
};

const STEPS: { key: 'draft' | 'submitted' | 'approved' | 'published' | 'locked'; label: string }[] = [
    { key: 'draft', label: 'Draft' },
    { key: 'submitted', label: 'Submitted' },
    { key: 'approved', label: 'Approved' },
    { key: 'published', label: 'Published' },
    { key: 'locked', label: 'Locked' },
];

export default function ResultSheet(props: Props) {
    const { sheet, actions, tab } = props;
    const base = `/school/results/${sheet.id}`;
    const reached = STEPS.findIndex(s => s.key === sheet.status);

    function act(action: ResultAction) {
        const meta = ACTION_LABELS[action];
        if (meta.confirm && !confirm(meta.confirm)) return;
        router.post(`${base}/status`, { action }, { preserveScroll: true });
    }

    const linkClass = 'inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 shadow-xs hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200';
    const headerActions = (
        <div className="flex flex-wrap gap-2">
            <Link href="/school/results" className={linkClass}><ArrowLeft className="size-4" /> All classes</Link>
            {props.canPrint && <Link href={`${base}/report-cards`} className={linkClass}><FileText className="size-4" /> Report cards</Link>}
        </div>
    );

    const tabs = [
        { key: 'scores', label: 'Scores' },
        { key: 'behaviour', label: 'Behaviour & skills' },
        { key: 'results', label: 'Results & positions' },
    ] as const;

    return (
        <AppLayout breadcrumbs={[{ label: 'Academic' }, { label: 'Term results', href: '/school/results' }, { label: sheet.class_name ?? 'Class' }]}>
            <div className="mx-auto max-w-6xl space-y-6 pb-24 md:pb-0">
                <PageHeader
                    title={`${sheet.class_name ?? 'Class'} results`}
                    description={sheet.term}
                    actions={headerActions}
                />
                {/* The header hides its buttons on phones, so they are repeated here */}
                <div className="md:hidden">{headerActions}</div>

                <Panel>
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <ol className="flex flex-wrap items-center gap-x-1 gap-y-2 text-xs" aria-label="Approval steps">
                            {STEPS.map((s, i) => (
                                <li key={s.key} className="flex items-center gap-1">
                                    <span className={cn(
                                        'inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-medium',
                                        i < reached && 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
                                        i === reached && 'bg-indigo-600 text-white',
                                        i > reached && 'bg-slate-100 text-slate-500 dark:bg-white/[0.06] dark:text-slate-400',
                                    )} aria-current={i === reached ? 'step' : undefined}>
                                        {i < reached && <Check className="size-3" />}{s.label}
                                    </span>
                                    {i < STEPS.length - 1 && <span className="text-slate-300 dark:text-slate-600">›</span>}
                                </li>
                            ))}
                        </ol>
                        {actions.length > 0 && (
                            <div className="flex flex-wrap gap-2">
                                {actions.map(a => (
                                    <Button key={a} size="sm" variant={ACTION_LABELS[a].primary ? 'default' : 'outline'} onClick={() => act(a)}
                                        className={ACTION_LABELS[a].primary ? 'bg-indigo-600 text-white hover:bg-indigo-700' : undefined}>
                                        {ACTION_LABELS[a].label}
                                    </Button>
                                ))}
                            </div>
                        )}
                    </div>
                    <p className="mt-3 text-xs text-slate-500 dark:text-slate-400">
                        {sheet.status === 'draft' && 'Scores can be entered and changed. Results are worked out again each time you save.'}
                        {sheet.status === 'submitted' && 'Waiting for the principal or admin to approve. Scores cannot be changed now.'}
                        {sheet.status === 'approved' && 'Approved. Parents and students will see these results once they are published.'}
                        {sheet.status === 'published' && 'Parents and students can see these results.'}
                        {sheet.status === 'locked' && 'Locked. These results are final.'}
                    </p>
                </Panel>

                <nav className="flex gap-1 border-b border-slate-200 dark:border-white/[0.08]" aria-label="Sections">
                    {tabs.map(t => (
                        <Link key={t.key} href={`${base}?tab=${t.key}${props.subjectId ? `&subject_id=${props.subjectId}` : ''}`} preserveScroll
                            className={cn('-mb-px border-b-2 px-3 py-2 text-sm font-medium',
                                tab === t.key ? 'border-indigo-600 text-indigo-700 dark:text-indigo-300' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200')}>
                            {t.label}
                        </Link>
                    ))}
                </nav>

                {props.students.length === 0 ? (
                    <Panel><EmptyState icon={ClipboardCheck} title="No students in this class" text="Add students to the class first." /></Panel>
                ) : tab === 'scores' ? (
                    <ScoresTab key={`${props.subjectId}-${sheet.version}`} {...props} />
                ) : tab === 'behaviour' ? (
                    <BehaviourTab key={sheet.version} {...props} />
                ) : (
                    <ResultsTab {...props} />
                )}
            </div>
        </AppLayout>
    );
}

function ScoresTab({ sheet, canEnter, subjects, subjectId, components, students, scores, subjectResults }: Props) {
    const { errors } = usePage<PageProps>().props;
    const [values, setValues] = useState<Record<string, string>>(() => {
        const v: Record<string, string> = {};
        students.forEach(s => components.forEach(c => {
            const score = scores[s.id]?.[c.id];
            v[`${s.id}-${c.id}`] = score === null || score === undefined ? '' : String(score);
        }));
        return v;
    });
    const [saving, setSaving] = useState(false);
    const [dirty, setDirty] = useState(false);

    const subjectItems = subjects.map(s => ({ value: String(s.id), label: s.name }));
    const firstError = Object.entries(errors).find(([k]) => k.startsWith('scores'))?.[1];

    function rowTotal(studentId: number) {
        let sum = 0; let any = false;
        components.forEach(c => {
            const v = values[`${studentId}-${c.id}`];
            if (v !== '' && v !== undefined) { sum += Number(v) || 0; any = true; }
        });
        return any ? Math.round(sum * 100) / 100 : null;
    }

    function save() {
        if (!subjectId) return;
        setSaving(true);
        router.post(`/school/results/${sheet.id}/scores`, {
            subject_id: subjectId,
            scores: students.flatMap(s => components.map(c => {
                const v = values[`${s.id}-${c.id}`];
                return { student_id: s.id, component_id: c.id, score: v === '' ? null : Number(v) };
            })),
        }, { preserveScroll: true, onSuccess: () => setDirty(false), onFinish: () => setSaving(false) });
    }

    function changeSubject(v: string | null) {
        if (!v) return;
        if (dirty && !confirm('You have scores that are not saved. Leave this subject anyway?')) return;
        router.get(`/school/results/${sheet.id}`, { tab: 'scores', subject_id: v }, { preserveScroll: true });
    }

    if (subjects.length === 0) {
        return <Panel><EmptyState icon={ClipboardCheck} title="No subjects in this class" text="Add subjects to the class first." /></Panel>;
    }

    return (
        <Panel flush
            title={
                <div className="w-64 max-w-full">
                    <Select items={subjectItems} value={subjectId ? String(subjectId) : ''} onValueChange={changeSubject}>
                        <SelectTrigger className="w-full" aria-label="Subject"><SelectValue placeholder="Choose a subject" /></SelectTrigger>
                        <SelectContent>{subjectItems.map(s => <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>)}</SelectContent>
                    </Select>
                </div>
            }
            action={canEnter && (
                <Button onClick={save} disabled={saving || !dirty} className="bg-indigo-600 text-white hover:bg-indigo-700">
                    {saving ? 'Saving…' : 'Save scores'}
                </Button>
            )}
        >
            {firstError && <p className="px-5 pb-3 text-sm text-red-600">{firstError}</p>}
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-slate-100 text-left text-xs text-slate-500 dark:border-white/[0.06] dark:text-slate-400">
                            <th className="sticky left-0 bg-white px-5 py-2 font-medium dark:bg-slate-900">Student</th>
                            {components.map(c => (
                                <th key={c.id} className="px-2 py-2 text-center font-medium" title={c.name}>{c.short_name} <span className="font-normal text-slate-400">/{Number(c.max_score)}</span></th>
                            ))}
                            <th className="px-2 py-2 text-right font-medium">Total</th>
                            <th className="px-2 py-2 text-right font-medium">Grade</th>
                            <th className="px-5 py-2 text-right font-medium">Position</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                        {students.map(s => {
                            const stored = subjectResults[s.id];
                            return (
                                <tr key={s.id}>
                                    <td className="sticky left-0 bg-white px-5 py-1.5 dark:bg-slate-900">
                                        <span className="block font-medium text-slate-900 dark:text-white">{s.name}</span>
                                        <span className="text-xs text-slate-400">{s.admission_no}</span>
                                    </td>
                                    {components.map(c => {
                                        const key = `${s.id}-${c.id}`;
                                        const over = values[key] !== '' && Number(values[key]) > Number(c.max_score);
                                        return (
                                            <td key={c.id} className="px-2 py-1.5 text-center">
                                                <input
                                                    type="number" inputMode="decimal" min={0} max={Number(c.max_score)} step="0.5"
                                                    disabled={!canEnter}
                                                    aria-label={`${c.short_name} for ${s.name}`}
                                                    value={values[key]}
                                                    onChange={e => { setValues(v => ({ ...v, [key]: e.target.value })); setDirty(true); }}
                                                    className={cn('h-8 w-16 rounded-md border bg-white px-2 text-center tabular-nums outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-slate-50 disabled:text-slate-500 dark:bg-white/[0.04] dark:disabled:bg-transparent',
                                                        over ? 'border-red-400' : 'border-slate-200 dark:border-white/10')}
                                                />
                                            </td>
                                        );
                                    })}
                                    <td className="px-2 py-1.5 text-right font-semibold tabular-nums text-slate-900 dark:text-white">{rowTotal(s.id) ?? '—'}</td>
                                    <td className="px-2 py-1.5 text-right font-semibold">{dirty ? '…' : stored?.grade ?? '—'}</td>
                                    <td className="px-5 py-1.5 text-right tabular-nums text-slate-600 dark:text-slate-400">{dirty ? '…' : ordinal(stored?.subject_position)}</td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
            <p className="px-5 py-3 text-xs text-slate-500 dark:text-slate-400">
                Leave a box empty if the student has no score for that part yet. Grades and positions update when you save.
            </p>
        </Panel>
    );
}

function BehaviourTab({ sheet, canEnter, students, traits, ratings, ratingLabels }: Props) {
    const [values, setValues] = useState<Record<string, string>>(() => {
        const v: Record<string, string> = {};
        students.forEach(s => traits.forEach(t => { v[`${s.id}-${t.id}`] = ratings[s.id]?.[t.id] ? String(ratings[s.id][t.id]) : ''; }));
        return v;
    });
    const [saving, setSaving] = useState(false);
    const [dirty, setDirty] = useState(false);
    const [domain, setDomain] = useState<'affective' | 'psychomotor'>('affective');
    const shown = useMemo(() => traits.filter(t => t.domain === domain), [traits, domain]);

    function save() {
        setSaving(true);
        router.post(`/school/results/${sheet.id}/ratings`, {
            ratings: students.flatMap(s => traits.map(t => {
                const v = values[`${s.id}-${t.id}`];
                return { student_id: s.id, trait_id: t.id, rating: v === '' ? null : Number(v) };
            })),
        }, { preserveScroll: true, onSuccess: () => setDirty(false), onFinish: () => setSaving(false) });
    }

    return (
        <Panel flush
            title={
                <div className="inline-flex rounded-lg border border-slate-200 p-0.5 text-sm dark:border-white/10" role="tablist">
                    {(['affective', 'psychomotor'] as const).map(d => (
                        <button key={d} type="button" role="tab" aria-selected={domain === d} onClick={() => setDomain(d)}
                            className={cn('rounded-md px-3 py-1 font-medium', domain === d ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 dark:text-slate-300')}>
                            {d === 'affective' ? 'Behaviour' : 'Skills'}
                        </button>
                    ))}
                </div>
            }
            description={`Rate each from 1 to 5: ${Object.entries(ratingLabels).sort(([a], [b]) => Number(b) - Number(a)).map(([n, l]) => `${n} ${l}`).join(', ')}.`}
            action={canEnter && (
                <Button onClick={save} disabled={saving || !dirty} className="bg-indigo-600 text-white hover:bg-indigo-700">
                    {saving ? 'Saving…' : 'Save ratings'}
                </Button>
            )}
        >
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-slate-100 text-left text-xs text-slate-500 dark:border-white/[0.06] dark:text-slate-400">
                            <th className="sticky left-0 bg-white px-5 py-2 font-medium dark:bg-slate-900">Student</th>
                            {shown.map(t => <th key={t.id} className="px-2 py-2 text-center font-medium">{t.name}</th>)}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                        {students.map(s => (
                            <tr key={s.id}>
                                <td className="sticky left-0 bg-white px-5 py-1.5 font-medium text-slate-900 dark:bg-slate-900 dark:text-white">{s.name}</td>
                                {shown.map(t => {
                                    const key = `${s.id}-${t.id}`;
                                    return (
                                        <td key={t.id} className="px-2 py-1.5 text-center">
                                            <select
                                                disabled={!canEnter}
                                                aria-label={`${t.name} for ${s.name}`}
                                                value={values[key]}
                                                onChange={e => { setValues(v => ({ ...v, [key]: e.target.value })); setDirty(true); }}
                                                className="h-8 rounded-md border border-slate-200 bg-white px-1.5 text-sm disabled:bg-slate-50 dark:border-white/10 dark:bg-slate-900"
                                            >
                                                <option value="">–</option>
                                                {[5, 4, 3, 2, 1].map(n => <option key={n} value={n}>{n}</option>)}
                                            </select>
                                        </td>
                                    );
                                })}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </Panel>
    );
}

function ResultsTab({ sheet, summaries, students }: Props) {
    if (summaries.length === 0) {
        return <Panel><EmptyState icon={ClipboardCheck} title="No results yet" text="Results appear here once scores are saved." /></Panel>;
    }

    return (
        <Panel flush
            title="Class positions"
            description={`${summaries.length} of ${students.length} students have results. Class average ${sheet.class_average ?? '—'}%. Students with the same average share a position.`}
        >
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-slate-100 text-left text-xs text-slate-500 dark:border-white/[0.06] dark:text-slate-400">
                            <th className="px-5 py-2 font-medium">Position</th>
                            <th className="px-2 py-2 font-medium">Student</th>
                            <th className="px-2 py-2 text-right font-medium">Subjects</th>
                            <th className="px-2 py-2 text-right font-medium">Total</th>
                            <th className="px-5 py-2 text-right font-medium">Average</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                        {summaries.map(s => (
                            <tr key={s.student_id}>
                                <td className="px-5 py-2 font-semibold tabular-nums text-slate-900 dark:text-white">{ordinal(s.position)}</td>
                                <td className="px-2 py-2">
                                    <span className="block text-slate-900 dark:text-white">{s.name}</span>
                                    <span className="text-xs text-slate-400">{s.admission_no}</span>
                                </td>
                                <td className="px-2 py-2 text-right tabular-nums">{s.subjects_count}</td>
                                <td className="px-2 py-2 text-right tabular-nums">{s.total_score}</td>
                                <td className="px-5 py-2 text-right font-medium tabular-nums">{s.average}%</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </Panel>
    );
}
