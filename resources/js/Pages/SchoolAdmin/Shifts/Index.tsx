import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Plus, Pencil, Trash2, Clock } from 'lucide-react';
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
import type { PageProps, Shift } from '@/Types';

interface Props extends PageProps { shifts: Shift[] }

const schema = z.object({
    name:       z.string().min(1, 'Name is required'),
    start_time: z.string().regex(/^\d{2}:\d{2}$/, 'Use HH:MM format'),
    end_time:   z.string().regex(/^\d{2}:\d{2}$/, 'Use HH:MM format'),
});
type FormData = z.infer<typeof schema>;

export default function ShiftsIndex() {
    const { shifts } = usePage<Props>().props;
    const [open, setOpen]       = useState(false);
    const [editing, setEditing] = useState<Shift | null>(null);

    const { register, handleSubmit, reset, formState: { errors, isSubmitting } } =
        useForm<FormData>({ resolver: zodResolver(schema) });

    const openCreate = () => { reset({ name: '', start_time: '08:00', end_time: '14:00' }); setEditing(null); setOpen(true); };
    const openEdit   = (s: Shift) => { reset({ name: s.name, start_time: s.start_time.slice(0, 5), end_time: s.end_time.slice(0, 5) }); setEditing(s); setOpen(true); };

    const onSubmit = (data: FormData) => {
        if (editing) {
            router.put(`/school/shifts/${editing.id}`, data, { onSuccess: () => setOpen(false) });
        } else {
            router.post('/school/shifts', data, { onSuccess: () => setOpen(false) });
        }
    };

    return (
        <AppLayout breadcrumbs={[{ label: 'School Setup' }, { label: 'Shifts' }]}>
            <Head title="Shifts" />

            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-xl font-bold text-slate-900 dark:text-white">Shifts</h1>
                    <p className="text-sm text-slate-500 mt-0.5">{shifts.length} shift{shifts.length !== 1 ? 's' : ''} configured</p>
                </div>
                <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                    <Plus className="w-4 h-4" /> Add Shift
                </Button>
            </div>

            <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow className="hover:bg-transparent">
                                <TableHead>Shift Name</TableHead>
                                <TableHead>Start Time</TableHead>
                                <TableHead>End Time</TableHead>
                                <TableHead className="w-20" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {shifts.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={4} className="text-center py-16 text-slate-400">
                                        <Clock className="w-10 h-10 mx-auto mb-3 opacity-30" />
                                        <p className="text-sm">No shifts yet.</p>
                                    </TableCell>
                                </TableRow>
                            ) : shifts.map((s) => (
                                <TableRow key={s.id}>
                                    <TableCell className="font-medium text-slate-900 dark:text-white">{s.name}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{s.start_time.slice(0, 5)}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{s.end_time.slice(0, 5)}</TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-1">
                                            <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => openEdit(s)}><Pencil className="w-3.5 h-3.5" /></Button>
                                            <Button variant="ghost" size="icon" className="h-8 w-8 text-red-500 hover:text-red-600" onClick={() => { if (confirm(`Delete "${s.name}"?`)) router.delete(`/school/shifts/${s.id}`); }}><Trash2 className="w-3.5 h-3.5" /></Button>
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
                        <DialogTitle>{editing ? 'Edit Shift' : 'Add Shift'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 pt-2">
                        <div className="space-y-1.5">
                            <Label>Shift Name <span className="text-red-500">*</span></Label>
                            <Input placeholder="Morning" {...register('name')} />
                            {errors.name && <p className="text-xs text-red-500">{errors.name.message}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label>Start Time <span className="text-red-500">*</span></Label>
                                <Input type="time" {...register('start_time')} />
                                {errors.start_time && <p className="text-xs text-red-500">{errors.start_time.message}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label>End Time <span className="text-red-500">*</span></Label>
                                <Input type="time" {...register('end_time')} />
                                {errors.end_time && <p className="text-xs text-red-500">{errors.end_time.message}</p>}
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={isSubmitting} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {editing ? 'Save Changes' : 'Create Shift'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
