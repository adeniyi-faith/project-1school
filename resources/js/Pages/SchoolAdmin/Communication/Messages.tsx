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
import { Pencil } from 'lucide-react';

interface User    { id: number; name: string; }
interface Message {
    id: number;
    subject?: string;
    body: string;
    read_at?: string;
    created_at: string;
    sender?: User;
    recipient?: User;
}
interface PaginatedMessages {
    data: Message[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}
interface Props {
    inbox: PaginatedMessages;
    sent:  PaginatedMessages;
    users: User[];
}

export default function Messages({ inbox, sent, users }: Props) {
    const [open, setOpen] = useState(false);
    const [tab, setTab]   = useState<'inbox' | 'sent'>('inbox');

    const { data, setData, post, processing, errors, reset } = useForm({
        recipient_id: '',
        subject:      '',
        body:         '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/school/communication/messages', { onSuccess: () => { reset(); setOpen(false); } });
    }
    function markRead(id: number) {
        router.put(`/school/communication/messages/${id}/read`, {}, { preserveScroll: true });
    }

    return (
        <AppLayout title="Messages">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Messages</h1>
                        <p className="text-sm text-slate-500 mt-0.5">Internal messaging between staff members</p>
                    </div>
                    <Button onClick={() => { reset(); setOpen(true); }} className="gap-2">
                        <Pencil className="w-4 h-4" /> Compose
                    </Button>
                </div>

                {/* Tab buttons */}
                <div className="flex gap-1 border-b border-slate-200 dark:border-slate-700">
                    <button
                        onClick={() => setTab('inbox')}
                        className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${tab === 'inbox' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'}`}
                    >
                        Inbox
                        {inbox.data.filter(m => !m.read_at).length > 0 && (
                            <Badge className="ml-2 text-xs">{inbox.data.filter(m => !m.read_at).length}</Badge>
                        )}
                    </button>
                    <button
                        onClick={() => setTab('sent')}
                        className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${tab === 'sent' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'}`}
                    >
                        Sent
                    </button>
                </div>

                {tab === 'inbox' && (
                    <Card>
                        <CardHeader><CardTitle>Inbox ({inbox.total})</CardTitle></CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>From</TableHead>
                                        <TableHead>Subject</TableHead>
                                        <TableHead>Preview</TableHead>
                                        <TableHead>Received</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {inbox.data.length === 0 && (
                                        <TableRow><TableCell colSpan={6} className="text-center text-slate-400 py-8">No messages</TableCell></TableRow>
                                    )}
                                    {inbox.data.map(m => (
                                        <TableRow key={m.id} className={!m.read_at ? 'font-semibold' : ''}>
                                            <TableCell>{m.sender?.name ?? '—'}</TableCell>
                                            <TableCell>{m.subject ?? '(no subject)'}</TableCell>
                                            <TableCell className="max-w-xs truncate text-sm text-slate-500">{m.body}</TableCell>
                                            <TableCell className="text-sm">{new Date(m.created_at).toLocaleDateString()}</TableCell>
                                            <TableCell>
                                                {m.read_at
                                                    ? <Badge variant="secondary">Read</Badge>
                                                    : <Badge>Unread</Badge>
                                                }
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {!m.read_at && (
                                                    <Button size="sm" variant="outline" onClick={() => markRead(m.id)}>
                                                        Mark Read
                                                    </Button>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {tab === 'sent' && (
                    <Card>
                        <CardHeader><CardTitle>Sent ({sent.total})</CardTitle></CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>To</TableHead>
                                        <TableHead>Subject</TableHead>
                                        <TableHead>Preview</TableHead>
                                        <TableHead>Sent</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {sent.data.length === 0 && (
                                        <TableRow><TableCell colSpan={4} className="text-center text-slate-400 py-8">No sent messages</TableCell></TableRow>
                                    )}
                                    {sent.data.map(m => (
                                        <TableRow key={m.id}>
                                            <TableCell>{m.recipient?.name ?? '—'}</TableCell>
                                            <TableCell>{m.subject ?? '(no subject)'}</TableCell>
                                            <TableCell className="max-w-xs truncate text-sm text-slate-500">{m.body}</TableCell>
                                            <TableCell className="text-sm">{new Date(m.created_at).toLocaleDateString()}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Compose Dialog */}
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader><DialogTitle>New Message</DialogTitle></DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label>To *</Label>
                            <Select value={data.recipient_id} onValueChange={v => setData('recipient_id', v)}>
                                <SelectTrigger><SelectValue placeholder="Select recipient" /></SelectTrigger>
                                <SelectContent>
                                    {users.map(u => <SelectItem key={u.id} value={String(u.id)}>{u.name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                            {errors.recipient_id && <p className="text-xs text-red-500 mt-1">{errors.recipient_id}</p>}
                        </div>
                        <div>
                            <Label>Subject</Label>
                            <Input value={data.subject} onChange={e => setData('subject', e.target.value)} placeholder="Optional" />
                        </div>
                        <div>
                            <Label>Message *</Label>
                            <Textarea value={data.body} onChange={e => setData('body', e.target.value)} rows={5} />
                            {errors.body && <p className="text-xs text-red-500 mt-1">{errors.body}</p>}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={processing}>Send</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
