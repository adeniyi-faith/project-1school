import { useState } from 'react';
import { router, usePage, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ArrowLeft, Plus, Wrench } from 'lucide-react';
import type { PageProps } from '@/Types';

interface MaintenanceLog {
    id: number; date: string; description: string; cost: string;
    vendor: string | null; next_maintenance_date: string | null;
}
interface Asset {
    id: number; name: string; asset_code: string; category: string | null;
    purchase_date: string | null; purchase_price: string; current_value: string;
    depreciation_method: string; depreciation_rate: string;
    location: string | null; assigned_to: string | null;
    status: string; description: string | null;
    maintenance_logs: MaintenanceLog[];
}

const STATUS_STYLE: Record<string, string> = {
    active:      'bg-green-100 text-green-700',
    maintenance: 'bg-amber-100 text-amber-700',
    disposed:    'bg-slate-100 text-slate-500',
};

const logDefault = { date: new Date().toISOString().split('T')[0], description: '', cost: '0', vendor: '', next_maintenance_date: '' };

export default function AssetDetail({ asset }: { asset: Asset }) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen]   = useState(false);
    const [form, setForm]   = useState(logDefault);
    const [saving, setSaving] = useState(false);

    function handleLog() {
        setSaving(true);
        router.post(`/school/inventory/assets/${asset.id}/maintenance`, form, {
            preserveScroll: true,
            onSuccess: () => { setOpen(false); setForm(logDefault); setSaving(false); },
            onError:   () => setSaving(false),
        });
    }

    return (
        <AppLayout title={`Asset — ${asset.name}`}>
            <div className="max-w-3xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link href="/school/inventory/assets" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Assets
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <h1 className="text-xl font-bold text-slate-900 dark:text-white">{asset.name}</h1>
                        <Badge className={`border-0 text-xs capitalize ${STATUS_STYLE[asset.status] ?? ''}`}>{asset.status}</Badge>
                    </div>
                    <Button onClick={() => setOpen(true)} className="bg-amber-500 hover:bg-amber-600 text-white inline-flex items-center gap-2">
                        <Wrench className="w-4 h-4" /> Log Maintenance
                    </Button>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                {/* Asset Details */}
                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 p-6">
                    <div className="grid grid-cols-2 sm:grid-cols-3 gap-6">
                        {[
                            { label: 'Asset Code',    value: asset.asset_code },
                            { label: 'Category',      value: asset.category ?? '—' },
                            { label: 'Purchase Date', value: asset.purchase_date ? new Date(asset.purchase_date).toLocaleDateString() : '—' },
                            { label: 'Purchase Price',value: `৳${Number(asset.purchase_price).toLocaleString()}` },
                            { label: 'Current Value', value: `৳${Number(asset.current_value).toLocaleString()}` },
                            { label: 'Depreciation',  value: `${asset.depreciation_rate}% / yr (${asset.depreciation_method.replace('_', ' ')})` },
                            { label: 'Location',      value: asset.location ?? '—' },
                            { label: 'Assigned To',   value: asset.assigned_to ?? '—' },
                        ].map(({ label, value }) => (
                            <div key={label}>
                                <p className="text-xs text-slate-400 uppercase tracking-wide">{label}</p>
                                <p className="font-medium text-slate-900 dark:text-white mt-0.5 text-sm">{value}</p>
                            </div>
                        ))}
                    </div>
                    {asset.description && (
                        <div className="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <p className="text-xs text-slate-400 uppercase tracking-wide">Description</p>
                            <p className="text-sm text-slate-600 dark:text-slate-400 mt-0.5">{asset.description}</p>
                        </div>
                    )}
                </div>

                {/* Maintenance Logs */}
                <div>
                    <h2 className="text-base font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                        <Wrench className="w-4 h-4 text-amber-500" /> Maintenance History
                    </h2>
                    <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-hidden">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-slate-50 dark:bg-slate-900">
                                    <TableHead>Date</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead>Vendor</TableHead>
                                    <TableHead className="text-right">Cost</TableHead>
                                    <TableHead>Next Due</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {asset.maintenance_logs.length === 0 ? (
                                    <TableRow><TableCell colSpan={5} className="text-center py-12 text-slate-400">No maintenance logged yet.</TableCell></TableRow>
                                ) : asset.maintenance_logs.map(log => (
                                    <TableRow key={log.id}>
                                        <TableCell className="text-sm text-slate-500">{new Date(log.date).toLocaleDateString()}</TableCell>
                                        <TableCell className="text-sm text-slate-700 dark:text-slate-300">{log.description}</TableCell>
                                        <TableCell className="text-sm text-slate-500">{log.vendor ?? '—'}</TableCell>
                                        <TableCell className="text-right text-sm font-medium">৳{Number(log.cost).toLocaleString()}</TableCell>
                                        <TableCell className="text-sm text-slate-500">
                                            {log.next_maintenance_date ? new Date(log.next_maintenance_date).toLocaleDateString() : '—'}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Log Maintenance</DialogTitle></DialogHeader>
                    <div className="space-y-4 mt-2">
                        <div className="space-y-1.5">
                            <Label>Date <span className="text-red-500">*</span></Label>
                            <Input type="date" value={form.date} onChange={e => setForm(p => ({ ...p, date: e.target.value }))} />
                        </div>
                        <div className="space-y-1.5">
                            <Label>Description <span className="text-red-500">*</span></Label>
                            <Input value={form.description} onChange={e => setForm(p => ({ ...p, description: e.target.value }))} placeholder="What was done?" />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Cost (৳)</Label>
                                <Input type="number" min="0" value={form.cost} onChange={e => setForm(p => ({ ...p, cost: e.target.value }))} />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Vendor</Label>
                                <Input value={form.vendor} onChange={e => setForm(p => ({ ...p, vendor: e.target.value }))} />
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Next Maintenance Date</Label>
                            <Input type="date" value={form.next_maintenance_date} onChange={e => setForm(p => ({ ...p, next_maintenance_date: e.target.value }))} />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                        <Button onClick={handleLog} disabled={saving} className="bg-amber-500 hover:bg-amber-600 text-white">
                            {saving ? 'Saving...' : 'Save Log'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
