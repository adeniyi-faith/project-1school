import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Pill } from '@/components/app/kit';
import { naira } from '@/lib/format';
import { Plus, X } from 'lucide-react';
import type { InstalmentState, InstalmentSummary, PaymentPlanOption, PlanPart } from '@/Types';

const STATE: Record<InstalmentState, { tone: 'good' | 'bad' | 'warn' | 'neutral'; label: string }> = {
    paid: { tone: 'good', label: 'Paid' },
    overdue: { tone: 'bad', label: 'Overdue' },
    due: { tone: 'warn', label: 'Due today' },
    upcoming: { tone: 'neutral', label: 'Coming up' },
    cancelled: { tone: 'neutral', label: 'Cancelled' },
};

export function InstalmentPill({ state }: { state: InstalmentState }) {
    return <Pill tone={STATE[state].tone}>{STATE[state].label}</Pill>;
}

export function shortDate(iso: string) {
    return new Date(`${iso}T00:00:00`).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' });
}

/** The parts of one invoice: what each is, when it is due, and what is left on it */
export function InstalmentTable({ summary }: { summary: InstalmentSummary }) {
    return (
        <table className="w-full text-sm">
            <thead>
                <tr className="border-b border-slate-100 text-left text-xs text-slate-500 dark:border-white/[0.06]">
                    <th className="py-2 pr-3 font-medium">Part</th>
                    <th className="py-2 pr-3 font-medium">Due by</th>
                    <th className="py-2 pr-3 text-right font-medium">Amount</th>
                    <th className="py-2 pr-3 text-right font-medium">Still to pay</th>
                    <th className="py-2 font-medium">Status</th>
                </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                {summary.parts.map((p, i) => (
                    <tr key={i}>
                        <td className="py-2 pr-3">{p.label} <span className="text-xs text-slate-400">({p.percent}%)</span></td>
                        <td className="py-2 pr-3 whitespace-nowrap">{shortDate(p.due_date)}</td>
                        <td className="py-2 pr-3 text-right tabular-nums">{naira(p.amount, { kobo: true })}</td>
                        <td className="py-2 pr-3 text-right tabular-nums">{p.left > 0 ? naira(p.left, { kobo: true }) : '—'}</td>
                        <td className="py-2"><InstalmentPill state={p.state} /></td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

/** Spread n due dates across the term (or a month apart when the term has no dates) */
function suggestDates(n: number, term: { start: string | null; end: string | null } | null): string[] {
    const day = 86_400_000;
    const today = new Date(new Date().toISOString().slice(0, 10)).getTime();
    const start = term?.start ? Math.max(new Date(term.start).getTime(), today) : today;
    const end = term?.end ? new Date(term.end).getTime() : 0;
    const step = end > start ? (end - start) / n : 30 * day;
    return Array.from({ length: n }, (_, i) => new Date(start + Math.round(i * step / day) * day).toISOString().slice(0, 10));
}

export const CUSTOM = 'custom';
const NONE = 'none';

interface EditorProps {
    plans: PaymentPlanOption[];
    planId: string;
    parts: PlanPart[];
    onChange: (planId: string, parts: PlanPart[]) => void;
    term: { start: string | null; end: string | null } | null;
    errors: Record<string, string | undefined>;
    /** Offer "Pay in one go" (no parts) as the first choice */
    allowNone?: boolean;
}

/**
 * Pick a saved plan (or make one up) and set each part's due date. Dates start spread across the term;
 * every label, share and date can be changed.
 */
export function PlanPartsEditor({ plans, planId, parts, onChange, term, errors, allowNone = false }: EditorProps) {
    const items = [
        ...(allowNone ? [{ value: NONE, label: 'Pay in one go' }] : []),
        ...plans.map(p => ({ value: String(p.id), label: p.name })),
        { value: CUSTOM, label: 'My own parts' },
    ];
    const total = parts.reduce((sum, p) => sum + (Number(p.percent) || 0), 0);

    function choose(value: string | null) {
        const v = value === NONE ? '' : (value ?? '');
        if (v === '') return onChange('', []);
        const plan = plans.find(p => String(p.id) === v);
        const base: PlanPart[] = plan ? plan.parts : [{ label: 'First part', percent: 50 }, { label: 'Second part', percent: 50 }];
        const dates = suggestDates(base.length, term);
        onChange(v, base.map((p, i) => ({ label: p.label, percent: p.percent, due_date: dates[i] })));
    }

    function set(i: number, patch: Partial<PlanPart>) {
        // Changing a part makes it the school's own arrangement unless it was already
        onChange(planId === '' ? CUSTOM : planId, parts.map((p, j) => (j === i ? { ...p, ...patch } : p)));
    }

    function add() {
        const last = parts[parts.length - 1]?.due_date ?? new Date().toISOString().slice(0, 10);
        onChange(planId, [...parts, { label: `Part ${parts.length + 1}`, percent: 0, due_date: last }]);
    }

    const partErrors = Object.entries(errors).filter(([k]) => k.startsWith('parts.')).map(([, v]) => v);

    return (
        <div className="space-y-3">
            <div className="space-y-1.5">
                <Label>Payment plan</Label>
                <Select items={items} value={planId || (allowNone ? NONE : null)} onValueChange={choose}>
                    <SelectTrigger className="w-full"><SelectValue placeholder="Choose a plan" /></SelectTrigger>
                    <SelectContent>{items.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                </Select>
            </div>
            {parts.length > 0 && (
                <div className="space-y-2 rounded-lg border border-slate-200 p-3 dark:border-white/10">
                    <div className="grid grid-cols-[1fr_4.5rem_8.5rem_1.75rem] gap-2 text-xs font-medium text-slate-500">
                        <span>Part</span><span>Share %</span><span>Due by</span><span />
                    </div>
                    {parts.map((p, i) => (
                        <div key={i} className="grid grid-cols-[1fr_4.5rem_8.5rem_1.75rem] items-center gap-2">
                            <Input aria-label={`Part ${i + 1} name`} value={p.label} onChange={e => set(i, { label: e.target.value })} />
                            <Input aria-label={`Part ${i + 1} share`} type="number" min={1} max={99} step="0.01" value={p.percent} onChange={e => set(i, { percent: e.target.value })} />
                            <Input aria-label={`Part ${i + 1} due date`} type="date" value={p.due_date ?? ''} onChange={e => set(i, { due_date: e.target.value })} />
                            {parts.length > 2 ? (
                                <Button type="button" variant="ghost" size="icon" aria-label={`Remove part ${i + 1}`} onClick={() => onChange(planId === '' ? CUSTOM : planId, parts.filter((_, j) => j !== i))}><X className="size-4" /></Button>
                            ) : <span />}
                        </div>
                    ))}
                    <div className="flex items-center justify-between pt-1">
                        {parts.length < 6 ? <Button type="button" variant="ghost" size="sm" onClick={add}><Plus className="size-4" /> Add a part</Button> : <span />}
                        <span className={Math.abs(total - 100) < 0.01 ? 'text-xs text-emerald-600' : 'text-xs text-red-500'}>Adds up to {Number(total.toFixed(2))}%</span>
                    </div>
                </div>
            )}
            {errors.parts && <p className="text-xs text-red-500">{errors.parts}</p>}
            {partErrors.map((e, i) => <p key={i} className="text-xs text-red-500">{e}</p>)}
        </div>
    );
}

/** What gets sent: the saved plan's id only when the parts came from one */
export function planPayload(planId: string, parts: PlanPart[]) {
    return { payment_plan_id: planId && planId !== CUSTOM ? Number(planId) : null, parts };
}
