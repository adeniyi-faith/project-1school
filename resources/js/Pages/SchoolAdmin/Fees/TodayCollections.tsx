import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { EmptyState, PageHeader, Panel, StatStrip } from '@/components/app/kit';
import { naira } from '@/lib/format';
import { Banknote, Printer } from 'lucide-react';

interface Payment {
    id: number; receipt: string | null; student: string | null; class: string | null; admission_no: string | null;
    invoice_id: number; fee: string; method: string | null; method_label: string; amount: number; by: string | null; reversed: boolean;
}
interface Props {
    date: string;
    total: number;
    count: number;
    by_method: { method: string; label: string; count: number; total: number }[];
    reversed_count: number;
    payments: Payment[];
}

const today = () => new Date().toISOString().slice(0, 10);

export default function TodayCollections({ date, total, count, by_method, reversed_count, payments }: Props) {
    const [day, setDay] = useState(date);
    const isToday = date === today();

    function go(d: string) {
        setDay(d);
        if (d) router.get('/school/fees/collections-today', { date: d }, { preserveState: true });
    }

    return (
        <AppLayout title="Today's Collections">
            <div className="space-y-6">
                <PageHeader
                    title={isToday ? "Today's Collections" : 'Collections for one day'}
                    description="Money received on one day, by how it was paid, so you can match it against the bank. Counts invoice payments only; payments that were reversed are left out of the totals."
                    actions={(
                        <div className="flex flex-wrap items-center gap-2">
                            <Input type="date" value={day} max={today()} onChange={e => go(e.target.value)} className="w-40" aria-label="Day" />
                            <a href={`/school/fees/collections-today/pdf?date=${date}`} target="_blank" rel="noreferrer">
                                <Button variant="outline"><Printer className="size-4" /> Print</Button>
                            </a>
                        </div>
                    )}
                />

                <StatStrip items={[
                    { label: 'Received', value: naira(total), tone: 'good', hint: `${count} payment${count === 1 ? '' : 's'}` },
                    ...by_method.map(m => ({ label: m.label, value: naira(m.total), hint: `${m.count} payment${m.count === 1 ? '' : 's'}` })),
                ]} />

                <Panel flush>
                    {payments.length === 0 ? (
                        <div className="pb-5"><EmptyState icon={Banknote} title="No payments on this day" text="Pick another day, or use Take a payment on the Invoices page." /></div>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-5">Receipt</TableHead>
                                        <TableHead>Student</TableHead>
                                        <TableHead>Fee</TableHead>
                                        <TableHead>Paid by</TableHead>
                                        <TableHead>Taken by</TableHead>
                                        <TableHead className="pr-5 text-right">Amount</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {payments.map(p => (
                                        <TableRow key={p.id} className={p.reversed ? 'text-slate-400 line-through' : undefined}>
                                            <TableCell className="pl-5 font-mono text-xs">
                                                <Link href={`/school/fees/invoices/${p.invoice_id}`} className="text-indigo-600 hover:underline dark:text-indigo-400">{p.receipt}</Link>
                                            </TableCell>
                                            <TableCell>
                                                <p className="font-medium">{p.student ?? 'Removed student'}</p>
                                                <p className="text-xs text-slate-500">{p.class} · {p.admission_no}</p>
                                            </TableCell>
                                            <TableCell>{p.fee}</TableCell>
                                            <TableCell>{p.method_label}</TableCell>
                                            <TableCell>{p.by ?? 'Online'}</TableCell>
                                            <TableCell className="pr-5 text-right tabular-nums">{naira(p.amount)}{p.reversed && <span className="block text-xs">reversed</span>}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </Panel>
                {reversed_count > 0 && <p className="text-sm text-slate-500">{reversed_count} payment{reversed_count === 1 ? ' was' : 's were'} reversed and {reversed_count === 1 ? 'is' : 'are'} not counted above.</p>}
            </div>
        </AppLayout>
    );
}
