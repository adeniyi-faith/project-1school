import AppLayout from '@/Layouts/AppLayout';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Plus, Pencil, Trash2, Eye, Filter } from 'lucide-react';

interface SchoolClass { id: number; name: string; }
interface Subject     { id: number; name: string; }
interface Staff       { id: number; first_name: string; last_name: string; }

interface Homework {
    id: number;
    title: string;
    description?: string;
    due_date: string;
    is_active: boolean;
    submissions_count: number;
    school_class?: SchoolClass;
    subject?: Subject;
    teacher?: Staff;
}

interface PaginatedHomework {
    data: Homework[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    homework: PaginatedHomework;
    classes:  SchoolClass[];
    subjects: Subject[];
    staff:    Staff[];
    filters:  { class_id?: string; subject_id?: string };
}

const empty = { class_id: '', subject_id: '', teacher_id: '', title: '', description: '', due_date: '' };

export default function HomeworkIndex({ homework, classes, subjects, staff, filters }: Props) {
    const [open, setOpen]       = useState(false);
    const [editing, setEditing] = useState<Homework | null>(null);
    const [filterClass, setFilterClass]     = useState(filters.class_id ?? '');
    const [filterSubject, setFilterSubject] = useState(filters.subject_id ?? '');

    const { data, setData, post, put, processing, errors, reset } = useForm({ ...empty });

    function openCreate() {
        reset(); setEditing(null); setOpen(true);
    }
    function openEdit(hw: Homework) {
        setEditing(hw);
        setData({
            class_id:    String(hw.school_class?.id ?? ''),
            subject_id:  String(hw.subject?.id ?? ''),
            teacher_id:  String(hw.teacher?.id ?? ''),
            title:       hw.title,
            description: hw.description ?? '',
            due_date:    hw.due_date,
        });
        setOpen(true);
    }
    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (editing) {
            put(`/school/homework/${editing.id}`, { onSuccess: () => { reset(); setOpen(false); } });
        } else {
            post('/school/homework', { onSuccess: () => { reset(); setOpen(false); } });
        }
    }
    function destroy(id: number) {
        if (confirm('Delete this homework?')) router.delete(`/school/homework/${id}`);
    }
    function applyFilter() {
        router.get('/school/homework', { class_id: filterClass || undefined, subject_id: filterSubject || undefined }, { preserveState: true });
    }

    return (
        <AppLayout title="Homework">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Homework</h1>
                        <p className="text-sm text-slate-500 mt-0.5">Manage and track homework assignments</p>
                    </div>
                    <Button onClick={openCreate} className="gap-2">
                        <Plus className="w-4 h-4" /> Assign Homework
                    </Button>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="pt-4 pb-4">
                        <div className="flex flex-wrap gap-3 items-end">
                            <div className="w-48">
                                <Label className="text-xs mb-1 block">Class</Label>
                                <Select value={filterClass} onValueChange={setFilterClass}>
                                    <SelectTrigger><SelectValue placeholder="All classes" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All classes</SelectItem>
                                        {classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="w-48">
                                <Label className="text-xs mb-1 block">Subject</Label>
                                <Select value={filterSubject} onValueChange={setFilterSubject}>
                                    <SelectTrigger><SelectValue placeholder="All subjects" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All subjects</SelectItem>
                                        {subjects.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button variant="outline" onClick={applyFilter} className="gap-2">
                                <Filter className="w-4 h-4" /> Filter
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Table */}
                <Card>
                    <CardHeader><CardTitle>Assignments ({homework.total})</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Title</TableHead>
                                    <TableHead>Class</TableHead>
                                    <TableHead>Subject</TableHead>
                                    <TableHead>Teacher</TableHead>
                                    <TableHead>Due Date</TableHead>
                                    <TableHead>Submissions</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {homework.data.length === 0 && (
                                    <TableRow><TableCell colSpan={8} className="text-center text-slate-400 py-8">No homework found</TableCell></TableRow>
                                )}
                                {homework.data.map(hw => (
                                    <TableRow key={hw.id}>
                                        <TableCell className="font-medium">{hw.title}</TableCell>
                                        <TableCell>{hw.school_class?.name ?? '—'}</TableCell>
                                        <TableCell>{hw.subject?.name ?? '—'}</TableCell>
                                        <TableCell>{hw.teacher ? `${hw.teacher.first_name} ${hw.teacher.last_name}` : '—'}</TableCell>
                                        <TableCell>{new Date(hw.due_date).toLocaleDateString()}</TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">{hw.submissions_count}</Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={hw.is_active ? 'default' : 'secondary'}>
                                                {hw.is_active ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right space-x-1">
                                            <Button size="sm" variant="outline" onClick={() => router.visit(`/school/homework/${hw.id}/submissions`)}>
                                                <Eye className="w-3.5 h-3.5" />
                                            </Button>
                                            <Button size="sm" variant="outline" onClick={() => openEdit(hw)}>
                                                <Pencil className="w-3.5 h-3.5" />
                                            </Button>
                                            <Button size="sm" variant="destructive" onClick={() => destroy(hw.id)}>
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Pagination */}
                {homework.last_page > 1 && (
                    <div className="flex justify-center gap-1">
                        {homework.links.map((link, i) => (
                            <Button
                                key={i}
                                size="sm"
                                variant={link.active ? 'default' : 'outline'}
                                disabled={!link.url}
                                onClick={() => link.url && router.visit(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>

            {/* Dialog */}
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Edit Homework' : 'Assign Homework'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Class *</Label>
                                <Select value={data.class_id} onValueChange={v => setData('class_id', v)}>
                                    <SelectTrigger><SelectValue placeholder="Select class" /></SelectTrigger>
                                    <SelectContent>
                                        {classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                                {errors.class_id && <p className="text-xs text-red-500 mt-1">{errors.class_id}</p>}
                            </div>
                            <div>
                                <Label>Subject *</Label>
                                <Select value={data.subject_id} onValueChange={v => setData('subject_id', v)}>
                                    <SelectTrigger><SelectValue placeholder="Select subject" /></SelectTrigger>
                                    <SelectContent>
                                        {subjects.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                                {errors.subject_id && <p className="text-xs text-red-500 mt-1">{errors.subject_id}</p>}
                            </div>
                        </div>
                        <div>
                            <Label>Teacher</Label>
                            <Select value={data.teacher_id} onValueChange={v => setData('teacher_id', v)}>
                                <SelectTrigger><SelectValue placeholder="Select teacher (optional)" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">None</SelectItem>
                                    {staff.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.first_name} {s.last_name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Title *</Label>
                            <Input value={data.title} onChange={e => setData('title', e.target.value)} placeholder="e.g. Chapter 5 Exercise" />
                            {errors.title && <p className="text-xs text-red-500 mt-1">{errors.title}</p>}
                        </div>
                        <div>
                            <Label>Description</Label>
                            <Textarea value={data.description} onChange={e => setData('description', e.target.value)} rows={3} placeholder="Instructions..." />
                        </div>
                        <div>
                            <Label>Due Date *</Label>
                            <Input type="date" value={data.due_date} onChange={e => setData('due_date', e.target.value)} />
                            {errors.due_date && <p className="text-xs text-red-500 mt-1">{errors.due_date}</p>}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={processing}>{editing ? 'Update' : 'Assign'}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
