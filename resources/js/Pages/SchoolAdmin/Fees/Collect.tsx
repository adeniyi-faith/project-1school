import { useState } from 'react';
import { useForm, router, usePage, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Search, User, DollarSign } from 'lucide-react';
import type { SchoolClass, PageProps } from '@/Types';

interface Student {
    id: number; first_name: string; last_name: string | null; admission_no: string; class_id: number;
    school_class?: { name: string };
}
interface FeeStructure {
    id: number; academic_year: string; amount: string; frequency: string; due_date: string | null;
    fee_category?: { id: number; name: string; type: string };
}

interface Props {
    student: Student | null;
    structures: FeeStructure[];
    classes: SchoolClass[];
}

export default function CollectFee({ student, structures, classes }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [searchId, setSearchId] = useState('');

    const { data, setData, post, processing, errors } = useForm({
        student_id:       student?.id ? String(student.id) : '',
        fee_structure_id: '',
        amount_due:       '',
        amount_paid:      '',
        discount:         '0',
        fine:             '0',
        payment_date:     new Date().toISOString().split('T')[0],
        month_year:       '',
        method:           'cash',
        note:             '',
    });

    function searchStudent() {
        if (!searchId.trim()) return;
        router.get('/school/fees/payments/collect', { student_id: searchId }, { preserveScroll: true });
    }

    function onStructureChange(structId: string) {
        setData('fee_structure_id', structId);
        const struct = structures.find(s => String(s.id) === structId);
        if (struct) setData('amount_due', struct.amount);
    }

    const balance = (() => {
        const due    = Number(data.amount_due) || 0;
        const paid   = Number(data.amount_paid) || 0;
        const disc   = Number(data.discount) || 0;
        const fine   = Number(data.fine) || 0;
        return Math.max(0, due + fine - disc - paid);
    })();

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/school/fees/payments');
    }

    return (
        <AppLayout title="Collect Fee">
            <div className="max-w-2xl mx-auto space-y-6">
                <div className="flex items-center gap-3">
                    <Link href="/school/fees/payments" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                        <ArrowLeft className="w-4 h-4" /> Payments
                    </Link>
                    <span className="text-slate-300 dark:text-slate-700">|</span>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Collect Fee</h1>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                {/* Student Search */}
                <Card className="border-slate-200 dark:border-slate-800">
                    <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Search className="w-4 h-4" /> Find Student</CardTitle></CardHeader>
                    <CardContent>
                        <div className="flex gap-2">
                            <Input
                                placeholder="Student ID (e.g. STU-2026-0001)"
                                value={searchId}
                                onChange={e => setSearchId(e.target.value)}
                                onKeyDown={e => e.key === 'Enter' && searchStudent()}
                                className="flex-1"
                            />
                            <Button type="button" onClick={searchStudent} variant="outline" className="inline-flex items-center gap-2">
                                <Search className="w-4 h-4" /> Search
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Student Info */}
                {student && (
                    <Card className="border-indigo-200 dark:border-indigo-900 bg-indigo-50 dark:bg-indigo-950/20">
                        <CardContent className="p-4 flex items-center gap-4">
                            <div className="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-sm">
                                {student.first_name[0]}{student.last_name?.[0] ?? ''}
                            </div>
                            <div>
                                <p className="font-semibold text-slate-900 dark:text-white">{student.first_name} {student.last_name}</p>
                                <p className="text-sm text-slate-500">ID: {student.admission_no} · {student.school_class?.name}</p>
                            </div>
                            <Badge className="ml-auto bg-indigo-100 text-indigo-700 border-0">Found</Badge>
                        </CardContent>
                    </Card>
                )}

                {/* Payment Form */}
                {student && (
                    <Card className="border-slate-200 dark:border-slate-800">
                        <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><DollarSign className="w-4 h-4" /> Payment Details</CardTitle></CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div className="space-y-1.5">
                                    <Label>Fee Structure <span className="text-red-500">*</span></Label>
                                    <Select value={data.fee_structure_id} onValueChange={onStructureChange}>
                                        <SelectTrigger><SelectValue placeholder="Select fee type" /></SelectTrigger>
                                        <SelectContent>
                                            {structures.map(s => (
                                                <SelectItem key={s.id} value={String(s.id)}>
                                                    {s.fee_category?.name} — ৳{Number(s.amount).toLocaleString()} ({s.frequency}) · {s.academic_year}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.fee_structure_id && <p className="text-xs text-red-500">{errors.fee_structure_id}</p>}
                                </div>

                                <div className="grid grid-cols-2 gap-3">
                                    <div className="space-y-1.5">
                                        <Label>Amount Due (৳) <span className="text-red-500">*</span></Label>
                                        <Input type="number" min="0" step="0.01" value={data.amount_due} onChange={e => setData('amount_due', e.target.value)} />
                                        {errors.amount_due && <p className="text-xs text-red-500">{errors.amount_due}</p>}
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>Month/Year (for monthly)</Label>
                                        <Input type="month" value={data.month_year} onChange={e => setData('month_year', e.target.value)} />
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-3">
                                    <div className="space-y-1.5">
                                        <Label>Discount (৳)</Label>
                                        <Input type="number" min="0" step="0.01" value={data.discount} onChange={e => setData('discount', e.target.value)} />
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>Fine (৳)</Label>
                                        <Input type="number" min="0" step="0.01" value={data.fine} onChange={e => setData('fine', e.target.value)} />
                                    </div>
                                </div>

                                {/* Net Due Summary */}
                                <div className="rounded-lg bg-slate-50 dark:bg-slate-900 px-4 py-3 flex items-center justify-between">
                                    <div className="text-sm text-slate-500">Net Due</div>
                                    <div className="font-bold text-lg text-slate-900 dark:text-white">
                                        ৳{(Number(data.amount_due || 0) + Number(data.fine || 0) - Number(data.discount || 0)).toLocaleString()}
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-3">
                                    <div className="space-y-1.5">
                                        <Label>Amount Paid (৳) <span className="text-red-500">*</span></Label>
                                        <Input type="number" min="0" step="0.01" value={data.amount_paid} onChange={e => setData('amount_paid', e.target.value)} />
                                        {errors.amount_paid && <p className="text-xs text-red-500">{errors.amount_paid}</p>}
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>Balance</Label>
                                        <div className={`h-10 rounded-md border px-3 flex items-center font-semibold ${balance > 0 ? 'text-red-600 border-red-200 bg-red-50 dark:bg-red-950/20' : 'text-green-600 border-green-200 bg-green-50 dark:bg-green-950/20'}`}>
                                            {balance > 0 ? `৳${balance.toLocaleString()}` : 'Fully Paid'}
                                        </div>
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-3">
                                    <div className="space-y-1.5">
                                        <Label>Payment Date <span className="text-red-500">*</span></Label>
                                        <Input type="date" value={data.payment_date} onChange={e => setData('payment_date', e.target.value)} />
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>Payment Method <span className="text-red-500">*</span></Label>
                                        <Select value={data.method} onValueChange={v => setData('method', v)}>
                                            <SelectTrigger><SelectValue /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="cash">Cash</SelectItem>
                                                <SelectItem value="card">Card</SelectItem>
                                                <SelectItem value="online">Online</SelectItem>
                                                <SelectItem value="bkash">bKash</SelectItem>
                                                <SelectItem value="nagad">Nagad</SelectItem>
                                                <SelectItem value="rocket">Rocket</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <div className="space-y-1.5">
                                    <Label>Note</Label>
                                    <Input value={data.note} onChange={e => setData('note', e.target.value)} placeholder="Optional note" />
                                </div>

                                <div className="flex justify-end gap-3 pt-2">
                                    <Link href="/school/fees/payments">
                                        <Button type="button" variant="ghost">Cancel</Button>
                                    </Link>
                                    <Button type="submit" disabled={processing} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                        {processing ? 'Processing...' : 'Record Payment'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {!student && (
                    <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 py-20 text-center">
                        <User className="w-12 h-12 mx-auto text-slate-300 mb-3" />
                        <p className="text-slate-400">Search for a student by their ID to collect fees.</p>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
