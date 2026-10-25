import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { EmptyState, PageHeader, Panel } from '@/components/app/kit';
import { FileCheck2, Library, Plus, Printer } from 'lucide-react';
import type { ExamPaperRow, LessonChoices } from '@/Types';

interface Props extends LessonChoices {
    papers: ExamPaperRow[];
    filters: { class_id?: string | null; term_id?: string | null };
    canManage: boolean;
}

/** Every printed exam paper, newest first */
export default function Papers({ papers, classes, terms, filters, canManage }: Props) {
    function filter(key: string, value: string) {
        router.get('/school/cbt/papers', { ...filters, [key]: value || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    }

    const lists = [
        { key: 'class_id', label: 'Class', items: [{ value: '', label: 'All classes' }, ...classes.map(c => ({ value: String(c.id), label: c.name }))] },
        { key: 'term_id', label: 'Term', items: [{ value: '', label: 'All terms' }, ...terms.map(t => ({ value: String(t.id), label: t.name }))] },
    ];

    const actions = (
        <div className="flex flex-wrap gap-2">
            <Link href="/school/cbt/banks" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                <Library className="size-4" /> Question banks
            </Link>
            {canManage && (
                <Link href="/school/cbt/papers/new" className="inline-flex h-9 items-center gap-2 rounded-lg bg-indigo-600 px-3.5 text-sm font-medium text-white hover:bg-indigo-700">
                    <Plus className="size-4" /> New exam paper
                </Link>
            )}
        </div>
    );

    return (
        <AppLayout breadcrumbs={[{ label: 'Learning' }, { label: 'Printed Papers' }]}>
            <div className="mx-auto max-w-5xl space-y-5 pb-24 md:pb-0">
                <PageHeader title="Printed exam papers" description="Build a paper from your question banks, then print it with the school's name and logo for students to write on. Each paper has a marking scheme for teachers." actions={actions} />
                <div className="flex flex-wrap gap-2 md:hidden">{actions}</div>

                <div className="grid gap-2 sm:grid-cols-2">
                    {lists.map(f => (
                        <Select key={f.key} items={f.items} value={(filters as Record<string, string | null | undefined>)[f.key] ?? ''} onValueChange={v => filter(f.key, v ?? '')}>
                            <SelectTrigger className="w-full" aria-label={f.label}><SelectValue /></SelectTrigger>
                            <SelectContent>{f.items.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                        </Select>
                    ))}
                </div>

                {papers.length === 0 ? (
                    <Panel>
                        <EmptyState icon={Printer} title="No exam papers yet"
                            text={canManage ? 'Start one with "New exam paper". Questions come from your question banks, so add questions there first (you can ask the AI to write them).' : 'Papers appear here when teachers make them.'} />
                    </Panel>
                ) : (
                    <Panel flush>
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {papers.map(p => (
                                <li key={p.id} className="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-5 py-3">
                                    <Link href={`/school/cbt/papers/${p.id}`} className="min-w-0 flex-1 hover:underline">
                                        <p className="truncate font-medium text-slate-900 dark:text-white">{[p.subject, p.title].filter(Boolean).join(' · ')}</p>
                                        <p className="text-xs text-slate-500">
                                            {[p.class, p.term, `${p.questions} question${p.questions === 1 ? '' : 's'}`, `${p.marks} marks`, p.by].filter(Boolean).join(' · ')}
                                        </p>
                                    </Link>
                                    <div className="flex gap-3 text-sm">
                                        <a href={`/school/cbt/papers/${p.id}/pdf`} target="_blank" rel="noopener" className="inline-flex items-center gap-1 text-indigo-600 hover:underline dark:text-indigo-400"><Printer className="size-4" /> Paper</a>
                                        <a href={`/school/cbt/papers/${p.id}/marking-scheme`} target="_blank" rel="noopener" className="inline-flex items-center gap-1 text-indigo-600 hover:underline dark:text-indigo-400"><FileCheck2 className="size-4" /> Marking scheme</a>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </Panel>
                )}
            </div>
        </AppLayout>
    );
}
