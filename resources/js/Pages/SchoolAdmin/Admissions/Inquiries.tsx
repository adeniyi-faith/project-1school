import AppLayout from '@/Layouts/AppLayout';
import { useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Plus, Edit, Trash2, MessageSquare, ChevronDown, ChevronUp, ClipboardList } from 'lucide-react';

interface Followup { id: number; note: string; next_date: string | null; created_at: string; staff: { name: string } | null; }
interface Inquiry {
    id: number; student_name: string; class_interested: string;
    guardian_name: string; guardian_phone: string; guardian_email: string | null;
    status: 'new' | 'follow_up' | 'admitted' | 'dropped';
    notes: string | null; next_followup_date: string | null;
    source: string; followups: Followup[];
}
interface PaginatedInquiries { data: Inquiry[]; current_page: number; last_page: number; total: number; }
interface Props { inquiries: PaginatedInquiries; filters: { status?: string; search?: string }; }

const statusColors: Record<string, string> = {
    new:       'bg-blue-100 text-blue-700',
    follow_up: 'bg-yellow-100 text-yellow-700',
    admitted:  'bg-green-100 text-green-700',
    dropped:   'bg-red-100 text-red-700',
};
const statusLabels: Record<string, string> = {
    new: 'New', follow_up: 'Follow Up', admitted: 'Admitted', dropped: 'Dropped',
};

export default function Inquiries({ inquiries, filters }: Props) {
    const [showModal,  setShowModal]  = useState(false);
    const [editItem,   setEditItem]   = useState<Inquiry | null>(null);
    const [delItem,    setDelItem]    = useState<Inquiry | null>(null);
    const [expandedId, setExpandedId] = useState<number | null>(null);
    const [followupId, setFollowupId] = useState<number | null>(null);

    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    const form = useForm({
        student_name: '', class_interested: '', guardian_name: '', guardian_phone: '',
        guardian_email: '', status: 'new' as Inquiry['status'], notes: '',
        next_followup_date: '', source: 'walk-in',
    });

    const followupForm = useForm({ note: '', next_date: '' });

    function applyFilters() {
        router.get('/school/admissions/inquiries', { search, status }, { preserveState: true });
    }

    function openCreate() { form.reset(); form.clearErrors(); setEditItem(null); setShowModal(true); }
    function openEdit(i: Inquiry) {
        form.setData({
            student_name: i.student_name, class_interested: i.class_interested,
            guardian_name: i.guardian_name, guardian_phone: i.guardian_phone,
            guardian_email: i.guardian_email ?? '', status: i.status,
            notes: i.notes ?? '', next_followup_date: i.next_followup_date ?? '',
            source: i.source,
        });
        form.clearErrors(); setEditItem(i); setShowModal(true);
    }
    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (editItem) {
            form.put(`/school/admissions/inquiries/${editItem.id}`, { onSuccess: () => setShowModal(false) });
        } else {
            form.post('/school/admissions/inquiries', { onSuccess: () => setShowModal(false) });
        }
    }
    function confirmDelete() {
        if (!delItem) return;
        router.delete(`/school/admissions/inquiries/${delItem.id}`, { onSuccess: () => setDelItem(null) });
    }
    function submitFollowup(e: React.FormEvent) {
        e.preventDefault();
        if (!followupId) return;
        followupForm.post(`/school/admissions/inquiries/${followupId}/followup`, {
            onSuccess: () => { setFollowupId(null); followupForm.reset(); },
        });
    }

    return (
        <AppLayout title="Admission Inquiries">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Admission Inquiries</h1>
                        <p className="text-sm text-slate-500 mt-0.5">Manage prospective student inquiries</p>
                    </div>
                    <Button onClick={openCreate} className="gap-2"><Plus className="w-4 h-4" /> New Inquiry</Button>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="pt-4">
                        <div className="flex flex-wrap gap-3 items-end">
                            <div className="flex-1 min-w-[200px]">
                                <Label>Search</Label>
                                <Input value={search} onChange={e => setSearch(e.target.value)}
                                    placeholder="Name, guardian, phone…" onKeyDown={e => e.key === 'Enter' && applyFilters()} />
                            </div>
                            <div className="w-44">
                                <Label>Status</Label>
                                <Select value={status || '_all'} onValueChange={v => setStatus(v === '_all' ? '' : v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="_all">All Statuses</SelectItem>
                                        <SelectItem value="new">New</SelectItem>
                                        <SelectItem value="follow_up">Follow Up</SelectItem>
                                        <SelectItem value="admitted">Admitted</SelectItem>
                                        <SelectItem value="dropped">Dropped</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button onClick={applyFilters}>Filter</Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <ClipboardList className="w-4 h-4 text-indigo-500" /> Inquiries ({inquiries.total})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {inquiries.data.length === 0 && (
                                <p className="text-center py-10 text-slate-400">No inquiries found.</p>
                            )}
                            {inquiries.data.map(i => (
                                <div key={i.id} className="border border-slate-200 dark:border-slate-700 rounded-lg">
                                    <div className="flex items-center gap-3 p-4">
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <span className="font-semibold text-slate-900 dark:text-white">{i.student_name}</span>
                                                <span className="text-xs text-slate-400">Class: {i.class_interested}</span>
                                                <span className={`text-[11px] px-2 py-0.5 rounded-full font-medium ${statusColors[i.status]}`}>
                                                    {statusLabels[i.status]}
                                                </span>
                                                <span className="text-xs text-slate-400 capitalize">{i.source}</span>
                                            </div>
                                            <div className="text-sm text-slate-500 mt-0.5">
                                                {i.guardian_name} · {i.guardian_phone}
                                                {i.guardian_email && <span className="ml-2">{i.guardian_email}</span>}
                                            </div>
                                            {i.next_followup_date && (
                                                <div className="text-xs text-amber-600 mt-0.5">Follow up: {new Date(i.next_followup_date).toLocaleDateString()}</div>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-1 shrink-0">
                                            <button onClick={() => setFollowupId(i.id)}
                                                className="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-indigo-600" title="Add followup">
                                                <MessageSquare className="w-3.5 h-3.5" />
                                            </button>
                                            <button onClick={() => openEdit(i)}
                                                className="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-indigo-600">
                                                <Edit className="w-3.5 h-3.5" />
                                            </button>
                                            <button onClick={() => setDelItem(i)}
                                                className="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-red-600">
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </button>
                                            {i.followups.length > 0 && (
                                                <button onClick={() => setExpandedId(expandedId === i.id ? null : i.id)}
                                                    className="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-700 flex items-center gap-1 text-xs">
                                                    {i.followups.length} notes
                                                    {expandedId === i.id ? <ChevronUp className="w-3 h-3" /> : <ChevronDown className="w-3 h-3" />}
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                    {expandedId === i.id && i.followups.length > 0 && (
                                        <div className="border-t border-slate-100 dark:border-slate-800 px-4 pb-3 pt-2 space-y-2">
                                            {i.followups.map(f => (
                                                <div key={f.id} className="text-sm bg-slate-50 dark:bg-slate-800/50 rounded p-2">
                                                    <div className="flex items-center justify-between text-xs text-slate-400 mb-1">
                                                        <span>{f.staff?.name ?? 'Staff'}</span>
                                                        <span>{new Date(f.created_at).toLocaleDateString()}</span>
                                                    </div>
                                                    <p className="text-slate-700 dark:text-slate-300">{f.note}</p>
                                                    {f.next_date && <p className="text-xs text-amber-600 mt-0.5">Next: {new Date(f.next_date).toLocaleDateString()}</p>}
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>

                        {/* Pagination */}
                        {inquiries.last_page > 1 && (
                            <div className="mt-4 flex justify-center gap-2">
                                {Array.from({ length: inquiries.last_page }, (_, idx) => idx + 1).map(page => (
                                    <button key={page} onClick={() => router.get('/school/admissions/inquiries', { ...filters, page }, { preserveState: true })}
                                        className={`px-3 py-1 rounded text-sm ${page === inquiries.current_page ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300'}`}>
                                        {page}
                                    </button>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Create/Edit Modal */}
            <Dialog open={showModal} onOpenChange={setShowModal}>
                <DialogContent className="max-w-lg">
                    <DialogHeader><DialogTitle>{editItem ? 'Edit Inquiry' : 'New Inquiry'}</DialogTitle></DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Student Name *</Label>
                                <Input value={form.data.student_name} onChange={e => form.setData('student_name', e.target.value)} />
                                {form.errors.student_name && <p className="text-xs text-red-500 mt-1">{form.errors.student_name}</p>}
                            </div>
                            <div>
                                <Label>Class Interested *</Label>
                                <Input value={form.data.class_interested} onChange={e => form.setData('class_interested', e.target.value)} placeholder="e.g. Grade 5" />
                                {form.errors.class_interested && <p className="text-xs text-red-500 mt-1">{form.errors.class_interested}</p>}
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Guardian Name *</Label>
                                <Input value={form.data.guardian_name} onChange={e => form.setData('guardian_name', e.target.value)} />
                                {form.errors.guardian_name && <p className="text-xs text-red-500 mt-1">{form.errors.guardian_name}</p>}
                            </div>
                            <div>
                                <Label>Guardian Phone *</Label>
                                <Input value={form.data.guardian_phone} onChange={e => form.setData('guardian_phone', e.target.value)} />
                                {form.errors.guardian_phone && <p className="text-xs text-red-500 mt-1">{form.errors.guardian_phone}</p>}
                            </div>
                        </div>
                        <div>
                            <Label>Guardian Email</Label>
                            <Input type="email" value={form.data.guardian_email} onChange={e => form.setData('guardian_email', e.target.value)} />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Status</Label>
                                <Select value={form.data.status} onValueChange={v => form.setData('status', v as Inquiry['status'])}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="new">New</SelectItem>
                                        <SelectItem value="follow_up">Follow Up</SelectItem>
                                        <SelectItem value="admitted">Admitted</SelectItem>
                                        <SelectItem value="dropped">Dropped</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>Source</Label>
                                <Select value={form.data.source} onValueChange={v => form.setData('source', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="walk-in">Walk-in</SelectItem>
                                        <SelectItem value="online">Online</SelectItem>
                                        <SelectItem value="referral">Referral</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div>
                            <Label>Next Followup Date</Label>
                            <Input type="date" value={form.data.next_followup_date} onChange={e => form.setData('next_followup_date', e.target.value)} />
                        </div>
                        <div>
                            <Label>Notes</Label>
                            <Textarea rows={3} value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setShowModal(false)}>Cancel</Button>
                            <Button type="submit" disabled={form.processing}>{editItem ? 'Save Changes' : 'Create'}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Followup Modal */}
            <Dialog open={!!followupId} onOpenChange={open => !open && setFollowupId(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader><DialogTitle>Add Followup Note</DialogTitle></DialogHeader>
                    <form onSubmit={submitFollowup} className="space-y-4">
                        <div>
                            <Label>Note *</Label>
                            <Textarea rows={3} value={followupForm.data.note} onChange={e => followupForm.setData('note', e.target.value)} placeholder="Followup details…" />
                            {followupForm.errors.note && <p className="text-xs text-red-500 mt-1">{followupForm.errors.note}</p>}
                        </div>
                        <div>
                            <Label>Next Date</Label>
                            <Input type="date" value={followupForm.data.next_date} onChange={e => followupForm.setData('next_date', e.target.value)} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setFollowupId(null)}>Cancel</Button>
                            <Button type="submit" disabled={followupForm.processing}>Save</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Confirm */}
            <Dialog open={!!delItem} onOpenChange={open => !open && setDelItem(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader><DialogTitle>Delete Inquiry</DialogTitle></DialogHeader>
                    <p className="text-sm text-slate-600 dark:text-slate-400">
                        Delete inquiry for <strong>{delItem?.student_name}</strong>? This cannot be undone.
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
