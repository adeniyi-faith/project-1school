import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { PageHeader, Panel, Pill } from '@/components/app/kit';
import { Play } from 'lucide-react';

interface Command { key: string; label: string; what: string; changes: boolean; note: string | null; enabled: boolean }
interface Run { id: number; command: string; label: string; by: string; reason: string | null; status: string; duration_ms: number; output: string | null; at: string }

/** A short fixed list of maintenance commands. Nothing is typed in; each run is saved with who, why and the output. */
export default function CommandsIndex({ commands, runs }: { commands: Command[]; runs: Run[] }) {
    const [asking, setAsking] = useState<Command | null>(null);
    const [open, setOpen] = useState<number | null>(null);
    const [busy, setBusy] = useState<string | null>(null);
    const form = useForm({ reason: '' });

    const done = { preserveScroll: true, onFinish: () => setBusy(null) };

    function start(c: Command) {
        if (c.changes) { setAsking(c); return; }
        setBusy(c.key);
        router.post(`/super-admin/commands/${c.key}/run`, {}, done);
    }

    function confirm(e: React.FormEvent) {
        e.preventDefault();
        if (!asking) return;
        setBusy(asking.key);
        form.post(`/super-admin/commands/${asking.key}/run`, { ...done, onSuccess: () => { setAsking(null); form.reset(); } });
    }

    return (
        <AppLayout title="Safe commands">
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader title="Safe commands" description="A short, fixed list of maintenance jobs. You cannot type your own. Every run is saved with who ran it, why, and what it printed." />

                <div className="grid gap-4 md:grid-cols-2">
                    {commands.map((c) => (
                        <Panel key={c.key} title={c.label} action={<Pill tone={c.changes ? 'warn' : 'good'}>{c.changes ? 'Changes things' : 'Look only'}</Pill>}>
                            <p className="text-sm text-slate-600 dark:text-slate-300">{c.what}</p>
                            {c.note && <p className="mt-2 text-[13px] text-slate-500 dark:text-slate-400">{c.note}</p>}
                            <div className="mt-4">
                                <Button size="sm" variant="outline" disabled={!c.enabled || busy !== null} onClick={() => start(c)}>
                                    <Play /> {busy === c.key ? 'Running…' : 'Run'}
                                </Button>
                                {!c.enabled && <span className="ml-3 text-xs text-slate-500">Switched off in this environment</span>}
                            </div>
                        </Panel>
                    ))}
                </div>

                <Panel title="Recent runs" description="The last 25 runs." flush>
                    {runs.length === 0 ? (
                        <p className="px-5 pb-5 text-sm text-slate-500">Nothing has been run yet.</p>
                    ) : (
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {runs.map((r) => (
                                <li key={r.id} className="px-4 py-3 sm:px-5">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <p className="min-w-0 break-words text-sm font-medium text-slate-900 dark:text-white">{r.label}</p>
                                        <Pill tone={r.status === 'ok' ? 'good' : 'bad'}>{r.status === 'ok' ? 'Finished' : 'Failed'}</Pill>
                                    </div>
                                    <p className="text-xs text-slate-500">{new Date(r.at).toLocaleString()} · {r.by} · took {r.duration_ms < 1000 ? `${r.duration_ms} ms` : `${(r.duration_ms / 1000).toFixed(1)} s`}</p>
                                    {r.reason && <p className="mt-1 break-words text-[13px] text-slate-600 dark:text-slate-300">Reason: {r.reason}</p>}
                                    {r.output && (
                                        <>
                                            <Button className="mt-2" variant="outline" size="sm" onClick={() => setOpen(open === r.id ? null : r.id)}>{open === r.id ? 'Hide output' : 'Show output'}</Button>
                                            {open === r.id && <pre className="mt-2 max-h-80 overflow-auto rounded-lg bg-slate-50 p-3 text-xs text-slate-700 dark:bg-white/[0.04] dark:text-slate-300">{r.output}</pre>}
                                        </>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </Panel>
            </div>

            <Dialog open={asking !== null} onOpenChange={(o) => { if (!o) { setAsking(null); form.reset(); } }}>
                <DialogContent className="sm:max-w-md">
                    <form onSubmit={confirm} className="space-y-4">
                        <DialogHeader>
                            <DialogTitle>{asking?.label}</DialogTitle>
                            <DialogDescription>{asking?.what}</DialogDescription>
                        </DialogHeader>
                        <div className="space-y-1.5">
                            <Label htmlFor="reason">Why are you running this?</Label>
                            <Textarea id="reason" rows={3} value={form.data.reason} onChange={(e) => form.setData('reason', e.target.value)} placeholder="For example: new permission not showing for principals" />
                            {form.errors.reason && <p className="text-xs text-red-500">{form.errors.reason}</p>}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setAsking(null)}>Cancel</Button>
                            <Button type="submit" disabled={form.processing}>Run it</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
