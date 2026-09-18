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
import { ArrowLeft, Plus, Pencil, Trash2 } from 'lucide-react';
import type { PageProps } from '@/Types';

interface Hostel { id: number; name: string; type: string; total_rooms: number; total_capacity: number; }
interface Room {
    id: number; room_no: string; floor: string | null; type: string;
    capacity: number; occupied: number; ac: boolean; monthly_fee: string; status: string;
    allocations_count: number;
}
interface Props { hostel: Hostel; rooms: Room[]; }

const STATUS_STYLE: Record<string, string> = {
    available:   'bg-green-100 text-green-700',
    full:        'bg-red-100 text-red-700',
    maintenance: 'bg-amber-100 text-amber-700',
};

const rDefault = { room_no: '', floor: '', type: 'double', capacity: '2', ac: 'false', monthly_fee: '0' };

export default function HostelRooms({ hostel, rooms }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen]     = useState(false);
    const [editing, setEditing] = useState<Room | null>(null);
    const [form, setForm]     = useState<Record<string, string>>(rDefault);
    const [saving, setSaving] = useState(false);

    function openCreate() { setEditing(null); setForm(rDefault); setOpen(true); }
    function openEdit(r: Room) {
        setEditing(r);
        setForm({ room_no: r.room_no, floor: r.floor ?? '', type: r.type, capacity: String(r.capacity), ac: String(r.ac), monthly_fee: r.monthly_fee, status: r.status });
        setOpen(true);
    }

    function handleSave() {
        setSaving(true);
        const url    = editing ? `/school/hostel/${hostel.id}/rooms/${editing.id}` : `/school/hostel/${hostel.id}/rooms`;
        const method = editing ? 'put' : 'post';
        router[method](url, form, {
            preserveScroll: true,
            onSuccess: () => { setOpen(false); setSaving(false); },
            onError:   () => setSaving(false),
        });
    }

    function handleDelete(roomId: number) {
        if (!confirm('Delete this room?')) return;
        router.delete(`/school/hostel/${hostel.id}/rooms/${roomId}`, { preserveScroll: true });
    }

    return (
        <AppLayout title={`Rooms — ${hostel.name}`}>
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link href="/school/hostel" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Hostels
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <div>
                            <h1 className="text-xl font-bold text-slate-900 dark:text-white">{hostel.name}</h1>
                            <p className="text-sm text-slate-500">{rooms.length} rooms · {hostel.total_capacity} total beds</p>
                        </div>
                    </div>
                    <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                        <Plus className="w-4 h-4" /> Add Room
                    </Button>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Room No.</TableHead>
                                <TableHead>Floor</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead className="text-center">Capacity</TableHead>
                                <TableHead className="text-center">Occupied</TableHead>
                                <TableHead>AC</TableHead>
                                <TableHead className="text-right">Fee/month</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-20"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rooms.length === 0 ? (
                                <TableRow><TableCell colSpan={9} className="text-center py-16 text-slate-400">No rooms added yet.</TableCell></TableRow>
                            ) : rooms.map(r => (
                                <TableRow key={r.id}>
                                    <TableCell className="font-medium text-sm text-slate-900 dark:text-white">{r.room_no}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{r.floor ?? '—'}</TableCell>
                                    <TableCell className="text-sm text-slate-500 capitalize">{r.type}</TableCell>
                                    <TableCell className="text-center text-sm">{r.capacity}</TableCell>
                                    <TableCell className="text-center text-sm font-medium text-indigo-600">{r.occupied}</TableCell>
                                    <TableCell className="text-sm">{r.ac ? '✓' : '—'}</TableCell>
                                    <TableCell className="text-right text-sm">৳{Number(r.monthly_fee).toLocaleString()}</TableCell>
                                    <TableCell>
                                        <Badge className={`border-0 text-xs capitalize ${STATUS_STYLE[r.status] ?? ''}`}>{r.status}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex gap-1">
                                            <Button size="sm" variant="ghost" onClick={() => openEdit(r)}><Pencil className="w-3.5 h-3.5" /></Button>
                                            <Button size="sm" variant="ghost" className="text-red-500" onClick={() => handleDelete(r.id)}><Trash2 className="w-3.5 h-3.5" /></Button>
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
                    <DialogHeader><DialogTitle>{editing ? 'Edit' : 'Add'} Room</DialogTitle></DialogHeader>
                    <div className="space-y-4 mt-2">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Room No. <span className="text-red-500">*</span></Label>
                                <Input value={form.room_no} onChange={e => setForm(p => ({ ...p, room_no: e.target.value }))} placeholder="e.g. 101" />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Floor</Label>
                                <Input value={form.floor} onChange={e => setForm(p => ({ ...p, floor: e.target.value }))} placeholder="e.g. Ground, 1st" />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Room Type <span className="text-red-500">*</span></Label>
                                <Select value={form.type} onValueChange={v => setForm(p => ({ ...p, type: v }))}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="single">Single</SelectItem>
                                        <SelectItem value="double">Double</SelectItem>
                                        <SelectItem value="dormitory">Dormitory</SelectItem>
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
                                <Label>Monthly Fee (৳)</Label>
                                <Input type="number" min="0" value={form.monthly_fee} onChange={e => setForm(p => ({ ...p, monthly_fee: e.target.value }))} />
                            </div>
                            <div className="space-y-1.5">
                                <Label>AC</Label>
                                <Select value={form.ac} onValueChange={v => setForm(p => ({ ...p, ac: v }))}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="false">Non-AC</SelectItem>
                                        <SelectItem value="true">AC</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        {editing && (
                            <div className="space-y-1.5">
                                <Label>Status</Label>
                                <Select value={form.status} onValueChange={v => setForm(p => ({ ...p, status: v }))}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="available">Available</SelectItem>
                                        <SelectItem value="full">Full</SelectItem>
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
