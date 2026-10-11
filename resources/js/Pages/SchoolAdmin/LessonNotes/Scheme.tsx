import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageHeader, Panel, Pill } from '@/components/app/kit';
import { statusLabel } from '@/components/lesson-notes/LessonTabs';
import { ArrowLeft, Download, FileUp, Loader2, NotebookPen, Plus, Sparkles, Trash2, X } from 'lucide-react';
import type { AiAvailability, LessonChoices, SchemeWeek } from '@/Types';

interface Props extends LessonChoices {
    scheme: {
        id: number; class_id: number; subject_id: number; term_id: number | null; title: string;
        weeks: SchemeWeek[]; source_name: string | null;
        notes: Record<string, { id: number; status: keyof typeof statusLabel }[]>;
    } | null;
    currentTerm: number | null;
    canManage: boolean;
    ai: AiAvailability;
}

/** Add or change one scheme of work: upload or paste it, let it be read into weeks, check them, save */
export default function Scheme({ scheme, classes, subjects, terms, currentTerm, canManage, ai }: Props) {
    const aiReady = ai.shown && !ai.reason;
    const form = useForm<{ class_id: string; subject_id: string; term_id: string; title: string; weeks: SchemeWeek[]; file: File | null }>({
        class_id: scheme ? String(scheme.class_id) : '',
        subject_id: scheme ? String(scheme.subject_id) : '',
        term_id: scheme ? (scheme.term_id ? String(scheme.term_id) : '') : (currentTerm ? String(currentTerm) : ''),
        title: scheme?.title ?? '',
        weeks: scheme?.weeks ?? [],
        file: null,
    });
    const d = form.data;
    const [text, setText] = useState('');
    const [useAi, setUseAi] = useState(aiReady);
    const [reading, setReading] = useState(false);
    const [readNote, setReadNote] = useState<{ tone: 'good' | 'bad'; text: string } | null>(null);
    const editable = canManage;

    const classItems = classes.map(c => ({ value: String(c.id), label: c.name }));
    const subjectItems = subjects.filter(s => !d.class_id || !s.class_id || String(s.class_id) === d.class_id).map(s => ({ value: String(s.id), label: s.name }));
    const termItems = [{ value: '', label: 'No term' }, ...terms.map(t => ({ value: String(t.id), label: t.name }))];
    const isPdf = d.file?.name.toLowerCase().endsWith('.pdf') ?? false;

    async function read() {
        if (d.weeks.length > 0 && !confirm('The weeks below will be replaced by what is read. Continue?')) return;
        setReading(true);
        setReadNote(null);
        const body = new FormData();
        if (d.file) body.append('file', d.file);
        else body.append('text', text);
        body.append('use_ai', useAi ? '1' : '0');
        body.append('class_id', d.class_id);
        body.append('subject_id', d.subject_id);
        if (d.term_id) body.append('term_id', d.term_id);
        try {
            const { data } = await axios.post<{ weeks: SchemeWeek[] }>('/school/lesson-notes/schemes/read', body);
            form.setData('weeks', data.weeks);
            setReadNote({ tone: 'good', text: `${data.weeks.length} week${data.weeks.length === 1 ? '' : 's'} found. Check each one below and correct anything that is wrong, then save.` });
        } catch (e) {
            const errs = axios.isAxiosError(e) ? e.response?.data?.errors : null;
            const message = errs ? (errs.source?.[0] ?? errs.ai?.[0] ?? errs.file?.[0] ?? errs.class_id?.[0] ?? errs.subject_id?.[0]) : null;
            setReadNote({ tone: 'bad', text: message || 'The scheme could not be read just now. Try again.' });
        } finally {
            setReading(false);
        }
    }

    function setWeek(i: number, patch: Partial<SchemeWeek>) {
        form.setData('weeks', d.weeks.map((w, n) => (n === i ? { ...w, ...patch } : w)));
    }

    function addWeek() {
        const next = d.weeks.reduce((m, w) => Math.max(m, w.week), 0) + 1;
        if (next <= 20) form.setData('weeks', [...d.weeks, { week: next, topic: '', details: '' }]);
    }

    function save(e: React.FormEvent) {
        e.preventDefault();
        const options = { forceFormData: true, preserveScroll: true };
        if (scheme) form.post(`/school/lesson-notes/schemes/${scheme.id}`, options);
        else form.post('/school/lesson-notes/schemes', options);
    }

    function remove() {
        if (scheme && confirm('Delete this scheme of work? Lesson notes already written from it are kept.')) router.delete(`/school/lesson-notes/schemes/${scheme.id}`);
    }

    const weekErrors = Object.entries(form.errors).filter(([k]) => k.startsWith('weeks'));

    const actions = (
        <div className="flex flex-wrap gap-2">
            <Link href="/school/lesson-notes/schemes" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                <ArrowLeft className="size-4" /> All schemes
            </Link>
            {scheme?.source_name && (
                <a href={`/school/lesson-notes/schemes/${scheme.id}/file`} className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                    <Download className="size-4" /> Original file
                </a>
            )}
        </div>
    );

    return (
        <AppLayout breadcrumbs={[{ label: 'Lesson notes', href: '/school/lesson-notes' }, { label: 'Schemes of work', href: '/school/lesson-notes/schemes' }, { label: scheme ? scheme.title : 'New scheme' }]}>
            <form onSubmit={save} className="mx-auto max-w-4xl space-y-5 pb-28 md:pb-10">
                <PageHeader title={scheme ? scheme.title : 'Add a scheme of work'} description="One subject, one class, one term. Upload the file you already have, or type or paste the weeks." actions={actions} />
                <div className="md:hidden">{actions}</div>

                <Panel title="Which subject and class">
                    <div className="grid gap-4 sm:grid-cols-3">
                        {[
                            { id: 'class', label: 'Class', items: classItems, key: 'class_id' as const, error: form.errors.class_id },
                            { id: 'subject', label: 'Subject', items: subjectItems, key: 'subject_id' as const, error: form.errors.subject_id },
                            { id: 'term', label: 'Term', items: termItems, key: 'term_id' as const, error: form.errors.term_id },
                        ].map(f => (
                            <div key={f.id} className="space-y-1.5">
                                <Label htmlFor={f.id}>{f.label}</Label>
                                <Select items={f.items} value={d[f.key]} onValueChange={v => form.setData(f.key, v ?? '')} disabled={!editable}>
                                    <SelectTrigger id={f.id} className="w-full"><SelectValue placeholder="Choose" /></SelectTrigger>
                                    <SelectContent>{f.items.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                                </Select>
                                {f.error && <p className="text-xs text-red-500">{f.error}</p>}
                            </div>
                        ))}
                        <div className="space-y-1.5 sm:col-span-3">
                            <Label htmlFor="title">Name (optional)</Label>
                            <Input id="title" disabled={!editable} value={d.title} onChange={e => form.setData('title', e.target.value)} maxLength={255} placeholder="Left empty, it is named after the subject and class" />
                        </div>
                    </div>
                </Panel>

                {editable && (
                    <Panel title={d.weeks.length ? 'Read it again from a file or text' : 'Upload or paste the scheme'} description="PDF, Word (.docx) or a text file, up to 10 MB. Or paste the text, one week per line, such as “Week 1: Number bases”.">
                        <div className="space-y-3">
                            <label className="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm hover:border-indigo-400 dark:border-white/15">
                                <FileUp className="size-5 text-slate-500" aria-hidden="true" />
                                <span className="min-w-0 flex-1 truncate">{d.file ? d.file.name : scheme?.source_name ? `Kept: ${scheme.source_name}. Choose a new file to replace it.` : 'Choose a file'}</span>
                                {d.file && <button type="button" onClick={e => { e.preventDefault(); form.setData('file', null); }} aria-label="Remove the file" className="text-slate-500 hover:text-slate-800"><X className="size-4" /></button>}
                                <input type="file" accept=".pdf,.docx,.txt" className="sr-only" onChange={e => form.setData('file', e.target.files?.[0] ?? null)} />
                            </label>
                            {form.errors.file && <p className="text-xs text-red-500">{form.errors.file}</p>}
                            {!d.file && (
                                <Textarea rows={5} value={text} onChange={e => setText(e.target.value)} maxLength={60000} aria-label="Scheme of work text"
                                    placeholder={'Week 1: Number bases\nConverting from base 10 to other bases\nWeek 2: Modular arithmetic\n…'} />
                            )}
                            <div className="flex flex-wrap items-center gap-3">
                                {ai.shown && (
                                    <label className={`flex items-center gap-2 text-sm ${aiReady ? '' : 'opacity-60'}`}>
                                        <input type="checkbox" className="size-4 accent-violet-600" disabled={!aiReady} checked={useAi} onChange={e => setUseAi(e.target.checked)} />
                                        <Sparkles className="size-4 text-violet-600" aria-hidden="true" /> Read it with AI
                                    </label>
                                )}
                                <Button type="button" variant="outline" className="ml-auto" onClick={read} disabled={reading || !d.class_id || !d.subject_id || (!d.file && !text.trim())}>
                                    {reading ? <Loader2 className="size-4 animate-spin" /> : null} {reading ? 'Reading…' : 'Read the weeks'}
                                </Button>
                            </div>
                            {ai.shown && ai.reason && <p className="text-xs text-slate-500">{ai.reason} Typed "Week 1: …" lines can still be read.</p>}
                            {!useAi && isPdf && <p className="text-xs text-amber-700 dark:text-amber-300">A PDF can only be read by the AI. Without it, type the weeks below; the file is still kept with the scheme when you save.</p>}
                            {(!d.class_id || !d.subject_id) && <p className="text-xs text-slate-500">Choose the class and subject first.</p>}
                            {readNote && <p role="status" className={`text-sm ${readNote.tone === 'good' ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-600'}`}>{readNote.text}</p>}
                        </div>
                    </Panel>
                )}

                <Panel title={`Weeks (${d.weeks.length})`} flush>
                    {d.weeks.length === 0 ? (
                        <p className="px-5 pb-5 text-sm text-slate-500">No weeks yet. Read them from the scheme above, or add them one by one.</p>
                    ) : (
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {d.weeks.map((w, i) => {
                                const written = scheme?.notes[String(w.week)] ?? [];
                                return (
                                    <li key={i} className="grid gap-3 px-5 py-4 sm:grid-cols-[80px_1fr]">
                                        <div className="space-y-1">
                                            <Label htmlFor={`wk-${i}`} className="text-xs text-slate-500">Week</Label>
                                            <Input id={`wk-${i}`} type="number" min={1} max={20} disabled={!editable} value={w.week} onChange={e => setWeek(i, { week: Number(e.target.value) })} />
                                        </div>
                                        <div className="space-y-2">
                                            <div className="flex gap-2">
                                                <Input aria-label={`Week ${w.week} topic`} disabled={!editable} value={w.topic} onChange={e => setWeek(i, { topic: e.target.value })} maxLength={255} placeholder="Topic" />
                                                {editable && <Button type="button" variant="ghost" size="icon" onClick={() => form.setData('weeks', d.weeks.filter((_, n) => n !== i))} aria-label={`Remove week ${w.week}`}><Trash2 className="size-4" /></Button>}
                                            </div>
                                            <Textarea aria-label={`Week ${w.week} content`} rows={2} disabled={!editable} value={w.details} onChange={e => setWeek(i, { details: e.target.value })} maxLength={2000} placeholder="Sub-topics and content (optional)" />
                                            {scheme && (
                                                <div className="flex flex-wrap items-center gap-2 text-sm">
                                                    {written.map(n => (
                                                        <Link key={n.id} href={`/school/lesson-notes/${n.id}`} className="inline-flex items-center gap-1.5 text-indigo-700 hover:underline dark:text-indigo-300">
                                                            <NotebookPen className="size-3.5" aria-hidden="true" /> Lesson note <Pill tone={statusLabel[n.status].tone}>{statusLabel[n.status].label}</Pill>
                                                        </Link>
                                                    ))}
                                                    {canManage && (
                                                        <Link href={`/school/lesson-notes/new?scheme=${scheme.id}&week=${w.week}`} className="inline-flex items-center gap-1 font-medium text-indigo-700 hover:underline dark:text-indigo-300">
                                                            <Plus className="size-3.5" aria-hidden="true" /> {written.length ? 'Another note' : 'Write the lesson note'}
                                                        </Link>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                    {editable && d.weeks.length < 20 && (
                        <div className="border-t border-slate-100 px-5 py-3 dark:border-white/[0.06]">
                            <Button type="button" variant="outline" size="sm" onClick={addWeek}><Plus className="size-4" /> Add a week</Button>
                        </div>
                    )}
                </Panel>
                {weekErrors.length > 0 && <p className="text-sm text-red-500">{weekErrors[0][1]}</p>}

                {editable && (
                    <div className="flex flex-wrap items-center justify-end gap-2">
                        {scheme && <Button type="button" variant="outline" onClick={remove} className="mr-auto text-red-600"><Trash2 className="size-4" /> Delete</Button>}
                        <Button type="submit" disabled={form.processing || d.weeks.length === 0} className="bg-indigo-600 text-white hover:bg-indigo-700">Save scheme of work</Button>
                    </div>
                )}
                {scheme && scheme.weeks.length !== d.weeks.length && <p className="text-right text-xs text-slate-500">Save to keep the changes to the weeks.</p>}
            </form>
        </AppLayout>
    );
}
