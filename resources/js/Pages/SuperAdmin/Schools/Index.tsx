import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, Search, School, MoreHorizontal, Pencil, Ban, CheckCircle, Trash2, Eye } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { PageProps, PaginatedResponse, School as SchoolType } from '@/Types';

interface SchoolsPageProps extends PageProps {
    schools: PaginatedResponse<SchoolType>;
    filters: { search?: string; status?: string };
    stats: { total: number; active: number; suspended: number };
}

const statusBadge = (status: string) => {
    const map: Record<string, string> = {
        active:    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400',
        inactive:  'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
        suspended: 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-400',
    };
    return (
        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${map[status] ?? map.inactive}`}>
            {status.charAt(0).toUpperCase() + status.slice(1)}
        </span>
    );
};

export default function SchoolsIndex() {
    const { schools, filters, stats } = usePage<SchoolsPageProps>().props;
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilters = (params: Record<string, string>) => {
        router.get('/super-admin/schools', { ...filters, ...params }, { preserveState: true, replace: true });
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters({ search });
    };

    const confirmDelete = (school: SchoolType) => {
        if (confirm(`Delete "${school.name}"? This cannot be undone.`)) {
            router.delete(`/super-admin/schools/${school.id}`);
        }
    };

    return (
        <AppLayout breadcrumbs={[{ label: 'Super Admin' }, { label: 'Schools' }]}>
            <Head title="Schools" />

            {/* Header */}
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-xl font-bold text-slate-900 dark:text-white">Schools</h1>
                    <p className="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Manage all registered schools</p>
                </div>
                <Button asChild className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                    <Link href="/super-admin/schools/create">
                        <Plus className="w-4 h-4" /> Add School
                    </Link>
                </Button>
            </div>

            {/* Stats */}
            <div className="grid grid-cols-3 gap-4 mb-6">
                {[
                    { label: 'Total Schools',    value: stats.total,     color: 'text-slate-700 dark:text-slate-200' },
                    { label: 'Active',           value: stats.active,    color: 'text-emerald-600 dark:text-emerald-400' },
                    { label: 'Suspended',        value: stats.suspended, color: 'text-red-600 dark:text-red-400' },
                ].map((s) => (
                    <div key={s.label} className="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4">
                        <p className="text-xs text-slate-500 dark:text-slate-400">{s.label}</p>
                        <p className={`text-2xl font-bold mt-1 ${s.color}`}>{s.value}</p>
                    </div>
                ))}
            </div>

            {/* Filters */}
            <div className="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                <div className="flex items-center gap-3 p-4 border-b border-slate-200 dark:border-slate-800">
                    <form onSubmit={handleSearch} className="flex items-center gap-2 flex-1 max-w-sm">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <Input
                                placeholder="Search schools…"
                                className="pl-9 h-9"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                        <Button type="submit" size="sm" variant="secondary">Search</Button>
                    </form>
                    <Select
                        value={filters.status ?? 'all'}
                        onValueChange={(v) => applyFilters({ status: v === 'all' ? '' : v })}
                    >
                        <SelectTrigger className="w-36 h-9">
                            <SelectValue placeholder="All status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All status</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="inactive">Inactive</SelectItem>
                            <SelectItem value="suspended">Suspended</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Table */}
                <Table>
                    <TableHeader>
                        <TableRow className="hover:bg-transparent">
                            <TableHead>School</TableHead>
                            <TableHead>Contact</TableHead>
                            <TableHead>Location</TableHead>
                            <TableHead>Users</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Created</TableHead>
                            <TableHead className="w-10" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {schools.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={7} className="text-center py-16 text-slate-400">
                                    <School className="w-10 h-10 mx-auto mb-3 opacity-30" />
                                    <p className="text-sm">No schools found</p>
                                </TableCell>
                            </TableRow>
                        ) : schools.data.map((school) => (
                            <TableRow key={school.id}>
                                <TableCell>
                                    <div className="flex items-center gap-3">
                                        <div className="flex items-center justify-center w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 shrink-0">
                                            {school.logo_url
                                                ? <img src={school.logo_url} alt="" className="w-7 h-7 rounded object-cover" />
                                                : <School className="w-4 h-4 text-indigo-500" />
                                            }
                                        </div>
                                        <div>
                                            <p className="font-medium text-slate-900 dark:text-white text-sm">{school.name}</p>
                                            <p className="text-xs text-slate-400">{school.slug}</p>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell className="text-sm text-slate-600 dark:text-slate-400">
                                    <p>{school.email ?? '—'}</p>
                                    <p className="text-xs">{school.phone ?? ''}</p>
                                </TableCell>
                                <TableCell className="text-sm text-slate-600 dark:text-slate-400">
                                    {[school.city, school.country].filter(Boolean).join(', ') || '—'}
                                </TableCell>
                                <TableCell className="text-sm text-slate-600 dark:text-slate-400">
                                    {school.users_count ?? 0}
                                </TableCell>
                                <TableCell>{statusBadge(school.status)}</TableCell>
                                <TableCell className="text-xs text-slate-400">
                                    {new Date(school.created_at).toLocaleDateString()}
                                </TableCell>
                                <TableCell>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="icon" className="h-8 w-8">
                                                <MoreHorizontal className="w-4 h-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem asChild>
                                                <Link href={`/super-admin/schools/${school.id}`} className="flex items-center gap-2 text-sm">
                                                    <Eye className="w-4 h-4 shrink-0" /> View
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem asChild>
                                                <Link href={`/super-admin/schools/${school.id}/edit`} className="flex items-center gap-2 text-sm">
                                                    <Pencil className="w-4 h-4 shrink-0" /> Edit
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            {school.status === 'suspended' ? (
                                                <DropdownMenuItem className="flex items-center gap-2 text-sm" onClick={() => router.patch(`/super-admin/schools/${school.id}/activate`)}>
                                                    <CheckCircle className="w-4 h-4 shrink-0 text-emerald-500" /> Activate
                                                </DropdownMenuItem>
                                            ) : (
                                                <DropdownMenuItem className="flex items-center gap-2 text-sm" onClick={() => router.patch(`/super-admin/schools/${school.id}/suspend`)}>
                                                    <Ban className="w-4 h-4 shrink-0 text-amber-500" /> Suspend
                                                </DropdownMenuItem>
                                            )}
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                className="flex items-center gap-2 text-sm text-red-600 dark:text-red-400"
                                                onClick={() => confirmDelete(school)}
                                            >
                                                <Trash2 className="w-4 h-4 shrink-0" /> Delete
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                {/* Pagination */}
                {schools.meta.last_page > 1 && (
                    <div className="flex items-center justify-between px-4 py-3 border-t border-slate-200 dark:border-slate-800">
                        <p className="text-xs text-slate-500">
                            Showing {schools.meta.from}–{schools.meta.to} of {schools.meta.total}
                        </p>
                        <div className="flex gap-1">
                            {schools.links.prev && (
                                <Button variant="outline" size="sm" onClick={() => router.get(schools.links.prev!)}>Previous</Button>
                            )}
                            {schools.links.next && (
                                <Button variant="outline" size="sm" onClick={() => router.get(schools.links.next!)}>Next</Button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
