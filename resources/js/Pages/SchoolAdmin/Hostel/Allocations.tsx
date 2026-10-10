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
import { ArrowLeft, Plus, LogOut } from 'lucide-react';
import type { PageProps, PaginatedResponse } from '@/Types';

interface HostelOption { id: number; name: string; type: string; }
interface RoomOption { id: number; room_no: string; floor: string | null; type: string; capacity: number; occupied: number; monthly_fee: string; ac: boolean; }
interface Allocation {
    id: number; bed_no: string | null; joining_date: string; leaving_date: string | null; status: string;
    student?: { id: number; first_name: string; last_name: string | null; admission_no: string; school_class?: { name: string } };
    hostel?: { id: number; name: string; type: string };
    room?: { id: number; room_no: string; floor: string | null; type: string };
}
interface Props {
    allocations: PaginatedResponse<Allocation>;
    hostels: HostelOption[];
    filters: { hostel_id?: string; status?: string };
}

const aDefault = { hostel_id: '', room_id: '', student_search: '', student_id: '', bed_no: '', joining_date: new Date().toISOString().split('T')[0], fee_linked: 'true', notes: '' };

export default function HostelAllocations({ allocations, hostels, filters }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen]         = useState(false);
    const [vacateOpen, setVacateOpen] = useState<Allocation | null>(null);
    const [form, setForm]         = useState<Record<string, string>>(aDefault);
    const [rooms, setRooms]       = useState<RoomOption[]>([]);
    const [students, setStudents] = useState<{ id: number; first_name: string; last_name: string | null; admission_no: string }[]>([]);
    const [vacateDate, setVacateDate] = useState(new Date().toISOString().split('T')[0]);
    const [saving, setSaving]     = useState(false);

    function applyFilter(key: string, value: string) {
        router.get('/school/hostel/allocations', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    async function loadRooms(hostelId: string) {
        setForm(p => ({ ...p, hostel_id: hostelId, room_id: '' }));
        if (!hostelId) { setRooms([]); return; }
        const res = await fetch(`/school/hostel/${hostelId}/available-rooms`);
        const data = await res.json();
        setRooms(data);
    }

    function searchStudents(q: string) {
        setForm(p => ({ ...p, student_search: q }));
        if (q.length < 2) { setStudents([]); return; }
        fetch(`/api/v1/students/search?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(d => setStudents(d.data ?? []));
    }

    function handleAllocate() {
        setSaving(true);
        router.post('/school/hostel/allocations', form, {
            preserveScroll: true,
            onSuccess: () => { setOpen(false); setForm(aDefault); setRooms([]); setStudents([]); setSaving(false); },
            onError:   () => setSaving(false),
        });
    }

    function handleVacate() {
        if (!vacateOpen) return;
        setSaving(true);
        router.put(`/school/hostel/allocations/${vacateOpen.id}/vacate`, { leaving_date: vacateDate }, {
            preserveScroll: true,
            onSuccess: () => { setVacateOpen(null); setSaving(false); },
            onError:   () => setSaving(false),
        });
    }

    const selectedRoom = rooms.find(r => String(r.id) === form.room_id);

    return (
        <AppLayout title="Hostel Allocations">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link href="/school/hostel" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Hostels
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <div>
                            <h1 className="text-xl font-bold text-slate-900 dark:text-white">Room Allocations</h1>
                            <p className="text-sm text-slate-500">{allocations.meta?.total ?? 0} records</p>
                        </div>
                    </div>
                    <Button onClick={() => setOpen(true)} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                        <Plus className="w-4 h-4" /> Allocate Room
                    </Button>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                <div className="flex gap-3">
                    <Select value={filters.hostel_id ?? ''} onValueChange={v => applyFilter('hostel_id', v)}>
                        <SelectTrigger className="w-44"><SelectValue placeholder="All Hostels" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Hostels</SelectItem>
                            {hostels.map(h => <SelectItem key={h.id} value={String(h.id)}>{h.name}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Select value={filters.status ?? ''} onValueChange={v => applyFilter('status', v)}>
                        <SelectTrigger className="w-36"><SelectValue placeholder="All Status" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="left">Left</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Student</TableHead>
                                <TableHead>Hostel</TableHead>
                                <TableHead>Room</TableHead>
                                <TableHead>Bed</TableHead>
                                <TableHead>Joining</TableHead>
                                <TableHead>Leaving</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-16"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {allocations.data.length === 0 ? (
                                <TableRow><TableCell colSpan={8} className="text-center py-16 text-slate-400">No allocations found.</TableCell></TableRow>
                            ) : allocations.data.map(a => (
                                <TableRow key={a.id}>
                                    <TableCell>
                                        <p className="font-medium text-sm text-slate-900 dark:text-white">{a.student?.first_name} {a.student?.last_name}</p>
                                        <p className="text-xs text-slate-400">{a.student?.admission_no} · {a.student?.school_class?.name}</p>
                                    </TableCell>
                                    <TableCell className="text-sm text-slate-600 dark:text-slate-400">{a.hostel?.name}</TableCell>
                                    <TableCell className="text-sm text-slate-500">
                                        {a.room?.room_no} {a.room?.floor ? `(${a.room.floor})` : ''}
                                    </TableCell>
                                    <TableCell className="text-sm text-slate-500">{a.bed_no ?? '—'}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{new Date(a.joining_date).toLocaleDateString()}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{a.leaving_date ? new Date(a.leaving_date).toLocaleDateString() : '—'}</TableCell>
                                    <TableCell>
                                        <Badge className={`border-0 text-xs capitalize ${a.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'}`}>{a.status}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        {a.status === 'active' && (
                                            <Button size="sm" variant="ghost" className="text-red-500 hover:text-red-700 inline-flex items-center gap-1"
                                                onClick={() => { setVacateOpen(a); setVacateDate(new Date().toISOString().split('T')[0]); }}>
                                                <LogOut className="w-3.5 h-3.5" />
                                            </Button>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            {/* Allocate Dialog */}
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Allocate Room</DialogTitle></DialogHeader>
                    <div className="space-y-4 mt-2">
                        <div className="space-y-1.5">
                            <Label>Hostel <span className="text-red-500">*</span></Label>
                            <Select value={form.hostel_id} onValueChange={loadRooms}>
                                <SelectTrigger><SelectValue placeholder="Select hostel" /></SelectTrigger>
                                <SelectContent>
                                    {hostels.map(h => <SelectItem key={h.id} value={String(h.id)}>{h.name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                        {rooms.length > 0 && (
                            <div className="space-y-1.5">
                                <Label>Room <span className="text-red-500">*</span></Label>
                                <Select value={form.room_id} onValueChange={v => setForm(p => ({ ...p, room_id: v }))}>
                                    <SelectTrigger><SelectValue placeholder="Select room" /></SelectTrigger>
                                    <SelectContent>
                                        {rooms.map(r => (
                                            <SelectItem key={r.id} value={String(r.id)}>
                                                Room {r.room_no} {r.floor ? `(${r.floor})` : ''} — {r.type}{r.ac ? ' AC' : ''} · {r.capacity - r.occupied} beds left · ৳{Number(r.monthly_fee).toLocaleString()}/mo
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {selectedRoom && (
                                    <p className="text-xs text-slate-500">{selectedRoom.capacity - selectedRoom.occupied} beds available</p>
                                )}
                            </div>
                        )}
                        <div className="space-y-1.5">
                            <Label>Student ID / Name</Label>
                            <Input
                                value={form.student_search}
                                onChange={e => searchStudents(e.target.value)}
                                placeholder="Type to search..."
                            />
                        </div>
                        {students.length > 0 && (
                            <div className="space-y-1.5">
                                <Label>Select Student <span className="text-red-500">*</span></Label>
                                <Select value={form.student_id} onValueChange={v => setForm(p => ({ ...p, student_id: v }))}>
                                    <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent>
                                        {students.map(s => (
                                            <SelectItem key={s.id} value={String(s.id)}>
                                                {s.first_name} {s.last_name} ({s.admission_no})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Bed No.</Label>
                                <Input value={form.bed_no} onChange={e => setForm(p => ({ ...p, bed_no: e.target.value }))} placeholder="e.g. A1" />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Joining Date <span className="text-red-500">*</span></Label>
                                <Input type="date" value={form.joining_date} onChange={e => setForm(p => ({ ...p, joining_date: e.target.value }))} />
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                        <Button onClick={handleAllocate} disabled={saving} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                            {saving ? 'Allocating...' : 'Allocate'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Vacate Dialog */}
            <Dialog open={!!vacateOpen} onOpenChange={() => setVacateOpen(null)}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader><DialogTitle>Vacate Room</DialogTitle></DialogHeader>
                    <div className="space-y-3 mt-2">
                        <div className="rounded-lg bg-slate-50 dark:bg-slate-900 p-3 text-sm">
                            <p className="font-medium">{vacateOpen?.student?.first_name} {vacateOpen?.student?.last_name}</p>
                            <p className="text-slate-500">{vacateOpen?.hostel?.name} · Room {vacateOpen?.room?.room_no}</p>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Leaving Date <span className="text-red-500">*</span></Label>
                            <Input type="date" value={vacateDate} onChange={e => setVacateDate(e.target.value)} />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setVacateOpen(null)}>Cancel</Button>
                        <Button onClick={handleVacate} disabled={saving} className="bg-red-600 hover:bg-red-700 text-white">
                            {saving ? 'Processing...' : 'Confirm Vacate'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
