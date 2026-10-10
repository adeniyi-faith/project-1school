import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { EmptyState, PageHeader, Panel } from '@/components/app/kit';
import { ResultStatusPill } from '@/components/results/ResultStatus';
import { MessageSquareQuote, Pencil, Plus, Sparkles, Trash2 } from 'lucide-react';
import type { CommentBankEntry, ResultStatus, TermOption } from '@/Types';

interface Writer { value: string; label: string }
interface SheetRow { id: number; class_name: string; status: ResultStatus; students: number; labels: string[] }

interface Props {
    writers: Writer[];
    entries: CommentBankEntry[];
    terms: TermOption[];
    termId: number | null;
    sheets: SheetRow[];
    signerLabels: string[];
    blanks: string[];
}

interface EntryForm { writer_permission: string; min_average: string; max_average: string; comment: string }

const BANK_TITLE: Record<string, string> = {
    'marks.entry': "Teachers' comments",
    'results.publish': "Heads' comments (Principal, Head Teacher ...)",
};

export default function CommentBank({ writers, entries, terms, termId, sheets, signerLabels, blanks }: Props) {
    const [editing, setEditing] = useState<CommentBankEntry | null>(null);
    const [open, setOpen] = useState(false);
    const form = useForm<EntryForm>({ writer_permission: writers[0]?.value ?? '', min_average: '0', max_average: '100', comment: '' });

    function startAdd(writer: string) {
        setEditing(null);
        form.clearErrors();
        form.setData({ writer_permission: writer, min_average: '0', max_average: '100', comment: '' });
        setOpen(true);
    }

    function startEdit(entry: CommentBankEntry) {
        setEditing(entry);
        form.clearErrors();
        form.setData({ writer_permission: entry.writer_permission, min_average: String(Number(entry.min_average)), max_average: String(Number(entry.max_average)), comment: entry.comment });
        setOpen(true);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };
        if (editing) form.put(`/school/results/comment-bank/${editing.id}`, opts);
        else form.post('/school/results/comment-bank', opts);
    }

    function remove(entry: CommentBankEntry) {
        if (!window.confirm('Remove this comment from the bank? Comments already on report cards stay as they are.')) return;
        router.delete(`/school/results/comment-bank/${entry.id}`, { preserveScroll: true });
    }

    return (
        <AppLayout breadcrumbs={[{ label: 'Academic' }, { label: 'Term results', href: '/school/results' }, { label: 'Comment bank' }]}>
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader
                    title="Comment bank"
                    description="Save report card comments once and reuse them every term. Give each comment an average range, and the right one is filled in for each student."
                />

                {writers.length === 0 ? (
                    <Panel><EmptyState icon={MessageSquareQuote} title="Nothing to set up here" text="Only staff who write report card comments can use the comment bank." /></Panel>
                ) : (
                    <>
                        <p className="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:bg-white/[0.04] dark:text-slate-300">
                            Comments can include blanks that are filled in for each student:{' '}
                            {blanks.map(b => <code key={b} className="mx-0.5 rounded bg-white px-1 py-0.5 text-xs text-slate-800 ring-1 ring-slate-200 dark:bg-white/10 dark:text-slate-100 dark:ring-white/10">{b}</code>)}.
                            For example, "{'{name}'} did well. {'{he_she}'} should keep it up." prints as "Ada did well. She should keep it up."
                        </p>

                        {writers.map(w => {
                            const list = entries.filter(e => e.writer_permission === w.value);
                            return (
                                <Panel key={w.value} flush>
                                    <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4 dark:border-white/[0.06]">
                                        <div>
                                            <p className="font-medium text-slate-900 dark:text-white">{BANK_TITLE[w.value] ?? w.label}</p>
                                            <p className="text-xs text-slate-500">For comment boxes written by: {w.label.toLowerCase()}</p>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            {list.length === 0 && (
                                                <Button variant="outline" onClick={() => router.post('/school/results/comment-bank/starters', { writer_permission: w.value }, { preserveScroll: true })}>
                                                    <Sparkles className="size-4" /> Add ready-made comments
                                                </Button>
                                            )}
                                            <Button onClick={() => startAdd(w.value)} className="bg-indigo-600 text-white hover:bg-indigo-700"><Plus className="size-4" /> Add comment</Button>
                                        </div>
                                    </div>
                                    {list.length === 0 ? (
                                        <p className="px-5 py-6 text-sm text-slate-500">No saved comments yet. Add your own, or start with the ready-made ones and change them.</p>
                                    ) : (
                                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                                            {list.map(entry => (
                                                <li key={entry.id} className="flex items-start gap-3 px-5 py-3">
                                                    <span className="mt-0.5 w-24 shrink-0 text-xs font-medium tabular-nums text-slate-500">
                                                        {Number(entry.min_average)}% – {Number(entry.max_average)}%
                                                    </span>
                                                    <p className="flex-1 text-sm text-slate-800 dark:text-slate-200">{entry.comment}</p>
                                                    <div className="flex shrink-0 gap-1">
                                                        <Button variant="ghost" size="icon" aria-label="Change comment" onClick={() => startEdit(entry)}><Pencil className="size-4" /></Button>
                                                        <Button variant="ghost" size="icon" aria-label="Remove comment" onClick={() => remove(entry)}><Trash2 className="size-4 text-red-500" /></Button>
                                                    </div>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </Panel>
                            );
                        })}

                        <ApplyToClasses terms={terms} termId={termId} sheets={sheets} signerLabels={signerLabels} />
                    </>
                )}
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader><DialogTitle>{editing ? 'Change comment' : 'Add comment'}</DialogTitle></DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        {writers.length > 1 && (
                            <div className="space-y-1.5">
                                <Label>For</Label>
                                <Select items={writers} value={form.data.writer_permission} onValueChange={v => form.setData('writer_permission', v ?? '')}>
                                    <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                    <SelectContent>{writers.map(w => <SelectItem key={w.value} value={w.value}>{BANK_TITLE[w.value] ?? w.label}</SelectItem>)}</SelectContent>
                                </Select>
                            </div>
                        )}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="min">Average from (%)</Label>
                                <Input id="min" type="number" min={0} max={100} step="0.01" value={form.data.min_average} onChange={e => form.setData('min_average', e.target.value)} />
                                {form.errors.min_average && <p className="text-xs text-red-500">{form.errors.min_average}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="max">Average up to (%)</Label>
                                <Input id="max" type="number" min={0} max={100} step="0.01" value={form.data.max_average} onChange={e => form.setData('max_average', e.target.value)} />
                                {form.errors.max_average && <p className="text-xs text-red-500">{form.errors.max_average}</p>}
                            </div>
                        </div>
                        <p className="-mt-2 text-xs text-slate-500">Use 0 to 100 for a comment that suits any student.</p>
                        <div className="space-y-1.5">
                            <Label htmlFor="comment">Comment</Label>
                            <Textarea id="comment" rows={3} maxLength={600} value={form.data.comment} onChange={e => form.setData('comment', e.target.value)} placeholder="e.g. {name} is a hardworking pupil. Keep it up." />
                            {form.errors.comment && <p className="text-xs text-red-500">{form.errors.comment}</p>}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={form.processing || !form.data.comment.trim()} className="bg-indigo-600 text-white hover:bg-indigo-700">Save</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

interface ApplyForm { term_id: string; sheet_ids: number[]; signer_label: string; mode: 'bank' | 'same'; text: string; overwrite: boolean }

/** Write one signer's comments (usually the Principal's) for many classes at once */
function ApplyToClasses({ terms, termId, sheets, signerLabels }: { terms: TermOption[]; termId: number | null; sheets: SheetRow[]; signerLabels: string[] }) {
    const defaultLabel = signerLabels.find(l => !/teacher|form/i.test(l)) ?? signerLabels[0] ?? '';
    const form = useForm<ApplyForm>({ term_id: termId ? String(termId) : '', sheet_ids: [], signer_label: defaultLabel, mode: 'bank', text: '', overwrite: false });
    const termItems = terms.map(t => ({ value: String(t.id), label: t.is_current ? `${t.label} (current)` : t.label }));
    const labelItems = signerLabels.map(l => ({ value: l, label: l }));
    const label = form.data.signer_label.toLowerCase();
    const usable = sheets.filter(s => s.status !== 'locked' && s.students > 0 && s.labels.some(l => l.toLowerCase() === label));
    const allChosen = usable.length > 0 && usable.every(s => form.data.sheet_ids.includes(s.id));

    function toggle(id: number, on: boolean) {
        form.setData('sheet_ids', on ? [...form.data.sheet_ids, id] : form.data.sheet_ids.filter(x => x !== id));
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (form.data.overwrite && !window.confirm(`Replace the ${form.data.signer_label}'s comments already written in the chosen classes?`)) return;
        form.post('/school/results/comment-bank/apply', { preserveScroll: true });
    }

    return (
        <Panel>
            <form onSubmit={submit} className="space-y-5">
                <div>
                    <p className="font-medium text-slate-900 dark:text-white">Write once for many classes</p>
                    <p className="mt-1 text-sm text-slate-500">
                        Fill one person's comments, for example the Principal's, on every class you choose. Each student gets the saved comment that fits their average, or one comment you write for everyone. You can still change any single comment on a class's report cards page.
                    </p>
                </div>

                {terms.length === 0 ? (
                    <p className="text-sm text-slate-500">Add a school year on the Terms page first.</p>
                ) : (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label>Term</Label>
                                <Select items={termItems} value={form.data.term_id} onValueChange={v => v && router.get('/school/results/comment-bank', { term_id: v }, { preserveScroll: true })}>
                                    <SelectTrigger className="w-full"><SelectValue placeholder="Choose a term" /></SelectTrigger>
                                    <SelectContent>{termItems.map(t => <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>)}</SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label>Whose comment</Label>
                                <Select items={labelItems} value={form.data.signer_label} onValueChange={v => form.setData({ ...form.data, signer_label: v ?? '', sheet_ids: [] })}>
                                    <SelectTrigger className="w-full"><SelectValue placeholder="Choose" /></SelectTrigger>
                                    <SelectContent>{labelItems.map(l => <SelectItem key={l.value} value={l.value}>{l.label}</SelectItem>)}</SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label>What to write</Label>
                            <div className="flex flex-wrap gap-4 text-sm">
                                <label className="flex items-center gap-2">
                                    <input type="radio" name="mode" checked={form.data.mode === 'bank'} onChange={() => form.setData('mode', 'bank')} />
                                    A saved comment that fits each student's average
                                </label>
                                <label className="flex items-center gap-2">
                                    <input type="radio" name="mode" checked={form.data.mode === 'same'} onChange={() => form.setData('mode', 'same')} />
                                    The same comment for everyone
                                </label>
                            </div>
                            {form.data.mode === 'same' && (
                                <Textarea rows={2} maxLength={600} value={form.data.text} onChange={e => form.setData('text', e.target.value)} placeholder="e.g. Wishing {name} a restful holiday. Resume on time." />
                            )}
                            {form.errors.text && <p className="text-xs text-red-500">{form.errors.text}</p>}
                        </div>

                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <Label>Classes</Label>
                                {usable.length > 0 && (
                                    <button type="button" className="text-xs text-indigo-600 hover:underline dark:text-indigo-400" onClick={() => form.setData('sheet_ids', allChosen ? [] : usable.map(s => s.id))}>
                                        {allChosen ? 'Clear all' : 'Choose all'}
                                    </button>
                                )}
                            </div>
                            {sheets.length === 0 ? (
                                <p className="text-sm text-slate-500">No classes have results for this term yet.</p>
                            ) : (
                                <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    {sheets.map(s => {
                                        const can = usable.some(u => u.id === s.id);
                                        const why = s.status === 'locked' ? 'Locked' : s.students === 0 ? 'No results yet' : `No ${form.data.signer_label} box`;
                                        return (
                                            <label key={s.id} className={`flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-white/10 ${can ? '' : 'opacity-50'}`}>
                                                <Checkbox checked={form.data.sheet_ids.includes(s.id)} disabled={!can} onCheckedChange={v => toggle(s.id, !!v)} />
                                                <span className="flex-1 truncate">{s.class_name}</span>
                                                {can ? <ResultStatusPill status={s.status} /> : <span className="text-xs text-slate-500">{why}</span>}
                                            </label>
                                        );
                                    })}
                                </div>
                            )}
                            {form.errors.sheet_ids && <p className="text-xs text-red-500">{form.errors.sheet_ids}</p>}
                        </div>

                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox checked={form.data.overwrite} onCheckedChange={v => form.setData('overwrite', !!v)} />
                            Replace comments already written (otherwise only empty ones are filled)
                        </label>

                        <div className="flex justify-end">
                            <Button type="submit" disabled={form.processing || form.data.sheet_ids.length === 0 || !form.data.signer_label || (form.data.mode === 'same' && !form.data.text.trim())} className="bg-indigo-600 text-white hover:bg-indigo-700">
                                Write comments for {form.data.sheet_ids.length} {form.data.sheet_ids.length === 1 ? 'class' : 'classes'}
                            </Button>
                        </div>
                    </>
                )}
            </form>
        </Panel>
    );
}
