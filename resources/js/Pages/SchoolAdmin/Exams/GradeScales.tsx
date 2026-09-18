import { useState } from 'react';
import { useForm, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Plus, MoreHorizontal, Pencil, Trash2, ArrowLeft, Settings } from 'lucide-react';
import { Link } from '@inertiajs/react';
import type { PageProps } from '@/Types';

interface GradeScale { id: number; grade: string; gpa: string; min_marks: string; max_marks: string; remarks: string | null; sort_order: number; }

const emptyForm = { grade: '', gpa: '', min_marks: '', max_marks: '', remarks: '', sort_order: '0' };

export default function GradeScalesIndex({ scales }: { scales: GradeScale[] }) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<GradeScale | null>(null);
    const { data, setData, post, put, processing, errors, reset } = useForm(emptyForm);

    function openCreate() { reset(); setEditing(null); setOpen(true); }
    function openEdit(s: GradeScale) {
        setData({ grade: s.grade, gpa: s.gpa, min_marks: s.min_marks, max_marks: s.max_marks, remarks: s.remarks ?? '', sort_order: String(s.sort_order) });
        setEditing(s); setOpen(true);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (editing) {
            put(`/school/grade-scales/${editing.id}`, { onSuccess: () => { setOpen(false); setEditing(null); reset(); } });
        } else {
            post('/school/grade-scales', { onSuccess: () => { setOpen(false); reset(); } });
        }
    }

    function handleDelete(s: GradeScale) {
        if (!confirm(`Delete grade "${s.grade}"?`)) return;
        router.delete(`/school/grade-scales/${s.id}`);
    }

    const GRADE_COLOR: Record<string, string> = {
        'A+': 'bg-emerald-100 text-emerald-700', 'A': 'bg-green-100 text-green-700',
        'A-': 'bg-teal-100 text-teal-700', 'B': 'bg-blue-100 text-blue-700',
        'C': 'bg-amber-100 text-amber-700', 'D': 'bg-orange-100 text-orange-700', 'F': 'bg-red-100 text-red-700',
    };

    return (
        <AppLayout title="Grade Scale">
            <div className="max-w-3xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link href="/school/exams" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Exams
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Grade Scale</h1>
                    </div>
                    <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                        <Plus className="w-4 h-4" /> Add Grade
                    </Button>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                {scales.length === 0 && (
                    <div className="rounded-lg bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 px-4 py-3 text-sm text-amber-700 dark:text-amber-300">
                        No grade scale configured. Add grades to enable automatic grading for exam results.
                    </div>
                )}

                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Grade</TableHead>
                                <TableHead>GPA</TableHead>
                                <TableHead>Marks Range (%)</TableHead>
                                <TableHead>Remarks</TableHead>
                                <TableHead className="w-12"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {scales.map(s => (
                                <TableRow key={s.id}>
                                    <TableCell>
                                        <Badge className={`border-0 text-sm font-bold ${GRADE_COLOR[s.grade] ?? 'bg-slate-100 text-slate-600'}`}>{s.grade}</Badge>
                                    </TableCell>
                                    <TableCell className="font-semibold text-slate-700 dark:text-slate-300">{s.gpa}</TableCell>
                                    <TableCell className="text-slate-600 dark:text-slate-400">{s.min_marks} – {s.max_marks}</TableCell>
                                    <TableCell className="text-slate-500">{s.remarks ?? '—'}</TableCell>
                                    <TableCell>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="icon" className="w-8 h-8"><MoreHorizontal className="w-4 h-4" /></Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem onClick={() => openEdit(s)} className="flex items-center gap-2 text-sm"><Pencil className="w-4 h-4" /> Edit</DropdownMenuItem>
                                                <DropdownMenuItem onClick={() => handleDelete(s)} className="flex items-center gap-2 text-sm text-red-600 focus:text-red-600"><Trash2 className="w-4 h-4" /> Delete</DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader><DialogTitle>{editing ? 'Edit Grade' : 'Add Grade'}</DialogTitle></DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4 mt-2">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Grade <span className="text-red-500">*</span></Label>
                                <Input value={data.grade} onChange={e => setData('grade', e.target.value)} placeholder="A+" />
                                {errors.grade && <p className="text-xs text-red-500">{errors.grade}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label>GPA <span className="text-red-500">*</span></Label>
                                <Input type="number" step="0.01" min="0" max="5" value={data.gpa} onChange={e => setData('gpa', e.target.value)} placeholder="5.00" />
                                {errors.gpa && <p className="text-xs text-red-500">{errors.gpa}</p>}
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Min % <span className="text-red-500">*</span></Label>
                                <Input type="number" min="0" max="100" value={data.min_marks} onChange={e => setData('min_marks', e.target.value)} placeholder="80" />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Max % <span className="text-red-500">*</span></Label>
                                <Input type="number" min="0" max="100" value={data.max_marks} onChange={e => setData('max_marks', e.target.value)} placeholder="100" />
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Remarks</Label>
                            <Input value={data.remarks} onChange={e => setData('remarks', e.target.value)} placeholder="e.g. Outstanding" />
                        </div>
                        <div className="space-y-1.5">
                            <Label>Sort Order</Label>
                            <Input type="number" value={data.sort_order} onChange={e => setData('sort_order', e.target.value)} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={processing} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {processing ? 'Saving...' : editing ? 'Update' : 'Add'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
