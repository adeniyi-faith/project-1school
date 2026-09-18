import { useState } from 'react';
import { Link, router, usePage, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Card, CardContent } from '@/components/ui/card';
import { Plus, MoreHorizontal, Pencil, Trash2, ClipboardList, BarChart3, BookOpen, FileText, Settings } from 'lucide-react';
import type { SchoolClass, PageProps, PaginatedResponse } from '@/Types';

interface Exam {
    id: number; name: string; type: string; class_id: number;
    start_date: string | null; end_date: string | null;
    status: 'draft' | 'published' | 'completed';
    description: string | null; created_at: string;
    school_class?: SchoolClass;
}

interface Props {
    exams: PaginatedResponse<Exam>;
    classes: SchoolClass[];
    filters: { class_id?: string; status?: string };
    stats: { total: number; draft: number; published: number; completed: number };
}

const STATUS_STYLE = {
    draft:     'bg-slate-100 text-slate-600',
    published: 'bg-blue-100 text-blue-700',
    completed: 'bg-green-100 text-green-700',
};
const TYPE_LABELS: Record<string, string> = {
    unit_test: 'Unit Test', mid_term: 'Mid Term', final: 'Final', custom: 'Custom',
};

const emptyForm = { name: '', type: 'mid_term', class_id: '', start_date: '', end_date: '', status: 'draft', description: '' };

export default function ExamsIndex({ exams, classes, filters, stats }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Exam | null>(null);
    const { data, setData, post, put, processing, errors, reset } = useForm(emptyForm);

    function applyFilter(key: string, value: string) {
        router.get('/school/exams', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    function openCreate() { reset(); setEditing(null); setOpen(true); }
    function openEdit(e: Exam) {
        setData({ name: e.name, type: e.type, class_id: String(e.class_id), start_date: e.start_date ?? '', end_date: e.end_date ?? '', status: e.status, description: e.description ?? '' });
        setEditing(e); setOpen(true);
    }

    function handleSubmit(ev: React.FormEvent) {
        ev.preventDefault();
        if (editing) {
            put(`/school/exams/${editing.id}`, { onSuccess: () => { setOpen(false); setEditing(null); reset(); } });
        } else {
            post('/school/exams', { onSuccess: () => { setOpen(false); reset(); } });
        }
    }

    function handleDelete(e: Exam) {
        if (!confirm(`Delete exam "${e.name}"?`)) return;
        router.delete(`/school/exams/${e.id}`);
    }

    const statCards = [
        { label: 'Total', value: stats.total, color: 'text-indigo-600', icon: BookOpen },
        { label: 'Draft', value: stats.draft, color: 'text-slate-500', icon: FileText },
        { label: 'Published', value: stats.published, color: 'text-blue-600', icon: ClipboardList },
        { label: 'Completed', value: stats.completed, color: 'text-green-600', icon: BarChart3 },
    ];

    return (
        <AppLayout title="Examinations">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Examinations</h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{stats.total} exams configured</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/school/grade-scales">
                            <Button variant="outline" className="inline-flex items-center gap-2"><Settings className="w-4 h-4" /> Grade Scale</Button>
                        </Link>
                        <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                            <Plus className="w-4 h-4" /> Add Exam
                        </Button>
                    </div>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                {/* Stats */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    {statCards.map(({ label, value, color, icon: Icon }) => (
                        <Card key={label} className="border-slate-200 dark:border-slate-800">
                            <CardContent className="p-4 flex items-center gap-3">
                                <div className={`p-2 rounded-lg bg-slate-100 dark:bg-slate-800 ${color}`}><Icon className="w-5 h-5" /></div>
                                <div>
                                    <p className="text-2xl font-bold text-slate-900 dark:text-white">{value}</p>
                                    <p className="text-xs text-slate-500">{label}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Filters */}
                <div className="flex gap-3 flex-wrap">
                    <Select value={filters.class_id ?? ''} onValueChange={v => applyFilter('class_id', v)}>
                        <SelectTrigger className="w-40"><SelectValue placeholder="All Classes" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Classes</SelectItem>
                            {classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Select value={filters.status ?? ''} onValueChange={v => applyFilter('status', v)}>
                        <SelectTrigger className="w-36"><SelectValue placeholder="All Status" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Status</SelectItem>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="published">Published</SelectItem>
                            <SelectItem value="completed">Completed</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Table */}
                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Exam Name</TableHead>
                                <TableHead>Class</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Dates</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {exams.data.length === 0 ? (
                                <TableRow><TableCell colSpan={6} className="text-center py-16 text-slate-400">No exams yet. Create one to get started.</TableCell></TableRow>
                            ) : exams.data.map((exam) => (
                                <TableRow key={exam.id}>
                                    <TableCell className="font-medium text-slate-900 dark:text-white">{exam.name}</TableCell>
                                    <TableCell className="text-slate-500 text-sm">{exam.school_class?.name ?? '—'}</TableCell>
                                    <TableCell><Badge variant="outline" className="text-xs">{TYPE_LABELS[exam.type] ?? exam.type}</Badge></TableCell>
                                    <TableCell className="text-slate-500 text-sm">
                                        {exam.start_date ? new Date(exam.start_date).toLocaleDateString() : '—'}
                                        {exam.end_date ? ' – ' + new Date(exam.end_date).toLocaleDateString() : ''}
                                    </TableCell>
                                    <TableCell>
                                        <Badge className={`border-0 ${STATUS_STYLE[exam.status]}`}>{exam.status.charAt(0).toUpperCase() + exam.status.slice(1)}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-1">
                                            <Link href={`/school/exams/${exam.id}/marks`}>
                                                <Button variant="outline" size="sm" className="text-xs">Marks</Button>
                                            </Link>
                                            <Link href={`/school/exams/${exam.id}/results`}>
                                                <Button variant="outline" size="sm" className="text-xs">Results</Button>
                                            </Link>
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="icon" className="w-8 h-8"><MoreHorizontal className="w-4 h-4" /></Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem onClick={() => openEdit(exam)} className="flex items-center gap-2 text-sm"><Pencil className="w-4 h-4" /> Edit</DropdownMenuItem>
                                                    <DropdownMenuItem onClick={() => handleDelete(exam)} className="flex items-center gap-2 text-sm text-red-600 focus:text-red-600"><Trash2 className="w-4 h-4" /> Delete</DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader><DialogTitle>{editing ? 'Edit Exam' : 'Create Exam'}</DialogTitle></DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4 mt-2">
                        <div className="space-y-1.5">
                            <Label>Exam Name <span className="text-red-500">*</span></Label>
                            <Input value={data.name} onChange={e => setData('name', e.target.value)} placeholder="e.g. Mid Term Exam 2026" />
                            {errors.name && <p className="text-xs text-red-500">{errors.name}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Type <span className="text-red-500">*</span></Label>
                                <Select value={data.type} onValueChange={v => setData('type', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="unit_test">Unit Test</SelectItem>
                                        <SelectItem value="mid_term">Mid Term</SelectItem>
                                        <SelectItem value="final">Final</SelectItem>
                                        <SelectItem value="custom">Custom</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label>Class <span className="text-red-500">*</span></Label>
                                <Select value={data.class_id} onValueChange={v => setData('class_id', v)}>
                                    <SelectTrigger><SelectValue placeholder="Select class" /></SelectTrigger>
                                    <SelectContent>
                                        {classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                                {errors.class_id && <p className="text-xs text-red-500">{errors.class_id}</p>}
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Start Date</Label>
                                <Input type="date" value={data.start_date} onChange={e => setData('start_date', e.target.value)} />
                            </div>
                            <div className="space-y-1.5">
                                <Label>End Date</Label>
                                <Input type="date" value={data.end_date} onChange={e => setData('end_date', e.target.value)} />
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Status</Label>
                            <Select value={data.status} onValueChange={v => setData('status', v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="draft">Draft</SelectItem>
                                    <SelectItem value="published">Published</SelectItem>
                                    <SelectItem value="completed">Completed</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Description</Label>
                            <Textarea value={data.description} onChange={e => setData('description', e.target.value)} rows={2} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={processing} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {processing ? 'Saving...' : editing ? 'Update' : 'Create'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
