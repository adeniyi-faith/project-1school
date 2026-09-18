import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Search, Users, Eye, Pencil, Trash2, MoreHorizontal } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { PageProps, PaginatedResponse, Student, SchoolClass, Section } from '@/Types';

interface Props extends PageProps {
    students: PaginatedResponse<Student>;
    filters:  { search?: string; class_id?: string; section_id?: string; status?: string };
    classes:  Pick<SchoolClass, 'id' | 'name'>[];
    sections: (Pick<Section, 'id' | 'name'> & { class_id: number })[];
    stats:    { total: number; active: number; alumni: number; transferred: number };
}

const statusBadge = (status: string) => {
    const map: Record<string, string> = {
        active:      'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400',
        alumni:      'bg-blue-100 text-blue-700 dark:bg-blue-950/50 dark:text-blue-400',
        transferred: 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400',
        inactive:    'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
    };
    return (
        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${map[status] ?? map.inactive}`}>
            {status.charAt(0).toUpperCase() + status.slice(1)}
        </span>
    );
};

export default function StudentsIndex() {
    const { students, filters, classes, sections, stats } = usePage<Props>().props;
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilter = (params: Record<string, string>) =>
        router.get('/school/students', { ...filters, ...params }, { preserveState: true, replace: true });

    const visibleSections = filters.class_id
        ? sections.filter((s) => String(s.class_id) === filters.class_id)
        : sections;

    const confirmDelete = (s: Student) => {
        if (confirm(`Remove student "${s.full_name}"?`)) router.delete(`/school/students/${s.id}`);
    };

    return (
        <AppLayout breadcrumbs={[{ label: 'Students' }]}>
            <Head title="Students" />

            {/* Header */}
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-xl font-bold text-slate-900 dark:text-white">Students</h1>
                    <p className="text-sm text-slate-500 mt-0.5">Manage student admissions & records</p>
                </div>
                <Button asChild className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                    <Link href="/school/students/create"><Plus className="w-4 h-4" /> Admit Student</Link>
                </Button>
            </div>

            {/* Stats */}
            <div className="grid grid-cols-4 gap-4 mb-6">
                {[
                    { label: 'Total',       value: stats.total,       color: 'text-slate-800 dark:text-slate-100' },
                    { label: 'Active',      value: stats.active,      color: 'text-emerald-600 dark:text-emerald-400' },
                    { label: 'Alumni',      value: stats.alumni,      color: 'text-blue-600 dark:text-blue-400' },
                    { label: 'Transferred', value: stats.transferred, color: 'text-amber-600 dark:text-amber-400' },
                ].map((s) => (
                    <div key={s.label} className="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4">
                        <p className="text-xs text-slate-500 dark:text-slate-400">{s.label}</p>
                        <p className={`text-2xl font-bold mt-1 ${s.color}`}>{s.value}</p>
                    </div>
                ))}
            </div>

            {/* Table card */}
            <div className="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                {/* Filters */}
                <div className="flex flex-wrap items-center gap-3 p-4 border-b border-slate-200 dark:border-slate-800">
                    <form onSubmit={(e) => { e.preventDefault(); applyFilter({ search }); }} className="flex items-center gap-2 flex-1 min-w-52 max-w-sm">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <Input placeholder="Search name / admission no…" className="pl-9 h-9" value={search} onChange={(e) => setSearch(e.target.value)} />
                        </div>
                        <Button type="submit" size="sm" variant="secondary">Search</Button>
                    </form>
                    <Select value={filters.class_id ?? 'all'} onValueChange={(v) => applyFilter({ class_id: v === 'all' ? '' : v, section_id: '' })}>
                        <SelectTrigger className="w-36 h-9"><SelectValue placeholder="All classes" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All classes</SelectItem>
                            {classes.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Select value={filters.section_id ?? 'all'} onValueChange={(v) => applyFilter({ section_id: v === 'all' ? '' : v })}>
                        <SelectTrigger className="w-32 h-9"><SelectValue placeholder="Section" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All sections</SelectItem>
                            {visibleSections.map((s) => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Select value={filters.status ?? 'all'} onValueChange={(v) => applyFilter({ status: v === 'all' ? '' : v })}>
                        <SelectTrigger className="w-32 h-9"><SelectValue placeholder="Status" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All status</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="alumni">Alumni</SelectItem>
                            <SelectItem value="transferred">Transferred</SelectItem>
                            <SelectItem value="inactive">Inactive</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow className="hover:bg-transparent">
                            <TableHead>Student</TableHead>
                            <TableHead>Admission No</TableHead>
                            <TableHead>Class / Section</TableHead>
                            <TableHead>Guardian</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Admitted</TableHead>
                            <TableHead className="w-10" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {students.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={7} className="text-center py-16 text-slate-400">
                                    <Users className="w-10 h-10 mx-auto mb-3 opacity-30" />
                                    <p className="text-sm">No students found</p>
                                </TableCell>
                            </TableRow>
                        ) : students.data.map((s) => (
                            <TableRow key={s.id}>
                                <TableCell>
                                    <div className="flex items-center gap-3">
                                        <div className="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-950/40 flex items-center justify-center shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                            {s.photo_url
                                                ? <img src={s.photo_url} className="w-8 h-8 rounded-full object-cover" alt="" />
                                                : s.first_name[0].toUpperCase()
                                            }
                                        </div>
                                        <div>
                                            <p className="font-medium text-slate-900 dark:text-white text-sm">{s.full_name}</p>
                                            <p className="text-xs text-slate-400 capitalize">{s.gender}</p>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell className="text-sm font-mono text-slate-500">{s.admission_no}</TableCell>
                                <TableCell className="text-sm text-slate-600 dark:text-slate-400">
                                    {s.school_class?.name ?? '—'}
                                    {s.section && <span className="text-xs text-slate-400"> / {s.section.name}</span>}
                                </TableCell>
                                <TableCell className="text-sm text-slate-600 dark:text-slate-400">
                                    {s.guardian?.name ?? '—'}
                                    {s.guardian?.phone && <p className="text-xs text-slate-400">{s.guardian.phone}</p>}
                                </TableCell>
                                <TableCell>{statusBadge(s.status)}</TableCell>
                                <TableCell className="text-xs text-slate-400">
                                    {s.admission_date ? new Date(s.admission_date).toLocaleDateString() : '—'}
                                </TableCell>
                                <TableCell>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="icon" className="h-8 w-8"><MoreHorizontal className="w-4 h-4" /></Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem asChild>
                                                <Link href={`/school/students/${s.id}`} className="flex items-center gap-2 text-sm"><Eye className="w-4 h-4 shrink-0" /> View</Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem asChild>
                                                <Link href={`/school/students/${s.id}/edit`} className="flex items-center gap-2 text-sm"><Pencil className="w-4 h-4 shrink-0" /> Edit</Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem className="flex items-center gap-2 text-sm text-red-600 dark:text-red-400" onClick={() => confirmDelete(s)}>
                                                <Trash2 className="w-4 h-4 shrink-0" /> Remove
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                {/* Pagination */}
                {students.meta.last_page > 1 && (
                    <div className="flex items-center justify-between px-4 py-3 border-t border-slate-200 dark:border-slate-800">
                        <p className="text-xs text-slate-500">Showing {students.meta.from}–{students.meta.to} of {students.meta.total}</p>
                        <div className="flex gap-1">
                            {students.links.prev && <Button variant="outline" size="sm" onClick={() => router.get(students.links.prev!)}>Previous</Button>}
                            {students.links.next && <Button variant="outline" size="sm" onClick={() => router.get(students.links.next!)}>Next</Button>}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
