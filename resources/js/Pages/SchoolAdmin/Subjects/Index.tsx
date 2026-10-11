import { useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { BookOpen, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { EmptyState, PageHeader, Panel, Pill } from '@/components/app/kit';
import type { PageProps } from '@/Types';
import { useCan } from '@/lib/can';

interface ClassLine { id: number; departments: string[] }
interface SubjectRow { id: number; name: string; code: string | null; type: 'theory' | 'practical'; full_marks: number; pass_marks: number; classes: ClassLine[] }
interface ClassOption { id: number; name: string; level: string | null }

interface Props extends PageProps {
    subjects: SubjectRow[];
    classes: ClassOption[];
    levels: Record<string, string>;
    departments: string[];
}

interface FormData { name: string; code: string; type: 'theory' | 'practical'; full_marks: string; pass_marks: string; classes: ClassLine[] }

const empty: FormData = { name: '', code: '', type: 'theory', full_marks: '100', pass_marks: '40', classes: [] };
const typeItems = [{ value: 'theory', label: 'Written (theory)' }, { value: 'practical', label: 'Practical' }];

/** One list of subjects for the whole school; each subject is ticked for the classes that offer it */
export default function SubjectsIndex({ subjects, classes, levels, departments }: Props) {
    const can = useCan();
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<SubjectRow | null>(null);
    const [search, setSearch] = useState('');
    const [classFilter, setClassFilter] = useState('');
    const form = useForm<FormData>(empty);
    const d = form.data;

    const className = useMemo(() => new Map(classes.map(c => [c.id, c.name])), [classes]);
    const groups = useMemo(() => {
        const order = [...Object.keys(levels), ''];
        return order.map(level => ({ level, label: levels[level] ?? 'Other classes', classes: classes.filter(c => (c.level ?? '') === level) }))
            .filter(g => g.classes.length > 0);
    }, [classes, levels]);

    const shown = subjects.filter(s =>
        (!search || s.name.toLowerCase().includes(search.toLowerCase()) || (s.code ?? '').toLowerCase().includes(search.toLowerCase()))
        && (!classFilter || s.classes.some(c => String(c.id) === classFilter)));

    function openCreate() {
        form.clearErrors();
        form.setData({ ...empty, classes: classFilter ? [{ id: Number(classFilter), departments: [] }] : [] });
        setEditing(null);
        setOpen(true);
    }

    function openEdit(s: SubjectRow) {
        form.clearErrors();
        form.setData({ name: s.name, code: s.code ?? '', type: s.type, full_marks: String(s.full_marks), pass_marks: String(s.pass_marks), classes: s.classes });
        setEditing(s);
        setOpen(true);
    }

    const ticked = (id: number) => d.classes.some(c => c.id === id);
    function toggle(ids: number[], on: boolean) {
        const rest = d.classes.filter(c => !ids.includes(c.id));
        form.setData('classes', on ? [...rest, ...ids.map(id => d.classes.find(c => c.id === id) ?? { id, departments: [] })] : rest);
    }
    function toggleDepartment(classId: number, dept: string, on: boolean) {
        form.setData('classes', d.classes.map(c => c.id !== classId ? c
            : { ...c, departments: on ? [...c.departments, dept] : c.departments.filter(x => x !== dept) }));
    }
    /** The same departments for every ticked senior class */
    function departmentForLevel(level: string, dept: string, on: boolean) {
        const ids = classes.filter(c => c.level === level).map(c => c.id);
        form.setData('classes', d.classes.map(c => !ids.includes(c.id) ? c
            : { ...c, departments: on ? [...c.departments.filter(x => x !== dept), dept] : c.departments.filter(x => x !== dept) }));
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };
        form.transform(data => ({ ...data, full_marks: data.full_marks || null, pass_marks: data.pass_marks || null, code: data.code || null }));
        if (editing) form.put(`/school/subjects/${editing.id}`, options);
        else form.post('/school/subjects', options);
    }

    function destroy(s: SubjectRow) {
        if (confirm(`Delete "${s.name}"? It is removed from every class. Scores and results already given in it are kept.`)) {
            router.delete(`/school/subjects/${s.id}`, { preserveScroll: true });
        }
    }

    const canEdit = can('settings.edit');
    const actions = canEdit ? (
        <Button onClick={openCreate} className="bg-indigo-600 text-white hover:bg-indigo-700">
            <Plus className="size-4" /> Add subject
        </Button>
    ) : null;
    const classItems = [{ value: '', label: 'All classes' }, ...classes.map(c => ({ value: String(c.id), label: c.name }))];

    return (
        <AppLayout breadcrumbs={[{ label: 'School Setup' }, { label: 'Subjects' }]}>
            <Head title="Subjects" />
            <div className="mx-auto max-w-5xl space-y-5 pb-24 md:pb-0">
                <PageHeader title="Subjects"
                    description="Each subject is made once and ticked for every class that offers it, so Mathematics is one subject from Primary 1 to SS 3. In senior classes a subject can be kept to some departments, such as Physics for Science arms."
                    actions={actions} />
                <div className="flex flex-wrap gap-2 md:hidden">{actions}</div>

                <div className="grid gap-2 sm:grid-cols-2">
                    <div className="relative">
                        <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                        <Input value={search} onChange={e => setSearch(e.target.value)} placeholder="Find a subject" className="pl-9" aria-label="Find a subject" />
                    </div>
                    <Select items={classItems} value={classFilter} onValueChange={v => setClassFilter(v ?? '')}>
                        <SelectTrigger className="w-full" aria-label="Class"><SelectValue /></SelectTrigger>
                        <SelectContent>{classItems.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                    </Select>
                </div>

                {shown.length === 0 ? (
                    <Panel>
                        <EmptyState icon={BookOpen} title={subjects.length === 0 ? 'No subjects yet' : 'No subject matches'}
                            text={subjects.length === 0 ? 'Add your first subject and tick the classes that offer it.' : 'Try another name or class.'} />
                    </Panel>
                ) : (
                    <Panel flush>
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {shown.map(s => (
                                <li key={s.id} className="flex flex-wrap items-start gap-3 px-4 py-3 sm:px-5">
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="font-medium text-slate-900 dark:text-white">{s.name}</span>
                                            {s.code && <span className="text-xs text-slate-400">{s.code}</span>}
                                            {s.type === 'practical' && <Pill tone="info">Practical</Pill>}
                                            <span className="text-xs text-slate-400">Pass {s.pass_marks} of {s.full_marks}</span>
                                        </div>
                                        <div className="mt-1.5 flex flex-wrap gap-1.5">
                                            {s.classes.length === 0 ? <Pill tone="warn">No class offers it yet</Pill> : s.classes
                                                .slice().sort((a, b) => classes.findIndex(c => c.id === a.id) - classes.findIndex(c => c.id === b.id))
                                                .map(c => (
                                                    <span key={c.id} className="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-600 dark:bg-white/[0.06] dark:text-slate-300">
                                                        {className.get(c.id)}{c.departments.length > 0 && <span className="text-slate-400"> · {c.departments.join(', ')}</span>}
                                                    </span>
                                                ))}
                                        </div>
                                    </div>
                                    <div className={canEdit ? 'flex gap-1' : 'hidden'}>
                                        <Button variant="ghost" size="sm" onClick={() => openEdit(s)} aria-label={`Change ${s.name}`}><Pencil className="size-4" /></Button>
                                        <Button variant="ghost" size="sm" onClick={() => destroy(s)} aria-label={`Delete ${s.name}`} className="text-red-600 hover:text-red-700"><Trash2 className="size-4" /></Button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </Panel>
                )}
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                    <DialogHeader><DialogTitle>{editing ? `Change ${editing.name}` : 'Add a subject'}</DialogTitle></DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid gap-3 sm:grid-cols-[2fr_1fr]">
                            <div className="space-y-1.5">
                                <Label htmlFor="subject-name">Name</Label>
                                <Input id="subject-name" value={d.name} onChange={e => form.setData('name', e.target.value)} placeholder="Mathematics" />
                                {form.errors.name && <p className="text-xs text-red-600">{form.errors.name}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="subject-code">Short code</Label>
                                <Input id="subject-code" value={d.code} onChange={e => form.setData('code', e.target.value)} placeholder="MTH" />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <div className="col-span-2 space-y-1.5 sm:col-span-1">
                                <Label>Kind</Label>
                                <Select items={typeItems} value={d.type} onValueChange={v => form.setData('type', (v ?? 'theory') as FormData['type'])}>
                                    <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                    <SelectContent>{typeItems.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="full-marks">Full marks</Label>
                                <Input id="full-marks" type="number" min={1} value={d.full_marks} onChange={e => form.setData('full_marks', e.target.value)} />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="pass-marks">Pass mark</Label>
                                <Input id="pass-marks" type="number" min={1} value={d.pass_marks} onChange={e => form.setData('pass_marks', e.target.value)} />
                                {form.errors.pass_marks && <p className="text-xs text-red-600">{form.errors.pass_marks}</p>}
                            </div>
                        </div>

                        <div className="space-y-3">
                            <div>
                                <Label>Classes that offer it</Label>
                                <p className="text-xs text-slate-500">Tick a whole level at once, or single classes. {d.classes.length} ticked.</p>
                            </div>
                            {classes.length === 0 && <p className="text-sm text-slate-500">Add classes first, under School Setup, Classes.</p>}
                            {groups.map(g => {
                                const ids = g.classes.map(c => c.id);
                                const all = ids.every(ticked);
                                const senior = g.level === 'senior';
                                return (
                                    <div key={g.level || 'other'} className="rounded-lg border border-slate-200 p-3 dark:border-white/10">
                                        <label className="flex items-center gap-2 text-sm font-medium text-slate-800 dark:text-slate-100">
                                            <Checkbox checked={all} indeterminate={!all && ids.some(ticked)} onCheckedChange={v => toggle(ids, !!v)} />
                                            {g.label}
                                        </label>
                                        <div className="mt-2 grid grid-cols-2 gap-x-3 gap-y-2 pl-6 sm:grid-cols-3">
                                            {g.classes.map(c => (
                                                <label key={c.id} className="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                                    <Checkbox checked={ticked(c.id)} onCheckedChange={v => toggle([c.id], !!v)} />
                                                    {c.name}
                                                </label>
                                            ))}
                                        </div>
                                        {senior && ids.some(ticked) && departments.length > 0 && (
                                            <div className="mt-3 space-y-2 border-t border-slate-100 pt-3 pl-6 dark:border-white/[0.06]">
                                                <p className="text-xs text-slate-500">Only for some departments? Leave all unticked if every student takes it.</p>
                                                <div className="flex flex-wrap gap-x-4 gap-y-2">
                                                    {departments.map(dept => {
                                                        const on = d.classes.filter(c => ids.includes(c.id)).every(c => c.departments.includes(dept));
                                                        return (
                                                            <label key={dept} className="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                                                <Checkbox checked={on} onCheckedChange={v => departmentForLevel(g.level, dept, !!v)} />
                                                                {dept}
                                                            </label>
                                                        );
                                                    })}
                                                </div>
                                                {g.classes.filter(c => ticked(c.id)).length > 1 && (
                                                    <details className="text-xs text-slate-500">
                                                        <summary className="cursor-pointer">Different departments for each class</summary>
                                                        <div className="mt-2 space-y-2">
                                                            {g.classes.filter(c => ticked(c.id)).map(c => (
                                                                <div key={c.id} className="flex flex-wrap items-center gap-x-3 gap-y-1">
                                                                    <span className="w-20 font-medium text-slate-700 dark:text-slate-300">{c.name}</span>
                                                                    {departments.map(dept => (
                                                                        <label key={dept} className="flex items-center gap-1.5">
                                                                            <Checkbox checked={!!d.classes.find(x => x.id === c.id)?.departments.includes(dept)} onCheckedChange={v => toggleDepartment(c.id, dept, !!v)} />
                                                                            {dept}
                                                                        </label>
                                                                    ))}
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </details>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                            {form.errors.classes && <p className="text-xs text-red-600">{form.errors.classes}</p>}
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">{editing ? 'Save' : 'Add subject'}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
