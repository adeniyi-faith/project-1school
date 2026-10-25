import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Check, ShieldCheck, X } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface Row {
    id: number; label: string; target_type: string | null; target: string | null; reason: string; by: string | null; mine: boolean;
    status: string; at: string | null; decided_by: string | null; decided_at: string | null; note: string | null; expires_at: string | null;
}
interface Props { switched_on: boolean; waiting: Row[]; recent: Row[] }

const when = (iso: string | null) => (iso ? new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' }) : '');

const pill: Record<string, string> = {
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400',
    used: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    denied: 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-400',
    expired: 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
};
const words: Record<string, string> = { approved: 'Approved, not used yet', used: 'Approved and done', denied: 'Turned down', expired: 'Ran out of time' };

export default function ApprovalsIndex({ switched_on, waiting, recent }: Props) {
    const [notes, setNotes] = useState<Record<number, string>>({});
    const answer = (row: Row, yes: boolean) =>
        router.post(`/super-admin/approvals/${row.id}/${yes ? 'approve' : 'deny'}`, { note: notes[row.id] ?? '' }, { preserveScroll: true });

    return (
        <AppLayout breadcrumbs={[{ label: 'Super Admin' }, { label: 'Approvals' }]}>
            <Head title="Approvals" />

            <div className="mb-6">
                <h1 className="text-xl font-bold text-slate-900 dark:text-white">Approvals</h1>
                <p className="mt-0.5 max-w-2xl text-sm text-slate-500 dark:text-slate-400">
                    Risky actions by platform staff, like deleting a school, wait here for a second person to say yes. An approval lets that one person do that one thing, once, within a day.
                </p>
            </div>

            {!switched_on && (
                <div className="mb-4 rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                    Second approval is switched off on this site, so staff only have to write a reason. To turn it on, set <code>PLATFORM_SECOND_APPROVAL=true</code>.
                </div>
            )}

            <h2 className="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-200">Waiting for an answer ({waiting.length})</h2>
            {waiting.length === 0 ? (
                <div className="mb-8 rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-slate-700">
                    <ShieldCheck className="mx-auto mb-2 size-6 text-slate-400" /> Nothing is waiting.
                </div>
            ) : (
                <div className="mb-8 space-y-3">
                    {waiting.map((r) => (
                        <div key={r.id} className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p className="font-semibold text-slate-900 dark:text-white">{r.label}{r.target ? `: ${r.target}` : ''}</p>
                                    <p className="text-xs text-slate-500">Asked by {r.by} on {when(r.at)}</p>
                                </div>
                            </div>
                            <p className="mt-2 rounded-lg bg-slate-50 p-3 text-sm text-slate-700 dark:bg-slate-800/60 dark:text-slate-200"><span className="font-medium">Reason:</span> {r.reason}</p>
                            {r.mine ? (
                                <p className="mt-3 text-sm text-slate-500">This is your own request, so someone else has to answer it.</p>
                            ) : (
                                <div className="mt-3 flex flex-wrap items-center gap-2">
                                    <Input className="min-w-48 flex-1" placeholder="Note (optional)" value={notes[r.id] ?? ''} onChange={(e) => setNotes({ ...notes, [r.id]: e.target.value })} />
                                    <Button className="bg-emerald-600 text-white hover:bg-emerald-700" onClick={() => answer(r, true)}><Check className="mr-1 size-4" /> Approve</Button>
                                    <Button variant="outline" className="text-red-600" onClick={() => answer(r, false)}><X className="mr-1 size-4" /> Turn down</Button>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}

            <h2 className="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-200">Answered lately</h2>
            {recent.length === 0 ? (
                <p className="text-sm text-slate-500">Nothing yet.</p>
            ) : (
                <div className="space-y-2">
                    {recent.map((r) => (
                        <div key={r.id} className="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-900">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <p className="font-medium text-slate-900 dark:text-white">{r.label}{r.target ? `: ${r.target}` : ''}</p>
                                <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${pill[r.status] ?? pill.used}`}>{words[r.status] ?? r.status}</span>
                            </div>
                            <p className="mt-1 text-xs text-slate-500">
                                Asked by {r.by}{r.decided_by ? `, answered by ${r.decided_by} on ${when(r.decided_at)}` : ''}. Reason: {r.reason}{r.note ? ` Note: ${r.note}` : ''}
                            </p>
                        </div>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
