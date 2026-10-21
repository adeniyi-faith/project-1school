import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { PageHeader, Panel, Pill, StatStrip } from '@/components/app/kit';
import { RefreshCw } from 'lucide-react';

interface TableRow { name: string; rows: number; size: number | null; per_school: boolean }
interface Props {
    counted_at: string;
    totals: { tables: number; rows: number; size: number };
    tables: TableRow[];
    schools: { id: number; name: string; rows: number }[];
    chosen: { id: number; name: string; tables: { name: string; rows: number }[] } | null;
    slow: { hash: string; sql: string; times: number; worst_ms: number; avg_ms: number; last_seen: string; path: string | null }[];
    slow_limit_ms: number;
}

const num = (n: number) => n.toLocaleString();
const bytes = (b: number) => (b >= 1073741824 ? `${(b / 1073741824).toFixed(1)} GB` : b >= 1048576 ? `${(b / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.round(b / 1024))} KB`);

/** A horizontal bar showing a row count against the biggest one on the list */
function Bar({ value, max }: { value: number; max: number }) {
    return (
        <div className="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-white/[0.06]">
            <div className="h-full rounded-full bg-indigo-500/70" style={{ width: `${max ? Math.max(2, (value / max) * 100) : 0}%` }} />
        </div>
    );
}

/** Database facts: size, where the records are, and the slowest questions. Read-only. */
export default function DatabaseFactsIndex({ counted_at, totals, tables, schools, chosen, slow, slow_limit_ms }: Props) {
    const maxTable = tables[0]?.rows ?? 0;
    const maxSchool = schools[0]?.rows ?? 0;

    return (
        <AppLayout title="Database facts">
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader title="Database facts" description="How big the database is and where the records are. Read-only." />

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="text-sm text-slate-500 dark:text-slate-400">Counted {new Date(counted_at).toLocaleString()} (counts are kept for 10 minutes)</p>
                    <Button variant="outline" size="sm" onClick={() => router.get('/super-admin/database', { refresh: 1 }, { preserveScroll: true })}><RefreshCw /> Count again</Button>
                </div>

                <StatStrip items={[
                    { label: 'Tables', value: num(totals.tables) },
                    { label: 'Records', value: num(totals.rows) },
                    { label: 'Size on disk', value: totals.size ? bytes(totals.size) : 'Not shown', hint: totals.size ? undefined : 'This database does not report sizes' },
                ]} />

                <div className="grid gap-4 lg:grid-cols-2">
                    <Panel title="Biggest tables" description="By number of records.">
                        <ul className="space-y-3">
                            {tables.map((t) => (
                                <li key={t.name}>
                                    <div className="flex items-baseline justify-between gap-3 text-sm">
                                        <span className="min-w-0 break-all font-medium text-slate-900 dark:text-white">{t.name}</span>
                                        <span className="shrink-0 tabular-nums text-slate-600 dark:text-slate-300">{num(t.rows)}{t.size ? <span className="text-xs text-slate-400"> · {bytes(t.size)}</span> : null}</span>
                                    </div>
                                    <Bar value={t.rows} max={maxTable} />
                                </li>
                            ))}
                        </ul>
                    </Panel>

                    <Panel title="Biggest schools" description="By records they own. Tap a school to see which tables hold them.">
                        {schools.length === 0 ? <p className="text-sm text-slate-500">No school has any records yet.</p> : (
                            <ul className="space-y-3">
                                {schools.map((s) => (
                                    <li key={s.id}>
                                        <Link href={`/super-admin/database?school=${s.id}`} preserveScroll className="block rounded-md hover:bg-slate-50 dark:hover:bg-white/[0.04]">
                                            <div className="flex items-baseline justify-between gap-3 text-sm">
                                                <span className="min-w-0 break-words font-medium text-slate-900 dark:text-white">{s.name}</span>
                                                <span className="shrink-0 tabular-nums text-slate-600 dark:text-slate-300">{num(s.rows)}</span>
                                            </div>
                                            <Bar value={s.rows} max={maxSchool} />
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Panel>
                </div>

                {chosen && (
                    <Panel title={`${chosen.name}: records by table`} action={<Link href="/super-admin/database" className="text-sm text-indigo-600 hover:underline dark:text-indigo-400">Close</Link>}>
                        {chosen.tables.length === 0 ? <p className="text-sm text-slate-500">This school has no records yet.</p> : (
                            <dl className="divide-y divide-slate-100 text-sm dark:divide-white/[0.06]">
                                {chosen.tables.map((t) => (
                                    <div key={t.name} className="flex items-baseline justify-between gap-3 py-1.5">
                                        <dt className="min-w-0 break-all text-slate-600 dark:text-slate-300">{t.name}</dt>
                                        <dd className="shrink-0 font-medium tabular-nums text-slate-900 dark:text-white">{num(t.rows)}</dd>
                                    </div>
                                ))}
                            </dl>
                        )}
                    </Panel>
                )}

                <Panel title="Slow questions to the database" description={slow_limit_ms > 0 ? `Anything slower than ${slow_limit_ms} ms is written down (the shape of the question only, never the values). Last 7 days.` : 'Recording is switched off (PLATFORM_SLOW_QUERY_MS is 0).'} flush>
                    {slow.length === 0 ? (
                        <p className="px-5 pb-5 text-sm text-slate-500">{slow_limit_ms > 0 ? 'Nothing slow has been seen. Good news.' : 'Nothing recorded.'}</p>
                    ) : (
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {slow.map((q) => (
                                <li key={q.hash} className="px-4 py-3 sm:px-5">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Pill tone={q.worst_ms > 2000 ? 'bad' : 'warn'}>worst {q.worst_ms} ms</Pill>
                                        <Pill>{q.times}×</Pill>
                                        <span className="text-xs text-slate-500">average {q.avg_ms} ms · last {new Date(q.last_seen).toLocaleString()}{q.path ? ` · ${q.path}` : ''}</span>
                                    </div>
                                    <pre className="mt-2 max-h-32 overflow-auto whitespace-pre-wrap break-all rounded-lg bg-slate-50 p-3 text-xs text-slate-700 dark:bg-white/[0.04] dark:text-slate-300">{q.sql}</pre>
                                </li>
                            ))}
                        </ul>
                    )}
                </Panel>
            </div>
        </AppLayout>
    );
}
