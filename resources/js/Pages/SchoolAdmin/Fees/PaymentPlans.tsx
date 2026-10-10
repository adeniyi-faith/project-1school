import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { EmptyState, PageHeader, Panel, Pill } from '@/components/app/kit';
import { ArrowLeft, CalendarClock, Pencil, Plus, Trash2, X } from 'lucide-react';
import type { PlanPart } from '@/Types';

interface Plan { id: number; name: string; description: string | null; parts: PlanPart[]; is_active: boolean; open_invoices: number }
interface Props { plans: Plan[]; can: { manage: boolean } }

type PlanForm = { name: string; description: string; parts: PlanPart[] };

// Common ways Nigerian schools split a term's fees
const STARTERS: PlanForm[] = [
    { name: 'Two parts (60/40)', description: '60% at resumption, the rest by mid-term', parts: [{ label: 'At resumption', percent: 60 }, { label: 'By mid-term', percent: 40 }] },
    { name: 'Two equal parts', description: 'Half at resumption, half by mid-term', parts: [{ label: 'At resumption', percent: 50 }, { label: 'By mid-term', percent: 50 }] },
    { name: 'Three parts (40/30/30)', description: 'Spread across the term', parts: [{ label: 'At resumption', percent: 40 }, { label: 'Mid-term', percent: 30 }, { label: 'Before exams', percent: 30 }] },
];

export default function PaymentPlans({ plans, can }: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Plan | null>(null);
    const form = useForm<PlanForm>({ name: '', description: '', parts: [] });
    const errors = form.errors as Record<string, string | undefined>;
    const total = form.data.parts.reduce((sum, p) => sum + (Number(p.percent) || 0), 0);

    function start(plan: Plan | null, starter?: PlanForm) {
        form.clearErrors();
        form.setData(plan
            ? { name: plan.name, description: plan.description ?? '', parts: plan.parts.map(p => ({ label: p.label, percent: p.percent })) }
            : (starter ?? { name: '', description: '', parts: [{ label: 'First part', percent: 50 }, { label: 'Second part', percent: 50 }] }));
        setEditing(plan);
        setOpen(true);
    }

    function save(e: React.FormEvent) {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };
        if (editing) form.put(`/school/fees/payment-plans/${editing.id}`, opts);
        else form.post('/school/fees/payment-plans', opts);
    }

    function remove(plan: Plan) {
        if (confirm(`Remove "${plan.name}"? Invoices already split with it keep their parts.`)) {
            router.delete(`/school/fees/payment-plans/${plan.id}`, { preserveScroll: true });
        }
    }

    const setPart = (i: number, patch: Partial<PlanPart>) => form.setData('parts', form.data.parts.map((p, j) => (j === i ? { ...p, ...patch } : p)));

    return (
        <AppLayout title="Payment plans">
            <div className="space-y-6">
                <Link href="/school/fees/invoices" className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                    <ArrowLeft className="size-4" /> Invoices
                </Link>
                <PageHeader
                    title="Payment plans"
                    description="Saved ways of letting parents pay a fee in parts. Choose one when you create invoices, or use “Pay in parts” on the Invoices page. You set the due dates each term."
                    actions={can.manage ? <Button onClick={() => start(null)} className="bg-indigo-600 text-white hover:bg-indigo-700"><Plus className="size-4" /> New plan</Button> : undefined}
                />
                {can.manage && <div className="md:hidden"><Button onClick={() => start(null)} className="bg-indigo-600 text-white hover:bg-indigo-700"><Plus className="size-4" /> New plan</Button></div>}

                {plans.length === 0 ? (
                    <Panel>
                        <EmptyState icon={CalendarClock} title="No payment plans yet" text={can.manage ? 'Start from one of these common plans, or make your own.' : 'The bursar has not set up any plans yet.'} />
                        {can.manage && (
                            <div className="flex flex-wrap justify-center gap-2 pb-4">
                                {STARTERS.map(s => <Button key={s.name} variant="outline" onClick={() => start(null, s)}>{s.name}</Button>)}
                            </div>
                        )}
                    </Panel>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {plans.map(plan => (
                            <Panel
                                key={plan.id}
                                title={plan.name}
                                description={plan.description ?? undefined}
                                action={can.manage ? (
                                    <div className="flex gap-1">
                                        <Button variant="ghost" size="icon" aria-label={`Edit ${plan.name}`} onClick={() => start(plan)}><Pencil className="size-4" /></Button>
                                        <Button variant="ghost" size="icon" aria-label={`Remove ${plan.name}`} onClick={() => remove(plan)}><Trash2 className="size-4 text-red-500" /></Button>
                                    </div>
                                ) : undefined}
                            >
                                <ol className="space-y-1.5 text-sm">
                                    {plan.parts.map((p, i) => (
                                        <li key={i} className="flex justify-between"><span>{i + 1}. {p.label}</span><span className="font-medium tabular-nums">{p.percent}%</span></li>
                                    ))}
                                </ol>
                                <div className="mt-3 flex gap-2">
                                    {!plan.is_active && <Pill>Not offered</Pill>}
                                    <Pill tone={plan.open_invoices ? 'info' : 'neutral'}>{plan.open_invoices} open invoice{plan.open_invoices === 1 ? '' : 's'}</Pill>
                                </div>
                            </Panel>
                        ))}
                    </div>
                )}
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>{editing ? 'Edit payment plan' : 'New payment plan'}</DialogTitle></DialogHeader>
                    <form onSubmit={save} className="mt-2 space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="plan-name">Name</Label>
                            <Input id="plan-name" value={form.data.name} onChange={e => form.setData('name', e.target.value)} placeholder="e.g. Two parts (60/40)" />
                            {errors.name && <p className="text-xs text-red-500">{errors.name}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="plan-desc">Note for staff (optional)</Label>
                            <Input id="plan-desc" value={form.data.description} onChange={e => form.setData('description', e.target.value)} placeholder="e.g. For parents who ask to spread the fees" />
                        </div>
                        <div className="space-y-2">
                            <Label>Parts</Label>
                            {form.data.parts.map((p, i) => (
                                <div key={i} className="grid grid-cols-[1fr_5rem_1.75rem] items-center gap-2">
                                    <Input aria-label={`Part ${i + 1} name`} value={p.label} onChange={e => setPart(i, { label: e.target.value })} />
                                    <Input aria-label={`Part ${i + 1} share`} type="number" min={1} max={99} step="0.01" value={p.percent} onChange={e => setPart(i, { percent: e.target.value })} />
                                    {form.data.parts.length > 2 ? (
                                        <Button type="button" variant="ghost" size="icon" aria-label={`Remove part ${i + 1}`} onClick={() => form.setData('parts', form.data.parts.filter((_, j) => j !== i))}><X className="size-4" /></Button>
                                    ) : <span />}
                                </div>
                            ))}
                            <div className="flex items-center justify-between">
                                {form.data.parts.length < 6 ? (
                                    <Button type="button" variant="ghost" size="sm" onClick={() => form.setData('parts', [...form.data.parts, { label: `Part ${form.data.parts.length + 1}`, percent: 0 }])}><Plus className="size-4" /> Add a part</Button>
                                ) : <span />}
                                <span className={Math.abs(total - 100) < 0.01 ? 'text-xs text-emerald-600' : 'text-xs text-red-500'}>Adds up to {Number(total.toFixed(2))}%</span>
                            </div>
                            {Object.entries(errors).filter(([k]) => k.startsWith('parts')).map(([k, v]) => <p key={k} className="text-xs text-red-500">{v}</p>)}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Save plan</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
