import { useState } from 'react';
import { router, usePage, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ArrowLeft, Plus, UserMinus } from 'lucide-react';
import type { PageProps } from '@/Types';

interface Stop { name: string; pickup_time: string; }
interface AssignedStudent {
    id: number; first_name: string; last_name: string | null; admission_no: string;
    school_class?: { name: string };
    pivot: { stop: string | null; fee_linked: boolean };
}
interface AvailableStudent {
    id: number; first_name: string; last_name: string | null; admission_no: string;
    school_class?: { name: string };
}
interface Route {
    id: number; name: string; monthly_fee: string; stops: Stop[] | null;
    vehicle?: { name: string | null; registration_no: string; capacity: number } | null;
    students: AssignedStudent[];
}
interface Props {
    route: Route;
    available: AvailableStudent[];
    filters: { search?: string };
}

export default function Assignments({ route, available, filters }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen]   = useState(false);
    const [assignForm, setAssignForm] = useState({ student_id: '', stop: '', fee_linked: false });
    const [search, setSearch] = useState(filters.search ?? '');
    const [saving, setSaving] = useState(false);

    function doSearch() {
        router.get(`/school/transport/routes/${route.id}/assignments`, { search: search || undefined }, { preserveScroll: true });
    }

    function handleAssign() {
        setSaving(true);
        router.post(`/school/transport/routes/${route.id}/assign`, assignForm, {
            preserveScroll: true,
            onSuccess: () => { setOpen(false); setAssignForm({ student_id: '', stop: '', fee_linked: false }); setSaving(false); },
            onError:   () => setSaving(false),
        });
    }

    function handleRemove(studentId: number) {
        if (!confirm('Remove this student from the route?')) return;
        router.delete(`/school/transport/routes/${route.id}/students/${studentId}`, { preserveScroll: true });
    }

    const stopOptions = route.stops ?? [];

    return (
        <AppLayout title={`Assignments — ${route.name}`}>
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link href="/school/transport/routes" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Routes
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <div>
                            <h1 className="text-xl font-bold text-slate-900 dark:text-white">{route.name}</h1>
                            <p className="text-sm text-slate-500">
                                {route.vehicle ? (route.vehicle.name ?? route.vehicle.registration_no) : 'No vehicle'} · {route.students.length} students
                            </p>
                        </div>
                    </div>
                    <Button onClick={() => setOpen(true)} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                        <Plus className="w-4 h-4" /> Assign Student
                    </Button>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                {/* Assigned Students */}
                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>#</TableHead>
                                <TableHead>Student</TableHead>
                                <TableHead>Class</TableHead>
                                <TableHead>Pickup Stop</TableHead>
                                <TableHead className="w-16"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {route.students.length === 0 ? (
                                <TableRow><TableCell colSpan={5} className="text-center py-16 text-slate-400">No students assigned.</TableCell></TableRow>
                            ) : route.students.map((s, idx) => (
                                <TableRow key={s.id}>
                                    <TableCell className="text-slate-400 text-xs">{idx + 1}</TableCell>
                                    <TableCell>
                                        <p className="font-medium text-sm text-slate-900 dark:text-white">{s.first_name} {s.last_name}</p>
                                        <p className="text-xs text-slate-400">{s.admission_no}</p>
                                    </TableCell>
                                    <TableCell className="text-sm text-slate-500">{s.school_class?.name ?? '—'}</TableCell>
                                    <TableCell className="text-sm text-slate-500">{s.pivot.stop ?? '—'}</TableCell>
                                    <TableCell>
                                        <Button size="sm" variant="ghost" className="text-red-500 hover:text-red-700" onClick={() => handleRemove(s.id)}>
                                            <UserMinus className="w-3.5 h-3.5" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            {/* Assign Dialog */}
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Assign Student to Route</DialogTitle></DialogHeader>
                    <div className="space-y-4 mt-2">
                        {/* Search available */}
                        <div className="space-y-1.5">
                            <Label>Search Student</Label>
                            <div className="flex gap-2">
                                <Input
                                    placeholder="Name or ID..."
                                    value={search}
                                    onChange={e => setSearch(e.target.value)}
                                    onKeyDown={e => e.key === 'Enter' && doSearch()}
                                    className="flex-1"
                                />
                                <Button type="button" variant="outline" onClick={doSearch}>Search</Button>
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Student <span className="text-red-500">*</span></Label>
                            <Select value={assignForm.student_id} onValueChange={v => setAssignForm(p => ({ ...p, student_id: v }))}>
                                <SelectTrigger><SelectValue placeholder="Select student" /></SelectTrigger>
                                <SelectContent>
                                    {available.map(s => (
                                        <SelectItem key={s.id} value={String(s.id)}>
                                            {s.first_name} {s.last_name} ({s.admission_no}) — {s.school_class?.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        {stopOptions.length > 0 && (
                            <div className="space-y-1.5">
                                <Label>Pickup Stop</Label>
                                <Select value={assignForm.stop} onValueChange={v => setAssignForm(p => ({ ...p, stop: v }))}>
                                    <SelectTrigger><SelectValue placeholder="Select stop" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">None</SelectItem>
                                        {stopOptions.map((s, i) => (
                                            <SelectItem key={i} value={s.name}>{s.name} {s.pickup_time ? `(${s.pickup_time})` : ''}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                        <Button onClick={handleAssign} disabled={saving} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                            {saving ? 'Assigning...' : 'Assign'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
