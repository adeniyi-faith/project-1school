import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { EmptyState, PageHeader, Panel } from '@/components/app/kit';
import LessonTabs from '@/components/lesson-notes/LessonTabs';
import { CalendarRange, Paperclip, Plus } from 'lucide-react';
import type { LessonChoices, SchemeRow } from '@/Types';

interface Props extends LessonChoices {
    schemes: SchemeRow[];
    filters: { class_id?: string; subject_id?: string; term_id?: string };
    canManage: boolean;
}

/** Every scheme of work: a subject's topics for one class and term, week by week */
export default function Schemes({ schemes, classes, subjects, terms, filters, canManage }: Props) {
    function filter(key: string, value: string) {
        router.get('/school/lesson-notes/schemes', { ...filters, [key]: value || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    }

    const lists = [
        { key: 'class_id', label: 'Class', items: [{ value: '', label: 'All classes' }, ...classes.map(c => ({ value: String(c.id), label: c.name }))] },
        { key: 'subject_id', label: 'Subject', items: [{ value: '', label: 'All subjects' }, ...subjects.filter(s => !filters.class_id || !s.class_id || String(s.class_id) === filters.class_id).map(s => ({ value: String(s.id), label: s.name }))] },
        { key: 'term_id', label: 'Term', items: [{ value: '', label: 'All terms' }, ...terms.map(t => ({ value: String(t.id), label: t.name }))] },
    ];

    const actions = canManage ? (
        <Link href="/school/lesson-notes/schemes/new" className="inline-flex h-9 items-center gap-2 rounded-lg bg-indigo-600 px-3.5 text-sm font-medium text-white hover:bg-indigo-700">
            <Plus className="size-4" /> Add a scheme of work
        </Link>
    ) : undefined;

    return (
        <AppLayout breadcrumbs={[{ label: 'Lesson notes', href: '/school/lesson-notes' }, { label: 'Schemes of work' }]}>
            <div className="mx-auto max-w-5xl space-y-5 pb-24 md:pb-0">
                <PageHeader title="Schemes of work" description="Upload each subject's scheme of work for the term, or type it in. Teachers then pick a week when writing a lesson note, and the topic fills in." actions={actions} />
                {actions && <div className="md:hidden">{actions}</div>}
                <LessonTabs current="schemes" />

                <div className="grid gap-2 sm:grid-cols-3">
                    {lists.map(f => (
                        <Select key={f.key} items={f.items} value={(filters as Record<string, string | undefined>)[f.key] ?? ''} onValueChange={v => filter(f.key, v ?? '')}>
                            <SelectTrigger className="w-full" aria-label={f.label}><SelectValue /></SelectTrigger>
                            <SelectContent>{f.items.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                        </Select>
                    ))}
                </div>

                {schemes.length === 0 ? (
                    <Panel><EmptyState icon={CalendarRange} title="No schemes of work yet" text={canManage ? 'Add one for each subject and class. You can upload the PDF or Word file you already have.' : 'Schemes appear here when teachers add them.'} /></Panel>
                ) : (
                    <div className="grid gap-3 sm:grid-cols-2">
                        {schemes.map(s => (
                            <Link key={s.id} href={`/school/lesson-notes/schemes/${s.id}`} className="rounded-xl border border-slate-200 bg-white p-4 shadow-xs hover:border-indigo-300 dark:border-white/[0.08] dark:bg-slate-900">
                                <p className="font-semibold text-slate-900 dark:text-white">{s.subject} · {s.class}</p>
                                <p className="text-sm text-slate-500">{s.term ?? 'No term chosen'}</p>
                                <p className="mt-3 text-sm text-slate-700 dark:text-slate-300">
                                    <span className="text-xl font-semibold tabular-nums text-slate-900 dark:text-white">{s.weeks}</span> weeks · {s.notes} lesson note{s.notes === 1 ? '' : 's'} written
                                </p>
                                {s.has_file && <p className="mt-1 inline-flex items-center gap-1 text-xs text-slate-500"><Paperclip className="size-3.5" aria-hidden="true" /> Original file kept</p>}
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
