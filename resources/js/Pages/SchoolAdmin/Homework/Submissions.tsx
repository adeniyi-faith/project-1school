import AppLayout from '@/Layouts/AppLayout';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ArrowLeft, CheckCircle } from 'lucide-react';

interface Student { id: number; first_name: string; last_name: string; admission_no: string; }
interface Submission {
    id: number;
    status: 'submitted' | 'reviewed' | 'returned';
    text_response?: string;
    file?: string;
    teacher_remarks?: string;
    created_at: string;
    student?: Student;
}
interface Homework {
    id: number;
    title: string;
    due_date: string;
    description?: string;
    school_class?: { id: number; name: string };
    subject?: { id: number; name: string };
    teacher?: { id: number; first_name: string; last_name: string };
}
interface Props {
    homework:    Homework;
    submissions: Submission[];
    filters:     { status?: string };
}

const statusColor: Record<string, 'default' | 'secondary' | 'destructive'> = {
    submitted: 'default',
    reviewed:  'secondary',
    returned:  'destructive',
};

export default function Submissions({ homework, submissions, filters }: Props) {
    const [reviewing, setReviewing] = useState<Submission | null>(null);
    const [filterStatus, setFilterStatus] = useState(filters.status ?? '');

    const { data, setData, put, processing, errors } = useForm({
        status:           'reviewed' as 'reviewed' | 'returned',
        teacher_remarks:  '',
    });

    function openReview(sub: Submission) {
        setReviewing(sub);
        setData({ status: 'reviewed', teacher_remarks: sub.teacher_remarks ?? '' });
    }
    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (!reviewing) return;
        put(`/school/homework/submissions/${reviewing.id}/review`, {
            onSuccess: () => setReviewing(null),
        });
    }
    function applyFilter() {
        router.get(`/school/homework/${homework.id}/submissions`, { status: filterStatus || undefined }, { preserveState: true });
    }

    return (
        <AppLayout title="Submissions">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Button variant="outline" size="sm" onClick={() => router.visit('/school/homework')}>
                        <ArrowLeft className="w-4 h-4 mr-1" /> Back
                    </Button>
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">{homework.title}</h1>
                        <p className="text-sm text-slate-500 mt-0.5">
                            {homework.school_class?.name} · {homework.subject?.name} · Due {new Date(homework.due_date).toLocaleDateString()}
                        </p>
                    </div>
                </div>

                {/* Filters */}
                <div className="flex gap-3 items-end">
                    <div className="w-44">
                        <Label className="text-xs mb-1 block">Status</Label>
                        <Select value={filterStatus} onValueChange={setFilterStatus}>
                            <SelectTrigger><SelectValue placeholder="All statuses" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All</SelectItem>
                                <SelectItem value="submitted">Submitted</SelectItem>
                                <SelectItem value="reviewed">Reviewed</SelectItem>
                                <SelectItem value="returned">Returned</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <Button variant="outline" size="sm" onClick={applyFilter}>Filter</Button>
                </div>

                {/* Table */}
                <Card>
                    <CardHeader><CardTitle>Submissions ({submissions.length})</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Student</TableHead>
                                    <TableHead>Admission No</TableHead>
                                    <TableHead>Submitted At</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Remarks</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {submissions.length === 0 && (
                                    <TableRow><TableCell colSpan={6} className="text-center text-slate-400 py-8">No submissions yet</TableCell></TableRow>
                                )}
                                {submissions.map(sub => (
                                    <TableRow key={sub.id}>
                                        <TableCell className="font-medium">
                                            {sub.student ? `${sub.student.first_name} ${sub.student.last_name}` : '—'}
                                        </TableCell>
                                        <TableCell>{sub.student?.admission_no ?? '—'}</TableCell>
                                        <TableCell>{new Date(sub.created_at).toLocaleString()}</TableCell>
                                        <TableCell>
                                            <Badge variant={statusColor[sub.status] ?? 'secondary'}>
                                                {sub.status.charAt(0).toUpperCase() + sub.status.slice(1)}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="max-w-xs truncate text-sm text-slate-500">
                                            {sub.teacher_remarks ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button size="sm" variant="outline" onClick={() => openReview(sub)}>
                                                <CheckCircle className="w-3.5 h-3.5 mr-1" /> Review
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            {/* Review Dialog */}
            <Dialog open={!!reviewing} onOpenChange={() => setReviewing(null)}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Review Submission</DialogTitle>
                    </DialogHeader>
                    {reviewing && (
                        <form onSubmit={submit} className="space-y-4">
                            <p className="text-sm text-slate-600 dark:text-slate-400">
                                Student: <strong>{reviewing.student?.first_name} {reviewing.student?.last_name}</strong>
                            </p>
                            {reviewing.text_response && (
                                <div>
                                    <Label className="text-xs">Student's Response</Label>
                                    <p className="mt-1 text-sm bg-slate-50 dark:bg-slate-900 rounded p-2 whitespace-pre-wrap">
                                        {reviewing.text_response}
                                    </p>
                                </div>
                            )}
                            <div>
                                <Label>Action *</Label>
                                <Select value={data.status} onValueChange={v => setData('status', v as 'reviewed' | 'returned')}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="reviewed">Mark as Reviewed</SelectItem>
                                        <SelectItem value="returned">Return for Revision</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.status && <p className="text-xs text-red-500 mt-1">{errors.status}</p>}
                            </div>
                            <div>
                                <Label>Teacher Remarks</Label>
                                <Textarea
                                    value={data.teacher_remarks}
                                    onChange={e => setData('teacher_remarks', e.target.value)}
                                    rows={3}
                                    placeholder="Optional feedback..."
                                />
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setReviewing(null)}>Cancel</Button>
                                <Button type="submit" disabled={processing}>Submit Review</Button>
                            </DialogFooter>
                        </form>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
