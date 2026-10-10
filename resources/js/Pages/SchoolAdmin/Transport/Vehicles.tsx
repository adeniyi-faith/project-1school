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
import { Plus, Pencil, Trash2, Bus, Map } from 'lucide-react';
import type { PageProps, PaginatedResponse } from '@/Types';

interface Vehicle {
    id: number; registration_no: string; name: string | null; type: string;
    capacity: number; driver_name: string | null; driver_phone: string | null;
    helper_name: string | null; status: string; routes_count: number;
}
interface Stats { total: number; active: number; maintenance: number; }
interface Props {
    vehicles: PaginatedResponse<Vehicle>;
    filters: { status?: string };
    stats: Stats;
}

const STATUS_STYLE: Record<string, string> = {
    active:      'bg-green-100 text-green-700',
    inactive:    'bg-slate-100 text-slate-500',
    maintenance: 'bg-amber-100 text-amber-700',
};

const vDefault = { registration_no: '', name: '', type: 'bus', capacity: '40', driver_name: '', driver_phone: '', helper_name: '' };

export default function Vehicles({ vehicles, filters, stats }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen]     = useState(false);
    const [editing, setEditing] = useState<Vehicle | null>(null);
    const [form, setForm]     = useState<Record<string, string>>(vDefault);
    const [saving, setSaving] = useState(false);

    function applyFilter(key: string, value: string) {
        router.get('/school/transport/vehicles', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    function openCreate() { setEditing(null); setForm(vDefault); setOpen(true); }
    function openEdit(v: Vehicle) {
        setEditing(v);
        setForm({ registration_no: v.registration_no, name: v.name ?? '', type: v.type, capacity: String(v.capacity), driver_name: v.driver_name ?? '', driver_phone: v.driver_phone ?? '', helper_name: v.helper_name ?? '', status: v.status });
        setOpen(true);
    }

    function handleSave() {
        setSaving(true);
        const url    = editing ? `/school/transport/vehicles/${editing.id}` : '/school/transport/vehicles';
        const method = editing ? 'put' : 'post';
        router[method](url, form, {
            preserveScroll: true,
            onSuccess: () => { setOpen(false); setSaving(false); },
            onError:   () => setSaving(false),
        });
    }

    function handleDelete(id: number) {
        if (!confirm('Remove this vehicle?')) return;
        router.delete(`/school/transport/vehicles/${id}`, { preserveScroll: true });
    }

    return (
        <AppLayout title="Fleet Management">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Fleet Management</h1>
                        <p className="text-sm text-slate-500 mt-0.5">{vehicles.meta?.total ?? 0} vehicles</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/school/transport/routes">
                            <Button variant="outline" className="inline-flex items-center gap-2"><Map className="w-4 h-4" /> Routes</Button>
                        </Link>
                        <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                            <Plus className="w-4 h-4" /> Add Vehicle
                        </Button>
                    </div>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                {/* Stats */}
                <div className="grid grid-cols-3 gap-4 max-w-sm">
                    {[
                        { label: 'Total', value: stats.total, color: 'text-indigo-600' },
                        { label: 'Active', value: stats.active, color: 'text-green-600' },
                        { label: 'Maintenance', value: stats.maintenance, color: 'text-amber-600' },
                    ].map(({ label, value, color }) => (
                        <Card key={label} className="border-slate-200 dark:border-slate-800">
                            <CardContent className="p-4 text-center">
                                <p className={`text-2xl font-bold ${color}`}>{value}</p>
                                <p className="text-xs text-slate-500">{label}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Filter */}
                <div className="flex gap-3">
                    <Select value={filters.status ?? ''} onValueChange={v => applyFilter('status', v)}>
                        <SelectTrigger className="w-36"><SelectValue placeholder="All Status" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="inactive">Inactive</SelectItem>
                            <SelectItem value="maintenance">Maintenance</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Vehicle</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead className="text-center">Capacity</TableHead>
                                <TableHead>Driver</TableHead>
                                <TableHead className="text-center">Routes</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-20"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {vehicles.data.length === 0 ? (
                                <TableRow><TableCell colSpan={7} className="text-center py-16 text-slate-400">No vehicles added.</TableCell></TableRow>
                            ) : vehicles.data.map(v => (
                                <TableRow key={v.id}>
                                    <TableCell>
                                        <div className="flex items-center gap-2">
                                            <Bus className="w-4 h-4 text-indigo-400" />
                                            <div>
                                                <p className="font-medium text-sm text-slate-900 dark:text-white">{v.name ?? v.registration_no}</p>
                                                <p className="text-xs text-slate-400 font-mono">{v.registration_no}</p>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-sm text-slate-500 capitalize">{v.type}</TableCell>
                                    <TableCell className="text-center text-sm">{v.capacity}</TableCell>
                                    <TableCell>
                                        <p className="text-sm text-slate-700 dark:text-slate-300">{v.driver_name ?? '—'}</p>
                                        {v.driver_phone && <p className="text-xs text-slate-400">{v.driver_phone}</p>}
                                    </TableCell>
                                    <TableCell className="text-center text-sm text-slate-500">{v.routes_count}</TableCell>
                                    <TableCell>
                                        <Badge className={`border-0 text-xs capitalize ${STATUS_STYLE[v.status] ?? ''}`}>{v.status}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex gap-1">
                                            <Button size="sm" variant="ghost" onClick={() => openEdit(v)}><Pencil className="w-3.5 h-3.5" /></Button>
                                            <Button size="sm" variant="ghost" className="text-red-500 hover:text-red-700" onClick={() => handleDelete(v.id)}><Trash2 className="w-3.5 h-3.5" /></Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>{editing ? 'Edit' : 'Add'} Vehicle</DialogTitle></DialogHeader>
                    <div className="space-y-4 mt-2">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Registration No. <span className="text-red-500">*</span></Label>
                                <Input value={form.registration_no} onChange={e => setForm(p => ({ ...p, registration_no: e.target.value }))} placeholder="DHAKA-METRO-XX-XXXX" />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Vehicle Name</Label>
                                <Input value={form.name} onChange={e => setForm(p => ({ ...p, name: e.target.value }))} placeholder="e.g. Bus 01" />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Type <span className="text-red-500">*</span></Label>
                                <Select value={form.type} onValueChange={v => setForm(p => ({ ...p, type: v }))}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        {['bus', 'minibus', 'van', 'car', 'other'].map(t => (
                                            <SelectItem key={t} value={t} className="capitalize">{t}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label>Capacity <span className="text-red-500">*</span></Label>
                                <Input type="number" min="1" value={form.capacity} onChange={e => setForm(p => ({ ...p, capacity: e.target.value }))} />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Driver Name</Label>
                                <Input value={form.driver_name} onChange={e => setForm(p => ({ ...p, driver_name: e.target.value }))} />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Driver Phone</Label>
                                <Input value={form.driver_phone} onChange={e => setForm(p => ({ ...p, driver_phone: e.target.value }))} />
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Helper Name</Label>
                            <Input value={form.helper_name} onChange={e => setForm(p => ({ ...p, helper_name: e.target.value }))} />
                        </div>
                        {editing && (
                            <div className="space-y-1.5">
                                <Label>Status</Label>
                                <Select value={form.status} onValueChange={v => setForm(p => ({ ...p, status: v }))}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="inactive">Inactive</SelectItem>
                                        <SelectItem value="maintenance">Maintenance</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
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
