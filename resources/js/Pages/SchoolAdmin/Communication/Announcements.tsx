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
import { Checkbox } from '@/components/ui/checkbox';
import { Plus, Pencil, Trash2, Pin } from 'lucide-react';

interface SchoolClass  { id: number; name: string; }
interface Author       { id: number; name: string; }
interface Announcement {
    id: number;
    title: string;
    body: string;
    audience: 'all' | 'class' | 'role';
    target_role?: string;
    is_pinned: boolean;
    published_at?: string;
    author?: Author;
    school_class?: SchoolClass;
}
interface PaginatedAnnouncements {
    data: Announcement[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}
interface Props {
    announcements: PaginatedAnnouncements;
    classes:       SchoolClass[];
    filters:       { audience?: string };
}

const emptyForm = {
    title: '', body: '', audience: 'all', class_id: '', target_role: '',
    is_pinned: false, published_at: '',
};

const audienceColor: Record<string, 'default' | 'secondary'> = {
    all: 'default', class: 'secondary', role: 'secondary',
};

export default function Announcements({ announcements, classes, filters }: Props) {
    const [open, setOpen]       = useState(false);
    const [editing, setEditing] = useState<Announcement | null>(null);
    const [filterAudience, setFilterAudience] = useState(filters.audience ?? '');

    const { data, setData, post, put, processing, errors, reset } = useForm({ ...emptyForm });

    function openCreate() { reset(); setEditing(null); setOpen(true); }
    function openEdit(a: Announcement) {
        setEditing(a);
        setData({
            title: a.title, body: a.body, audience: a.audience,
            class_id: String(a.school_class?.id ?? ''),
            target_role: a.target_role ?? '',
            is_pinned: a.is_pinned,
            published_at: a.published_at ? a.published_at.slice(0, 16) : '',
        });
        setOpen(true);
    }
    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (editing) {
            put(`/school/communication/announcements/${editing.id}`, { onSuccess: () => { reset(); setOpen(false); } });
        } else {
            post('/school/communication/announcements', { onSuccess: () => { reset(); setOpen(false); } });
        }
    }
    function destroy(id: number) {
        if (confirm('Delete this announcement?')) router.delete(`/school/communication/announcements/${id}`);
    }
    function applyFilter() {
        router.get('/school/communication/announcements', { audience: filterAudience || undefined }, { preserveState: true });
    }

    return (
        <AppLayout title="Announcements">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Announcements</h1>
                        <p className="text-sm text-slate-500 mt-0.5">Post school-wide and class-specific announcements</p>
                    </div>
                    <Button onClick={openCreate} className="gap-2"><Plus className="w-4 h-4" /> New Announcement</Button>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="pt-4 pb-4">
                        <div className="flex gap-3 items-end">
                            <div className="w-44">
                                <Label className="text-xs mb-1 block">Audience</Label>
                                <Select value={filterAudience} onValueChange={setFilterAudience}>
                                    <SelectTrigger><SelectValue placeholder="All" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all_filter">All</SelectItem>
                                        <SelectItem value="all">School-wide</SelectItem>
                                        <SelectItem value="class">Class</SelectItem>
                                        <SelectItem value="role">Role</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button variant="outline" onClick={applyFilter}>Filter</Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Announcements list */}
                <div className="space-y-3">
                    {announcements.data.length === 0 && (
                        <Card><CardContent className="py-12 text-center text-slate-400">No announcements found</CardContent></Card>
                    )}
                    {announcements.data.map(a => (
                        <Card key={a.id} className={a.is_pinned ? 'border-indigo-300 dark:border-indigo-700' : ''}>
                            <CardContent className="pt-4 pb-4">
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2 flex-wrap">
                                            {a.is_pinned && <Pin className="w-3.5 h-3.5 text-indigo-500 shrink-0" />}
                                            <h3 className="font-semibold text-slate-900 dark:text-white">{a.title}</h3>
                                            <Badge variant={audienceColor[a.audience] ?? 'secondary'} className="text-xs">
                                                {a.audience === 'all' ? 'School-wide' : a.audience === 'class' ? (a.school_class?.name ?? 'Class') : (a.target_role ?? 'Role')}
                                            </Badge>
                                        </div>
                                        <p className="text-sm text-slate-500 mt-1 line-clamp-2">{a.body}</p>
                                        <p className="text-xs text-slate-400 mt-1">
                                            By {a.author?.name ?? 'Unknown'} · {a.published_at ? new Date(a.published_at).toLocaleDateString() : 'Draft'}
                                        </p>
                                    </div>
                                    <div className="flex gap-1 shrink-0">
                                        <Button size="sm" variant="outline" onClick={() => openEdit(a)}><Pencil className="w-3.5 h-3.5" /></Button>
                                        <Button size="sm" variant="destructive" onClick={() => destroy(a.id)}><Trash2 className="w-3.5 h-3.5" /></Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {announcements.last_page > 1 && (
                    <div className="flex justify-center gap-1">
                        {announcements.links.map((link, i) => (
                            <Button key={i} size="sm" variant={link.active ? 'default' : 'outline'} disabled={!link.url}
                                onClick={() => link.url && router.visit(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }} />
                        ))}
                    </div>
                )}
            </div>

            {/* Dialog */}
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader><DialogTitle>{editing ? 'Edit Announcement' : 'New Announcement'}</DialogTitle></DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label>Title *</Label>
                            <Input value={data.title} onChange={e => setData('title', e.target.value)} />
                            {errors.title && <p className="text-xs text-red-500 mt-1">{errors.title}</p>}
                        </div>
                        <div>
                            <Label>Body *</Label>
                            <Textarea value={data.body} onChange={e => setData('body', e.target.value)} rows={4} />
                            {errors.body && <p className="text-xs text-red-500 mt-1">{errors.body}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Audience *</Label>
                                <Select value={data.audience} onValueChange={v => setData('audience', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">School-wide</SelectItem>
                                        <SelectItem value="class">Specific Class</SelectItem>
                                        <SelectItem value="role">Specific Role</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            {data.audience === 'class' && (
                                <div>
                                    <Label>Class</Label>
                                    <Select value={data.class_id} onValueChange={v => setData('class_id', v)}>
                                        <SelectTrigger><SelectValue placeholder="Select class" /></SelectTrigger>
                                        <SelectContent>{classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}</SelectContent>
                                    </Select>
                                </div>
                            )}
                            {data.audience === 'role' && (
                                <div>
                                    <Label>Role</Label>
                                    <Select value={data.target_role} onValueChange={v => setData('target_role', v)}>
                                        <SelectTrigger><SelectValue placeholder="Select role" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="teacher">Teachers</SelectItem>
                                            <SelectItem value="student">Students</SelectItem>
                                            <SelectItem value="accountant">Accountants</SelectItem>
                                            <SelectItem value="librarian">Librarians</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                        </div>
                        <div>
                            <Label>Publish Date/Time</Label>
                            <Input type="datetime-local" value={data.published_at} onChange={e => setData('published_at', e.target.value)} />
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox id="pin" checked={data.is_pinned} onCheckedChange={v => setData('is_pinned', !!v)} />
                            <label htmlFor="pin" className="text-sm cursor-pointer">Pin this announcement</label>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={processing}>{editing ? 'Update' : 'Publish'}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
