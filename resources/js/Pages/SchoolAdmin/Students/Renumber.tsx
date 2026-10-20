import { router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PageHeader, Panel } from '@/components/app/kit';

interface Options { class_id: number | null; order: string; prefix: string; start: number; digits: number }
interface Row { id: number; name: string; old: string; new: string }
interface Props {
    classes: { id: number; name: string }[];
    orders: Record<string, string>;
    options: Options;
    plan: { rows: Row[]; problem: string | null } | null;
}

const ALL = 'all';

export default function Renumber({ classes, orders, options, plan }: Props) {
    const form = useForm({
        class_id: options.class_id ? String(options.class_id) : ALL,
        order: options.order,
        prefix: options.prefix,
        start: String(options.start),
        digits: String(options.digits),
    });

    const params = () => ({ ...form.data, class_id: form.data.class_id === ALL ? '' : form.data.class_id });
    const changed = plan ? plan.rows.filter(r => r.old !== r.new).length : 0;

    function apply() {
        if (!confirm(`Change ${changed} admission numbers? Printed ID cards and slips that show the old number will need reprinting.`)) return;
        router.post('/school/students/renumber', params());
    }

    return (
        <AppLayout title="Renumber admission numbers">
            <div className="space-y-6">
                <PageHeader
                    title="Renumber admission numbers"
                    description="Give active students numbers in register order (A to Z), instead of the order they were entered. You see the new numbers first. Nothing changes until you confirm."
                />
                <Panel>
                    <div className="grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-5">
                        <div className="space-y-1.5">
                            <Label>Who</Label>
                            <Select value={form.data.class_id} onValueChange={v => form.setData('class_id', v ?? ALL)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>Whole school</SelectItem>
                                    {classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Order</Label>
                            <Select value={form.data.order} onValueChange={v => form.setData('order', v ?? 'surname')}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {Object.entries(orders).map(([k, label]) => <SelectItem key={k} value={k}>{label}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1.5"><Label>Starts with</Label><Input value={form.data.prefix} onChange={e => form.setData('prefix', e.target.value)} /></div>
                        <div className="space-y-1.5"><Label>First number</Label><Input type="number" min={1} value={form.data.start} onChange={e => form.setData('start', e.target.value)} /></div>
                        <div className="space-y-1.5"><Label>Digits</Label><Input type="number" min={1} max={8} value={form.data.digits} onChange={e => form.setData('digits', e.target.value)} /></div>
                    </div>
                    <div className="flex flex-wrap gap-2 border-t border-slate-100 p-4 dark:border-slate-800">
                        <Button onClick={() => router.get('/school/students/renumber', { ...params(), preview: 1 })}>Show new numbers</Button>
                        {plan && !plan.problem && changed > 0 && <Button variant="outline" onClick={apply}>Change {changed} numbers</Button>}
                    </div>
                    {form.errors.prefix && <p className="px-4 pb-4 text-sm text-red-600">{form.errors.prefix}</p>}
                </Panel>

                {plan && (
                    <Panel>
                        {plan.problem && <p className="p-4 text-sm text-red-600">{plan.problem}</p>}
                        {!plan.problem && changed === 0 && <p className="p-4 text-sm text-slate-500">These students already have the numbers you chose.</p>}
                        <Table>
                            <TableHeader><TableRow><TableHead>Student</TableHead><TableHead>Now</TableHead><TableHead>New</TableHead></TableRow></TableHeader>
                            <TableBody>
                                {plan.rows.map(r => (
                                    <TableRow key={r.id}>
                                        <TableCell>{r.name}</TableCell>
                                        <TableCell className="font-mono text-[13px] text-slate-500">{r.old}</TableCell>
                                        <TableCell className={'font-mono text-[13px] ' + (r.old !== r.new ? 'font-semibold text-indigo-600' : 'text-slate-500')}>{r.new}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </Panel>
                )}
            </div>
        </AppLayout>
    );
}
