import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { PageHeader, Panel, Pill } from '@/components/app/kit';
import LessonTabs from '@/components/lesson-notes/LessonTabs';
import { ArrowDown, ArrowUp, Pencil, Plus, Trash2 } from 'lucide-react';
import type { LessonNoteFormat } from '@/Types';

interface Props {
    formats: LessonNoteFormat[];
    canManage: boolean;
}

type Row = { label: string; hint: string };

/** The headings every lesson note is written under. Heads choose them; teachers pick one per note. */
export default function Formats({ formats, canManage }: Props) {
    const [editing, setEditing] = useState<LessonNoteFormat | 'new' | null>(null);
    const form = useForm<{ name: string; sections: Row[]; is_default: boolean }>({ name: '', sections: [], is_default: false });
    const d = form.data;

    function open(f: LessonNoteFormat | 'new') {
        form.clearErrors();
        if (f === 'new') {
            const base = formats.find(x => x.is_default) ?? formats[0];
            form.setData({ name: '', sections: base ? base.sections.map(s => ({ label: s.label, hint: s.hint ?? '' })) : [{ label: '', hint: '' }], is_default: false });
        } else {
            form.setData({ name: f.name, sections: f.sections.map(s => ({ label: s.label, hint: s.hint ?? '' })), is_default: f.is_default });
        }
        setEditing(f);
    }

    function setRow(i: number, patch: Partial<Row>) {
        form.setData('sections', d.sections.map((r, n) => (n === i ? { ...r, ...patch } : r)));
    }

    function move(i: number, by: number) {
        const rows = [...d.sections];
        const j = i + by;
        if (j < 0 || j >= rows.length) return;
        [rows[i], rows[j]] = [rows[j], rows[i]];
        form.setData('sections', rows);
    }

    function save(e: React.FormEvent) {
        e.preventDefault();
        const done = { preserveScroll: true, onSuccess: () => setEditing(null) };
        if (editing === 'new') form.post('/school/lesson-notes/formats', done);
        else if (editing) form.put(`/school/lesson-notes/formats/${editing.id}`, done);
    }

    function remove(f: LessonNoteFormat) {
        if (confirm(`Delete the format "${f.name}"?`)) router.delete(`/school/lesson-notes/formats/${f.id}`, { preserveScroll: true });
    }

    const firstError = Object.entries(form.errors).find(([k]) => k !== 'name')?.[1];

    const actions = canManage ? <Button onClick={() => open('new')} className="bg-indigo-600 text-white hover:bg-indigo-700"><Plus className="size-4" /> New format</Button> : undefined;

    return (
        <AppLayout breadcrumbs={[{ label: 'Lesson notes', href: '/school/lesson-notes' }, { label: 'Formats' }]}>
            <div className="mx-auto max-w-5xl space-y-5 pb-24 md:pb-0">
                <PageHeader title="Lesson note formats" description="The headings a lesson note is written under. The default format is used for new notes; teachers can pick another. The AI writes under the same headings." actions={actions} />
                {actions && <div className="md:hidden">{actions}</div>}
                <LessonTabs current="formats" />

                <div className="grid gap-3 md:grid-cols-2">
                    {formats.map(f => (
                        <Panel key={f.id} title={f.name} description={f.notes ? `${f.notes} lesson note${f.notes === 1 ? '' : 's'} written in it` : 'Not used yet'}>
                            <div className="space-y-3">
                                {f.is_default && <Pill tone="info">Default</Pill>}
                                <ol className="list-decimal space-y-0.5 pl-5 text-sm text-slate-700 dark:text-slate-300">
                                    {f.sections.map(s => <li key={s.key}>{s.label}</li>)}
                                </ol>
                                {canManage && (
                                    <div className="flex flex-wrap gap-2">
                                        <Button variant="outline" size="sm" onClick={() => open(f)}><Pencil className="size-4" /> Change</Button>
                                        {!f.is_default && !f.notes && <Button variant="outline" size="sm" onClick={() => remove(f)} className="text-red-600"><Trash2 className="size-4" /> Delete</Button>}
                                    </div>
                                )}
                            </div>
                        </Panel>
                    ))}
                </div>
                {!canManage && <p className="text-sm text-slate-500">Only a principal or admin can change the formats.</p>}

                <Dialog open={editing !== null} onOpenChange={o => !o && setEditing(null)}>
                    <DialogContent className="max-h-[90dvh] overflow-y-auto sm:max-w-2xl">
                        <DialogHeader><DialogTitle>{editing === 'new' ? 'New format' : 'Change format'}</DialogTitle></DialogHeader>
                        <form onSubmit={save} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="fname">Name</Label>
                                <Input id="fname" value={d.name} onChange={e => form.setData('name', e.target.value)} maxLength={120} placeholder="e.g. Our school's lesson note" />
                                {form.errors.name && <p className="text-xs text-red-500">{form.errors.name}</p>}
                            </div>
                            <div className="space-y-2">
                                <p className="text-sm font-medium">Headings, in order</p>
                                <p className="text-xs text-slate-500">The hint shows inside the empty box and tells the AI what to write there.</p>
                                {d.sections.map((r, i) => (
                                    <div key={i} className="grid gap-2 rounded-lg border border-slate-200 p-2.5 dark:border-white/10 sm:grid-cols-[1fr_1.4fr_auto]">
                                        <Input aria-label={`Heading ${i + 1}`} value={r.label} onChange={e => setRow(i, { label: e.target.value })} maxLength={80} placeholder="Heading" />
                                        <Input aria-label={`Hint for heading ${i + 1}`} value={r.hint} onChange={e => setRow(i, { hint: e.target.value })} maxLength={255} placeholder="Hint (optional)" />
                                        <div className="flex gap-1">
                                            <Button type="button" variant="ghost" size="icon" onClick={() => move(i, -1)} disabled={i === 0} aria-label="Move up"><ArrowUp className="size-4" /></Button>
                                            <Button type="button" variant="ghost" size="icon" onClick={() => move(i, 1)} disabled={i === d.sections.length - 1} aria-label="Move down"><ArrowDown className="size-4" /></Button>
                                            <Button type="button" variant="ghost" size="icon" onClick={() => form.setData('sections', d.sections.filter((_, n) => n !== i))} aria-label="Remove heading"><Trash2 className="size-4" /></Button>
                                        </div>
                                    </div>
                                ))}
                                {d.sections.length < 15 && <Button type="button" variant="outline" size="sm" onClick={() => form.setData('sections', [...d.sections, { label: '', hint: '' }])}><Plus className="size-4" /> Add a heading</Button>}
                                {firstError && <p className="text-xs text-red-500">{firstError}</p>}
                            </div>
                            <label className="flex items-center gap-2 text-sm">
                                <input type="checkbox" className="size-4 accent-indigo-600" checked={d.is_default} onChange={e => form.setData('is_default', e.target.checked)} /> Use this format for new lesson notes
                            </label>
                            {editing !== 'new' && editing?.notes ? <p className="text-xs text-slate-500">Notes already written keep their text. A heading you rename starts empty in them; its old text still shows and prints.</p> : null}
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setEditing(null)}>Cancel</Button>
                                <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Save format</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
