import { useState } from 'react';
import { router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { PageHeader, Panel, Pill, StatStrip } from '@/components/app/kit';
import { RefreshCw } from 'lucide-react';

type Status = 'ok' | 'warn' | 'bad' | 'unknown';

interface Check {
    key: string;
    label: string;
    status: Status;
    summary: string;
    facts: { label: string; value: string }[];
    notes: string[];
}

interface Props {
    report: {
        checks: Check[];
        summary: Record<Status, number>;
        checked_at: string;
    };
}

const pill: Record<Status, { tone: 'good' | 'warn' | 'bad' | 'neutral'; text: string }> = {
    ok: { tone: 'good', text: 'Healthy' },
    warn: { tone: 'warn', text: 'Needs a look' },
    bad: { tone: 'bad', text: 'Problem' },
    unknown: { tone: 'neutral', text: 'Could not check' },
};

/** Platform health: is the database, cache, queue, file storage, disk and PHP working? Read-only. */
export default function HealthIndex({ report }: Props) {
    const [busy, setBusy] = useState(false);
    const { summary } = report;
    const checkedAt = new Date(report.checked_at).toLocaleString();

    function refresh() {
        router.reload({ onStart: () => setBusy(true), onFinish: () => setBusy(false) });
    }

    const headline = summary.bad ? `${summary.bad} problem${summary.bad === 1 ? '' : 's'} to fix` : summary.warn ? `${summary.warn} thing${summary.warn === 1 ? '' : 's'} to look at` : 'Everything is healthy';

    return (
        <AppLayout title="Platform health">
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader title="Platform health" description="A read-only look at the machinery the app runs on. Nothing on this page changes anything." />

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="text-sm text-slate-500 dark:text-slate-400">Checked {checkedAt}</p>
                    <Button type="button" variant="outline" size="sm" onClick={refresh} disabled={busy}>
                        <RefreshCw className={busy ? 'animate-spin' : ''} /> Check again
                    </Button>
                </div>

                <StatStrip
                    items={[
                        { label: 'Overall', value: <span className="text-lg sm:text-xl">{headline}</span>, tone: summary.bad ? 'bad' : summary.warn ? 'warn' : 'good' },
                        { label: 'Healthy', value: summary.ok, tone: 'good' },
                        { label: 'Needs a look', value: summary.warn, tone: summary.warn ? 'warn' : 'default' },
                        { label: 'Problems', value: summary.bad + summary.unknown, tone: summary.bad ? 'bad' : 'default', hint: summary.unknown ? `${summary.unknown} could not be checked` : undefined },
                    ]}
                />

                <div className="grid gap-4 lg:grid-cols-2">
                    {report.checks.map((c) => (
                        <Panel key={c.key} title={c.label} action={<Pill tone={pill[c.status].tone}>{pill[c.status].text}</Pill>} description={c.summary}>
                            {c.facts.length > 0 && (
                                <dl className="divide-y divide-slate-100 text-sm dark:divide-white/[0.06]">
                                    {c.facts.map((f) => (
                                        <div key={f.label} className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-0.5 py-1.5">
                                            <dt className="text-slate-500 dark:text-slate-400">{f.label}</dt>
                                            <dd className="min-w-0 break-words text-right font-medium text-slate-900 dark:text-white">{f.value}</dd>
                                        </div>
                                    ))}
                                </dl>
                            )}
                            {c.notes.length > 0 && (
                                <ul className="mt-3 space-y-1 rounded-lg bg-slate-50 p-3 text-[13px] text-slate-600 dark:bg-white/[0.04] dark:text-slate-300">
                                    {c.notes.map((n) => <li key={n}>{n}</li>)}
                                </ul>
                            )}
                        </Panel>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
