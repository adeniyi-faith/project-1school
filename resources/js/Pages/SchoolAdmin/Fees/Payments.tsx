import { router, usePage, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import { Plus, DollarSign, CheckCircle2, Clock, AlertCircle, Settings2, Tag, TrendingDown } from 'lucide-react';
import type { SchoolClass, PageProps, PaginatedResponse } from '@/Types';

interface FeePayment {
    id: number; receipt_no: string; amount_due: string; amount_paid: string; discount: string; fine: string;
    payment_date: string | null; month_year: string | null; method: string; status: string;
    student?: { id: number; first_name: string; last_name: string | null; admission_no: string; school_class?: { name: string } };
    fee_structure?: { academic_year: string; frequency: string; fee_category?: { name: string; type: string } };
}
interface Stats { total_collected: number; total_outstanding: number; paid_count: number; pending_count: number; }

interface Props {
    payments: PaginatedResponse<FeePayment>;
    classes: SchoolClass[];
    filters: { student_id?: string; status?: string; class_id?: string; month_year?: string };
    stats: Stats;
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

export default function FeePayments({ payments, classes, filters, stats }: Props) {
    function applyFilter(key: string, value: string) {
        router.get('/school/fees/payments', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    const statCards = [
        { label: 'Total Collected', value: `৳${stats.total_collected?.toLocaleString() ?? 0}`, color: 'text-green-600', icon: DollarSign },
        { label: 'Outstanding', value: `৳${Math.max(0, stats.total_outstanding ?? 0).toLocaleString()}`, color: 'text-red-600', icon: TrendingDown },
        { label: 'Paid Receipts', value: stats.paid_count, color: 'text-indigo-600', icon: CheckCircle2 },
        { label: 'Pending', value: stats.pending_count, color: 'text-amber-600', icon: Clock },
    ];

    return (
        <AppLayout title="Fee Payments">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Fee Management</h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{payments.meta?.total ?? 0} payment records</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/school/fees/outstanding">
                            <Button variant="outline" className="inline-flex items-center gap-2"><AlertCircle className="w-4 h-4" /> Outstanding</Button>
                        </Link>
                        <Link href="/school/fees/structures">
                            <Button variant="outline" className="inline-flex items-center gap-2"><Settings2 className="w-4 h-4" /> Structures</Button>
                        </Link>
                        <Link href="/school/fees/categories">
                            <Button variant="outline" className="inline-flex items-center gap-2"><Tag className="w-4 h-4" /> Categories</Button>
                        </Link>
                        <Link href="/school/fees/payments/collect">
                            <Button className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                                <Plus className="w-4 h-4" /> Collect Fee
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    {statCards.map(({ label, value, color, icon: Icon }) => (
                        <Card key={label} className="border-slate-200 dark:border-slate-800">
                            <CardContent className="p-4 flex items-center gap-3">
                                <div className={`p-2 rounded-lg bg-slate-100 dark:bg-slate-800 ${color}`}><Icon className="w-5 h-5" /></div>
                                <div>
                                    <p className={`text-xl font-bold ${color}`}>{value}</p>
                                    <p className="text-xs text-slate-500">{label}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Filters */}
                <div className="flex gap-3 flex-wrap">
                    <Select value={filters.class_id ?? ''} onValueChange={v => applyFilter('class_id', v)}>
                        <SelectTrigger className="w-40"><SelectValue placeholder="All Classes" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Classes</SelectItem>
                            {classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Select value={filters.status ?? ''} onValueChange={v => applyFilter('status', v)}>
                        <SelectTrigger className="w-36"><SelectValue placeholder="All Status" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Status</SelectItem>
                            <SelectItem value="paid">Paid</SelectItem>
                            <SelectItem value="partial">Partial</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="overdue">Overdue</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Table */}
                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Receipt</TableHead>
                                <TableHead>Student</TableHead>
                                <TableHead>Fee</TableHead>
                                <TableHead className="text-right">Amount Due</TableHead>
                                <TableHead className="text-right">Paid</TableHead>
                                <TableHead className="text-right">Balance</TableHead>
                                <TableHead>Method</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-16"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {payments.data.length === 0 ? (
                                <TableRow><TableCell colSpan={10} className="text-center py-16 text-slate-400">No payments recorded yet.</TableCell></TableRow>
                            ) : payments.data.map(p => {
                                const balance = (Number(p.amount_due) + Number(p.fine) - Number(p.discount) - Number(p.amount_paid)).toFixed(2);
                                return (
                                    <TableRow key={p.id}>
                                        <TableCell className="font-mono text-xs text-slate-500">{p.receipt_no}</TableCell>
                                        <TableCell>
                                            <p className="font-medium text-sm text-slate-900 dark:text-white">{p.student?.first_name} {p.student?.last_name}</p>
                                            <p className="text-xs text-slate-400">{p.student?.school_class?.name} · ID: {p.student?.admission_no}</p>
                                        </TableCell>
                                        <TableCell>
                                            <p className="text-sm text-slate-700 dark:text-slate-300">{p.fee_structure?.fee_category?.name}</p>
                                            <p className="text-xs text-slate-400">{p.fee_structure?.academic_year} {p.month_year ? `· ${p.month_year}` : ''}</p>
                                        </TableCell>
                                        <TableCell className="text-right text-sm">৳{Number(p.amount_due).toLocaleString()}</TableCell>
                                        <TableCell className="text-right text-sm font-semibold text-green-600">৳{Number(p.amount_paid).toLocaleString()}</TableCell>
                                        <TableCell className={`text-right text-sm font-medium ${Number(balance) > 0 ? 'text-red-600' : 'text-green-600'}`}>
                                            {Number(balance) > 0 ? `৳${Number(balance).toLocaleString()}` : '—'}
                                        </TableCell>
                                        <TableCell className="text-xs text-slate-500">{METHOD_LABELS[p.method] ?? p.method}</TableCell>
                                        <TableCell className="text-xs text-slate-500">
                                            {p.payment_date ? new Date(p.payment_date).toLocaleDateString() : '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge className={`border-0 text-xs capitalize ${STATUS_STYLE[p.status] ?? ''}`}>{p.status}</Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Link href={`/school/fees/payments/${p.id}`}>
                                                <Button variant="outline" size="sm" className="text-xs">Receipt</Button>
                                            </Link>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AppLayout>
    );
}
