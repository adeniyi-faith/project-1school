import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { ClipboardCheck } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { EmptyState, PageHeader, Panel, Pill, Segmented, StatStrip } from '@/components/app/kit';
import { when } from '@/components/support/helpers';

interface Row {
    id: number; name: string; age_days: number; done: number; total: number; missing: string[]; last_login_at: string | null;
    reasons: string[]; stuck: boolean; nudged_at: string | null; can_nudge: boolean;
}
interface Props {
    schools: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
    show: 'stuck' | 'done' | 'all';
    counts: { all: number; stuck: number; done: number; never: number };
    reasons: Record<string, string>;
}

const pageLabel = (label: string) => (/previous/i.test(label) ? 'Previous' : /next/i.test(label) ? 'Next' : label);

export default function Onboarding({ schools, show, counts, reasons }: Props) {
    const [target, setTarget] = useState<Row | null>(null);
    const [message, setMessage] = useState('');
    const [busy, setBusy] = useState(false);

    const openNudge = async (row: Row) => {
        setTarget(row); setMessage('');
        try {
            const res = await fetch(`/super-admin/onboarding/${row.id}/draft`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            setMessage((await res.json()).message ?? '');
        } catch { setMessage(''); }
    };
    const send = () => {
        if (!target) return;
        setBusy(true);
        router.post(`/super-admin/onboarding/${target.id}/nudge`, { message }, { preserveScroll: true, onSuccess: () => setTarget(null), onFinish: () => setBusy(false) });
    };

    return (
        <AppLayout title="Onboarding">
            <div className="space-y-5">
                <PageHeader title="Onboarding tracker" description="How far each active school has got with setting up, and which ones look stuck. Nothing is sent unless you press Nudge." />

                <StatStrip items={[
                    { label: 'Active schools', value: counts.all },
                    { label: 'Look stuck', value: counts.stuck },
                    { label: 'Never signed in', value: counts.never },
                    { label: 'Fully set up', value: counts.done },
                ]} />

                <Segmented label="Show" value={show} onChange={(v) => router.get('/super-admin/onboarding', { show: v }, { preserveState: true, replace: true })} options={[
                    { value: 'stuck', label: 'Stuck', count: counts.stuck }, { value: 'all', label: 'All', count: counts.all }, { value: 'done', label: 'Fully set up', count: counts.done },
                ]} />

                <Panel flush>
                    {schools.data.length === 0 ? (
                        <EmptyState icon={ClipboardCheck} title={show === 'stuck' ? 'No school looks stuck' : 'Nothing to show'} text={show === 'stuck' ? 'Every active school is signing in and setting up on time.' : undefined} />
                    ) : (
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {schools.data.map((r) => (
                                <li key={r.id} className="px-4 py-4 sm:px-5">
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div className="min-w-0 grow basis-60">
                                            <Link href={`/super-admin/schools/${r.id}`} className="text-sm font-medium text-slate-900 hover:text-indigo-600 dark:text-white break-words">{r.name}</Link>
                                            <p className="mt-0.5 text-xs text-slate-500">Joined {r.age_days} day{r.age_days === 1 ? '' : 's'} ago · last sign-in {r.last_login_at ? when(r.last_login_at) : 'never'}</p>
                                            <div className="mt-2 flex items-center gap-2">
                                                <div className="h-2 w-32 max-w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800" role="progressbar" aria-valuenow={r.done} aria-valuemin={0} aria-valuemax={r.total} aria-label="Setup progress">
                                                    <div className="h-full rounded-full bg-indigo-500" style={{ width: `${(r.done / r.total) * 100}%` }} />
                                                </div>
                                                <span className="text-xs tabular-nums text-slate-500">{r.done} of {r.total} steps</span>
                                            </div>
                                            {r.missing.length > 0 && <p className="mt-1.5 text-xs text-slate-500">Still to do: {r.missing.slice(0, 3).join('; ')}{r.missing.length > 3 ? `; and ${r.missing.length - 3} more` : ''}</p>}
                                        </div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            {r.reasons.map((k) => <Pill key={k} tone="warn">{reasons[k] ?? k}</Pill>)}
                                            {!r.stuck && r.done === r.total && <Pill tone="good">Fully set up</Pill>}
                                        </div>
                                    </div>
                                    <div className="mt-3 flex flex-wrap items-center gap-3">
                                        <Button size="sm" variant="outline" disabled={!r.can_nudge} onClick={() => openNudge(r)}>Nudge</Button>
                                        {r.nudged_at && <span className="text-xs text-slate-500">Last nudged {when(r.nudged_at)}{!r.can_nudge ? ' (wait a few days before another)' : ''}</span>}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </Panel>

                {schools.links.length > 3 && (
                    <nav className="flex flex-wrap gap-1" aria-label="Pages">
                        {schools.links.map((l, i) => l.url ? <Link key={i} href={l.url} preserveScroll className={`px-3 py-1.5 rounded-md text-sm ${l.active ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-white/[0.06]'}`}>{pageLabel(l.label)}</Link> : null)}
                    </nav>
                )}
            </div>

            <Dialog open={!!target} onOpenChange={(o) => { if (!o) setTarget(null); }}>
                <DialogContent>
                    <DialogHeader><DialogTitle>Nudge {target?.name}</DialogTitle></DialogHeader>
                    <p className="text-sm text-slate-500">This goes as a bell notice and an email to the school's admins and principals. You can change the words.</p>
                    <label htmlFor="nudge" className="sr-only">Message</label>
                    <Textarea id="nudge" rows={6} value={message} maxLength={1000} onChange={(e) => setMessage(e.target.value)} />
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setTarget(null)}>Cancel</Button>
                        <Button type="button" disabled={busy || message.trim().length < 10} onClick={send} className="bg-indigo-600 text-white hover:bg-indigo-700">Send nudge</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
