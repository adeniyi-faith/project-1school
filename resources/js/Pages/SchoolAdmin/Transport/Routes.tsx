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
import { Plus, Pencil, Trash2, Users, Bus, MapPin, X } from 'lucide-react';
import type { PageProps, PaginatedResponse } from '@/Types';

interface VehicleOption { id: number; name: string | null; registration_no: string; capacity: number; }
interface Stop { name: string; pickup_time: string; }
interface Route {
    id: number; name: string; start_point: string | null; end_point: string | null;
    monthly_fee: string; is_active: boolean; students_count: number; stops: Stop[] | null;
    vehicle?: { id: number; name: string | null; registration_no: string };
}
interface Props {
    routes: PaginatedResponse<Route>;
    vehicles: VehicleOption[];
    filters: { vehicle_id?: string };
}

const rDefault = { name: '', vehicle_id: '', start_point: '', end_point: '', monthly_fee: '0' };

export default function TransportRoutes({ routes, vehicles, filters }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen]     = useState(false);
    const [editing, setEditing] = useState<Route | null>(null);
    const [form, setForm]     = useState<Record<string, string>>(rDefault);
    const [stops, setStops]   = useState<Stop[]>([]);
    const [saving, setSaving] = useState(false);

    function openCreate() { setEditing(null); setForm(rDefault); setStops([]); setOpen(true); }
    function openEdit(r: Route) {
        setEditing(r);
        setForm({ name: r.name, vehicle_id: r.vehicle?.id ? String(r.vehicle.id) : '', start_point: r.start_point ?? '', end_point: r.end_point ?? '', monthly_fee: r.monthly_fee, is_active: String(r.is_active) });
        setStops(r.stops ?? []);
        setOpen(true);
    }

    function addStop() { setStops(p => [...p, { name: '', pickup_time: '' }]); }
    function updateStop(i: number, field: keyof Stop, value: string) {
        setStops(p => p.map((s, idx) => idx === i ? { ...s, [field]: value } : s));
    }
    function removeStop(i: number) { setStops(p => p.filter((_, idx) => idx !== i)); }

    function handleSave() {
        setSaving(true);
        const url    = editing ? `/school/transport/routes/${editing.id}` : '/school/transport/routes';
        const method = editing ? 'put' : 'post';
        router[method](url, { ...form, stops }, {
            preserveScroll: true,
            onSuccess: () => { setOpen(false); setSaving(false); },
            onError:   () => setSaving(false),
        });
    }

    function handleDelete(id: number) {
        if (!confirm('Delete this route?')) return;
        router.delete(`/school/transport/routes/${id}`, { preserveScroll: true });
    }

    return (
        <AppLayout title="Transport Routes">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Transport Routes</h1>
                        <p className="text-sm text-slate-500 mt-0.5">{routes.meta?.total ?? 0} routes</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/school/transport/vehicles">
                            <Button variant="outline" className="inline-flex items-center gap-2"><Bus className="w-4 h-4" /> Fleet</Button>
                        </Link>
                        <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                            <Plus className="w-4 h-4" /> Add Route
                        </Button>
                    </div>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Route</TableHead>
                                <TableHead>Vehicle</TableHead>
                                <TableHead>Stops</TableHead>
                                <TableHead className="text-right">Monthly Fee</TableHead>
                                <TableHead className="text-center">Students</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-28"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {routes.data.length === 0 ? (
                                <TableRow><TableCell colSpan={7} className="text-center py-16 text-slate-400">No routes defined.</TableCell></TableRow>
                            ) : routes.data.map(r => (
                                <TableRow key={r.id}>
                                    <TableCell>
                                        <p className="font-medium text-sm text-slate-900 dark:text-white">{r.name}</p>
                                        {(r.start_point || r.end_point) && (
                                            <p className="text-xs text-slate-400">{r.start_point} → {r.end_point}</p>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-sm text-slate-500">
                                        {r.vehicle ? (r.vehicle.name ?? r.vehicle.registration_no) : '—'}
                                    </TableCell>
                                    <TableCell className="text-sm text-slate-500">{r.stops?.length ?? 0} stops</TableCell>
                                    <TableCell className="text-right text-sm font-medium">৳{Number(r.monthly_fee).toLocaleString()}</TableCell>
                                    <TableCell className="text-center">
                                        <Badge className="bg-indigo-100 text-indigo-700 border-0 text-xs">{r.students_count}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge className={`border-0 text-xs ${r.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'}`}>
                                            {r.is_active ? 'Active' : 'Inactive'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex gap-1">
                                            <Link href={`/school/transport/routes/${r.id}/assignments`}>
                                                <Button size="sm" variant="ghost" title="Manage Students"><Users className="w-3.5 h-3.5" /></Button>
                                            </Link>
                                            <Button size="sm" variant="ghost" onClick={() => openEdit(r)}><Pencil className="w-3.5 h-3.5" /></Button>
                                            <Button size="sm" variant="ghost" className="text-red-500 hover:text-red-700" onClick={() => handleDelete(r.id)}><Trash2 className="w-3.5 h-3.5" /></Button>
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
                    <DialogHeader><DialogTitle>{editing ? 'Edit' : 'Add'} Route</DialogTitle></DialogHeader>
                    <div className="space-y-4 mt-2 max-h-[70vh] overflow-y-auto pr-1">
                        <div className="space-y-1.5">
                            <Label>Route Name <span className="text-red-500">*</span></Label>
                            <Input value={form.name} onChange={e => setForm(p => ({ ...p, name: e.target.value }))} placeholder="e.g. Mirpur Route" />
                        </div>
                        <div className="space-y-1.5">
                            <Label>Assign Vehicle</Label>
                            <Select value={form.vehicle_id} onValueChange={v => setForm(p => ({ ...p, vehicle_id: v }))}>
                                <SelectTrigger><SelectValue placeholder="Select vehicle" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">None</SelectItem>
                                    {vehicles.map(v => (
                                        <SelectItem key={v.id} value={String(v.id)}>
                                            {v.name ?? v.registration_no} (cap. {v.capacity})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Start Point</Label>
                                <Input value={form.start_point} onChange={e => setForm(p => ({ ...p, start_point: e.target.value }))} />
                            </div>
                            <div className="space-y-1.5">
                                <Label>End Point</Label>
                                <Input value={form.end_point} onChange={e => setForm(p => ({ ...p, end_point: e.target.value }))} />
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Monthly Fee (৳)</Label>
                            <Input type="number" min="0" value={form.monthly_fee} onChange={e => setForm(p => ({ ...p, monthly_fee: e.target.value }))} />
                        </div>

                        {/* Stops */}
                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <Label>Stops</Label>
                                <Button type="button" size="sm" variant="outline" onClick={addStop} className="inline-flex items-center gap-1 h-7 text-xs">
                                    <Plus className="w-3 h-3" /> Add Stop
                                </Button>
                            </div>
                            {stops.map((stop, i) => (
                                <div key={i} className="flex gap-2 items-center">
                                    <MapPin className="w-4 h-4 text-slate-400 flex-shrink-0" />
                                    <Input className="flex-1" placeholder="Stop name" value={stop.name} onChange={e => updateStop(i, 'name', e.target.value)} />
                                    <Input className="w-24" type="time" value={stop.pickup_time} onChange={e => updateStop(i, 'pickup_time', e.target.value)} />
                                    <Button size="sm" variant="ghost" className="text-red-500 flex-shrink-0" onClick={() => removeStop(i)}><X className="w-3.5 h-3.5" /></Button>
                                </div>
                            ))}
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                        <Button onClick={handleSave} disabled={saving} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                            {saving ? 'Saving...' : 'Save Route'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
