import { useState } from 'react';
import { router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { EmptyState, PageHeader, Panel, Pill, StatStrip } from '@/components/app/kit';
import { ScanSearch } from 'lucide-react';

type Status = 'ok' | 'warn' | 'bad';
interface Check { key: string; label: string; question: string; status: Status; summary: string; findings: string[]; more: number }
interface Report { checked_at: string; tables: number; summary: Record<Status, number>; checks: Check[]; incomplete: string | null }

const pill = { ok: ['good', 'Clear'], warn: ['warn', 'Needs a look'], bad: ['bad', 'Problem'] } as const;

/** The school-separation self-check: the safety-net tests' questions, asked of the live data. Read-only. */
export default function IsolationCheckIndex({ report }: { report: Report | null }) {
    const [busy, setBusy] = useState(false);
    const run = () => router.post('/super-admin/isolation-check/run', {}, { preserveScroll: true, onStart: () => setBusy(true), onFinish: () => setBusy(false) });

    return (
        <AppLayout title="School separation check">
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader title="School separation check" description="Asks the live data the same questions the safety-net tests ask: is every school's information kept apart? It only reads. It shows counts and record numbers, never anyone's details." />

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="text-sm text-slate-500 dark:text-slate-400">{report ? `Last run ${new Date(report.checked_at).toLocaleString()} · ${report.tables} school-owned tables` : 'Not run yet'}</p>
                    <Button size="sm" onClick={run} disabled={busy}><ScanSearch /> {busy ? 'Checking… this can take a minute' : report ? 'Run the check again' : 'Run the check'}</Button>
                </div>

                {!report ? (
                    <Panel><EmptyState icon={ScanSearch} title="No result yet" text="Press the button to run the check. It is safe to run on the live site at any time." /></Panel>
                ) : (
                    <>
                        <StatStrip items={[
                            { label: 'Overall', value: <span className="text-lg sm:text-xl">{report.summary.bad ? 'Needs fixing' : report.summary.warn ? 'Mostly clear' : 'All clear'}</span>, tone: report.summary.bad ? 'bad' : report.summary.warn ? 'warn' : 'good' },
                            { label: 'Clear', value: report.summary.ok, tone: 'good' },
                            { label: 'Needs a look', value: report.summary.warn, tone: report.summary.warn ? 'warn' : 'default' },
                            { label: 'Problems', value: report.summary.bad, tone: report.summary.bad ? 'bad' : 'default' },
                        ]} />
                        {report.incomplete && <p className="rounded-lg bg-amber-50 p-3 text-[13px] text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">{report.incomplete}</p>}

                        <div className="grid gap-4 lg:grid-cols-2">
                            {report.checks.map((c) => (
                                <Panel key={c.key} title={c.label} description={c.question} action={<Pill tone={pill[c.status][0]}>{pill[c.status][1]}</Pill>}>
                                    <p className="text-sm text-slate-600 dark:text-slate-300">{c.status === 'ok' ? c.summary : null}</p>
                                    {c.findings.length > 0 && (
                                        <ul className="space-y-1.5 text-[13px] text-slate-700 dark:text-slate-200">
                                            {c.findings.map((f) => <li key={f} className="break-words rounded-lg bg-slate-50 p-2.5 dark:bg-white/[0.04]">{f}</li>)}
                                            {c.more > 0 && <li className="text-slate-500">… and {c.more} more</li>}
                                        </ul>
                                    )}
                                </Panel>
                            ))}
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
