import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Plus, Pencil, Trash2, GraduationCap } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { PageProps, SchoolClass } from '@/Types';

interface Props extends PageProps { classes: SchoolClass[] }

const schema = z.object({
    name:         z.string().min(1, 'Name is required'),
    numeric_name: z.coerce.number().int().positive().nullable().optional(),
    capacity:     z.coerce.number().int().min(0).optional(),
});
type FormData = z.infer<typeof schema>;

export default function ClassesIndex() {
    const { classes } = usePage<Props>().props;
    const [open, setOpen]     = useState(false);
    const [editing, setEditing] = useState<SchoolClass | null>(null);

    const { register, handleSubmit, reset, formState: { errors, isSubmitting } } =
        useForm<FormData>({ resolver: zodResolver(schema) });

    const openCreate = () => { reset({ name: '', numeric_name: undefined, capacity: 0 }); setEditing(null); setOpen(true); };
    const openEdit   = (c: SchoolClass) => { reset({ name: c.name, numeric_name: c.numeric_name ?? undefined, capacity: c.capacity }); setEditing(c); setOpen(true); };

    const onSubmit = (data: FormData) => {
        if (editing) {
            router.put(`/school/classes/${editing.id}`, data, { onSuccess: () => setOpen(false) });
        } else {
            router.post('/school/classes', data, { onSuccess: () => setOpen(false) });
        }
    };

    const destroy = (c: SchoolClass) => {
        if (confirm(`Delete "${c.name}"?`)) router.delete(`/school/classes/${c.id}`);
    };

    return (
        <AppLayout breadcrumbs={[{ label: 'School Setup' }, { label: 'Classes' }]}>
            <Head title="Classes" />

            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-xl font-bold text-slate-900 dark:text-white">Classes</h1>
                    <p className="text-sm text-slate-500 mt-0.5">{classes.length} class{classes.length !== 1 ? 'es' : ''} configured</p>
                </div>
                <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                    <Plus className="w-4 h-4" /> Add Class
                </Button>
            </div>

            <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow className="hover:bg-transparent">
                                <TableHead>#</TableHead>
                                <TableHead>Class Name</TableHead>
                                <TableHead>Sections</TableHead>
                                <TableHead>Subjects</TableHead>
                                <TableHead>Capacity</TableHead>
                                <TableHead className="w-20" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {classes.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center py-16 text-slate-400">
                                        <GraduationCap className="w-10 h-10 mx-auto mb-3 opacity-30" />
                                        <p className="text-sm">No classes yet. Add your first class.</p>
                                    </TableCell>
                                </TableRow>
                            ) : classes.map((c) => (
                                <TableRow key={c.id}>
                                    <TableCell className="text-slate-400 text-sm">{c.numeric_name ?? '—'}</TableCell>
                                    <TableCell className="font-medium text-slate-900 dark:text-white">{c.name}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{c.sections_count ?? 0}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{c.subjects_count ?? 0}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{c.capacity || '—'}</TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-1">
                                            <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => openEdit(c)}>
                                                <Pencil className="w-3.5 h-3.5" />
                                            </Button>
                                            <Button variant="ghost" size="icon" className="h-8 w-8 text-red-500 hover:text-red-600" onClick={() => destroy(c)}>
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Edit Class' : 'Add Class'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 pt-2">
                        <div className="space-y-1.5">
                            <Label>Class Name <span className="text-red-500">*</span></Label>
                            <Input placeholder="e.g. Grade 1 or Class 10" {...register('name')} />
                            {errors.name && <p className="text-xs text-red-500">{errors.name.message}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label>Order (numeric)</Label>
                                <Input type="number" placeholder="1" {...register('numeric_name')} />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Capacity</Label>
                                <Input type="number" placeholder="40" {...register('capacity')} />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={isSubmitting} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {editing ? 'Save Changes' : 'Create Class'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
