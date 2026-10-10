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
import { Plus, Minus, ArrowLeft } from 'lucide-react';
import type { PageProps, PaginatedResponse } from '@/Types';

interface SalaryItem { label: string; amount: number; }
interface SalaryStruct { basic_salary: string; allowances: SalaryItem[] | null; deductions: SalaryItem[] | null; }
interface StaffRow {
    id: number; first_name: string; last_name: string | null; emp_id: string;
    department?: { name: string }; designation?: { name: string };
    salary_structure: SalaryStruct | null;
}
interface Department { id: number; name: string; }

interface Props {
    staffList: PaginatedResponse<StaffRow>;
    departments: Department[];
    filters: { department_id?: string };
}

export default function SalaryStructurePage({ staffList, departments, filters }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen] = useState(false);
    const [editStaff, setEditStaff] = useState<StaffRow | null>(null);
    const [form, setForm] = useState({ basic_salary: '', allowances: [] as SalaryItem[], deductions: [] as SalaryItem[] });
    const [saving, setSaving] = useState(false);

    function applyFilter(key: string, value: string) {
        router.get('/school/hr/salary-structure', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    function openEdit(s: StaffRow) {
        setEditStaff(s);
        setForm({
            basic_salary: s.salary_structure?.basic_salary ?? '',
            allowances: s.salary_structure?.allowances ?? [],
            deductions: s.salary_structure?.deductions ?? [],
        });
        setOpen(true);
    }

    function addItem(type: 'allowances' | 'deductions') {
        setForm(prev => ({ ...prev, [type]: [...prev[type], { label: '', amount: 0 }] }));
    }
    function removeItem(type: 'allowances' | 'deductions', idx: number) {
        setForm(prev => ({ ...prev, [type]: prev[type].filter((_, i) => i !== idx) }));
    }
    function updateItem(type: 'allowances' | 'deductions', idx: number, field: 'label' | 'amount', value: string) {
        setForm(prev => ({
            ...prev,
            [type]: prev[type].map((item, i) => i === idx ? { ...item, [field]: field === 'amount' ? Number(value) : value } : item),
        }));
    }

    const grossSalary = Number(form.basic_salary || 0) + form.allowances.reduce((s, a) => s + Number(a.amount || 0), 0);
    const totalDeductions = form.deductions.reduce((s, d) => s + Number(d.amount || 0), 0);
    const netSalary = grossSalary - totalDeductions;

    function handleSave() {
        if (!editStaff) return;
        setSaving(true);
        router.put(`/school/hr/salary-structure/${editStaff.id}`, form, {
            preserveScroll: true,
            onSuccess: () => { setOpen(false); setSaving(false); },
            onError: () => setSaving(false),
        });
    }

    return (
        <AppLayout title="Salary Structure">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link href="/school/hr/payroll" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Payroll
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Salary Structure</h1>
                            <p className="text-sm text-slate-500">Configure salary components per staff</p>
                        </div>
                    </div>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                <div className="flex gap-3">
                    <Select value={filters.department_id ?? ''} onValueChange={v => applyFilter('department_id', v)}>
                        <SelectTrigger className="w-44"><SelectValue placeholder="All Departments" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Departments</SelectItem>
                            {departments.map(d => <SelectItem key={d.id} value={String(d.id)}>{d.name}</SelectItem>)}
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Staff</TableHead>
                                <TableHead>Department · Designation</TableHead>
                                <TableHead className="text-right">Basic</TableHead>
                                <TableHead className="text-right">Allowances</TableHead>
                                <TableHead className="text-right">Deductions</TableHead>
                                <TableHead className="text-right">Net Salary</TableHead>
                                <TableHead className="w-20"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {staffList.data.map(s => {
                                const struct = s.salary_structure;
                                const allowTotal = struct?.allowances?.reduce((sum, a) => sum + Number(a.amount), 0) ?? 0;
                                const dedTotal   = struct?.deductions?.reduce((sum, d) => sum + Number(d.amount), 0) ?? 0;
                                const netSal     = struct ? Number(struct.basic_salary) + allowTotal - dedTotal : null;
                                return (
                                    <TableRow key={s.id}>
                                        <TableCell>
                                            <p className="font-medium text-sm text-slate-900 dark:text-white">{s.first_name} {s.last_name}</p>
                                            <p className="text-xs text-slate-400">{s.emp_id}</p>
                                        </TableCell>
                                        <TableCell className="text-slate-500 text-sm">{s.department?.name} · {s.designation?.name}</TableCell>
                                        <TableCell className="text-right text-sm">{struct ? `৳${Number(struct.basic_salary).toLocaleString()}` : '—'}</TableCell>
                                        <TableCell className="text-right text-sm text-green-600">{struct ? `৳${allowTotal.toLocaleString()}` : '—'}</TableCell>
                                        <TableCell className="text-right text-sm text-red-600">{struct ? `৳${dedTotal.toLocaleString()}` : '—'}</TableCell>
                                        <TableCell className="text-right font-bold text-indigo-600">{netSal != null ? `৳${netSal.toLocaleString()}` : <Badge className="bg-amber-100 text-amber-700 border-0 text-xs">Not Set</Badge>}</TableCell>
                                        <TableCell>
                                            <Button size="sm" variant="outline" className="text-xs" onClick={() => openEdit(s)}>Edit</Button>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-lg max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Salary Structure — {editStaff?.first_name} {editStaff?.last_name}</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-5 mt-2">
                        <div className="space-y-1.5">
                            <Label>Basic Salary (৳) <span className="text-red-500">*</span></Label>
                            <Input type="number" min="0" step="0.01" value={form.basic_salary} onChange={e => setForm(p => ({ ...p, basic_salary: e.target.value }))} />
                        </div>

                        {/* Allowances */}
                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <Label className="text-green-700 dark:text-green-400">Allowances</Label>
                                <Button type="button" size="sm" variant="outline" className="h-7 text-xs" onClick={() => addItem('allowances')}>
                                    <Plus className="w-3 h-3 mr-1" /> Add
                                </Button>
                            </div>
                            {form.allowances.map((a, i) => (
                                <div key={i} className="flex gap-2 mb-2">
                                    <Input placeholder="Label (e.g. House Rent)" value={a.label} onChange={e => updateItem('allowances', i, 'label', e.target.value)} className="flex-1" />
                                    <Input type="number" min="0" placeholder="Amount" value={a.amount} onChange={e => updateItem('allowances', i, 'amount', e.target.value)} className="w-28" />
                                    <Button type="button" size="icon" variant="ghost" className="text-red-500 h-9 w-9" onClick={() => removeItem('allowances', i)}><Minus className="w-3 h-3" /></Button>
                                </div>
                            ))}
                        </div>

                        {/* Deductions */}
                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <Label className="text-red-700 dark:text-red-400">Deductions</Label>
                                <Button type="button" size="sm" variant="outline" className="h-7 text-xs" onClick={() => addItem('deductions')}>
                                    <Plus className="w-3 h-3 mr-1" /> Add
                                </Button>
                            </div>
                            {form.deductions.map((d, i) => (
                                <div key={i} className="flex gap-2 mb-2">
                                    <Input placeholder="Label (e.g. Income Tax)" value={d.label} onChange={e => updateItem('deductions', i, 'label', e.target.value)} className="flex-1" />
                                    <Input type="number" min="0" placeholder="Amount" value={d.amount} onChange={e => updateItem('deductions', i, 'amount', e.target.value)} className="w-28" />
                                    <Button type="button" size="icon" variant="ghost" className="text-red-500 h-9 w-9" onClick={() => removeItem('deductions', i)}><Minus className="w-3 h-3" /></Button>
                                </div>
                            ))}
                        </div>

                        {/* Summary */}
                        <div className="rounded-lg bg-slate-50 dark:bg-slate-900 p-3 space-y-1 text-sm">
                            <div className="flex justify-between"><span className="text-slate-500">Gross Salary</span><span className="font-medium">৳{grossSalary.toLocaleString()}</span></div>
                            <div className="flex justify-between text-red-600"><span>Total Deductions</span><span>- ৳{totalDeductions.toLocaleString()}</span></div>
                            <div className="flex justify-between font-bold text-base border-t border-slate-200 dark:border-slate-700 pt-1 mt-1">
                                <span>Net Salary</span><span className="text-indigo-600">৳{netSalary.toLocaleString()}</span>
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                        <Button onClick={handleSave} disabled={saving} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                            {saving ? 'Saving...' : 'Save Structure'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
