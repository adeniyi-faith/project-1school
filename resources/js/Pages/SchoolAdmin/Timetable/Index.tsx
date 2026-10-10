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
import { Card, CardContent } from '@/components/ui/card';
import { Plus, Trash2, CalendarDays, User } from 'lucide-react';
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

const DAY_LABELS: Record<string, string> = {
    monday: 'Mon', tuesday: 'Tue', wednesday: 'Wed',
    thursday: 'Thu', friday: 'Fri', saturday: 'Sat', sunday: 'Sun',
};

const SUBJECT_COLORS = [
    'bg-indigo-100 text-indigo-700 border-indigo-200',
    'bg-emerald-100 text-emerald-700 border-emerald-200',
    'bg-amber-100 text-amber-700 border-amber-200',
    'bg-rose-100 text-rose-700 border-rose-200',
    'bg-cyan-100 text-cyan-700 border-cyan-200',
    'bg-violet-100 text-violet-700 border-violet-200',
    'bg-orange-100 text-orange-700 border-orange-200',
    'bg-teal-100 text-teal-700 border-teal-200',
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

    return (
        <AppLayout title="Timetable">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Timetable</h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Weekly class schedule builder</p>
                    </div>
                    <Link href="/school/timetable/teacher">
                        <Button variant="outline" className="inline-flex items-center gap-2">
                            <User className="w-4 h-4" /> Teacher Schedule
                        </Button>
                    </Link>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                        {flash.success}
                    </div>
                )}

                {/* Filters */}
                <Card className="border-slate-200 dark:border-slate-800">
                    <CardContent className="p-4">
                        <div className="flex flex-wrap gap-3">
                            <div className="space-y-1">
                                <label className="text-xs font-medium text-slate-500 uppercase tracking-wide">Class</label>
                                <Select value={filters.class_id ?? ''} onValueChange={v => applyFilter('class_id', v)}>
                                    <SelectTrigger className="w-40"><SelectValue placeholder="Select class" /></SelectTrigger>
                                    <SelectContent>
                                        {classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                            </div>
                            {filteredSections.length > 0 && (
                                <div className="space-y-1">
                                    <label className="text-xs font-medium text-slate-500 uppercase tracking-wide">Section</label>
                                    <Select value={filters.section_id ?? ''} onValueChange={v => applyFilter('section_id', v)}>
                                        <SelectTrigger className="w-36"><SelectValue placeholder="All sections" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">All Sections</SelectItem>
                                            {filteredSections.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Grid */}
                {!filters.class_id ? (
                    <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 flex items-center justify-center py-24">
                        <div className="text-center">
                            <CalendarDays className="w-12 h-12 mx-auto text-slate-300 mb-3" />
                            <p className="text-slate-500">Select a class to view or build its timetable</p>
                        </div>
                    </div>
                ) : (
                    <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                        <table className="w-full min-w-[700px]">
                            <thead>
                                <tr className="bg-slate-50 dark:bg-slate-900">
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide w-28 border-b border-slate-200 dark:border-slate-800">
                                        Time
                                    </th>
                                    {days.map(day => (
                                        <th key={day} className="px-2 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide border-b border-l border-slate-200 dark:border-slate-800">
                                            {DAY_LABELS[day]}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {defaultSlots.map((slot, slotIdx) => (
                                    <tr key={slot.start} className="border-b border-slate-100 dark:border-slate-800/50 last:border-0">
                                        <td className="px-4 py-2 text-xs text-slate-400 whitespace-nowrap">
                                            <span className="font-medium text-slate-600 dark:text-slate-300">{fmt12(slot.start)}</span>
                                            <br />
                                            <span className="text-[11px]">{fmt12(slot.end)}</span>
                                        </td>
                                        {days.map(day => {
                                            const period = getPeriod(day, slot);
                                            const colorClass = period ? (subjectColorMap[period.subject_id] ?? SUBJECT_COLORS[0]) : '';

                                            return (
                                                <td key={day} className="px-1 py-1 border-l border-slate-100 dark:border-slate-800/50 align-top">
                                                    {period ? (
                                                        <div
                                                            className={`rounded-lg border p-2 cursor-pointer hover:opacity-80 transition-opacity group relative ${colorClass}`}
                                                            onClick={() => openSlot(day, slot)}
                                                        >
                                                            <p className="text-xs font-semibold truncate">{period.subject?.name ?? '—'}</p>
                                                            {period.teacher && (
                                                                <p className="text-[10px] opacity-70 truncate">{period.teacher.first_name} {period.teacher.last_name}</p>
                                                            )}
                                                            {period.room && (
                                                                <p className="text-[10px] opacity-60 truncate">Room {period.room}</p>
                                                            )}
                                                            <button
                                                                type="button"
                                                                onClick={e => { e.stopPropagation(); handleDelete(period); }}
                                                                className="absolute top-1 right-1 opacity-0 group-hover:opacity-100 text-current hover:text-red-600 transition-all"
                                                            >
                                                                <Trash2 className="w-3 h-3" />
                                                            </button>
                                                        </div>
                                                    ) : (
                                                        <button
                                                            type="button"
                                                            onClick={() => openSlot(day, slot)}
                                                            className="w-full h-14 rounded-lg border-2 border-dashed border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-300 dark:text-slate-700 hover:border-indigo-300 hover:text-indigo-400 transition-colors"
                                                        >
                                                            <Plus className="w-4 h-4" />
                                                        </button>
                                                    )}
                                                </td>
                                            );
                                        })}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* Subject color legend */}
                {subjects.length > 0 && filters.class_id && (
                    <div className="flex flex-wrap gap-2">
                        {subjects.map((s, i) => (
                            <span key={s.id} className={`px-2.5 py-1 rounded-full text-xs font-medium border ${SUBJECT_COLORS[i % SUBJECT_COLORS.length]}`}>
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
