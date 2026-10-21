import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    ArrowLeft, Pencil, Ban, CheckCircle, Globe, Phone, Mail, MapPin, Users, Calendar, LogIn,
    GraduationCap, UserCog, Banknote, Clock, CreditCard, Layers, KeyRound, Plus, Sparkles, History, Trash2,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { useCan } from '@/lib/can';
import SchoolOverviewPanels, { type Overview } from '@/components/school360/Overview';
import type { PageProps, School, AcademicYear } from '@/Types';

interface Admin { id: number; name: string; email: string; phone: string | null; status: string; last_login_at: string | null }
interface ShowPageProps extends PageProps {
    school: School & { academic_years: AcademicYear[]; delete_scheduled_at: string | null; delete_reason: string | null };
    grace_days: number;
    export_enabled: boolean;
    usage: { students: number; staff: number; users: number; outstanding: number; last_login: string | null };
    subscription: { id: number; package: string | null; status: string; start: string | null; end: string | null; is_trial: boolean; max_students: number | null; max_staff: number | null } | null;
    packages: { id: number; name: string; price_monthly: string; price_yearly: string }[];
    modules: { slug: string; label: string; is_enabled: boolean }[];
    admins: Admin[];
    ai: { blocked: boolean; limit: string | null; spent: number };
    activity: { id: number; text: string; by: string | null; at: string }[];
    overview: Overview;
    managers: { id: number; name: string }[];
}

const statusMap: Record<string, string> = {
    active:    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400',
    inactive:  'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
    suspended: 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-400',
};

const naira = (n: number) => '₦' + n.toLocaleString('en-NG', { maximumFractionDigits: 0 });
const when = (s: string | null) => (s ? new Date(s).toLocaleString() : 'Never');
const field = 'w-full h-9 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 text-sm';

export default function ShowSchool() {
    const { grace_days, export_enabled, school, usage, subscription, packages, modules, admins, ai, activity } = usePage<ShowPageProps>().props;
    const can = useCan();
    const statusClass = statusMap[school.status] ?? statusMap.inactive;
    const base = `/super-admin/schools/${school.id}`;

    // Features (module switches)
    const [mods, setMods] = useState<Record<string, boolean>>(Object.fromEntries(modules.map((m) => [m.slug, m.is_enabled])));
    const modsChanged = modules.some((m) => mods[m.slug] !== m.is_enabled);

    // Plan
    const today = new Date().toISOString().slice(0, 10);
    const nextYear = new Date(Date.now() + 365 * 86400000).toISOString().slice(0, 10);
    const [plan, setPlan] = useState({ package_id: '', status: 'active', start_date: today, end_date: nextYear, amount_paid: '0' });
    const [planOpen, setPlanOpen] = useState(false);

    // Admins
    const [adminOpen, setAdminOpen] = useState(false);
    const [admin, setAdmin] = useState({ name: '', email: '', phone: '', password: '' });
    const [resetFor, setResetFor] = useState<Admin | null>(null);
    const [newPassword, setNewPassword] = useState('');

    // AI
    const [aiBlocked, setAiBlocked] = useState(ai.blocked);
    const [aiLimit, setAiLimit] = useState(ai.limit ?? '');

    const savePlan = () => {
        router.post('/super-admin/subscriptions', {
            school_id: school.id, ...plan, package_id: Number(plan.package_id), is_trial: plan.status === 'trial',
        }, { onSuccess: () => setPlanOpen(false) });
    };

    // Deleting a school: a reason and the school's name, then a waiting period
    const scheduled = school.delete_scheduled_at;
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [exportOpen, setExportOpen] = useState(false);
    const [exportReason, setExportReason] = useState('');
    const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    const delForm = useForm({ reason: '', confirm_name: '' });
    const day = (d: string) => new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });

    return (
        <AppLayout breadcrumbs={[{ label: 'Schools', href: '/super-admin/schools' }, { label: school.name }]}>
            <Head title={school.name} />

            {/* Header */}
            <div className="flex flex-wrap items-start justify-between gap-3 mb-6">
                <div className="flex items-center gap-3 min-w-0">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/super-admin/schools"><ArrowLeft className="w-4 h-4" /></Link>
                    </Button>
                    <div className="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 flex items-center justify-center shrink-0">
                        {school.logo_url
                            ? <img src={school.logo_url} alt="" className="w-10 h-10 rounded-lg object-cover" />
                            : <span className="text-xl font-bold text-indigo-600">{school.name[0]}</span>}
                    </div>
                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-xl font-bold text-slate-900 dark:text-white">{school.name}</h1>
                            <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${statusClass}`}>{school.status}</span>
                        </div>
                        <p className="text-sm text-slate-400 font-mono truncate">{school.slug}</p>
                    </div>
                </div>
                <div className="flex flex-wrap gap-2">
                    {can('platform.schools.enter') && (
                        <Button size="sm" className="bg-amber-500 text-amber-950 hover:bg-amber-600" onClick={() => router.post(`${base}/enter`)}>
                            <LogIn className="w-4 h-4 mr-1.5" /> Open this school
                        </Button>
                    )}
                    {scheduled || !can('platform.schools.manage') ? null : school.status === 'suspended' ? (
                        <Button size="sm" variant="outline" className="text-emerald-600" onClick={() => router.patch(`${base}/activate`)}>
                            <CheckCircle className="w-4 h-4 mr-1.5" /> Activate
                        </Button>
                    ) : (
                        <Button size="sm" variant="outline" className="text-amber-600" onClick={() => confirm(`Suspend ${school.name}? Everyone there is signed out and cannot sign in until you activate it again.`) && router.patch(`${base}/suspend`)}>
                            <Ban className="w-4 h-4 mr-1.5" /> Suspend
                        </Button>
                    )}
                    {can('platform.schools.manage') && (
                        <Button size="sm" asChild className="bg-indigo-600 hover:bg-indigo-700 text-white">
                            <Link href={`${base}/edit`}><Pencil className="w-4 h-4 mr-1.5" /> Edit</Link>
                        </Button>
                    )}
                </div>
            </div>

            {scheduled && (
                <div role="alert" className="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/30 dark:text-red-100">
                    <p className="min-w-0 break-words"><b className="font-semibold">Scheduled for deletion on {day(scheduled)}.</b> The school is paused now and nothing is removed until then. Reason: {school.delete_reason}</p>
                    <Button size="sm" variant="outline" onClick={() => router.post(`${base}/cancel-delete`, {}, { preserveScroll: true })}>Cancel deletion</Button>
                </div>
            )}

            {!scheduled && school.status === 'suspended' && (
                <div className="mb-4 rounded-lg border border-red-200 bg-red-50 dark:bg-red-950/30 dark:border-red-900 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                    This school is suspended. Nobody there can sign in until you activate it.
                </div>
            )}

            {/* Numbers */}
            <div className="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
                {[
                    { icon: GraduationCap, label: 'Students', value: usage.students.toLocaleString() },
                    { icon: UserCog, label: 'Staff', value: usage.staff.toLocaleString() },
                    { icon: Users, label: 'Sign-in accounts', value: usage.users.toLocaleString() },
                    { icon: Banknote, label: 'Fees still owed', value: naira(usage.outstanding) },
                    { icon: Clock, label: 'Last sign-in', value: when(usage.last_login) },
                ].map((s) => (
                    <Card key={s.label} className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 text-xs text-slate-500"><s.icon className="w-3.5 h-3.5" /> {s.label}</div>
                            <p className="text-sm font-semibold text-slate-900 dark:text-white mt-1 break-words">{s.value}</p>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                {/* Plan */}
                <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
                        <CardTitle className="text-sm flex items-center gap-2"><CreditCard className="w-4 h-4" /> Plan</CardTitle>
                        {can('platform.billing.manage') && <Button size="sm" variant="outline" onClick={() => setPlanOpen(true)}>{subscription ? 'Change plan' : 'Give a plan'}</Button>}
                    </CardHeader>
                    <CardContent className="text-sm space-y-1.5">
                        {subscription ? (
                            <>
                                <p className="font-semibold text-slate-900 dark:text-white">{subscription.package ?? 'Plan'} <span className="ml-1 text-xs font-medium text-slate-500 capitalize">({subscription.status}{subscription.is_trial ? ', trial' : ''})</span></p>
                                <p className="text-slate-500">From {subscription.start} to {subscription.end}</p>
                                <p className="text-slate-500">
                                    Students: {usage.students.toLocaleString()}{subscription.max_students ? ` of ${subscription.max_students.toLocaleString()}` : ' (no limit set)'}
                                    {subscription.max_students && usage.students > subscription.max_students && <span className="text-red-600 font-medium"> — over the limit</span>}
                                </p>
                                <p className="text-slate-500">Staff: {usage.staff.toLocaleString()}{subscription.max_staff ? ` of ${subscription.max_staff.toLocaleString()}` : ' (no limit set)'}</p>
                            </>
                        ) : <p className="text-slate-400">This school has no plan yet.</p>}
                    </CardContent>
                </Card>

                {/* Features */}
                <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
                        <CardTitle className="text-sm flex items-center gap-2"><Layers className="w-4 h-4" /> Features this school has</CardTitle>
                        {can('platform.modules.manage') && <Button size="sm" disabled={!modsChanged} onClick={() => router.post('/super-admin/module-manager/bulk', { school_id: school.id, modules: mods })}>Save</Button>}
                    </CardHeader>
                    <CardContent className="grid grid-cols-2 gap-2">
                        {modules.map((m) => (
                            <label key={m.slug} className="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" className="h-4 w-4 accent-indigo-600" checked={mods[m.slug]} onChange={(e) => setMods({ ...mods, [m.slug]: e.target.checked })} />
                                {m.label}
                            </label>
                        ))}
                    </CardContent>
                </Card>

                {/* Administrators */}
                <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
                        <CardTitle className="text-sm flex items-center gap-2"><UserCog className="w-4 h-4" /> School administrators</CardTitle>
                        {can('platform.schools.manage') && <Button size="sm" variant="outline" onClick={() => setAdminOpen(true)}><Plus className="w-3.5 h-3.5 mr-1" /> Add</Button>}
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {admins.length === 0 && <p className="text-sm text-slate-400">This school has no administrator. Add one so someone there can sign in.</p>}
                        {admins.map((a) => (
                            <div key={a.id} className="flex flex-wrap items-center justify-between gap-2">
                                <div className="min-w-0">
                                    <p className="text-sm font-medium text-slate-900 dark:text-white">{a.name} {a.status !== 'active' && <span className="text-xs text-red-600 capitalize">({a.status})</span>}</p>
                                    <p className="text-xs text-slate-500 truncate">{a.email} · last in: {when(a.last_login_at)}</p>
                                </div>
                                {can('platform.users.manage') && <div className="flex flex-wrap gap-1.5">
                                    <Button size="sm" variant="outline" onClick={() => { setResetFor(a); setNewPassword(''); }}><KeyRound className="w-3.5 h-3.5 mr-1" /> New password</Button>
                                    {a.status === 'active'
                                        ? <Button size="sm" variant="outline" className="text-amber-600" onClick={() => router.patch(`/super-admin/users/${a.id}/suspend`)}>Suspend</Button>
                                        : <Button size="sm" variant="outline" className="text-emerald-600" onClick={() => router.patch(`/super-admin/users/${a.id}/activate`)}>Activate</Button>}
                                </div>}
                            </div>
                        ))}
                        <Link href={`/super-admin/users?school_id=${school.id}`} className="text-xs text-indigo-600 hover:underline inline-block">See every account in this school</Link>
                    </CardContent>
                </Card>

                {/* AI */}
                <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <CardHeader className="pb-3"><CardTitle className="text-sm flex items-center gap-2"><Sparkles className="w-4 h-4" /> AI helper</CardTitle></CardHeader>
                    <CardContent className="space-y-3 text-sm">
                        <p className="text-slate-500">Spent this month: <span className="font-medium text-slate-900 dark:text-white">{naira(ai.spent)}</span></p>
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" className="h-4 w-4 accent-indigo-600" checked={aiBlocked} onChange={(e) => setAiBlocked(e.target.checked)} />
                            Switch AI off for this school
                        </label>
                        <div className="flex flex-wrap items-end gap-2">
                            <div>
                                <Label className="text-xs">Monthly allowance (₦) — empty uses the platform default</Label>
                                <Input type="number" min="0" className="h-9 w-48" value={aiLimit} onChange={(e) => setAiLimit(e.target.value)} />
                            </div>
                            {can('platform.settings.manage') && <Button size="sm" onClick={() => router.post(`/super-admin/ai/schools/${school.id}`, { blocked: aiBlocked, limit: aiLimit === '' ? null : aiLimit })}>Save</Button>}
                        </div>
                    </CardContent>
                </Card>

                {/* Activity */}
                <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <CardHeader className="pb-3"><CardTitle className="text-sm flex items-center gap-2"><History className="w-4 h-4" /> Latest activity</CardTitle></CardHeader>
                    <CardContent className="space-y-2">
                        {activity.length === 0 && <p className="text-sm text-slate-400">Nothing recorded yet.</p>}
                        {activity.map((a) => (
                            <div key={a.id} className="text-sm">
                                <p className="text-slate-700 dark:text-slate-300">{a.text}</p>
                                <p className="text-xs text-slate-400">{a.by ?? 'System'} · {when(a.at)}</p>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                {/* Details */}
                <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <CardHeader className="pb-3"><CardTitle className="text-sm">Contact, location and years</CardTitle></CardHeader>
                    <CardContent className="grid grid-cols-2 gap-3 text-sm">
                        {[
                            { icon: Mail, label: 'Email', value: school.email },
                            { icon: Phone, label: 'Phone', value: school.phone },
                            { icon: MapPin, label: 'City', value: school.city },
                            { icon: Globe, label: 'Country', value: school.country },
                        ].map(({ icon: Icon, label, value }) => (
                            <div key={label} className="flex items-start gap-2 min-w-0">
                                <Icon className="w-4 h-4 text-slate-400 mt-0.5 shrink-0" />
                                <div className="min-w-0">
                                    <p className="text-xs text-slate-400">{label}</p>
                                    <p className="text-slate-700 dark:text-slate-300 break-words">{value ?? '—'}</p>
                                </div>
                            </div>
                        ))}
                        <div className="col-span-2 flex items-start gap-2">
                            <Calendar className="w-4 h-4 text-slate-400 mt-0.5 shrink-0" />
                            <div>
                                <p className="text-xs text-slate-400">School years</p>
                                {school.academic_years?.length === 0 ? <p className="text-slate-400">None yet</p> : school.academic_years?.map((y) => (
                                    <p key={y.id} className="text-slate-700 dark:text-slate-300">{y.name}{y.is_current && <span className="ml-2 text-[10px] px-1.5 py-0.5 rounded-full bg-indigo-100 text-indigo-600">Current</span>}</p>
                                ))}
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <SchoolOverviewPanels />

            {export_enabled && can('platform.schools.delete') && (
                <div className="mt-6 rounded-lg border border-slate-200 dark:border-slate-800 p-4 flex flex-wrap items-center justify-between gap-3">
                    <div className="min-w-0">
                        <p className="text-sm font-medium text-slate-800 dark:text-slate-100">Download this school's data</p>
                        <p className="text-xs text-slate-500">A ZIP with one spreadsheet file per table. Passwords and keys are left out, and so are uploaded files. It needs a reason and is logged. Nothing changes in the school.</p>
                    </div>
                    <Button size="sm" variant="outline" onClick={() => { setExportReason(''); setExportOpen(true); }}>Download data…</Button>
                </div>
            )}

            <Dialog open={exportOpen} onOpenChange={setExportOpen}>
                <DialogContent>
                    <DialogHeader><DialogTitle>Download data of {school.name}</DialogTitle></DialogHeader>
                    <form method="post" action={`${base}/export`} onSubmit={() => setTimeout(() => setExportOpen(false), 500)} className="space-y-3">
                        <input type="hidden" name="_token" value={csrf} />
                        <p className="text-sm text-slate-500">The file can hold names, phone numbers and results of children, so keep it safe and delete it when you are done.</p>
                        <div>
                            <label htmlFor="exp-why" className="text-sm font-medium">Reason (required, at least 10 letters)</label>
                            <Textarea id="exp-why" name="reason" rows={3} maxLength={500} value={exportReason} onChange={(e) => setExportReason(e.target.value)} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setExportOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={exportReason.trim().length < 10} className="bg-indigo-600 text-white hover:bg-indigo-700">Download</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {!scheduled && can('platform.schools.delete') && (
                <div className="mt-6 rounded-lg border border-red-200 dark:border-red-900 p-4 flex flex-wrap items-center justify-between gap-3">
                    <div className="min-w-0">
                        <p className="text-sm font-medium text-red-700 dark:text-red-400">Delete this school</p>
                        <p className="text-xs text-slate-500">Needs a reason. The school is paused now and deleted after {grace_days} days. Until then you can cancel, and afterwards you can bring it back.</p>
                    </div>
                    <Button size="sm" variant="outline" className="text-red-600" onClick={() => { delForm.reset(); delForm.clearErrors(); setDeleteOpen(true); }}><Trash2 className="w-4 h-4 mr-1.5" /> Delete…</Button>
                </div>
            )}

            <Dialog open={deleteOpen} onOpenChange={setDeleteOpen}>
                <DialogContent>
                    <DialogHeader><DialogTitle>Delete {school.name}</DialogTitle></DialogHeader>
                    <p className="text-sm text-slate-500">The school is paused right away, so nobody there can sign in. After {grace_days} days it is deleted (hidden, not wiped, and you can bring it back from the Schools page). Until then you can cancel at any time.</p>
                    <div>
                        <label htmlFor="del-why" className="text-sm font-medium">Reason (required, at least 10 letters)</label>
                        <Textarea id="del-why" rows={3} value={delForm.data.reason} maxLength={500} onChange={(e) => delForm.setData('reason', e.target.value)} />
                        {delForm.errors.reason && <p className="text-xs text-red-600 mt-1">{delForm.errors.reason}</p>}
                    </div>
                    <div>
                        <label htmlFor="del-name" className="text-sm font-medium">Type the school name to confirm</label>
                        <Input id="del-name" value={delForm.data.confirm_name} onChange={(e) => delForm.setData('confirm_name', e.target.value)} placeholder={school.name} className="mt-1 h-9" />
                        {delForm.errors.confirm_name && <p className="text-xs text-red-600 mt-1">{delForm.errors.confirm_name}</p>}
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setDeleteOpen(false)}>Cancel</Button>
                        <Button type="button" variant="destructive" disabled={delForm.processing || delForm.data.reason.trim().length < 10 || !delForm.data.confirm_name.trim()}
                            onClick={() => delForm.post(`${base}/schedule-delete`, { preserveScroll: true, onSuccess: () => setDeleteOpen(false) })}>Schedule deletion</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Plan dialog */}
            <Dialog open={planOpen} onOpenChange={setPlanOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader><DialogTitle>{subscription ? 'Change plan' : 'Give a plan'}</DialogTitle></DialogHeader>
                    <div className="space-y-3">
                        <div>
                            <Label>Plan</Label>
                            <select className={field} value={plan.package_id} onChange={(e) => setPlan({ ...plan, package_id: e.target.value })}>
                                <option value="">Choose a plan…</option>
                                {packages.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                            </select>
                        </div>
                        <div>
                            <Label>State</Label>
                            <select className={field} value={plan.status} onChange={(e) => setPlan({ ...plan, status: e.target.value })}>
                                <option value="active">Active</option>
                                <option value="trial">Free trial</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div><Label>Starts</Label><Input type="date" value={plan.start_date} onChange={(e) => setPlan({ ...plan, start_date: e.target.value })} /></div>
                            <div><Label>Ends</Label><Input type="date" value={plan.end_date} onChange={(e) => setPlan({ ...plan, end_date: e.target.value })} /></div>
                        </div>
                        <div><Label>Amount paid (₦)</Label><Input type="number" min="0" value={plan.amount_paid} onChange={(e) => setPlan({ ...plan, amount_paid: e.target.value })} /></div>
                        <p className="text-xs text-slate-500">This adds a new plan record. The newest one is the school's current plan.</p>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setPlanOpen(false)}>Cancel</Button>
                        <Button disabled={!plan.package_id} onClick={savePlan}>Save plan</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Add administrator dialog */}
            <Dialog open={adminOpen} onOpenChange={setAdminOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader><DialogTitle>Add a school administrator</DialogTitle></DialogHeader>
                    <div className="space-y-3">
                        <div><Label>Full name</Label><Input value={admin.name} onChange={(e) => setAdmin({ ...admin, name: e.target.value })} /></div>
                        <div><Label>Email (they sign in with this)</Label><Input type="email" value={admin.email} onChange={(e) => setAdmin({ ...admin, email: e.target.value })} /></div>
                        <div><Label>Phone (optional)</Label><Input value={admin.phone} onChange={(e) => setAdmin({ ...admin, phone: e.target.value })} /></div>
                        <div><Label>Starting password (at least 8 characters)</Label><Input value={admin.password} onChange={(e) => setAdmin({ ...admin, password: e.target.value })} /></div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setAdminOpen(false)}>Cancel</Button>
                        <Button disabled={!admin.name || !admin.email || admin.password.length < 8} onClick={() => router.post(`${base}/admins`, admin, { onSuccess: () => { setAdminOpen(false); setAdmin({ name: '', email: '', phone: '', password: '' }); } })}>Add administrator</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Reset password dialog */}
            <Dialog open={!!resetFor} onOpenChange={(o) => !o && setResetFor(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader><DialogTitle>New password for {resetFor?.name}</DialogTitle></DialogHeader>
                    <div><Label>New password (at least 8 characters)</Label><Input value={newPassword} onChange={(e) => setNewPassword(e.target.value)} /></div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setResetFor(null)}>Cancel</Button>
                        <Button disabled={newPassword.length < 8} onClick={() => resetFor && router.patch(`/super-admin/users/${resetFor.id}/reset-password`, { password: newPassword }, { onSuccess: () => setResetFor(null) })}>Save password</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
