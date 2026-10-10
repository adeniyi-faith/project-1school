import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PageHeader, Panel, Pill } from '@/components/app/kit';
import { cn } from '@/lib/utils';
import { Pencil, Plus, Star, Trash2, X } from 'lucide-react';
import type { AssessmentComponent, AssessmentPresets, AssessmentScheme, GradeBand, GradingScheme } from '@/Types';

interface ClassRow { id: number; name: string; assessment_scheme_id: number | null; grading_scheme_id: number | null }
interface SubjectRow { id: number; name: string; class_name: string | null; assessment_scheme_id: number | null }

interface Props {
    assessmentSchemes: AssessmentScheme[];
    gradingSchemes: GradingScheme[];
    classes: ClassRow[];
    subjects: SubjectRow[];
    presets: AssessmentPresets;
    canEdit: boolean;
}

const DEFAULT = 'default';

function ErrorText({ children }: { children?: string }) {
    return children ? <p className="text-xs text-red-500">{children}</p> : null;
}

/** Errors for list rows come back as "components.2.max_score"; show the first one for the list */
function firstRowError(errors: Record<string, string>, prefix: string) {
    const key = Object.keys(errors).find(k => k.startsWith(`${prefix}.`));
    return key ? errors[key] : undefined;
}

export default function Assessment({ assessmentSchemes, gradingSchemes, classes, subjects, presets, canEdit }: Props) {
    const defaultScoreSetup = assessmentSchemes.find(s => s.is_default);
    const defaultGradeScale = gradingSchemes.find(s => s.is_default);

    // ───── score setup editor ─────
    const [scoreOpen, setScoreOpen] = useState(false);
    const [scoreEditing, setScoreEditing] = useState<AssessmentScheme | null>(null);
    const scoreForm = useForm<{ name: string; is_default: boolean; components: AssessmentComponent[] }>({ name: '', is_default: false, components: [] });
    const scoreTotal = scoreForm.data.components.reduce((sum, c) => sum + (Number(c.max_score) || 0), 0);

    function openScore(s: AssessmentScheme | null) {
        scoreForm.clearErrors();
        const start = s ?? { name: presets.assessment[0].name, is_default: false, components: presets.assessment[0].components };
        scoreForm.setData({ name: start.name, is_default: s?.is_default ?? false, components: start.components.map(c => ({ ...c })) });
        setScoreEditing(s);
        setScoreOpen(true);
    }

    function setPart(i: number, patch: Partial<AssessmentComponent>) {
        scoreForm.setData('components', scoreForm.data.components.map((c, j) => (j === i ? { ...c, ...patch } : c)));
    }

    function saveScore(e: React.FormEvent) {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setScoreOpen(false) };
        if (scoreEditing) scoreForm.put(`/school/academics/assessment-schemes/${scoreEditing.id}`, opts);
        else scoreForm.post('/school/academics/assessment-schemes', opts);
    }

    // ───── grade scale editor ─────
    const [gradeOpen, setGradeOpen] = useState(false);
    const [gradeEditing, setGradeEditing] = useState<GradingScheme | null>(null);
    const gradeForm = useForm<{ name: string; is_default: boolean; bands: GradeBand[] }>({ name: '', is_default: false, bands: [] });

    function openGrade(s: GradingScheme | null) {
        gradeForm.clearErrors();
        const start = s ?? { name: presets.grading[0].name, is_default: false, bands: presets.grading[0].bands };
        gradeForm.setData({ name: start.name, is_default: s?.is_default ?? false, bands: start.bands.map(b => ({ ...b })) });
        setGradeEditing(s);
        setGradeOpen(true);
    }

    function setBand(i: number, patch: Partial<GradeBand>) {
        gradeForm.setData('bands', gradeForm.data.bands.map((b, j) => (j === i ? { ...b, ...patch } : b)));
    }

    function saveGrade(e: React.FormEvent) {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setGradeOpen(false) };
        if (gradeEditing) gradeForm.put(`/school/academics/grading-schemes/${gradeEditing.id}`, opts);
        else gradeForm.post('/school/academics/grading-schemes', opts);
    }

    // ───── quick actions ─────
    function makeDefaultScore(s: AssessmentScheme) {
        router.put(`/school/academics/assessment-schemes/${s.id}`, { name: s.name, is_default: true, components: s.components }, { preserveScroll: true });
    }
    function makeDefaultGrade(s: GradingScheme) {
        router.put(`/school/academics/grading-schemes/${s.id}`, { name: s.name, is_default: true, bands: s.bands }, { preserveScroll: true });
    }
    function remove(url: string, name: string) {
        if (!confirm(`Delete "${name}"? Classes using it will go back to the school default.`)) return;
        router.delete(url, { preserveScroll: true });
    }
    function assignClass(c: ClassRow, patch: Partial<ClassRow>) {
        const next = { ...c, ...patch };
        router.put(`/school/academics/classes/${c.id}/scheme`, {
            assessment_scheme_id: next.assessment_scheme_id, grading_scheme_id: next.grading_scheme_id,
        }, { preserveScroll: true });
    }
    function assignSubject(subjectId: number, schemeId: number | null) {
        router.put(`/school/academics/subjects/${subjectId}/scheme`, { assessment_scheme_id: schemeId }, { preserveScroll: true });
    }

    // ───── subject exceptions ─────
    const exceptions = subjects.filter(s => s.assessment_scheme_id !== null);
    const [exceptionOpen, setExceptionOpen] = useState(false);
    const [exSubject, setExSubject] = useState('');
    const [exScheme, setExScheme] = useState('');
    const subjectLabel = (s: SubjectRow) => (s.class_name ? `${s.name} (${s.class_name})` : s.name);

    function saveException(e: React.FormEvent) {
        e.preventDefault();
        if (!exSubject || !exScheme) return;
        assignSubject(Number(exSubject), Number(exScheme));
        setExceptionOpen(false);
        setExSubject('');
        setExScheme('');
    }

    const scoreItems = [
        { value: DEFAULT, label: `School default${defaultScoreSetup ? ` (${defaultScoreSetup.name})` : ''}` },
        ...assessmentSchemes.map(s => ({ value: String(s.id), label: s.name })),
    ];
    const gradeItems = [
        { value: DEFAULT, label: `School default${defaultGradeScale ? ` (${defaultGradeScale.name})` : ''}` },
        ...gradingSchemes.map(s => ({ value: String(s.id), label: s.name })),
    ];

    return (
        <AppLayout breadcrumbs={[{ label: 'Academic' }, { label: 'Examinations', href: '/school/exams' }, { label: 'Scores & grades' }]}>
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader
                    title="Scores & grades"
                    description="Choose how a subject score is made up (CA tests, assignments, exam) and how scores turn into grades."
                />

                {/* ───── score setups ───── */}
                <Panel
                    title="Score setups"
                    description="The parts that make up a subject score in a term. The parts of each setup add up to 100."
                    action={canEdit && <Button size="sm" variant="outline" onClick={() => openScore(null)} className="inline-flex items-center gap-1.5"><Plus className="size-4" /> New setup</Button>}
                >
                    <div className="grid gap-3 sm:grid-cols-2">
                        {assessmentSchemes.map(s => (
                            <div key={s.id} className="rounded-lg border border-slate-200 p-4 dark:border-white/[0.08]">
                                <div className="flex items-start justify-between gap-2">
                                    <p className="text-sm font-semibold text-slate-900 dark:text-white">{s.name}</p>
                                    {s.is_default && <Pill tone="good">Default</Pill>}
                                </div>
                                <div className="mt-3 flex flex-wrap gap-1.5">
                                    {s.components.map(c => (
                                        <span key={c.id ?? c.short_name} className="rounded-md bg-slate-100 px-2 py-1 text-xs text-slate-700 dark:bg-white/[0.06] dark:text-slate-300" title={c.name}>
                                            {c.short_name} <span className="font-semibold tabular-nums">{Number(c.max_score)}</span>
                                        </span>
                                    ))}
                                </div>
                                {canEdit && (
                                    <div className="mt-3 flex flex-wrap gap-1">
                                        <Button size="sm" variant="ghost" onClick={() => openScore(s)} className="inline-flex items-center gap-1.5"><Pencil className="size-3.5" /> Edit</Button>
                                        {!s.is_default && <Button size="sm" variant="ghost" onClick={() => makeDefaultScore(s)} className="inline-flex items-center gap-1.5"><Star className="size-3.5" /> Make default</Button>}
                                        {!s.is_default && <Button size="sm" variant="ghost" onClick={() => remove(`/school/academics/assessment-schemes/${s.id}`, s.name)} className="inline-flex items-center gap-1.5 text-red-600"><Trash2 className="size-3.5" /> Delete</Button>}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </Panel>

                {/* ───── grade scales ───── */}
                <Panel
                    title="Grade scales"
                    description="How a final score (out of 100) becomes a grade and a remark on the report card."
                    action={canEdit && <Button size="sm" variant="outline" onClick={() => openGrade(null)} className="inline-flex items-center gap-1.5"><Plus className="size-4" /> New scale</Button>}
                >
                    <div className="grid gap-3 sm:grid-cols-2">
                        {gradingSchemes.map(s => (
                            <div key={s.id} className="rounded-lg border border-slate-200 p-4 dark:border-white/[0.08]">
                                <div className="flex items-start justify-between gap-2">
                                    <p className="text-sm font-semibold text-slate-900 dark:text-white">{s.name}</p>
                                    {s.is_default && <Pill tone="good">Default</Pill>}
                                </div>
                                <table className="mt-3 w-full text-xs">
                                    <tbody>
                                        {s.bands.map(b => (
                                            <tr key={b.id ?? b.grade} className="border-t border-slate-100 first:border-0 dark:border-white/[0.06]">
                                                <td className="py-1 pr-2 font-semibold text-slate-900 dark:text-white">{b.grade}</td>
                                                <td className="py-1 pr-2 tabular-nums text-slate-600 dark:text-slate-400">{b.min_marks} – {b.max_marks}</td>
                                                <td className="py-1 text-slate-500 dark:text-slate-400">{b.remarks}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                                {canEdit && (
                                    <div className="mt-3 flex flex-wrap gap-1">
                                        <Button size="sm" variant="ghost" onClick={() => openGrade(s)} className="inline-flex items-center gap-1.5"><Pencil className="size-3.5" /> Edit</Button>
                                        {!s.is_default && <Button size="sm" variant="ghost" onClick={() => makeDefaultGrade(s)} className="inline-flex items-center gap-1.5"><Star className="size-3.5" /> Make default</Button>}
                                        {!s.is_default && <Button size="sm" variant="ghost" onClick={() => remove(`/school/academics/grading-schemes/${s.id}`, s.name)} className="inline-flex items-center gap-1.5 text-red-600"><Trash2 className="size-3.5" /> Delete</Button>}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </Panel>

                {/* ───── per class ───── */}
                <Panel title="What each class uses" description="Leave a class on the school default unless it needs something different, e.g. nursery and primary on a simpler scale." flush>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-5">Class</TableHead>
                                <TableHead>Score setup</TableHead>
                                <TableHead className="pr-5">Grade scale</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {classes.map(c => (
                                <TableRow key={c.id}>
                                    <TableCell className="pl-5 font-medium text-slate-900 dark:text-white">{c.name}</TableCell>
                                    <TableCell className="min-w-48">
                                        <Select
                                            disabled={!canEdit}
                                            items={scoreItems}
                                            value={c.assessment_scheme_id ? String(c.assessment_scheme_id) : DEFAULT}
                                            onValueChange={v => assignClass(c, { assessment_scheme_id: v && v !== DEFAULT ? Number(v) : null })}
                                        >
                                            <SelectTrigger className="w-full" aria-label={`Score setup for ${c.name}`}><SelectValue /></SelectTrigger>
                                            <SelectContent>
                                                {scoreItems.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}
                                            </SelectContent>
                                        </Select>
                                    </TableCell>
                                    <TableCell className="min-w-48 pr-5">
                                        <Select
                                            disabled={!canEdit}
                                            items={gradeItems}
                                            value={c.grading_scheme_id ? String(c.grading_scheme_id) : DEFAULT}
                                            onValueChange={v => assignClass(c, { grading_scheme_id: v && v !== DEFAULT ? Number(v) : null })}
                                        >
                                            <SelectTrigger className="w-full" aria-label={`Grade scale for ${c.name}`}><SelectValue /></SelectTrigger>
                                            <SelectContent>
                                                {gradeItems.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}
                                            </SelectContent>
                                        </Select>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </Panel>

                {/* ───── subject exceptions ───── */}
                <Panel
                    title="Subjects with their own score setup"
                    description="For a subject that is scored differently from the rest of its class, e.g. a practical part."
                    action={canEdit && subjects.length > 0 && <Button size="sm" variant="outline" onClick={() => setExceptionOpen(true)} className="inline-flex items-center gap-1.5"><Plus className="size-4" /> Add subject</Button>}
                >
                    {exceptions.length === 0 ? (
                        <p className="text-sm text-slate-500 dark:text-slate-400">None. Every subject uses its class's score setup.</p>
                    ) : (
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {exceptions.map(s => (
                                <li key={s.id} className="flex items-center justify-between gap-3 py-2 text-sm">
                                    <span className="text-slate-900 dark:text-white">{subjectLabel(s)}</span>
                                    <span className="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                                        {assessmentSchemes.find(a => a.id === s.assessment_scheme_id)?.name}
                                        {canEdit && (
                                            <Button size="icon" variant="ghost" className="size-7" onClick={() => assignSubject(s.id, null)} aria-label={`Remove the exception for ${s.name}`}>
                                                <X className="size-4" />
                                            </Button>
                                        )}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </Panel>
            </div>

            {/* ───── score setup dialog ───── */}
            <Dialog open={scoreOpen} onOpenChange={setScoreOpen}>
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader><DialogTitle>{scoreEditing ? 'Edit score setup' : 'New score setup'}</DialogTitle></DialogHeader>
                    <form onSubmit={saveScore} className="mt-2 space-y-4">
                        {!scoreEditing && (
                            <div className="space-y-1.5">
                                <Label>Start from</Label>
                                <div className="flex flex-wrap gap-1.5">
                                    {presets.assessment.map(p => (
                                        <Button key={p.key} type="button" size="sm" variant="outline"
                                            onClick={() => scoreForm.setData(d => ({ ...d, name: p.name, components: p.components.map(c => ({ ...c })) }))}>
                                            {p.name}
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        )}
                        <div className="space-y-1.5">
                            <Label htmlFor="score-name">Name</Label>
                            <Input id="score-name" value={scoreForm.data.name} onChange={e => scoreForm.setData('name', e.target.value)} />
                            <ErrorText>{scoreForm.errors.name}</ErrorText>
                        </div>
                        <div className="space-y-2">
                            <div className="grid grid-cols-[1fr_6rem_5rem_2rem] gap-2 text-xs font-medium text-slate-500">
                                <span>Part</span><span>Short name</span><span>Marks</span><span />
                            </div>
                            {scoreForm.data.components.map((c, i) => (
                                <div key={i} className="grid grid-cols-[1fr_6rem_5rem_2rem] items-center gap-2">
                                    <Input aria-label="Part name" value={c.name} onChange={e => setPart(i, { name: e.target.value })} placeholder="e.g. Assignment" />
                                    <Input aria-label="Short name" value={c.short_name} onChange={e => setPart(i, { short_name: e.target.value })} placeholder="e.g. Assign" />
                                    <Input aria-label="Marks" type="number" min="1" max="100" step="0.5" value={c.max_score} onChange={e => setPart(i, { max_score: e.target.value === '' ? 0 : Number(e.target.value) })} />
                                    <Button type="button" size="icon" variant="ghost" className="size-8" disabled={scoreForm.data.components.length <= 1}
                                        onClick={() => scoreForm.setData('components', scoreForm.data.components.filter((_, j) => j !== i))} aria-label="Remove this part">
                                        <X className="size-4" />
                                    </Button>
                                </div>
                            ))}
                            <div className="flex items-center justify-between">
                                <Button type="button" size="sm" variant="ghost" className="inline-flex items-center gap-1.5"
                                    onClick={() => scoreForm.setData('components', [...scoreForm.data.components, { name: '', short_name: '', max_score: 0 }])}>
                                    <Plus className="size-4" /> Add part
                                </Button>
                                <span className={cn('text-sm font-medium tabular-nums', scoreTotal === 100 ? 'text-emerald-600' : 'text-amber-600')}>
                                    Total {scoreTotal} / 100
                                </span>
                            </div>
                            <ErrorText>{scoreForm.errors.components ?? firstRowError(scoreForm.errors, 'components')}</ErrorText>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setScoreOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={scoreForm.processing || scoreTotal !== 100} className="bg-indigo-600 text-white hover:bg-indigo-700">
                                {scoreForm.processing ? 'Saving…' : 'Save'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* ───── grade scale dialog ───── */}
            <Dialog open={gradeOpen} onOpenChange={setGradeOpen}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader><DialogTitle>{gradeEditing ? 'Edit grade scale' : 'New grade scale'}</DialogTitle></DialogHeader>
                    <form onSubmit={saveGrade} className="mt-2 space-y-4">
                        {!gradeEditing && (
                            <div className="space-y-1.5">
                                <Label>Start from</Label>
                                <div className="flex flex-wrap gap-1.5">
                                    {presets.grading.map(p => (
                                        <Button key={p.key} type="button" size="sm" variant="outline"
                                            onClick={() => gradeForm.setData(d => ({ ...d, name: p.name, bands: p.bands.map(b => ({ ...b })) }))}>
                                            {p.name}
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        )}
                        <div className="space-y-1.5">
                            <Label htmlFor="grade-name">Name</Label>
                            <Input id="grade-name" value={gradeForm.data.name} onChange={e => gradeForm.setData('name', e.target.value)} />
                            <ErrorText>{gradeForm.errors.name}</ErrorText>
                        </div>
                        <div className="max-h-[50vh] space-y-2 overflow-y-auto">
                            <div className="grid grid-cols-[4rem_4.5rem_4.5rem_1fr_4rem_2rem] gap-2 text-xs font-medium text-slate-500">
                                <span>Grade</span><span>From %</span><span>To %</span><span>Remark</span><span>Points</span><span />
                            </div>
                            {gradeForm.data.bands.map((b, i) => (
                                <div key={i} className="grid grid-cols-[4rem_4.5rem_4.5rem_1fr_4rem_2rem] items-center gap-2">
                                    <Input aria-label="Grade" value={b.grade} onChange={e => setBand(i, { grade: e.target.value })} />
                                    <Input aria-label="From %" type="number" min="0" max="100" step="0.5" value={b.min_marks} onChange={e => setBand(i, { min_marks: Number(e.target.value) })} />
                                    <Input aria-label="To %" type="number" min="0" max="100" step="0.5" value={b.max_marks} onChange={e => setBand(i, { max_marks: Number(e.target.value) })} />
                                    <Input aria-label="Remark" value={b.remarks ?? ''} onChange={e => setBand(i, { remarks: e.target.value })} />
                                    <Input aria-label="Grade points" type="number" min="0" max="5" step="0.5" value={b.gpa} onChange={e => setBand(i, { gpa: Number(e.target.value) })} />
                                    <Button type="button" size="icon" variant="ghost" className="size-8" disabled={gradeForm.data.bands.length <= 2}
                                        onClick={() => gradeForm.setData('bands', gradeForm.data.bands.filter((_, j) => j !== i))} aria-label="Remove this grade">
                                        <X className="size-4" />
                                    </Button>
                                </div>
                            ))}
                            <Button type="button" size="sm" variant="ghost" className="inline-flex items-center gap-1.5"
                                onClick={() => gradeForm.setData('bands', [...gradeForm.data.bands, { grade: '', min_marks: 0, max_marks: 0, remarks: '', gpa: 0 }])}>
                                <Plus className="size-4" /> Add grade
                            </Button>
                            <ErrorText>{gradeForm.errors.bands ?? firstRowError(gradeForm.errors, 'bands')}</ErrorText>
                        </div>
                        <p className="text-xs text-slate-500 dark:text-slate-400">The lowest grade must start at 0, so every score gets a grade. Grade points are optional.</p>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setGradeOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={gradeForm.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">
                                {gradeForm.processing ? 'Saving…' : 'Save'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* ───── subject exception dialog ───── */}
            <Dialog open={exceptionOpen} onOpenChange={setExceptionOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Give a subject its own score setup</DialogTitle></DialogHeader>
                    <form onSubmit={saveException} className="mt-2 space-y-4">
                        <div className="space-y-1.5">
                            <Label>Subject</Label>
                            <Select value={exSubject} onValueChange={v => setExSubject(v ?? '')} items={subjects.map(s => ({ value: String(s.id), label: subjectLabel(s) }))}>
                                <SelectTrigger className="w-full"><SelectValue placeholder="Choose a subject" /></SelectTrigger>
                                <SelectContent>
                                    {subjects.map(s => <SelectItem key={s.id} value={String(s.id)}>{subjectLabel(s)}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Score setup</Label>
                            <Select value={exScheme} onValueChange={v => setExScheme(v ?? '')} items={assessmentSchemes.map(s => ({ value: String(s.id), label: s.name }))}>
                                <SelectTrigger className="w-full"><SelectValue placeholder="Choose a setup" /></SelectTrigger>
                                <SelectContent>
                                    {assessmentSchemes.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setExceptionOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={!exSubject || !exScheme} className="bg-indigo-600 text-white hover:bg-indigo-700">Save</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
