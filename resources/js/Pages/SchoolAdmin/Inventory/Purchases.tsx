import { useState } from 'react';
import { router, usePage, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ArrowLeft, Plus } from 'lucide-react';
import type { PageProps, PaginatedResponse } from '@/Types';

interface ItemOption { id: number; name: string; unit: string; }
interface Purchase {
    id: number; vendor: string | null; purchase_date: string; quantity: string;
    unit_price: string; total_price: string; invoice_no: string | null; notes: string | null;
    item?: { id: number; name: string; unit: string };
}
interface Props {
    purchases: PaginatedResponse<Purchase>;
    items: ItemOption[];
    filters: { item_id?: string };
}

const empty = { item_id: '', vendor: '', purchase_date: new Date().toISOString().split('T')[0], quantity: '', unit_price: '', invoice_no: '', notes: '' };

export default function InventoryPurchases({ purchases, items, filters }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen]   = useState(false);
    const [form, setForm]   = useState(empty);
    const [saving, setSaving] = useState(false);

    function applyFilter(key: string, value: string) {
        router.get('/school/inventory/purchases', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    function handleSave() {
        setSaving(true);
        router.post('/school/inventory/purchases', form, {
            preserveScroll: true,
            onSuccess: () => { setOpen(false); setForm(empty); setSaving(false); },
            onError:   () => setSaving(false),
        });
    }

    const total = (() => {
        const q = Number(form.quantity) || 0;
        const p = Number(form.unit_price) || 0;
        return (q * p).toLocaleString('en', { minimumFractionDigits: 2 });
    })();

    return (
        <AppLayout title="Inventory Purchases">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link href="/school/inventory/items" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Items
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <div>
                            <h1 className="text-xl font-bold text-slate-900 dark:text-white">Purchase History</h1>
                            <p className="text-sm text-slate-500">{purchases.meta?.total ?? 0} records</p>
                        </div>
                    </div>
                    <Button onClick={() => setOpen(true)} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                        <Plus className="w-4 h-4" /> Record Purchase
                    </Button>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                {/* Filter */}
                <div className="flex gap-3">
                    <Select value={filters.item_id ?? ''} onValueChange={v => applyFilter('item_id', v)}>
                        <SelectTrigger className="w-52"><SelectValue placeholder="All Items" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Items</SelectItem>
                            {items.map(i => <SelectItem key={i.id} value={String(i.id)}>{i.name}</SelectItem>)}
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Date</TableHead>
                                <TableHead>Item</TableHead>
                                <TableHead>Vendor</TableHead>
                                <TableHead>Invoice</TableHead>
                                <TableHead className="text-right">Qty</TableHead>
                                <TableHead className="text-right">Unit Price</TableHead>
                                <TableHead className="text-right">Total</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {purchases.data.length === 0 ? (
                                <TableRow><TableCell colSpan={7} className="text-center py-16 text-slate-400">No purchases recorded.</TableCell></TableRow>
                            ) : purchases.data.map(p => (
                                <TableRow key={p.id}>
                                    <TableCell className="text-sm text-slate-500">{new Date(p.purchase_date).toLocaleDateString()}</TableCell>
                                    <TableCell>
                                        <p className="font-medium text-sm text-slate-900 dark:text-white">{p.item?.name}</p>
                                        <p className="text-xs text-slate-400">{p.item?.unit}</p>
                                    </TableCell>
                                    <TableCell className="text-sm text-slate-500">{p.vendor ?? '—'}</TableCell>
                                    <TableCell className="text-sm text-slate-400 font-mono">{p.invoice_no ?? '—'}</TableCell>
                                    <TableCell className="text-right text-sm">{Number(p.quantity).toLocaleString()}</TableCell>
                                    <TableCell className="text-right text-sm text-slate-500">৳{Number(p.unit_price).toLocaleString()}</TableCell>
                                    <TableCell className="text-right font-semibold text-sm text-slate-900 dark:text-white">৳{Number(p.total_price).toLocaleString()}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Record Purchase</DialogTitle></DialogHeader>
                    <div className="space-y-4 mt-2">
                        <div className="space-y-1.5">
                            <Label>Item <span className="text-red-500">*</span></Label>
                            <Select value={form.item_id} onValueChange={v => setForm(p => ({ ...p, item_id: v }))}>
                                <SelectTrigger><SelectValue placeholder="Select item" /></SelectTrigger>
                                <SelectContent>
                                    {items.map(i => <SelectItem key={i.id} value={String(i.id)}>{i.name} ({i.unit})</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Purchase Date <span className="text-red-500">*</span></Label>
                                <Input type="date" value={form.purchase_date} onChange={e => setForm(p => ({ ...p, purchase_date: e.target.value }))} />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Vendor</Label>
                                <Input value={form.vendor} onChange={e => setForm(p => ({ ...p, vendor: e.target.value }))} />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Quantity <span className="text-red-500">*</span></Label>
                                <Input type="number" min="0.01" step="0.01" value={form.quantity} onChange={e => setForm(p => ({ ...p, quantity: e.target.value }))} />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Unit Price (৳) <span className="text-red-500">*</span></Label>
                                <Input type="number" min="0" step="0.01" value={form.unit_price} onChange={e => setForm(p => ({ ...p, unit_price: e.target.value }))} />
                            </div>
                        </div>
                        {(form.quantity && form.unit_price) && (
                            <div className="rounded-lg bg-slate-50 dark:bg-slate-900 px-4 py-2 flex justify-between text-sm">
                                <span className="text-slate-500">Total</span>
                                <span className="font-bold text-slate-900 dark:text-white">৳{total}</span>
                            </div>
                        )}
                        <div className="space-y-1.5">
                            <Label>Invoice No.</Label>
                            <Input value={form.invoice_no} onChange={e => setForm(p => ({ ...p, invoice_no: e.target.value }))} />
                        </div>
                        <div className="space-y-1.5">
                            <Label>Notes</Label>
                            <Input value={form.notes} onChange={e => setForm(p => ({ ...p, notes: e.target.value }))} />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                        <Button onClick={handleSave} disabled={saving} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                            {saving ? 'Saving...' : 'Record'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
