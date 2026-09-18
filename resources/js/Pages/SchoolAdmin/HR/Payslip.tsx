import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Printer } from 'lucide-react';

interface SalaryItem { label: string; amount: number; }
interface Payroll {
    id: number; month_year: string; basic_salary: string; total_allowances: string;
    total_deductions: string; net_salary: string; working_days: number; present_days: number;
    leave_days: number; status: string; paid_on: string | null; note: string | null;
    allowances_snapshot: SalaryItem[] | null;
    deductions_snapshot: SalaryItem[] | null;
    staff?: {
        first_name: string; last_name: string | null; emp_id: string;
        joining_date: string | null;
        department?: { name: string };
        designation?: { name: string };
    };
}

const STATUS_STYLE: Record<string, string> = {
    draft:     'bg-slate-100 text-slate-600',
    generated: 'bg-blue-100 text-blue-700',
    paid:      'bg-green-100 text-green-700',
};

export default function Payslip({ payroll }: { payroll: Payroll }) {
    return (
        <AppLayout title={`Payslip — ${payroll.month_year}`}>
            <style>{`
                @media print {
                    body * { visibility: hidden !important; }
                    #payslip-print-area, #payslip-print-area * { visibility: visible !important; }
                    #payslip-print-area {
                        position: fixed !important;
                        inset: 0 !important;
                        width: 100% !important;
                        padding: 24px !important;
                        background: white !important;
                    }
                }
            `}</style>
            <div className="max-w-xl mx-auto space-y-4">
                <div className="flex items-center justify-between print:hidden">
                    <div className="flex items-center gap-3">
                        <Link href="/school/hr/payroll" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Payroll
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <h1 className="text-xl font-bold text-slate-900 dark:text-white">Payslip</h1>
                    </div>
                    <Button variant="outline" onClick={() => window.print()} className="inline-flex items-center gap-2">
                        <Printer className="w-4 h-4" /> Print
                    </Button>
                </div>

                <div id="payslip-print-area" className="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    {/* Header */}
                    <div className="bg-indigo-700 text-white px-6 py-5">
                        <div className="flex items-start justify-between">
                            <div>
                                <h2 className="text-lg font-bold">Salary Payslip</h2>
                                <p className="text-indigo-200 text-sm mt-0.5">School Management System</p>
                            </div>
                            <div className="text-right">
                                <p className="font-bold text-xl">{payroll.month_year}</p>
                                <Badge className={`border-0 text-xs mt-1 capitalize ${STATUS_STYLE[payroll.status] ?? ''}`}>{payroll.status}</Badge>
                            </div>
                        </div>
                    </div>

                    <div className="p-6 space-y-5">
                        {/* Employee Info */}
                        <div className="grid grid-cols-2 gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                            <div>
                                <p className="text-xs text-slate-400 uppercase tracking-wide">Employee</p>
                                <p className="font-semibold text-slate-900 dark:text-white mt-0.5">{payroll.staff?.first_name} {payroll.staff?.last_name}</p>
                                <p className="text-sm text-slate-500">{payroll.staff?.emp_id}</p>
                            </div>
                            <div>
                                <p className="text-xs text-slate-400 uppercase tracking-wide">Department</p>
                                <p className="font-medium text-slate-700 dark:text-slate-300 mt-0.5">{payroll.staff?.department?.name}</p>
                                <p className="text-sm text-slate-500">{payroll.staff?.designation?.name}</p>
                            </div>
                        </div>

                        {/* Attendance */}
                        <div className="grid grid-cols-3 gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                            {[
                                { label: 'Working Days', value: payroll.working_days },
                                { label: 'Present Days', value: payroll.present_days, color: 'text-green-600' },
                                { label: 'Leave Days',   value: payroll.leave_days,   color: 'text-amber-600' },
                            ].map(({ label, value, color }) => (
                                <div key={label} className="text-center bg-slate-50 dark:bg-slate-900 rounded-lg p-2">
                                    <p className={`text-xl font-bold ${color ?? 'text-slate-900 dark:text-white'}`}>{value}</p>
                                    <p className="text-xs text-slate-400">{label}</p>
                                </div>
                            ))}
                        </div>

                        {/* Earnings */}
                        <div>
                            <p className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Earnings</p>
                            <div className="space-y-1.5">
                                <div className="flex justify-between text-sm">
                                    <span className="text-slate-600 dark:text-slate-400">Basic Salary</span>
                                    <span className="font-medium text-slate-900 dark:text-white">৳{Number(payroll.basic_salary).toLocaleString()}</span>
                                </div>
                                {(payroll.allowances_snapshot ?? []).map((a, i) => (
                                    <div key={i} className="flex justify-between text-sm">
                                        <span className="text-slate-500">{a.label}</span>
                                        <span className="text-green-600">৳{Number(a.amount).toLocaleString()}</span>
                                    </div>
                                ))}
                                <div className="flex justify-between text-sm font-semibold border-t border-slate-100 dark:border-slate-800 pt-1">
                                    <span className="text-slate-700 dark:text-slate-300">Gross Salary</span>
                                    <span>৳{(Number(payroll.basic_salary) + Number(payroll.total_allowances)).toLocaleString()}</span>
                                </div>
                            </div>
                        </div>

                        {/* Deductions */}
                        {(payroll.deductions_snapshot ?? []).length > 0 && (
                            <div>
                                <p className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Deductions</p>
                                <div className="space-y-1.5">
                                    {(payroll.deductions_snapshot ?? []).map((d, i) => (
                                        <div key={i} className="flex justify-between text-sm">
                                            <span className="text-slate-500">{d.label}</span>
                                            <span className="text-red-600">- ৳{Number(d.amount).toLocaleString()}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Net Salary */}
                        <div className="rounded-lg bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-800 px-4 py-3 flex justify-between items-center">
                            <span className="font-semibold text-indigo-900 dark:text-indigo-200">Net Salary</span>
                            <span className="text-2xl font-bold text-indigo-600">৳{Number(payroll.net_salary).toLocaleString()}</span>
                        </div>

                        {payroll.paid_on && (
                            <p className="text-sm text-green-600 text-center">Paid on {new Date(payroll.paid_on).toLocaleDateString()}</p>
                        )}

                        {payroll.note && (
                            <p className="text-xs text-slate-400 text-center">{payroll.note}</p>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
