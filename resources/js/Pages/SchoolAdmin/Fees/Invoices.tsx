import { useMemo, useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { EmptyState, PageHeader, Panel, StatStrip } from '@/components/app/kit';
import { InvoiceStatus } from '@/components/fees/InvoiceStatus';
import { naira } from '@/lib/format';
import { PlanPartsEditor, planPayload } from '@/components/fees/Instalments';
import { TakePayment } from '@/components/fees/TakePayment';
import { CalendarClock, Check, Copy, FileText, Gift, Phone, Plus } from 'lucide-react';
import type { InvoiceRow, PaymentPlanOption, PlanPart } from '@/Types';
import { Pager } from '@/components/app/Pager';

interface Structure { id: number; class_id: number; class_name: string | null; name: string; amount: number; academic_year: string; due_date: string | null }
interface TermOption { id: number; label: string; is_current: boolean; start: string | null; end: string | null }

interface Props {
    invoices: {
        data: InvoiceRow[];
        meta: { total: number; current_page: number; last_page: number; from: number | null; to: number | null };
        links: { prev: string | null; next: string | null };
    };
    stats: { outstanding: number; open_count: number; collected: number; behind_count: number; overdue: number };
    classes: { id: number; name: string }[];
    terms: TermOption[];
    structures: Structure[];
    plans: PaymentPlanOption[];
    filters: { status?: string; class_id?: string; term_id?: string; search?: string };
    can: { issue: boolean; collect: boolean };
}

const ALL = 'all';
const STATUS_ITEMS = [
    { value: ALL, label: 'Any status' },
    { value: 'open', label: 'Still owing' },
    { value: 'behind', label: 'Behind on parts' },
    { value: 'unpaid', label: 'Unpaid' },
    { value: 'partial', label: 'Part paid' },
    { value: 'paid', label: 'Paid' },
    { value: 'void', label: 'Cancelled' },
];

type IssueForm = { fee_structure_ids: number[]; term_id: string; period: string; due_date: string; plan_id: string; parts: PlanPart[]; replace: boolean };

export default function Invoices({ invoices, stats, classes, terms, structures, plans, filters, contacts, can }: Props) {
    const [copied, setCopied] = useState<'numbers' | 'list' | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');
    // 'issue' makes invoices (parts optional); 'plan' puts parts on invoices already made
    const [mode, setMode] = useState<'issue' | 'plan' | null>(null);
    const issueOpen = mode !== null;
    const setIssueOpen = (open: boolean) => setMode(open ? mode : null);
    const currentTerm = terms.find(t => t.is_current) ?? terms[0];
    const form = useForm<IssueForm>({ fee_structure_ids: [], term_id: currentTerm ? String(currentTerm.id) : '', period: '', due_date: '', plan_id: '', parts: [], replace: false });
    const chosenTerm = terms.find(t => String(t.id) === form.data.term_id) ?? null;
    const errors = form.errors as Record<string, string | undefined>;

    const classItems = [{ value: ALL, label: 'All classes' }, ...classes.map(c => ({ value: String(c.id), label: c.name }))];
    const termItems = [{ value: ALL, label: 'All terms' }, ...terms.map(t => ({ value: String(t.id), label: t.label }))];

    // Fee structures grouped by class, for the "create invoices" dialog
    const byClass = useMemo(() => {
        const groups = new Map<string, Structure[]>();
        structures.forEach(s => groups.set(s.class_name ?? 'No class', [...(groups.get(s.class_name ?? 'No class') ?? []), s]));
        return [...groups.entries()];
    }, [structures]);

    function filter(key: string, value: string | null) {
        router.get('/school/fees/invoices', { ...filters, [key]: value && value !== ALL ? value : undefined }, { preserveState: true, preserveScroll: true });
    }

    function copy(kind: 'numbers' | 'list') {
        if (!contacts) return;
        const text = kind === 'numbers'
            ? contacts.map(c => c.phone).join(', ')
            : contacts.map(c => `${c.name} (${c.children.join(', ')}) ${c.phone} - owes ${naira(c.owed)}`).join('\n');
        navigator.clipboard?.writeText(text).then(() => { setCopied(kind); setTimeout(() => setCopied(null), 2500); }).catch(() => undefined);
    }

    function toggle(id: number) {
        const ids = form.data.fee_structure_ids;
        form.setData('fee_structure_ids', ids.includes(id) ? ids.filter(x => x !== id) : [...ids, id]);
    }

    function issue(e: React.FormEvent) {
        e.preventDefault();
        const plan = planPayload(form.data.plan_id, form.data.parts);
        form.transform(d => ({
            fee_structure_ids: d.fee_structure_ids, term_id: d.term_id, period: d.period, replace: d.replace,
            due_date: d.parts.length ? '' : d.due_date, ...(d.parts.length ? plan : {}),
        }));
        form.post(mode === 'plan' ? '/school/fees/invoices/plans' : '/school/fees/invoices', {
            preserveScroll: true,
            onSuccess: () => { setMode(null); form.setData(d => ({ ...d, fee_structure_ids: [], plan_id: '', parts: [] })); },
        });
    }

    function openDialog(m: 'issue' | 'plan') {
        form.clearErrors();
        form.setData(d => ({ ...d, plan_id: '', parts: [], replace: false }));
        setMode(m);
    }

    const headerActions = (
        <div className="flex flex-wrap gap-2">
            {can.collect && <TakePayment />}
            <Link href="/school/fees/scholarships"><Button variant="outline"><Gift className="size-4" /> Scholarships</Button></Link>
            <Link href="/school/fees/payment-plans"><Button variant="outline"><CalendarClock className="size-4" /> Payment plans</Button></Link>
            {can.issue && (
                <Button variant="outline" onClick={() => openDialog('plan')}><CalendarClock className="size-4" /> Pay in parts</Button>
            )}
            {can.issue && (
                <Button onClick={() => openDialog('issue')} className="bg-indigo-600 text-white hover:bg-indigo-700">
                    <Plus className="size-4" /> Create invoices
                </Button>
            )}
        </div>
    );

    return (
        <AppLayout title="Invoices">
            <div className="space-y-6">
                <PageHeader
                    title="Invoices"
                    description="Each invoice is one fee for one student for one term. Payments, fines and discounts are added as lines underneath it."
                    actions={headerActions}
                />
                {/* The header hides its buttons on phones, so they are repeated here */}
                <div className="flex flex-wrap gap-2 md:hidden">{headerActions}</div>

                <StatStrip items={[
                    { label: 'Still owed', value: naira(stats.outstanding), tone: stats.outstanding > 0 ? 'bad' : 'default' },
                    { label: 'Open invoices', value: stats.open_count },
                    { label: 'Collected on invoices', value: naira(stats.collected), tone: 'good' },
                    { label: 'Overdue parts', value: naira(stats.overdue), tone: stats.overdue > 0 ? 'bad' : 'default', hint: stats.behind_count ? `${stats.behind_count} invoice${stats.behind_count === 1 ? '' : 's'} behind` : undefined },
                ]} />

                <div className="flex flex-wrap gap-3">
                    <form onSubmit={e => { e.preventDefault(); filter('search', search); }}>
                        <Input value={search} onChange={e => setSearch(e.target.value)} placeholder="Name, admission or invoice no." className="w-64" />
                    </form>
                    <Select items={classItems} value={filters.class_id ?? ALL} onValueChange={v => filter('class_id', v)}>
                        <SelectTrigger className="w-40" aria-label="Class"><SelectValue /></SelectTrigger>
                        <SelectContent>{classItems.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                    </Select>
                    <Select items={termItems} value={filters.term_id ?? ALL} onValueChange={v => filter('term_id', v)}>
                        <SelectTrigger className="w-56" aria-label="Term"><SelectValue /></SelectTrigger>
                        <SelectContent>{termItems.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                    </Select>
                    <Select items={STATUS_ITEMS} value={filters.status ?? ALL} onValueChange={v => filter('status', v)}>
                        <SelectTrigger className="w-36" aria-label="Status"><SelectValue /></SelectTrigger>
                        <SelectContent>{STATUS_ITEMS.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                    </Select>
                </div>

                {contacts && contacts.length > 0 && (
                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm dark:border-amber-500/20 dark:bg-amber-500/10">
                        <p className="text-amber-900 dark:text-amber-200">
                            <Phone className="mr-1.5 inline size-4" />
                            <strong>{contacts.length}</strong> parent{contacts.length === 1 ? '' : 's'} in this list still owe {naira(contacts.reduce((t, c) => t + c.owed, 0))}. Each phone number is counted once.
                        </p>
                        <div className="flex gap-2">
                            <Button size="sm" variant="outline" onClick={() => copy('numbers')}>{copied === 'numbers' ? <Check className="size-4" /> : <Copy className="size-4" />} {copied === 'numbers' ? 'Copied' : 'Copy numbers'}</Button>
                            <Button size="sm" variant="outline" onClick={() => copy('list')}>{copied === 'list' ? <Check className="size-4" /> : <Copy className="size-4" />} {copied === 'list' ? 'Copied' : 'Copy names and amounts'}</Button>
                        </div>
                    </div>
                )}

                <Panel flush>
                    {invoices.data.length === 0 ? (
                        <div className="pb-5">
                            <EmptyState icon={FileText} title="No invoices yet" text={can.issue ? 'Use "Create invoices" to bill a class for a term.' : 'Invoices will show here once they are created.'} />
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-5">Invoice</TableHead>
                                        <TableHead>Student</TableHead>
                                        <TableHead>Fee</TableHead>
                                        <TableHead className="text-right">Amount</TableHead>
                                        <TableHead className="text-right">Still owed</TableHead>
                                        <TableHead>Due</TableHead>
                                        <TableHead className="pr-5">Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {invoices.data.map(i => (
                                        <TableRow key={i.id} className="cursor-pointer" onClick={() => router.visit(`/school/fees/invoices/${i.id}`)}>
                                            <TableCell className="pl-5 font-mono text-xs">
                                                <Link href={`/school/fees/invoices/${i.id}`} className="text-indigo-600 hover:underline dark:text-indigo-400">{i.invoice_no}</Link>
                                            </TableCell>
                                            <TableCell>
                                                <p className="font-medium text-slate-900 dark:text-white">{i.student?.name ?? 'Removed student'}</p>
                                                <p className="text-xs text-slate-500">{i.student?.class} · {i.student?.admission_no}</p>
                                                {i.parent_phone && i.balance > 0 && (
                                                    <a href={`tel:${i.parent_phone}`} onClick={e => e.stopPropagation()} className="mt-0.5 inline-flex items-center gap-1 text-xs text-indigo-600 hover:underline dark:text-indigo-400">
                                                        <Phone className="size-3" /> {i.parent_phone}
                                                    </a>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <p>{i.fee}</p>
                                                <p className="text-xs text-slate-500">{i.period}</p>
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">{naira(i.amount)}</TableCell>
                                            <TableCell className="text-right font-medium tabular-nums">
                                                {i.balance > 0 ? naira(i.balance) : '—'}
                                                {i.status !== 'void' && i.instalments && i.instalments.overdue > 0 && <span className="block text-xs font-normal text-red-600">{naira(i.instalments.overdue)} overdue</span>}
                                            </TableCell>
                                            <TableCell className="text-xs text-slate-500">
                                                {i.instalments?.next && i.status !== 'void' ? (
                                                    <>{i.instalments.next.due_date}<span className="block">{i.instalments.next.label} of {i.instalments.parts.length}</span></>
                                                ) : (i.due_date ?? '—')}
                                            </TableCell>
                                            <TableCell className="pr-5"><InvoiceStatus status={i.status} /></TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                    <Pager meta={invoices.meta} links={invoices.links} />
                </Panel>
            </div>

            <Dialog open={issueOpen} onOpenChange={setIssueOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader><DialogTitle>{mode === 'plan' ? 'Pay in parts' : 'Create invoices'}</DialogTitle></DialogHeader>
                    <form onSubmit={issue} className="mt-2 space-y-4">
                        <p className="text-sm text-slate-500">{mode === 'plan'
                            ? 'Split invoices already made for these fees and term into dated parts. Parents see each part and when it is due. Payments already made count towards the first parts.'
                            : 'Every active student in the chosen classes gets one invoice per fee. Students who already have it for this term are skipped, so running this twice is safe. Scholarships they hold come off straight away.'}</p>
                        <div className="space-y-1.5">
                            <Label>Term</Label>
                            <Select items={terms.map(t => ({ value: String(t.id), label: t.label }))} value={form.data.term_id} onValueChange={v => form.setData('term_id', v ?? '')}>
                                <SelectTrigger className="w-full"><SelectValue placeholder="Choose a term" /></SelectTrigger>
                                <SelectContent>{terms.map(t => <SelectItem key={t.id} value={String(t.id)}>{t.label}</SelectItem>)}</SelectContent>
                            </Select>
                            {form.errors.term_id && <p className="text-xs text-red-500">{form.errors.term_id}</p>}
                            {form.errors.period && <p className="text-xs text-red-500">{form.errors.period}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label>{mode === 'plan' ? 'Fees' : 'Fees to bill'}</Label>
                            <div className="max-h-64 space-y-3 overflow-y-auto rounded-lg border border-slate-200 p-3 dark:border-white/10">
                                {byClass.length === 0 && <p className="text-sm text-slate-500">No fees set up yet. Add them under Fee Structures first.</p>}
                                {byClass.map(([className, list]) => (
                                    <div key={className}>
                                        <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{className}</p>
                                        {list.map(s => (
                                            <label key={s.id} className="flex items-center gap-2 py-1 text-sm">
                                                <Checkbox checked={form.data.fee_structure_ids.includes(s.id)} onCheckedChange={() => toggle(s.id)} />
                                                <span className="flex-1">{s.name} <span className="text-slate-400">· {s.academic_year}</span></span>
                                                <span className="tabular-nums">{naira(s.amount)}</span>
                                            </label>
                                        ))}
                                    </div>
                                ))}
                            </div>
                            {form.errors.fee_structure_ids && <p className="text-xs text-red-500">Choose at least one fee.</p>}
                        </div>
                        <PlanPartsEditor
                            plans={plans} planId={form.data.plan_id} parts={form.data.parts} allowNone={mode === 'issue'}
                            term={chosenTerm} errors={errors}
                            onChange={(plan_id, parts) => form.setData(d => ({ ...d, plan_id, parts }))}
                        />
                        {mode === 'issue' && form.data.parts.length === 0 && (
                            <div className="space-y-1.5">
                                <Label htmlFor="due">Due date (optional)</Label>
                                <Input id="due" type="date" value={form.data.due_date} onChange={e => form.setData('due_date', e.target.value)} />
                            </div>
                        )}
                        {mode === 'plan' && (
                            <label className="flex items-start gap-2 text-sm">
                                <Checkbox checked={form.data.replace} onCheckedChange={v => form.setData('replace', v === true)} className="mt-0.5" />
                                <span>Also replace parts on invoices that already have them <span className="block text-xs text-slate-500">Left off, those invoices keep their own arrangement.</span></span>
                            </label>
                        )}
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setIssueOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={form.processing || form.data.fee_structure_ids.length === 0 || (mode === 'plan' && form.data.parts.length === 0)} className="bg-indigo-600 text-white hover:bg-indigo-700">{mode === 'plan' ? 'Split into parts' : 'Create invoices'}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
