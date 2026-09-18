import { useState } from 'react';
import { useForm, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
import { Plus, MoreHorizontal, Pencil, Trash2, Building2 } from 'lucide-react';
import type { Department, PageProps } from '@/Types';

interface Props {
    departments: Department[];
}

const emptyForm = { name: '', code: '', description: '' };

export default function DepartmentsIndex({ departments }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen]       = useState(false);
    const [editing, setEditing] = useState<Department | null>(null);

    const { data, setData, post, put, processing, errors, reset } = useForm(emptyForm);

    function openCreate() {
        reset();
        setEditing(null);
        setOpen(true);
    }

    function openEdit(dept: Department) {
        setData({ name: dept.name, code: dept.code ?? '', description: dept.description ?? '' });
        setEditing(dept);
        setOpen(true);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (editing) {
            put(`/school/departments/${editing.id}`, {
                onSuccess: () => { reset(); setOpen(false); setEditing(null); },
            });
        } else {
            post('/school/departments', {
                onSuccess: () => { reset(); setOpen(false); },
            });
        }
    }

    function handleDelete(dept: Department) {
        if (!confirm(`Delete department "${dept.name}"?`)) return;
        router.delete(`/school/departments/${dept.id}`);
    }

    return (
        <AppLayout title="Departments">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Departments</h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{departments.length} departments</p>
                    </div>
                    <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                        <Plus className="w-4 h-4" />
                        Add Department
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
                                <TableHead>Code</TableHead>
                                <TableHead>Staff Count</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead className="w-12"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {departments.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-center py-12 text-slate-400">
                                        <Building2 className="w-10 h-10 mx-auto mb-2 opacity-40" />
                                        No departments yet. Add one to get started.
                                    </TableCell>
                                </TableRow>
                            ) : departments.map((dept) => (
                                <TableRow key={dept.id}>
                                    <TableCell className="font-medium text-slate-900 dark:text-white">{dept.name}</TableCell>
                                    <TableCell>
                                        {dept.code ? <Badge variant="outline">{dept.code}</Badge> : <span className="text-slate-400">—</span>}
                                    </TableCell>
                                    <TableCell>
                                        <Badge className="bg-indigo-100 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300 border-0">
                                            {dept.staff_count ?? 0} staff
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-slate-500 dark:text-slate-400 max-w-xs truncate">
                                        {dept.description ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="icon" className="w-8 h-8">
                                                    <MoreHorizontal className="w-4 h-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem onClick={() => openEdit(dept)} className="flex items-center gap-2 text-sm">
                                                    <Pencil className="w-4 h-4" /> Edit
                                                </DropdownMenuItem>
                                                <DropdownMenuItem onClick={() => handleDelete(dept)} className="flex items-center gap-2 text-sm text-red-600 focus:text-red-600">
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
                        <DialogTitle>{editing ? 'Edit Department' : 'Add Department'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4 mt-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="name">Name <span className="text-red-500">*</span></Label>
                            <Input id="name" value={data.name} onChange={e => setData('name', e.target.value)} placeholder="e.g. Science & Technology" />
                            {errors.name && <p className="text-xs text-red-500">{errors.name}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="code">Code</Label>
                            <Input id="code" value={data.code} onChange={e => setData('code', e.target.value)} placeholder="e.g. SCI" />
                            {errors.code && <p className="text-xs text-red-500">{errors.code}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="description">Description</Label>
                            <Textarea id="description" value={data.description} onChange={e => setData('description', e.target.value)} rows={3} placeholder="Optional description..." />
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
