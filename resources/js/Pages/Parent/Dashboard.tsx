import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { EmptyState, PageHeader, Panel, PersonAvatar, Pill } from '@/components/app/kit';
import { naira } from '@/lib/format';
import { Bell, Users } from 'lucide-react';

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
    const tone = pct >= 75 ? 'bg-emerald-500' : pct >= 50 ? 'bg-amber-500' : 'bg-red-500';
    return (
        <div>
            <div className="mb-1.5 flex items-center justify-between text-xs">
                <span className="text-slate-500">{label}</span>
                <span className="font-semibold tabular-nums text-slate-900 dark:text-white">{pct}%</span>
            </div>
            <div className="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10" role="progressbar" aria-valuenow={pct} aria-valuemin={0} aria-valuemax={100}>
                <div className={cn('h-full rounded-full', tone)} style={{ width: `${Math.min(100, pct)}%` }} />
            </div>
        </div>
    );
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <div className="border-t border-slate-100 pt-4 dark:border-white/[0.06]">
            <p className="mb-3 text-xs font-medium uppercase tracking-wide text-slate-500">{title}</p>
            {children}
        </div>
    );
}

function ChildCard({ child }: { child: Child }) {
    return (
        <Panel>
            <div className="space-y-4">
                <div className="flex items-center gap-3">
                    <PersonAvatar name={child.full_name} src={child.photo_url} className="size-12" />
                    <div className="min-w-0 flex-1">
                        <p className="truncate font-semibold text-slate-900 dark:text-white">{child.full_name}</p>
                        <p className="truncate text-xs text-slate-500">{[child.class, child.section].filter(Boolean).join(' · ')} · {child.admission_no}</p>
                    </div>
                </div>

                <Section title="Attendance this month">
                    <AttendanceBar pct={child.attendance.percentage} label={`${child.attendance.present} of ${child.attendance.total} days present`} />
                    {child.attendance.absent > 0 && <p className="mt-2 text-xs text-red-700 dark:text-red-400">{child.attendance.absent} absent</p>}
                </Section>

                <Section title="Fees">
                    <div className="flex items-end justify-between">
                        <div>
                            <p className="text-xs text-slate-500">Paid</p>
                            <p className="text-lg font-semibold tabular-nums text-slate-900 dark:text-white">{naira(child.fees.total_paid)}</p>
                        </div>
                        <div className="text-right">
                            <p className="text-xs text-slate-500">Balance</p>
                            {child.fees.balance > 0
                                ? <p className="text-lg font-semibold tabular-nums text-red-700 dark:text-red-400">{naira(child.fees.balance)}</p>
                                : <Pill tone="good">Cleared</Pill>}
                        </div>
                    </div>
                    {child.fees.recent.length > 0 && (
                        <ul className="mt-3 space-y-2">
                            {child.fees.recent.map((f, i) => (
                                <li key={i} className="flex items-center justify-between text-sm">
                                    <span className="text-slate-600 dark:text-slate-300">{f.month}</span>
                                    <Pill tone={f.status === 'paid' ? 'good' : 'warn'}>{f.status}</Pill>
                                </li>
                            ))}
                        </ul>
                    )}
                </Section>

                {child.marks.length > 0 && (
                    <Section title="Recent results">
                        <ul className="space-y-2.5">
                            {child.marks.slice(0, 4).map((m, i) => (
                                <li key={i} className="flex items-center justify-between gap-3 text-sm">
                                    <div className="min-w-0">
                                        <p className="truncate font-medium text-slate-800 dark:text-slate-200">{m.subject}</p>
                                        <p className="truncate text-xs text-slate-500">{m.exam}</p>
                                    </div>
                                    {m.absent ? (
                                        <Pill tone="bad">Absent</Pill>
                                    ) : (
                                        <div className="flex shrink-0 items-center gap-2">
                                            <span className="tabular-nums text-slate-600 dark:text-slate-300">{m.marks ?? '—'}</span>
                                            {m.grade && <span className="flex size-7 items-center justify-center rounded-md bg-indigo-50 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">{m.grade}</span>}
                                        </div>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </Section>
                )}
            </div>
        </Panel>
    );
}

export default function ParentDashboard({ linked, guardian, children, announcements }: Props) {
    if (!linked || !guardian) {
        return (
            <AppLayout title="Parent Dashboard">
                <Panel>
                    <EmptyState icon={Users} title="Account not linked" text="Your account hasn't been linked to a guardian record yet. Please contact the school office." />
                </Panel>
            </AppLayout>
        );
    }

    const totalDue = children.reduce((sum, c) => sum + c.fees.balance, 0);

    return (
        <AppLayout breadcrumbs={[{ label: 'Home' }]}>
            <div className="space-y-6">
                <PageHeader
                    title={`Welcome, ${guardian.name.split(' ')[0]}`}
                    description={`${children.length} ${children.length === 1 ? 'child' : 'children'} enrolled${guardian.phone ? ` · ${guardian.phone}` : ''}`}
                />

                <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4 shadow-xs sm:p-5 dark:border-white/[0.08] dark:bg-slate-900">
                    <div>
                        <p className="text-[13px] text-slate-500">{totalDue > 0 ? 'Total fees due' : 'Fees'}</p>
                        <p className={cn('mt-1 text-2xl font-semibold tabular-nums tracking-[-0.03em]', totalDue > 0 ? 'text-red-700 dark:text-red-400' : 'text-emerald-700 dark:text-emerald-400')}>
                            {totalDue > 0 ? naira(totalDue) : 'All clear'}
                        </p>
                    </div>
                    {totalDue > 0 && <Pill tone="warn">Payment needed</Pill>}
                </div>

                <div className={cn('grid gap-6', children.length === 1 ? 'max-w-lg grid-cols-1' : 'grid-cols-1 md:grid-cols-2')}>
                    {children.map(child => <ChildCard key={child.id} child={child} />)}
                </div>

                {announcements.length > 0 && (
                    <Panel title="School announcements" action={<Bell className="size-4 text-slate-400" />} flush>
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {announcements.map(a => (
                                <li key={a.id} className="px-5 py-3.5">
                                    <div className="flex items-center gap-2">
                                        <p className="text-sm font-medium text-slate-900 dark:text-white">{a.title}</p>
                                        {a.pinned && <Pill tone="info">Pinned</Pill>}
                                    </div>
                                    <p className="mt-0.5 line-clamp-2 text-sm text-slate-500">{a.body}</p>
                                    {a.date && <p className="mt-1 text-xs text-slate-400">{a.date}</p>}
                                </li>
                            ))}
                        </ul>
                    </Panel>
                )}
            </div>
        </AppLayout>
    );
}
