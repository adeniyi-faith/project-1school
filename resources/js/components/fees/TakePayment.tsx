import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { METHOD_LABELS } from '@/components/fees/InvoiceStatus';
import { naira } from '@/lib/format';
import { Banknote } from 'lucide-react';
import type { PaymentMethod } from '@/Types';

interface FoundInvoice { id: number; invoice_no: string; fee: string; period: string; balance: number }
interface FoundStudent { id: number; name: string; admission_no: string; class: string | null; invoices: FoundInvoice[] }

const METHOD_ITEMS = (Object.keys(METHOD_LABELS) as PaymentMethod[]).map(m => ({ value: m, label: METHOD_LABELS[m] }));

function today() {
    return new Date().toISOString().slice(0, 10);
}

/** A big button that opens a quick "type a name, take the money" box: student, invoice, amount, method, done. */
export function TakePayment({ className }: { className?: string }) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<FoundStudent[]>([]);
    const [student, setStudent] = useState<FoundStudent | null>(null);
    const [invoiceId, setInvoiceId] = useState<number | null>(null);
    const [amount, setAmount] = useState('');
    const [method, setMethod] = useState<PaymentMethod>('cash');
    const [note, setNote] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [busy, setBusy] = useState(false);

    // Look students up a moment after the person stops typing
    useEffect(() => {
        if (!open || student || query.trim().length < 2) { setResults([]); return; }
        const timer = setTimeout(() => {
            fetch(`/school/fees/invoices/find?q=${encodeURIComponent(query.trim())}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                .then(r => (r.ok ? r.json() : { data: [] }))
                .then(r => setResults(r.data))
                .catch(() => setResults([]));
        }, 250);
        return () => clearTimeout(timer);
    }, [query, open, student]);

    function reset() {
        setQuery(''); setResults([]); setStudent(null); setInvoiceId(null); setAmount(''); setMethod('cash'); setNote(''); setErrors({});
    }

    function choose(s: FoundStudent) {
        setStudent(s);
        // One open invoice: pick it for them, with the full balance ready to take
        if (s.invoices.length === 1) { setInvoiceId(s.invoices[0].id); setAmount(String(s.invoices[0].balance)); }
    }

    function pickInvoice(id: number) {
        setInvoiceId(id);
        const inv = student?.invoices.find(i => i.id === id);
        if (inv) setAmount(String(inv.balance));
    }

    const invoice = student?.invoices.find(i => i.id === invoiceId) ?? null;

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (!invoice) return;
        router.post(`/school/fees/invoices/${invoice.id}/payments`, { amount, method, entry_date: today(), note }, {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
            onError: errs => setErrors(errs as Record<string, string>),
            onSuccess: () => { setOpen(false); reset(); },
        });
    }

    return (
        <>
            <Button onClick={() => { reset(); setOpen(true); }} className={className ?? 'bg-emerald-600 text-white hover:bg-emerald-700'}>
                <Banknote className="size-4" /> Take a payment
            </Button>
            <Dialog open={open} onOpenChange={o => { setOpen(o); if (!o) reset(); }}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Take a payment</DialogTitle></DialogHeader>
                    <form onSubmit={submit} className="mt-2 space-y-4">
                        {!student ? (
                            <div className="space-y-1.5">
                                <Label htmlFor="tp-search">Student name or admission number</Label>
                                <Input id="tp-search" autoFocus autoComplete="off" value={query} onChange={e => setQuery(e.target.value)} placeholder="Start typing…" />
                                {query.trim().length >= 2 && results.length === 0 && <p className="text-xs text-slate-500">No student with an unpaid invoice matches that.</p>}
                                <ul className="space-y-1">
                                    {results.map(s => (
                                        <li key={s.id}>
                                            <button type="button" onClick={() => choose(s)} className="w-full rounded-lg border border-slate-200 px-3 py-2 text-left text-sm hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5">
                                                <span className="font-medium text-slate-900 dark:text-white">{s.name}</span>
                                                <span className="block text-xs text-slate-500">{s.class} · {s.admission_no} · owes {naira(s.invoices.reduce((t, i) => t + i.balance, 0))}</span>
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ) : (
                            <>
                                <div className="flex items-start justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm dark:bg-white/5">
                                    <span><span className="font-medium text-slate-900 dark:text-white">{student.name}</span><span className="block text-xs text-slate-500">{student.class} · {student.admission_no}</span></span>
                                    <button type="button" className="text-xs text-indigo-600 hover:underline" onClick={reset}>Change</button>
                                </div>
                                <div className="space-y-1.5">
                                    <Label>Which invoice</Label>
                                    <div className="space-y-1">
                                        {student.invoices.map(i => (
                                            <label key={i.id} className="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-white/10">
                                                <input type="radio" name="tp-invoice" checked={invoiceId === i.id} onChange={() => pickInvoice(i.id)} />
                                                <span className="flex-1">{i.fee}<span className="block text-xs text-slate-500">{i.period}</span></span>
                                                <span className="tabular-nums">{naira(i.balance)}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="space-y-1.5">
                                        <Label htmlFor="tp-amount">Amount (₦)</Label>
                                        <Input id="tp-amount" type="number" min="0.01" step="0.01" max={invoice?.balance} value={amount} onChange={e => setAmount(e.target.value)} />
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>Paid by</Label>
                                        <Select items={METHOD_ITEMS} value={method} onValueChange={v => v && setMethod(v as PaymentMethod)}>
                                            <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                            <SelectContent>{METHOD_ITEMS.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                                        </Select>
                                    </div>
                                </div>
                                {(errors.amount || errors.method) && <p className="text-xs text-red-500">{errors.amount ?? errors.method}</p>}
                                <div className="space-y-1.5">
                                    <Label htmlFor="tp-note">Bank or POS reference (optional)</Label>
                                    <Input id="tp-note" value={note} onChange={e => setNote(e.target.value)} />
                                </div>
                            </>
                        )}
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={busy || !invoice || !amount} className="bg-emerald-600 text-white hover:bg-emerald-700">Record payment</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
