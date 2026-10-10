import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { EmptyState, PageHeader, Panel, Pill, Segmented } from '@/components/app/kit';
import { naira } from '@/lib/format';
import { CreditCard, RefreshCw, Settings } from 'lucide-react';

type Status = 'pending' | 'success' | 'failed' | 'needs_review';

interface Row {
    id: number; reference: string; amount: number; status: Status; channel: string | null; problem: string | null;
    student: string | null; admission_no: string | null; invoice_id: number; invoice_no: string | null; payer: string | null; email: string;
    started_at: string | null; paid_at: string | null;
}

interface Props {
    payments: { data: Row[]; meta: { total: number; from: number | null; to: number | null }; links: { prev: string | null; next: string | null } };
    status: string | null;
    counts: Partial<Record<Status, number>>;
    setup: { enabled: boolean; public_key: string; secret_hint: string | null; webhook_url: string | null };
    canSetup: boolean;
    canCheck: boolean;
}

const STATUS: Record<Status, { label: string; tone: 'good' | 'warn' | 'bad' | 'neutral' }> = {
    success: { label: 'Paid', tone: 'good' },
    pending: { label: 'Not confirmed', tone: 'neutral' },
    failed: { label: 'Did not go through', tone: 'bad' },
    needs_review: { label: 'Needs checking', tone: 'warn' },
};

const when = (iso: string | null) => (iso ? new Date(iso).toLocaleString('en-NG', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' }) : '–');

export default function OnlinePayments({ payments, status, counts, setup, canSetup, canCheck }: Props) {
    const [settingUp, setSettingUp] = useState(false);
    const filter = (s: string) => router.get('/school/fees/online-payments', s === 'all' ? {} : { status: s }, { preserveScroll: true });

    return (
        <AppLayout breadcrumbs={[{ label: 'Fees' }, { label: 'Online payments' }]}>
            <div className="mx-auto max-w-6xl space-y-6 pb-24 md:pb-0">
                <PageHeader
                    title="Online payments"
                    description="Fees parents pay from their portal through Paystack. A payment goes on the invoice by itself once Paystack confirms the money."
                    actions={canSetup && <Button variant="outline" onClick={() => setSettingUp(true)}><Settings className="size-4" /> Paystack set-up</Button>}
                />

                {!setup.enabled && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                        Online payment is off, so parents do not see a Pay button.{' '}
                        {canSetup ? <button type="button" className="font-medium underline" onClick={() => setSettingUp(true)}>Connect your school's Paystack account</button> : 'Ask the school admin to connect Paystack.'}
                    </div>
                )}

                <Segmented label="Show" value={status ?? 'all'} onChange={filter} options={[
                    { value: 'all', label: 'All' },
                    { value: 'success', label: 'Paid', count: counts.success ?? 0 },
                    { value: 'needs_review', label: 'Needs checking', count: counts.needs_review ?? 0 },
                    { value: 'pending', label: 'Not confirmed', count: counts.pending ?? 0 },
                    { value: 'failed', label: 'Did not go through', count: counts.failed ?? 0 },
                ]} />

                <Panel flush>
                    {payments.data.length === 0 ? (
                        <div className="px-5 pb-5"><EmptyState icon={CreditCard} title="No online payments yet" text="Payments parents make online will show here." /></div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="text-left text-xs text-slate-500">
                                    <tr className="border-b border-slate-100 dark:border-white/[0.06]">
                                        <th className="px-5 py-2 font-medium">Student</th>
                                        <th className="px-3 py-2 font-medium">Invoice</th>
                                        <th className="px-3 py-2 text-right font-medium">Amount</th>
                                        <th className="px-3 py-2 font-medium">Status</th>
                                        <th className="px-3 py-2 font-medium">Paid by</th>
                                        <th className="px-3 py-2 font-medium">When</th>
                                        {canCheck && <th className="px-5 py-2" />}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                                    {payments.data.map(p => (
                                        <tr key={p.id} className="align-top">
                                            <td className="px-5 py-2.5"><p className="font-medium text-slate-900 dark:text-white">{p.student ?? '–'}</p><p className="text-xs text-slate-500">{p.admission_no}</p></td>
                                            <td className="px-3 py-2.5"><Link href={`/school/fees/invoices/${p.invoice_id}`} className="text-indigo-600 hover:underline dark:text-indigo-400">{p.invoice_no}</Link><p className="font-mono text-[11px] text-slate-500">{p.reference}</p></td>
                                            <td className="px-3 py-2.5 text-right font-medium tabular-nums">{naira(p.amount, { kobo: true })}</td>
                                            <td className="px-3 py-2.5">
                                                <Pill tone={STATUS[p.status].tone}>{STATUS[p.status].label}</Pill>
                                                {p.channel && <p className="mt-1 text-xs capitalize text-slate-500">{p.channel.replace('_', ' ')}</p>}
                                                {p.problem && p.status !== 'success' && <p className="mt-1 max-w-56 text-xs text-slate-500">{p.problem}</p>}
                                            </td>
                                            <td className="px-3 py-2.5"><p>{p.payer ?? '–'}</p><p className="text-xs text-slate-500">{p.email}</p></td>
                                            <td className="px-3 py-2.5 text-xs text-slate-500">{p.paid_at ? `Paid ${when(p.paid_at)}` : `Started ${when(p.started_at)}`}</td>
                                            {canCheck && (
                                                <td className="px-5 py-2.5 text-right">
                                                    {p.status === 'pending' && (
                                                        <Button variant="ghost" size="sm" title="Ask Paystack again" onClick={() => router.post(`/school/fees/online-payments/${p.id}/check`, {}, { preserveScroll: true })}>
                                                            <RefreshCw className="size-4" /> Check
                                                        </Button>
                                                    )}
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    {(payments.links.prev || payments.links.next) && (
                        <div className="flex items-center justify-between border-t border-slate-100 px-5 py-3 text-sm dark:border-white/[0.06]">
                            <span className="text-slate-500">{payments.meta.from}–{payments.meta.to} of {payments.meta.total}</span>
                            <div className="flex gap-2">
                                <Button variant="outline" size="sm" disabled={!payments.links.prev} onClick={() => payments.links.prev && router.visit(payments.links.prev)}>Previous</Button>
                                <Button variant="outline" size="sm" disabled={!payments.links.next} onClick={() => payments.links.next && router.visit(payments.links.next)}>Next</Button>
                            </div>
                        </div>
                    )}
                </Panel>
            </div>
            {settingUp && <SetupDialog setup={setup} onClose={() => setSettingUp(false)} />}
        </AppLayout>
    );
}

function SetupDialog({ setup, onClose }: { setup: Props['setup']; onClose: () => void }) {
    const form = useForm({ public_key: setup.public_key, secret_key: '', enabled: setup.enabled || !setup.secret_hint });
    const errors = form.errors as Record<string, string | undefined>;
    return (
        <Dialog open onOpenChange={o => !o && onClose()}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader><DialogTitle>Connect Paystack</DialogTitle></DialogHeader>
                <form onSubmit={e => { e.preventDefault(); form.post('/school/fees/online-payments/setup', { preserveScroll: true, onSuccess: onClose }); }} className="space-y-4">
                    <ol className="list-decimal space-y-1 pl-5 text-sm text-slate-600 dark:text-slate-300">
                        <li>Log in to your school's Paystack account at paystack.com (the money goes straight to its bank account).</li>
                        <li>Open Settings, then <span className="font-medium">API Keys &amp; Webhooks</span>, and copy the two keys below.</li>
                        <li>On the same page, paste this into <span className="font-medium">Live Webhook URL</span>, so payments are confirmed even if a parent closes the page early:</li>
                    </ol>
                    {setup.webhook_url && <p className="break-all rounded-lg bg-slate-100 px-3 py-2 font-mono text-xs dark:bg-white/[0.06]">{setup.webhook_url}</p>}
                    <div className="space-y-1.5">
                        <Label htmlFor="public_key">Public key</Label>
                        <Input id="public_key" value={form.data.public_key} onChange={e => form.setData('public_key', e.target.value.trim())} placeholder="pk_live_..." autoComplete="off" className="font-mono" />
                        {errors.public_key && <p className="text-xs text-red-500">{errors.public_key}</p>}
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="secret_key">Secret key</Label>
                        <Input id="secret_key" type="password" value={form.data.secret_key} onChange={e => form.setData('secret_key', e.target.value.trim())} placeholder={setup.secret_hint ? `Saved (${setup.secret_hint}). Leave empty to keep it.` : 'sk_live_...'} autoComplete="off" className="font-mono" />
                        <p className="text-xs text-slate-500">Kept encrypted and never shown again. Keys starting with pk_test_ and sk_test_ let you try it without real money.</p>
                        {errors.secret_key && <p className="text-xs text-red-500">{errors.secret_key}</p>}
                    </div>
                    <label className="flex items-center gap-2 text-sm"><Checkbox checked={form.data.enabled} onCheckedChange={v => form.setData('enabled', !!v)} /> Let parents pay online</label>
                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onClose}>Cancel</Button>
                        <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">{form.processing ? 'Checking with Paystack…' : 'Save and check'}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
