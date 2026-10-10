import { useState } from 'react';
import { router, usePage, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import { DollarSign, Zap, Settings2, CheckCircle2, Clock } from 'lucide-react';
import type { PageProps, PaginatedResponse } from '@/Types';

interface Department { id: number; name: string; }
interface PayrollRow {
    id: number; staff_id: number; month_year: string; basic_salary: string;
    total_allowances: string; total_deductions: string; net_salary: string;
    present_days: number; working_days: number; leave_days: number; status: string; paid_on: string | null;
    staff?: { first_name: string; last_name: string | null; emp_id: string; department?: { name: string } };
}
interface Stats { total_net: number; paid_count: number; draft_count: number; }

interface Props {
    payrolls: PaginatedResponse<PayrollRow>;
    departments: Department[];
    filters: { month_year?: string; department_id?: string; status?: string };
    stats: Stats;
}

const STATUS_STYLE: Record<string, string> = {
    draft:     'bg-slate-100 text-slate-600',
    generated: 'bg-blue-100 text-blue-700',
    paid:      'bg-green-100 text-green-700',
};

export default function PayrollPage({ payrolls, departments, filters, stats }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [genOpen, setGenOpen] = useState(false);
    const [genForm, setGenForm] = useState({ month_year: new Date().toISOString().slice(0, 7), department_id: '', working_days: '26' });
    const [generating, setGenerating] = useState(false);

    function applyFilter(key: string, value: string) {
        router.get('/school/hr/payroll', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    function handleGenerate() {
        setGenerating(true);
        router.post('/school/hr/payroll/generate', genForm, {
            preserveScroll: true,
            onSuccess: () => { setGenOpen(false); setGenerating(false); },
            onError: () => setGenerating(false),
        });
    }

    function markPaid(p: PayrollRow) {
        if (!confirm(`Mark payroll for ${p.staff?.first_name} ${p.staff?.last_name} as paid?`)) return;
        router.put(`/school/hr/payroll/${p.id}/paid`, {}, { preserveScroll: true });
    }

    return (
        <AppLayout title="Payroll">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Payroll</h1>
                        <p className="text-sm text-slate-500 mt-0.5">{payrolls.meta?.total ?? 0} payroll records</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/school/hr/salary-structure">
                            <Button variant="outline" className="inline-flex items-center gap-2"><Settings2 className="w-4 h-4" /> Salary Structure</Button>
                        </Link>
                        <Button onClick={() => setGenOpen(true)} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                            <Zap className="w-4 h-4" /> Generate Payroll
                        </Button>
                    </div>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                {/* Stats */}
                <div className="grid grid-cols-3 gap-4 max-w-lg">
                    {[
                        { label: 'Net Payable', value: `৳${stats.total_net?.toLocaleString() ?? 0}`, color: 'text-indigo-600', icon: DollarSign },
                        { label: 'Paid', value: stats.paid_count, color: 'text-green-600', icon: CheckCircle2 },
                        { label: 'Pending', value: stats.draft_count, color: 'text-amber-600', icon: Clock },
                    ].map(({ label, value, color, icon: Icon }) => (
                        <Card key={label} className="border-slate-200 dark:border-slate-800">
                            <CardContent className="p-4 flex items-center gap-3">
                                <Icon className={`w-5 h-5 ${color}`} />
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
                    <Input
                        type="month"
                        className="w-40"
                        value={filters.month_year ?? ''}
                        onChange={e => applyFilter('month_year', e.target.value)}
                    />
                    <Select value={filters.department_id ?? ''} onValueChange={v => applyFilter('department_id', v)}>
                        <SelectTrigger className="w-44"><SelectValue placeholder="All Departments" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Departments</SelectItem>
                            {departments.map(d => <SelectItem key={d.id} value={String(d.id)}>{d.name}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Select value={filters.status ?? ''} onValueChange={v => applyFilter('status', v)}>
                        <SelectTrigger className="w-36"><SelectValue placeholder="All Status" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Status</SelectItem>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="generated">Generated</SelectItem>
                            <SelectItem value="paid">Paid</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Table */}
                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Staff</TableHead>
                                <TableHead>Month</TableHead>
                                <TableHead className="text-right">Basic</TableHead>
                                <TableHead className="text-right">Allowances</TableHead>
                                <TableHead className="text-right">Deductions</TableHead>
                                <TableHead className="text-right">Net Salary</TableHead>
                                <TableHead className="text-center">Days</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-28"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {payrolls.data.length === 0 ? (
                                <TableRow><TableCell colSpan={9} className="text-center py-16 text-slate-400">No payroll records. Generate payroll first.</TableCell></TableRow>
                            ) : payrolls.data.map(p => (
                                <TableRow key={p.id}>
                                    <TableCell>
                                        <p className="font-medium text-sm text-slate-900 dark:text-white">{p.staff?.first_name} {p.staff?.last_name}</p>
                                        <p className="text-xs text-slate-400">{p.staff?.emp_id} · {p.staff?.department?.name}</p>
                                    </TableCell>
                                    <TableCell className="text-sm text-slate-600 dark:text-slate-400">{p.month_year}</TableCell>
                                    <TableCell className="text-right text-sm">৳{Number(p.basic_salary).toLocaleString()}</TableCell>
                                    <TableCell className="text-right text-sm text-green-600">৳{Number(p.total_allowances).toLocaleString()}</TableCell>
                                    <TableCell className="text-right text-sm text-red-600">৳{Number(p.total_deductions).toLocaleString()}</TableCell>
                                    <TableCell className="text-right font-bold text-indigo-600">৳{Number(p.net_salary).toLocaleString()}</TableCell>
                                    <TableCell className="text-center text-sm text-slate-500">{p.present_days}/{p.working_days}</TableCell>
                                    <TableCell>
                                        <Badge className={`border-0 text-xs capitalize ${STATUS_STYLE[p.status] ?? ''}`}>{p.status}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex gap-1">
                                            <Link href={`/school/hr/payroll/${p.id}/slip`}>
                                                <Button size="sm" variant="outline" className="text-xs h-7">Slip</Button>
                                            </Link>
                                            {p.status !== 'paid' && (
                                                <Button size="sm" className="text-xs h-7 bg-green-600 hover:bg-green-700 text-white" onClick={() => markPaid(p)}>
                                                    Mark Paid
                                                </Button>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            {/* Generate Dialog */}
            <Dialog open={genOpen} onOpenChange={setGenOpen}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader><DialogTitle>Generate Payroll</DialogTitle></DialogHeader>
                    <div className="space-y-4 mt-2">
                        <div className="space-y-1.5">
                            <Label>Month <span className="text-red-500">*</span></Label>
                            <Input type="month" value={genForm.month_year} onChange={e => setGenForm(p => ({ ...p, month_year: e.target.value }))} />
                        </div>
                        <div className="space-y-1.5">
                            <Label>Department (optional)</Label>
                            <Select value={genForm.department_id} onValueChange={v => setGenForm(p => ({ ...p, department_id: v }))}>
                                <SelectTrigger><SelectValue placeholder="All Departments" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All Departments</SelectItem>
                                    {departments.map(d => <SelectItem key={d.id} value={String(d.id)}>{d.name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Working Days <span className="text-red-500">*</span></Label>
                            <Input type="number" min="1" max="31" value={genForm.working_days} onChange={e => setGenForm(p => ({ ...p, working_days: e.target.value }))} />
                        </div>
                        <div className="rounded-lg bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 px-3 py-2 text-xs text-amber-700 dark:text-amber-300">
                            Net salary will be adjusted based on attendance records. Staff without a salary structure will be skipped.
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setGenOpen(false)}>Cancel</Button>
                        <Button onClick={handleGenerate} disabled={generating} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                            {generating ? 'Generating...' : 'Generate'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
