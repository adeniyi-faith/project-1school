import { useState } from 'react';
import { router, usePage, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog';
import { EmptyState, PageHeader, Panel } from '@/components/app/kit';
import { Plus, Trash2, CalendarDays, User } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import type { SchoolClass, Section, Subject, Staff, Timetable, TimeSlot, DayOfWeek, PageProps } from '@/Types';

interface Props {
    classes: SchoolClass[];
    sections: Section[];
    subjects: Subject[];
    teachers: Staff[];
    periods: Timetable[];
    grid: Record<string, Record<string, Timetable>>;
    days: DayOfWeek[];
    defaultSlots: TimeSlot[];
    filters: { class_id?: string; section_id?: string };
}

const DAY_FULL: Record<string, string> = {
    monday: 'Monday', tuesday: 'Tuesday', wednesday: 'Wednesday',
    thursday: 'Thursday', friday: 'Friday', saturday: 'Saturday', sunday: 'Sunday',
};

const DAY_LABELS: Record<string, string> = {
    monday: 'Mon', tuesday: 'Tue', wednesday: 'Wed',
    thursday: 'Thu', friday: 'Fri', saturday: 'Sat', sunday: 'Sun',
};

const SUBJECT_COLORS = [
    'bg-indigo-50 text-indigo-800 ring-indigo-200/80 dark:bg-indigo-500/15 dark:text-indigo-200 dark:ring-indigo-400/20',
    'bg-emerald-50 text-emerald-800 ring-emerald-200/80 dark:bg-emerald-500/15 dark:text-emerald-200 dark:ring-emerald-400/20',
    'bg-amber-50 text-amber-900 ring-amber-200/80 dark:bg-amber-500/15 dark:text-amber-200 dark:ring-amber-400/20',
    'bg-rose-50 text-rose-800 ring-rose-200/80 dark:bg-rose-500/15 dark:text-rose-200 dark:ring-rose-400/20',
    'bg-cyan-50 text-cyan-800 ring-cyan-200/80 dark:bg-cyan-500/15 dark:text-cyan-200 dark:ring-cyan-400/20',
    'bg-violet-50 text-violet-800 ring-violet-200/80 dark:bg-violet-500/15 dark:text-violet-200 dark:ring-violet-400/20',
    'bg-orange-50 text-orange-900 ring-orange-200/80 dark:bg-orange-500/15 dark:text-orange-200 dark:ring-orange-400/20',
    'bg-teal-50 text-teal-800 ring-teal-200/80 dark:bg-teal-500/15 dark:text-teal-200 dark:ring-teal-400/20',
];

function fmt12(time: string) {
    const [h, m] = time.split(':').map(Number);
    const ampm = h >= 12 ? 'PM' : 'AM';
    return `${h % 12 || 12}:${String(m).padStart(2, '0')} ${ampm}`;
}

export default function TimetableIndex({ classes, sections, subjects, teachers, grid, days, defaultSlots, filters }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [dialogOpen, setDialogOpen] = useState(false);
    const [selectedSlot, setSelectedSlot] = useState<{ day: DayOfWeek; start: string; end: string } | null>(null);
    const [existingPeriod, setExistingPeriod] = useState<Timetable | null>(null);
    const [mobileDay, setMobileDay] = useState<DayOfWeek | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        class_id:    filters.class_id ?? '',
        section_id:  filters.section_id ?? '',
        subject_id:  '',
        teacher_id:  '',
        day_of_week: '' as DayOfWeek | '',
        start_time:  '',
        end_time:    '',
        room:        '',
        notes:       '',
    });

    function applyFilter(key: string, value: string) {
        router.get('/school/timetable', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    function openSlot(day: DayOfWeek, slot: TimeSlot) {
        const existing = grid[day]?.[slot.start + ':00'] ?? grid[day]?.[slot.start];
        setExistingPeriod(existing ?? null);
        setSelectedSlot({ day, start: slot.start, end: slot.end });
        setData({
            class_id:    filters.class_id ?? '',
            section_id:  filters.section_id ?? '',
            subject_id:  existing ? String(existing.subject_id) : '',
            teacher_id:  existing?.teacher_id ? String(existing.teacher_id) : '',
            day_of_week: day,
            start_time:  slot.start,
            end_time:    slot.end,
            room:        existing?.room ?? '',
            notes:       existing?.notes ?? '',
        });
        setDialogOpen(true);
    }

    function handleSave(e: React.FormEvent) {
        e.preventDefault();
        post('/school/timetable', {
            onSuccess: () => { setDialogOpen(false); reset(); },
        });
    }

    function handleDelete(period: Timetable) {
        if (!confirm('Remove this period?')) return;
        router.delete(`/school/timetable/${period.id}`, { preserveScroll: true });
    }

    const filteredSections = filters.class_id
        ? sections.filter(s => s.class_id === Number(filters.class_id))
        : [];

    // Map subject id → color index for consistent coloring
    const subjectColorMap: Record<number, string> = {};
    subjects.forEach((s, i) => { subjectColorMap[s.id] = SUBJECT_COLORS[i % SUBJECT_COLORS.length]; });

    // Normalize grid keys (backend sends "HH:MM:SS", normalize to "HH:MM")
    function getPeriod(day: DayOfWeek, slot: TimeSlot): Timetable | undefined {
        const dayGrid = grid[day] ?? {};
        return dayGrid[slot.start] ?? dayGrid[slot.start + ':00'];
    }

    const activeDay: DayOfWeek = mobileDay ?? days[0];

    const PeriodCell = ({ day, slot, period, compact }: { day: DayOfWeek; slot: TimeSlot; period?: Timetable; compact?: boolean }) => {
        if (!period) {
            return (
                <button
                    type="button"
                    onClick={() => openSlot(day, slot)}
                    aria-label={`Add a period on ${DAY_FULL[day]} at ${fmt12(slot.start)}`}
                    className={cn(
                        'flex w-full items-center justify-center rounded-lg border border-dashed border-slate-200 text-slate-300 outline-none transition-colors hover:border-indigo-300 hover:bg-indigo-50/60 hover:text-indigo-500 focus-visible:ring-2 focus-visible:ring-indigo-400 dark:border-white/10 dark:text-slate-600 dark:hover:bg-indigo-500/10',
                        compact ? 'h-12' : 'h-[3.75rem]',
                    )}
                >
                    <Plus className="size-4" />
                </button>
            );
        }
        const color = subjectColorMap[period.subject_id] ?? SUBJECT_COLORS[0];
        return (
            <div className={cn('group relative rounded-lg p-2.5 ring-1 ring-inset transition-shadow hover:shadow-sm', color)}>
                <button type="button" onClick={() => openSlot(day, slot)} className="block w-full text-left outline-none after:absolute after:inset-0 after:rounded-lg focus-visible:after:ring-2 focus-visible:after:ring-indigo-400">
                    <span className="block truncate text-[13px] font-semibold leading-tight">{period.subject?.name ?? '—'}</span>
                    {period.teacher && <span className="mt-0.5 block truncate text-[11px] opacity-75">{period.teacher.first_name} {period.teacher.last_name}</span>}
                    {period.room && <span className="block truncate text-[11px] opacity-60">Room {period.room}</span>}
                </button>
                <button
                    type="button"
                    onClick={() => handleDelete(period)}
                    aria-label={`Remove ${period.subject?.name ?? 'period'}`}
                    className="absolute right-1.5 top-1.5 z-10 rounded p-1 opacity-0 outline-none transition-opacity hover:text-red-600 focus-visible:opacity-100 group-hover:opacity-100 max-md:opacity-60"
                >
                    <Trash2 className="size-3" />
                </button>
            </div>
        );
    };

    return (
        <AppLayout breadcrumbs={[{ label: 'Academic' }, { label: 'Timetable' }]}>
            <div className="space-y-6">
                <PageHeader
                    title="Timetable"
                    description="Build the weekly schedule. Select an empty slot to add a period."
                    actions={
                        <Link href="/school/timetable/teacher" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                            <User className="size-4" /> Teacher schedule
                        </Link>
                    }
                />

                {flash?.success && (
                    <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                        {flash.success}
                    </div>
                )}

                <div className="flex flex-wrap gap-3">
                    <div className="space-y-1.5">
                        <label className="text-xs font-medium text-slate-500">Class</label>
                        <Select value={filters.class_id ?? ''} onValueChange={v => applyFilter('class_id', v)}>
                            <SelectTrigger className="h-10 w-44"><SelectValue placeholder="Select class" /></SelectTrigger>
                            <SelectContent>
                                {classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                            </SelectContent>
                        </Select>
                    </div>
                    {filteredSections.length > 0 && (
                        <div className="space-y-1.5">
                            <label className="text-xs font-medium text-slate-500">Section</label>
                            <Select value={filters.section_id ?? ''} onValueChange={v => applyFilter('section_id', v)}>
                                <SelectTrigger className="h-10 w-40"><SelectValue placeholder="All sections" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All sections</SelectItem>
                                    {filteredSections.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                    )}
                </div>

                {!filters.class_id ? (
                    <Panel>
                        <EmptyState icon={CalendarDays} title="Pick a class" text="Choose a class above to see or build its weekly timetable." />
                    </Panel>
                ) : (
                    <>
                        {/* Desktop grid */}
                        <Panel flush className="hidden overflow-hidden md:block" bodyClassName="pt-0">
                            <div className="overflow-x-auto scroll-quiet">
                                <table data-no-stack className="w-full min-w-[720px] border-collapse">
                                    <thead>
                                        <tr>
                                            <th className="w-28 border-b border-slate-200 bg-slate-50/70 px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500 dark:border-white/[0.08] dark:bg-white/[0.03]">Time</th>
                                            {days.map(day => (
                                                <th key={day} className="border-b border-l border-slate-200 bg-slate-50/70 px-2 py-3 text-center text-xs font-medium uppercase tracking-wide text-slate-500 dark:border-white/[0.08] dark:bg-white/[0.03]">
                                                    {DAY_LABELS[day]}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {defaultSlots.map(slot => (
                                            <tr key={slot.start} className="border-b border-slate-100 last:border-0 dark:border-white/[0.05]">
                                                <td className="whitespace-nowrap px-4 py-2 align-top text-xs text-slate-400">
                                                    <span className="font-medium text-slate-700 dark:text-slate-200">{fmt12(slot.start)}</span>
                                                    <br />
                                                    <span className="text-[11px]">{fmt12(slot.end)}</span>
                                                </td>
                                                {days.map(day => (
                                                    <td key={day} className="border-l border-slate-100 p-1 align-top dark:border-white/[0.05]">
                                                        <PeriodCell day={day} slot={slot} period={getPeriod(day, slot)} />
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </Panel>

                        {/* Phone: one day at a time */}
                        <div className="space-y-3 md:hidden">
                            <div role="tablist" aria-label="Day" className="grid gap-1.5" style={{ gridTemplateColumns: `repeat(${days.length}, minmax(0, 1fr))` }}>
                                {days.map(day => (
                                    <button
                                        key={day}
                                        role="tab"
                                        aria-selected={activeDay === day}
                                        onClick={() => setMobileDay(day)}
                                        className={cn(
                                            'rounded-xl border py-2.5 text-center text-[13px] font-medium outline-none transition-colors focus-visible:ring-2 focus-visible:ring-indigo-400',
                                            activeDay === day
                                                ? 'border-indigo-600 bg-indigo-600 text-white'
                                                : 'border-slate-200 bg-white text-slate-600 dark:border-white/10 dark:bg-slate-900 dark:text-slate-300',
                                        )}
                                    >
                                        {DAY_LABELS[day]}
                                    </button>
                                ))}
                            </div>
                            <Panel title={DAY_FULL[activeDay]} flush bodyClassName="divide-y divide-slate-100 dark:divide-white/[0.06]">
                                {defaultSlots.map(slot => (
                                    <div key={slot.start} className="flex gap-3 px-4 py-3">
                                        <div className="w-14 shrink-0 pt-1 text-xs text-slate-400">
                                            <span className="block font-medium text-slate-700 dark:text-slate-200">{fmt12(slot.start)}</span>
                                            {fmt12(slot.end)}
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <PeriodCell day={activeDay} slot={slot} period={getPeriod(activeDay, slot)} compact />
                                        </div>
                                    </div>
                                ))}
                            </Panel>
                        </div>
                    </>
                )}

                {subjects.length > 0 && filters.class_id && (
                    <div className="flex flex-wrap gap-2">
                        {subjects.map((s, i) => (
                            <span key={s.id} className={cn('rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset', SUBJECT_COLORS[i % SUBJECT_COLORS.length])}>
                                {s.name}
                            </span>
                        ))}
                    </div>
                )}
            </div>

            {/* Add / Edit Period Dialog */}
            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            {existingPeriod ? 'Edit Period' : 'Add Period'} — {selectedSlot && DAY_LABELS[selectedSlot.day]} {selectedSlot && fmt12(selectedSlot.start)}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSave} className="space-y-4 mt-2">
                        <div className="space-y-1.5">
                            <Label>Subject <span className="text-red-500">*</span></Label>
                            <Select value={data.subject_id} onValueChange={v => setData('subject_id', v)}>
                                <SelectTrigger><SelectValue placeholder="Select subject" /></SelectTrigger>
                                <SelectContent>
                                    {subjects.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.name} ({s.code})</SelectItem>)}
                                </SelectContent>
                            </Select>
                            {errors.subject_id && <p className="text-xs text-red-500">{errors.subject_id}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label>Teacher</Label>
                            <Select value={data.teacher_id} onValueChange={v => setData('teacher_id', v)}>
                                <SelectTrigger><SelectValue placeholder="Assign teacher (optional)" /></SelectTrigger>
                                <SelectContent>
                                    {teachers.map(t => (
                                        <SelectItem key={t.id} value={String(t.id)}>{t.first_name} {t.last_name}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.teacher_id && <p className="text-xs text-red-500">{errors.teacher_id}</p>}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Start Time</Label>
                                <Input type="time" value={data.start_time} onChange={e => setData('start_time', e.target.value)} />
                            </div>
                            <div className="space-y-1.5">
                                <Label>End Time</Label>
                                <Input type="time" value={data.end_time} onChange={e => setData('end_time', e.target.value)} />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label>Room / Hall</Label>
                            <Input value={data.room} onChange={e => setData('room', e.target.value)} placeholder="e.g. Room 101" />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setDialogOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={processing} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {processing ? 'Saving...' : existingPeriod ? 'Update' : 'Add Period'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
