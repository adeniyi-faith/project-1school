import { useState } from 'react';
import { router, usePage, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ArrowLeft, Plus, RotateCcw } from 'lucide-react';
import type { PageProps, PaginatedResponse } from '@/Types';

interface ItemOption  { id: number; name: string; unit: string; current_stock: string; }
interface StaffOption { id: number; first_name: string; last_name: string | null; emp_id: string; }
interface Issue {
    id: number; quantity: string; issue_date: string; return_date: string | null;
    purpose: string | null; status: string; issued_to_name: string; issued_to_type: string;
    item?: { id: number; name: string; unit: string };
}
interface Props {
    issues: PaginatedResponse<Issue>;
    items: ItemOption[];
    staffList: StaffOption[];
    filters: { status?: string };
}

const STATUS_STYLE: Record<string, string> = {
    issued:   'bg-blue-100 text-blue-700',
    returned: 'bg-green-100 text-green-700',
    partial:  'bg-amber-100 text-amber-700',
};

const issueDefault = { item_id: '', issued_to_type: 'staff', issued_to_id: '', issued_to_name: '', quantity: '', issue_date: new Date().toISOString().split('T')[0], purpose: '', notes: '' };

export default function InventoryIssues({ issues, items, staffList, filters }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen]         = useState(false);
    const [returnOpen, setReturnOpen] = useState<Issue | null>(null);
    const [form, setForm]         = useState(issueDefault);
    const [returnForm, setReturnForm] = useState({ return_date: new Date().toISOString().split('T')[0], returned_quantity: '' });
    const [saving, setSaving]     = useState(false);

    function applyFilter(key: string, value: string) {
        router.get('/school/inventory/issues', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    function handleStaffChange(staffId: string) {
        const s = staffList.find(m => String(m.id) === staffId);
        setForm(p => ({ ...p, issued_to_id: staffId, issued_to_name: s ? `${s.first_name} ${s.last_name ?? ''}`.trim() : '' }));
    }

    function handleIssue() {
        setSaving(true);
        router.post('/school/inventory/issues', form, {
            preserveScroll: true,
            onSuccess: () => { setOpen(false); setForm(issueDefault); setSaving(false); },
            onError:   () => setSaving(false),
        });
    }

    function handleReturn() {
        if (!returnOpen) return;
        setSaving(true);
        router.put(`/school/inventory/issues/${returnOpen.id}/return`, returnForm, {
            preserveScroll: true,
            onSuccess: () => { setReturnOpen(null); setSaving(false); },
            onError:   () => setSaving(false),
        });
    }

    return (
        <AppLayout title="Inventory Issues">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link href="/school/inventory/items" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Items
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <div>
                            <h1 className="text-xl font-bold text-slate-900 dark:text-white">Issue / Return</h1>
                            <p className="text-sm text-slate-500">{issues.meta?.total ?? 0} records</p>
                        </div>
                    </div>
                    <Button onClick={() => setOpen(true)} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                        <Plus className="w-4 h-4" /> Issue Item
                    </Button>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                <div className="flex gap-3">
                    <Select value={filters.status ?? ''} onValueChange={v => applyFilter('status', v)}>
                        <SelectTrigger className="w-36"><SelectValue placeholder="All Status" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All</SelectItem>
                            <SelectItem value="issued">Issued</SelectItem>
                            <SelectItem value="returned">Returned</SelectItem>
                            <SelectItem value="partial">Partial</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Item</TableHead>
                                <TableHead>Issued To</TableHead>
                                <TableHead>Purpose</TableHead>
                                <TableHead className="text-right">Qty</TableHead>
                                <TableHead>Issue Date</TableHead>
                                <TableHead>Return Date</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-20"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {issues.data.length === 0 ? (
                                <TableRow><TableCell colSpan={8} className="text-center py-16 text-slate-400">No issue records found.</TableCell></TableRow>
                            ) : issues.data.map(iss => (
                                <TableRow key={iss.id}>
                                    <TableCell>
                                        <p className="font-medium text-sm text-slate-900 dark:text-white">{iss.item?.name}</p>
                                        <p className="text-xs text-slate-400">{iss.item?.unit}</p>
                                    </TableCell>
                                    <TableCell>
                                        <p className="text-sm text-slate-700 dark:text-slate-300">{iss.issued_to_name}</p>
                                        <p className="text-xs text-slate-400 capitalize">{iss.issued_to_type}</p>
                                    </TableCell>
                                    <TableCell className="text-sm text-slate-500">{iss.purpose ?? '—'}</TableCell>
                                    <TableCell className="text-right text-sm font-medium">{Number(iss.quantity).toLocaleString()}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{new Date(iss.issue_date).toLocaleDateString()}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{iss.return_date ? new Date(iss.return_date).toLocaleDateString() : '—'}</TableCell>
                                    <TableCell>
                                        <Badge className={`border-0 text-xs capitalize ${STATUS_STYLE[iss.status] ?? ''}`}>{iss.status}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        {iss.status !== 'returned' && (
                                            <Button size="sm" variant="outline" className="text-xs h-7 inline-flex items-center gap-1"
                                                onClick={() => { setReturnOpen(iss); setReturnForm({ return_date: new Date().toISOString().split('T')[0], returned_quantity: iss.quantity }); }}>
                                                <RotateCcw className="w-3 h-3" /> Return
                                            </Button>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            {/* Issue Dialog */}
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Issue Item</DialogTitle></DialogHeader>
                    <div className="space-y-4 mt-2">
                        <div className="space-y-1.5">
                            <Label>Item <span className="text-red-500">*</span></Label>
                            <Select value={form.item_id} onValueChange={v => setForm(p => ({ ...p, item_id: v }))}>
                                <SelectTrigger><SelectValue placeholder="Select item" /></SelectTrigger>
                                <SelectContent>
                                    {items.map(i => (
                                        <SelectItem key={i.id} value={String(i.id)}>
                                            {i.name} — {Number(i.current_stock).toLocaleString()} {i.unit} avail.
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Issue To (Staff) <span className="text-red-500">*</span></Label>
                            <Select value={form.issued_to_id} onValueChange={handleStaffChange}>
                                <SelectTrigger><SelectValue placeholder="Select staff" /></SelectTrigger>
                                <SelectContent>
                                    {staffList.map(s => (
                                        <SelectItem key={s.id} value={String(s.id)}>
                                            {s.first_name} {s.last_name} ({s.emp_id})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Quantity <span className="text-red-500">*</span></Label>
                                <Input type="number" min="0.01" step="0.01" value={form.quantity} onChange={e => setForm(p => ({ ...p, quantity: e.target.value }))} />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Issue Date <span className="text-red-500">*</span></Label>
                                <Input type="date" value={form.issue_date} onChange={e => setForm(p => ({ ...p, issue_date: e.target.value }))} />
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Purpose</Label>
                            <Input value={form.purpose} onChange={e => setForm(p => ({ ...p, purpose: e.target.value }))} placeholder="e.g. Classroom use" />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                        <Button onClick={handleIssue} disabled={saving} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                            {saving ? 'Issuing...' : 'Issue Item'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Return Dialog */}
            <Dialog open={!!returnOpen} onOpenChange={() => setReturnOpen(null)}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader><DialogTitle>Return Item</DialogTitle></DialogHeader>
                    <div className="space-y-4 mt-2">
                        <div className="rounded-lg bg-slate-50 dark:bg-slate-900 p-3 text-sm space-y-1">
                            <p className="font-medium">{returnOpen?.item?.name}</p>
                            <p className="text-slate-500">Issued: {Number(returnOpen?.quantity ?? 0).toLocaleString()} {returnOpen?.item?.unit}</p>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Return Date <span className="text-red-500">*</span></Label>
                            <Input type="date" value={returnForm.return_date} onChange={e => setReturnForm(p => ({ ...p, return_date: e.target.value }))} />
                        </div>
                        <div className="space-y-1.5">
                            <Label>Returned Quantity <span className="text-red-500">*</span></Label>
                            <Input type="number" min="0.01" step="0.01" value={returnForm.returned_quantity} onChange={e => setReturnForm(p => ({ ...p, returned_quantity: e.target.value }))} />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setReturnOpen(null)}>Cancel</Button>
                        <Button onClick={handleReturn} disabled={saving} className="bg-green-600 hover:bg-green-700 text-white">
                            {saving ? 'Processing...' : 'Confirm Return'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
