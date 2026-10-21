import AppLayout from '@/Layouts/AppLayout';
import { EmptyState, PageHeader, Panel, Pill, StatStrip } from '@/components/app/kit';
import { Timer } from 'lucide-react';

interface Task { name: string; expression: string; next_due: string | null; last_run: string | null; last_status: string | null; last_ms: number | null }
interface Run { id: number; name: string; started_at: string; duration_ms: number | null; status: string; output: string | null }

interface Props {
    heartbeat: string | null;
    alive: boolean;
    tasks: Task[];
    runs: Run[];
    failed_week: number;
}

const when = (iso: string | null) => (iso ? new Date(iso).toLocaleString() : '—');
const took = (ms: number | null) => (ms === null ? '—' : ms < 1000 ? `${ms} ms` : `${(ms / 1000).toFixed(1)} s`);

/** What the scheduler is set to run, when it last ran, and whether the server's cron job is calling it at all */
export default function ScheduledTasksIndex({ heartbeat, alive, tasks, runs, failed_week }: Props) {
    return (
        <AppLayout title="Scheduled tasks">
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader title="Scheduled tasks" description="Jobs the app runs by itself on a timetable. Read-only." />

                <StatStrip items={[
                    { label: 'Server cron job', value: alive ? 'Running' : heartbeat ? 'Stopped' : 'Not seen', tone: alive ? 'good' : 'bad', hint: heartbeat ? `Last call ${when(heartbeat)}` : 'It has never called the scheduler' },
                    { label: 'Scheduled tasks', value: tasks.length },
                    { label: 'Failed this week', value: failed_week, tone: failed_week ? 'bad' : 'good' },
                ]} />

                {!alive && (
                    <p className="rounded-lg bg-amber-50 p-3 text-[13px] text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                        The scheduler is not being called every minute, so nothing scheduled will run. In cPanel, add a cron job that runs <code>php artisan schedule:run</code> in the project folder every minute.
                    </p>
                )}

                <Panel title="What is scheduled" flush>
                    {tasks.length === 0 ? (
                        <EmptyState icon={Timer} title="No tasks are scheduled yet" text="When the app gets timed jobs (reminders, clean-ups), they will be listed here." />
                    ) : (
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {tasks.map((t) => (
                                <li key={t.name} className="flex flex-wrap items-start justify-between gap-x-6 gap-y-1 px-4 py-3 sm:px-5">
                                    <div className="min-w-0">
                                        <p className="break-words text-sm font-medium text-slate-900 dark:text-white">{t.name}</p>
                                        <p className="text-xs text-slate-500">Timetable <code>{t.expression}</code> · next due {when(t.next_due)}</p>
                                    </div>
                                    <div className="text-right text-xs text-slate-500">
                                        {t.last_status ? <Pill tone={t.last_status === 'ok' ? 'good' : 'bad'}>{t.last_status === 'ok' ? 'Last run fine' : 'Last run failed'}</Pill> : <Pill>Not run yet</Pill>}
                                        <p className="mt-1">{when(t.last_run)}</p>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </Panel>

                <Panel title="Recent runs" description="The last 60 runs, kept for two weeks." flush>
                    {runs.length === 0 ? (
                        <p className="px-5 pb-5 text-sm text-slate-500">Nothing has run yet.</p>
                    ) : (
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {runs.map((r) => (
                                <li key={r.id} className="px-4 py-3 sm:px-5">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <p className="min-w-0 break-words text-sm font-medium text-slate-900 dark:text-white">{r.name}</p>
                                        <Pill tone={r.status === 'ok' ? 'good' : 'bad'}>{r.status === 'ok' ? 'Fine' : 'Failed'}</Pill>
                                    </div>
                                    <p className="text-xs text-slate-500">{when(r.started_at)} · took {took(r.duration_ms)}</p>
                                    {r.status !== 'ok' && r.output && <pre className="mt-2 max-h-40 overflow-auto rounded-lg bg-slate-50 p-3 text-xs text-slate-700 dark:bg-white/[0.04] dark:text-slate-300">{r.output}</pre>}
                                </li>
                            ))}
                        </ul>
                    )}
                </Panel>
            </div>
        </AppLayout>
    );
}
