import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ClipboardList } from 'lucide-react';

interface DayEntry { date: string; status: string; }
interface MonthData { label: string; total: number; present: number; absent: number; late: number; percentage: number; calendar: DayEntry[]; }
interface Child { id: number; full_name: string; class: string | null; section: string | null; months: MonthData[]; }
interface Props { linked: boolean; guardian: { name: string } | null; children: Child[]; }

const STATUS_COLOR: Record<string, string> = {
    present: 'bg-green-500', absent: 'bg-red-500', late: 'bg-amber-400',
};

export default function ParentAttendance({ linked, children }: Props) {
    if (!linked) {
        return (
            <AppLayout title="Children Attendance">
                <div className="flex flex-col items-center justify-center py-24 text-center">
                    <ClipboardList className="w-14 h-14 text-slate-300 mb-4" />
                    <h2 className="text-lg font-semibold text-slate-600 dark:text-slate-400">Account not linked</h2>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout title="Children Attendance">
            <div className="space-y-8">
                <h1 className="text-xl font-bold text-slate-900 dark:text-white">Children Attendance</h1>
                {children.map(child => (
                    <div key={child.id}>
                        <p className="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">
                            {child.full_name} — {child.class} {child.section && `(${child.section})`}
                        </p>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {child.months.map(m => (
                                <Card key={m.label}>
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-sm flex justify-between">
                                            <span>{m.label}</span>
                                            <span className={cn('font-bold', m.percentage >= 75 ? 'text-green-600' : m.percentage >= 50 ? 'text-amber-600' : 'text-red-600')}>{m.percentage}%</span>
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-2">
                                        <div className="flex gap-3 text-xs">
                                            <span className="text-green-600">{m.present} Present</span>
                                            <span className="text-red-500">{m.absent} Absent</span>
                                            <span className="text-amber-600">{m.late} Late</span>
                                        </div>
                                        <div className="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                            <div className={cn('h-full rounded-full', m.percentage >= 75 ? 'bg-green-500' : m.percentage >= 50 ? 'bg-amber-500' : 'bg-red-500')}
                                                style={{ width: `${m.percentage}%` }} />
                                        </div>
                                        <div className="flex flex-wrap gap-1 pt-1">
                                            {m.calendar.map((d, i) => (
                                                <div key={i} title={`${d.date}: ${d.status}`}
                                                    className={cn('w-3.5 h-3.5 rounded-sm', STATUS_COLOR[d.status] ?? 'bg-slate-200')} />
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </AppLayout>
    );
}
