import AppLayout from '@/Layouts/AppLayout';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Plus, Trash2, CheckCircle, XCircle } from 'lucide-react';

interface SchoolClass { id: number; name: string; }
interface Subject     { id: number; name: string; }
interface Staff       { id: number; first_name: string; last_name: string; }

interface LessonPlan {
    id: number;
    title: string;
    objectives?: string;
    content?: string;
    teaching_methods?: string;
    resources?: string;
    week_start: string;
    status: 'draft' | 'submitted' | 'approved' | 'rejected';
    reviewer_feedback?: string;
    school_class?: SchoolClass;
    subject?: Subject;
    teacher?: Staff;
}

interface PaginatedPlans {
    data: LessonPlan[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    plans:    PaginatedPlans;
    classes:  SchoolClass[];
    subjects: Subject[];
    staff:    Staff[];
    filters:  { status?: string; class_id?: string };
}

const statusColor: Record<string, 'default' | 'secondary' | 'destructive'> = {
    draft:     'secondary',
    submitted: 'default',
    approved:  'default',
    rejected:  'destructive',
};

const emptyForm = {
    class_id: '', subject_id: '', teacher_id: '',
    title: '', objectives: '', content: '', teaching_methods: '', resources: '', week_start: '',
};

export default function LessonPlans({ plans, classes, subjects, staff, filters }: Props) {
    const [open, setOpen]               = useState(false);
    const [reviewing, setReviewing]     = useState<LessonPlan | null>(null);
    const [filterStatus, setFilterStatus]   = useState(filters.status ?? '');
    const [filterClass, setFilterClass]     = useState(filters.class_id ?? '');

    const { data, setData, post, processing, errors, reset } = useForm({ ...emptyForm });
    const reviewForm = useForm({ action: 'approved' as 'approved' | 'rejected' | 'submitted', reviewer_feedback: '' });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/school/homework/lesson-plans', { onSuccess: () => { reset(); setOpen(false); } });
    }
    function submitReview(e: React.FormEvent) {
        e.preventDefault();
        if (!reviewing) return;
        reviewForm.put(`/school/homework/lesson-plans/${reviewing.id}/review`, {
            onSuccess: () => setReviewing(null),
        });
    }
    function destroy(id: number) {
        if (confirm('Delete this lesson plan?')) router.delete(`/school/homework/lesson-plans/${id}`);
    }
    function applyFilter() {
        router.get('/school/homework/lesson-plans', {
            status: filterStatus || undefined,
            class_id: filterClass || undefined,
        }, { preserveState: true });
    }

    return (
        <AppLayout title="Lesson Plans">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Lesson Plans</h1>
                        <p className="text-sm text-slate-500 mt-0.5">Create and review weekly lesson plans</p>
                    </div>
                    <Button onClick={() => { reset(); setOpen(true); }} className="gap-2">
                        <Plus className="w-4 h-4" /> New Plan
                    </Button>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="pt-4 pb-4">
                        <div className="flex flex-wrap gap-3 items-end">
                            <div className="w-44">
                                <Label className="text-xs mb-1 block">Status</Label>
                                <Select value={filterStatus} onValueChange={setFilterStatus}>
                                    <SelectTrigger><SelectValue placeholder="All statuses" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All</SelectItem>
                                        <SelectItem value="draft">Draft</SelectItem>
                                        <SelectItem value="submitted">Submitted</SelectItem>
                                        <SelectItem value="approved">Approved</SelectItem>
                                        <SelectItem value="rejected">Rejected</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="w-44">
                                <Label className="text-xs mb-1 block">Class</Label>
                                <Select value={filterClass} onValueChange={setFilterClass}>
                                    <SelectTrigger><SelectValue placeholder="All classes" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All</SelectItem>
                                        {classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button variant="outline" onClick={applyFilter}>Filter</Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Table */}
                <Card>
                    <CardHeader><CardTitle>Plans ({plans.total})</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Title</TableHead>
                                    <TableHead>Class</TableHead>
                                    <TableHead>Subject</TableHead>
                                    <TableHead>Teacher</TableHead>
                                    <TableHead>Week Start</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {plans.data.length === 0 && (
                                    <TableRow><TableCell colSpan={7} className="text-center text-slate-400 py-8">No lesson plans found</TableCell></TableRow>
                                )}
                                {plans.data.map(plan => (
                                    <TableRow key={plan.id}>
                                        <TableCell className="font-medium">{plan.title}</TableCell>
                                        <TableCell>{plan.school_class?.name ?? '—'}</TableCell>
                                        <TableCell>{plan.subject?.name ?? '—'}</TableCell>
                                        <TableCell>{plan.teacher ? `${plan.teacher.first_name} ${plan.teacher.last_name}` : '—'}</TableCell>
                                        <TableCell>{new Date(plan.week_start).toLocaleDateString()}</TableCell>
                                        <TableCell>
                                            <Badge variant={statusColor[plan.status] ?? 'secondary'}>
                                                {plan.status.charAt(0).toUpperCase() + plan.status.slice(1)}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right space-x-1">
                                            <Button size="sm" variant="outline" onClick={() => {
                                                setReviewing(plan);
                                                reviewForm.setData({ action: 'approved', reviewer_feedback: '' });
                                            }}>
                                                <CheckCircle className="w-3.5 h-3.5" />
                                            </Button>
                                            <Button size="sm" variant="destructive" onClick={() => destroy(plan.id)}>
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {plans.last_page > 1 && (
                    <div className="flex justify-center gap-1">
                        {plans.links.map((link, i) => (
                            <Button key={i} size="sm" variant={link.active ? 'default' : 'outline'} disabled={!link.url}
                                onClick={() => link.url && router.visit(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }} />
                        ))}
                    </div>
                )}
            </div>

            {/* Create Dialog */}
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader><DialogTitle>New Lesson Plan</DialogTitle></DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Class *</Label>
                                <Select value={data.class_id} onValueChange={v => setData('class_id', v)}>
                                    <SelectTrigger><SelectValue placeholder="Select class" /></SelectTrigger>
                                    <SelectContent>{classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}</SelectContent>
                                </Select>
                                {errors.class_id && <p className="text-xs text-red-500 mt-1">{errors.class_id}</p>}
                            </div>
                            <div>
                                <Label>Subject *</Label>
                                <Select value={data.subject_id} onValueChange={v => setData('subject_id', v)}>
                                    <SelectTrigger><SelectValue placeholder="Select subject" /></SelectTrigger>
                                    <SelectContent>{subjects.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}</SelectContent>
                                </Select>
                                {errors.subject_id && <p className="text-xs text-red-500 mt-1">{errors.subject_id}</p>}
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Teacher</Label>
                                <Select value={data.teacher_id} onValueChange={v => setData('teacher_id', v)}>
                                    <SelectTrigger><SelectValue placeholder="Optional" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">None</SelectItem>
                                        {staff.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.first_name} {s.last_name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>Week Start *</Label>
                                <Input type="date" value={data.week_start} onChange={e => setData('week_start', e.target.value)} />
                                {errors.week_start && <p className="text-xs text-red-500 mt-1">{errors.week_start}</p>}
                            </div>
                        </div>
                        <div>
                            <Label>Title *</Label>
                            <Input value={data.title} onChange={e => setData('title', e.target.value)} placeholder="e.g. Introduction to Algebra" />
                            {errors.title && <p className="text-xs text-red-500 mt-1">{errors.title}</p>}
                        </div>
                        <div>
                            <Label>Learning Objectives</Label>
                            <Textarea value={data.objectives} onChange={e => setData('objectives', e.target.value)} rows={2} />
                        </div>
                        <div>
                            <Label>Content</Label>
                            <Textarea value={data.content} onChange={e => setData('content', e.target.value)} rows={3} />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Teaching Methods</Label>
                                <Textarea value={data.teaching_methods} onChange={e => setData('teaching_methods', e.target.value)} rows={2} />
                            </div>
                            <div>
                                <Label>Resources</Label>
                                <Textarea value={data.resources} onChange={e => setData('resources', e.target.value)} rows={2} />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={processing}>Create Plan</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Review Dialog */}
            <Dialog open={!!reviewing} onOpenChange={() => setReviewing(null)}>
                <DialogContent className="max-w-md">
                    <DialogHeader><DialogTitle>Review Lesson Plan</DialogTitle></DialogHeader>
                    {reviewing && (
                        <form onSubmit={submitReview} className="space-y-4">
                            <p className="text-sm text-slate-600 dark:text-slate-400">
                                Plan: <strong>{reviewing.title}</strong>
                            </p>
                            <div>
                                <Label>Decision *</Label>
                                <Select value={reviewForm.data.action} onValueChange={v => reviewForm.setData('action', v as 'approved' | 'rejected' | 'submitted')}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="approved"><span className="flex items-center gap-2"><CheckCircle className="w-4 h-4 text-green-500" /> Approve</span></SelectItem>
                                        <SelectItem value="rejected"><span className="flex items-center gap-2"><XCircle className="w-4 h-4 text-red-500" /> Reject</span></SelectItem>
                                        <SelectItem value="submitted">Mark as Submitted</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>Feedback</Label>
                                <Textarea
                                    value={reviewForm.data.reviewer_feedback}
                                    onChange={e => reviewForm.setData('reviewer_feedback', e.target.value)}
                                    rows={3} placeholder="Optional feedback..."
                                />
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setReviewing(null)}>Cancel</Button>
                                <Button type="submit" disabled={reviewForm.processing}>Submit</Button>
                            </DialogFooter>
                        </form>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
