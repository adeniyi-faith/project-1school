import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { EmptyState, PageHeader, Panel, Pill, Segmented } from '@/components/app/kit';
import { naira } from '@/lib/format';
import { CreditCard, RefreshCw, Settings } from 'lucide-react';

type Status = 'pending' | 'success' | 'failed' | 'needs_review';

interface Row {
    id: number; reference: string; gateway: string; gateway_reference: string | null; amount: number; status: Status; channel: string | null; problem: string | null;
    student: string | null; admission_no: string | null; invoice_id: number; invoice_no: string | null; payer: string | null; email: string;
    started_at: string | null; paid_at: string | null;
}

interface Props {
    payments: { data: Row[]; meta: { total: number; from: number | null; to: number | null }; links: { prev: string | null; next: string | null } };
    status: string | null;
    counts: Partial<Record<Status, number>>;
    setup: Setup;
    canSetup: boolean;
    canCheck: boolean;
}

interface ProviderField {
    name: string; label: string; secret: boolean; optional: boolean; placeholder: string; help: string | null;
    options: { value: string; label: string }[] | null; value: string; saved_hint: string | null;
}
interface Provider { key: string; label: string; help: string; webhook_url: string; fields: ProviderField[] }
interface Setup { provider: string | null; enabled: boolean; providers: Provider[] }

const STATUS: Record<Status, { label: string; tone: 'good' | 'warn' | 'bad' | 'neutral' }> = {
    success: { label: 'Paid', tone: 'good' },
    pending: { label: 'Not confirmed', tone: 'neutral' },
    failed: { label: 'Did not go through', tone: 'bad' },
    needs_review: { label: 'Needs checking', tone: 'warn' },
};

const when = (iso: string | null) => (iso ? new Date(iso).toLocaleString('en-NG', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' }) : '–');

export default function OnlinePayments({ payments, status, counts, setup, canSetup, canCheck }: Props) {
    const [settingUp, setSettingUp] = useState(false);
    const current = setup.providers.find(p => p.key === setup.provider);
    const label = (key: string) => setup.providers.find(p => p.key === key)?.label ?? key;
    const filter = (s: string) => router.get('/school/fees/online-payments', s === 'all' ? {} : { status: s }, { preserveScroll: true });

    return (
        <AppLayout breadcrumbs={[{ label: 'Fees' }, { label: 'Online payments' }]}>
            <div className="mx-auto max-w-6xl space-y-6 pb-24 md:pb-0">
                <PageHeader
                    title="Online payments"
                    description={`Fees parents pay from their portal${current ? ` through ${current.label}` : ''}. A payment goes on the invoice by itself once the provider confirms the money.`}
                    actions={canSetup && <Button variant="outline" onClick={() => setSettingUp(true)}><Settings className="size-4" /> Payment provider</Button>}
                />

                {!setup.enabled && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                        Online payment is off, so parents do not see a Pay button.{' '}
                        {canSetup ? <button type="button" className="font-medium underline" onClick={() => setSettingUp(true)}>Choose a provider and connect your school's account</button> : 'Ask the school admin to connect a payment provider.'}
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
                                            <td className="px-3 py-2.5"><Link href={`/school/fees/invoices/${p.invoice_id}`} className="text-indigo-600 hover:underline dark:text-indigo-400">{p.invoice_no}</Link><p className="font-mono text-[11px] text-slate-500">{p.reference}</p><p className="text-[11px] text-slate-500">{label(p.gateway)}{p.gateway_reference ? ` · ${p.gateway_reference}` : ''}</p></td>
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

function SetupDialog({ setup, onClose }: { setup: Setup; onClose: () => void }) {
    const initial = setup.provider ?? setup.providers[0]?.key ?? 'paystack';
    const valuesFor = (key: string) => Object.fromEntries((setup.providers.find(p => p.key === key)?.fields ?? []).map(f => [f.name, f.value || (f.options?.[0]?.value ?? '')]));
    const form = useForm<{ provider: string; keys: Record<string, string>; enabled: boolean }>({ provider: initial, keys: valuesFor(initial), enabled: setup.enabled || !setup.provider });
    const errors = form.errors as Record<string, string | undefined>;
    const provider = setup.providers.find(p => p.key === form.data.provider) ?? setup.providers[0];
    const items = setup.providers.map(p => ({ value: p.key, label: p.label }));
    const setKey = (name: string, value: string) => form.setData('keys', { ...form.data.keys, [name]: value });

    return (
        <Dialog open onOpenChange={o => !o && onClose()}>
            <DialogContent className="max-h-[90dvh] overflow-y-auto sm:max-w-lg">
                <DialogHeader><DialogTitle>Payment provider</DialogTitle></DialogHeader>
                <form onSubmit={e => { e.preventDefault(); form.post('/school/fees/online-payments/setup', { preserveScroll: true, onSuccess: onClose }); }} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label>Provider</Label>
                        <Select items={items} value={form.data.provider} onValueChange={v => v && form.setData({ ...form.data, provider: v, keys: valuesFor(v) })}>
                            <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>{items.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                        </Select>
                        <p className="text-xs text-slate-500">Use the account your school already has. The money goes straight to that account, then to the school's bank.</p>
                    </div>
                    <p className="text-sm text-slate-600 dark:text-slate-300">{provider.help}</p>
                    <div className="space-y-1">
                        <p className="text-xs font-medium text-slate-500">Webhook link</p>
                        <p className="break-all rounded-lg bg-slate-100 px-3 py-2 font-mono text-xs dark:bg-white/[0.06]">{provider.webhook_url}</p>
                    </div>
                    {provider.fields.map(f => (
                        <div key={f.name} className="space-y-1.5">
                            <Label htmlFor={f.name}>{f.label}{f.optional ? ' (optional)' : ''}</Label>
                            {f.options ? (
                                <Select items={f.options} value={form.data.keys[f.name] ?? ''} onValueChange={v => setKey(f.name, v ?? '')}>
                                    <SelectTrigger id={f.name} className="w-full"><SelectValue /></SelectTrigger>
                                    <SelectContent>{f.options.map(o => <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>)}</SelectContent>
                                </Select>
                            ) : (
                                <Input id={f.name} type={f.secret ? 'password' : 'text'} value={form.data.keys[f.name] ?? ''} onChange={e => setKey(f.name, e.target.value.trim())}
                                    placeholder={f.saved_hint ? `Saved (${f.saved_hint}). Leave empty to keep it.` : f.placeholder} autoComplete="off" className="font-mono" />
                            )}
                            {f.help && <p className="text-xs text-slate-500">{f.help}</p>}
                            {errors[`keys.${f.name}`] && <p className="text-xs text-red-500">{errors[`keys.${f.name}`]}</p>}
                        </div>
                    ))}
                    <p className="text-xs text-slate-500">Secret keys are kept encrypted and never shown again.</p>
                    {errors.keys && <p className="text-sm text-red-600">{errors.keys}</p>}
                    <label className="flex items-center gap-2 text-sm"><Checkbox checked={form.data.enabled} onCheckedChange={v => form.setData('enabled', !!v)} /> Let parents pay online with {provider.label}</label>
                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onClose}>Cancel</Button>
                        <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">{form.processing ? `Checking with ${provider.label}…` : 'Save'}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
