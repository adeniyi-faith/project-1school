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
import { Plus, Pencil, Trash2, BedDouble, Users, DoorOpen } from 'lucide-react';
import type { PageProps } from '@/Types';

interface StaffOption { id: number; first_name: string; last_name: string | null; emp_id: string; }
interface Hostel {
    id: number; name: string; type: string; address: string | null; status: string;
    total_rooms: number; total_capacity: number; allocations_count: number; rooms_count: number;
    warden?: { first_name: string; last_name: string | null } | null;
}
interface Stats { total_hostels: number; total_capacity: number; occupied: number; }
interface Props { hostels: Hostel[]; staffList: StaffOption[]; stats: Stats; }

const TYPE_STYLE: Record<string, string> = {
    boys:  'bg-blue-100 text-blue-700',
    girls: 'bg-pink-100 text-pink-700',
    mixed: 'bg-purple-100 text-purple-700',
};

const hDefault = { name: '', type: 'boys', warden_id: '', address: '', status: 'active' };

export default function HostelIndex({ hostels, staffList, stats }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen]     = useState(false);
    const [editing, setEditing] = useState<Hostel | null>(null);
    const [form, setForm]     = useState<Record<string, string>>(hDefault);
    const [saving, setSaving] = useState(false);

    function openCreate() { setEditing(null); setForm(hDefault); setOpen(true); }
    function openEdit(h: Hostel) {
        setEditing(h);
        setForm({ name: h.name, type: h.type, warden_id: '', address: h.address ?? '', status: h.status });
        setOpen(true);
    }

    function handleSave() {
        setSaving(true);
        const url    = editing ? `/school/hostel/${editing.id}` : '/school/hostel';
        const method = editing ? 'put' : 'post';
        router[method](url, form, {
            preserveScroll: true,
            onSuccess: () => { setOpen(false); setSaving(false); },
            onError:   () => setSaving(false),
        });
    }

    function handleDelete(id: number) {
        if (!confirm('Delete this hostel?')) return;
        router.delete(`/school/hostel/${id}`, { preserveScroll: true });
    }

    const vacant = stats.total_capacity - stats.occupied;

    return (
        <AppLayout title="Hostel Management">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Hostel Management</h1>
                        <p className="text-sm text-slate-500 mt-0.5">{hostels.length} hostels</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/school/hostel/allocations">
                            <Button variant="outline" className="inline-flex items-center gap-2"><Users className="w-4 h-4" /> Allocations</Button>
                        </Link>
                        <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                            <Plus className="w-4 h-4" /> Add Hostel
                        </Button>
                    </div>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                {/* Stats */}
                <div className="grid grid-cols-3 gap-4 max-w-md">
                    {[
                        { label: 'Total Capacity', value: stats.total_capacity, color: 'text-indigo-600', icon: BedDouble },
                        { label: 'Occupied',       value: stats.occupied,        color: 'text-red-500',    icon: Users },
                        { label: 'Vacant',         value: vacant,                color: 'text-green-600',  icon: DoorOpen },
                    ].map(({ label, value, color, icon: Icon }) => (
                        <Card key={label} className="border-slate-200 dark:border-slate-800">
                            <CardContent className="p-4 flex items-center gap-3">
                                <Icon className={`w-5 h-5 ${color}`} />
                                <div>
                                    <p className={`text-2xl font-bold ${color}`}>{value}</p>
                                    <p className="text-xs text-slate-500">{label}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Hostel Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {hostels.length === 0 ? (
                        <div className="col-span-3 rounded-xl border border-slate-200 dark:border-slate-800 py-20 text-center text-slate-400">
                            No hostels created yet.
                        </div>
                    ) : hostels.map(h => (
                        <div key={h.id} className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 p-5 space-y-3">
                            <div className="flex items-start justify-between">
                                <div>
                                    <h3 className="font-semibold text-slate-900 dark:text-white">{h.name}</h3>
                                    <div className="flex gap-2 mt-1">
                                        <Badge className={`border-0 text-xs capitalize ${TYPE_STYLE[h.type] ?? ''}`}>{h.type}</Badge>
                                        <Badge className={`border-0 text-xs ${h.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'}`}>{h.status}</Badge>
                                    </div>
                                </div>
                                <div className="flex gap-1">
                                    <Button size="sm" variant="ghost" onClick={() => openEdit(h)}><Pencil className="w-3.5 h-3.5" /></Button>
                                    <Button size="sm" variant="ghost" className="text-red-500" onClick={() => handleDelete(h.id)}><Trash2 className="w-3.5 h-3.5" /></Button>
                                </div>
                            </div>
                            <div className="grid grid-cols-3 gap-2 text-center">
                                <div className="rounded-lg bg-slate-50 dark:bg-slate-900 py-2">
                                    <p className="text-lg font-bold text-slate-900 dark:text-white">{h.rooms_count}</p>
                                    <p className="text-xs text-slate-400">Rooms</p>
                                </div>
                                <div className="rounded-lg bg-slate-50 dark:bg-slate-900 py-2">
                                    <p className="text-lg font-bold text-slate-900 dark:text-white">{h.total_capacity}</p>
                                    <p className="text-xs text-slate-400">Capacity</p>
                                </div>
                                <div className="rounded-lg bg-slate-50 dark:bg-slate-900 py-2">
                                    <p className="text-lg font-bold text-indigo-600">{h.allocations_count}</p>
                                    <p className="text-xs text-slate-400">Occupied</p>
                                </div>
                            </div>
                            {h.warden && (
                                <p className="text-xs text-slate-500">Warden: {h.warden.first_name} {h.warden.last_name}</p>
                            )}
                            <Link href={`/school/hostel/${h.id}/rooms`}>
                                <Button variant="outline" size="sm" className="w-full mt-1 text-xs">Manage Rooms</Button>
                            </Link>
                        </div>
                    ))}
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>{editing ? 'Edit' : 'Add'} Hostel</DialogTitle></DialogHeader>
                    <div className="space-y-4 mt-2">
                        <div className="space-y-1.5">
                            <Label>Hostel Name <span className="text-red-500">*</span></Label>
                            <Input value={form.name} onChange={e => setForm(p => ({ ...p, name: e.target.value }))} placeholder="e.g. Boys Hostel Block A" />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Type <span className="text-red-500">*</span></Label>
                                <Select value={form.type} onValueChange={v => setForm(p => ({ ...p, type: v }))}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="boys">Boys</SelectItem>
                                        <SelectItem value="girls">Girls</SelectItem>
                                        <SelectItem value="mixed">Mixed</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label>Status</Label>
                                <Select value={form.status} onValueChange={v => setForm(p => ({ ...p, status: v }))}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="inactive">Inactive</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Warden (Staff)</Label>
                            <Select value={form.warden_id} onValueChange={v => setForm(p => ({ ...p, warden_id: v }))}>
                                <SelectTrigger><SelectValue placeholder="Assign warden" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">None</SelectItem>
                                    {staffList.map(s => (
                                        <SelectItem key={s.id} value={String(s.id)}>{s.first_name} {s.last_name} ({s.emp_id})</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Address</Label>
                            <Input value={form.address} onChange={e => setForm(p => ({ ...p, address: e.target.value }))} />
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
