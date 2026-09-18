import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { GraduationCap, DollarSign, Bell, TrendingUp, Users, User, CheckCircle, AlertTriangle } from 'lucide-react';

interface Guardian { id: number; name: string; phone: string; email: string | null; }
interface ChildAttendance { total: number; present: number; absent: number; percentage: number; }
interface ChildFees {
    total_due: number; total_paid: number; balance: number;
    recent: { month: string; paid: number; balance: number; status: string }[];
}
interface Mark { exam: string | null; subject: string | null; marks: number | null; grade: string | null; absent: boolean; }
interface Child {
    id: number; full_name: string; admission_no: string;
    class: string | null; section: string | null; photo_url: string | null;
    attendance: ChildAttendance; fees: ChildFees; marks: Mark[];
}
interface Announcement { id: number; title: string; body: string; pinned: boolean; date: string | null; }

interface Props {
    linked: boolean; guardian: Guardian | null;
    children: Child[]; announcements: Announcement[];
}

function AttendanceBar({ pct, label }: { pct: number; label: string }) {
    return (
        <div>
            <div className="flex items-center justify-between text-xs mb-1">
                <span className="text-slate-500">{label}</span>
                <span className={cn('font-semibold', pct >= 75 ? 'text-green-600' : pct >= 50 ? 'text-amber-600' : 'text-red-600')}>{pct}%</span>
            </div>
            <div className="h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                <div className={cn('h-full rounded-full transition-all', pct >= 75 ? 'bg-green-500' : pct >= 50 ? 'bg-amber-500' : 'bg-red-500')}
                    style={{ width: `${pct}%` }} />
            </div>
        </div>
    );
}

function ChildCard({ child }: { child: Child }) {
    return (
        <Card>
            <CardContent className="p-5 space-y-4">
                {/* Child header */}
                <div className="flex items-center gap-3">
                    <div className="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center overflow-hidden shrink-0">
                        {child.photo_url
                            ? <img src={child.photo_url} alt={child.full_name} className="w-full h-full object-cover" />
                            : <User className="w-6 h-6 text-indigo-600 dark:text-indigo-400" />}
                    </div>
                    <div className="flex-1 min-w-0">
                        <p className="font-semibold text-slate-900 dark:text-white">{child.full_name}</p>
                        <p className="text-xs text-slate-500">{child.class} — {child.section} · {child.admission_no}</p>
                    </div>
                </div>

                {/* Attendance */}
                <div className="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-3 space-y-2">
                    <p className="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wide">Attendance (This Month)</p>
                    <AttendanceBar pct={child.attendance.percentage} label={`${child.attendance.present}/${child.attendance.total} days present`} />
                    <div className="flex gap-3 text-xs text-slate-500">
                        <span className="text-green-600 font-medium">{child.attendance.present} present</span>
                        <span className="text-red-500 font-medium">{child.attendance.absent} absent</span>
                    </div>
                </div>

                {/* Fees */}
                <div className="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-3">
                    <p className="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wide mb-2">Fee Status</p>
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-xs text-slate-500">Total Paid</p>
                            <p className="font-semibold text-green-600">৳{child.fees.total_paid.toLocaleString()}</p>
                        </div>
                        <div className="text-right">
                            <p className="text-xs text-slate-500">Balance Due</p>
                            <p className={cn('font-semibold', child.fees.balance > 0 ? 'text-red-600' : 'text-green-600')}>
                                {child.fees.balance > 0 ? `৳${child.fees.balance.toLocaleString()}` : 'Clear'}
                            </p>
                        </div>
                    </div>
                    {child.fees.recent.length > 0 && (
                        <div className="mt-2 space-y-1">
                            {child.fees.recent.map((f, i) => (
                                <div key={i} className="flex items-center justify-between text-xs">
                                    <span className="text-slate-500">{f.month}</span>
                                    <Badge variant={f.status === 'paid' ? 'default' : 'secondary'} className="text-[10px] h-4 px-1.5">{f.status}</Badge>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Recent Marks */}
                {child.marks.length > 0 && (
                    <div>
                        <p className="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wide mb-2">Recent Results</p>
                        <div className="space-y-1.5">
                            {child.marks.slice(0, 4).map((m, i) => (
                                <div key={i} className="flex items-center justify-between text-xs">
                                    <div className="min-w-0">
                                        <span className="text-slate-700 dark:text-slate-300 font-medium truncate block">{m.subject}</span>
                                        <span className="text-slate-400">{m.exam}</span>
                                    </div>
                                    <div className="flex items-center gap-2 shrink-0 ml-2">
                                        {m.absent ? (
                                            <span className="text-red-500">Absent</span>
                                        ) : (
                                            <>
                                                <span className="text-slate-600 dark:text-slate-300">{m.marks ?? '—'}</span>
                                                {m.grade && <span className="font-bold text-indigo-600 dark:text-indigo-400">{m.grade}</span>}
                                            </>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export default function ParentDashboard({ linked, guardian, children, announcements }: Props) {
    if (!linked || !guardian) {
        return (
            <AppLayout title="Parent Dashboard">
                <div className="flex flex-col items-center justify-center py-24 text-center">
                    <Users className="w-16 h-16 text-slate-300 mb-4" />
                    <h2 className="text-xl font-semibold text-slate-700 dark:text-slate-300">Account not linked</h2>
                    <p className="text-slate-500 mt-2 max-w-sm">Your account hasn't been linked to a guardian record yet. Please contact the school administrator.</p>
                </div>
            </AppLayout>
        );
    }

    const totalDue = children.reduce((sum, c) => sum + c.fees.balance, 0);
    const allPresent = children.every(c => c.attendance.percentage >= 75);

    return (
        <AppLayout title="Parent Dashboard">
            <div className="space-y-6">

                {/* Header */}
                <div className="rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 p-6 text-white flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-bold">Welcome, {guardian.name}</h1>
                        <p className="text-white/70 text-sm mt-0.5">
                            {children.length} {children.length === 1 ? 'child' : 'children'} enrolled
                            {guardian.phone && ` · ${guardian.phone}`}
                        </p>
                    </div>
                    <div className="text-right">
                        {totalDue > 0 ? (
                            <div className="flex items-center gap-2 bg-white/20 rounded-lg px-3 py-2">
                                <AlertTriangle className="w-4 h-4" />
                                <div>
                                    <p className="text-xs text-white/80">Total Due</p>
                                    <p className="font-bold">৳{totalDue.toLocaleString()}</p>
                                </div>
                            </div>
                        ) : (
                            <div className="flex items-center gap-2 bg-white/20 rounded-lg px-3 py-2">
                                <CheckCircle className="w-4 h-4" />
                                <p className="text-sm font-medium">All fees clear</p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Children Cards */}
                <div>
                    <h2 className="text-sm font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wide mb-3">
                        My Children ({children.length})
                    </h2>
                    <div className={cn('grid gap-6', children.length === 1 ? 'grid-cols-1 max-w-lg' : 'grid-cols-1 md:grid-cols-2')}>
                        {children.map(child => <ChildCard key={child.id} child={child} />)}
                    </div>
                </div>

                {/* Announcements */}
                {announcements.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                <Bell className="w-4 h-4 text-violet-500" /> School Announcements
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul className="divide-y divide-slate-100 dark:divide-slate-800">
                                {announcements.map(a => (
                                    <li key={a.id} className="py-3 first:pt-0 last:pb-0">
                                        <div className="flex items-start gap-2">
                                            {a.pinned && <span className="text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-medium shrink-0 mt-0.5">Pinned</span>}
                                            <div className="flex-1">
                                                <p className="text-sm font-medium text-slate-800 dark:text-slate-200">{a.title}</p>
                                                <p className="text-xs text-slate-500 mt-0.5 line-clamp-2">{a.body}</p>
                                                {a.date && <p className="text-[11px] text-slate-400 mt-1">{a.date}</p>}
                                            </div>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
