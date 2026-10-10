import { useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PageHeader, Panel, StatStrip } from '@/components/app/kit';
import { InvoiceStatus, METHOD_LABELS } from '@/components/fees/InvoiceStatus';
import { naira } from '@/lib/format';
import { cn } from '@/lib/utils';
import { ArrowLeft, Ban, Banknote, CircleAlert, Undo2 } from 'lucide-react';
import type { InvoiceDetail, LedgerLine, LedgerType, PaymentMethod } from '@/Types';

interface Props {
    invoice: InvoiceDetail;
    entries: LedgerLine[];
    can: { collect: boolean; correct: boolean };
}

const TYPE_LABELS: Record<LedgerType, string> = {
    charge: 'Fee charged', fine: 'Fine', payment: 'Payment', discount: 'Scholarship / discount', reversal: 'Reversal',
};
const METHOD_ITEMS = (Object.keys(METHOD_LABELS) as PaymentMethod[]).map(m => ({ value: m, label: METHOD_LABELS[m] }));

type Dialogs = 'pay' | 'fine' | 'void' | null;
type PayForm = { amount: string; method: PaymentMethod; entry_date: string; note: string };

function today() {
    return new Date().toISOString().slice(0, 10);
}

function ErrorText({ children }: { children?: string }) {
    return children ? <p className="text-xs text-red-500">{children}</p> : null;
}

export default function Invoice({ invoice, entries, can }: Props) {
    const [open, setOpen] = useState<Dialogs>(null);
    const [reversing, setReversing] = useState<LedgerLine | null>(null);

    const pay = useForm<PayForm>({ amount: '', method: 'cash', entry_date: today(), note: '' });
    const fine = useForm({ amount: '', note: '' });
    const voidForm = useForm({ reason: '' });
    const reverse = useForm({ reason: '' });

    // A running total after each line, so the record reads like a bank statement
    let running = 0;
    const rows = entries.map(e => ({ ...e, after: (running = Math.round((running + e.amount) * 100) / 100) }));
    const linesById = new Map(entries.map(e => [e.id, e]));

    const close = () => { setOpen(null); setReversing(null); };
    const opts = { preserveScroll: true, onSuccess: close };

    return (
        <AppLayout title={invoice.invoice_no}>
            <div className="space-y-6">
                <Link href="/school/fees/invoices" className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                    <ArrowLeft className="size-4" /> All invoices
                </Link>

                <PageHeader
                    title={invoice.invoice_no}
                    description={<span className="inline-flex items-center gap-2">{invoice.fee} · {invoice.period} <InvoiceStatus status={invoice.status} /></span>}
                    actions={
                        <div className="flex flex-wrap gap-2">
                            {can.collect && invoice.balance > 0 && (
                                <Button onClick={() => { pay.reset(); pay.clearErrors(); pay.setData('amount', String(invoice.balance)); setOpen('pay'); }} className="bg-indigo-600 text-white hover:bg-indigo-700">
                                    <Banknote className="size-4" /> Record payment
                                </Button>
                            )}
                            {can.collect && (
                                <Button variant="outline" onClick={() => { fine.reset(); fine.clearErrors(); setOpen('fine'); }}><CircleAlert className="size-4" /> Add fine</Button>
                            )}
                            {can.correct && invoice.net_paid <= 0 && (
                                <Button variant="outline" onClick={() => { voidForm.reset(); voidForm.clearErrors(); setOpen('void'); }} className="text-red-600"><Ban className="size-4" /> Cancel invoice</Button>
                            )}
                        </div>
                    }
                />

                {invoice.status === 'void' && (
                    <p className="rounded-lg bg-slate-100 px-4 py-3 text-sm text-slate-600 dark:bg-white/[0.06] dark:text-slate-300">
                        This invoice was cancelled{invoice.void_reason ? `: ${invoice.void_reason}` : '.'} Its lines stay below for the record.
                    </p>
                )}

                <StatStrip items={[
                    { label: 'Student', value: invoice.student?.name ?? '—', hint: `${invoice.student?.class ?? ''} · ${invoice.student?.admission_no ?? ''}` },
                    { label: 'Fee', value: naira(invoice.amount), hint: invoice.due_date ? `Due ${invoice.due_date}` : undefined },
                    { label: 'Paid', value: naira(invoice.net_paid), tone: 'good' },
                    { label: 'Still owed', value: naira(invoice.balance), tone: invoice.balance > 0 ? 'bad' : 'good', hint: invoice.guardian ? `Parent: ${invoice.guardian.name}${invoice.guardian.phone ? ` · ${invoice.guardian.phone}` : ''}` : undefined },
                ]} />

                <Panel title="Record" description="Lines are never changed or deleted. A mistake is fixed by reversing the line, which adds its opposite." flush>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-5">Date</TableHead>
                                    <TableHead>What</TableHead>
                                    <TableHead>Details</TableHead>
                                    <TableHead className="text-right">Amount</TableHead>
                                    <TableHead className="text-right">Owed after</TableHead>
                                    <TableHead>By</TableHead>
                                    <TableHead className="pr-5"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rows.map(e => {
                                    const target = e.reverses_id ? linesById.get(e.reverses_id) : undefined;
                                    return (
                                        <TableRow key={e.id} className={cn(e.reversed && 'text-slate-400 line-through decoration-slate-300')}>
                                            <TableCell className="pl-5 text-xs whitespace-nowrap">{e.entry_date}</TableCell>
                                            <TableCell className="font-medium">
                                                {TYPE_LABELS[e.type]}
                                                {target && <span className="block text-xs font-normal text-slate-500">of {TYPE_LABELS[target.type].toLowerCase()}{target.reference ? ` ${target.reference}` : ''}</span>}
                                            </TableCell>
                                            <TableCell className="text-xs text-slate-500">
                                                {[e.method && METHOD_LABELS[e.method], e.reference, e.note].filter(Boolean).join(' · ') || '—'}
                                            </TableCell>
                                            <TableCell className={cn('text-right tabular-nums', e.amount < 0 ? 'text-emerald-700 dark:text-emerald-400' : '')}>
                                                {e.amount < 0 ? `− ${naira(-e.amount, { kobo: true })}` : `+ ${naira(e.amount, { kobo: true })}`}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">{naira(e.after, { kobo: true })}</TableCell>
                                            <TableCell className="text-xs text-slate-500">{e.recorded_by ?? '—'}</TableCell>
                                            <TableCell className="pr-5 text-right">
                                                {can.correct && !e.reversed && e.type !== 'reversal' && (
                                                    <Button variant="ghost" size="sm" onClick={() => { reverse.reset(); reverse.clearErrors(); setReversing(e); }}>
                                                        <Undo2 className="size-3.5" /> Reverse
                                                    </Button>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </div>
                </Panel>
            </div>

            <Dialog open={open === 'pay'} onOpenChange={o => !o && close()}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Record a payment</DialogTitle></DialogHeader>
                    <form onSubmit={e => { e.preventDefault(); pay.post(`/school/fees/invoices/${invoice.id}/payments`, opts); }} className="mt-2 space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="pay-amount">Amount (₦)</Label>
                            <Input id="pay-amount" type="number" min="0.01" step="0.01" max={invoice.balance} value={pay.data.amount} onChange={e => pay.setData('amount', e.target.value)} />
                            <p className="text-xs text-slate-500">Up to {naira(invoice.balance, { kobo: true })} is still owed.</p>
                            <ErrorText>{pay.errors.amount}</ErrorText>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Paid by</Label>
                                <Select items={METHOD_ITEMS} value={pay.data.method} onValueChange={v => v && pay.setData('method', v as PaymentMethod)}>
                                    <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                    <SelectContent>{METHOD_ITEMS.map(m => <SelectItem key={m.value} value={m.value}>{m.label}</SelectItem>)}</SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="pay-date">Date paid</Label>
                                <Input id="pay-date" type="date" max={today()} value={pay.data.entry_date} onChange={e => pay.setData('entry_date', e.target.value)} />
                                <ErrorText>{pay.errors.entry_date}</ErrorText>
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="pay-note">Bank or POS reference (optional)</Label>
                            <Input id="pay-note" value={pay.data.note} onChange={e => pay.setData('note', e.target.value)} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={close}>Cancel</Button>
                            <Button type="submit" disabled={pay.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Record payment</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={open === 'fine'} onOpenChange={o => !o && close()}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Add a fine</DialogTitle></DialogHeader>
                    <form onSubmit={e => { e.preventDefault(); fine.post(`/school/fees/invoices/${invoice.id}/fines`, opts); }} className="mt-2 space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="fine-amount">Amount (₦)</Label>
                            <Input id="fine-amount" type="number" min="0.01" step="0.01" value={fine.data.amount} onChange={e => fine.setData('amount', e.target.value)} />
                            <ErrorText>{fine.errors.amount}</ErrorText>
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="fine-note">Reason</Label>
                            <Input id="fine-note" value={fine.data.note} onChange={e => fine.setData('note', e.target.value)} placeholder="e.g. Paid after the due date" />
                            <ErrorText>{fine.errors.note}</ErrorText>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={close}>Cancel</Button>
                            <Button type="submit" disabled={fine.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Add fine</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={reversing !== null} onOpenChange={o => !o && close()}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Reverse this line</DialogTitle></DialogHeader>
                    {reversing && (
                        <form onSubmit={e => { e.preventDefault(); reverse.post(`/school/fees/invoices/${invoice.id}/entries/${reversing.id}/reverse`, opts); }} className="mt-2 space-y-4">
                            <p className="text-sm text-slate-500">
                                This adds a new line that cancels the {TYPE_LABELS[reversing.type].toLowerCase()} of {naira(Math.abs(reversing.amount), { kobo: true })}. The original line stays in the record, crossed out.
                            </p>
                            <div className="space-y-1.5">
                                <Label htmlFor="rev-reason">Why?</Label>
                                <Textarea id="rev-reason" value={reverse.data.reason} onChange={e => reverse.setData('reason', e.target.value)} placeholder="e.g. Typed 15,000 instead of 150,000" />
                                <ErrorText>{reverse.errors.reason ?? (reverse.errors as Record<string, string>).entry}</ErrorText>
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="ghost" onClick={close}>Cancel</Button>
                                <Button type="submit" disabled={reverse.processing} className="bg-red-600 text-white hover:bg-red-700">Reverse</Button>
                            </DialogFooter>
                        </form>
                    )}
                </DialogContent>
            </Dialog>

            <Dialog open={open === 'void'} onOpenChange={o => !o && close()}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Cancel this invoice</DialogTitle></DialogHeader>
                    <form onSubmit={e => { e.preventDefault(); voidForm.post(`/school/fees/invoices/${invoice.id}/void`, opts); }} className="mt-2 space-y-4">
                        <p className="text-sm text-slate-500">Use this for an invoice made by mistake. It only works while nothing has been paid on it. The invoice and its lines stay in the record.</p>
                        <div className="space-y-1.5">
                            <Label htmlFor="void-reason">Why?</Label>
                            <Textarea id="void-reason" value={voidForm.data.reason} onChange={e => voidForm.setData('reason', e.target.value)} placeholder="e.g. Student does not take the school bus" />
                            <ErrorText>{voidForm.errors.reason ?? (voidForm.errors as Record<string, string>).invoice}</ErrorText>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={close}>Keep it</Button>
                            <Button type="submit" disabled={voidForm.processing} className="bg-red-600 text-white hover:bg-red-700">Cancel invoice</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
