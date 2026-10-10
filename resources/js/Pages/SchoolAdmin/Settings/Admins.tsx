import AppLayout from '@/Layouts/AppLayout';
import { useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog';
import { UserCog, Plus, Search, Edit, Trash2, Ban, CheckCircle, ChevronLeft, ChevronRight } from 'lucide-react';

interface Role { name: string; }
interface UserRow {
    id: number; name: string; email: string; phone: string | null;
    status: string; roles: Role[]; created_at: string; last_login_at: string | null;
}
interface Meta { total: number; per_page: number; current_page: number; last_page: number; }
interface Props {
    users: { data: UserRow[]; meta: Meta };
    roles: string[];
    filters: { search?: string; role?: string; status?: string };
}

const STATUSES = ['active', 'inactive', 'suspended'];

const statusBadge = (status: string) => {
    const map: Record<string, string> = {
        active:    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        inactive:  'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
        suspended: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${map[status] ?? ''}`}>{status}</span>;
};

const roleLabel = (name: string) => name.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

export default function Admins({ users, roles, filters }: Props) {
    const [search, setSearch]           = useState(filters.search ?? '');
    const [roleFilter, setRoleFilter]   = useState(filters.role ?? '');
    const [statusFilter, setStatusFilter] = useState(filters.status ?? '');

    const [showModal, setShowModal]   = useState(false);
    const [editUser, setEditUser]     = useState<UserRow | null>(null);
    const [deleteUser, setDeleteUser] = useState<UserRow | null>(null);

    const form = useForm({
        name: '', email: '', phone: '', password: '',
        role: '', status: 'active',
    });

    function applyFilters(overrides: Record<string, string> = {}) {
        router.get('/school/settings/admins', {
            search: search, role: roleFilter, status: statusFilter, ...overrides,
        }, { preserveState: true, replace: true });
    }

    function openCreate() {
        form.reset();
        form.clearErrors();
        setEditUser(null);
        setShowModal(true);
    }

    function openEdit(u: UserRow) {
        form.setData({
            name: u.name, email: u.email, phone: u.phone ?? '',
            password: '', role: u.roles[0]?.name ?? '', status: u.status,
        });
        form.clearErrors();
        setEditUser(u);
        setShowModal(true);
    }

    function submitForm(e: React.FormEvent) {
        e.preventDefault();
        if (editUser) {
            form.put(`/school/settings/admins/${editUser.id}`, { onSuccess: () => setShowModal(false) });
        } else {
            form.post('/school/settings/admins', { onSuccess: () => setShowModal(false) });
        }
    }

    function confirmDelete() {
        if (!deleteUser) return;
        router.delete(`/school/settings/admins/${deleteUser.id}`, { onSuccess: () => setDeleteUser(null) });
    }

    function toggleStatus(u: UserRow) {
        const action = u.status === 'suspended' ? 'activate' : 'suspend';
        router.patch(`/school/settings/admins/${u.id}/${action}`);
    }

    return (
        <AppLayout title="User Management">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">User Management</h1>
                        <p className="text-sm text-slate-500 mt-0.5">Manage staff accounts and roles within your school</p>
                    </div>
                    <Button onClick={openCreate} className="gap-2">
                        <Plus className="w-4 h-4" /> Add User
                    </Button>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="pt-4">
                        <div className="flex flex-wrap gap-3">
                            <div className="relative flex-1 min-w-[200px]">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                <Input className="pl-9" placeholder="Search name or email..."
                                    value={search} onChange={e => setSearch(e.target.value)}
                                    onKeyDown={e => e.key === 'Enter' && applyFilters({ search })}
                                />
                            </div>
                            <Select value={roleFilter || '_all'} onValueChange={v => { setRoleFilter(v === '_all' ? '' : v); applyFilters({ role: v === '_all' ? '' : v }); }}>
                                <SelectTrigger className="w-44"><SelectValue placeholder="All Roles" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="_all">All Roles</SelectItem>
                                    {roles.map(r => <SelectItem key={r} value={r}>{roleLabel(r)}</SelectItem>)}
                                </SelectContent>
                            </Select>
                            <Select value={statusFilter || '_all'} onValueChange={v => { setStatusFilter(v === '_all' ? '' : v); applyFilters({ status: v === '_all' ? '' : v }); }}>
                                <SelectTrigger className="w-36"><SelectValue placeholder="All Status" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="_all">All Status</SelectItem>
                                    {STATUSES.map(s => <SelectItem key={s} value={s} className="capitalize">{s}</SelectItem>)}
                                </SelectContent>
                            </Select>
                            <Button variant="outline" onClick={() => applyFilters({ search })}>Search</Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Table */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <UserCog className="w-4 h-4 text-indigo-500" />
                            School Users ({users.meta.total})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-200 dark:border-slate-700 text-slate-500 text-xs uppercase">
                                        <th className="text-left py-3 px-4 font-medium">Name</th>
                                        <th className="text-left py-3 px-4 font-medium">Email</th>
                                        <th className="text-left py-3 px-4 font-medium">Phone</th>
                                        <th className="text-left py-3 px-4 font-medium">Role</th>
                                        <th className="text-left py-3 px-4 font-medium">Status</th>
                                        <th className="text-left py-3 px-4 font-medium">Last Login</th>
                                        <th className="text-right py-3 px-4 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {users.data.length === 0 && (
                                        <tr>
                                            <td colSpan={7} className="text-center py-10 text-slate-400">No users found.</td>
                                        </tr>
                                    )}
                                    {users.data.map(u => (
                                        <tr key={u.id} className="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                            <td className="py-3 px-4 font-medium text-slate-900 dark:text-white">{u.name}</td>
                                            <td className="py-3 px-4 text-slate-600 dark:text-slate-400">{u.email}</td>
                                            <td className="py-3 px-4 text-slate-600 dark:text-slate-400">{u.phone ?? '—'}</td>
                                            <td className="py-3 px-4">
                                                {u.roles[0]
                                                    ? <Badge variant="outline" className="text-xs">{roleLabel(u.roles[0].name)}</Badge>
                                                    : <span className="text-slate-400">—</span>}
                                            </td>
                                            <td className="py-3 px-4">{statusBadge(u.status)}</td>
                                            <td className="py-3 px-4 text-slate-500 text-xs">
                                                {u.last_login_at ? new Date(u.last_login_at).toLocaleDateString() : '—'}
                                            </td>
                                            <td className="py-3 px-4">
                                                <div className="flex items-center justify-end gap-1">
                                                    <button onClick={() => toggleStatus(u)}
                                                        title={u.status === 'suspended' ? 'Activate' : 'Suspend'}
                                                        className={`p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 ${u.status === 'suspended' ? 'text-green-500' : 'text-amber-500'}`}>
                                                        {u.status === 'suspended' ? <CheckCircle className="w-3.5 h-3.5" /> : <Ban className="w-3.5 h-3.5" />}
                                                    </button>
                                                    <button onClick={() => openEdit(u)} title="Edit"
                                                        className="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-indigo-600">
                                                        <Edit className="w-3.5 h-3.5" />
                                                    </button>
                                                    <button onClick={() => setDeleteUser(u)} title="Delete"
                                                        className="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-red-600">
                                                        <Trash2 className="w-3.5 h-3.5" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {users.meta.last_page > 1 && (
                            <div className="flex items-center justify-between mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                                <p className="text-sm text-slate-500">
                                    Page {users.meta.current_page} of {users.meta.last_page} — {users.meta.total} users
                                </p>
                                <div className="flex gap-2">
                                    <Button variant="outline" size="sm" disabled={users.meta.current_page <= 1}
                                        onClick={() => applyFilters({ page: String(users.meta.current_page - 1) })}>
                                        <ChevronLeft className="w-4 h-4" />
                                    </Button>
                                    <Button variant="outline" size="sm" disabled={users.meta.current_page >= users.meta.last_page}
                                        onClick={() => applyFilters({ page: String(users.meta.current_page + 1) })}>
                                        <ChevronRight className="w-4 h-4" />
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Create / Edit Modal */}
            <Dialog open={showModal} onOpenChange={setShowModal}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>{editUser ? 'Edit User' : 'Add User'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitForm} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Name *</Label>
                                <Input value={form.data.name} onChange={e => form.setData('name', e.target.value)} />
                                {form.errors.name && <p className="text-xs text-red-500 mt-1">{form.errors.name}</p>}
                            </div>
                            <div>
                                <Label>Email *</Label>
                                <Input type="email" value={form.data.email} onChange={e => form.setData('email', e.target.value)} />
                                {form.errors.email && <p className="text-xs text-red-500 mt-1">{form.errors.email}</p>}
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Phone</Label>
                                <Input value={form.data.phone} onChange={e => form.setData('phone', e.target.value)} placeholder="+1234567890" />
                            </div>
                            <div>
                                <Label>{editUser ? 'New Password' : 'Password *'}</Label>
                                <Input type="password" value={form.data.password}
                                    onChange={e => form.setData('password', e.target.value)}
                                    placeholder={editUser ? 'Leave blank to keep' : 'Min 8 chars'} />
                                {form.errors.password && <p className="text-xs text-red-500 mt-1">{form.errors.password}</p>}
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Role *</Label>
                                <Select value={form.data.role} onValueChange={v => form.setData('role', v)}>
                                    <SelectTrigger><SelectValue placeholder="Select role" /></SelectTrigger>
                                    <SelectContent>
                                        {roles.map(r => <SelectItem key={r} value={r}>{roleLabel(r)}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                                {form.errors.role && <p className="text-xs text-red-500 mt-1">{form.errors.role}</p>}
                            </div>
                            <div>
                                <Label>Status *</Label>
                                <Select value={form.data.status} onValueChange={v => form.setData('status', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        {STATUSES.map(s => <SelectItem key={s} value={s} className="capitalize">{s}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setShowModal(false)}>Cancel</Button>
                            <Button type="submit" disabled={form.processing}>
                                {editUser ? 'Save Changes' : 'Create User'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Confirm */}
            <Dialog open={!!deleteUser} onOpenChange={open => !open && setDeleteUser(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle>Delete User</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-slate-600 dark:text-slate-400">
                        Are you sure you want to delete <strong>{deleteUser?.name}</strong>? This cannot be undone.
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteUser(null)}>Cancel</Button>
                        <Button className="bg-red-600 hover:bg-red-700 text-white" onClick={confirmDelete}>Delete</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
