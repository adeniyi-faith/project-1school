import { Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Pill } from '@/components/app/kit';
import { StatusPill, Thread, when, type ThreadMessage } from '@/components/support/helpers';
import { useCan } from '@/lib/can';

interface Props {
    ticket: { id: number; subject: string; category: string; priority: string; status: string; by: string | null; created_at: string | null };
    messages: ThreadMessage[];
}

export default function SupportShow({ ticket, messages }: Props) {
    const can = useCan();
    const form = useForm({ body: '' });
    const send = (e: React.FormEvent) => { e.preventDefault(); form.post(`/school/support/${ticket.id}/reply`, { preserveScroll: true, onSuccess: () => form.reset() }); };

    return (
        <AppLayout title={ticket.subject}>
            <div className="space-y-5 max-w-3xl">
                <Link href="/school/support" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 dark:hover:text-slate-200"><ArrowLeft className="size-4" /> All requests</Link>
                <div>
                    <h1 className="text-xl font-semibold text-slate-900 dark:text-white break-words">{ticket.subject}</h1>
                    <div className="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                        <StatusPill status={ticket.status} school />
                        {ticket.priority === 'urgent' && <Pill tone="bad">Urgent</Pill>}
                        <span>{ticket.category} · started by {ticket.by ?? 'someone'} · {when(ticket.created_at)}</span>
                    </div>
                </div>

                <Thread messages={messages} />

                {can('support.create') && (ticket.status === 'closed'
                    ? <p className="text-sm text-slate-500">This request is closed. Write a reply below to open it again.</p> : null)}
                {can('support.create') && (
                    <form onSubmit={send} className="space-y-2">
                        <label htmlFor="reply" className="sr-only">Your reply</label>
                        <Textarea id="reply" rows={4} value={form.data.body} maxLength={5000} onChange={(e) => form.setData('body', e.target.value)} placeholder="Write a reply…" />
                        {form.errors.body && <p className="text-xs text-red-600">{form.errors.body}</p>}
                        <div className="flex flex-wrap justify-between gap-2">
                            {ticket.status !== 'closed'
                                ? <Button type="button" variant="outline" onClick={() => router.patch(`/school/support/${ticket.id}/close`)}>Close request</Button>
                                : <span />}
                            <Button type="submit" disabled={form.processing || !form.data.body.trim()} className="bg-indigo-600 text-white hover:bg-indigo-700">Send reply</Button>
                        </div>
                    </form>
                )}
            </div>
        </AppLayout>
    );
}
