import { Head, router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Ban, CheckCircle, KeyRound, Pencil, Plus, ShieldCheck, Trash2, UserMinus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import RiskyConfirm from '@/components/platform/RiskyConfirm';

interface RoleInfo { key: string; label: string; about: string; permissions: string[] }
interface PermissionInfo { key: string; group: string; label: string }
interface SchoolInfo { id: number; name: string }
interface StaffRow {
    id: number; name: string; email: string; phone: string | null; status: string;
    last_login_at: string | null; role: string | null; role_label: string;
    extras: string[]; scope: 'all' | 'assigned'; schools: SchoolInfo[]; note: string | null;
}
interface Props { staff: StaffRow[]; roles: RoleInfo[]; permissions: PermissionInfo[]; schools: SchoolInfo[] }

const blank = { name: '', email: '', phone: '', role: 'platform-support', extras: [] as string[], school_scope: 'all' as 'all' | 'assigned', school_ids: [] as number[], note: '', password: '' };

const when = (iso: string | null) => (iso ? new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' }) : 'Never signed in');

const statusPill = (status: string) => {
    const tone = status === 'active'
        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400'
        : status === 'suspended' ? 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
    return <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${tone}`}>{status === 'suspended' ? 'Switched off' : status.charAt(0).toUpperCase() + status.slice(1)}</span>;
};

export default function StaffIndex({ staff, roles, permissions, schools }: Props) {
    const [editing, setEditing] = useState<StaffRow | 'new' | null>(null);
    const [removing, setRemoving] = useState<StaffRow | null>(null);
    const [resetting, setResetting] = useState<StaffRow | null>(null);
    const [schoolFilter, setSchoolFilter] = useState('');
    const [newPassword, setNewPassword] = useState('');

    const form = useForm(blank);
    const role = roles.find((r) => r.key === form.data.role);
    const groups = useMemo(() => {
        const out: Record<string, PermissionInfo[]> = {};
        permissions.forEach((p) => { (out[p.group] ??= []).push(p); });
        return out;
    }, [permissions]);
    const visibleSchools = schools.filter((s) => s.name.toLowerCase().includes(schoolFilter.toLowerCase()));

    const open = (row: StaffRow | 'new') => {
        form.clearErrors();
        if (row === 'new') form.setData({ ...blank });
        else form.setData({
            name: row.name, email: row.email, phone: row.phone ?? '', role: row.role ?? 'platform-support', extras: row.extras,
            school_scope: row.scope, school_ids: row.schools.map((s) => s.id), note: row.note ?? '', password: '',
        });
        setSchoolFilter('');
        setEditing(row);
    };

    const save = (e: React.FormEvent) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setEditing(null) };
        if (editing === 'new') form.post('/super-admin/staff', options);
        else if (editing) form.put(`/super-admin/staff/${editing.id}`, options);
    };

    const toggle = <T,>(list: T[], item: T) => (list.includes(item) ? list.filter((x) => x !== item) : [...list, item]);

    return (
        <AppLayout breadcrumbs={[{ label: 'Super Admin' }, { label: 'Platform Staff' }]}>
            <Head title="Platform Staff" />

            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="text-xl font-bold text-slate-900 dark:text-white">Platform staff</h1>
                    <p className="mt-0.5 max-w-2xl text-sm text-slate-500 dark:text-slate-400">
                        The people who work for Adino on the platform. Each one gets a role that decides what they can open, and can be limited to chosen schools.
                    </p>
                </div>
                <Button onClick={() => open('new')} className="gap-2 bg-indigo-600 text-white hover:bg-indigo-700">
                    <Plus className="size-4" /> Add staff member
                </Button>
            </div>

            {/* What each role is for */}
            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
                {roles.map((r) => (
                    <div key={r.key} className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                        <p className="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-white"><ShieldCheck className="size-4 text-indigo-500" /> {r.label}</p>
                        <p className="mt-1.5 hidden text-xs leading-relaxed text-slate-500 sm:block dark:text-slate-400">{r.about}</p>
                        <p className="mt-2 text-[11px] text-slate-400">{staff.filter((s) => s.role === r.key).length} on the team</p>
                    </div>
                ))}
            </div>

            {/* The team */}
            {staff.length === 0 ? (
                <div className="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900">
                    <ShieldCheck className="mx-auto size-8 text-slate-300" />
                    <p className="mt-3 text-sm font-medium text-slate-900 dark:text-white">No staff yet</p>
                    <p className="mx-auto mt-1 max-w-sm text-sm text-slate-500">Right now only you can use the platform. Add a support, finance, sales, developer or content person and choose what they can do.</p>
                    <Button onClick={() => open('new')} className="mt-4 gap-2"><Plus className="size-4" /> Add the first staff member</Button>
                </div>
            ) : (
                <div className="space-y-3">
                    {staff.map((s) => (
                        <div key={s.id} className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="truncate text-sm font-semibold text-slate-900 dark:text-white">{s.name}</p>
                                        <span className="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">{s.role_label}</span>
                                        {statusPill(s.status)}
                                    </div>
                                    <p className="mt-0.5 truncate text-xs text-slate-500">{s.email}{s.phone ? ` · ${s.phone}` : ''}</p>
                                    <p className="mt-1.5 text-xs text-slate-500">
                                        {s.scope === 'all'
                                            ? 'Can reach every school'
                                            : <>Only these schools: <span className="text-slate-700 dark:text-slate-300">{s.schools.map((x) => x.name).join(', ') || '—'}</span></>}
                                    </p>
                                    {s.extras.length > 0 && (
                                        <p className="mt-1 text-xs text-slate-500">Extra: {s.extras.map((k) => permissions.find((p) => p.key === k)?.label ?? k).join('; ')}</p>
                                    )}
                                    {s.note && <p className="mt-1 text-xs italic text-slate-400">{s.note}</p>}
                                    <p className="mt-1.5 text-[11px] text-slate-400">Last signed in: {when(s.last_login_at)}</p>
                                </div>
                                <div className="flex flex-wrap gap-1.5">
                                    <Button size="sm" variant="outline" onClick={() => open(s)}><Pencil className="mr-1 size-3.5" /> Edit</Button>
                                    <Button size="sm" variant="outline" onClick={() => { setResetting(s); setNewPassword(''); }}><KeyRound className="mr-1 size-3.5" /> New password</Button>
                                    {s.status === 'suspended' ? (
                                        <Button size="sm" variant="outline" className="text-emerald-600" onClick={() => router.patch(`/super-admin/staff/${s.id}/activate`, {}, { preserveScroll: true })}><CheckCircle className="mr-1 size-3.5" /> Switch on</Button>
                                    ) : (
                                        <Button size="sm" variant="outline" className="text-amber-600" onClick={() => router.patch(`/super-admin/staff/${s.id}/suspend`, {}, { preserveScroll: true })}><Ban className="mr-1 size-3.5" /> Switch off</Button>
                                    )}
                                    <Button size="sm" variant="outline" className="text-red-600" onClick={() => setRemoving(s)}><UserMinus className="mr-1 size-3.5" /> Remove</Button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Add or edit */}
            <Dialog open={editing !== null} onOpenChange={(o) => !o && setEditing(null)}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{editing === 'new' ? 'Add a staff member' : 'Change staff member'}</DialogTitle>
                        <DialogDescription>{editing === 'new' ? 'We make a sign-in for them and show you the password once.' : 'Changes apply the next time they open a page.'}</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={save} className="space-y-5">
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div><Label>Full name</Label><Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />{form.errors.name && <p className="mt-1 text-xs text-red-600">{form.errors.name}</p>}</div>
                            <div><Label>Email (used to sign in)</Label><Input type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} required />{form.errors.email && <p className="mt-1 text-xs text-red-600">{form.errors.email}</p>}</div>
                            <div><Label>Phone (optional)</Label><Input value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} />{form.errors.phone && <p className="mt-1 text-xs text-red-600">{form.errors.phone}</p>}</div>
                            {editing === 'new' && (
                                <div><Label>Password (optional)</Label><Input type="text" autoComplete="off" placeholder="Empty = we make one" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} />{form.errors.password && <p className="mt-1 text-xs text-red-600">{form.errors.password}</p>}</div>
                            )}
                        </div>

                        <div>
                            <Label>Role</Label>
                            <Select value={form.data.role} onValueChange={(v) => form.setData('role', v as string)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>{roles.map((r) => <SelectItem key={r.key} value={r.key}>{r.label}</SelectItem>)}</SelectContent>
                            </Select>
                            {role && <p className="mt-1.5 text-xs text-slate-500">{role.about}</p>}
                        </div>

                        <div>
                            <Label>Which schools can they reach?</Label>
                            <div className="mt-1.5 grid gap-2 sm:grid-cols-2">
                                {([['all', 'Every school'], ['assigned', 'Only the schools I choose']] as const).map(([v, label]) => (
                                    <label key={v} className={`flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm ${form.data.school_scope === v ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/30' : 'border-slate-200 dark:border-slate-700'}`}>
                                        <input type="radio" name="scope" className="accent-indigo-600" checked={form.data.school_scope === v} onChange={() => form.setData('school_scope', v)} /> {label}
                                    </label>
                                ))}
                            </div>
                            {form.data.school_scope === 'assigned' && (
                                <div className="mt-2 rounded-lg border border-slate-200 p-2 dark:border-slate-700">
                                    <p className="px-1 pb-1.5 text-xs text-slate-500">People limited to some schools cannot open platform-wide pages such as the dashboard, packages or settings.</p>
                                    <Input placeholder="Find a school…" value={schoolFilter} onChange={(e) => setSchoolFilter(e.target.value)} className="mb-2 h-9" />
                                    <div className="max-h-40 space-y-0.5 overflow-y-auto">
                                        {visibleSchools.map((s) => (
                                            <label key={s.id} className="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                                                <Checkbox checked={form.data.school_ids.includes(s.id)} onCheckedChange={() => form.setData('school_ids', toggle(form.data.school_ids, s.id))} /> {s.name}
                                            </label>
                                        ))}
                                        {visibleSchools.length === 0 && <p className="px-2 py-2 text-xs text-slate-400">No school matches.</p>}
                                    </div>
                                    <p className="px-1 pt-1.5 text-xs text-slate-500">{form.data.school_ids.length} chosen</p>
                                </div>
                            )}
                            {form.errors.school_ids && <p className="mt-1 text-xs text-red-600">{form.errors.school_ids}</p>}
                        </div>

                        <div>
                            <Label>Extra things for this person</Label>
                            <p className="mb-2 text-xs text-slate-500">Ticked and greyed items already come with the role. Tick others to give just this person more.</p>
                            <div className="space-y-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                                {Object.entries(groups).map(([group, items]) => (
                                    <div key={group}>
                                        <p className="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">{group}</p>
                                        <div className="space-y-1">
                                            {items.map((p) => {
                                                const fromRole = role?.permissions.includes(p.key) ?? false;
                                                return (
                                                    <label key={p.key} className={`flex items-start gap-2 text-sm ${fromRole ? 'opacity-60' : 'cursor-pointer'}`}>
                                                        <Checkbox className="mt-0.5" disabled={fromRole} checked={fromRole || form.data.extras.includes(p.key)} onCheckedChange={() => form.setData('extras', toggle(form.data.extras, p.key))} />
                                                        <span>{p.label}</span>
                                                    </label>
                                                );
                                            })}
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <p className="mt-1.5 text-xs text-slate-500">Looking inside a school never lets them change anything unless you also tick “Make changes while inside a school”.</p>
                        </div>

                        <div><Label>Note (optional)</Label><Textarea rows={2} value={form.data.note} onChange={(e) => form.setData('note', e.target.value)} placeholder="For example: looks after the Lagos schools" /></div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setEditing(null)}>Cancel</Button>
                            <Button type="submit" disabled={form.processing}>{editing === 'new' ? 'Add staff member' : 'Save changes'}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* New password */}
            <Dialog open={resetting !== null} onOpenChange={(o) => !o && setResetting(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>New password for {resetting?.name}</DialogTitle>
                        <DialogDescription>Leave it empty and we make one for you. It is shown once.</DialogDescription>
                    </DialogHeader>
                    <Input type="text" autoComplete="off" placeholder="At least 8 characters" value={newPassword} onChange={(e) => setNewPassword(e.target.value)} />
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setResetting(null)}>Cancel</Button>
                        <Button disabled={newPassword.length > 0 && newPassword.length < 8} onClick={() => resetting && router.patch(`/super-admin/staff/${resetting.id}/reset-password`, { password: newPassword || null }, { preserveScroll: true, onSuccess: () => setResetting(null) })}>Set password</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Remove */}
            <RiskyConfirm open={removing !== null} onClose={() => setRemoving(null)} url={`/super-admin/staff/${removing?.id}`} title={`Remove ${removing?.name ?? 'this person'} from the team?`}
                description="They lose all platform access and can no longer sign in. Their past actions stay in the records. You can add them again later." typeName={removing?.name} actionLabel="Remove" canNeedApproval />
        </AppLayout>
    );
}
