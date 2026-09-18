import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Plus, Pencil, Trash2, CalendarOff } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { PageProps, Holiday } from '@/Types';

interface Props extends PageProps { holidays: Holiday[] }

const schema = z.object({
    name:        z.string().min(1, 'Name is required'),
    date:        z.string().min(1, 'Date is required'),
    description: z.string().optional(),
});
type FormData = z.infer<typeof schema>;

export default function HolidaysIndex() {
    const { holidays } = usePage<Props>().props;
    const [open, setOpen]       = useState(false);
    const [editing, setEditing] = useState<Holiday | null>(null);

    const { register, handleSubmit, reset, formState: { errors, isSubmitting } } =
        useForm<FormData>({ resolver: zodResolver(schema) });

    const openCreate = () => { reset({ name: '', date: '', description: '' }); setEditing(null); setOpen(true); };
    const openEdit   = (h: Holiday) => { reset({ name: h.name, date: h.date.slice(0, 10), description: h.description ?? '' }); setEditing(h); setOpen(true); };

    const onSubmit = (data: FormData) => {
        if (editing) {
            router.put(`/school/holidays/${editing.id}`, data, { onSuccess: () => setOpen(false) });
        } else {
            router.post('/school/holidays', data, { onSuccess: () => setOpen(false) });
        }
    };

    return (
        <AppLayout breadcrumbs={[{ label: 'School Setup' }, { label: 'Holidays' }]}>
            <Head title="Holidays" />

            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-xl font-bold text-slate-900 dark:text-white">Holidays</h1>
                    <p className="text-sm text-slate-500 mt-0.5">{holidays.length} holiday{holidays.length !== 1 ? 's' : ''} defined</p>
                </div>
                <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                    <Plus className="w-4 h-4" /> Add Holiday
                </Button>
            </div>

            <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow className="hover:bg-transparent">
                                <TableHead>Date</TableHead>
                                <TableHead>Holiday Name</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead className="w-20" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {holidays.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={4} className="text-center py-16 text-slate-400">
                                        <CalendarOff className="w-10 h-10 mx-auto mb-3 opacity-30" />
                                        <p className="text-sm">No holidays defined yet.</p>
                                    </TableCell>
                                </TableRow>
                            ) : holidays.map((h) => (
                                <TableRow key={h.id}>
                                    <TableCell className="font-medium text-slate-900 dark:text-white">
                                        {new Date(h.date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}
                                    </TableCell>
                                    <TableCell className="text-sm text-slate-700 dark:text-slate-300">{h.name}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{h.description ?? '—'}</TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-1">
                                            <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => openEdit(h)}><Pencil className="w-3.5 h-3.5" /></Button>
                                            <Button variant="ghost" size="icon" className="h-8 w-8 text-red-500 hover:text-red-600" onClick={() => { if (confirm(`Delete "${h.name}"?`)) router.delete(`/school/holidays/${h.id}`); }}><Trash2 className="w-3.5 h-3.5" /></Button>
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
                        <DialogTitle>{editing ? 'Edit Holiday' : 'Add Holiday'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 pt-2">
                        <div className="space-y-1.5">
                            <Label>Holiday Name <span className="text-red-500">*</span></Label>
                            <Input placeholder="Eid-ul-Fitr" {...register('name')} />
                            {errors.name && <p className="text-xs text-red-500">{errors.name.message}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label>Date <span className="text-red-500">*</span></Label>
                            <Input type="date" {...register('date')} />
                            {errors.date && <p className="text-xs text-red-500">{errors.date.message}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label>Description</Label>
                            <Input placeholder="Optional note" {...register('description')} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={isSubmitting} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {editing ? 'Save Changes' : 'Add Holiday'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
