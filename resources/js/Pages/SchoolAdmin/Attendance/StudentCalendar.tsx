import { router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, ChevronLeft, ChevronRight } from 'lucide-react';
import { Link } from '@inertiajs/react';
import type { Student, PageProps } from '@/Types';

type AttendanceStatus = 'present' | 'absent' | 'late' | 'half_day';

interface AttendanceRecord {
    date: string;
    status: AttendanceStatus;
    remarks: string | null;
}

interface Props {
    student: Student;
    records: Record<string, AttendanceRecord>;
    month: string;
}

const STATUS_STYLE: Record<AttendanceStatus, { bg: string; label: string }> = {
    present:  { bg: 'bg-green-500',  label: 'P'  },
    absent:   { bg: 'bg-red-500',    label: 'A'  },
    late:     { bg: 'bg-amber-400',  label: 'L'  },
    half_day: { bg: 'bg-blue-400',   label: 'H'  },
};

function getDaysInMonth(year: number, month: number) {
    return new Date(year, month, 0).getDate();
}

function getFirstDayOfMonth(year: number, month: number) {
    return new Date(year, month - 1, 1).getDay(); // 0=Sun
}

export default function StudentCalendar({ student, records, month }: Props) {
    const [year, mon] = month.split('-').map(Number);
    const daysInMonth  = getDaysInMonth(year, mon);
    const firstDay     = getFirstDayOfMonth(year, mon);

    const monthLabel   = new Date(year, mon - 1, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

    function navigate(dir: -1 | 1) {
        let y = year, m = mon + dir;
        if (m < 1) { y--; m = 12; }
        if (m > 12) { y++; m = 1; }
        const newMonth = `${y}-${String(m).padStart(2, '0')}`;
        router.get(`/school/attendance/students/${student.id}/calendar`, { month: newMonth }, { preserveScroll: true });
    }

    const today = new Date().toISOString().slice(0, 10);

    // Summary
    const summary = { present: 0, absent: 0, late: 0, half_day: 0 };
    Object.values(records).forEach(r => { summary[r.status]++; });

    return (
        <AppLayout title={`Attendance — ${student.full_name}`}>
            <div className="max-w-3xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <Link href={`/school/students/${student.id}`} className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                        <ArrowLeft className="w-4 h-4" /> Back to Profile
                    </Link>
                    <Link href="/school/attendance">
                        <Button variant="outline" size="sm">Mark Attendance</Button>
                    </Link>
                </div>

                {/* Student info */}
                <div className="flex items-center gap-4">
                    <div className="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-950 flex items-center justify-center text-lg font-bold text-indigo-600 dark:text-indigo-400 shrink-0">
                        {student.first_name[0]}
                    </div>
                    <div>
                        <h1 className="text-xl font-bold text-slate-900 dark:text-white">{student.full_name}</h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            {(student as any).school_class?.name ?? ''} {(student as any).section?.name ? `/ ${(student as any).section.name}` : ''} · Adm {student.admission_no}
                        </p>
                    </div>
                </div>

                {/* Summary badges */}
                <div className="flex gap-3 flex-wrap">
                    {[
                        { key: 'present',  label: 'Present',  color: 'bg-green-100 text-green-700' },
                        { key: 'absent',   label: 'Absent',   color: 'bg-red-100 text-red-700'     },
                        { key: 'late',     label: 'Late',     color: 'bg-amber-100 text-amber-700' },
                        { key: 'half_day', label: 'Half Day', color: 'bg-blue-100 text-blue-700'   },
                    ].map(({ key, label, color }) => (
                        <div key={key} className={`px-3 py-1.5 rounded-lg ${color} text-sm font-medium`}>
                            {label}: <span className="font-bold">{summary[key as AttendanceStatus]}</span>
                        </div>
                    ))}
                </div>

                {/* Calendar */}
                <Card className="border-slate-200 dark:border-slate-800">
                    <CardHeader className="pb-3">
                        <div className="flex items-center justify-between">
                            <CardTitle className="text-base">{monthLabel}</CardTitle>
                            <div className="flex gap-1">
                                <Button variant="ghost" size="icon" className="w-8 h-8" onClick={() => navigate(-1)}>
                                    <ChevronLeft className="w-4 h-4" />
                                </Button>
                                <Button variant="ghost" size="icon" className="w-8 h-8" onClick={() => navigate(1)}>
                                    <ChevronRight className="w-4 h-4" />
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {/* Day labels */}
                        <div className="grid grid-cols-7 mb-2">
                            {['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].map(d => (
                                <div key={d} className="text-center text-xs font-medium text-slate-400 py-1">{d}</div>
                            ))}
                        </div>

                        {/* Calendar grid */}
                        <div className="grid grid-cols-7 gap-1">
                            {/* Empty cells before first day */}
                            {Array.from({ length: firstDay }).map((_, i) => (
                                <div key={`empty-${i}`} />
                            ))}

                            {/* Day cells */}
                            {Array.from({ length: daysInMonth }).map((_, i) => {
                                const day  = i + 1;
                                const dateStr = `${year}-${String(mon).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                                const rec  = records[dateStr];
                                const isToday = dateStr === today;
                                const status = rec?.status;
                                const style  = status ? STATUS_STYLE[status] : null;

                                return (
                                    <div
                                        key={day}
                                        className={`relative aspect-square rounded-lg flex flex-col items-center justify-center text-sm font-medium transition-all
                                            ${style ? style.bg + ' text-white' : 'bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-300'}
                                            ${isToday && !style ? 'ring-2 ring-indigo-400' : ''}
                                        `}
                                        title={rec?.remarks ?? (status ?? '')}
                                    >
                                        <span className="text-xs font-bold">{day}</span>
                                        {style && <span className="text-[10px] opacity-90">{style.label}</span>}
                                    </div>
                                );
                            })}
                        </div>

                        {/* Legend */}
                        <div className="flex gap-3 mt-4 flex-wrap">
                            {Object.entries(STATUS_STYLE).map(([key, { bg, label }]) => (
                                <div key={key} className="flex items-center gap-1.5 text-xs text-slate-500">
                                    <div className={`w-4 h-4 rounded ${bg} flex items-center justify-center text-white text-[10px] font-bold`}>{label}</div>
                                    <span className="capitalize">{key.replace('_', ' ')}</span>
                                </div>
                            ))}
                            <div className="flex items-center gap-1.5 text-xs text-slate-500">
                                <div className="w-4 h-4 rounded bg-slate-100 dark:bg-slate-800" />
                                <span>No record</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
