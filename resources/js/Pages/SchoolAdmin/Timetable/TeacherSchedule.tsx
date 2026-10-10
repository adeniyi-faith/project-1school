import { router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, User } from 'lucide-react';
import { Link } from '@inertiajs/react';
import type { Staff, Timetable, TimeSlot, DayOfWeek, PageProps } from '@/Types';

interface Props {
    teachers: Staff[];
    periods: Timetable[];
    grid: Record<string, Record<string, Timetable>>;
    days: DayOfWeek[];
    defaultSlots: TimeSlot[];
    filters: { teacher_id?: string };
}

const DAY_LABELS: Record<string, string> = {
    monday: 'Monday', tuesday: 'Tuesday', wednesday: 'Wednesday',
    thursday: 'Thursday', friday: 'Friday', saturday: 'Saturday',
};

const DAY_SHORT: Record<string, string> = {
    monday: 'Mon', tuesday: 'Tue', wednesday: 'Wed',
    thursday: 'Thu', friday: 'Fri', saturday: 'Sat',
};

function fmt12(time: string) {
    const [h, m] = time.split(':').map(Number);
    const ampm = h >= 12 ? 'PM' : 'AM';
    return `${h % 12 || 12}:${String(m).padStart(2, '0')} ${ampm}`;
}

const COLORS = [
    'bg-indigo-100 text-indigo-700',
    'bg-emerald-100 text-emerald-700',
    'bg-amber-100 text-amber-700',
    'bg-rose-100 text-rose-700',
    'bg-cyan-100 text-cyan-700',
    'bg-violet-100 text-violet-700',
];

export default function TeacherSchedule({ teachers, periods, grid, days, defaultSlots, filters }: Props) {
    function applyFilter(key: string, value: string) {
        router.get('/school/timetable/teacher', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    const selectedTeacher = teachers.find(t => String(t.id) === filters.teacher_id);

    // Count periods per day for summary
    const dayCount: Record<string, number> = {};
    periods.forEach(p => { dayCount[p.day_of_week] = (dayCount[p.day_of_week] ?? 0) + 1; });

    function getPeriod(day: DayOfWeek, slot: TimeSlot): Timetable | undefined {
        const dayGrid = grid[day] ?? {};
        return dayGrid[slot.start] ?? dayGrid[slot.start + ':00'];
    }

    return (
        <AppLayout title="Teacher Schedule">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link href="/school/timetable" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Timetable
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Teacher Schedule</h1>
                    </div>
                </div>

                {/* Teacher selector */}
                <Card className="border-slate-200 dark:border-slate-800">
                    <CardContent className="p-4">
                        <div className="space-y-1">
                            <label className="text-xs font-medium text-slate-500 uppercase tracking-wide">Teacher</label>
                            <Select value={filters.teacher_id ?? ''} onValueChange={v => applyFilter('teacher_id', v)}>
                                <SelectTrigger className="w-64"><SelectValue placeholder="Select a teacher" /></SelectTrigger>
                                <SelectContent>
                                    {teachers.map(t => (
                                        <SelectItem key={t.id} value={String(t.id)}>{t.first_name} {t.last_name} ({t.emp_id})</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                {!filters.teacher_id ? (
                    <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 flex items-center justify-center py-24">
                        <div className="text-center">
                            <User className="w-12 h-12 mx-auto text-slate-300 mb-3" />
                            <p className="text-slate-500">Select a teacher to view their weekly schedule</p>
                        </div>
                    </div>
                ) : (
                    <>
                        {/* Summary */}
                        {selectedTeacher && (
                            <div className="flex items-center gap-4 flex-wrap">
                                <div>
                                    <p className="font-semibold text-slate-900 dark:text-white">{selectedTeacher.first_name} {selectedTeacher.last_name}</p>
                                    <p className="text-sm text-slate-400">{periods.length} periods/week</p>
                                </div>
                                <div className="flex gap-2 flex-wrap ml-auto">
                                    {days.map(day => dayCount[day] ? (
                                        <Badge key={day} variant="outline" className="text-xs">
                                            {DAY_SHORT[day]}: {dayCount[day]} periods
                                        </Badge>
                                    ) : null)}
                                </div>
                            </div>
                        )}

                        {/* Grid */}
                        <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                            <table className="w-full min-w-[700px]">
                                <thead>
                                    <tr className="bg-slate-50 dark:bg-slate-900">
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase w-28 border-b border-slate-200 dark:border-slate-800">Time</th>
                                        {days.map(day => (
                                            <th key={day} className="px-2 py-3 text-center text-xs font-semibold text-slate-500 uppercase border-b border-l border-slate-200 dark:border-slate-800">
                                                {DAY_SHORT[day]}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {defaultSlots.map((slot, idx) => (
                                        <tr key={slot.start} className="border-b border-slate-100 dark:border-slate-800/50 last:border-0">
                                            <td className="px-4 py-2 text-xs text-slate-400">
                                                <span className="font-medium text-slate-600 dark:text-slate-300">{fmt12(slot.start)}</span>
                                                <br />
                                                <span className="text-[11px]">{fmt12(slot.end)}</span>
                                            </td>
                                            {days.map(day => {
                                                const period = getPeriod(day, slot);
                                                const color = period ? COLORS[idx % COLORS.length] : '';
                                                return (
                                                    <td key={day} className="px-1 py-1 border-l border-slate-100 dark:border-slate-800/50 align-top">
                                                        {period ? (
                                                            <div className={`rounded-lg p-2 ${color}`}>
                                                                <p className="text-xs font-semibold truncate">{period.subject?.name ?? '—'}</p>
                                                                <p className="text-[10px] opacity-70 truncate">
                                                                    {period.school_class?.name ?? ''} {period.section?.name ?? ''}
                                                                </p>
                                                                {period.room && (
                                                                    <p className="text-[10px] opacity-60">Room {period.room}</p>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <div className="h-14 rounded-lg bg-slate-50 dark:bg-slate-900/50" />
                                                        )}
                                                    </td>
                                                );
                                            })}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
