import AppLayout from '@/Layouts/AppLayout';
import { Link } from '@inertiajs/react';
import {
    AreaChart, Area, BarChart, Bar, PieChart, Pie, Cell,
    XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend,
} from 'recharts';
import {
    School, Users, GraduationCap, UserCog, CreditCard,
    TrendingUp, DollarSign, Package, Tag, AlertTriangle,
    ArrowUpRight, CheckCircle, Clock, Ban,
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

interface Kpi {
    totalSchools: number; activeSchools: number; suspendedSchools: number;
    totalUsers: number; totalStudents: number; totalStaff: number;
    activeSubscriptions: number; trialSubscriptions: number;
    totalRevenue: number; revenueThisMonth: number;
    totalPackages: number; totalCoupons: number;
}
interface GrowthPoint  { month: string; schools: number; users: number; }
interface RevenuePoint { month: string; revenue: number; }
interface TopSchool    { id: number; name: string; status: string; students: number; users: number; }
interface RecentSchool { id: number; name: string; status: string; email: string | null; city: string | null; created_at: string; }
interface ExpiringSub  { id: number; school: string; package: string; end_date: string; days_left: number; }
interface RecentUser   { id: number; name: string; email: string; school: string; created_at: string; }
interface PkgDist      { name: string; value: number; }

interface Props {
    kpi: Kpi;
    schoolGrowth: GrowthPoint[];
    revenueTrend: RevenuePoint[];
    statusDistribution: Record<string, number>;
    packageDistribution: PkgDist[];
    topSchools: TopSchool[];
    recentSchools: RecentSchool[];
    expiringSubscriptions: ExpiringSub[];
    recentUsers: RecentUser[];
}

const PIE_COLORS  = ['#6366f1','#22c55e','#f59e0b','#ef4444','#8b5cf6','#14b8a6'];
const AREA_COLOR  = '#6366f1';
const BAR_COLOR   = '#22c55e';

function fmt(n: number) {
    if (n >= 1_000_000) return `$${(n / 1_000_000).toFixed(1)}M`;
    if (n >= 1_000)     return `$${(n / 1_000).toFixed(1)}K`;
    return `$${n.toFixed(0)}`;
}

function KpiCard({ icon: Icon, label, value, sub, color, href }: {
    icon: React.ElementType; label: string; value: string | number;
    sub?: string; color: string; href?: string;
}) {
    const inner = (
        <div className={cn(
            'flex items-start justify-between p-5 rounded-xl border transition-shadow hover:shadow-md',
            'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800'
        )}>
            <div>
                <p className="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">{label}</p>
                <p className="text-2xl font-bold text-slate-900 dark:text-white mt-1">{value}</p>
                {sub && <p className="text-xs text-slate-400 mt-0.5">{sub}</p>}
            </div>
            <div className={cn('w-10 h-10 rounded-lg flex items-center justify-center shrink-0', color)}>
                <Icon className="w-5 h-5 text-white" />
            </div>
        </div>
    );
    return href ? <Link href={href}>{inner}</Link> : inner;
}

const statusColor: Record<string, string> = {
    active:    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    inactive:  'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
    suspended: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
};

export default function SuperAdminDashboard({
    kpi, schoolGrowth, revenueTrend, statusDistribution,
    packageDistribution, topSchools, recentSchools,
    expiringSubscriptions, recentUsers,
}: Props) {

    const statusPieData = Object.entries(statusDistribution).map(([name, value]) => ({ name, value }));

    return (
        <AppLayout title="Super Admin Dashboard">
            <div className="space-y-6">

                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Platform Overview</h1>
                        <p className="text-sm text-slate-500 mt-0.5">Real-time analytics across all schools</p>
                    </div>
                    <Link href="/super-admin/schools"
                        className="flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                        Manage Schools <ArrowUpRight className="w-4 h-4" />
                    </Link>
                </div>

                {/* KPI Row 1 — Schools & Users */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <KpiCard icon={School}        label="Total Schools"   value={kpi.totalSchools}
                        sub={`${kpi.activeSchools} active`}  color="bg-indigo-500" href="/super-admin/schools" />
                    <KpiCard icon={Users}         label="Platform Users"  value={kpi.totalUsers}
                        sub="all roles"                       color="bg-sky-500"    href="/super-admin/users" />
                    <KpiCard icon={GraduationCap} label="Total Students"  value={kpi.totalStudents.toLocaleString()}
                        sub="across all schools"              color="bg-violet-500" />
                    <KpiCard icon={UserCog}       label="Total Staff"     value={kpi.totalStaff.toLocaleString()}
                        sub="across all schools"              color="bg-teal-500" />
                </div>

                {/* KPI Row 2 — Revenue & Subscriptions */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <KpiCard icon={DollarSign}  label="Total Revenue"      value={fmt(kpi.totalRevenue)}
                        sub="all subscriptions"               color="bg-green-500" />
                    <KpiCard icon={TrendingUp}  label="Revenue This Month"  value={fmt(kpi.revenueThisMonth)}
                        sub="current month"                   color="bg-emerald-500" />
                    <KpiCard icon={CreditCard}  label="Active Subscriptions" value={kpi.activeSubscriptions}
                        sub={`${kpi.trialSubscriptions} trials`} color="bg-orange-500" href="/super-admin/subscriptions" />
                    <KpiCard icon={Package}    label="Active Packages"      value={kpi.totalPackages}
                        sub={`${kpi.totalCoupons} coupons`}   color="bg-pink-500"  href="/super-admin/packages" />
                </div>

                {/* Charts Row 1 */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {/* School & User Growth — spans 2 cols */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="text-sm font-semibold text-slate-700 dark:text-slate-300">
                                School & User Growth (12 months)
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={220}>
                                <AreaChart data={schoolGrowth} margin={{ top: 4, right: 8, left: -20, bottom: 0 }}>
                                    <defs>
                                        <linearGradient id="colorSchools" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%"  stopColor="#6366f1" stopOpacity={0.3} />
                                            <stop offset="95%" stopColor="#6366f1" stopOpacity={0} />
                                        </linearGradient>
                                        <linearGradient id="colorUsers" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%"  stopColor="#22c55e" stopOpacity={0.3} />
                                            <stop offset="95%" stopColor="#22c55e" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
                                    <XAxis dataKey="month" tick={{ fontSize: 11 }} />
                                    <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                    <Tooltip contentStyle={{ fontSize: 12 }} />
                                    <Legend wrapperStyle={{ fontSize: 12 }} />
                                    <Area type="monotone" dataKey="schools" name="Schools" stroke="#6366f1"
                                        fill="url(#colorSchools)" strokeWidth={2} dot={false} isAnimationActive={false} />
                                    <Area type="monotone" dataKey="users"   name="Users"   stroke="#22c55e"
                                        fill="url(#colorUsers)"   strokeWidth={2} dot={false} isAnimationActive={false} />
                                </AreaChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    {/* School Status Distribution */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-semibold text-slate-700 dark:text-slate-300">
                                Schools by Status
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {statusPieData.length > 0 ? (
                                <>
                                    <ResponsiveContainer width="100%" height={160}>
                                        <PieChart>
                                            <Pie data={statusPieData} dataKey="value" cx="50%" cy="50%"
                                                innerRadius={45} outerRadius={70} paddingAngle={3} isAnimationActive={false}>
                                                {statusPieData.map((_, i) => (
                                                    <Cell key={i} fill={PIE_COLORS[i % PIE_COLORS.length]} />
                                                ))}
                                            </Pie>
                                            <Tooltip contentStyle={{ fontSize: 12 }} />
                                        </PieChart>
                                    </ResponsiveContainer>
                                    <ul className="mt-2 space-y-1">
                                        {statusPieData.map((d, i) => (
                                            <li key={d.name} className="flex items-center justify-between text-xs">
                                                <span className="flex items-center gap-1.5">
                                                    <span className="w-2.5 h-2.5 rounded-full"
                                                        style={{ background: PIE_COLORS[i % PIE_COLORS.length] }} />
                                                    <span className="capitalize text-slate-600 dark:text-slate-400">{d.name}</span>
                                                </span>
                                                <span className="font-semibold text-slate-800 dark:text-slate-200">{d.value}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </>
                            ) : (
                                <div className="flex items-center justify-center h-40 text-slate-400 text-sm">No data</div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Charts Row 2 */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {/* Revenue Trend */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="text-sm font-semibold text-slate-700 dark:text-slate-300">
                                Subscription Revenue (6 months)
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={200}>
                                <BarChart data={revenueTrend} margin={{ top: 4, right: 8, left: -10, bottom: 0 }}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
                                    <XAxis dataKey="month" tick={{ fontSize: 11 }} />
                                    <YAxis tick={{ fontSize: 11 }} tickFormatter={v => `$${v}`} />
                                    <Tooltip contentStyle={{ fontSize: 12 }} formatter={(v: number) => [`$${v.toFixed(2)}`, 'Revenue']} />
                                    <Bar dataKey="revenue" name="Revenue" fill={BAR_COLOR} radius={[4, 4, 0, 0]} isAnimationActive={false} />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    {/* Package Distribution */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-semibold text-slate-700 dark:text-slate-300">
                                Subscriptions by Package
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {packageDistribution.length > 0 ? (
                                <>
                                    <ResponsiveContainer width="100%" height={160}>
                                        <PieChart>
                                            <Pie data={packageDistribution} dataKey="value" cx="50%" cy="50%"
                                                outerRadius={70} paddingAngle={3} isAnimationActive={false}>
                                                {packageDistribution.map((_, i) => (
                                                    <Cell key={i} fill={PIE_COLORS[i % PIE_COLORS.length]} />
                                                ))}
                                            </Pie>
                                            <Tooltip contentStyle={{ fontSize: 12 }} />
                                        </PieChart>
                                    </ResponsiveContainer>
                                    <ul className="mt-2 space-y-1">
                                        {packageDistribution.map((d, i) => (
                                            <li key={d.name} className="flex items-center justify-between text-xs">
                                                <span className="flex items-center gap-1.5">
                                                    <span className="w-2.5 h-2.5 rounded-full"
                                                        style={{ background: PIE_COLORS[i % PIE_COLORS.length] }} />
                                                    <span className="text-slate-600 dark:text-slate-400">{d.name}</span>
                                                </span>
                                                <span className="font-semibold text-slate-800 dark:text-slate-200">{d.value}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </>
                            ) : (
                                <div className="flex items-center justify-center h-40 text-slate-400 text-sm">No subscriptions yet</div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Bottom Row */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {/* Top Schools by Students */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-semibold text-slate-700 dark:text-slate-300">
                                Top Schools by Students
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {topSchools.length === 0 ? (
                                <p className="text-sm text-slate-400 text-center py-6">No data</p>
                            ) : (
                                <ul className="space-y-3">
                                    {topSchools.map((s, i) => (
                                        <li key={s.id} className="flex items-center gap-3">
                                            <span className="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 text-xs font-bold flex items-center justify-center shrink-0">
                                                {i + 1}
                                            </span>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium text-slate-800 dark:text-slate-200 truncate">{s.name}</p>
                                                <p className="text-xs text-slate-400">{s.users} users</p>
                                            </div>
                                            <div className="text-right shrink-0">
                                                <p className="text-sm font-semibold text-slate-800 dark:text-slate-200">{s.students}</p>
                                                <p className="text-xs text-slate-400">students</p>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    {/* Recent Schools */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle className="text-sm font-semibold text-slate-700 dark:text-slate-300">
                                Recent Schools
                            </CardTitle>
                            <Link href="/super-admin/schools" className="text-xs text-indigo-500 hover:text-indigo-600">View all</Link>
                        </CardHeader>
                        <CardContent>
                            {recentSchools.length === 0 ? (
                                <p className="text-sm text-slate-400 text-center py-6">No schools yet</p>
                            ) : (
                                <ul className="space-y-3">
                                    {recentSchools.map(s => (
                                        <li key={s.id} className="flex items-start gap-3">
                                            <div className="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center shrink-0">
                                                <School className="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium text-slate-800 dark:text-slate-200 truncate">{s.name}</p>
                                                <p className="text-xs text-slate-400">{s.city ?? s.email ?? '—'} · {s.created_at}</p>
                                            </div>
                                            <span className={cn('text-[10px] px-1.5 py-0.5 rounded-full font-medium shrink-0 capitalize', statusColor[s.status])}>
                                                {s.status}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    {/* Expiring Subscriptions + Recent Users stacked */}
                    <div className="space-y-6">
                        {/* Expiring Soon */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                                    <AlertTriangle className="w-4 h-4 text-amber-500" /> Expiring Soon
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {expiringSubscriptions.length === 0 ? (
                                    <div className="flex items-center gap-2 text-xs text-green-600 dark:text-green-400 py-2">
                                        <CheckCircle className="w-4 h-4" /> No subscriptions expiring in 30 days
                                    </div>
                                ) : (
                                    <ul className="space-y-2">
                                        {expiringSubscriptions.map(sub => (
                                            <li key={sub.id} className="flex items-center justify-between text-xs">
                                                <div className="min-w-0 flex-1">
                                                    <p className="font-medium text-slate-800 dark:text-slate-200 truncate">{sub.school}</p>
                                                    <p className="text-slate-400">{sub.package} · {sub.end_date}</p>
                                                </div>
                                                <span className={cn('ml-2 shrink-0 px-1.5 py-0.5 rounded font-semibold',
                                                    sub.days_left <= 7
                                                        ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                                                        : 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400'
                                                )}>
                                                    {sub.days_left}d
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>

                        {/* Recent Users */}
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle className="text-sm font-semibold text-slate-700 dark:text-slate-300">
                                    Recent Users
                                </CardTitle>
                                <Link href="/super-admin/users" className="text-xs text-indigo-500 hover:text-indigo-600">View all</Link>
                            </CardHeader>
                            <CardContent>
                                <ul className="space-y-2.5">
                                    {recentUsers.map(u => (
                                        <li key={u.id} className="flex items-center gap-2">
                                            <div className="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-500 shrink-0">
                                                {u.name[0]?.toUpperCase()}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-xs font-medium text-slate-800 dark:text-slate-200 truncate">{u.name}</p>
                                                <p className="text-[11px] text-slate-400 truncate">{u.school}</p>
                                            </div>
                                            <p className="text-[10px] text-slate-400 shrink-0">{u.created_at}</p>
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
