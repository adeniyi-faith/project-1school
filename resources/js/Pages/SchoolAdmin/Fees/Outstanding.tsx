import { router, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import { ArrowLeft, AlertCircle, Users, DollarSign } from 'lucide-react';
import type { SchoolClass } from '@/Types';

interface OutstandingRow {
    student: { id: number; first_name: string; last_name: string | null; admission_no: string; school_class?: { name: string } };
    payment_count: number;
    total_due: number;
    total_paid: number;
    balance: number;
    payments: any[];
}

interface Props {
    outstanding: OutstandingRow[];
    classes: SchoolClass[];
    filters: { class_id?: string };
    summary: { total_students: number; total_outstanding: number };
}

export default function OutstandingFees({ outstanding, classes, filters, summary }: Props) {
    function applyFilter(key: string, value: string) {
        router.get('/school/fees/outstanding', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    return (
        <AppLayout title="Outstanding Fees">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link href="/school/fees/payments" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Payments
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Outstanding Fees</h1>
                            <p className="text-sm text-slate-500">{summary.total_students} students with unpaid fees</p>
                        </div>
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-2 gap-4 max-w-sm">
                    <Card className="border-slate-200 dark:border-slate-800">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-red-500"><Users className="w-5 h-5" /></div>
                            <div>
                                <p className="text-2xl font-bold text-red-600">{summary.total_students}</p>
                                <p className="text-xs text-slate-500">Students</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-slate-200 dark:border-slate-800">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-red-500"><DollarSign className="w-5 h-5" /></div>
                            <div>
                                <p className="text-xl font-bold text-red-600">৳{summary.total_outstanding?.toLocaleString()}</p>
                                <p className="text-xs text-slate-500">Total Due</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filter */}
                <div className="flex gap-3">
                    <Select value={filters.class_id ?? ''} onValueChange={v => applyFilter('class_id', v)}>
                        <SelectTrigger className="w-40"><SelectValue placeholder="All Classes" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Classes</SelectItem>
                            {classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                        </SelectContent>
                    </Select>
                </div>

                {outstanding.length === 0 ? (
                    <div className="rounded-xl border border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-950/20 py-16 text-center">
                        <AlertCircle className="w-12 h-12 mx-auto text-green-300 mb-3" />
                        <p className="text-green-600 font-medium">No outstanding fees!</p>
                        <p className="text-sm text-green-500 mt-1">All fees have been collected.</p>
                    </div>
                ) : (
                    <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-slate-50 dark:bg-slate-900">
                                    <TableHead>#</TableHead>
                                    <TableHead>Student</TableHead>
                                    <TableHead>Class</TableHead>
                                    <TableHead className="text-center">Pending Items</TableHead>
                                    <TableHead className="text-right">Total Due</TableHead>
                                    <TableHead className="text-right">Paid</TableHead>
                                    <TableHead className="text-right">Balance</TableHead>
                                    <TableHead className="w-24"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {outstanding.map((row, idx) => (
                                    <TableRow key={row.student.id} className="bg-red-50/30 dark:bg-red-950/5">
                                        <TableCell className="text-slate-400 text-xs">{idx + 1}</TableCell>
                                        <TableCell>
                                            <p className="font-medium text-slate-900 dark:text-white text-sm">{row.student.first_name} {row.student.last_name}</p>
                                            <p className="text-xs text-slate-400">{row.student.admission_no}</p>
                                        </TableCell>
                                        <TableCell className="text-slate-600 dark:text-slate-400 text-sm">{row.student.school_class?.name}</TableCell>
                                        <TableCell className="text-center">
                                            <Badge className="bg-amber-100 text-amber-700 border-0 text-xs">{row.payment_count} item{row.payment_count !== 1 ? 's' : ''}</Badge>
                                        </TableCell>
                                        <TableCell className="text-right text-sm text-slate-700 dark:text-slate-300">৳{row.total_due.toLocaleString()}</TableCell>
                                        <TableCell className="text-right text-sm text-green-600">৳{row.total_paid.toLocaleString()}</TableCell>
                                        <TableCell className="text-right font-bold text-red-600">৳{row.balance.toLocaleString()}</TableCell>
                                        <TableCell>
                                            <Link href={`/school/fees/payments/collect?student_id=${row.student.admission_no}`}>
                                                <Button size="sm" className="text-xs bg-indigo-600 hover:bg-indigo-700 text-white">Collect</Button>
                                            </Link>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
