import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { DollarSign, CheckCircle, AlertTriangle } from 'lucide-react';

interface Payment { id: number; month: string; due: number; paid: number; balance: number; status: string; payment_date: string | null; }
interface Summary { total_due: number; total_paid: number; balance: number; }
interface Props {
    linked: boolean;
    student: { full_name: string; class: string | null } | null;
    summary: Summary;
    payments: Payment[];
}

const statusColor: Record<string, string> = {
    paid:        'bg-green-100 text-green-700',
    partial:     'bg-amber-100 text-amber-700',
    unpaid:      'bg-red-100 text-red-700',
    overdue:     'bg-red-100 text-red-700',
};

export default function Fees({ linked, student, summary, payments }: Props) {
    if (!linked) {
        return (
            <AppLayout title="My Fees">
                <div className="flex flex-col items-center justify-center py-24 text-center">
                    <DollarSign className="w-14 h-14 text-slate-300 mb-4" />
                    <h2 className="text-lg font-semibold text-slate-600 dark:text-slate-400">Account not linked</h2>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout title="My Fees">
            <div className="space-y-6">
                <div>
                    <h1 className="text-xl font-bold text-slate-900 dark:text-white">My Fees</h1>
                    <p className="text-sm text-slate-500">{student?.class}</p>
                </div>

                {/* Summary */}
                <div className="grid grid-cols-3 gap-4">
                    <Card>
                        <CardContent className="p-4 text-center">
                            <p className="text-xs text-slate-500 mb-1">Total Due</p>
                            <p className="text-lg font-bold text-slate-800 dark:text-white">৳{summary.total_due.toLocaleString()}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-4 text-center">
                            <p className="text-xs text-slate-500 mb-1">Total Paid</p>
                            <p className="text-lg font-bold text-green-600">৳{summary.total_paid.toLocaleString()}</p>
                        </CardContent>
                    </Card>
                    <Card className={cn(summary.balance > 0 ? 'border-red-200 dark:border-red-900' : 'border-green-200 dark:border-green-900')}>
                        <CardContent className="p-4 text-center">
                            <div className="flex items-center justify-center gap-1 mb-1">
                                {summary.balance > 0
                                    ? <AlertTriangle className="w-3 h-3 text-red-500" />
                                    : <CheckCircle className="w-3 h-3 text-green-500" />}
                                <p className="text-xs text-slate-500">Balance</p>
                            </div>
                            <p className={cn('text-lg font-bold', summary.balance > 0 ? 'text-red-600' : 'text-green-600')}>
                                {summary.balance > 0 ? `৳${summary.balance.toLocaleString()}` : 'Clear'}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Payment list */}
                <Card>
                    <CardHeader><CardTitle className="text-sm font-semibold">Payment History</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        {payments.length === 0 ? (
                            <p className="py-8 text-center text-slate-400 text-sm">No payments recorded.</p>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-xs text-slate-500 border-b dark:border-slate-700">
                                        <th className="text-left px-4 py-2 font-medium">Month</th>
                                        <th className="text-right px-4 py-2 font-medium">Due</th>
                                        <th className="text-right px-4 py-2 font-medium">Paid</th>
                                        <th className="text-right px-4 py-2 font-medium">Balance</th>
                                        <th className="text-center px-4 py-2 font-medium">Status</th>
                                        <th className="text-right px-4 py-2 font-medium hidden md:table-cell">Date</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {payments.map(p => (
                                        <tr key={p.id}>
                                            <td className="px-4 py-2.5 text-slate-700 dark:text-slate-300">{p.month}</td>
                                            <td className="px-4 py-2.5 text-right text-slate-600">৳{p.due.toLocaleString()}</td>
                                            <td className="px-4 py-2.5 text-right text-green-600">৳{p.paid.toLocaleString()}</td>
                                            <td className={cn('px-4 py-2.5 text-right font-medium', p.balance > 0 ? 'text-red-500' : 'text-green-600')}>
                                                {p.balance > 0 ? `৳${p.balance.toLocaleString()}` : '—'}
                                            </td>
                                            <td className="px-4 py-2.5 text-center">
                                                <span className={cn('text-[10px] font-medium px-2 py-0.5 rounded-full capitalize', statusColor[p.status] ?? 'bg-slate-100 text-slate-600')}>{p.status}</span>
                                            </td>
                                            <td className="px-4 py-2.5 text-right text-slate-400 text-xs hidden md:table-cell">{p.payment_date ?? '—'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
