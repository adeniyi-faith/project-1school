import AppLayout from '@/Layouts/AppLayout';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Checkbox } from '@/components/ui/checkbox';
import { Plus, Pencil, Trash2 } from 'lucide-react';

interface SchoolClass { id: number; name: string; }
interface Subject     { id: number; name: string; }
interface Topic       { title: string; covered: boolean; }

interface Syllabus {
    id: number;
    title: string;
    academic_year: string;
    completion_percent: number;
    topics?: Topic[];
    school_class?: SchoolClass;
    subject?: Subject;
}

interface PaginatedSyllabi {
    data: Syllabus[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    syllabi:  PaginatedSyllabi;
    classes:  SchoolClass[];
    subjects: Subject[];
    filters:  { class_id?: string; academic_year?: string };
}

const emptyForm = { class_id: '', subject_id: '', academic_year: '', title: '', topics: [] as Topic[] };

export default function Syllabi({ syllabi, classes, subjects, filters }: Props) {
    const [open, setOpen]           = useState(false);
    const [editing, setEditing]     = useState<Syllabus | null>(null);
    const [filterClass, setFilterClass]   = useState(filters.class_id ?? '');
    const [filterYear, setFilterYear]     = useState(filters.academic_year ?? '');
    const [newTopic, setNewTopic]         = useState('');

    const { data, setData, post, put, processing, errors, reset } = useForm({ ...emptyForm });

    function openCreate() { reset(); setEditing(null); setOpen(true); }
    function openEdit(s: Syllabus) {
        setEditing(s);
        setData({ class_id: String(s.school_class?.id ?? ''), subject_id: String(s.subject?.id ?? ''), academic_year: s.academic_year, title: s.title, topics: s.topics ?? [] });
        setOpen(true);
    }
    function addTopic() {
        if (!newTopic.trim()) return;
        setData('topics', [...data.topics, { title: newTopic.trim(), covered: false }]);
        setNewTopic('');
    }
    function removeTopic(i: number) {
        setData('topics', data.topics.filter((_, idx) => idx !== i));
    }
    function toggleCovered(i: number) {
        const updated = data.topics.map((t, idx) => idx === i ? { ...t, covered: !t.covered } : t);
        setData('topics', updated);
    }
    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (editing) {
            put(`/school/homework/syllabi/${editing.id}`, { onSuccess: () => { reset(); setOpen(false); } });
        } else {
            post('/school/homework/syllabi', { onSuccess: () => { reset(); setOpen(false); } });
        }
    }
    function applyFilter() {
        router.get('/school/homework/syllabi', { class_id: filterClass || undefined, academic_year: filterYear || undefined }, { preserveState: true });
    }

    function completionColor(pct: number) {
        if (pct >= 80) return 'bg-green-500';
        if (pct >= 50) return 'bg-yellow-500';
        return 'bg-red-500';
    }

    return (
        <AppLayout title="Syllabus">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Syllabus</h1>
                        <p className="text-sm text-slate-500 mt-0.5">Track topic-wise syllabus completion</p>
                    </div>
                    <Button onClick={openCreate} className="gap-2"><Plus className="w-4 h-4" /> New Syllabus</Button>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="pt-4 pb-4">
                        <div className="flex flex-wrap gap-3 items-end">
                            <div className="w-44">
                                <Label className="text-xs mb-1 block">Class</Label>
                                <Select value={filterClass} onValueChange={setFilterClass}>
                                    <SelectTrigger><SelectValue placeholder="All classes" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All</SelectItem>
                                        {classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="w-36">
                                <Label className="text-xs mb-1 block">Academic Year</Label>
                                <Input value={filterYear} onChange={e => setFilterYear(e.target.value)} placeholder="e.g. 2025-26" />
                            </div>
                            <Button variant="outline" onClick={applyFilter}>Filter</Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Table */}
                <Card>
                    <CardHeader><CardTitle>Syllabi ({syllabi.total})</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Title</TableHead>
                                    <TableHead>Class</TableHead>
                                    <TableHead>Subject</TableHead>
                                    <TableHead>Year</TableHead>
                                    <TableHead>Topics</TableHead>
                                    <TableHead>Completion</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {syllabi.data.length === 0 && (
                                    <TableRow><TableCell colSpan={7} className="text-center text-slate-400 py-8">No syllabi found</TableCell></TableRow>
                                )}
                                {syllabi.data.map(s => (
                                    <TableRow key={s.id}>
                                        <TableCell className="font-medium">{s.title}</TableCell>
                                        <TableCell>{s.school_class?.name ?? '—'}</TableCell>
                                        <TableCell>{s.subject?.name ?? '—'}</TableCell>
                                        <TableCell>{s.academic_year}</TableCell>
                                        <TableCell>{s.topics?.length ?? 0}</TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-2">
                                                <div className="w-24 h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                                    <div className={`h-full rounded-full ${completionColor(Number(s.completion_percent))}`}
                                                        style={{ width: `${s.completion_percent}%` }} />
                                                </div>
                                                <span className="text-xs text-slate-500">{Number(s.completion_percent).toFixed(0)}%</span>
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right space-x-1">
                                            <Button size="sm" variant="outline" onClick={() => openEdit(s)}>
                                                <Pencil className="w-3.5 h-3.5" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {syllabi.last_page > 1 && (
                    <div className="flex justify-center gap-1">
                        {syllabi.links.map((link, i) => (
                            <Button key={i} size="sm" variant={link.active ? 'default' : 'outline'} disabled={!link.url}
                                onClick={() => link.url && router.visit(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }} />
                        ))}
                    </div>
                )}
            </div>

            {/* Dialog */}
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader><DialogTitle>{editing ? 'Edit Syllabus' : 'New Syllabus'}</DialogTitle></DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        {!editing && (
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label>Class *</Label>
                                    <Select value={data.class_id} onValueChange={v => setData('class_id', v)}>
                                        <SelectTrigger><SelectValue placeholder="Select class" /></SelectTrigger>
                                        <SelectContent>{classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}</SelectContent>
                                    </Select>
                                    {errors.class_id && <p className="text-xs text-red-500 mt-1">{errors.class_id}</p>}
                                </div>
                                <div>
                                    <Label>Subject *</Label>
                                    <Select value={data.subject_id} onValueChange={v => setData('subject_id', v)}>
                                        <SelectTrigger><SelectValue placeholder="Select subject" /></SelectTrigger>
                                        <SelectContent>{subjects.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}</SelectContent>
                                    </Select>
                                    {errors.subject_id && <p className="text-xs text-red-500 mt-1">{errors.subject_id}</p>}
                                </div>
                            </div>
                        )}
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Title *</Label>
                                <Input value={data.title} onChange={e => setData('title', e.target.value)} />
                                {errors.title && <p className="text-xs text-red-500 mt-1">{errors.title}</p>}
                            </div>
                            {!editing && (
                                <div>
                                    <Label>Academic Year *</Label>
                                    <Input value={data.academic_year} onChange={e => setData('academic_year', e.target.value)} placeholder="e.g. 2025-26" />
                                    {errors.academic_year && <p className="text-xs text-red-500 mt-1">{errors.academic_year}</p>}
                                </div>
                            )}
                        </div>

                        {/* Topics */}
                        <div>
                            <Label>Topics</Label>
                            <div className="mt-2 space-y-1.5 max-h-48 overflow-y-auto border rounded p-2">
                                {data.topics.length === 0 && <p className="text-sm text-slate-400 text-center py-2">No topics added</p>}
                                {data.topics.map((t, i) => (
                                    <div key={i} className="flex items-center gap-2">
                                        <Checkbox checked={t.covered} onCheckedChange={() => toggleCovered(i)} id={`topic-${i}`} />
                                        <label htmlFor={`topic-${i}`} className={`flex-1 text-sm cursor-pointer ${t.covered ? 'line-through text-slate-400' : ''}`}>{t.title}</label>
                                        <button type="button" onClick={() => removeTopic(i)} className="text-red-400 hover:text-red-600 text-xs">×</button>
                                    </div>
                                ))}
                            </div>
                            <div className="flex gap-2 mt-2">
                                <Input value={newTopic} onChange={e => setNewTopic(e.target.value)}
                                    onKeyDown={e => e.key === 'Enter' && (e.preventDefault(), addTopic())}
                                    placeholder="Add topic..." />
                                <Button type="button" variant="outline" onClick={addTopic}>Add</Button>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={processing}>{editing ? 'Update' : 'Create'}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
