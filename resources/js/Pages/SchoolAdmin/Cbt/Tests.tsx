import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { EmptyState, PageHeader, Panel } from '@/components/app/kit';
import { CbtStatusPill, examTime } from '@/components/cbt/CbtParts';
import { Library, MonitorCheck, Plus } from 'lucide-react';
import type { CbtTestRow } from '@/Types';

interface Props {
    tests: { data: (CbtTestRow & { attempts: number; finished: number })[]; total: number; from: number | null; to: number | null; prev_page_url: string | null; next_page_url: string | null };
    canManage: boolean;
    banks: number;
}

export default function CbtTests({ tests, canManage, banks }: Props) {
    const actions = (
        <div className="flex flex-wrap gap-2">
            <Link href="/school/cbt/banks" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                <Library className="size-4" /> Question banks
            </Link>
            {canManage && (
                <Link href="/school/cbt/tests/new" className="inline-flex h-9 items-center gap-2 rounded-lg bg-indigo-600 px-3.5 text-sm font-medium text-white hover:bg-indigo-700">
                    <Plus className="size-4" /> New test
                </Link>
            )}
        </div>
    );

    return (
        <AppLayout breadcrumbs={[{ label: 'CBT' }, { label: 'Tests' }]}>
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader title="Computer-based tests" description="Timed tests students take in their portal. Each one is marked as soon as it is handed in." actions={actions} />
                <div className="md:hidden">{actions}</div>

                <Panel flush>
                    {tests.data.length === 0 ? (
                        <div className="px-5 pb-5">
                            <EmptyState icon={MonitorCheck} title="No tests yet"
                                text={banks ? 'Create a test and choose how many questions to draw from each question bank.' : 'Start by creating a question bank and adding questions to it.'} />
                        </div>
                    ) : (
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {tests.data.map(t => (
                                <li key={t.id}>
                                    <Link href={`/school/cbt/tests/${t.id}`} className="flex flex-wrap items-start justify-between gap-3 px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-white/[0.03]">
                                        <div className="min-w-0 space-y-0.5">
                                            <p className="font-medium text-slate-900 dark:text-white">{t.title}</p>
                                            <p className="text-sm text-slate-500">
                                                {[t.class, t.subject].filter(Boolean).join(' · ')} · {t.questions} questions in {t.duration_minutes} min
                                            </p>
                                            <p className="text-xs text-slate-500">Opens {examTime(t.opens_at)}{t.closes_at ? `, closes ${examTime(t.closes_at)}` : ''}</p>
                                        </div>
                                        <div className="text-right">
                                            <CbtStatusPill status={t.status} />
                                            <p className="mt-1 text-xs text-slate-500">{t.finished} handed in{t.attempts > t.finished ? `, ${t.attempts - t.finished} writing` : ''}</p>
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                    {(tests.prev_page_url || tests.next_page_url) && (
                        <div className="flex items-center justify-between px-5 py-3 text-sm text-slate-500">
                            <span>{tests.from}–{tests.to} of {tests.total}</span>
                            <div className="flex gap-2">
                                {tests.prev_page_url && <Button variant="outline" size="sm" onClick={() => router.get(tests.prev_page_url!)}>Previous</Button>}
                                {tests.next_page_url && <Button variant="outline" size="sm" onClick={() => router.get(tests.next_page_url!)}>Next</Button>}
                            </div>
                        </div>
                    )}
                </Panel>
            </div>
        </AppLayout>
    );
}
