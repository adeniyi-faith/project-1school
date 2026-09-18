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
import { Plus, Pencil, Trash2, AlertTriangle, Package, Tag } from 'lucide-react';
import type { PageProps, PaginatedResponse } from '@/Types';

interface Category { id: number; name: string; }
interface InventoryItem {
    id: number; name: string; unit: string; current_stock: string;
    minimum_stock: string; description: string | null; is_active: boolean;
    category?: { id: number; name: string };
}
interface Stats { total_items: number; low_stock_count: number; }
interface Props {
    items: PaginatedResponse<InventoryItem>;
    categories: Category[];
    filters: { category_id?: string; search?: string; low_stock?: string };
    stats: Stats;
}

const itemDefault = { category_id: '', name: '', unit: 'pcs', minimum_stock: '5', description: '' };

export default function InventoryItems({ items, categories, filters, stats }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen]   = useState(false);
    const [editing, setEditing] = useState<InventoryItem | null>(null);
    const [form, setForm]   = useState(itemDefault);
    const [saving, setSaving] = useState(false);

    function applyFilter(key: string, value: string) {
        router.get('/school/inventory/items', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    function openCreate() { setEditing(null); setForm(itemDefault); setOpen(true); }
    function openEdit(item: InventoryItem) {
        setEditing(item);
        setForm({ category_id: String(item.category?.id ?? ''), name: item.name, unit: item.unit, minimum_stock: item.minimum_stock, description: item.description ?? '' });
        setOpen(true);
    }

    function handleSave() {
        setSaving(true);
        const url    = editing ? `/school/inventory/items/${editing.id}` : '/school/inventory/items';
        const method = editing ? 'put' : 'post';
        router[method](url, form, {
            preserveScroll: true,
            onSuccess: () => { setOpen(false); setSaving(false); },
            onError:   () => setSaving(false),
        });
    }

    function handleDelete(id: number) {
        if (!confirm('Delete this item?')) return;
        router.delete(`/school/inventory/items/${id}`, { preserveScroll: true });
    }

    return (
        <AppLayout title="Inventory Items">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Inventory Items</h1>
                        <p className="text-sm text-slate-500 mt-0.5">{items.meta?.total ?? 0} items</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/school/inventory/categories">
                            <Button variant="outline" className="inline-flex items-center gap-2"><Tag className="w-4 h-4" /> Categories</Button>
                        </Link>
                        <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                            <Plus className="w-4 h-4" /> Add Item
                        </Button>
                    </div>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                {/* Stats */}
                <div className="grid grid-cols-2 sm:grid-cols-3 gap-4 max-w-lg">
                    <Card className="border-slate-200 dark:border-slate-800">
                        <CardContent className="p-4 flex items-center gap-3">
                            <Package className="w-5 h-5 text-indigo-500" />
                            <div>
                                <p className="text-xl font-bold text-slate-900 dark:text-white">{stats.total_items}</p>
                                <p className="text-xs text-slate-500">Total Items</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-slate-200 dark:border-slate-800">
                        <CardContent className="p-4 flex items-center gap-3">
                            <AlertTriangle className="w-5 h-5 text-amber-500" />
                            <div>
                                <p className="text-xl font-bold text-amber-600">{stats.low_stock_count}</p>
                                <p className="text-xs text-slate-500">Low Stock</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <div className="flex gap-3 flex-wrap">
                    <Input
                        placeholder="Search items..."
                        value={filters.search ?? ''}
                        onChange={e => applyFilter('search', e.target.value)}
                        className="w-52"
                    />
                    <Select value={filters.category_id ?? ''} onValueChange={v => applyFilter('category_id', v)}>
                        <SelectTrigger className="w-44"><SelectValue placeholder="All Categories" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Categories</SelectItem>
                            {categories.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Select value={filters.low_stock ?? ''} onValueChange={v => applyFilter('low_stock', v)}>
                        <SelectTrigger className="w-36"><SelectValue placeholder="All Stock" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Stock</SelectItem>
                            <SelectItem value="yes">Low Stock Only</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Item</TableHead>
                                <TableHead>Category</TableHead>
                                <TableHead>Unit</TableHead>
                                <TableHead className="text-right">Stock</TableHead>
                                <TableHead className="text-right">Min Stock</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-20"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {items.data.length === 0 ? (
                                <TableRow><TableCell colSpan={7} className="text-center py-16 text-slate-400">No items found.</TableCell></TableRow>
                            ) : items.data.map(item => {
                                const isLow = Number(item.current_stock) <= Number(item.minimum_stock);
                                return (
                                    <TableRow key={item.id} className={isLow ? 'bg-amber-50/30 dark:bg-amber-950/10' : ''}>
                                        <TableCell>
                                            <p className="font-medium text-sm text-slate-900 dark:text-white">{item.name}</p>
                                            {item.description && <p className="text-xs text-slate-400">{item.description}</p>}
                                        </TableCell>
                                        <TableCell className="text-sm text-slate-500">{item.category?.name ?? '—'}</TableCell>
                                        <TableCell className="text-sm text-slate-500">{item.unit}</TableCell>
                                        <TableCell className={`text-right font-semibold text-sm ${isLow ? 'text-amber-600' : 'text-slate-900 dark:text-white'}`}>
                                            {Number(item.current_stock).toLocaleString()}
                                        </TableCell>
                                        <TableCell className="text-right text-sm text-slate-500">{Number(item.minimum_stock).toLocaleString()}</TableCell>
                                        <TableCell>
                                            {isLow
                                                ? <Badge className="bg-amber-100 text-amber-700 border-0 text-xs inline-flex items-center gap-1"><AlertTriangle className="w-3 h-3" />Low</Badge>
                                                : <Badge className="bg-green-100 text-green-700 border-0 text-xs">OK</Badge>
                                            }
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex gap-1">
                                                <Button size="sm" variant="ghost" onClick={() => openEdit(item)}><Pencil className="w-3.5 h-3.5" /></Button>
                                                <Button size="sm" variant="ghost" className="text-red-500 hover:text-red-700" onClick={() => handleDelete(item.id)}><Trash2 className="w-3.5 h-3.5" /></Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>{editing ? 'Edit' : 'Add'} Item</DialogTitle></DialogHeader>
                    <div className="space-y-4 mt-2">
                        <div className="space-y-1.5">
                            <Label>Category <span className="text-red-500">*</span></Label>
                            <Select value={form.category_id} onValueChange={v => setForm(p => ({ ...p, category_id: v }))}>
                                <SelectTrigger><SelectValue placeholder="Select category" /></SelectTrigger>
                                <SelectContent>
                                    {categories.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Item Name <span className="text-red-500">*</span></Label>
                            <Input value={form.name} onChange={e => setForm(p => ({ ...p, name: e.target.value }))} />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Unit <span className="text-red-500">*</span></Label>
                                <Select value={form.unit} onValueChange={v => setForm(p => ({ ...p, unit: v }))}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        {['pcs', 'kg', 'litre', 'box', 'ream', 'pack', 'set'].map(u => (
                                            <SelectItem key={u} value={u}>{u}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label>Min Stock Alert</Label>
                                <Input type="number" min="0" value={form.minimum_stock} onChange={e => setForm(p => ({ ...p, minimum_stock: e.target.value }))} />
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Description</Label>
                            <Input value={form.description} onChange={e => setForm(p => ({ ...p, description: e.target.value }))} />
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
