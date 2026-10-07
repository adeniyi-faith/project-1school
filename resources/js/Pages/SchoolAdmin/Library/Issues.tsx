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
import { ArrowLeft, Plus, RotateCcw } from 'lucide-react';
import type { PageProps, PaginatedResponse } from '@/Types';

interface BookOption { id: number; title: string; author: string; available_copies: number; isbn: string | null; }
interface MemberOption { id: number; first_name: string; last_name: string | null; admission_no?: string; emp_id?: string; }
interface Issue {
    id: number; book_id: number; member_type: string; member_id: number; member_type_label: string;
    member_name: string; member_id_no: string;
    issued_date: string; due_date: string; returned_date: string | null;
    fine: string; status: string; note: string | null;
    book?: { title: string; author: string; isbn: string | null };
}

interface Props {
    issues: PaginatedResponse<Issue>;
    books: BookOption[];
    students: MemberOption[];
    staffList: MemberOption[];
    filters: { status?: string; member_type?: string };
}

const STATUS_STYLE: Record<string, string> = {
    issued:   'bg-blue-100 text-blue-700',
    returned: 'bg-green-100 text-green-700',
    overdue:  'bg-red-100 text-red-700',
};

const issueDefault = { book_id: '', member_type: 'student', member_id: '', issued_date: new Date().toISOString().split('T')[0], due_date: '', fine_per_day: '2', note: '' };

export default function LibraryIssues({ issues, books, students, staffList, filters }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [issueOpen, setIssueOpen] = useState(false);
    const [returnOpen, setReturnOpen] = useState<Issue | null>(null);
    const [issueForm, setIssueForm] = useState(issueDefault);
    const [returnDate, setReturnDate] = useState(new Date().toISOString().split('T')[0]);
    const [saving, setSaving] = useState(false);

    function applyFilter(key: string, value: string) {
        router.get('/school/library/issues', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    const memberOptions = issueForm.member_type === 'student' ? students : staffList;

    function handleIssue() {
        setSaving(true);
        router.post('/school/library/issues', issueForm, {
            preserveScroll: true,
            onSuccess: () => { setIssueOpen(false); setIssueForm(issueDefault); setSaving(false); },
            onError: () => setSaving(false),
        });
    }

    function handleReturn() {
        if (!returnOpen) return;
        setSaving(true);
        router.put(`/school/library/issues/${returnOpen.id}/return`, { returned_date: returnDate }, {
            preserveScroll: true,
            onSuccess: () => { setReturnOpen(null); setSaving(false); },
            onError: () => setSaving(false),
        });
    }

    return (
        <AppLayout title="Library — Issue / Return">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link href="/school/library/books" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Books
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <div>
                            <h1 className="text-xl font-bold text-slate-900 dark:text-white">Issue / Return</h1>
                            <p className="text-sm text-slate-500">{issues.meta?.total ?? 0} records</p>
                        </div>
                    </div>
                    <Button onClick={() => setIssueOpen(true)} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                        <Plus className="w-4 h-4" /> Issue Book
                    </Button>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                {/* Filters */}
                <div className="flex gap-3">
                    <Select value={filters.status ?? ''} onValueChange={v => applyFilter('status', v)}>
                        <SelectTrigger className="w-36"><SelectValue placeholder="All Status" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All</SelectItem>
                            <SelectItem value="issued">Issued</SelectItem>
                            <SelectItem value="returned">Returned</SelectItem>
                            <SelectItem value="overdue">Overdue</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select value={filters.member_type ?? ''} onValueChange={v => applyFilter('member_type', v)}>
                        <SelectTrigger className="w-36"><SelectValue placeholder="All Members" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All</SelectItem>
                            <SelectItem value="student">Students</SelectItem>
                            <SelectItem value="staff">Staff</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Table */}
                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Book</TableHead>
                                <TableHead>Member</TableHead>
                                <TableHead>Issued</TableHead>
                                <TableHead>Due</TableHead>
                                <TableHead>Returned</TableHead>
                                <TableHead className="text-right">Fine</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-24"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {issues.data.length === 0 ? (
                                <TableRow><TableCell colSpan={8} className="text-center py-16 text-slate-400">No issue records found.</TableCell></TableRow>
                            ) : issues.data.map(iss => (
                                <TableRow key={iss.id} className={iss.status === 'overdue' ? 'bg-red-50/30 dark:bg-red-950/10' : ''}>
                                    <TableCell>
                                        <p className="font-medium text-sm text-slate-900 dark:text-white">{iss.book?.title}</p>
                                        <p className="text-xs text-slate-400">{iss.book?.author}</p>
                                    </TableCell>
                                    <TableCell>
                                        <p className="text-sm text-slate-700 dark:text-slate-300">{iss.member_name}</p>
                                        <p className="text-xs text-slate-400">{iss.member_type_label} · {iss.member_id_no}</p>
                                    </TableCell>
                                    <TableCell className="text-sm text-slate-500">{new Date(iss.issued_date).toLocaleDateString()}</TableCell>
                                    <TableCell className={`text-sm ${iss.status === 'overdue' ? 'text-red-600 font-medium' : 'text-slate-500'}`}>
                                        {new Date(iss.due_date).toLocaleDateString()}
                                    </TableCell>
                                    <TableCell className="text-sm text-slate-500">
                                        {iss.returned_date ? new Date(iss.returned_date).toLocaleDateString() : '—'}
                                    </TableCell>
                                    <TableCell className={`text-right text-sm ${Number(iss.fine) > 0 ? 'text-red-600 font-medium' : 'text-slate-400'}`}>
                                        {Number(iss.fine) > 0 ? `৳${Number(iss.fine).toLocaleString()}` : '—'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge className={`border-0 text-xs capitalize ${STATUS_STYLE[iss.status] ?? ''}`}>{iss.status}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        {iss.status !== 'returned' && (
                                            <Button size="sm" variant="outline" className="text-xs h-7 inline-flex items-center gap-1"
                                                onClick={() => { setReturnOpen(iss); setReturnDate(new Date().toISOString().split('T')[0]); }}>
                                                <RotateCcw className="w-3 h-3" /> Return
                                            </Button>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            {/* Issue Book Dialog */}
            <Dialog open={issueOpen} onOpenChange={setIssueOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Issue Book</DialogTitle></DialogHeader>
                    <div className="space-y-4 mt-2">
                        <div className="space-y-1.5">
                            <Label>Book <span className="text-red-500">*</span></Label>
                            <Select value={issueForm.book_id} onValueChange={v => setIssueForm(p => ({ ...p, book_id: v }))}>
                                <SelectTrigger><SelectValue placeholder="Select book" /></SelectTrigger>
                                <SelectContent>
                                    {books.map(b => (
                                        <SelectItem key={b.id} value={String(b.id)}>
                                            {b.title} — {b.author} ({b.available_copies} avail.)
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Member Type <span className="text-red-500">*</span></Label>
                                <Select value={issueForm.member_type} onValueChange={v => setIssueForm(p => ({ ...p, member_type: v, member_id: '' }))}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="student">Student</SelectItem>
                                        <SelectItem value="staff">Staff</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label>Member <span className="text-red-500">*</span></Label>
                                <Select value={issueForm.member_id} onValueChange={v => setIssueForm(p => ({ ...p, member_id: v }))}>
                                    <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent>
                                        {memberOptions.map(m => (
                                            <SelectItem key={m.id} value={String(m.id)}>
                                                {m.first_name} {m.last_name} ({m.admission_no ?? m.emp_id})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Issue Date <span className="text-red-500">*</span></Label>
                                <Input type="date" value={issueForm.issued_date} onChange={e => setIssueForm(p => ({ ...p, issued_date: e.target.value }))} />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Due Date <span className="text-red-500">*</span></Label>
                                <Input type="date" value={issueForm.due_date} onChange={e => setIssueForm(p => ({ ...p, due_date: e.target.value }))} />
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Fine Per Day (৳)</Label>
                            <Input type="number" min="0" step="0.5" value={issueForm.fine_per_day} onChange={e => setIssueForm(p => ({ ...p, fine_per_day: e.target.value }))} />
                        </div>
                        <div className="space-y-1.5">
                            <Label>Note</Label>
                            <Input value={issueForm.note} onChange={e => setIssueForm(p => ({ ...p, note: e.target.value }))} placeholder="Optional" />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setIssueOpen(false)}>Cancel</Button>
                        <Button onClick={handleIssue} disabled={saving} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                            {saving ? 'Issuing...' : 'Issue Book'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Return Dialog */}
            <Dialog open={!!returnOpen} onOpenChange={() => setReturnOpen(null)}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader><DialogTitle>Return Book</DialogTitle></DialogHeader>
                    <div className="space-y-3 mt-2">
                        <div className="rounded-lg bg-slate-50 dark:bg-slate-900 p-3 text-sm space-y-1">
                            <p className="font-medium">{returnOpen?.book?.title}</p>
                            <p className="text-slate-500">Due: {returnOpen?.due_date ? new Date(returnOpen.due_date).toLocaleDateString() : '—'}</p>
                            <p className="text-slate-500">Fine rate: ৳{returnOpen?.fine} / day</p>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Return Date <span className="text-red-500">*</span></Label>
                            <Input type="date" value={returnDate} onChange={e => setReturnDate(e.target.value)} />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setReturnOpen(null)}>Cancel</Button>
                        <Button onClick={handleReturn} disabled={saving} className="bg-green-600 hover:bg-green-700 text-white">
                            {saving ? 'Processing...' : 'Confirm Return'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
