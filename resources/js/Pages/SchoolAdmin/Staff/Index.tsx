import { Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Card, CardContent } from '@/components/ui/card';
import {
    Plus, Search, MoreHorizontal, Eye, Pencil, Trash2,
    UserCog, Users, UserCheck, UserMinus,
} from 'lucide-react';
import type { Staff, Department, Designation, PaginatedResponse, PageProps } from '@/Types';
import { useState } from 'react';

interface Props {
    staff: PaginatedResponse<Staff>;
    filters: {
        search?: string;
        department_id?: string;
        designation_id?: string;
        status?: string;
    };
    departments: Department[];
    designations: Designation[];
    stats: {
        total: number;
        active: number;
        on_leave: number;
        resigned: number;
    };
}

const statusColors: Record<string, string> = {
    active:     'bg-green-100 text-green-700 dark:bg-green-950/50 dark:text-green-300',
    on_leave:   'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
    resigned:   'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
    terminated: 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-300',
};

const statusLabels: Record<string, string> = {
    active: 'Active', on_leave: 'On Leave', resigned: 'Resigned', terminated: 'Terminated',
};

export default function StaffIndex({ staff, filters, departments, designations, stats }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search ?? '');

    function applyFilter(key: string, value: string) {
        router.get('/school/staff', { ...filters, [key]: value || undefined, page: undefined }, { preserveScroll: true, preserveState: true });
    }

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        applyFilter('search', search);
    }

    function handleDelete(s: Staff) {
        if (!confirm(`Remove staff member "${s.full_name}"?`)) return;
        router.delete(`/school/staff/${s.id}`);
    }

    const statCards = [
        { label: 'Total Staff', value: stats.total,    icon: Users,     color: 'text-indigo-600' },
        { label: 'Active',      value: stats.active,   icon: UserCheck, color: 'text-green-600' },
        { label: 'On Leave',    value: stats.on_leave, icon: UserCog,   color: 'text-amber-600' },
        { label: 'Resigned',    value: stats.resigned, icon: UserMinus, color: 'text-slate-500' },
    ];

    return (
        <AppLayout title="Staff">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Staff</h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{stats.total} staff members registered</p>
                    </div>
                    <Link href="/school/staff/create">
                        <Button className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                            <Plus className="w-4 h-4" />
                            Add Staff
                        </Button>
                    </Link>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                        {flash.success}
                    </div>
                )}

                {/* Stats */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    {statCards.map(({ label, value, icon: Icon, color }) => (
                        <Card key={label} className="border-slate-200 dark:border-slate-800">
                            <CardContent className="p-4 flex items-center gap-3">
                                <div className={`p-2 rounded-lg bg-slate-100 dark:bg-slate-800 ${color}`}>
                                    <Icon className="w-5 h-5" />
                                </div>
                                <div>
                                    <p className="text-2xl font-bold text-slate-900 dark:text-white">{value}</p>
                                    <p className="text-xs text-slate-500 dark:text-slate-400">{label}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Filters */}
                <div className="flex flex-wrap gap-3">
                    <form onSubmit={handleSearch} className="flex gap-2 flex-1 min-w-[200px]">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <Input
                                className="pl-9"
                                placeholder="Search by name, ID, email..."
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                            />
                        </div>
                        <Button type="submit" variant="outline">Search</Button>
                    </form>

                    <Select value={filters.department_id ?? ''} onValueChange={v => applyFilter('department_id', v)}>
                        <SelectTrigger className="w-44"><SelectValue placeholder="All Departments" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Departments</SelectItem>
                            {departments.map(d => <SelectItem key={d.id} value={String(d.id)}>{d.name}</SelectItem>)}
                        </SelectContent>
                    </Select>

                    <Select value={filters.designation_id ?? ''} onValueChange={v => applyFilter('designation_id', v)}>
                        <SelectTrigger className="w-44"><SelectValue placeholder="All Designations" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Designations</SelectItem>
                            {designations.map(d => <SelectItem key={d.id} value={String(d.id)}>{d.name}</SelectItem>)}
                        </SelectContent>
                    </Select>

                    <Select value={filters.status ?? ''} onValueChange={v => applyFilter('status', v)}>
                        <SelectTrigger className="w-36"><SelectValue placeholder="All Status" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Status</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="on_leave">On Leave</SelectItem>
                            <SelectItem value="resigned">Resigned</SelectItem>
                            <SelectItem value="terminated">Terminated</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Table */}
                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Staff</TableHead>
                                <TableHead>Emp ID</TableHead>
                                <TableHead>Department</TableHead>
                                <TableHead>Designation</TableHead>
                                <TableHead>Phone</TableHead>
                                <TableHead>Joining Date</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-12"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {staff.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-center py-16 text-slate-400">
                                        <UserCog className="w-10 h-10 mx-auto mb-2 opacity-40" />
                                        No staff members found.
                                    </TableCell>
                                </TableRow>
                            ) : staff.data.map((s) => (
                                <TableRow key={s.id}>
                                    <TableCell>
                                        <div className="flex items-center gap-3">
                                            {s.photo_url ? (
                                                <img src={s.photo_url} alt={s.full_name} className="w-8 h-8 rounded-full object-cover" />
                                            ) : (
                                                <div className="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-950 flex items-center justify-center text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                                    {s.first_name[0]}{s.last_name?.[0] ?? ''}
                                                </div>
                                            )}
                                            <div>
                                                <p className="font-medium text-slate-900 dark:text-white text-sm">{s.full_name}</p>
                                                <p className="text-xs text-slate-500 dark:text-slate-400">{s.email ?? s.phone ?? '—'}</p>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell className="font-mono text-xs text-slate-600 dark:text-slate-400">{s.emp_id}</TableCell>
                                    <TableCell className="text-slate-600 dark:text-slate-400 text-sm">{s.department?.name ?? '—'}</TableCell>
                                    <TableCell className="text-slate-600 dark:text-slate-400 text-sm">{s.designation?.name ?? '—'}</TableCell>
                                    <TableCell className="text-slate-600 dark:text-slate-400 text-sm">{s.phone ?? '—'}</TableCell>
                                    <TableCell className="text-slate-600 dark:text-slate-400 text-sm">
                                        {s.joining_date ? new Date(s.joining_date).toLocaleDateString() : '—'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge className={`border-0 ${statusColors[s.status] ?? ''}`}>
                                            {statusLabels[s.status] ?? s.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="icon" className="w-8 h-8">
                                                    <MoreHorizontal className="w-4 h-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem asChild>
                                                    <Link href={`/school/staff/${s.id}`} className="flex items-center gap-2 text-sm">
                                                        <Eye className="w-4 h-4" /> View
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem asChild>
                                                    <Link href={`/school/staff/${s.id}/edit`} className="flex items-center gap-2 text-sm">
                                                        <Pencil className="w-4 h-4" /> Edit
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem onClick={() => handleDelete(s)} className="flex items-center gap-2 text-sm text-red-600 focus:text-red-600">
                                                    <Trash2 className="w-4 h-4" /> Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {/* Pagination */}
                    {staff.meta.last_page > 1 && (
                        <div className="flex items-center justify-between px-4 py-3 border-t border-slate-200 dark:border-slate-800">
                            <p className="text-sm text-slate-500 dark:text-slate-400">
                                Showing {staff.meta.from}–{staff.meta.to} of {staff.meta.total}
                            </p>
                            <div className="flex gap-2">
                                {staff.links.prev && (
                                    <Link href={staff.links.prev} preserveScroll>
                                        <Button variant="outline" size="sm">Previous</Button>
                                    </Link>
                                )}
                                {staff.links.next && (
                                    <Link href={staff.links.next} preserveScroll>
                                        <Button variant="outline" size="sm">Next</Button>
                                    </Link>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
