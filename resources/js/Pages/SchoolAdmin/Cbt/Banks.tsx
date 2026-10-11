import { useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { EmptyState, PageHeader, Panel } from '@/components/app/kit';
import { Library, Plus, MonitorCheck, Printer } from 'lucide-react';
import type { CbtBankRow } from '@/Types';

interface Props {
    banks: CbtBankRow[];
    subjects: { id: number; name: string }[];
    canManage: boolean;
}

export default function CbtBanks({ banks, subjects, canManage }: Props) {
    const [open, setOpen] = useState(false);
    const form = useForm({ name: '', subject_id: '', description: '' });
    const subjectItems = [{ value: '', label: 'No subject' }, ...subjects.map(s => ({ value: String(s.id), label: s.name }))];

    function create(e: React.FormEvent) {
        e.preventDefault();
        form.transform(d => ({ ...d, subject_id: d.subject_id || null }));
        form.post('/school/cbt/banks', { onSuccess: () => setOpen(false) });
    }

    const actions = (
        <div className="flex flex-wrap gap-2">
            <Link href="/school/cbt/tests" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                <MonitorCheck className="size-4" /> Tests
            </Link>
            <Link href="/school/cbt/papers" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                <Printer className="size-4" /> Exam papers
            </Link>
            {canManage && <Button onClick={() => setOpen(true)} className="bg-indigo-600 text-white hover:bg-indigo-700"><Plus className="size-4" /> New question bank</Button>}
        </div>
    );

    return (
        <AppLayout breadcrumbs={[{ label: 'CBT' }, { label: 'Question banks' }]}>
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader title="Question banks" description="Keep questions for each subject here, sorted by topic and difficulty. Tests draw their questions from these banks, so every student can get a different paper." actions={actions} />
                <div className="flex flex-wrap gap-2 md:hidden">{actions}</div>

                {banks.length === 0 ? (
                    <Panel><EmptyState icon={Library} title="No question banks yet" text="Create one for each subject, for example “JSS 1 Mathematics” or “Entrance English”." /></Panel>
                ) : (
                    <div className="grid gap-3 sm:grid-cols-2">
                        {banks.map(b => (
                            <Link key={b.id} href={`/school/cbt/banks/${b.id}`} className="rounded-xl border border-slate-200 bg-white p-4 shadow-xs hover:border-indigo-300 dark:border-white/[0.08] dark:bg-slate-900">
                                <p className="font-semibold text-slate-900 dark:text-white">{b.name}</p>
                                <p className="text-sm text-slate-500">{b.subject ?? 'Not linked to a subject'}</p>
                                <p className="mt-3 text-2xl font-semibold tabular-nums text-slate-900 dark:text-white">{b.questions} <span className="text-sm font-normal text-slate-500">questions</span></p>
                                <p className="mt-1 text-xs text-slate-500">{b.levels.easy} easy · {b.levels.medium} medium · {b.levels.hard} hard{b.theory ? ` · ${b.theory} theory` : ''}</p>
                            </Link>
                        ))}
                    </div>
                )}

                <Dialog open={open} onOpenChange={setOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader><DialogTitle>New question bank</DialogTitle></DialogHeader>
                        <form onSubmit={create} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="name">Name</Label>
                                <Input id="name" value={form.data.name} onChange={e => form.setData('name', e.target.value)} placeholder="e.g. JSS 1 Mathematics" maxLength={120} />
                                {form.errors.name && <p className="text-xs text-red-500">{form.errors.name}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label>Subject (optional)</Label>
                                <Select items={subjectItems} value={form.data.subject_id} onValueChange={v => form.setData('subject_id', v ?? '')}>
                                    <SelectTrigger className="w-full"><SelectValue placeholder="No subject" /></SelectTrigger>
                                    <SelectContent>{subjectItems.map(s => <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>)}</SelectContent>
                                </Select>
                                <p className="text-xs text-slate-500">Leave empty for banks like entrance exam questions.</p>
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="description">Notes (optional)</Label>
                                <Textarea id="description" rows={2} value={form.data.description} onChange={e => form.setData('description', e.target.value)} maxLength={500} />
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                                <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Create</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
