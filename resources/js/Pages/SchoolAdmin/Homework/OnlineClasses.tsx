import AppLayout from '@/Layouts/AppLayout';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Plus, Trash2, ExternalLink } from 'lucide-react';

interface SchoolClass { id: number; name: string; }
interface Subject     { id: number; name: string; }
interface Staff       { id: number; first_name: string; last_name: string; }

interface OnlineClass {
    id: number;
    title: string;
    platform: string;
    meeting_url: string;
    meeting_id?: string;
    passcode?: string;
    scheduled_at: string;
    duration_minutes: number;
    status: 'scheduled' | 'live' | 'completed' | 'cancelled';
    school_class?: SchoolClass;
    subject?: Subject;
    teacher?: Staff;
}

interface PaginatedClasses {
    data: OnlineClass[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    onlineClasses: PaginatedClasses;
    classes:       SchoolClass[];
    subjects:      Subject[];
    staff:         Staff[];
    filters:       { status?: string; class_id?: string };
}

const statusColor: Record<string, 'default' | 'secondary' | 'destructive'> = {
    scheduled:  'secondary',
    live:       'default',
    completed:  'secondary',
    cancelled:  'destructive',
};

const platformLabel: Record<string, string> = {
    zoom: 'Zoom', google_meet: 'Google Meet', jitsi: 'Jitsi', other: 'Other',
};

const emptyForm = {
    class_id: '', subject_id: '', teacher_id: '',
    title: '', platform: 'zoom', meeting_url: '', meeting_id: '', passcode: '',
    scheduled_at: '', duration_minutes: '60',
};

export default function OnlineClasses({ onlineClasses, classes, subjects, staff, filters }: Props) {
    const [open, setOpen]                   = useState(false);
    const [filterStatus, setFilterStatus]   = useState(filters.status ?? '');
    const [filterClass, setFilterClass]     = useState(filters.class_id ?? '');

    const { data, setData, post, processing, errors, reset } = useForm({ ...emptyForm });
    const statusForm = useForm({ status: '' as OnlineClass['status'] });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/school/homework/online-classes', { onSuccess: () => { reset(); setOpen(false); } });
    }
    function updateStatus(id: number, status: OnlineClass['status']) {
        statusForm.setData('status', status);
        router.put(`/school/homework/online-classes/${id}/status`, { status }, { preserveScroll: true });
    }
    function destroy(id: number) {
        if (confirm('Remove this online class?')) router.delete(`/school/homework/online-classes/${id}`);
    }
    function applyFilter() {
        router.get('/school/homework/online-classes', {
            status: filterStatus || undefined,
            class_id: filterClass || undefined,
        }, { preserveState: true });
    }

    return (
        <AppLayout title="Online Classes">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Online Classes</h1>
                        <p className="text-sm text-slate-500 mt-0.5">Schedule and manage virtual classroom sessions</p>
                    </div>
                    <Button onClick={() => { reset(); setOpen(true); }} className="gap-2">
                        <Plus className="w-4 h-4" /> Schedule Class
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
                                        <SelectItem value="scheduled">Scheduled</SelectItem>
                                        <SelectItem value="live">Live</SelectItem>
                                        <SelectItem value="completed">Completed</SelectItem>
                                        <SelectItem value="cancelled">Cancelled</SelectItem>
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
                    <CardHeader><CardTitle>Classes ({onlineClasses.total})</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Title</TableHead>
                                    <TableHead>Class</TableHead>
                                    <TableHead>Subject</TableHead>
                                    <TableHead>Platform</TableHead>
                                    <TableHead>Scheduled At</TableHead>
                                    <TableHead>Duration</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {onlineClasses.data.length === 0 && (
                                    <TableRow><TableCell colSpan={8} className="text-center text-slate-400 py-8">No online classes found</TableCell></TableRow>
                                )}
                                {onlineClasses.data.map(oc => (
                                    <TableRow key={oc.id}>
                                        <TableCell className="font-medium">{oc.title}</TableCell>
                                        <TableCell>{oc.school_class?.name ?? '—'}</TableCell>
                                        <TableCell>{oc.subject?.name ?? '—'}</TableCell>
                                        <TableCell>{platformLabel[oc.platform] ?? oc.platform}</TableCell>
                                        <TableCell>{new Date(oc.scheduled_at).toLocaleString()}</TableCell>
                                        <TableCell>{oc.duration_minutes} min</TableCell>
                                        <TableCell>
                                            <Select value={oc.status} onValueChange={v => updateStatus(oc.id, v as OnlineClass['status'])}>
                                                <SelectTrigger className="h-7 text-xs w-28">
                                                    <Badge variant={statusColor[oc.status] ?? 'secondary'} className="text-xs">
                                                        {oc.status.charAt(0).toUpperCase() + oc.status.slice(1)}
                                                    </Badge>
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="scheduled">Scheduled</SelectItem>
                                                    <SelectItem value="live">Live</SelectItem>
                                                    <SelectItem value="completed">Completed</SelectItem>
                                                    <SelectItem value="cancelled">Cancelled</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </TableCell>
                                        <TableCell className="text-right space-x-1">
                                            <Button size="sm" variant="outline" asChild>
                                                <a href={oc.meeting_url} target="_blank" rel="noreferrer">
                                                    <ExternalLink className="w-3.5 h-3.5" />
                                                </a>
                                            </Button>
                                            <Button size="sm" variant="destructive" onClick={() => destroy(oc.id)}>
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {onlineClasses.last_page > 1 && (
                    <div className="flex justify-center gap-1">
                        {onlineClasses.links.map((link, i) => (
                            <Button key={i} size="sm" variant={link.active ? 'default' : 'outline'} disabled={!link.url}
                                onClick={() => link.url && router.visit(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }} />
                        ))}
                    </div>
                )}
            </div>

            {/* Schedule Dialog */}
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
                    <DialogHeader><DialogTitle>Schedule Online Class</DialogTitle></DialogHeader>
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
                                <Label>Platform *</Label>
                                <Select value={data.platform} onValueChange={v => setData('platform', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="zoom">Zoom</SelectItem>
                                        <SelectItem value="google_meet">Google Meet</SelectItem>
                                        <SelectItem value="jitsi">Jitsi</SelectItem>
                                        <SelectItem value="other">Other</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.platform && <p className="text-xs text-red-500 mt-1">{errors.platform}</p>}
                            </div>
                        </div>
                        <div>
                            <Label>Title *</Label>
                            <Input value={data.title} onChange={e => setData('title', e.target.value)} placeholder="e.g. Math - Quadratic Equations" />
                            {errors.title && <p className="text-xs text-red-500 mt-1">{errors.title}</p>}
                        </div>
                        <div>
                            <Label>Meeting URL *</Label>
                            <Input type="url" value={data.meeting_url} onChange={e => setData('meeting_url', e.target.value)} placeholder="https://zoom.us/j/..." />
                            {errors.meeting_url && <p className="text-xs text-red-500 mt-1">{errors.meeting_url}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Meeting ID</Label>
                                <Input value={data.meeting_id} onChange={e => setData('meeting_id', e.target.value)} placeholder="Optional" />
                            </div>
                            <div>
                                <Label>Passcode</Label>
                                <Input value={data.passcode} onChange={e => setData('passcode', e.target.value)} placeholder="Optional" />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Scheduled At *</Label>
                                <Input type="datetime-local" value={data.scheduled_at} onChange={e => setData('scheduled_at', e.target.value)} />
                                {errors.scheduled_at && <p className="text-xs text-red-500 mt-1">{errors.scheduled_at}</p>}
                            </div>
                            <div>
                                <Label>Duration (minutes) *</Label>
                                <Input type="number" min="15" max="480" value={data.duration_minutes} onChange={e => setData('duration_minutes', e.target.value)} />
                                {errors.duration_minutes && <p className="text-xs text-red-500 mt-1">{errors.duration_minutes}</p>}
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={processing}>Schedule</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
