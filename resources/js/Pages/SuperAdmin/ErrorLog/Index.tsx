import { useState } from 'react';
import { router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { EmptyState, PageHeader, Panel, Pill, Segmented, StatStrip } from '@/components/app/kit';
import { CheckCircle2, ExternalLink, Search } from 'lucide-react';

interface Group {
    key: string; level: string; class: string | null; count: number; first_seen: string; last_seen: string; message: string; where: string | null; trace: string;
}

type Level = 'problems' | 'errors' | 'warnings' | 'all';

interface Props {
    groups: Group[];
    total: number;
    files: { name: string; size: string; partial: boolean }[];
    filters: { q: string; level: Level };
    trackerUrl: string | null;
}

const tone = (level: string) => (['emergency', 'alert', 'critical', 'error'].includes(level) ? 'bad' : level === 'warning' ? 'warn' : 'neutral') as 'bad' | 'warn' | 'neutral';
const shortClass = (c: string | null) => (c ? c.split('\\').pop() : null);

/** The error log: repeats folded into groups, searchable, with the newest first */
export default function ErrorLogIndex({ groups, total, files, filters, trackerUrl }: Props) {
    const [q, setQ] = useState(filters.q);
    const [open, setOpen] = useState<string | null>(null);

    const go = (next: Partial<{ q: string; level: Level }>) =>
        router.get('/super-admin/error-log', { q: filters.q, level: filters.level, ...next }, { preserveState: true, replace: true });

    return (
        <AppLayout title="Error log">
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader title="Error log" description="What went wrong inside the app, with repeats counted as one line. Read-only: nothing here changes the log."
                    actions={trackerUrl ? <Button variant="outline" size="sm" render={<a href={trackerUrl} target="_blank" rel="noreferrer" />}><ExternalLink /> Open error tracker</Button> : undefined} />

                <StatStrip items={[
                    { label: 'Different problems', value: groups.length, tone: groups.length ? 'warn' : 'good' },
                    { label: 'Times they happened', value: total },
                    { label: 'Log files read', value: files.length, hint: files.map((f) => `${f.name} (${f.size})`).join(', ') || 'none found' },
                ]} />

                {files.some((f) => f.partial) && (
                    <p className="rounded-lg bg-slate-50 p-3 text-[13px] text-slate-600 dark:bg-white/[0.04] dark:text-slate-300">A log file is very large, so only its newest 3 MB was read.</p>
                )}

                <div className="flex flex-wrap items-center gap-3">
                    <form className="relative min-w-0 flex-1 basis-60" onSubmit={(e) => { e.preventDefault(); go({ q }); }}>
                        <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                        <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search the message, class or file" className="pl-9" maxLength={100} />
                    </form>
                    <Segmented<Level> label="Show" value={filters.level} onChange={(level) => go({ level })} options={[
                        { value: 'problems', label: 'Problems' }, { value: 'errors', label: 'Errors' }, { value: 'warnings', label: 'Warnings' }, { value: 'all', label: 'Everything' },
                    ]} />
                </div>

                {groups.length === 0 ? (
                    <Panel><EmptyState icon={CheckCircle2} title="Nothing to show" text={filters.q || filters.level !== 'problems' ? 'No log lines match this search.' : 'No errors or warnings in the log. Good news.'} /></Panel>
                ) : (
                    <div className="space-y-3">
                        {groups.map((g) => (
                            <Panel key={g.key} title={<span className="break-words">{g.message || shortClass(g.class) || '(no message)'}</span>}
                                description={[shortClass(g.class), g.where].filter(Boolean).join(' · ') || undefined}
                                action={<div className="flex items-center gap-2"><Pill tone={tone(g.level)}>{g.level}</Pill><Pill>{g.count}×</Pill></div>}>
                                <p className="text-xs text-slate-500 dark:text-slate-400">First seen {g.first_seen} · last seen {g.last_seen} (server time)</p>
                                {g.trace && (
                                    <>
                                        <Button className="mt-3" variant="outline" size="sm" onClick={() => setOpen(open === g.key ? null : g.key)}>{open === g.key ? 'Hide details' : 'Show details'}</Button>
                                        {open === g.key && <pre className="mt-3 max-h-72 overflow-auto rounded-lg bg-slate-50 p-3 text-xs text-slate-700 dark:bg-white/[0.04] dark:text-slate-300">{g.trace}</pre>}
                                    </>
                                )}
                            </Panel>
                        ))}
                    </div>
                )}

                {!trackerUrl && (
                    <p className="text-[13px] text-slate-500 dark:text-slate-400">
                        Want alerts without opening this page? Set <code>PLATFORM_ERROR_TRACKER_URL</code> in the server settings to your error tracker's address (Sentry is a good fit) and a button appears here.
                    </p>
                )}
            </div>
        </AppLayout>
    );
}
