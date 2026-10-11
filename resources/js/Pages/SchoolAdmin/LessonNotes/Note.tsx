import { useMemo, useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageHeader, Panel, Pill } from '@/components/app/kit';
import { statusLabel } from '@/components/lesson-notes/LessonTabs';
import { ArrowLeft, Check, FileText, Loader2, Send, Sparkles, Trash2, Undo2 } from 'lucide-react';
import type { AiAvailability, LessonChoices, LessonNoteFormat, SchemeWeek } from '@/Types';

interface Values {
    class_id: number | null;
    subject_id: number | null;
    term_id: number | null;
    scheme_of_work_id: number | null;
    lesson_note_format_id: number | null;
    week: number | null;
    week_start: string;
    duration_minutes: number | null;
    title: string;
    sections: Record<string, string>;
    status: 'draft' | 'submitted' | 'approved' | 'rejected';
    ai_drafted: boolean;
}

interface Props extends LessonChoices {
    note: { id: number; reviewer_feedback: string | null; reviewed_by: string | null; reviewed_at: string | null } | null;
    values: Values;
    formats: LessonNoteFormat[];
    schemes: { id: number; label: string; class_id: number; subject_id: number; term_id: number | null; weeks: SchemeWeek[] }[];
    termStarts: Record<string, string | null>;
    canEdit: boolean;
    canApprove: boolean;
    ai: AiAvailability;
}

const str = (v: number | null | undefined) => (v ? String(v) : '');

/** Write, print and send one lesson note; heads approve it or send it back here */
export default function LessonNote({ note, values, classes, subjects, terms, formats, schemes, termStarts, canEdit, canApprove, ai }: Props) {
    const form = useForm({ ...values, submit: false });
    const pageErrors = usePage().props.errors as Record<string, string>;
    const d = form.data;
    const [instructions, setInstructions] = useState('');
    const [drafting, setDrafting] = useState(false);
    const [aiNote, setAiNote] = useState<{ tone: 'good' | 'bad'; text: string } | null>(null);
    const [feedback, setFeedback] = useState('');
    const [reviewing, setReviewing] = useState(false);

    const format = formats.find(f => f.id === d.lesson_note_format_id) ?? formats.find(f => f.is_default) ?? formats[0];
    const scheme = schemes.find(s => s.id === d.scheme_of_work_id) ?? null;
    const status = statusLabel[values.status];
    // Text under headings the chosen format does not have is shown too, so nothing typed disappears
    const extra = useMemo(() => Object.keys(d.sections).filter(k => !format?.sections.some(s => s.key === k) && (d.sections[k] ?? '').trim() !== ''), [d.sections, format]);

    const schemeItems = [{ value: '', label: 'No scheme of work' }, ...schemes.map(s => ({ value: String(s.id), label: s.label }))];
    const weekItems = (scheme?.weeks ?? []).map(w => ({ value: String(w.week), label: `Week ${w.week}: ${w.topic}` }));
    const classItems = classes.map(c => ({ value: String(c.id), label: c.name }));
    const subjectItems = subjects.filter(s => !d.class_id || !s.class_id || s.class_id === d.class_id).map(s => ({ value: String(s.id), label: s.name }));
    const termItems = [{ value: '', label: 'No term' }, ...terms.map(t => ({ value: String(t.id), label: t.name }))];
    const formatItems = formats.map(f => ({ value: String(f.id), label: f.name + (f.is_default ? ' (default)' : '') }));

    function mondayOf(termId: number | null, week: number | null): string | null {
        const start = termId ? termStarts[String(termId)] : null;
        if (!start || !week) return null;
        const date = new Date(start + 'T00:00:00');
        date.setDate(date.getDate() - ((date.getDay() + 6) % 7) + (week - 1) * 7);
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }

    function pickScheme(id: string) {
        const s = schemes.find(x => String(x.id) === id);
        if (!s) { form.setData('scheme_of_work_id', null); return; }
        form.setData(data => ({ ...data, scheme_of_work_id: s.id, class_id: s.class_id, subject_id: s.subject_id, term_id: s.term_id ?? data.term_id }));
    }

    function pickWeek(value: string) {
        const w = scheme?.weeks.find(x => String(x.week) === value);
        if (!w) return;
        form.setData(data => ({ ...data, week: w.week, title: w.topic, week_start: mondayOf(data.term_id, w.week) ?? data.week_start }));
    }

    function setSection(key: string, text: string) {
        form.setData('sections', { ...d.sections, [key]: text });
    }

    async function draftWithAi() {
        const filled = Object.values(d.sections).some(t => (t ?? '').trim() !== '');
        if (filled && !confirm('The AI will replace what is written under the headings. Continue?')) return;
        setDrafting(true);
        setAiNote(null);
        try {
            const { data } = await axios.post<{ sections: Record<string, string> }>('/school/lesson-notes/ai', {
                class_id: d.class_id, subject_id: d.subject_id, term_id: d.term_id, scheme_of_work_id: d.scheme_of_work_id,
                lesson_note_format_id: format?.id, week: d.week, topic: d.title, duration_minutes: d.duration_minutes, instructions,
            });
            form.setData(data0 => ({ ...data0, sections: { ...data0.sections, ...data.sections }, ai_drafted: true }));
            setAiNote({ tone: 'good', text: 'The AI has written a draft. Read it carefully, change anything that does not suit your class, then save.' });
        } catch (e) {
            const errs = axios.isAxiosError(e) ? e.response?.data?.errors : null;
            const message = errs ? (errs.ai?.[0] ?? errs.topic?.[0] ?? errs.class_id?.[0] ?? errs.subject_id?.[0]) : null;
            setAiNote({ tone: 'bad', text: message || 'The AI could not write the note just now. Try again.' });
        } finally {
            setDrafting(false);
        }
    }

    function save(submit: boolean) {
        form.transform(data => ({ ...data, submit, lesson_note_format_id: format?.id ?? null }));
        if (note) form.put(`/school/lesson-notes/${note.id}`, { preserveScroll: true });
        else form.post('/school/lesson-notes');
    }

    function review(action: 'approved' | 'rejected') {
        if (!note) return;
        setReviewing(true);
        router.put(`/school/lesson-notes/${note.id}/review`, { action, reviewer_feedback: feedback || null }, { preserveScroll: true, onFinish: () => setReviewing(false) });
    }

    function remove() {
        if (note && confirm('Delete this lesson note?')) router.delete(`/school/lesson-notes/${note.id}`);
    }

    const select = (id: string, label: string, items: { value: string; label: string }[], value: string, onChange: (v: string) => void, error?: string, placeholder?: string) => (
        <div className="space-y-1.5">
            <Label htmlFor={id}>{label}</Label>
            <Select items={items} value={value} onValueChange={v => onChange(v ?? '')} disabled={!canEdit}>
                <SelectTrigger id={id} className="w-full"><SelectValue placeholder={placeholder ?? 'Choose'} /></SelectTrigger>
                <SelectContent>{items.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
            </Select>
            {error && <p className="text-xs text-red-500">{error}</p>}
        </div>
    );

    const headerActions = (
        <div className="flex flex-wrap gap-2">
            <Link href="/school/lesson-notes" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                <ArrowLeft className="size-4" /> All notes
            </Link>
            {note && (
                <a href={`/school/lesson-notes/${note.id}/pdf`} target="_blank" rel="noopener" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                    <FileText className="size-4" /> Print (PDF)
                </a>
            )}
        </div>
    );

    return (
        <AppLayout breadcrumbs={[{ label: 'Lesson notes', href: '/school/lesson-notes' }, { label: note ? d.title || 'Lesson note' : 'New lesson note' }]}>
            <div className="mx-auto max-w-4xl space-y-5 pb-28 md:pb-10">
                <PageHeader title={note ? 'Lesson note' : 'New lesson note'} description={note ? <span className="inline-flex items-center gap-2"><Pill tone={status.tone}>{status.label}</Pill>{values.ai_drafted && <span className="text-xs text-violet-600 dark:text-violet-300">Started by the AI</span>}</span> : 'Pick the week from your scheme of work, or type the topic. Then write the note, or let the AI draft it for you to check.'} actions={headerActions} />
                <div className="md:hidden">{headerActions}</div>

                {values.status === 'rejected' && note?.reviewer_feedback && (
                    <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
                        <p className="font-medium">Sent back{note.reviewed_by ? ` by ${note.reviewed_by}` : ''}</p>
                        <p className="mt-0.5 whitespace-pre-line">{note.reviewer_feedback}</p>
                    </div>
                )}
                {values.status === 'submitted' && !canApprove && <p className="rounded-xl bg-indigo-50 px-4 py-3 text-sm text-indigo-800 dark:bg-indigo-500/10 dark:text-indigo-200">This note is waiting for approval, so it cannot be changed now.</p>}
                {values.status === 'approved' && <p className="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200">Approved{note?.reviewed_by ? ` by ${note.reviewed_by}` : ''}. An approved note can no longer be changed.</p>}

                {canApprove && note && values.status === 'submitted' && (
                    <Panel title="Review this note">
                        <div className="space-y-3">
                            <Textarea rows={2} value={feedback} onChange={e => setFeedback(e.target.value)} placeholder="Comment for the teacher (needed when sending back)" maxLength={1000} aria-label="Comment for the teacher" />
                            {(pageErrors.reviewer_feedback || pageErrors.action) && <p className="text-xs text-red-500">{pageErrors.reviewer_feedback || pageErrors.action}</p>}
                            <div className="flex flex-wrap justify-end gap-2">
                                <Button variant="outline" disabled={reviewing} onClick={() => review('rejected')}><Undo2 className="size-4" /> Send back</Button>
                                <Button disabled={reviewing} onClick={() => review('approved')} className="bg-emerald-600 text-white hover:bg-emerald-700"><Check className="size-4" /> Approve</Button>
                            </div>
                        </div>
                    </Panel>
                )}

                <Panel title="Lesson details">
                    <div className="grid gap-4 sm:grid-cols-2">
                        {schemes.length > 0 && select('scheme', 'Scheme of work', schemeItems, str(d.scheme_of_work_id), pickScheme)}
                        {scheme && weekItems.length > 0 && select('scheme-week', 'Week in the scheme', weekItems, str(d.week), pickWeek, undefined, 'Pick a week to fill the topic')}
                        {select('class', 'Class', classItems, str(d.class_id), v => form.setData('class_id', v ? Number(v) : null), form.errors.class_id)}
                        {select('subject', 'Subject', subjectItems, str(d.subject_id), v => form.setData('subject_id', v ? Number(v) : null), form.errors.subject_id)}
                        {select('term', 'Term', termItems, str(d.term_id), v => form.setData('term_id', v ? Number(v) : null), form.errors.term_id)}
                        <div className="grid grid-cols-3 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="week">Week</Label>
                                <Input id="week" type="number" min={1} max={20} disabled={!canEdit} value={d.week ?? ''} onChange={e => form.setData('week', e.target.value ? Number(e.target.value) : null)} />
                            </div>
                            <div className="col-span-2 space-y-1.5">
                                <Label htmlFor="week_start">Week starting</Label>
                                <Input id="week_start" type="date" disabled={!canEdit} value={d.week_start} onChange={e => form.setData('week_start', e.target.value)} />
                                {form.errors.week_start && <p className="text-xs text-red-500">{form.errors.week_start}</p>}
                            </div>
                        </div>
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="title">Topic</Label>
                            <Input id="title" disabled={!canEdit} value={d.title} onChange={e => form.setData('title', e.target.value)} maxLength={255} placeholder="e.g. Number bases: converting base 10 to base 2" />
                            {form.errors.title && <p className="text-xs text-red-500">{form.errors.title}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="duration">Lesson length (minutes)</Label>
                            <Input id="duration" type="number" min={10} max={300} disabled={!canEdit} value={d.duration_minutes ?? ''} onChange={e => form.setData('duration_minutes', e.target.value ? Number(e.target.value) : null)} />
                        </div>
                        {select('format', 'Format', formatItems, str(format?.id), v => form.setData('lesson_note_format_id', v ? Number(v) : null), form.errors.lesson_note_format_id)}
                    </div>
                </Panel>

                {canEdit && ai.shown && (
                    <Panel title="Write it with AI" description="The AI drafts the note under your format's headings, using the topic and your scheme of work. Nothing is saved until you press Save.">
                        {ai.reason ? (
                            <p className="text-sm text-slate-500">{ai.reason}</p>
                        ) : (
                            <div className="space-y-3">
                                <Textarea rows={2} value={instructions} onChange={e => setInstructions(e.target.value)} maxLength={1500} aria-label="Anything the AI should know"
                                    placeholder="Anything it should know (optional). For example: the class is slow with fractions; use a market example; we have no projector." />
                                <div className="flex flex-wrap items-center justify-end gap-3">
                                    {aiNote && <p className={`mr-auto text-sm ${aiNote.tone === 'good' ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-600'}`} role="status">{aiNote.text}</p>}
                                    <Button type="button" onClick={draftWithAi} disabled={drafting || !d.class_id || !d.subject_id || !d.title.trim()} className="bg-violet-600 text-white hover:bg-violet-700">
                                        {drafting ? <Loader2 className="size-4 animate-spin" /> : <Sparkles className="size-4" />} {drafting ? 'Writing…' : 'Write with AI'}
                                    </Button>
                                </div>
                                {(!d.class_id || !d.subject_id || !d.title.trim()) && <p className="text-xs text-slate-500">Choose the class and subject and type the topic first.</p>}
                            </div>
                        )}
                    </Panel>
                )}

                <Panel title="The note">
                    <div className="space-y-5">
                        {format?.sections.map(s => (
                            <div key={s.key} className="space-y-1.5">
                                <Label htmlFor={`s-${s.key}`}>{s.label}</Label>
                                {canEdit ? (
                                    <Textarea id={`s-${s.key}`} rows={s.key === 'presentation' || s.key === 'explain' ? 8 : 4} value={d.sections[s.key] ?? ''} onChange={e => setSection(s.key, e.target.value)} placeholder={s.hint} maxLength={8000} />
                                ) : (
                                    <p className="whitespace-pre-line text-sm text-slate-700 dark:text-slate-300">{d.sections[s.key] || <span className="italic text-slate-400">Not filled in.</span>}</p>
                                )}
                            </div>
                        ))}
                        {extra.map(k => (
                            <div key={k} className="space-y-1.5">
                                <div className="flex items-center justify-between gap-2">
                                    <Label>{k.replace(/_/g, ' ').replace(/^./, c => c.toUpperCase())} <span className="font-normal text-slate-500">(not a heading in this format, but kept)</span></Label>
                                    {canEdit && <button type="button" onClick={() => { const { [k]: _removed, ...rest } = d.sections; void _removed; form.setData('sections', rest); }} className="text-xs text-red-600 hover:underline">Remove</button>}
                                </div>
                                <p className="whitespace-pre-line rounded-lg bg-slate-50 p-3 text-sm text-slate-600 dark:bg-white/[0.04] dark:text-slate-300">{d.sections[k]}</p>
                            </div>
                        ))}
                        {(form.errors as Record<string, string>).sections && <p className="text-sm text-red-500">{(form.errors as Record<string, string>).sections}</p>}
                    </div>
                </Panel>

                {canEdit && (
                    <div className="flex flex-wrap items-center justify-end gap-2">
                        {note && <Button variant="outline" onClick={remove} className="mr-auto text-red-600"><Trash2 className="size-4" /> Delete</Button>}
                        <Button variant="outline" disabled={form.processing} onClick={() => save(false)}>Save draft</Button>
                        <Button disabled={form.processing} onClick={() => save(true)} className="bg-indigo-600 text-white hover:bg-indigo-700"><Send className="size-4" /> Save and send for approval</Button>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
