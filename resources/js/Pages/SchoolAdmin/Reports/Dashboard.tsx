import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Activity as ActivityIcon, Check, ChevronRight } from 'lucide-react';
import {
    Bar, BarChart, CartesianGrid, Legend, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis,
} from 'recharts';
import AppLayout from '@/Layouts/AppLayout';
import { EmptyState, PageHeader, PersonAvatar, Panel, StatStrip, type StatItem } from '@/components/app/kit';
import { naira, nairaCompact } from '@/lib/format';
import type { PageProps } from '@/Types';

interface Activity { id: number; description: string; causer?: { name: string }; created_at: string; }
interface Props {
    role:             string;
    totalStudents:    number;
    totalStaff:       number;
    attendancePct:    number;
    monthFees:        number;
    pendingFees:      number;
    pendingHomework:  number;
    todayCollection?: number;
    feeChart:         { month: string; amount: number }[];
    attChart:         { day: string; present: number; absent: number }[];
    recentActivity:   Activity[];
    schools?:         number;
    setup?:           { done: number; total: number; items: { key: string; title: string; text: string; href: string; done: boolean }[] } | null;
}

const AXIS = { fontSize: 12, fill: 'var(--color-slate-400)' };
const GRID = 'color-mix(in oklch, var(--color-slate-400) 22%, transparent)';

function ChartTip({ active, payload, label, money }: { active?: boolean; payload?: { name: string; value: number; color: string }[]; label?: string; money?: boolean }) {
    if (!active || !payload?.length) return null;
    return (
        <div className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs shadow-lg dark:border-white/10 dark:bg-slate-800">
            <p className="mb-1 font-medium text-slate-900 dark:text-white">{label}</p>
            {payload.map((p) => (
                <p key={p.name} className="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                    <span className="size-2 rounded-full" style={{ background: p.color }} />
                    <span className="capitalize">{p.name}</span>
                    <span className="ml-auto pl-3 font-semibold tabular-nums text-slate-900 dark:text-white">{money ? naira(p.value) : p.value}</span>
                </p>
            ))}
        </div>
    );
}

function timeAgo(iso: string) {
    const mins = Math.round((Date.now() - new Date(iso).getTime()) / 60000);
    if (mins < 1) return 'Just now';
    if (mins < 60) return `${mins} min ago`;
    const hrs = Math.round(mins / 60);
    if (hrs < 24) return `${hrs} hr ago`;
    return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short' });
}

export default function Dashboard({ role, totalStudents, totalStaff, attendancePct, monthFees, pendingFees, pendingHomework, todayCollection, feeChart, attChart, recentActivity, schools, setup }: Props) {
    const [hideSetup, setHideSetup] = useState(() => { try { return localStorage.getItem('setup-hidden') === '1'; } catch { return false; } });
    const { auth } = usePage<PageProps>().props;
    const fmt = (n: number) => new Intl.NumberFormat('en-NG').format(n);
    const firstName = auth.user?.name?.split(' ')[0] ?? '';
    const hour = new Date().getHours();
    const greeting = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';
    const today = new Date().toLocaleDateString('en-NG', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

    const stats: StatItem[] = role === 'super-admin'
        ? [
            { label: 'Schools', value: fmt(schools ?? 0) },
            { label: 'Students', value: fmt(totalStudents) },
            { label: 'Revenue', value: nairaCompact(monthFees) },
            { label: 'Outstanding fees', value: nairaCompact(pendingFees), tone: pendingFees > 0 ? 'warn' : 'default' },
        ]
        : [
            { label: 'Students', value: fmt(totalStudents), hint: 'Active this session' },
            { label: 'Staff', value: fmt(totalStaff), hint: 'Active members' },
            { label: 'Present today', value: `${attendancePct}%`, tone: attendancePct >= 90 ? 'good' : attendancePct >= 75 ? 'default' : 'warn', hint: 'Of students marked' },
            { label: 'Fees this month', value: nairaCompact(monthFees), hint: `${nairaCompact(pendingFees)} still outstanding` },
        ];

    const extras: StatItem[] = [];
    if (role !== 'super-admin') extras.push({ label: 'Outstanding fees', value: nairaCompact(pendingFees), tone: pendingFees > 0 ? 'warn' : 'default' });
    if (role === 'accountant' && todayCollection !== undefined) extras.push({ label: 'Collected today', value: naira(todayCollection), tone: 'good' });
    if (['school-admin', 'principal', 'teacher'].includes(role)) extras.push({ label: 'Homework pending', value: fmt(pendingHomework) });

    return (
        <AppLayout title="Dashboard">
            <div className="space-y-6">
                <PageHeader title={`${greeting}${firstName ? `, ${firstName}` : ''}`} description={today} />

                {setup && !hideSetup && (
                    <Panel title="Get your school ready" description={`${setup.done} of ${setup.total} steps done. Each one ticks itself when you finish it.`}
                        action={<button type="button" className="text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200" onClick={() => { try { localStorage.setItem('setup-hidden', '1'); } catch { /* ignore */ } setHideSetup(true); }}>Hide for now</button>}>
                        <div className="mb-3 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                            <div className="h-full rounded-full bg-indigo-500" style={{ width: `${Math.round((setup.done / setup.total) * 100)}%` }} />
                        </div>
                        <ul className="grid gap-1 sm:grid-cols-2">
                            {setup.items.map((i) => (
                                <li key={i.key}>
                                    <Link href={i.href} className="flex items-start gap-3 rounded-lg px-2 py-2 hover:bg-slate-50 dark:hover:bg-white/5">
                                        <span className={`mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border ${i.done ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-300 dark:border-white/20'}`}>
                                            {i.done && <Check className="size-3" />}
                                        </span>
                                        <span className="min-w-0 flex-1">
                                            <span className={`block text-sm font-medium ${i.done ? 'text-slate-400 line-through' : 'text-slate-800 dark:text-slate-100'}`}>{i.title}</span>
                                            {!i.done && <span className="block text-xs text-slate-500">{i.text}</span>}
                                        </span>
                                        {!i.done && <ChevronRight className="mt-1 size-4 shrink-0 text-slate-400" />}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </Panel>
                )}

                <StatStrip items={stats} />
                {extras.length > 0 && role !== 'super-admin' && <StatStrip items={extras} className={extras.length === 1 ? 'sm:max-w-xs' : ''} />}

                <div className="grid gap-4 lg:grid-cols-2">
                    <Panel title="Fees collected" description="Last 6 months">
                        {feeChart.length > 0 ? (
                            <ResponsiveContainer width="100%" height={240}>
                                <BarChart data={feeChart} margin={{ top: 8, right: 4, left: -8, bottom: 0 }} barCategoryGap="28%">
                                    <CartesianGrid vertical={false} stroke={GRID} />
                                    <XAxis dataKey="month" tick={AXIS} axisLine={false} tickLine={false} />
                                    <YAxis tick={AXIS} axisLine={false} tickLine={false} tickFormatter={nairaCompact} width={64} />
                                    <Tooltip cursor={{ fill: 'color-mix(in oklch, var(--color-indigo-500) 8%, transparent)' }} content={<ChartTip money />} />
                                    <Bar dataKey="amount" name="Collected" fill="var(--color-indigo-500)" radius={[5, 5, 0, 0]} maxBarSize={36} isAnimationActive={false} />
                                </BarChart>
                            </ResponsiveContainer>
                        ) : <EmptyState icon={ActivityIcon} title="No payments yet" text="Collected fees will chart here." />}
                    </Panel>

                    <Panel title="Attendance" description="Students present and absent, last 7 days">
                        {attChart.length > 0 ? (
                            <ResponsiveContainer width="100%" height={240}>
                                <LineChart data={attChart} margin={{ top: 8, right: 8, left: -20, bottom: 0 }}>
                                    <CartesianGrid vertical={false} stroke={GRID} />
                                    <XAxis dataKey="day" tick={AXIS} axisLine={false} tickLine={false} />
                                    <YAxis tick={AXIS} axisLine={false} tickLine={false} allowDecimals={false} />
                                    <Tooltip content={<ChartTip />} />
                                    <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: 12, paddingTop: 8 }} />
                                    <Line type="monotone" dataKey="present" name="Present" stroke="var(--color-indigo-500)" strokeWidth={2.25} dot={false} activeDot={{ r: 4 }} isAnimationActive={false} />
                                    <Line type="monotone" dataKey="absent" name="Absent" stroke="oklch(0.65 0.19 22)" strokeWidth={2.25} dot={false} activeDot={{ r: 4 }} isAnimationActive={false} />
                                </LineChart>
                            </ResponsiveContainer>
                        ) : <EmptyState icon={ActivityIcon} title="No attendance marked" text="Daily registers will chart here." />}
                    </Panel>
                </div>

                {recentActivity && recentActivity.length > 0 && (
                    <Panel title="Recent activity" flush bodyClassName="divide-y divide-slate-100 dark:divide-white/[0.06]">
                        {recentActivity.map((a) => (
                            <div key={a.id} className="flex items-center gap-3 px-5 py-3">
                                <PersonAvatar name={a.causer?.name ?? '?'} className="size-8" />
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm text-slate-800 dark:text-slate-200">{a.description}</p>
                                    <p className="text-xs text-slate-500">{a.causer?.name}</p>
                                </div>
                                <time className="shrink-0 text-xs text-slate-400" dateTime={a.created_at}>{timeAgo(a.created_at)}</time>
                            </div>
                        ))}
                    </Panel>
                )}
            </div>
        </AppLayout>
    );
}
