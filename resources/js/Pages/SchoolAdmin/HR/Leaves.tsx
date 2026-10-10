import { useState } from 'react';
import { useForm, router, usePage, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import { Plus, Settings, Clock, CheckCircle2, XCircle } from 'lucide-react';
import type { PageProps, PaginatedResponse } from '@/Types';

interface LeaveType { id: number; name: string; }
interface StaffItem { id: number; first_name: string; last_name: string | null; emp_id: string; }
interface LeaveRequest {
    id: number; staff_id: number; leave_type_id: number; start_date: string; end_date: string;
    days: number; reason: string | null; status: string;
    staff?: { first_name: string; last_name: string | null; emp_id: string; department?: { name: string } };
    leave_type?: { name: string; is_paid: boolean };
    approved_by_user?: { name: string };
    approval_note: string | null; actioned_at: string | null;
}
interface Stats { pending: number; approved: number; rejected: number; }

interface Props {
    requests: PaginatedResponse<LeaveRequest>;
    leaveTypes: LeaveType[];
    staffList: StaffItem[];
    filters: { status?: string; staff_id?: string };
    stats: Stats;
}

const STATUS_STYLE: Record<string, string> = {
    pending:  'bg-amber-100 text-amber-700',
    approved: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
};

const emptyForm = { staff_id: '', leave_type_id: '', start_date: '', end_date: '', reason: '' };

export default function LeaveRequests({ requests, leaveTypes, staffList, filters, stats }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen] = useState(false);
    const [approveOpen, setApproveOpen] = useState<{ req: LeaveRequest; action: 'approved' | 'rejected' } | null>(null);
    const [approvalNote, setApprovalNote] = useState('');

    const { data, setData, post, processing, errors, reset } = useForm(emptyForm);

    function applyFilter(key: string, value: string) {
        router.get('/school/hr/leaves', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/school/hr/leaves', { onSuccess: () => { setOpen(false); reset(); } });
    }

    function handleApprove() {
        if (!approveOpen) return;
        router.put(`/school/hr/leaves/${approveOpen.req.id}/approve`, {
            action: approveOpen.action,
            approval_note: approvalNote,
        }, { onSuccess: () => { setApproveOpen(null); setApprovalNote(''); } });
    }

    const statCards = [
        { label: 'Pending', value: stats.pending, color: 'text-amber-600', icon: Clock },
        { label: 'Approved', value: stats.approved, color: 'text-green-600', icon: CheckCircle2 },
        { label: 'Rejected', value: stats.rejected, color: 'text-red-600', icon: XCircle },
    ];

    return (
        <AppLayout title="Leave Management">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Leave Management</h1>
                        <p className="text-sm text-slate-500 mt-0.5">{requests.meta?.total ?? 0} total requests</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/school/hr/leave-types">
                            <Button variant="outline" className="inline-flex items-center gap-2"><Settings className="w-4 h-4" /> Leave Types</Button>
                        </Link>
                        <Button onClick={() => { reset(); setOpen(true); }} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                            <Plus className="w-4 h-4" /> Add Request
                        </Button>
                    </div>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                {/* Stats */}
                <div className="grid grid-cols-3 gap-4 max-w-md">
                    {statCards.map(({ label, value, color, icon: Icon }) => (
                        <Card key={label} className="border-slate-200 dark:border-slate-800">
                            <CardContent className="p-4 flex items-center gap-3">
                                <Icon className={`w-5 h-5 ${color}`} />
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
                    <Select value={filters.status ?? ''} onValueChange={v => applyFilter('status', v)}>
                        <SelectTrigger className="w-36"><SelectValue placeholder="All Status" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Status</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="approved">Approved</SelectItem>
                            <SelectItem value="rejected">Rejected</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Table */}
                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Staff</TableHead>
                                <TableHead>Leave Type</TableHead>
                                <TableHead>Dates</TableHead>
                                <TableHead className="text-center">Days</TableHead>
                                <TableHead>Reason</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-28"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {requests.data.length === 0 ? (
                                <TableRow><TableCell colSpan={7} className="text-center py-16 text-slate-400">No leave requests found.</TableCell></TableRow>
                            ) : requests.data.map(req => (
                                <TableRow key={req.id}>
                                    <TableCell>
                                        <p className="font-medium text-sm text-slate-900 dark:text-white">{req.staff?.first_name} {req.staff?.last_name}</p>
                                        <p className="text-xs text-slate-400">{req.staff?.emp_id} · {req.staff?.department?.name}</p>
                                    </TableCell>
                                    <TableCell>
                                        <p className="text-sm text-slate-700 dark:text-slate-300">{req.leave_type?.name}</p>
                                        <Badge className={`border-0 text-[10px] mt-0.5 ${req.leave_type?.is_paid ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'}`}>
                                            {req.leave_type?.is_paid ? 'Paid' : 'Unpaid'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-sm text-slate-600 dark:text-slate-400">
                                        {new Date(req.start_date).toLocaleDateString()} – {new Date(req.end_date).toLocaleDateString()}
                                    </TableCell>
                                    <TableCell className="text-center font-semibold text-indigo-600">{req.days}</TableCell>
                                    <TableCell className="text-sm text-slate-500 max-w-[150px] truncate">{req.reason ?? '—'}</TableCell>
                                    <TableCell>
                                        <Badge className={`border-0 text-xs capitalize ${STATUS_STYLE[req.status] ?? ''}`}>{req.status}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        {req.status === 'pending' && (
                                            <div className="flex gap-1">
                                                <Button size="sm" className="text-xs bg-green-600 hover:bg-green-700 text-white h-7 px-2"
                                                    onClick={() => setApproveOpen({ req, action: 'approved' })}>
                                                    Approve
                                                </Button>
                                                <Button size="sm" variant="outline" className="text-xs text-red-600 border-red-200 hover:bg-red-50 h-7 px-2"
                                                    onClick={() => setApproveOpen({ req, action: 'rejected' })}>
                                                    Reject
                                                </Button>
                                            </div>
                                        )}
                                        {req.status !== 'pending' && req.approval_note && (
                                            <p className="text-xs text-slate-400 italic">{req.approval_note}</p>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            {/* Add Leave Dialog */}
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Submit Leave Request</DialogTitle></DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4 mt-2">
                        <div className="space-y-1.5">
                            <Label>Staff <span className="text-red-500">*</span></Label>
                            <Select value={data.staff_id} onValueChange={v => setData('staff_id', v)}>
                                <SelectTrigger><SelectValue placeholder="Select staff" /></SelectTrigger>
                                <SelectContent>
                                    {staffList.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.first_name} {s.last_name} ({s.emp_id})</SelectItem>)}
                                </SelectContent>
                            </Select>
                            {errors.staff_id && <p className="text-xs text-red-500">{errors.staff_id}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label>Leave Type <span className="text-red-500">*</span></Label>
                            <Select value={data.leave_type_id} onValueChange={v => setData('leave_type_id', v)}>
                                <SelectTrigger><SelectValue placeholder="Select type" /></SelectTrigger>
                                <SelectContent>
                                    {leaveTypes.map(t => <SelectItem key={t.id} value={String(t.id)}>{t.name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                            {errors.leave_type_id && <p className="text-xs text-red-500">{errors.leave_type_id}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Start Date <span className="text-red-500">*</span></Label>
                                <Input type="date" value={data.start_date} onChange={e => setData('start_date', e.target.value)} />
                            </div>
                            <div className="space-y-1.5">
                                <Label>End Date <span className="text-red-500">*</span></Label>
                                <Input type="date" value={data.end_date} onChange={e => setData('end_date', e.target.value)} />
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Reason</Label>
                            <Input value={data.reason} onChange={e => setData('reason', e.target.value)} placeholder="Optional" />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={processing} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {processing ? 'Submitting...' : 'Submit'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Approve/Reject Dialog */}
            <Dialog open={!!approveOpen} onOpenChange={() => setApproveOpen(null)}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle className={approveOpen?.action === 'approved' ? 'text-green-700' : 'text-red-700'}>
                            {approveOpen?.action === 'approved' ? 'Approve' : 'Reject'} Leave Request
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3 mt-2">
                        <p className="text-sm text-slate-600 dark:text-slate-400">
                            {approveOpen?.req.staff?.first_name} {approveOpen?.req.staff?.last_name} — {approveOpen?.req.leave_type?.name} ({approveOpen?.req.days} days)
                        </p>
                        <div className="space-y-1.5">
                            <Label>Note (optional)</Label>
                            <Input value={approvalNote} onChange={e => setApprovalNote(e.target.value)} placeholder="Add a note..." />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setApproveOpen(null)}>Cancel</Button>
                        <Button
                            onClick={handleApprove}
                            className={approveOpen?.action === 'approved' ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-red-600 hover:bg-red-700 text-white'}
                        >
                            Confirm {approveOpen?.action === 'approved' ? 'Approve' : 'Reject'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
