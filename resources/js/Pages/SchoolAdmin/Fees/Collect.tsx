import { useState } from 'react';
import { useForm, router, usePage, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { EmptyState, PageHeader, Panel, PersonAvatar, Pill } from '@/components/app/kit';
import { naira } from '@/lib/format';
import { cn } from '@/lib/utils';
import { ArrowLeft, Search, User } from 'lucide-react';
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

    const netDue = (Number(data.amount_due) || 0) + (Number(data.fine) || 0) - (Number(data.discount) || 0);
    const err = (m?: string) => m && <p className="text-xs text-red-600">{m}</p>;
    const fieldInput = 'h-10';

    return (
        <AppLayout breadcrumbs={[{ label: 'Fees' }, { label: 'Payments', href: '/school/fees/payments' }, { label: 'Collect fee' }]}>
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader
                    title="Collect fee"
                    description="Find the student, choose the fee, and record what was paid."
                    actions={
                        <Link href="/school/fees/payments" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 shadow-xs hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                            <ArrowLeft className="size-4" /> Payments
                        </Link>
                    }
                />

                {flash?.success && (
                    <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">{flash.success}</div>
                )}

                <Panel title="Find student" description="Search with the student's ID.">
                    <div className="flex gap-2">
                        <div className="relative flex-1">
                            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                placeholder="e.g. STU-2026-0001"
                                value={searchId}
                                onChange={e => setSearchId(e.target.value)}
                                onKeyDown={e => e.key === 'Enter' && searchStudent()}
                                className="h-10 pl-9"
                            />
                        </div>
                        <Button type="button" onClick={searchStudent} variant="outline" className="h-10 px-4">Search</Button>
                    </div>
                </Panel>

                {!student ? (
                    <Panel><EmptyState icon={User} title="No student selected" text="Search for a student by their ID to start collecting a fee." /></Panel>
                ) : (
                    <form onSubmit={handleSubmit} className="grid gap-6 lg:grid-cols-[1fr_20rem] lg:items-start">
                        <Panel title="Payment details">
                            <div className="space-y-5">
                                <div className="space-y-1.5">
                                    <Label>Fee <span className="text-red-500">*</span></Label>
                                    <Select value={data.fee_structure_id} onValueChange={onStructureChange}>
                                        <SelectTrigger className="h-10 w-full"><SelectValue placeholder="Select fee type" /></SelectTrigger>
                                        <SelectContent>
                                            {structures.map(st => (
                                                <SelectItem key={st.id} value={String(st.id)}>
                                                    {st.fee_category?.name} · {naira(Number(st.amount))} ({st.frequency}) · {st.academic_year}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {err(errors.fee_structure_id)}
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-1.5">
                                        <Label>Amount due (₦) <span className="text-red-500">*</span></Label>
                                        <Input className={cn(fieldInput, 'tabular-nums')} type="number" inputMode="decimal" min="0" step="0.01" value={data.amount_due} onChange={e => setData('amount_due', e.target.value)} />
                                        {err(errors.amount_due)}
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>Month (for monthly fees)</Label>
                                        <Input className={fieldInput} type="month" value={data.month_year} onChange={e => setData('month_year', e.target.value)} />
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>Discount (₦)</Label>
                                        <Input className={cn(fieldInput, 'tabular-nums')} type="number" inputMode="decimal" min="0" step="0.01" value={data.discount} onChange={e => setData('discount', e.target.value)} />
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>Late fine (₦)</Label>
                                        <Input className={cn(fieldInput, 'tabular-nums')} type="number" inputMode="decimal" min="0" step="0.01" value={data.fine} onChange={e => setData('fine', e.target.value)} />
                                    </div>
                                </div>

                                <div className="border-t border-slate-100 pt-5 dark:border-white/[0.06]">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="space-y-1.5">
                                            <Label>Amount paid (₦) <span className="text-red-500">*</span></Label>
                                            <Input className={cn(fieldInput, 'text-base font-medium tabular-nums')} type="number" inputMode="decimal" min="0" step="0.01" value={data.amount_paid} onChange={e => setData('amount_paid', e.target.value)} />
                                            {err(errors.amount_paid)}
                                        </div>
                                        <div className="space-y-1.5">
                                            <Label>Payment method <span className="text-red-500">*</span></Label>
                                            <Select value={data.method} onValueChange={v => setData('method', v)}>
                                                <SelectTrigger className="h-10 w-full"><SelectValue /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="cash">Cash</SelectItem>
                                                    <SelectItem value="bank_transfer">Bank transfer</SelectItem>
                                                    <SelectItem value="pos">POS</SelectItem>
                                                    <SelectItem value="card">Card</SelectItem>
                                                    <SelectItem value="online">Online (Paystack, Flutterwave)</SelectItem>
                                                    <SelectItem value="ussd">USSD</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-1.5">
                                            <Label>Payment date <span className="text-red-500">*</span></Label>
                                            <Input className={fieldInput} type="date" value={data.payment_date} onChange={e => setData('payment_date', e.target.value)} />
                                        </div>
                                        <div className="space-y-1.5">
                                            <Label>Note</Label>
                                            <Input className={fieldInput} value={data.note} onChange={e => setData('note', e.target.value)} placeholder="Optional" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Panel>

                        <div className="space-y-4 lg:sticky lg:top-20">
                            <Panel>
                                <div className="flex items-center gap-3">
                                    <PersonAvatar name={`${student.first_name} ${student.last_name ?? ''}`} className="size-11" />
                                    <div className="min-w-0">
                                        <p className="truncate font-medium text-slate-900 dark:text-white">{student.first_name} {student.last_name}</p>
                                        <p className="truncate text-xs text-slate-500">{student.admission_no}{student.school_class?.name ? ` · ${student.school_class.name}` : ''}</p>
                                    </div>
                                </div>
                                <dl className="mt-5 space-y-3 border-t border-slate-100 pt-4 text-sm dark:border-white/[0.06]">
                                    <div className="flex justify-between"><dt className="text-slate-500">Net due</dt><dd className="font-medium tabular-nums text-slate-900 dark:text-white">{naira(netDue)}</dd></div>
                                    <div className="flex justify-between"><dt className="text-slate-500">Paying now</dt><dd className="font-medium tabular-nums text-slate-900 dark:text-white">{naira(Number(data.amount_paid) || 0)}</dd></div>
                                    <div className="flex items-center justify-between border-t border-slate-100 pt-3 dark:border-white/[0.06]">
                                        <dt className="text-slate-500">Balance</dt>
                                        <dd>{netDue <= 0 ? <span className="text-slate-400">—</span> : balance > 0 ? <span className="text-base font-semibold tabular-nums text-red-700 dark:text-red-400">{naira(balance)}</span> : <Pill tone="good">Fully paid</Pill>}</dd>
                                    </div>
                                </dl>
                            </Panel>
                            <div className="fixed inset-x-0 bottom-[calc(4rem+env(safe-area-inset-bottom))] z-20 flex gap-2 border-t border-slate-200 bg-white/95 p-3 backdrop-blur lg:static lg:border-0 lg:bg-transparent lg:p-0 dark:border-white/10 dark:bg-slate-950/95">
                                <Button type="submit" disabled={processing} className="h-11 flex-1 lg:h-10">
                                    {processing ? 'Recording…' : 'Record payment'}
                                </Button>
                            </div>
                        </div>
                    </form>
                )}
            </div>
        </AppLayout>
    );
}
