import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { CalendarDays } from 'lucide-react';

interface Slot { subject: string | null; start_time: string; end_time: string; room: string | null; }
interface Props {
    linked: boolean;
    student: { full_name: string; class: string | null; section: string | null } | null;
    timetable: Record<string, Slot[]>;
    today: string;
}

const DAYS = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
const COLORS = ['bg-indigo-50 border-indigo-200 text-indigo-700','bg-violet-50 border-violet-200 text-violet-700',
    'bg-sky-50 border-sky-200 text-sky-700','bg-emerald-50 border-emerald-200 text-emerald-700',
    'bg-amber-50 border-amber-200 text-amber-700','bg-rose-50 border-rose-200 text-rose-700',
    'bg-teal-50 border-teal-200 text-teal-700','bg-pink-50 border-pink-200 text-pink-700'];

export default function Timetable({ linked, student, timetable, today }: Props) {
    if (!linked) {
        return (
            <AppLayout title="My Timetable">
                <div className="flex flex-col items-center justify-center py-24 text-center">
                    <CalendarDays className="w-14 h-14 text-slate-300 mb-4" />
                    <h2 className="text-lg font-semibold text-slate-600 dark:text-slate-400">Account not linked</h2>
                </div>
            </AppLayout>
        );
    }

    const activeDays = DAYS.filter(d => timetable[d]?.length > 0);

    return (
        <AppLayout title="My Timetable">
            <div className="space-y-6">
                <div>
                    <h1 className="text-xl font-bold text-slate-900 dark:text-white">My Timetable</h1>
                    <p className="text-sm text-slate-500">{student?.class} — {student?.section}</p>
                </div>

                {activeDays.length === 0 ? (
                    <Card>
                        <CardContent className="py-16 text-center text-slate-400">No timetable scheduled yet.</CardContent>
                    </Card>
                ) : (
                    <div className="space-y-4">
                        {activeDays.map(day => (
                            <Card key={day} className={cn(day === today && 'ring-2 ring-indigo-400')}>
                                <CardHeader className="py-3 px-5">
                                    <CardTitle className="flex items-center gap-2 text-sm font-semibold capitalize">
                                        {day}
                                        {day === today && <Badge className="text-[10px] h-4 px-1.5 bg-indigo-600">Today</Badge>}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="px-5 pb-4">
                                    <div className="flex flex-wrap gap-2">
                                        {timetable[day].map((slot, i) => (
                                            <div key={i} className={cn('border rounded-lg px-3 py-2 text-xs font-medium', COLORS[i % COLORS.length])}>
                                                <p className="font-semibold">{slot.subject ?? '—'}</p>
                                                <p className="opacity-80">{slot.start_time} – {slot.end_time}</p>
                                                {slot.room && <p className="opacity-60">Room {slot.room}</p>}
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
