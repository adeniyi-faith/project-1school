import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Search, Users, Eye, Pencil, Trash2, MoreHorizontal, ChevronRight } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { EmptyState, PageHeader, Panel, PersonAvatar, Pill, StatStrip } from '@/components/app/kit';
import type { PageProps, PaginatedResponse, Student, SchoolClass, Section } from '@/Types';

interface Props extends PageProps {
    students: PaginatedResponse<Student>;
    filters:  { search?: string; class_id?: string; section_id?: string; status?: string };
    classes:  Pick<SchoolClass, 'id' | 'name'>[];
    sections: (Pick<Section, 'id' | 'name'> & { class_id: number })[];
    stats:    { total: number; active: number; alumni: number; transferred: number };
}

const STATUS_PILL: Record<string, { tone: 'good' | 'info' | 'warn' | 'neutral'; label: string }> = {
    active:      { tone: 'good',    label: 'Active' },
    alumni:      { tone: 'info',    label: 'Alumni' },
    transferred: { tone: 'warn',    label: 'Transferred' },
    inactive:    { tone: 'neutral', label: 'Inactive' },
};

const statusPill = (status: string) => {
    const s = STATUS_PILL[status] ?? STATUS_PILL.inactive;
    return <Pill tone={s.tone}>{s.label}</Pill>;
};

const classLabel = (s: Student) => `${s.school_class?.name ?? '—'}${s.section ? ` · ${s.section.name}` : ''}`;

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
        <AppLayout breadcrumbs={[{ label: 'Academic' }, { label: 'Students' }]}>
            <Head title="Students" />

            <div className="space-y-6">
                <PageHeader
                    title="Students"
                    description="Admissions and records for every student in your school."
                    actions={
                        <Link href="/school/students/create" className="inline-flex h-9 items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 text-sm font-medium text-white shadow-xs outline-none transition-colors hover:bg-indigo-700 focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2">
                            <Plus className="size-4" /> Admit student
                        </Link>
                    }
                />

                <StatStrip
                    items={[
                        { label: 'All students', value: stats.total },
                        { label: 'Active', value: stats.active, tone: 'good' },
                        { label: 'Alumni', value: stats.alumni },
                        { label: 'Transferred', value: stats.transferred },
                    ]}
                />

                <Panel flush bodyClassName="pt-0">
                    {/* Filters */}
                    <div className="flex flex-wrap items-center gap-2.5 border-b border-slate-200 p-4 dark:border-white/[0.08]">
                        <form onSubmit={(e) => { e.preventDefault(); applyFilter({ search }); }} className="relative min-w-0 flex-[1_1_16rem] max-w-md">
                            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                placeholder="Search by name or admission number"
                                className="h-10 rounded-lg bg-slate-50 pl-9 text-sm dark:bg-white/[0.04]"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                aria-label="Search students"
                            />
                        </form>
                        <Select value={filters.class_id ?? 'all'} onValueChange={(v) => applyFilter({ class_id: v === 'all' ? '' : v, section_id: '' })}>
                            <SelectTrigger className="h-10 w-[9.5rem]"><SelectValue placeholder="All classes" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All classes</SelectItem>
                                {classes.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                            </SelectContent>
                        </Select>
                        <Select value={filters.section_id ?? 'all'} onValueChange={(v) => applyFilter({ section_id: v === 'all' ? '' : v })}>
                            <SelectTrigger className="h-10 w-[9.5rem]"><SelectValue placeholder="Section" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All sections</SelectItem>
                                {visibleSections.map((s) => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}
                            </SelectContent>
                        </Select>
                        <Select value={filters.status ?? 'all'} onValueChange={(v) => applyFilter({ status: v === 'all' ? '' : v })}>
                            <SelectTrigger className="h-10 w-[9.5rem]"><SelectValue placeholder="Status" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All statuses</SelectItem>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="alumni">Alumni</SelectItem>
                                <SelectItem value="transferred">Transferred</SelectItem>
                                <SelectItem value="inactive">Inactive</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    {students.data.length === 0 ? (
                        <EmptyState icon={Users} title="No students found" text="Try a different name, or clear the class and status filters." />
                    ) : (
                        <>
                            {/* Desktop table */}
                            <div className="hidden md:block">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="hover:bg-transparent">
                                            <TableHead className="h-10 pl-5 text-xs font-medium uppercase tracking-wide text-slate-500">Student</TableHead>
                                            <TableHead className="h-10 text-xs font-medium uppercase tracking-wide text-slate-500">Admission no.</TableHead>
                                            <TableHead className="h-10 text-xs font-medium uppercase tracking-wide text-slate-500">Class</TableHead>
                                            <TableHead className="h-10 text-xs font-medium uppercase tracking-wide text-slate-500">Parent or guardian</TableHead>
                                            <TableHead className="h-10 text-xs font-medium uppercase tracking-wide text-slate-500">Status</TableHead>
                                            <TableHead className="h-10 text-xs font-medium uppercase tracking-wide text-slate-500">Admitted</TableHead>
                                            <TableHead className="h-10 w-12 pr-5"><span className="sr-only">Actions</span></TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {students.data.map((s) => (
                                            <TableRow key={s.id} className="group">
                                                <TableCell className="py-3 pl-5">
                                                    <Link href={`/school/students/${s.id}`} className="flex items-center gap-3 outline-none">
                                                        <PersonAvatar name={s.full_name} src={s.photo_url} />
                                                        <span className="min-w-0">
                                                            <span className="block truncate text-sm font-medium text-slate-900 group-hover:text-indigo-700 dark:text-white dark:group-hover:text-indigo-300">{s.full_name}</span>
                                                            <span className="block text-xs capitalize text-slate-500">{s.gender}</span>
                                                        </span>
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="font-mono text-[13px] text-slate-600 dark:text-slate-300">{s.admission_no}</TableCell>
                                                <TableCell className="text-sm text-slate-700 dark:text-slate-300">{classLabel(s)}</TableCell>
                                                <TableCell className="text-sm">
                                                    <span className="block text-slate-700 dark:text-slate-300">{s.guardian?.name ?? '—'}</span>
                                                    {s.guardian?.phone && <span className="block text-xs text-slate-500">{s.guardian.phone}</span>}
                                                </TableCell>
                                                <TableCell>{statusPill(s.status)}</TableCell>
                                                <TableCell className="whitespace-nowrap text-sm text-slate-500">
                                                    {s.admission_date ? new Date(s.admission_date).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'}
                                                </TableCell>
                                                <TableCell className="pr-5">
                                                    <RowMenu student={s} onDelete={confirmDelete} />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Phone list */}
                            <ul className="divide-y divide-slate-100 md:hidden dark:divide-white/[0.06]">
                                {students.data.map((s) => (
                                    <li key={s.id}>
                                        <Link href={`/school/students/${s.id}`} className="flex items-center gap-3 px-4 py-3 active:bg-slate-50 dark:active:bg-white/[0.04]">
                                            <PersonAvatar name={s.full_name} src={s.photo_url} />
                                            <span className="min-w-0 flex-1">
                                                <span className="block truncate text-sm font-medium text-slate-900 dark:text-white">{s.full_name}</span>
                                                <span className="block truncate text-xs text-slate-500">{classLabel(s)} · {s.admission_no}</span>
                                            </span>
                                            {statusPill(s.status)}
                                            <ChevronRight className="size-4 shrink-0 text-slate-300" />
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </>
                    )}

                    {students.meta.last_page > 1 && (
                        <div className="flex items-center justify-between gap-3 border-t border-slate-200 px-5 py-3 dark:border-white/[0.08]">
                            <p className="text-xs text-slate-500 tabular-nums">Showing {students.meta.from}–{students.meta.to} of {students.meta.total}</p>
                            <div className="flex gap-2">
                                {students.links.prev && <Button variant="outline" size="sm" onClick={() => router.get(students.links.prev!)}>Previous</Button>}
                                {students.links.next && <Button variant="outline" size="sm" onClick={() => router.get(students.links.next!)}>Next</Button>}
                            </div>
                        </div>
                    )}
                </Panel>

                <Link href="/school/students/create" aria-label="Admit student" className="fixed bottom-24 right-4 z-20 flex size-14 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg transition-colors hover:bg-indigo-700 md:hidden">
                    <Plus className="size-6" />
                </Link>
            </div>
        </AppLayout>
    );
}

function RowMenu({ student, onDelete }: { student: Student; onDelete: (s: Student) => void }) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="size-8 text-slate-400 hover:text-slate-700" aria-label={`Actions for ${student.full_name}`}>
                    <MoreHorizontal className="size-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem asChild>
                    <Link href={`/school/students/${student.id}`} className="flex items-center gap-2 text-sm"><Eye className="size-4 shrink-0" /> View profile</Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link href={`/school/students/${student.id}/edit`} className="flex items-center gap-2 text-sm"><Pencil className="size-4 shrink-0" /> Edit details</Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem className="flex items-center gap-2 text-sm text-red-600 dark:text-red-400" onClick={() => onDelete(student)}>
                    <Trash2 className="size-4 shrink-0" /> Remove
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
