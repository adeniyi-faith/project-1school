import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Plus, Pencil, Trash2, BookOpen } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import type { PageProps, SchoolClass, Subject } from '@/Types';

interface Props extends PageProps {
    subjects: Subject[];
    classes:  Pick<SchoolClass, 'id' | 'name'>[];
}

const schema = z.object({
    class_id:   z.coerce.number().int().positive('Select a class'),
    name:       z.string().min(1, 'Subject name is required'),
    code:       z.string().optional(),
    type:       z.enum(['theory', 'practical']),
    full_marks: z.coerce.number().int().min(1).optional(),
    pass_marks: z.coerce.number().int().min(1).optional(),
});
type FormData = z.infer<typeof schema>;

export default function SubjectsIndex() {
    const { subjects, classes } = usePage<Props>().props;
    const [open, setOpen]       = useState(false);
    const [editing, setEditing] = useState<Subject | null>(null);

    const { register, handleSubmit, reset, setValue, formState: { errors, isSubmitting } } =
        useForm<FormData>({ resolver: zodResolver(schema), defaultValues: { type: 'theory', full_marks: 100, pass_marks: 33 } });

    const openCreate = () => { reset({ type: 'theory', full_marks: 100, pass_marks: 33 }); setEditing(null); setOpen(true); };
    const openEdit   = (s: Subject) => { reset({ class_id: s.class_id, name: s.name, code: s.code ?? '', type: s.type, full_marks: s.full_marks, pass_marks: s.pass_marks }); setEditing(s); setOpen(true); };

    const onSubmit = (data: FormData) => {
        if (editing) {
            router.put(`/school/subjects/${editing.id}`, data, { onSuccess: () => setOpen(false) });
        } else {
            router.post('/school/subjects', data, { onSuccess: () => setOpen(false) });
        }
    };

    const destroy = (s: Subject) => {
        if (confirm(`Delete "${s.name}"?`)) router.delete(`/school/subjects/${s.id}`);
    };

    return (
        <AppLayout breadcrumbs={[{ label: 'School Setup' }, { label: 'Subjects' }]}>
            <Head title="Subjects" />

            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-xl font-bold text-slate-900 dark:text-white">Subjects</h1>
                    <p className="text-sm text-slate-500 mt-0.5">{subjects.length} subject{subjects.length !== 1 ? 's' : ''} configured</p>
                </div>
                <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                    <Plus className="w-4 h-4" /> Add Subject
                </Button>
            </div>

            <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow className="hover:bg-transparent">
                                <TableHead>Subject</TableHead>
                                <TableHead>Code</TableHead>
                                <TableHead>Class</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Full / Pass</TableHead>
                                <TableHead className="w-20" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {subjects.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center py-16 text-slate-400">
                                        <BookOpen className="w-10 h-10 mx-auto mb-3 opacity-30" />
                                        <p className="text-sm">No subjects yet.</p>
                                    </TableCell>
                                </TableRow>
                            ) : subjects.map((s) => (
                                <TableRow key={s.id}>
                                    <TableCell className="font-medium text-slate-900 dark:text-white">{s.name}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{s.code ?? '—'}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{s.school_class?.name ?? '—'}</TableCell>
                                    <TableCell>
                                        <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${s.type === 'theory' ? 'bg-blue-100 text-blue-700 dark:bg-blue-950/50 dark:text-blue-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400'}`}>
                                            {s.type}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-sm text-slate-500">{s.full_marks} / {s.pass_marks}</TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-1">
                                            <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => openEdit(s)}><Pencil className="w-3.5 h-3.5" /></Button>
                                            <Button variant="ghost" size="icon" className="h-8 w-8 text-red-500 hover:text-red-600" onClick={() => destroy(s)}><Trash2 className="w-3.5 h-3.5" /></Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Edit Subject' : 'Add Subject'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 pt-2">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5 col-span-2">
                                <Label>Subject Name <span className="text-red-500">*</span></Label>
                                <Input placeholder="Mathematics" {...register('name')} />
                                {errors.name && <p className="text-xs text-red-500">{errors.name.message}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label>Code</Label>
                                <Input placeholder="MATH101" {...register('code')} />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Type <span className="text-red-500">*</span></Label>
                                <Select defaultValue={editing?.type ?? 'theory'} onValueChange={(v) => setValue('type', v as 'theory' | 'practical')}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="theory">Theory</SelectItem>
                                        <SelectItem value="practical">Practical</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5 col-span-2">
                                <Label>Class <span className="text-red-500">*</span></Label>
                                <Select defaultValue={editing ? String(editing.class_id) : undefined} onValueChange={(v) => setValue('class_id', Number(v))}>
                                    <SelectTrigger><SelectValue placeholder="Select class" /></SelectTrigger>
                                    <SelectContent>
                                        {classes.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                                {errors.class_id && <p className="text-xs text-red-500">{errors.class_id.message}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label>Full Marks</Label>
                                <Input type="number" {...register('full_marks')} />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Pass Marks</Label>
                                <Input type="number" {...register('pass_marks')} />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={isSubmitting} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {editing ? 'Save Changes' : 'Create Subject'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
