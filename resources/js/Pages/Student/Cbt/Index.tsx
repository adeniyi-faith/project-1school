import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { EmptyState, PageHeader, Panel, Pill } from '@/components/app/kit';
import { examTime } from '@/components/cbt/CbtParts';
import { MonitorCheck } from 'lucide-react';

interface TestItem {
    id: number; title: string; subject: string | null; duration_minutes: number; questions: number; opens_at: string; closes_at: string | null;
    state: 'open' | 'writing' | 'done' | 'soon' | 'missed'; score: number | null; max_score: number | null;
}

const STATE: Record<TestItem['state'], { label: string; tone: 'good' | 'info' | 'neutral' | 'warn' }> = {
    open: { label: 'Open now', tone: 'good' },
    writing: { label: 'Not finished', tone: 'warn' },
    done: { label: 'Handed in', tone: 'info' },
    soon: { label: 'Coming up', tone: 'neutral' },
    missed: { label: 'Closed', tone: 'neutral' },
};

export default function StudentCbtIndex({ linked, tests }: { linked: boolean; tests: TestItem[] }) {
    return (
        <AppLayout title="My Tests">
            <div className="mx-auto max-w-3xl space-y-6 pb-24 md:pb-0">
                <PageHeader title="My tests" description="Computer-based tests for your class. Open one when it says “Open now”." />
                <Panel flush>
                    {!linked || tests.length === 0 ? (
                        <div className="px-5 pb-5"><EmptyState icon={MonitorCheck} title={linked ? 'No tests yet' : 'Account not linked'} text={linked ? 'Tests your teachers set will show here.' : 'Ask the school to link your account to your student record.'} /></div>
                    ) : (
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {tests.map(t => {
                                const body = (
                                    <div className="flex flex-wrap items-start justify-between gap-3 px-5 py-3.5">
                                        <div className="min-w-0">
                                            <p className="font-medium text-slate-900 dark:text-white">{t.title}</p>
                                            <p className="text-sm text-slate-500">{t.subject ? `${t.subject} · ` : ''}{t.questions} questions · {t.duration_minutes} minutes</p>
                                            <p className="text-xs text-slate-500">{t.state === 'soon' ? `Opens ${examTime(t.opens_at)}` : t.closes_at ? `Closes ${examTime(t.closes_at)}` : ''}</p>
                                        </div>
                                        <div className="text-right">
                                            <Pill tone={STATE[t.state].tone}>{STATE[t.state].label}</Pill>
                                            {t.score != null && <p className="mt-1 text-sm font-medium tabular-nums">{t.score} / {t.max_score}</p>}
                                        </div>
                                    </div>
                                );
                                return (
                                    <li key={t.id}>
                                        {['open', 'writing', 'done'].includes(t.state)
                                            ? <Link href={`/school/student/cbt/${t.id}`} className="block hover:bg-slate-50 dark:hover:bg-white/[0.03]">{body}</Link>
                                            : body}
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </Panel>
            </div>
        </AppLayout>
    );
}
