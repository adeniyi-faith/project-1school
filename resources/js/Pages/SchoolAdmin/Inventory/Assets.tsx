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
import { Card, CardContent } from '@/components/ui/card';
import { Plus, Pencil, Trash2, Eye, DollarSign, CheckCircle2, Wrench } from 'lucide-react';
import type { PageProps, PaginatedResponse } from '@/Types';

interface Asset {
    id: number; name: string; asset_code: string; category: string | null;
    purchase_date: string | null; purchase_price: string; current_value: string;
    depreciation_method: string; depreciation_rate: string;
    location: string | null; assigned_to: string | null; status: string;
    description: string | null; maintenance_logs_count: number;
}
interface Stats { total: number; active: number; maintenance: number; disposed: number; total_value: number; }
interface Props {
    assets: PaginatedResponse<Asset>;
    categories: string[];
    filters: { status?: string; category?: string; search?: string };
    stats: Stats;
}

const STATUS_STYLE: Record<string, string> = {
    active:      'bg-green-100 text-green-700',
    maintenance: 'bg-amber-100 text-amber-700',
    disposed:    'bg-slate-100 text-slate-500',
};

const assetDefault = {
    name: '', category: '', purchase_date: '', purchase_price: '',
    depreciation_method: 'straight_line', depreciation_rate: '10',
    location: '', assigned_to: '', description: '',
};

export default function Assets({ assets, categories, filters, stats }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen]     = useState(false);
    const [editing, setEditing] = useState<Asset | null>(null);
    const [form, setForm]     = useState<Record<string, string>>(assetDefault);
    const [saving, setSaving] = useState(false);

    function applyFilter(key: string, value: string) {
        router.get('/school/inventory/assets', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    function openCreate() { setEditing(null); setForm(assetDefault); setOpen(true); }
    function openEdit(a: Asset) {
        setEditing(a);
        setForm({
            name: a.name, category: a.category ?? '', purchase_date: a.purchase_date ?? '',
            purchase_price: a.purchase_price, current_value: a.current_value,
            depreciation_method: a.depreciation_method, depreciation_rate: a.depreciation_rate,
            location: a.location ?? '', assigned_to: a.assigned_to ?? '',
            status: a.status, description: a.description ?? '',
        });
        setOpen(true);
    }

    function handleSave() {
        setSaving(true);
        const url    = editing ? `/school/inventory/assets/${editing.id}` : '/school/inventory/assets';
        const method = editing ? 'put' : 'post';
        router[method](url, form, {
            preserveScroll: true,
            onSuccess: () => { setOpen(false); setSaving(false); },
            onError:   () => setSaving(false),
        });
    }

    function handleDelete(id: number) {
        if (!confirm('Remove this asset?')) return;
        router.delete(`/school/inventory/assets/${id}`, { preserveScroll: true });
    }

    const CATEGORIES = ['Furniture', 'Electronics', 'Vehicle', 'Equipment', 'Building', 'Other'];

    return (
        <AppLayout title="Assets">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Asset Register</h1>
                        <p className="text-sm text-slate-500 mt-0.5">{assets.meta?.total ?? 0} assets</p>
                    </div>
                    <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                        <Plus className="w-4 h-4" /> Add Asset
                    </Button>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                {/* Stats */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    {[
                        { label: 'Total Value', value: `৳${stats.total_value.toLocaleString()}`, color: 'text-indigo-600', icon: DollarSign },
                        { label: 'Active', value: stats.active, color: 'text-green-600', icon: CheckCircle2 },
                        { label: 'In Maintenance', value: stats.maintenance, color: 'text-amber-600', icon: Wrench },
                        { label: 'Disposed', value: stats.disposed, color: 'text-slate-500', icon: Trash2 },
                    ].map(({ label, value, color, icon: Icon }) => (
                        <Card key={label} className="border-slate-200 dark:border-slate-800">
                            <CardContent className="p-4 flex items-center gap-3">
                                <div className={`p-2 rounded-lg bg-slate-100 dark:bg-slate-800 ${color}`}><Icon className="w-5 h-5" /></div>
                                <div>
                                    <p className={`text-xl font-bold ${color}`}>{value}</p>
                                    <p className="text-xs text-slate-500">{label}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Filters */}
                <div className="flex gap-3 flex-wrap">
                    <Input placeholder="Search name or code..." value={filters.search ?? ''} onChange={e => applyFilter('search', e.target.value)} className="w-52" />
                    <Select value={filters.status ?? ''} onValueChange={v => applyFilter('status', v)}>
                        <SelectTrigger className="w-36"><SelectValue placeholder="All Status" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Status</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="maintenance">Maintenance</SelectItem>
                            <SelectItem value="disposed">Disposed</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select value={filters.category ?? ''} onValueChange={v => applyFilter('category', v)}>
                        <SelectTrigger className="w-40"><SelectValue placeholder="All Categories" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Categories</SelectItem>
                            {categories.map(c => <SelectItem key={c} value={c}>{c}</SelectItem>)}
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Asset</TableHead>
                                <TableHead>Category</TableHead>
                                <TableHead>Location</TableHead>
                                <TableHead className="text-right">Purchase Price</TableHead>
                                <TableHead className="text-right">Current Value</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-28"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {assets.data.length === 0 ? (
                                <TableRow><TableCell colSpan={7} className="text-center py-16 text-slate-400">No assets registered.</TableCell></TableRow>
                            ) : assets.data.map(a => (
                                <TableRow key={a.id}>
                                    <TableCell>
                                        <p className="font-medium text-sm text-slate-900 dark:text-white">{a.name}</p>
                                        <p className="text-xs text-slate-400 font-mono">{a.asset_code}</p>
                                    </TableCell>
                                    <TableCell className="text-sm text-slate-500">{a.category ?? '—'}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{a.location ?? '—'}</TableCell>
                                    <TableCell className="text-right text-sm text-slate-500">৳{Number(a.purchase_price).toLocaleString()}</TableCell>
                                    <TableCell className="text-right font-semibold text-sm text-slate-900 dark:text-white">৳{Number(a.current_value).toLocaleString()}</TableCell>
                                    <TableCell>
                                        <Badge className={`border-0 text-xs capitalize ${STATUS_STYLE[a.status] ?? ''}`}>{a.status}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex gap-1">
                                            <Link href={`/school/inventory/assets/${a.id}`}>
                                                <Button size="sm" variant="ghost"><Eye className="w-3.5 h-3.5" /></Button>
                                            </Link>
                                            <Button size="sm" variant="ghost" onClick={() => openEdit(a)}><Pencil className="w-3.5 h-3.5" /></Button>
                                            <Button size="sm" variant="ghost" className="text-red-500 hover:text-red-700" onClick={() => handleDelete(a.id)}><Trash2 className="w-3.5 h-3.5" /></Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader><DialogTitle>{editing ? 'Edit' : 'Register'} Asset</DialogTitle></DialogHeader>
                    <div className="space-y-4 mt-2 max-h-[70vh] overflow-y-auto pr-1">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5 col-span-2">
                                <Label>Asset Name <span className="text-red-500">*</span></Label>
                                <Input value={form.name} onChange={e => setForm(p => ({ ...p, name: e.target.value }))} />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Category</Label>
                                <Select value={form.category} onValueChange={v => setForm(p => ({ ...p, category: v }))}>
                                    <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent>
                                        {CATEGORIES.map(c => <SelectItem key={c} value={c}>{c}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label>Purchase Date</Label>
                                <Input type="date" value={form.purchase_date} onChange={e => setForm(p => ({ ...p, purchase_date: e.target.value }))} />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Purchase Price (৳) <span className="text-red-500">*</span></Label>
                                <Input type="number" min="0" value={form.purchase_price} onChange={e => setForm(p => ({ ...p, purchase_price: e.target.value }))} />
                            </div>
                            {editing && (
                                <div className="space-y-1.5">
                                    <Label>Current Value (৳)</Label>
                                    <Input type="number" min="0" value={form.current_value} onChange={e => setForm(p => ({ ...p, current_value: e.target.value }))} />
                                </div>
                            )}
                            <div className="space-y-1.5">
                                <Label>Depreciation Method</Label>
                                <Select value={form.depreciation_method} onValueChange={v => setForm(p => ({ ...p, depreciation_method: v }))}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="straight_line">Straight Line</SelectItem>
                                        <SelectItem value="reducing_balance">Reducing Balance</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label>Depreciation Rate (%/yr)</Label>
                                <Input type="number" min="0" max="100" value={form.depreciation_rate} onChange={e => setForm(p => ({ ...p, depreciation_rate: e.target.value }))} />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Location</Label>
                                <Input value={form.location} onChange={e => setForm(p => ({ ...p, location: e.target.value }))} placeholder="Room / Building" />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Assigned To</Label>
                                <Input value={form.assigned_to} onChange={e => setForm(p => ({ ...p, assigned_to: e.target.value }))} placeholder="Staff / Department" />
                            </div>
                            {editing && (
                                <div className="space-y-1.5">
                                    <Label>Status</Label>
                                    <Select value={form.status} onValueChange={v => setForm(p => ({ ...p, status: v }))}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">Active</SelectItem>
                                            <SelectItem value="maintenance">Maintenance</SelectItem>
                                            <SelectItem value="disposed">Disposed</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                            <div className="space-y-1.5 col-span-2">
                                <Label>Description</Label>
                                <Input value={form.description} onChange={e => setForm(p => ({ ...p, description: e.target.value }))} />
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                        <Button onClick={handleSave} disabled={saving} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                            {saving ? 'Saving...' : 'Save'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
