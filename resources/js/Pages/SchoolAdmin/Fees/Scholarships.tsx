import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { EmptyState, PageHeader, Panel, Pill } from '@/components/app/kit';
import { naira } from '@/lib/format';
import { Gift, Pencil, Plus, Trash2, UserPlus } from 'lucide-react';
import type { Scholarship, ScholarshipAward } from '@/Types';

interface Props {
    scholarships: Scholarship[];
    awards: ScholarshipAward[];
    categories: { id: number; name: string }[];
    can: { manage: boolean };
}

const ALL_FEES = 'all';
const TYPE_ITEMS = [
    { value: 'percent', label: 'Percentage off' },
    { value: 'fixed', label: 'Fixed amount off' },
];

type PolicyForm = { name: string; type: 'percent' | 'fixed'; value: string; fee_category_id: string; description: string };

function describe(s: Scholarship) {
    return `${s.type === 'percent' ? `${s.value}% off` : `${naira(s.value)} off`} ${s.fee_category ? s.fee_category : 'every fee'}`;
}

function ErrorText({ children }: { children?: string }) {
    return children ? <p className="text-xs text-red-500">{children}</p> : null;
}

export default function Scholarships({ scholarships, awards, categories, can }: Props) {
    const canManage = can.manage;

    const [policyOpen, setPolicyOpen] = useState(false);
    const [editing, setEditing] = useState<Scholarship | null>(null);
    const [awardOpen, setAwardOpen] = useState(false);

    const policy = useForm<PolicyForm>({ name: '', type: 'percent', value: '', fee_category_id: ALL_FEES, description: '' });
    const award = useForm({ admission_no: '', scholarship_id: '', note: '' });

    const categoryItems = [{ value: ALL_FEES, label: 'Every fee' }, ...categories.map(c => ({ value: String(c.id), label: c.name }))];
    const active = scholarships.filter(s => s.is_active);

    function openPolicy(s: Scholarship | null) {
        policy.clearErrors();
        policy.setData(s
            ? { name: s.name, type: s.type, value: String(s.value), fee_category_id: s.fee_category_id ? String(s.fee_category_id) : ALL_FEES, description: s.description ?? '' }
            : { name: '', type: 'percent', value: '', fee_category_id: ALL_FEES, description: '' });
        setEditing(s);
        setPolicyOpen(true);
    }

    function savePolicy(e: React.FormEvent) {
        e.preventDefault();
        policy.transform(d => ({ ...d, fee_category_id: d.fee_category_id === ALL_FEES ? null : Number(d.fee_category_id) }));
        const opts = { preserveScroll: true, onSuccess: () => setPolicyOpen(false) };
        if (editing) policy.put(`/school/fees/scholarships/${editing.id}`, opts);
        else policy.post('/school/fees/scholarships', opts);
    }

    function remove(s: Scholarship) {
        if (confirm(`Remove "${s.name}"? Students who hold it keep the discounts already given, but it will not apply to new invoices.`)) {
            router.delete(`/school/fees/scholarships/${s.id}`, { preserveScroll: true });
        }
    }

    function revoke(a: ScholarshipAward) {
        if (confirm(`Stop ${a.scholarship} for ${a.student?.name}? Discounts already given stay; new invoices will not get it.`)) {
            router.post(`/school/fees/scholarships/awards/${a.id}/revoke`, {}, { preserveScroll: true });
        }
    }

    const headerActions = canManage && (
        <div className="flex gap-2">
            <Button variant="outline" onClick={() => openPolicy(null)}><Plus className="size-4" /> New scholarship</Button>
            <Button disabled={active.length === 0} onClick={() => { award.reset(); award.clearErrors(); setAwardOpen(true); }} className="bg-indigo-600 text-white hover:bg-indigo-700">
                <UserPlus className="size-4" /> Give to a student
            </Button>
        </div>
    );

    return (
        <AppLayout title="Scholarships">
            <div className="space-y-6">
                <PageHeader
                    title="Scholarships and discounts"
                    description="Name each discount once (staff child, sibling, merit ...), then give it to students. Every award records who approved it and why."
                    actions={headerActions}
                />
                {/* The header hides its buttons on phones, so they are repeated here */}
                <div className="md:hidden">{headerActions}</div>

                <Panel title="Scholarships" flush>
                    {scholarships.length === 0 ? (
                        <div className="pb-5"><EmptyState icon={Gift} title="No scholarships yet" text="Add one, such as “Staff child, 50% off tuition”." /></div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-5">Name</TableHead>
                                    <TableHead>Discount</TableHead>
                                    <TableHead className="text-right">Students</TableHead>
                                    {canManage && <TableHead className="pr-5"></TableHead>}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {scholarships.map(s => (
                                    <TableRow key={s.id}>
                                        <TableCell className="pl-5">
                                            <p className="font-medium text-slate-900 dark:text-white">{s.name}</p>
                                            {s.description && <p className="text-xs text-slate-500">{s.description}</p>}
                                        </TableCell>
                                        <TableCell>{describe(s)}</TableCell>
                                        <TableCell className="text-right tabular-nums">{s.holders}</TableCell>
                                        {canManage && (
                                            <TableCell className="pr-5 text-right whitespace-nowrap">
                                                <Button variant="ghost" size="sm" onClick={() => openPolicy(s)} aria-label={`Edit ${s.name}`}><Pencil className="size-3.5" /></Button>
                                                <Button variant="ghost" size="sm" onClick={() => remove(s)} aria-label={`Remove ${s.name}`}><Trash2 className="size-3.5" /></Button>
                                            </TableCell>
                                        )}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </Panel>

                <Panel title="Who has one" description="Stopping a scholarship keeps discounts already given. To take one back from an invoice, reverse the discount line on that invoice." flush>
                    {awards.length === 0 ? (
                        <div className="pb-5"><EmptyState icon={UserPlus} title="No students have a scholarship yet" /></div>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-5">Student</TableHead>
                                        <TableHead>Scholarship</TableHead>
                                        <TableHead>Approved by</TableHead>
                                        <TableHead>Reason</TableHead>
                                        <TableHead>Status</TableHead>
                                        {canManage && <TableHead className="pr-5"></TableHead>}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {awards.map(a => (
                                        <TableRow key={a.id}>
                                            <TableCell className="pl-5">
                                                <p className="font-medium text-slate-900 dark:text-white">{a.student?.name ?? 'Removed student'}</p>
                                                <p className="text-xs text-slate-500">{a.student?.class} · {a.student?.admission_no}</p>
                                            </TableCell>
                                            <TableCell>{a.scholarship}</TableCell>
                                            <TableCell>
                                                <p>{a.approved_by ?? '—'}</p>
                                                <p className="text-xs text-slate-500">{a.approved_at}</p>
                                            </TableCell>
                                            <TableCell className="max-w-64 text-xs text-slate-500">{a.note}</TableCell>
                                            <TableCell>{a.active ? <Pill tone="good">Active</Pill> : <Pill>Stopped</Pill>}</TableCell>
                                            {canManage && (
                                                <TableCell className="pr-5 text-right">
                                                    {a.active && <Button variant="ghost" size="sm" onClick={() => revoke(a)}>Stop</Button>}
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </Panel>
            </div>

            <Dialog open={policyOpen} onOpenChange={setPolicyOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>{editing ? 'Edit scholarship' : 'New scholarship'}</DialogTitle></DialogHeader>
                    <form onSubmit={savePolicy} className="mt-2 space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="sch-name">Name</Label>
                            <Input id="sch-name" value={policy.data.name} onChange={e => policy.setData('name', e.target.value)} placeholder="e.g. Staff child" />
                            <ErrorText>{policy.errors.name}</ErrorText>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Kind</Label>
                                <Select items={TYPE_ITEMS} value={policy.data.type} onValueChange={v => v && policy.setData('type', v as 'percent' | 'fixed')}>
                                    <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                    <SelectContent>{TYPE_ITEMS.map(t => <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>)}</SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="sch-value">{policy.data.type === 'percent' ? 'Percent' : 'Amount (₦)'}</Label>
                                <Input id="sch-value" type="number" min="0.01" step="0.01" max={policy.data.type === 'percent' ? 100 : undefined} value={policy.data.value} onChange={e => policy.setData('value', e.target.value)} />
                                <ErrorText>{policy.errors.value}</ErrorText>
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Comes off</Label>
                            <Select items={categoryItems} value={policy.data.fee_category_id} onValueChange={v => policy.setData('fee_category_id', v ?? ALL_FEES)}>
                                <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>{categoryItems.map(c => <SelectItem key={c.value} value={c.value}>{c.label}</SelectItem>)}</SelectContent>
                            </Select>
                            <ErrorText>{policy.errors.fee_category_id}</ErrorText>
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="sch-desc">Notes (optional)</Label>
                            <Input id="sch-desc" value={policy.data.description} onChange={e => policy.setData('description', e.target.value)} />
                        </div>
                        {editing && <p className="text-xs text-slate-500">Changes apply to new invoices. Invoices already made keep their discount.</p>}
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setPolicyOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={policy.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Save</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={awardOpen} onOpenChange={setAwardOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Give a scholarship to a student</DialogTitle></DialogHeader>
                    <form onSubmit={e => { e.preventDefault(); award.post('/school/fees/scholarships/awards', { preserveScroll: true, onSuccess: () => setAwardOpen(false) }); }} className="mt-2 space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="aw-adm">Admission number</Label>
                            <Input id="aw-adm" value={award.data.admission_no} onChange={e => award.setData('admission_no', e.target.value)} />
                            <ErrorText>{award.errors.admission_no}</ErrorText>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Scholarship</Label>
                            <Select items={active.map(s => ({ value: String(s.id), label: s.name }))} value={award.data.scholarship_id} onValueChange={v => award.setData('scholarship_id', v ?? '')}>
                                <SelectTrigger className="w-full"><SelectValue placeholder="Choose one" /></SelectTrigger>
                                <SelectContent>{active.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.name} · {describe(s)}</SelectItem>)}</SelectContent>
                            </Select>
                            <ErrorText>{award.errors.scholarship_id}</ErrorText>
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="aw-note">Why does this student get it?</Label>
                            <Textarea id="aw-note" value={award.data.note} onChange={e => award.setData('note', e.target.value)} placeholder="e.g. Mother teaches Maths here" />
                            <ErrorText>{award.errors.note}</ErrorText>
                        </div>
                        <p className="text-xs text-slate-500">You will be recorded as the person who approved it. It comes off this student's unpaid invoices now, and future ones.</p>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setAwardOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={award.processing || !award.data.scholarship_id} className="bg-indigo-600 text-white hover:bg-indigo-700">Give scholarship</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
