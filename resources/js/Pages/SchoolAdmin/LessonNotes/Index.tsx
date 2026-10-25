import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { EmptyState, PageHeader, Panel, Pill } from '@/components/app/kit';
import LessonTabs, { statusLabel } from '@/components/lesson-notes/LessonTabs';
import { NotebookPen, Plus, Sparkles } from 'lucide-react';
import type { LessonChoices, LessonNoteRow } from '@/Types';

interface Props extends LessonChoices {
    notes: { data: LessonNoteRow[]; links: { url: string | null; label: string; active: boolean }[]; total: number };
    counts: Record<string, number>;
    filters: { status?: string; class_id?: string; subject_id?: string; term_id?: string; mine?: string };
    canCreate: boolean;
    canApprove: boolean;
    mine: boolean;
}

/** Every lesson note, newest week first, with filters; heads see everyone's, teachers their own */
export default function LessonNotesIndex({ notes, counts, classes, subjects, terms, filters, canCreate, canApprove, mine }: Props) {
    function filter(key: string, value: string) {
        router.get('/school/lesson-notes', { ...filters, [key]: value || undefined, page: undefined }, { preserveState: true, preserveScroll: true, replace: true });
    }

    const classItems = [{ value: '', label: 'All classes' }, ...classes.map(c => ({ value: String(c.id), label: c.name }))];
    const subjectItems = [{ value: '', label: 'All subjects' }, ...subjects.filter(s => !filters.class_id || s.class_ids.length === 0 || s.class_ids.includes(Number(filters.class_id))).map(s => ({ value: String(s.id), label: s.name }))];
    const termItems = [{ value: '', label: 'All terms' }, ...terms.map(t => ({ value: String(t.id), label: t.name }))];
    const statuses = ['', 'draft', 'submitted', 'approved', 'rejected'];

    const actions = canCreate ? (
        <Link href="/school/lesson-notes/new" className="inline-flex h-9 items-center gap-2 rounded-lg bg-indigo-600 px-3.5 text-sm font-medium text-white hover:bg-indigo-700">
            <Plus className="size-4" /> New lesson note
        </Link>
    ) : undefined;

    return (
        <AppLayout breadcrumbs={[{ label: 'Lesson notes' }]}>
            <div className="mx-auto max-w-5xl space-y-5 pb-24 md:pb-0">
                <PageHeader
                    title="Lesson notes"
                    description={canApprove ? 'Teachers write their notes here and send them for approval. Approve them or send them back with a comment.' : 'Write your lesson notes week by week, print them, and send them for approval.'}
                    actions={actions}
                />
                {actions && <div className="flex flex-wrap gap-2 md:hidden">{actions}</div>}
                <LessonTabs current="notes" />

                <div className="flex flex-wrap gap-1.5" role="group" aria-label="Status">
                    {statuses.map(s => {
                        const active = (filters.status ?? '') === s;
                        const n = s ? counts[s] ?? 0 : Object.values(counts).reduce((a, b) => a + b, 0);
                        return (
                            <button key={s || 'all'} type="button" onClick={() => filter('status', s)} aria-pressed={active}
                                className={`rounded-full border px-3 py-1 max-md:py-2 text-sm ${active ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200'}`}>
                                {s ? statusLabel[s].label : 'All'} <span className="tabular-nums opacity-70">{n}</span>
                            </button>
                        );
                    })}
                </div>

                <div className="grid gap-2 sm:grid-cols-3">
                    {[{ key: 'class_id', items: classItems, label: 'Class' }, { key: 'subject_id', items: subjectItems, label: 'Subject' }, { key: 'term_id', items: termItems, label: 'Term' }].map(f => (
                        <Select key={f.key} items={f.items} value={(filters as Record<string, string | undefined>)[f.key] ?? ''} onValueChange={v => filter(f.key, v ?? '')}>
                            <SelectTrigger className="w-full" aria-label={f.label}><SelectValue /></SelectTrigger>
                            <SelectContent>{f.items.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                        </Select>
                    ))}
                </div>
                {canApprove && (
                    <label className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input type="checkbox" className="size-4 accent-indigo-600" checked={mine} onChange={e => filter('mine', e.target.checked ? '1' : '')} /> Only my own notes
                    </label>
                )}

                {notes.data.length === 0 ? (
                    <Panel>
                        <EmptyState icon={NotebookPen} title="No lesson notes here yet"
                            text={canCreate ? 'Start one with "New lesson note". If your scheme of work is saved under "Schemes of work", you can pick the week and let the topic fill in.' : 'Notes appear here when teachers write them.'} />
                    </Panel>
                ) : (
                    <Panel flush>
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {notes.data.map(n => (
                                <li key={n.id}>
                                    <Link href={`/school/lesson-notes/${n.id}`} className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-5 py-3 hover:bg-slate-50 dark:hover:bg-white/[0.03]">
                                        <div className="min-w-0">
                                            <p className="truncate font-medium text-slate-900 dark:text-white">{n.title}</p>
                                            <p className="text-xs text-slate-500">
                                                {[n.subject, n.class, n.term, n.week ? `Week ${n.week}` : null, canApprove ? n.teacher : null].filter(Boolean).join(' · ')}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            {n.ai_drafted && <span className="inline-flex items-center gap-1 text-xs text-violet-600 dark:text-violet-300"><Sparkles className="size-3.5" aria-hidden="true" /> AI draft</span>}
                                            <Pill tone={statusLabel[n.status].tone}>{statusLabel[n.status].label}</Pill>
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </Panel>
                )}

                {notes.links.length > 3 && (
                    <nav className="flex flex-wrap justify-center gap-1" aria-label="Pages">
                        {notes.links.map((l, i) => (
                            <Link key={i} href={l.url ?? '#'} preserveScroll
                                className={`min-w-9 rounded-lg px-2.5 py-1.5 text-center text-sm ${l.active ? 'bg-indigo-600 text-white' : l.url ? 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/[0.06]' : 'pointer-events-none text-slate-400'}`}>
                                {l.label.replace('&laquo;', '‹').replace('&raquo;', '›')}
                            </Link>
                        ))}
                    </nav>
                )}
            </div>
        </AppLayout>
    );
}
