import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Printer } from 'lucide-react';

interface FeePayment {
    id: number; receipt_no: string; amount_due: string; amount_paid: string;
    discount: string; fine: string; payment_date: string | null; month_year: string | null;
    method: string; status: string; note: string | null; created_at: string;
    student?: {
        id: number; first_name: string; last_name: string | null;
        admission_no: string; school_class?: { name: string };
    };
    fee_structure?: {
        id: number; academic_year: string; frequency: string;
        fee_category?: { name: string; type: string };
    };
}

const STATUS_STYLE: Record<string, string> = {
    paid:    'bg-green-100 text-green-700',
    partial: 'bg-amber-100 text-amber-700',
    pending: 'bg-slate-100 text-slate-600',
    overdue: 'bg-red-100 text-red-700',
};
const METHOD_LABELS: Record<string, string> = {
    cash: 'Cash', card: 'Card', online: 'Online', bkash: 'bKash', nagad: 'Nagad', rocket: 'Rocket',
};

export default function FeeReceipt({ payment }: { payment: FeePayment }) {
    const balance = Number(payment.amount_due) + Number(payment.fine) - Number(payment.discount) - Number(payment.amount_paid);

    return (
        <AppLayout title={`Receipt — ${payment.receipt_no}`}>
            <div className="max-w-xl mx-auto space-y-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link href="/school/fees/payments" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Payments
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <h1 className="text-xl font-bold text-slate-900 dark:text-white">Receipt</h1>
                    </div>
                    <Button variant="outline" onClick={() => window.print()} className="inline-flex items-center gap-2">
                        <Printer className="w-4 h-4" /> Print
                    </Button>
                </div>

                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-hidden print:shadow-none">
                    {/* Header */}
                    <div className="bg-indigo-600 text-white px-6 py-5">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="text-lg font-bold">Fee Receipt</h2>
                                <p className="text-indigo-200 text-sm mt-0.5">School Management System</p>
                            </div>
                            <div className="text-right">
                                <p className="font-mono text-sm text-indigo-200">Receipt No.</p>
                                <p className="font-mono font-bold">{payment.receipt_no}</p>
                            </div>
                        </div>
                    </div>

                    <div className="p-6 space-y-5">
                        {/* Student Info */}
                        <div className="grid grid-cols-2 gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                            <div>
                                <p className="text-xs text-slate-400 uppercase tracking-wide">Student</p>
                                <p className="font-semibold text-slate-900 dark:text-white mt-0.5">
                                    {payment.student?.first_name} {payment.student?.last_name}
                                </p>
                                <p className="text-sm text-slate-500">{payment.student?.admission_no}</p>
                            </div>
                            <div>
                                <p className="text-xs text-slate-400 uppercase tracking-wide">Class</p>
                                <p className="font-medium text-slate-700 dark:text-slate-300 mt-0.5">{payment.student?.school_class?.name}</p>
                            </div>
                        </div>

                        {/* Fee Details */}
                        <div className="grid grid-cols-2 gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                            <div>
                                <p className="text-xs text-slate-400 uppercase tracking-wide">Fee Type</p>
                                <p className="font-medium text-slate-700 dark:text-slate-300 mt-0.5">{payment.fee_structure?.fee_category?.name}</p>
                                <p className="text-xs text-slate-400 capitalize">{payment.fee_structure?.fee_category?.type}</p>
                            </div>
                            <div>
                                <p className="text-xs text-slate-400 uppercase tracking-wide">Academic Year</p>
                                <p className="font-medium text-slate-700 dark:text-slate-300 mt-0.5">{payment.fee_structure?.academic_year}</p>
                                {payment.month_year && <p className="text-xs text-slate-400">Month: {payment.month_year}</p>}
                            </div>
                        </div>

                        {/* Amount Breakdown */}
                        <div className="space-y-2">
                            <div className="flex justify-between text-sm">
                                <span className="text-slate-500">Amount Due</span>
                                <span className="text-slate-900 dark:text-white">৳{Number(payment.amount_due).toLocaleString()}</span>
                            </div>
                            {Number(payment.fine) > 0 && (
                                <div className="flex justify-between text-sm">
                                    <span className="text-red-500">Fine</span>
                                    <span className="text-red-500">+ ৳{Number(payment.fine).toLocaleString()}</span>
                                </div>
                            )}
                            {Number(payment.discount) > 0 && (
                                <div className="flex justify-between text-sm">
                                    <span className="text-green-500">Discount</span>
                                    <span className="text-green-500">- ৳{Number(payment.discount).toLocaleString()}</span>
                                </div>
                            )}
                            <div className="border-t border-slate-100 dark:border-slate-800 pt-2 flex justify-between font-semibold">
                                <span className="text-slate-700 dark:text-slate-300">Net Due</span>
                                <span className="text-slate-900 dark:text-white">
                                    ৳{(Number(payment.amount_due) + Number(payment.fine) - Number(payment.discount)).toLocaleString()}
                                </span>
                            </div>
                            <div className="flex justify-between font-bold text-lg">
                                <span className="text-slate-700 dark:text-slate-300">Amount Paid</span>
                                <span className="text-green-600">৳{Number(payment.amount_paid).toLocaleString()}</span>
                            </div>
                            {balance > 0 && (
                                <div className="flex justify-between text-sm font-medium text-red-600">
                                    <span>Balance Due</span>
                                    <span>৳{balance.toLocaleString()}</span>
                                </div>
                            )}
                        </div>

                        {/* Payment Info */}
                        <div className="grid grid-cols-3 gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                            <div>
                                <p className="text-xs text-slate-400 uppercase tracking-wide">Date</p>
                                <p className="font-medium text-slate-700 dark:text-slate-300 text-sm mt-0.5">
                                    {payment.payment_date ? new Date(payment.payment_date).toLocaleDateString() : '—'}
                                </p>
                            </div>
                            <div>
                                <p className="text-xs text-slate-400 uppercase tracking-wide">Method</p>
                                <p className="font-medium text-slate-700 dark:text-slate-300 text-sm mt-0.5">{METHOD_LABELS[payment.method] ?? payment.method}</p>
                            </div>
                            <div>
                                <p className="text-xs text-slate-400 uppercase tracking-wide">Status</p>
                                <Badge className={`border-0 text-xs capitalize mt-0.5 ${STATUS_STYLE[payment.status] ?? ''}`}>{payment.status}</Badge>
                            </div>
                        </div>

                        {payment.note && (
                            <div>
                                <p className="text-xs text-slate-400 uppercase tracking-wide">Note</p>
                                <p className="text-sm text-slate-600 dark:text-slate-400 mt-0.5">{payment.note}</p>
                            </div>
                        )}

                        <p className="text-xs text-center text-slate-400 pt-2">Generated on {new Date(payment.created_at).toLocaleString()}</p>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
