import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Banknote, AlertTriangle, CheckCircle, CreditCard, Lock } from 'lucide-react';
import { InstalmentTable, shortDate } from '@/components/fees/Instalments';
import { naira } from '@/lib/format';
import type { InstalmentSummary } from '@/Types';

interface Payment { id: number; month: string; due: number; paid: number; balance: number; status: string; payment_date: string | null; due_date: string | null; instalments: InstalmentSummary | null; }
interface Child { id: number; full_name: string; class: string | null; total_due: number; total_paid: number; balance: number; payments: Payment[]; }
interface Props { linked: boolean; guardian: { name: string } | null; children: Child[]; payOnline?: boolean; }

const statusColor: Record<string, string> = {
    paid: 'bg-green-100 text-green-700', partial: 'bg-amber-100 text-amber-700',
    unpaid: 'bg-red-100 text-red-700',   overdue: 'bg-red-100 text-red-700',
};

export default function ParentFees({ linked, children, payOnline = false }: Props) {
    const [paying, setPaying] = useState<{ child: string; row: Payment } | null>(null);
    if (!linked) {
        return (
            <AppLayout title="Fee Status">
                <div className="flex flex-col items-center justify-center py-24 text-center">
                    <Banknote className="w-14 h-14 text-slate-300 mb-4" />
                    <h2 className="text-lg font-semibold text-slate-600 dark:text-slate-400">Account not linked</h2>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout title="Fee Status">
            <div className="space-y-8">
                <h1 className="text-xl font-bold text-slate-900 dark:text-white">Children Fee Status</h1>
                {children.map(child => (
                    <div key={child.id}>
                        <p className="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">{child.full_name} — {child.class}</p>
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                            <Card><CardContent className="p-3 text-center">
                                <p className="text-[11px] text-slate-500 mb-1">Total Due</p>
                                <p className="font-bold text-slate-800 dark:text-white">₦{child.total_due.toLocaleString()}</p>
                            </CardContent></Card>
                            <Card><CardContent className="p-3 text-center">
                                <p className="text-[11px] text-slate-500 mb-1">Total Paid</p>
                                <p className="font-bold text-green-600">₦{child.total_paid.toLocaleString()}</p>
                            </CardContent></Card>
                            <Card className={child.balance > 0 ? 'border-red-200' : 'border-green-200'}>
                                <CardContent className="p-3 text-center">
                                    <p className="text-[11px] text-slate-500 mb-1">Balance</p>
                                    <p className={cn('font-bold', child.balance > 0 ? 'text-red-600' : 'text-green-600')}>
                                        {child.balance > 0 ? `₦${child.balance.toLocaleString()}` : 'Clear'}
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                        <Card>
                            <CardContent className="p-0">
                                {child.payments.length === 0 ? (
                                    <p className="py-6 text-center text-slate-400 text-sm">No payments recorded.</p>
                                ) : (
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="text-xs text-slate-500 border-b dark:border-slate-700 text-left">
                                                <th className="px-4 py-2 font-medium">For</th>
                                                <th className="px-4 py-2 font-medium text-right">Due</th>
                                                <th className="px-4 py-2 font-medium text-right">Paid</th>
                                                <th className="px-4 py-2 font-medium text-center">Status</th>
                                                <th className="px-4 py-2 font-medium text-right hidden md:table-cell">Date</th>
                                                {payOnline && <th className="px-4 py-2" />}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                            {child.payments.map((p, i) => (
                                                <tr key={i}>
                                                    <td className="px-4 py-2 text-slate-700 dark:text-slate-300">
                                                        {p.month}
                                                        <PartsLine p={p} />
                                                    </td>
                                                    <td className="px-4 py-2 text-right text-slate-600">₦{p.due.toLocaleString()}</td>
                                                    <td className="px-4 py-2 text-right text-green-600">₦{p.paid.toLocaleString()}</td>
                                                    <td className="px-4 py-2 text-center">
                                                        <span className={cn('text-[10px] font-medium px-2 py-0.5 rounded-full capitalize', statusColor[p.status] ?? 'bg-slate-100 text-slate-600')}>{p.status}</span>
                                                    </td>
                                                    <td className="px-4 py-2 text-right text-slate-400 text-xs hidden md:table-cell">{p.payment_date ?? '—'}</td>
                                                    {payOnline && (
                                                        <td className="px-4 py-2 text-right">
                                                            {p.balance > 0 && (
                                                                <Button size="sm" onClick={() => setPaying({ child: child.full_name, row: p })} className="bg-emerald-600 text-white hover:bg-emerald-700">
                                                                    <CreditCard className="size-4" /> Pay
                                                                </Button>
                                                            )}
                                                        </td>
                                                    )}
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                ))}
            </div>
            {paying && <PayDialog child={paying.child} row={paying.row} onClose={() => setPaying(null)} />}
        </AppLayout>
    );
}

/** Under the fee's name: what is overdue, or the next part and its date */
function PartsLine({ p }: { p: Payment }) {
    const s = p.instalments;
    if (!s || p.balance <= 0) return null;
    if (s.overdue > 0) return <span className="block text-xs text-red-600">{naira(s.overdue, { kobo: true })} overdue · {s.parts.length} parts</span>;
    if (s.next) return <span className="block text-xs text-slate-500">Next: {naira(s.next.left, { kobo: true })} by {shortDate(s.next.due_date)} ({s.next.label})</span>;
    return null;
}

function PayDialog({ child, row, onClose }: { child: string; row: Payment; onClose: () => void }) {
    // With parts, start from what is due now (or the next part), not the whole balance
    const s = row.instalments;
    const suggested = s ? (s.due_now > 0 ? s.due_now : (s.next?.left ?? row.balance)) : row.balance;
    const form = useForm({ amount: String(Math.min(suggested, row.balance)) });
    return (
        <Dialog open onOpenChange={o => !o && onClose()}>
            <DialogContent className={s ? 'sm:max-w-lg' : 'sm:max-w-sm'}>
                <DialogHeader><DialogTitle>Pay online</DialogTitle></DialogHeader>
                <form onSubmit={e => { e.preventDefault(); form.post(`/school/parent/fees/invoices/${row.id}/pay`); }} className="space-y-4">
                    <p className="text-sm text-slate-600 dark:text-slate-300">{child}: {row.month}. Still owed: <span className="font-medium">₦{row.balance.toLocaleString()}</span>.</p>
                    {s && <div className="overflow-x-auto"><InstalmentTable summary={s} /></div>}
                    <div className="space-y-1.5">
                        <Label htmlFor="amount">Amount to pay (₦)</Label>
                        <Input id="amount" type="number" min={1} max={row.balance} step="0.01" value={form.data.amount} onChange={e => form.setData('amount', e.target.value)} />
                        <p className="text-xs text-slate-500">{s ? 'This fee is paid in parts. You can pay one part or more now.' : 'You can pay part now and the rest later.'}</p>
                        {form.errors.amount && <p className="text-xs text-red-500">{form.errors.amount}</p>}
                    </div>
                    <p className="flex items-start gap-2 text-xs text-slate-500"><Lock className="mt-0.5 size-3.5 shrink-0" /> You will pay on the school's secure payment page by card, bank transfer or USSD, then come back here. The school never sees your card details.</p>
                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onClose}>Cancel</Button>
                        <Button type="submit" disabled={form.processing} className="bg-emerald-600 text-white hover:bg-emerald-700">Continue to payment</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
