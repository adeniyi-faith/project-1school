import { Link, router } from '@inertiajs/react';
import { Check, ChevronRight } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { EmptyState, PageHeader, Panel } from '@/components/app/kit';

interface Step { key: string; title: string; text: string; href: string | null; auto: boolean; done: boolean }
interface Props {
    term:  { id: number; name: string; year: string | null } | null;
    steps: Step[];
}

export default function NewTerm({ term, steps }: Props) {
    const done = steps.filter((s) => s.done).length;

    function tick(key: string) {
        router.post('/school/new-term/tick', { step: key }, { preserveScroll: true });
    }

    return (
        <AppLayout title="Start a New Term">
            <div className="space-y-6">
                <PageHeader
                    title="Start a new term"
                    description={term ? `${term.name}${term.year ? `, ${term.year}` : ''}: ${done} of ${steps.length} steps done` : 'Do these in order at the start of every term.'}
                />

                {!term && (
                    <EmptyState icon={Check} title="No current term yet" text="Choose the current term first, then come back to this list."
                        action={<Link href="/school/academics/terms" className="text-sm font-medium text-indigo-600 hover:underline">Go to Terms</Link>} />
                )}

                <Panel title="Your checklist" description="Steps the system can see tick themselves. Tick the others yourself once you have checked them." flush>
                    <ol className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                        {steps.map((s, i) => (
                            <li key={s.key} className="flex items-start gap-3 px-5 py-4">
                                {s.auto || !term ? (
                                    <span className={`mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full border text-xs font-semibold ${s.done ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-300 text-slate-500 dark:border-white/20'}`}>
                                        {s.done ? <Check className="size-3.5" /> : i + 1}
                                    </span>
                                ) : (
                                    <button type="button" onClick={() => tick(s.key)} aria-pressed={s.done} aria-label={`Mark "${s.title}" as ${s.done ? 'not done' : 'done'}`}
                                        className={`mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full border text-xs font-semibold max-md:size-7 ${s.done ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-300 text-slate-500 hover:border-emerald-500 dark:border-white/20'}`}>
                                        {s.done ? <Check className="size-3.5" /> : i + 1}
                                    </button>
                                )}
                                <div className="min-w-0 flex-1">
                                    <p className={`text-sm font-medium ${s.done ? 'text-slate-400 line-through' : 'text-slate-900 dark:text-white'}`}>{s.title}</p>
                                    <p className="text-xs text-slate-500">{s.text}</p>
                                </div>
                                {s.href && (
                                    <Link href={s.href} className="inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/5">
                                        Open <ChevronRight className="size-3.5" />
                                    </Link>
                                )}
                            </li>
                        ))}
                    </ol>
                </Panel>
            </div>
        </AppLayout>
    );
}
