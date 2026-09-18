import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DollarSign, AlertTriangle, CheckCircle } from 'lucide-react';

interface Payment { month: string; due: number; paid: number; balance: number; status: string; payment_date: string | null; }
interface Child { id: number; full_name: string; class: string | null; total_due: number; total_paid: number; balance: number; payments: Payment[]; }
interface Props { linked: boolean; guardian: { name: string } | null; children: Child[]; }

const statusColor: Record<string, string> = {
    paid: 'bg-green-100 text-green-700', partial: 'bg-amber-100 text-amber-700',
    unpaid: 'bg-red-100 text-red-700',   overdue: 'bg-red-100 text-red-700',
};

export default function ParentFees({ linked, children }: Props) {
    if (!linked) {
        return (
            <AppLayout title="Fee Status">
                <div className="flex flex-col items-center justify-center py-24 text-center">
                    <DollarSign className="w-14 h-14 text-slate-300 mb-4" />
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
                        <div className="grid grid-cols-3 gap-3 mb-4">
                            <Card><CardContent className="p-3 text-center">
                                <p className="text-[11px] text-slate-500 mb-1">Total Due</p>
                                <p className="font-bold text-slate-800 dark:text-white">৳{child.total_due.toLocaleString()}</p>
                            </CardContent></Card>
                            <Card><CardContent className="p-3 text-center">
                                <p className="text-[11px] text-slate-500 mb-1">Total Paid</p>
                                <p className="font-bold text-green-600">৳{child.total_paid.toLocaleString()}</p>
                            </CardContent></Card>
                            <Card className={child.balance > 0 ? 'border-red-200' : 'border-green-200'}>
                                <CardContent className="p-3 text-center">
                                    <p className="text-[11px] text-slate-500 mb-1">Balance</p>
                                    <p className={cn('font-bold', child.balance > 0 ? 'text-red-600' : 'text-green-600')}>
                                        {child.balance > 0 ? `৳${child.balance.toLocaleString()}` : 'Clear'}
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
                                                <th className="px-4 py-2 font-medium">Month</th>
                                                <th className="px-4 py-2 font-medium text-right">Due</th>
                                                <th className="px-4 py-2 font-medium text-right">Paid</th>
                                                <th className="px-4 py-2 font-medium text-center">Status</th>
                                                <th className="px-4 py-2 font-medium text-right hidden md:table-cell">Date</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                            {child.payments.map((p, i) => (
                                                <tr key={i}>
                                                    <td className="px-4 py-2 text-slate-700 dark:text-slate-300">{p.month}</td>
                                                    <td className="px-4 py-2 text-right text-slate-600">৳{p.due.toLocaleString()}</td>
                                                    <td className="px-4 py-2 text-right text-green-600">৳{p.paid.toLocaleString()}</td>
                                                    <td className="px-4 py-2 text-center">
                                                        <span className={cn('text-[10px] font-medium px-2 py-0.5 rounded-full capitalize', statusColor[p.status] ?? 'bg-slate-100 text-slate-600')}>{p.status}</span>
                                                    </td>
                                                    <td className="px-4 py-2 text-right text-slate-400 text-xs hidden md:table-cell">{p.payment_date ?? '—'}</td>
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
        </AppLayout>
    );
}
