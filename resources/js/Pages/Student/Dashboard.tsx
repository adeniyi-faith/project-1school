import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    GraduationCap, ClipboardList, BookOpen, DollarSign,
    Bell, Calendar, CheckCircle, XCircle, Clock, AlertTriangle,
    TrendingUp, User,
} from 'lucide-react';

interface Student {
    id: number; full_name: string; admission_no: string;
    class: string; section: string; photo_url: string | null;
    guardian: { name: string; phone: string } | null;
}
interface Attendance {
    total: number; present: number; absent: number; late: number;
    percentage: number; today: string;
    calendar: { date: string; status: string }[];
}
interface Exam   { id: number; name: string; type: string; start_date: string; days_away: number; }
interface HW     { id: number; title: string; subject: string | null; due: string; overdue: boolean; }
interface Fees   {
    total_due: number; total_paid: number; balance: number;
    recent: { id: number; month: string; due: number; paid: number; balance: number; status: string; date: string | null }[];
}
interface Mark   { exam: string | null; subject: string | null; marks: number | null; grade: string | null; absent: boolean; }
interface TT     { subject: string | null; start_time: string; end_time: string; room: string | null; }
interface Announcement { id: number; title: string; body: string; pinned: boolean; date: string | null; }

interface Props {
    linked: boolean; student: Student | null;
    attendance: Attendance; exams: Exam[]; homework: HW[];
    fees: Fees; marks: Mark[]; timetableToday: TT[]; announcements: Announcement[];
}

const attColor: Record<string, string> = {
    present:    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    absent:     'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    late:       'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    'not-marked':'bg-slate-100 text-slate-500 dark:bg-slate-800',
};
const attIcon: Record<string, React.ReactNode> = {
    present:    <CheckCircle className="w-4 h-4" />,
    absent:     <XCircle className="w-4 h-4" />,
    late:       <Clock className="w-4 h-4" />,
    'not-marked': <Clock className="w-4 h-4" />,
};

function AttendanceCircle({ pct }: { pct: number }) {
    const r = 36, c = 2 * Math.PI * r;
    const dash = (pct / 100) * c;
    return (
        <svg width="90" height="90" viewBox="0 0 90 90">
            <circle cx="45" cy="45" r={r} fill="none" stroke="#e2e8f0" strokeWidth="8" />
            <circle cx="45" cy="45" r={r} fill="none"
                stroke={pct >= 75 ? '#22c55e' : pct >= 50 ? '#f59e0b' : '#ef4444'}
                strokeWidth="8" strokeLinecap="round"
                strokeDasharray={`${dash} ${c}`}
                transform="rotate(-90 45 45)" />
            <text x="45" y="49" textAnchor="middle" fontSize="16" fontWeight="bold" fill="currentColor">{pct}%</text>
        </svg>
    );
}

export default function StudentDashboard({ linked, student, attendance, exams, homework, fees, marks, timetableToday, announcements }: Props) {
    if (!linked || !student) {
        return (
            <AppLayout title="My Dashboard">
                <div className="flex flex-col items-center justify-center py-24 text-center">
                    <GraduationCap className="w-16 h-16 text-slate-300 mb-4" />
                    <h2 className="text-xl font-semibold text-slate-700 dark:text-slate-300">Account not linked</h2>
                    <p className="text-slate-500 mt-2 max-w-sm">Your user account hasn't been linked to a student record yet. Please contact your school administrator.</p>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout title="My Dashboard">
            <div className="space-y-6">

                {/* Profile Banner */}
                <div className="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 p-6 text-white flex items-center gap-5">
                    <div className="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center overflow-hidden shrink-0">
                        {student.photo_url
                            ? <img src={student.photo_url} alt={student.full_name} className="w-full h-full object-cover" />
                            : <User className="w-8 h-8 text-white/80" />}
                    </div>
                    <div className="flex-1 min-w-0">
                        <h1 className="text-xl font-bold">{student.full_name}</h1>
                        <p className="text-white/80 text-sm">{student.class} — {student.section} · Adm# {student.admission_no}</p>
                        {student.guardian && (
                            <p className="text-white/70 text-xs mt-0.5">Guardian: {student.guardian.name} · {student.guardian.phone}</p>
                        )}
                    </div>
                    {/* Today's attendance badge */}
                    <div className={cn('flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium', attColor[attendance.today])}>
                        {attIcon[attendance.today]}
                        <span className="capitalize">{attendance.today.replace('-', ' ')}</span>
                    </div>
                </div>

                {/* KPI Row */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Attendance */}
                    <Card>
                        <CardContent className="p-4 flex items-center gap-4">
                            <AttendanceCircle pct={attendance.percentage} />
                            <div>
                                <p className="text-xs text-slate-500 uppercase tracking-wide">Attendance</p>
                                <p className="text-sm font-semibold mt-1">{attendance.present}/{attendance.total} days</p>
                                <p className="text-xs text-slate-400">{attendance.absent} absent · {attendance.late} late</p>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Fees */}
                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 mb-2">
                                <div className="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                                    <DollarSign className="w-4 h-4 text-orange-600" />
                                </div>
                                <p className="text-xs text-slate-500 uppercase tracking-wide">Fees</p>
                            </div>
                            <p className={cn('text-xl font-bold', fees.balance > 0 ? 'text-red-600' : 'text-green-600')}>
                                {fees.balance > 0 ? `৳${fees.balance.toLocaleString()} due` : 'Paid up'}
                            </p>
                            <p className="text-xs text-slate-400 mt-0.5">Paid: ৳{fees.total_paid.toLocaleString()}</p>
                        </CardContent>
                    </Card>

                    {/* Upcoming Exams */}
                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 mb-2">
                                <div className="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                    <BookOpen className="w-4 h-4 text-indigo-600" />
                                </div>
                                <p className="text-xs text-slate-500 uppercase tracking-wide">Exams</p>
                            </div>
                            <p className="text-xl font-bold text-slate-900 dark:text-white">{exams.length}</p>
                            <p className="text-xs text-slate-400 mt-0.5">upcoming</p>
                        </CardContent>
                    </Card>

                    {/* Homework */}
                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 mb-2">
                                <div className="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                    <ClipboardList className="w-4 h-4 text-amber-600" />
                                </div>
                                <p className="text-xs text-slate-500 uppercase tracking-wide">Homework</p>
                            </div>
                            <p className="text-xl font-bold text-slate-900 dark:text-white">{homework.length}</p>
                            <p className="text-xs text-slate-400 mt-0.5">
                                {homework.filter(h => h.overdue).length > 0
                                    ? <span className="text-red-500">{homework.filter(h => h.overdue).length} overdue</span>
                                    : 'all on track'}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Main content grid */}
                <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">

                    {/* Left column */}
                    <div className="lg:col-span-3 space-y-6">

                        {/* Today's Timetable */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                    <Calendar className="w-4 h-4 text-indigo-500" /> Today's Schedule
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {timetableToday.length === 0 ? (
                                    <p className="text-sm text-slate-400 text-center py-4">No classes scheduled today.</p>
                                ) : (
                                    <div className="space-y-2">
                                        {timetableToday.map((t, i) => (
                                            <div key={i} className="flex items-center gap-3 p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                                                <div className="w-16 text-xs text-slate-500 shrink-0">
                                                    <div>{t.start_time}</div>
                                                    <div>{t.end_time}</div>
                                                </div>
                                                <div className="w-1 h-8 rounded-full bg-indigo-400 shrink-0" />
                                                <div>
                                                    <p className="text-sm font-medium text-slate-800 dark:text-slate-200">{t.subject ?? '—'}</p>
                                                    {t.room && <p className="text-xs text-slate-400">Room {t.room}</p>}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Recent Marks */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                    <TrendingUp className="w-4 h-4 text-green-500" /> Recent Exam Results
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {marks.length === 0 ? (
                                    <p className="text-sm text-slate-400 text-center py-4">No results yet.</p>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm">
                                            <thead>
                                                <tr className="text-xs uppercase text-slate-400 border-b border-slate-100 dark:border-slate-800">
                                                    <th className="text-left py-2 font-medium">Exam</th>
                                                    <th className="text-left py-2 font-medium">Subject</th>
                                                    <th className="text-center py-2 font-medium">Marks</th>
                                                    <th className="text-center py-2 font-medium">Grade</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {marks.map((m, i) => (
                                                    <tr key={i} className="border-b border-slate-50 dark:border-slate-800/50">
                                                        <td className="py-2 text-slate-700 dark:text-slate-300">{m.exam ?? '—'}</td>
                                                        <td className="py-2 text-slate-500">{m.subject ?? '—'}</td>
                                                        <td className="py-2 text-center">
                                                            {m.absent ? <span className="text-xs text-red-500">Absent</span> : (m.marks ?? '—')}
                                                        </td>
                                                        <td className="py-2 text-center">
                                                            {m.grade ? <span className="font-semibold text-indigo-600 dark:text-indigo-400">{m.grade}</span> : '—'}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Fee History */}
                        {fees.recent.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                        <DollarSign className="w-4 h-4 text-orange-500" /> Fee History
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-2">
                                        {fees.recent.map(f => (
                                            <div key={f.id} className="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                                                <div>
                                                    <p className="text-sm font-medium text-slate-800 dark:text-slate-200">{f.month}</p>
                                                    {f.date && <p className="text-xs text-slate-400">Paid: {f.date}</p>}
                                                </div>
                                                <div className="text-right">
                                                    <p className="text-sm font-semibold text-slate-800 dark:text-slate-200">৳{f.paid.toLocaleString()}</p>
                                                    {f.balance > 0 && <p className="text-xs text-red-500">৳{f.balance.toLocaleString()} due</p>}
                                                </div>
                                                <Badge variant={f.status === 'paid' ? 'default' : 'secondary'} className="ml-3">
                                                    {f.status}
                                                </Badge>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Right column */}
                    <div className="lg:col-span-2 space-y-6">

                        {/* Upcoming Exams */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                    <BookOpen className="w-4 h-4 text-indigo-500" /> Upcoming Exams
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {exams.length === 0 ? (
                                    <p className="text-sm text-slate-400 text-center py-4">No upcoming exams.</p>
                                ) : (
                                    <ul className="space-y-2.5">
                                        {exams.map(e => (
                                            <li key={e.id} className="flex items-start gap-3">
                                                <div className={cn('min-w-[44px] px-1.5 py-1 rounded-lg flex flex-col items-center justify-center text-center shrink-0 text-xs font-bold',
                                                    e.days_away <= 3 ? 'bg-red-100 text-red-600' : e.days_away <= 7 ? 'bg-amber-100 text-amber-600' : 'bg-indigo-100 text-indigo-600')}>
                                                    <span className="text-sm leading-tight tabular-nums">{e.days_away}</span>
                                                    <span className="text-[10px]">days</span>
                                                </div>
                                                <div className="min-w-0">
                                                    <p className="text-sm font-medium text-slate-800 dark:text-slate-200 truncate">{e.name}</p>
                                                    <p className="text-xs text-slate-400">{e.start_date} · {e.type}</p>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>

                        {/* Homework */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                    <ClipboardList className="w-4 h-4 text-amber-500" /> Homework Due
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {homework.length === 0 ? (
                                    <p className="text-sm text-slate-400 text-center py-4">No pending homework.</p>
                                ) : (
                                    <ul className="space-y-2">
                                        {homework.map(h => (
                                            <li key={h.id} className="flex items-center gap-2">
                                                <div className={cn('w-2 h-2 rounded-full shrink-0', h.overdue ? 'bg-red-500' : 'bg-green-500')} />
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-medium text-slate-800 dark:text-slate-200 truncate">{h.title}</p>
                                                    <p className="text-xs text-slate-400">{h.subject}</p>
                                                </div>
                                                <span className={cn('text-xs shrink-0 font-medium', h.overdue ? 'text-red-500' : 'text-slate-400')}>
                                                    {h.overdue ? 'Overdue' : h.due}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>

                        {/* Announcements */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                    <Bell className="w-4 h-4 text-violet-500" /> Announcements
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {announcements.length === 0 ? (
                                    <p className="text-sm text-slate-400 text-center py-4">No announcements.</p>
                                ) : (
                                    <ul className="space-y-2">
                                        {announcements.map(a => (
                                            <li key={a.id} className={cn(
                                                'rounded-lg px-3 py-2.5',
                                                a.pinned
                                                    ? 'bg-amber-50 border border-amber-200 dark:bg-amber-950/20 dark:border-amber-800/40'
                                                    : 'bg-slate-50 dark:bg-slate-800/40'
                                            )}>
                                                {a.pinned && (
                                                    <div className="flex items-center gap-1.5 mb-1">
                                                        <span className="text-[10px] font-bold uppercase tracking-wide text-amber-600 dark:text-amber-400">📌 Pinned</span>
                                                    </div>
                                                )}
                                                <p className={cn('text-sm font-semibold', a.pinned ? 'text-amber-900 dark:text-amber-200' : 'text-slate-800 dark:text-slate-200')}>{a.title}</p>
                                                <p className="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5">{a.body}</p>
                                                {a.date && <p className="text-[11px] text-slate-400 mt-1">{a.date}</p>}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
