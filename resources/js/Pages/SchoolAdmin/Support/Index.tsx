import { useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import { LifeBuoy, Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { EmptyState, PageHeader, Panel, Pill } from '@/components/app/kit';
import { StatusPill, when } from '@/components/support/helpers';
import { useCan } from '@/lib/can';

interface Ticket { id: number; subject: string; category: string; priority: string; status: string; by: string | null; last_reply_at: string | null }
interface Props { tickets: { data: Ticket[]; links?: unknown }; categories: Record<string, string> }

export default function SupportIndex({ tickets, categories }: Props) {
    const can = useCan();
    const [open, setOpen] = useState(false);
    const form = useForm({ subject: '', category: 'question', priority: 'normal', body: '' });
    const submit = (e: React.FormEvent) => { e.preventDefault(); form.post('/school/support', { onSuccess: () => { setOpen(false); form.reset(); } }); };
    const add = can('support.create') ? <Button onClick={() => setOpen(true)} className="bg-indigo-600 text-white hover:bg-indigo-700"><Plus className="size-4" /> New request</Button> : undefined;

    return (
        <AppLayout title="Help and support">
            <div className="space-y-6">
                <PageHeader title="Help and support" description="Ask the platform team a question or report a problem. Replies show up here and in your bell." actions={add} />
                {add && <div className="md:hidden">{add}</div>}

                <Panel flush>
                    {tickets.data.length === 0 ? (
                        <EmptyState icon={LifeBuoy} title="No requests yet" text="When you need help, send a request and the platform team will answer here." action={add} />
                    ) : (
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {tickets.data.map((t) => (
                                <li key={t.id}>
                                    <Link href={`/school/support/${t.id}`} className="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-3.5 hover:bg-slate-50 dark:hover:bg-white/[0.03] sm:px-5">
                                        <span className="min-w-0">
                                            <span className="block truncate text-sm font-medium text-slate-900 dark:text-white">{t.subject}</span>
                                            <span className="block text-xs text-slate-500">{t.category} · by {t.by ?? 'someone'} · {when(t.last_reply_at)}</span>
                                        </span>
                                        <span className="flex items-center gap-2">
                                            {t.priority === 'urgent' && t.status !== 'closed' && <Pill tone="bad">Urgent</Pill>}
                                            <StatusPill status={t.status} school />
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </Panel>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader><DialogTitle>New request</DialogTitle></DialogHeader>
                    <form onSubmit={submit} className="space-y-3">
                        <div>
                            <label htmlFor="subject" className="text-sm font-medium">What is it about?</label>
                            <Input id="subject" value={form.data.subject} maxLength={150} onChange={(e) => form.setData('subject', e.target.value)} autoFocus />
                            {form.errors.subject && <p className="text-xs text-red-600 mt-1">{form.errors.subject}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label htmlFor="category" className="text-sm font-medium">Kind</label>
                                <select id="category" value={form.data.category} onChange={(e) => form.setData('category', e.target.value)} className="mt-1 w-full h-9 rounded-lg border border-input bg-transparent px-2 text-sm dark:bg-slate-900">
                                    {Object.entries(categories).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                </select>
                            </div>
                            <div>
                                <label htmlFor="priority" className="text-sm font-medium">How soon?</label>
                                <select id="priority" value={form.data.priority} onChange={(e) => form.setData('priority', e.target.value)} className="mt-1 w-full h-9 rounded-lg border border-input bg-transparent px-2 text-sm dark:bg-slate-900">
                                    <option value="normal">Normal</option>
                                    <option value="urgent">Urgent, we are stuck</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label htmlFor="body" className="text-sm font-medium">Tell us more</label>
                            <Textarea id="body" rows={5} value={form.data.body} maxLength={5000} onChange={(e) => form.setData('body', e.target.value)} placeholder="What happened, and what did you expect?" />
                            {form.errors.body && <p className="text-xs text-red-600 mt-1">{form.errors.body}</p>}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={form.processing || !form.data.subject.trim() || !form.data.body.trim()} className="bg-indigo-600 text-white hover:bg-indigo-700">Send request</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
