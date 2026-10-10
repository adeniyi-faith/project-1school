import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Plus, Pencil, Trash2, Layers } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { PageProps, SchoolClass, Section } from '@/Types';

interface Props extends PageProps { classes: (SchoolClass & { sections: Section[] })[] }

const schema = z.object({
    class_id: z.coerce.number().int().positive('Select a class'),
    name:     z.string().min(1, 'Section name is required'),
    capacity: z.coerce.number().int().min(0).optional(),
});
type FormData = z.infer<typeof schema>;

export default function SectionsIndex() {
    const { classes } = usePage<Props>().props;
    const [open, setOpen]       = useState(false);
    const [editing, setEditing] = useState<Section | null>(null);

    const { register, handleSubmit, reset, setValue, formState: { errors, isSubmitting } } =
        useForm<FormData>({ resolver: zodResolver(schema) });

    const openCreate = () => { reset({ name: '', capacity: 0 }); setEditing(null); setOpen(true); };
    const openEdit   = (s: Section) => { reset({ class_id: s.class_id, name: s.name, capacity: s.capacity }); setEditing(s); setOpen(true); };

    const onSubmit = (data: FormData) => {
        if (editing) {
            router.put(`/school/sections/${editing.id}`, data, { onSuccess: () => setOpen(false) });
        } else {
            router.post('/school/sections', data, { onSuccess: () => setOpen(false) });
        }
    };

    const destroy = (s: Section) => {
        if (confirm(`Delete section "${s.name}"?`)) router.delete(`/school/sections/${s.id}`);
    };

    return (
        <AppLayout breadcrumbs={[{ label: 'School Setup' }, { label: 'Sections' }]}>
            <Head title="Sections" />

            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-xl font-bold text-slate-900 dark:text-white">Sections</h1>
                    <p className="text-sm text-slate-500 mt-0.5">Sections grouped by class</p>
                </div>
                <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                    <Plus className="w-4 h-4" /> Add Section
                </Button>
            </div>

            <div className="space-y-4">
                {classes.length === 0 ? (
                    <div className="text-center py-16 text-slate-400">
                        <Layers className="w-10 h-10 mx-auto mb-3 opacity-30" />
                        <p className="text-sm">No classes found. Add classes first.</p>
                    </div>
                ) : classes.map((cls) => (
                    <Card key={cls.id} className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                        <CardHeader className="pb-2 pt-4 px-4">
                            <CardTitle className="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center justify-between">
                                <span>{cls.name}</span>
                                <span className="text-xs font-normal text-slate-400">{cls.sections.length} section{cls.sections.length !== 1 ? 's' : ''}</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="px-4 pb-4">
                            {cls.sections.length === 0 ? (
                                <p className="text-xs text-slate-400">No sections — click Add Section above.</p>
                            ) : (
                                <div className="flex flex-wrap gap-2">
                                    {cls.sections.map((s) => (
                                        <div key={s.id} className="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 rounded-lg px-3 py-1.5">
                                            <span className="text-sm font-medium text-slate-700 dark:text-slate-300">{s.name}</span>
                                            {s.capacity > 0 && <span className="text-xs text-slate-400">({s.capacity})</span>}
                                            <button onClick={() => openEdit(s)} className="ml-1 text-slate-400 hover:text-indigo-600"><Pencil className="w-3 h-3" /></button>
                                            <button onClick={() => destroy(s)} className="text-slate-400 hover:text-red-500"><Trash2 className="w-3 h-3" /></button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                ))}
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Edit Section' : 'Add Section'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 pt-2">
                        <div className="space-y-1.5">
                            <Label>Class <span className="text-red-500">*</span></Label>
                            <Select
                                defaultValue={editing ? String(editing.class_id) : undefined}
                                onValueChange={(v) => setValue('class_id', Number(v))}
                            >
                                <SelectTrigger><SelectValue placeholder="Select class" /></SelectTrigger>
                                <SelectContent>
                                    {classes.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                            {errors.class_id && <p className="text-xs text-red-500">{errors.class_id.message}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label>Section Name <span className="text-red-500">*</span></Label>
                                <Input placeholder="A, B, Science…" {...register('name')} />
                                {errors.name && <p className="text-xs text-red-500">{errors.name.message}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label>Capacity</Label>
                                <Input type="number" placeholder="40" {...register('capacity')} />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={isSubmitting} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {editing ? 'Save Changes' : 'Create Section'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
