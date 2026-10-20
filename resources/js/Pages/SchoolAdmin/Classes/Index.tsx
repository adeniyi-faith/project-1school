import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Plus, Pencil, Trash2, GraduationCap, UserRound } from 'lucide-react';
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
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { PageProps, SchoolClass } from '@/Types';
import { useCan } from '@/lib/can';

interface SubjectTeacher { id: number; subject: string; teacher_id: number | null }
type ClassRow = SchoolClass & { class_teacher_staff_id?: number | null; subject_teachers?: SubjectTeacher[] };
interface StaffOption { id: number; name: string }
interface Props extends PageProps { classes: ClassRow[]; levels: Record<string, string>; staff: StaffOption[]; manager_label: string }

const schema = z.object({
    name:         z.string().min(1, 'Name is required'),
    numeric_name: z.coerce.number().int().positive().nullable().optional(),
    capacity:     z.coerce.number().int().min(0).optional(),
    level:        z.string().nullable().optional(),
});
type FormData = z.infer<typeof schema>;

export default function ClassesIndex() {
    const can = useCan();
    const { classes, levels, staff, manager_label } = usePage<Props>().props;
    const [label, setLabel] = useState(manager_label);
    const [teachersFor, setTeachersFor] = useState<ClassRow | null>(null);
    const [classTeacher, setClassTeacher] = useState('');
    const [lineTeachers, setLineTeachers] = useState<Record<number, string>>({});
    const staffName = (id?: number | null) => staff.find(x => x.id === id)?.name;

    const openTeachers = (c: ClassRow) => {
        setTeachersFor(c);
        setClassTeacher(c.class_teacher_staff_id ? String(c.class_teacher_staff_id) : '');
        setLineTeachers(Object.fromEntries((c.subject_teachers ?? []).map(l => [l.id, l.teacher_id ? String(l.teacher_id) : ''])));
    };
    const saveTeachers = () => {
        if (!teachersFor) return;
        router.post(`/school/classes/${teachersFor.id}/teachers`, {
            class_teacher_staff_id: classTeacher || null,
            subjects: Object.entries(lineTeachers).map(([id, t]) => ({ id: Number(id), teacher_id: t || null })),
        }, { onSuccess: () => setTeachersFor(null) });
    };
    const levelItems = [{ value: '', label: 'Not set' }, ...Object.entries(levels).map(([value, label]) => ({ value, label }))];
    const [open, setOpen]     = useState(false);
    const [editing, setEditing] = useState<SchoolClass | null>(null);

    const { register, handleSubmit, reset, setValue, watch, formState: { errors, isSubmitting } } =
        useForm<FormData>({ resolver: zodResolver(schema) });

    const openCreate = () => { reset({ name: '', numeric_name: undefined, capacity: 0, level: '' }); setEditing(null); setOpen(true); };
    const openEdit   = (c: SchoolClass) => { reset({ name: c.name, numeric_name: c.numeric_name ?? undefined, capacity: c.capacity, level: c.level ?? '' }); setEditing(c); setOpen(true); };

    const onSubmit = (data: FormData) => {
        if (editing) {
            router.put(`/school/classes/${editing.id}`, { ...data, level: data.level || null }, { onSuccess: () => setOpen(false) });
        } else {
            router.post('/school/classes', { ...data, level: data.level || null }, { onSuccess: () => setOpen(false) });
        }
    };

    const destroy = (c: SchoolClass) => {
        if (confirm(`Delete "${c.name}"?`)) router.delete(`/school/classes/${c.id}`);
    };

    return (
        <AppLayout breadcrumbs={[{ label: 'School Setup' }, { label: 'Classes' }]}>
            <Head title="Classes" />

            <div className="flex flex-wrap items-center justify-between gap-3 mb-6">
                <div>
                    <h1 className="text-xl font-bold text-slate-900 dark:text-white">Classes</h1>
                    <p className="text-sm text-slate-500 mt-0.5">{classes.length} class{classes.length !== 1 ? 'es' : ''} configured</p>
                </div>
                {can('settings.edit') && (
                    <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                        <Plus className="w-4 h-4" /> Add Class
                    </Button>
                )}
            </div>

            {can('settings.edit') && (
                <form
                    onSubmit={e => { e.preventDefault(); router.put('/school/class-manager-label', { label }, { preserveScroll: true }); }}
                    className="mb-4 flex flex-wrap items-end gap-3"
                >
                    <div className="space-y-1.5">
                        <Label>What do you call the person in charge of a class?</Label>
                        <Input value={label} onChange={e => setLabel(e.target.value)} maxLength={40} className="w-64" placeholder="Class Teacher, Manager, Form Master ..." />
                    </div>
                    <Button type="submit" variant="outline" disabled={!label.trim() || label === manager_label}>Save name</Button>
                </form>
            )}

            <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow className="hover:bg-transparent">
                                <TableHead>#</TableHead>
                                <TableHead>Class Name</TableHead>
                                <TableHead>Level</TableHead>
                                <TableHead>{manager_label}</TableHead>
                                <TableHead>Arms</TableHead>
                                <TableHead>Subjects</TableHead>
                                <TableHead>Capacity</TableHead>
                                <TableHead className="w-20" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {classes.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-center py-16 text-slate-400">
                                        <GraduationCap className="w-10 h-10 mx-auto mb-3 opacity-30" />
                                        <p className="text-sm">No classes yet. Add your first class.</p>
                                    </TableCell>
                                </TableRow>
                            ) : classes.map((c) => (
                                <TableRow key={c.id}>
                                    <TableCell className="text-slate-400 text-sm">{c.numeric_name ?? '—'}</TableCell>
                                    <TableCell className="font-medium text-slate-900 dark:text-white">{c.name}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{c.level ? levels[c.level] : '—'}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{staffName(c.class_teacher_staff_id) ?? '—'}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{c.sections_count ?? 0}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{c.subjects_count ?? 0}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{c.capacity || '—'}</TableCell>
                                    <TableCell>
                                        {can('settings.edit') && (
                                            <div className="flex items-center gap-1">
                                                <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => openTeachers(c)} aria-label={`Teachers of ${c.name}`} title={`${manager_label} and subject teachers`}>
                                                    <UserRound className="w-3.5 h-3.5" />
                                                </Button>
                                                <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => openEdit(c)} aria-label={`Change ${c.name}`}>
                                                    <Pencil className="w-3.5 h-3.5" />
                                                </Button>
                                                <Button variant="ghost" size="icon" className="h-8 w-8 text-red-500 hover:text-red-600" onClick={() => destroy(c)} aria-label={`Delete ${c.name}`}>
                                                    <Trash2 className="w-3.5 h-3.5" />
                                                </Button>
                                            </div>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Dialog open={teachersFor !== null} onOpenChange={(o) => { if (!o) setTeachersFor(null); }}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader><DialogTitle>Teachers of {teachersFor?.name}</DialogTitle></DialogHeader>
                    <div className="space-y-4 pt-2">
                        <div className="space-y-1.5">
                            <Label>{manager_label}</Label>
                            <select className="w-full h-9 rounded-md border border-slate-200 dark:border-slate-700 bg-transparent px-3 text-sm" value={classTeacher} onChange={e => setClassTeacher(e.target.value)}>
                                <option value="">Not set</option>
                                {staff.map(x => <option key={x.id} value={x.id}>{x.name}</option>)}
                            </select>
                            <p className="text-xs text-slate-500">Teachers come from the Staff list. Add someone under Staff first if they are missing.</p>
                        </div>
                        <div className="space-y-2">
                            <Label>Subject teachers</Label>
                            {(teachersFor?.subject_teachers ?? []).length === 0 && <p className="text-xs text-slate-500">This class has no subjects yet. Add them on the Subjects page.</p>}
                            <div className="max-h-72 overflow-y-auto space-y-2 pr-1">
                                {(teachersFor?.subject_teachers ?? []).map(l => (
                                    <div key={l.id} className="grid grid-cols-2 items-center gap-3">
                                        <span className="text-sm text-slate-700 dark:text-slate-200">{l.subject}</span>
                                        <select className="h-9 rounded-md border border-slate-200 dark:border-slate-700 bg-transparent px-2 text-sm" value={lineTeachers[l.id] ?? ''} onChange={e => setLineTeachers(prev => ({ ...prev, [l.id]: e.target.value }))}>
                                            <option value="">Not set</option>
                                            {staff.map(x => <option key={x.id} value={x.id}>{x.name}</option>)}
                                        </select>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setTeachersFor(null)}>Cancel</Button>
                        <Button type="button" onClick={saveTeachers} className="bg-indigo-600 hover:bg-indigo-700 text-white">Save teachers</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

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
                        <div className="space-y-1.5">
                            <Label>Level</Label>
                            <Select items={levelItems} value={watch('level') ?? ''} onValueChange={v => setValue('level', v ?? '')}>
                                <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>{levelItems.map(l => <SelectItem key={l.value} value={l.value}>{l.label}</SelectItem>)}</SelectContent>
                            </Select>
                            <p className="text-xs text-slate-500">Classes at the same level can be given the same subjects in one go.</p>
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
