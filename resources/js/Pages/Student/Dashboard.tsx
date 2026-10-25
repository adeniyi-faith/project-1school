import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Link } from '@inertiajs/react';
import ExpandableText from '@/components/ExpandableText';
import {
    GraduationCap, ClipboardList, BookOpen, Banknote,
    Bell, Calendar, CheckCircle, XCircle, Clock, AlertTriangle,
    TrendingUp, User, PlayCircle, Download,
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
interface LatestTerm {
    id: number; term: string; average: number | null; position: number | null; class_size: number | null;
    subjects: { subject: string | null; total: number | null; grade: string | null }[];
}
interface Today {
    lesson: TT | null;
    homework_due: { id: number; title: string; subject: string | null; due: string }[];
    open_tests: { id: number; title: string; subject: string | null; minutes: number }[];
}
interface TT     { subject: string | null; start_time: string; end_time: string; room: string | null; }
interface Announcement { id: number; title: string; body: string; pinned: boolean; date: string | null; }

interface Props {
    linked: boolean; student: Student | null;
    attendance: Attendance; exams: Exam[]; homework: HW[];
    fees: Fees; latestTerm: LatestTerm | null; today: Today | null; timetableToday: TT[]; announcements: Announcement[];
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

export default function StudentDashboard({ linked, student, attendance, exams, homework, fees, latestTerm, today, timetableToday, announcements }: Props) {
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
                <div className="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 p-4 sm:p-6 text-white flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:gap-5">
                    <div className="flex min-w-0 flex-1 items-center gap-3 sm:gap-5">
                    <div className="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-white/20 flex items-center justify-center overflow-hidden shrink-0">
                        {student.photo_url
                            ? <img src={student.photo_url} alt={student.full_name} className="w-full h-full object-cover" />
                            : <User className="w-8 h-8 text-white/80" />}
                    </div>
                    <div className="flex-1 min-w-0">
                        <h1 className="!text-base sm:!text-xl font-bold leading-tight">{student.full_name}</h1>
                        <p className="text-white/80 text-sm">{student.class} — {student.section} · Adm# {student.admission_no}</p>
                        {student.guardian && (
                            <p className="text-white/70 text-xs mt-0.5">Guardian: {student.guardian.name} · {student.guardian.phone}</p>
                        )}
                    </div>
                    </div>
                    {/* Today's attendance badge */}
                    <div className={cn('flex items-center gap-1.5 px-3 py-1 sm:py-1.5 rounded-full text-xs sm:text-sm font-medium', attColor[attendance.today])}>
                        {attIcon[attendance.today]}
                        <span className="capitalize">{attendance.today.replace('-', ' ')}</span>
                    </div>
                </div>

                {/* Today: what to do now */}
                {today && (today.lesson || today.homework_due.length > 0 || today.open_tests.length > 0) && (
                    <div className="space-y-2">
                        {today.open_tests.map(t => (
                            <div key={t.id} className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-500/30 dark:bg-indigo-500/10">
                                <div className="min-w-0">
                                    <p className="text-xs font-medium uppercase tracking-wide text-indigo-600 dark:text-indigo-300">Test open now</p>
                                    <p className="truncate font-semibold text-slate-900 dark:text-white">{t.title}</p>
                                    <p className="text-xs text-slate-500">{[t.subject, `${t.minutes} minutes`].filter(Boolean).join(' · ')}</p>
                                </div>
                                <Link href={`/school/student/cbt/${t.id}`} className="inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-6 text-base font-semibold text-white hover:bg-indigo-700 sm:w-auto">
                                    <PlayCircle className="size-5" /> Start test
                                </Link>
                            </div>
                        ))}
                        {today.homework_due.length > 0 && (
                            <Link href="/school/student/homework" className="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">
                                <ClipboardList className="size-5 shrink-0 text-amber-600" />
                                <div className="min-w-0 text-sm">
                                    <p className="font-semibold text-slate-900 dark:text-white">Homework due {today.homework_due[0].due}{today.homework_due.length > 1 ? ` and ${today.homework_due.length - 1} more` : ''}</p>
                                    <p className="truncate text-slate-600 dark:text-slate-300">{today.homework_due.map(h => h.subject ? `${h.subject}: ${h.title}` : h.title).join(' · ')}</p>
                                </div>
                            </Link>
                        )}
                        {today.lesson && (
                            <div className="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.03]">
                                <Calendar className="size-5 shrink-0 text-indigo-500" />
                                <p className="text-sm"><span className="font-semibold">Next lesson: {today.lesson.subject ?? '—'}</span> <span className="text-slate-500">at {today.lesson.start_time}{today.lesson.room ? `, Room ${today.lesson.room}` : ''}</span></p>
                            </div>
                        )}
                    </div>
                )}

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
                                    <Banknote className="w-4 h-4 text-orange-600" />
                                </div>
                                <p className="text-xs text-slate-500 uppercase tracking-wide">Fees</p>
                            </div>
                            <p className={cn('text-xl font-bold', fees.balance > 0 ? 'text-red-600' : 'text-green-600')}>
                                {fees.balance > 0 ? `₦${fees.balance.toLocaleString()} due` : 'Paid up'}
                            </p>
                            <p className="text-xs text-slate-400 mt-0.5">Paid: ₦{fees.total_paid.toLocaleString()}</p>
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

                        {/* Latest published term result: same figures as the report card */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                    <TrendingUp className="w-4 h-4 text-green-500" /> Latest Term Result
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {!latestTerm ? (
                                    <p className="text-sm text-slate-400 text-center py-4">No published results yet.</p>
                                ) : (
                                    <div className="space-y-3">
                                        <p className="text-sm font-medium text-slate-700 dark:text-slate-300">{latestTerm.term}</p>
                                        <div className="flex flex-wrap gap-6">
                                            <div><p className="text-xs text-slate-400 uppercase">Average</p><p className="text-xl font-bold">{latestTerm.average ?? '—'}</p></div>
                                            <div><p className="text-xs text-slate-400 uppercase">Position</p><p className="text-xl font-bold">{latestTerm.position ?? '—'}{latestTerm.class_size ? <span className="text-sm font-normal text-slate-400"> of {latestTerm.class_size}</span> : null}</p></div>
                                        </div>
                                        <ul className="divide-y divide-slate-50 text-sm dark:divide-slate-800/50">
                                            {latestTerm.subjects.map((m, i) => (
                                                <li key={i} className="flex justify-between py-1.5">
                                                    <span className="text-slate-700 dark:text-slate-300">{m.subject ?? '—'}</span>
                                                    <span>{m.total ?? '—'} {m.grade && <span className="ml-2 font-semibold text-indigo-600 dark:text-indigo-400">{m.grade}</span>}</span>
                                                </li>
                                            ))}
                                        </ul>
                                        <div className="flex flex-wrap gap-2 pt-1">
                                            <Link href="/school/student/results" className="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">All results</Link>
                                            <a href={`/school/student/report-cards/${latestTerm.id}`} className="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400"><Download className="size-4" /> Report card</a>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Fee History */}
                        {fees.recent.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                        <Banknote className="w-4 h-4 text-orange-500" /> Fee History
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
                                                    <p className="text-sm font-semibold text-slate-800 dark:text-slate-200">₦{f.paid.toLocaleString()}</p>
                                                    {f.balance > 0 && <p className="text-xs text-red-500">₦{f.balance.toLocaleString()} due</p>}
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
                                                <ExpandableText text={a.body} className="text-xs text-slate-500 dark:text-slate-400 mt-0.5" />
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
