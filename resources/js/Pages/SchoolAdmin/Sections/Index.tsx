import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Layers, Pencil, Plus, Trash2 } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { EmptyState, PageHeader, Panel, Pill } from '@/components/app/kit';
import type { PageProps, SchoolClass, Section } from '@/Types';

interface Props extends PageProps {
    classes: (Pick<SchoolClass, 'id' | 'name' | 'level'> & { sections: Section[] })[];
    departments: string[];
}

interface FormData { class_id: string; names: string; name: string; department: string; capacity: string }
const empty: FormData = { class_id: '', names: '', name: '', department: '', capacity: '' };
const quick = ['A, B', 'A, B, C', 'A, B, C, D'];

/** Arms of each class (JSS 1 A, JSS 1 B ... or JSS 1 Emerald). Senior arms can carry a department. */
export default function ArmsIndex({ classes, departments }: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Section | null>(null);
    const form = useForm<FormData>(empty);
    const d = form.data;
    const level = classes.find(c => String(c.id) === d.class_id)?.level ?? null;

    function openCreate(classId?: number) {
        form.clearErrors();
        form.setData({ ...empty, class_id: classId ? String(classId) : '' });
        setEditing(null);
        setOpen(true);
    }

    function openEdit(s: Section) {
        form.clearErrors();
        form.setData({ ...empty, class_id: String(s.class_id), name: s.name, department: s.department ?? '', capacity: s.capacity ? String(s.capacity) : '' });
        setEditing(s);
        setOpen(true);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };
        if (editing) {
            form.transform(data => ({ name: data.name, department: data.department || null, capacity: data.capacity || null }));
            form.put(`/school/sections/${editing.id}`, options);
        } else {
            form.transform(data => ({ class_id: data.class_id, names: data.names, department: data.department || null, capacity: data.capacity || null }));
            form.post('/school/sections', options);
        }
    }

    function destroy(s: Section, cls: string) {
        const n = s.students_count ?? 0;
        if (confirm(`Delete ${cls} ${s.name}?${n ? ` Its ${n} student${n === 1 ? '' : 's'} stay in ${cls} without an arm.` : ''}`)) {
            router.delete(`/school/sections/${s.id}`, { preserveScroll: true });
        }
    }

    const classItems = classes.map(c => ({ value: String(c.id), label: c.name }));
    const deptItems = [{ value: '', label: 'No department' }, ...departments.map(x => ({ value: x, label: x }))];
    const actions = (
        <Button onClick={() => openCreate()} disabled={classes.length === 0} className="bg-indigo-600 text-white hover:bg-indigo-700">
            <Plus className="size-4" /> Add arms
        </Button>
    );

    return (
        <AppLayout breadcrumbs={[{ label: 'School Setup' }, { label: 'Arms' }]}>
            <Head title="Arms" />
            <div className="mx-auto max-w-5xl space-y-5 pb-24 md:pb-0">
                <PageHeader title="Arms"
                    description="The groups each class is split into, such as JSS 1A and JSS 1B, or JSS 1 Emerald. In senior classes, give an arm its department (Science, Arts, Commercial) so its students only get that department's subjects."
                    actions={actions} />
                <div className="md:hidden">{actions}</div>

                {classes.length === 0 ? (
                    <Panel><EmptyState icon={Layers} title="No classes yet" text="Add classes first, under School Setup, Classes." /></Panel>
                ) : (
                    <div className="grid gap-3 sm:grid-cols-2">
                        {classes.map(cls => (
                            <Panel key={cls.id} title={cls.name}
                                description={cls.sections.length ? `${cls.sections.length} arm${cls.sections.length === 1 ? '' : 's'}, ${cls.sections.reduce((n, s) => n + (s.students_count ?? 0), 0)} students` : 'No arms: the class is taught as one group'}
                                action={<button type="button" onClick={() => openCreate(cls.id)} className="font-medium text-indigo-600 hover:underline dark:text-indigo-400">Add</button>}>
                                {cls.sections.length > 0 && (
                                    <ul className="-my-1 divide-y divide-slate-100 dark:divide-white/[0.06]">
                                        {cls.sections.map(s => (
                                            <li key={s.id} className="flex items-center gap-2 py-1.5">
                                                <span className="font-medium text-slate-800 dark:text-slate-100">{s.name}</span>
                                                {s.department && <Pill tone="info">{s.department}</Pill>}
                                                <span className="ml-auto text-xs text-slate-500">
                                                    {s.students_count ?? 0}{s.capacity > 0 ? ` of ${s.capacity}` : ''} student{(s.students_count ?? 0) === 1 && !s.capacity ? '' : 's'}
                                                </span>
                                                <Button variant="ghost" size="sm" onClick={() => openEdit(s)} aria-label={`Change ${cls.name} ${s.name}`}><Pencil className="size-3.5" /></Button>
                                                <Button variant="ghost" size="sm" onClick={() => destroy(s, cls.name)} aria-label={`Delete ${cls.name} ${s.name}`} className="text-red-600 hover:text-red-700"><Trash2 className="size-3.5" /></Button>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </Panel>
                        ))}
                    </div>
                )}
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>{editing ? 'Change arm' : 'Add arms'}</DialogTitle></DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        {!editing && (
                            <div className="space-y-1.5">
                                <Label>Class</Label>
                                <Select items={classItems} value={d.class_id} onValueChange={v => form.setData('class_id', v ?? '')}>
                                    <SelectTrigger className="w-full"><SelectValue placeholder="Choose a class" /></SelectTrigger>
                                    <SelectContent>{classItems.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                                </Select>
                                {form.errors.class_id && <p className="text-xs text-red-600">{form.errors.class_id}</p>}
                            </div>
                        )}
                        {editing ? (
                            <div className="space-y-1.5">
                                <Label htmlFor="arm-name">Name</Label>
                                <Input id="arm-name" value={d.name} onChange={e => form.setData('name', e.target.value)} />
                                {form.errors.name && <p className="text-xs text-red-600">{form.errors.name}</p>}
                            </div>
                        ) : (
                            <div className="space-y-1.5">
                                <Label htmlFor="arm-names">Names</Label>
                                <Input id="arm-names" value={d.names} onChange={e => form.setData('names', e.target.value)} placeholder="A, B, C  or  Emerald, Ruby" />
                                <div className="flex flex-wrap gap-1.5">
                                    {quick.map(q => (
                                        <button key={q} type="button" onClick={() => form.setData('names', q)} className="rounded-md border border-slate-200 px-2 py-0.5 text-xs text-slate-600 hover:bg-slate-50 dark:border-white/10 dark:text-slate-300">{q}</button>
                                    ))}
                                </div>
                                <p className="text-xs text-slate-500">Put commas between names to add several at once.</p>
                                {form.errors.names && <p className="text-xs text-red-600">{form.errors.names}</p>}
                            </div>
                        )}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Department</Label>
                                <Select items={deptItems} value={d.department} onValueChange={v => form.setData('department', v ?? '')}>
                                    <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                    <SelectContent>{deptItems.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="arm-capacity">Most students</Label>
                                <Input id="arm-capacity" type="number" min={0} value={d.capacity} onChange={e => form.setData('capacity', e.target.value)} placeholder="40" />
                            </div>
                        </div>
                        {level && level !== 'senior' && d.department && (
                            <p className="text-xs text-amber-700 dark:text-amber-400">Departments are usually for senior classes. You can still use one here.</p>
                        )}
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">{editing ? 'Save' : 'Add'}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
