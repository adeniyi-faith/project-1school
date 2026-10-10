import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { EmptyState, PageHeader, Panel, Pill, StatStrip } from '@/components/app/kit';
import { ArrowUpRight, GraduationCap, History, Undo2 } from 'lucide-react';
import type { PromotionBatchRow, PromotionCandidate, PromotionOutcome } from '@/Types';

interface ClassOption { id: number; name: string; waiting: number }
interface SectionOption { id: number; class_id: number; name: string }

interface Selected {
    class_id: number;
    class_name: string;
    next_class_id: number | null;
    is_top: boolean;
    pass_mark: number;
    students: PromotionCandidate[];
}

interface Props {
    years: { id: number; name: string; is_current: boolean }[];
    yearId: number | null;
    classes: ClassOption[];
    sections: SectionOption[];
    selected: Selected | null;
    batches: PromotionBatchRow[];
}

type Choice = PromotionOutcome | 'skip';
type Decision = { outcome: Choice; section_id: string; reason: string };

const NONE = 'none';
const OUTCOME_LABELS: Record<Choice, string> = { promoted: 'Move up', repeated: 'Repeat', graduated: 'Graduate', skip: 'Leave for now' };

export default function Promotions({ years, yearId, classes, sections, selected, batches }: Props) {
    const [undoing, setUndoing] = useState<PromotionBatchRow | null>(null);

    function visit(params: Record<string, string | number | null | undefined>) {
        router.get('/school/promotions', { year_id: yearId ?? undefined, class_id: selected?.class_id, ...params }, { preserveScroll: true });
    }

    const yearItems = years.map(y => ({ value: String(y.id), label: y.name + (y.is_current ? ' (current)' : '') }));

    return (
        <AppLayout title="Move students up">
            <div className="space-y-6">
                <PageHeader
                    title="Move students up"
                    description="At the end of the school year, move each class to the next one. Students below the pass mark are flagged so you can decide who repeats. The top class graduates."
                />

                <div className="flex flex-wrap items-end gap-3">
                    <div className="space-y-1.5">
                        <Label>School year being finished</Label>
                        <Select items={yearItems} value={yearId ? String(yearId) : ''} onValueChange={v => visit({ year_id: v, class_id: undefined })}>
                            <SelectTrigger className="w-56" aria-label="School year"><SelectValue placeholder="Choose a year" /></SelectTrigger>
                            <SelectContent>{yearItems.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                        </Select>
                    </div>
                </div>

                {!yearId ? (
                    <Panel><EmptyState icon={GraduationCap} title="No school years yet" text="Add a school year under Academic Years first." /></Panel>
                ) : (
                    <Panel title="Choose a class" description="The number shows how many students in each class have not been moved yet this year.">
                        <div className="flex flex-wrap gap-2">
                            {classes.map(c => (
                                <button
                                    key={c.id}
                                    type="button"
                                    onClick={() => visit({ class_id: c.id })}
                                    className={`flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors ${selected?.class_id === c.id
                                        ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300'
                                        : 'border-slate-200 hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/[0.04]'}`}
                                >
                                    {c.name}
                                    <span className={`rounded-full px-1.5 text-xs tabular-nums ${c.waiting ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 text-slate-400 dark:bg-white/[0.06]'}`}>{c.waiting}</span>
                                </button>
                            ))}
                        </div>
                    </Panel>
                )}

                {selected && yearId && (
                    <ClassMove key={`${yearId}-${selected.class_id}-${selected.pass_mark}`} yearId={yearId} selected={selected} classes={classes} sections={sections} onPassMark={pm => visit({ pass_mark: pm })} />
                )}

                <Panel title="Past moves" description="Undo puts every student in that move back where they were, as long as none of them has been moved or changed since." flush>
                    {batches.length === 0 ? (
                        <div className="pb-5"><EmptyState icon={History} title="Nothing moved yet" /></div>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-5">Year</TableHead>
                                        <TableHead>Class</TableHead>
                                        <TableHead>What happened</TableHead>
                                        <TableHead>Done by</TableHead>
                                        <TableHead className="pr-5 text-right"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {batches.map(b => (
                                        <TableRow key={b.id} className={b.undone ? 'opacity-60' : ''}>
                                            <TableCell className="pl-5">{b.year}</TableCell>
                                            <TableCell>{b.from}{b.to && b.promoted > 0 ? ` → ${b.to}` : ''}</TableCell>
                                            <TableCell className="text-sm">
                                                {[b.promoted && `${b.promoted} moved up`, b.repeated && `${b.repeated} repeating`, b.graduated && `${b.graduated} graduated`].filter(Boolean).join(', ')}
                                            </TableCell>
                                            <TableCell className="text-sm text-slate-500">{b.by} · {b.at}</TableCell>
                                            <TableCell className="pr-5 text-right">
                                                {b.undone
                                                    ? <Pill>Undone by {b.undone.by} · {b.undone.at}</Pill>
                                                    : <Button variant="ghost" size="sm" onClick={() => setUndoing(b)}><Undo2 className="size-4" /> Undo</Button>}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </Panel>
            </div>

            <Dialog open={!!undoing} onOpenChange={open => !open && setUndoing(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Undo this move?</DialogTitle></DialogHeader>
                    <p className="text-sm text-slate-500">
                        Every student moved from {undoing?.from} in {undoing?.year} goes back to the class, section and status they had before. Graduates become current students again.
                    </p>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setUndoing(null)}>Keep it</Button>
                        <Button
                            className="bg-red-600 text-white hover:bg-red-700"
                            onClick={() => undoing && router.post(`/school/promotions/${undoing.id}/undo`, {}, { preserveScroll: true, onFinish: () => setUndoing(null) })}
                        >Undo the move</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

function ClassMove({ yearId, selected, classes, sections, onPassMark }: {
    yearId: number; selected: Selected; classes: ClassOption[]; sections: SectionOption[]; onPassMark: (pm: string) => void;
}) {
    const [toClass, setToClass] = useState(selected.next_class_id ? String(selected.next_class_id) : NONE);
    const [passMark, setPassMark] = useState(String(selected.pass_mark));
    const [reviewing, setReviewing] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const toSections = sections.filter(s => String(s.class_id) === toClass);
    const sameNamedSection = (name: string | null) => toSections.find(s => s.name === name)?.id;

    const [decisions, setDecisions] = useState<Record<number, Decision>>(() => Object.fromEntries(
        selected.students.map(s => [s.id, { outcome: s.suggested, section_id: '', reason: '' }]),
    ));

    function change(id: number, patch: Partial<Decision>) {
        setDecisions(d => ({ ...d, [id]: { ...d[id], ...patch } }));
    }

    const chosen = selected.students.filter(s => decisions[s.id]?.outcome !== 'skip');
    const counts = useMemo(() => {
        const c = { promoted: 0, repeated: 0, graduated: 0, skip: 0 };
        selected.students.forEach(s => { c[decisions[s.id]?.outcome ?? 'skip']++; });
        return c;
    }, [decisions, selected.students]);
    const repeaters = selected.students.filter(s => decisions[s.id]?.outcome === 'repeated');
    const missingReason = repeaters.some(s => decisions[s.id].reason.trim().length < 3);
    const needsTarget = counts.promoted > 0 && toClass === NONE;
    const toName = classes.find(c => String(c.id) === toClass)?.name;

    const classItems = [{ value: NONE, label: 'No class (top class)' }, ...classes.filter(c => c.id !== selected.class_id).map(c => ({ value: String(c.id), label: c.name }))];
    const outcomeItems = (Object.keys(OUTCOME_LABELS) as Choice[]).map(k => ({ value: k, label: OUTCOME_LABELS[k] }));

    function submit() {
        setProcessing(true);
        router.post('/school/promotions', {
            academic_year_id: yearId,
            from_class_id: selected.class_id,
            to_class_id: toClass === NONE ? null : Number(toClass),
            pass_mark: Number(passMark),
            decisions: chosen.map(s => {
                const d = decisions[s.id];
                // An untouched section picker means "the section with the same name in the new class"
                const section = d.section_id || String(sameNamedSection(s.section) ?? NONE);
                return {
                    student_id: s.id,
                    outcome: d.outcome,
                    section_id: d.outcome === 'promoted' && section !== NONE ? Number(section) : null,
                    reason: d.reason || null,
                };
            }),
        }, {
            preserveScroll: true,
            onError: e => { setErrors(e); setReviewing(false); },
            onFinish: () => setProcessing(false),
        });
    }

    return (
        <Panel
            title={`${selected.class_name}: ${selected.students.length} student${selected.students.length === 1 ? '' : 's'} to move`}
            description={selected.is_top ? 'This is the top class, so students graduate by default.' : 'Check each student, then review and confirm.'}
            flush
        >
            {selected.students.length === 0 ? (
                <div className="pb-5"><EmptyState icon={GraduationCap} title="Everyone in this class has been moved for this year" text="New students added to the class later will show up here." /></div>
            ) : (
                <>
                    <div className="flex flex-wrap items-end gap-4 px-5 pb-4">
                        <div className="space-y-1.5">
                            <Label>Move up to</Label>
                            <Select items={classItems} value={toClass} onValueChange={v => { setToClass(v ?? NONE); setDecisions(d => Object.fromEntries(Object.entries(d).map(([k, x]) => [k, { ...x, section_id: '' }]))); }}>
                                <SelectTrigger className="w-48" aria-label="Move up to"><SelectValue /></SelectTrigger>
                                <SelectContent>{classItems.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                            </Select>
                            {errors.to_class_id && <p className="text-xs text-red-500">{errors.to_class_id}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="pass-mark">Pass mark (year average)</Label>
                            <div className="flex gap-2">
                                <Input id="pass-mark" type="number" min={0} max={100} value={passMark} onChange={e => setPassMark(e.target.value)} className="w-24" />
                                <Button variant="outline" onClick={() => onPassMark(passMark)} disabled={Number(passMark) === selected.pass_mark}>Re-check</Button>
                            </div>
                        </div>
                    </div>

                    {errors.decisions && <p className="px-5 pb-3 text-sm text-red-600">{errors.decisions}</p>}

                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-5">Student</TableHead>
                                    <TableHead className="text-right">Year average</TableHead>
                                    <TableHead>Decision</TableHead>
                                    <TableHead className="pr-5">Section or reason</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {selected.students.map(s => {
                                    const d = decisions[s.id];
                                    const below = s.year_average !== null && s.year_average < selected.pass_mark;
                                    const sectionItems = [{ value: NONE, label: 'No section' }, ...toSections.map(x => ({ value: String(x.id), label: x.name }))];
                                    const defaultSection = String(sameNamedSection(s.section) ?? NONE);
                                    const errorAt = chosen.indexOf(s);
                                    return (
                                        <TableRow key={s.id} className={d.outcome === 'repeated' ? 'bg-amber-50/60 dark:bg-amber-500/[0.06]' : ''}>
                                            <TableCell className="pl-5">
                                                <p className="font-medium text-slate-900 dark:text-white">{s.name}</p>
                                                <p className="text-xs text-slate-500">{s.admission_no}{s.section ? ` · Section ${s.section}` : ''}</p>
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {s.year_average === null
                                                    ? <span className="text-xs text-slate-400">No results</span>
                                                    : <span className={below ? 'font-medium text-amber-700 dark:text-amber-400' : ''}>{s.year_average.toFixed(1)}%</span>}
                                                {s.terms_counted > 0 && <p className="text-xs text-slate-400">{s.terms_counted} term{s.terms_counted === 1 ? '' : 's'}</p>}
                                            </TableCell>
                                            <TableCell>
                                                <Select items={outcomeItems} value={d.outcome} onValueChange={v => change(s.id, { outcome: (v ?? 'skip') as Choice })}>
                                                    <SelectTrigger className="w-36" aria-label={`Decision for ${s.name}`}><SelectValue /></SelectTrigger>
                                                    <SelectContent>{outcomeItems.map(o => <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>)}</SelectContent>
                                                </Select>
                                                {below && d.outcome !== 'repeated' && <p className="mt-1 text-xs text-amber-700 dark:text-amber-400">Below the pass mark</p>}
                                            </TableCell>
                                            <TableCell className="pr-5">
                                                {d.outcome === 'promoted' && toClass !== NONE && (
                                                    <Select items={sectionItems} value={d.section_id || defaultSection} onValueChange={v => change(s.id, { section_id: v ?? '' })}>
                                                        <SelectTrigger className="w-36" aria-label={`Section for ${s.name}`}><SelectValue /></SelectTrigger>
                                                        <SelectContent>{sectionItems.map(x => <SelectItem key={x.value} value={x.value}>{x.label}</SelectItem>)}</SelectContent>
                                                    </Select>
                                                )}
                                                {d.outcome === 'repeated' && (
                                                    <>
                                                        <Input value={d.reason} onChange={e => change(s.id, { reason: e.target.value })} placeholder="Why is this student repeating?" className="min-w-56" aria-label={`Reason for ${s.name}`} />
                                                        {errors[`decisions.${errorAt}.reason`] && <p className="mt-1 text-xs text-red-500">{errors[`decisions.${errorAt}.reason`]}</p>}
                                                    </>
                                                )}
                                                {d.outcome === 'graduated' && <span className="text-xs text-slate-500">Leaves the school as a past student</span>}
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-4 dark:border-white/[0.06]">
                        <p className="text-sm text-slate-500">
                            {[counts.promoted && `${counts.promoted} moving up`, counts.repeated && `${counts.repeated} repeating`, counts.graduated && `${counts.graduated} graduating`, counts.skip && `${counts.skip} left for now`].filter(Boolean).join(' · ')}
                        </p>
                        <div className="flex flex-col items-end gap-1">
                            <Button onClick={() => setReviewing(true)} disabled={chosen.length === 0 || missingReason || needsTarget} className="bg-indigo-600 text-white hover:bg-indigo-700">
                                <ArrowUpRight className="size-4" /> Review and confirm
                            </Button>
                            {missingReason && <p className="text-xs text-amber-700 dark:text-amber-400">Give a reason for each student who is repeating.</p>}
                            {needsTarget && <p className="text-xs text-amber-700 dark:text-amber-400">Choose the class to move up to.</p>}
                        </div>
                    </div>
                </>
            )}

            <Dialog open={reviewing} onOpenChange={setReviewing}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader><DialogTitle>Confirm the move for {selected.class_name}</DialogTitle></DialogHeader>
                    <StatStrip items={[
                        { label: `Moving up${toName ? ` to ${toName}` : ''}`, value: counts.promoted, tone: 'good' },
                        { label: 'Repeating', value: counts.repeated, tone: counts.repeated ? 'warn' : 'default' },
                        { label: 'Graduating', value: counts.graduated },
                    ]} />
                    {repeaters.length > 0 && (
                        <div className="space-y-2">
                            <p className="text-sm font-medium text-slate-900 dark:text-white">Please check the students repeating {selected.class_name}:</p>
                            <ul className="max-h-48 space-y-1.5 overflow-y-auto text-sm">
                                {repeaters.map(s => (
                                    <li key={s.id} className="rounded-lg bg-amber-50 px-3 py-2 dark:bg-amber-500/10">
                                        <span className="font-medium">{s.name}</span>
                                        {s.year_average !== null && <span className="text-slate-500"> · {s.year_average.toFixed(1)}%</span>}
                                        <p className="text-slate-600 dark:text-slate-300">{decisions[s.id].reason}</p>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                    {counts.skip > 0 && <p className="text-sm text-slate-500">{counts.skip} student{counts.skip === 1 ? '' : 's'} left for now will stay in {selected.class_name} and can be moved later.</p>}
                    <p className="text-sm text-slate-500">Students' past results stay with the class they sat them in. You can undo this from "Past moves" below.</p>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setReviewing(false)}>Go back</Button>
                        <Button onClick={submit} disabled={processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Confirm</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </Panel>
    );
}
