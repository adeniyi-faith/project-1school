import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { EmptyState, PageHeader, Panel, Pill } from '@/components/app/kit';
import { CalendarRange, Pencil, Plus } from 'lucide-react';
import type { AcademicYearWithTerms, Term } from '@/Types';

interface Props {
    years: AcademicYearWithTerms[];
    canEdit: boolean;
}

function formatDate(d: string | null) {
    return d ? new Date(d).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' }) : null;
}

export default function Terms({ years, canEdit }: Props) {
    const [yearOpen, setYearOpen] = useState(false);
    const [editing, setEditing] = useState<Term | null>(null);

    const yearForm = useForm({ name: '', start_date: '', end_date: '', make_current: false });
    const termForm = useForm({ name: '', start_date: '', end_date: '' });

    function openTerm(t: Term) {
        termForm.setData({ name: t.name, start_date: t.start_date ?? '', end_date: t.end_date ?? '' });
        termForm.clearErrors();
        setEditing(t);
    }

    function saveYear(e: React.FormEvent) {
        e.preventDefault();
        yearForm.post('/school/academics/years', { onSuccess: () => { setYearOpen(false); yearForm.reset(); } });
    }

    function saveTerm(e: React.FormEvent) {
        e.preventDefault();
        if (!editing) return;
        termForm.put(`/school/academics/terms/${editing.id}`, { onSuccess: () => setEditing(null) });
    }

    function makeCurrent(t: Term) {
        router.post(`/school/academics/terms/${t.id}/current`, {}, { preserveScroll: true });
    }

    const addYearButton = canEdit && (
        <Button onClick={() => setYearOpen(true)} className="inline-flex items-center gap-2 bg-indigo-600 text-white hover:bg-indigo-700">
            <Plus className="size-4" /> Add school year
        </Button>
    );

    return (
        <AppLayout breadcrumbs={[{ label: 'Academic' }, { label: 'School years & terms' }]}>
            <div className="mx-auto max-w-4xl space-y-6 pb-24 md:pb-0">
                <PageHeader
                    title="School years & terms"
                    description="Each school year is split into terms. Exams and results belong to a term."
                    actions={addYearButton}
                />
                {canEdit && <div className="md:hidden">{addYearButton}</div>}

                {years.length === 0 && (
                    <Panel>
                        <EmptyState icon={CalendarRange} title="No school years yet" text="Add your first school year. Its terms are created for you." />
                    </Panel>
                )}

                {years.map(year => (
                    <Panel
                        key={year.id}
                        title={<span className="inline-flex items-center gap-2">{year.name} {year.is_current && <Pill tone="good">Current year</Pill>}</span>}
                        description={year.start_date ? `${formatDate(year.start_date)} – ${formatDate(year.end_date)}` : undefined}
                        flush
                    >
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {year.terms.map(term => (
                                <li key={term.id} className="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                                    <div className="min-w-0">
                                        <p className="flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-white">
                                            {term.name} {term.is_current && <Pill tone="info">Current term</Pill>}
                                        </p>
                                        <p className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                            {term.start_date ? `${formatDate(term.start_date)} – ${formatDate(term.end_date) ?? '…'}` : 'Dates not set yet'}
                                        </p>
                                    </div>
                                    {canEdit && (
                                        <div className="flex items-center gap-2">
                                            {!term.is_current && (
                                                <Button variant="outline" size="sm" onClick={() => makeCurrent(term)}>Make current</Button>
                                            )}
                                            <Button variant="ghost" size="sm" onClick={() => openTerm(term)} className="inline-flex items-center gap-1.5">
                                                <Pencil className="size-3.5" /> Edit
                                            </Button>
                                        </div>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </Panel>
                ))}
            </div>

            <Dialog open={yearOpen} onOpenChange={setYearOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Add school year</DialogTitle></DialogHeader>
                    <form onSubmit={saveYear} className="mt-2 space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="year-name">Name</Label>
                            <Input id="year-name" value={yearForm.data.name} onChange={e => yearForm.setData('name', e.target.value)} placeholder="e.g. 2027/2028" />
                            {yearForm.errors.name && <p className="text-xs text-red-500">{yearForm.errors.name}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="year-start">Starts</Label>
                                <Input id="year-start" type="date" value={yearForm.data.start_date} onChange={e => yearForm.setData('start_date', e.target.value)} />
                                {yearForm.errors.start_date && <p className="text-xs text-red-500">{yearForm.errors.start_date}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="year-end">Ends</Label>
                                <Input id="year-end" type="date" value={yearForm.data.end_date} onChange={e => yearForm.setData('end_date', e.target.value)} />
                                {yearForm.errors.end_date && <p className="text-xs text-red-500">{yearForm.errors.end_date}</p>}
                            </div>
                        </div>
                        <label className="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                            <input type="checkbox" checked={yearForm.data.make_current} onChange={e => yearForm.setData('make_current', e.target.checked)} className="size-4" />
                            Make this the current year (its First Term becomes the current term)
                        </label>
                        <p className="text-xs text-slate-500 dark:text-slate-400">
                            The terms are created for you, using the number of terms per year in Settings (three if not set). You can add their dates afterwards.
                        </p>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setYearOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={yearForm.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">
                                {yearForm.processing ? 'Saving…' : 'Add year'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={editing !== null} onOpenChange={o => { if (!o) setEditing(null); }}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Edit term</DialogTitle></DialogHeader>
                    <form onSubmit={saveTerm} className="mt-2 space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="term-name">Name</Label>
                            <Input id="term-name" value={termForm.data.name} onChange={e => termForm.setData('name', e.target.value)} />
                            {termForm.errors.name && <p className="text-xs text-red-500">{termForm.errors.name}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="term-start">Starts</Label>
                                <Input id="term-start" type="date" value={termForm.data.start_date} onChange={e => termForm.setData('start_date', e.target.value)} />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="term-end">Ends</Label>
                                <Input id="term-end" type="date" value={termForm.data.end_date} onChange={e => termForm.setData('end_date', e.target.value)} />
                                {termForm.errors.end_date && <p className="text-xs text-red-500">{termForm.errors.end_date}</p>}
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setEditing(null)}>Cancel</Button>
                            <Button type="submit" disabled={termForm.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">
                                {termForm.processing ? 'Saving…' : 'Save'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
