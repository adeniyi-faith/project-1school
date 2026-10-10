import AppLayout from '@/Layouts/AppLayout';
import { useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Plus, Trash2, LogOut, Users } from 'lucide-react';

interface Visitor {
    id: number; name: string; phone: string; purpose: string;
    person_to_meet: string; time_in: string; time_out: string | null; remarks: string | null;
}
interface PaginatedVisitors { data: Visitor[]; current_page: number; last_page: number; total: number; }
interface Props { visitors: PaginatedVisitors; filters: { search?: string; date?: string }; }

function formatTime(dt: string) {
    return new Date(dt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
function formatDate(dt: string) {
    return new Date(dt).toLocaleDateString();
}

export default function Visitors({ visitors, filters }: Props) {
    const [showModal, setShowModal] = useState(false);
    const [delItem,   setDelItem]   = useState<Visitor | null>(null);

    const [search, setSearch] = useState(filters.search ?? '');
    const [date,   setDate]   = useState(filters.date   ?? '');

    const form = useForm({
        name: '', phone: '', purpose: '', person_to_meet: '', remarks: '',
    });

    function applyFilters() {
        router.get('/school/admissions/visitors', { search, date }, { preserveState: true });
    }
    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post('/school/admissions/visitors', { onSuccess: () => { setShowModal(false); form.reset(); } });
    }
    function checkout(id: number) {
        router.patch(`/school/admissions/visitors/${id}/checkout`);
    }
    function confirmDelete() {
        if (!delItem) return;
        router.delete(`/school/admissions/visitors/${delItem.id}`, { onSuccess: () => setDelItem(null) });
    }

    return (
        <AppLayout title="Visitor Log">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Visitor Log</h1>
                        <p className="text-sm text-slate-500 mt-0.5">Track visitor check-in and check-out</p>
                    </div>
                    <Button onClick={() => { form.reset(); form.clearErrors(); setShowModal(true); }} className="gap-2">
                        <Plus className="w-4 h-4" /> Check In Visitor
                    </Button>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="pt-4">
                        <div className="flex flex-wrap gap-3 items-end">
                            <div className="flex-1 min-w-[200px]">
                                <Label>Search</Label>
                                <Input value={search} onChange={e => setSearch(e.target.value)}
                                    placeholder="Name, phone, person to meet…" onKeyDown={e => e.key === 'Enter' && applyFilters()} />
                            </div>
                            <div className="w-44">
                                <Label>Date</Label>
                                <Input type="date" value={date} onChange={e => setDate(e.target.value)} />
                            </div>
                            <Button onClick={applyFilters}>Filter</Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Users className="w-4 h-4 text-indigo-500" /> Visitors ({visitors.total})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-200 dark:border-slate-700 text-xs uppercase text-slate-500">
                                        <th className="text-left py-3 px-4 font-medium">Visitor</th>
                                        <th className="text-left py-3 px-4 font-medium">Purpose</th>
                                        <th className="text-left py-3 px-4 font-medium">Meeting</th>
                                        <th className="text-left py-3 px-4 font-medium">In</th>
                                        <th className="text-left py-3 px-4 font-medium">Out</th>
                                        <th className="text-left py-3 px-4 font-medium">Status</th>
                                        <th className="text-right py-3 px-4 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {visitors.data.length === 0 && (
                                        <tr><td colSpan={7} className="text-center py-10 text-slate-400">No visitors found.</td></tr>
                                    )}
                                    {visitors.data.map(v => (
                                        <tr key={v.id} className="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                            <td className="py-3 px-4">
                                                <div className="font-medium text-slate-900 dark:text-white">{v.name}</div>
                                                <div className="text-xs text-slate-400">{v.phone}</div>
                                            </td>
                                            <td className="py-3 px-4 text-slate-600 dark:text-slate-400">{v.purpose}</td>
                                            <td className="py-3 px-4 text-slate-600 dark:text-slate-400">{v.person_to_meet}</td>
                                            <td className="py-3 px-4 text-slate-500 text-xs">
                                                <div>{formatDate(v.time_in)}</div>
                                                <div>{formatTime(v.time_in)}</div>
                                            </td>
                                            <td className="py-3 px-4 text-slate-500 text-xs">
                                                {v.time_out ? (
                                                    <>
                                                        <div>{formatDate(v.time_out)}</div>
                                                        <div>{formatTime(v.time_out)}</div>
                                                    </>
                                                ) : '—'}
                                            </td>
                                            <td className="py-3 px-4">
                                                <Badge variant={v.time_out ? 'secondary' : 'default'}>
                                                    {v.time_out ? 'Checked Out' : 'Inside'}
                                                </Badge>
                                            </td>
                                            <td className="py-3 px-4">
                                                <div className="flex items-center justify-end gap-1">
                                                    {!v.time_out && (
                                                        <button onClick={() => checkout(v.id)}
                                                            className="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-green-600" title="Check out">
                                                            <LogOut className="w-3.5 h-3.5" />
                                                        </button>
                                                    )}
                                                    <button onClick={() => setDelItem(v)}
                                                        className="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-red-600">
                                                        <Trash2 className="w-3.5 h-3.5" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {visitors.last_page > 1 && (
                            <div className="mt-4 flex justify-center gap-2">
                                {Array.from({ length: visitors.last_page }, (_, idx) => idx + 1).map(page => (
                                    <button key={page} onClick={() => router.get('/school/admissions/visitors', { ...filters, page }, { preserveState: true })}
                                        className={`px-3 py-1 rounded text-sm ${page === visitors.current_page ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300'}`}>
                                        {page}
                                    </button>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Check-in Modal */}
            <Dialog open={showModal} onOpenChange={setShowModal}>
                <DialogContent className="max-w-md">
                    <DialogHeader><DialogTitle>Check In Visitor</DialogTitle></DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Visitor Name *</Label>
                                <Input value={form.data.name} onChange={e => form.setData('name', e.target.value)} />
                                {form.errors.name && <p className="text-xs text-red-500 mt-1">{form.errors.name}</p>}
                            </div>
                            <div>
                                <Label>Phone *</Label>
                                <Input value={form.data.phone} onChange={e => form.setData('phone', e.target.value)} />
                                {form.errors.phone && <p className="text-xs text-red-500 mt-1">{form.errors.phone}</p>}
                            </div>
                        </div>
                        <div>
                            <Label>Purpose of Visit *</Label>
                            <Input value={form.data.purpose} onChange={e => form.setData('purpose', e.target.value)} placeholder="e.g. Meeting with teacher" />
                            {form.errors.purpose && <p className="text-xs text-red-500 mt-1">{form.errors.purpose}</p>}
                        </div>
                        <div>
                            <Label>Person to Meet *</Label>
                            <Input value={form.data.person_to_meet} onChange={e => form.setData('person_to_meet', e.target.value)} placeholder="e.g. Mr. Ahmed" />
                            {form.errors.person_to_meet && <p className="text-xs text-red-500 mt-1">{form.errors.person_to_meet}</p>}
                        </div>
                        <div>
                            <Label>Remarks</Label>
                            <Input value={form.data.remarks} onChange={e => form.setData('remarks', e.target.value)} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setShowModal(false)}>Cancel</Button>
                            <Button type="submit" disabled={form.processing}>Check In</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Confirm */}
            <Dialog open={!!delItem} onOpenChange={open => !open && setDelItem(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader><DialogTitle>Delete Record</DialogTitle></DialogHeader>
                    <p className="text-sm text-slate-600 dark:text-slate-400">
                        Delete visitor record for <strong>{delItem?.name}</strong>? This cannot be undone.
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDelItem(null)}>Cancel</Button>
                        <Button className="bg-red-600 hover:bg-red-700 text-white" onClick={confirmDelete}>Delete</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
