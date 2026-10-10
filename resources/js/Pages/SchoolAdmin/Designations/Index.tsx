import { useState } from 'react';
import { useForm, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Plus, MoreHorizontal, Pencil, Trash2, BadgeCheck } from 'lucide-react';
import type { Designation, Department, PageProps } from '@/Types';

interface Props {
    designations: Designation[];
    departments: Department[];
}

const emptyForm = { department_id: '', name: '', description: '' };

export default function DesignationsIndex({ designations, departments }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen]       = useState(false);
    const [editing, setEditing] = useState<Designation | null>(null);

    const { data, setData, post, put, processing, errors, reset } = useForm(emptyForm);

    function openCreate() {
        reset();
        setEditing(null);
        setOpen(true);
    }

    function openEdit(d: Designation) {
        setData({ department_id: d.department_id ? String(d.department_id) : '', name: d.name, description: d.description ?? '' });
        setEditing(d);
        setOpen(true);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (editing) {
            put(`/school/designations/${editing.id}`, {
                onSuccess: () => { reset(); setOpen(false); setEditing(null); },
            });
        } else {
            post('/school/designations', {
                onSuccess: () => { reset(); setOpen(false); },
            });
        }
    }

    function handleDelete(d: Designation) {
        if (!confirm(`Delete designation "${d.name}"?`)) return;
        router.delete(`/school/designations/${d.id}`);
    }

    return (
        <AppLayout title="Designations">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Designations</h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{designations.length} designations</p>
                    </div>
                    <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                        <Plus className="w-4 h-4" />
                        Add Designation
                    </Button>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                        {flash.success}
                    </div>
                )}

                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Name</TableHead>
                                <TableHead>Department</TableHead>
                                <TableHead>Staff Count</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead className="w-12"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {designations.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-center py-12 text-slate-400">
                                        <BadgeCheck className="w-10 h-10 mx-auto mb-2 opacity-40" />
                                        No designations yet. Add one to get started.
                                    </TableCell>
                                </TableRow>
                            ) : designations.map((d) => (
                                <TableRow key={d.id}>
                                    <TableCell className="font-medium text-slate-900 dark:text-white">{d.name}</TableCell>
                                    <TableCell>
                                        {d.department
                                            ? <Badge variant="outline">{d.department.name}</Badge>
                                            : <span className="text-slate-400">—</span>}
                                    </TableCell>
                                    <TableCell>
                                        <Badge className="bg-indigo-100 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300 border-0">
                                            {d.staff_count ?? 0} staff
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-slate-500 dark:text-slate-400 max-w-xs truncate">
                                        {d.description ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="icon" className="w-8 h-8">
                                                    <MoreHorizontal className="w-4 h-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem onClick={() => openEdit(d)} className="flex items-center gap-2 text-sm">
                                                    <Pencil className="w-4 h-4" /> Edit
                                                </DropdownMenuItem>
                                                <DropdownMenuItem onClick={() => handleDelete(d)} className="flex items-center gap-2 text-sm text-red-600 focus:text-red-600">
                                                    <Trash2 className="w-4 h-4" /> Delete
                                                </DropdownMenuItem>
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
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Edit Designation' : 'Add Designation'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4 mt-2">
                        <div className="space-y-1.5">
                            <Label>Department</Label>
                            <Select value={data.department_id} onValueChange={v => setData('department_id', v)}>
                                <SelectTrigger><SelectValue placeholder="Select department (optional)" /></SelectTrigger>
                                <SelectContent>
                                    {departments.map(dept => (
                                        <SelectItem key={dept.id} value={String(dept.id)}>{dept.name}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="dname">Name <span className="text-red-500">*</span></Label>
                            <Input id="dname" value={data.name} onChange={e => setData('name', e.target.value)} placeholder="e.g. Head of Department" />
                            {errors.name && <p className="text-xs text-red-500">{errors.name}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ddesc">Description</Label>
                            <Textarea id="ddesc" value={data.description} onChange={e => setData('description', e.target.value)} rows={3} placeholder="Optional description..." />
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
