import { Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ExternalLink } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Panel, Pill } from '@/components/app/kit';
import { StatusPill, Thread, when, type ThreadMessage } from '@/components/support/helpers';

interface Props {
    ticket: {
        id: number; subject: string; school: string | null; school_id: number; category: string; priority: string; status: string;
        by: string | null; requester_email: string | null; assigned_to: number | null; created_at: string | null;
    };
    messages: ThreadMessage[];
    team: { id: number; name: string }[];
}

export default function SupportTicket({ ticket, messages, team }: Props) {
    const form = useForm({ body: '', internal: false });
    const send = (e: React.FormEvent) => { e.preventDefault(); form.post(`/super-admin/support/${ticket.id}/reply`, { preserveScroll: true, onSuccess: () => form.reset() }); };
    const closed = ticket.status === 'closed';

    return (
        <AppLayout title={ticket.subject}>
            <div className="space-y-5">
                <Link href="/super-admin/support" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 dark:hover:text-slate-200"><ArrowLeft className="size-4" /> Support inbox</Link>
                <div className="flex flex-wrap items-center gap-2">
                    <h1 className="text-xl font-semibold text-slate-900 dark:text-white break-words">{ticket.subject}</h1>
                    <StatusPill status={ticket.status} />
                    {ticket.priority === 'urgent' && !closed && <Pill tone="bad">Urgent</Pill>}
                </div>

                <div className="grid gap-5 lg:grid-cols-3">
                    <div className="lg:col-span-2 space-y-4 min-w-0">
                        <Thread messages={messages} />
                        <form onSubmit={send} className="space-y-2">
                            <label htmlFor="reply" className="sr-only">Your reply</label>
                            <Textarea id="reply" rows={5} value={form.data.body} maxLength={5000} onChange={(e) => form.setData('body', e.target.value)}
                                placeholder={form.data.internal ? 'A private note for the team…' : 'Reply to the school…'} />
                            {form.errors.body && <p className="text-xs text-red-600">{form.errors.body}</p>}
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <label className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                    <input type="checkbox" checked={form.data.internal} onChange={(e) => form.setData('internal', e.target.checked)} /> Private note (the school cannot see it)
                                </label>
                                <Button type="submit" disabled={form.processing || !form.data.body.trim()} className="bg-indigo-600 text-white hover:bg-indigo-700">
                                    {form.data.internal ? 'Save note' : 'Send reply'}
                                </Button>
                            </div>
                        </form>
                    </div>

                    <aside className="space-y-4">
                        <Panel title="School">
                            <p className="text-sm font-medium text-slate-900 dark:text-white">{ticket.school}</p>
                            <p className="text-xs text-slate-500 mt-0.5">Asked by {ticket.by ?? 'someone'}{ticket.requester_email ? ` (${ticket.requester_email})` : ''}</p>
                            <p className="text-xs text-slate-500">{ticket.category} · {when(ticket.created_at)}</p>
                            <Link href={`/super-admin/schools/${ticket.school_id}`} className="mt-3 inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:underline">Open School 360 <ExternalLink className="size-3.5" /></Link>
                        </Panel>
                        <Panel title="Handling it">
                            <label htmlFor="assignee" className="text-xs text-slate-500">Assigned to</label>
                            <select id="assignee" value={ticket.assigned_to ?? ''} onChange={(e) => router.patch(`/super-admin/support/${ticket.id}/assign`, { assigned_to: e.target.value || null }, { preserveScroll: true })}
                                className="mt-1 w-full h-9 rounded-lg border border-input bg-transparent px-2 text-sm dark:bg-slate-900">
                                <option value="">Nobody</option>
                                {team.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                            </select>
                            <Button variant="outline" className="mt-3 w-full" onClick={() => router.patch(`/super-admin/support/${ticket.id}/status`, { status: closed ? 'open' : 'closed' }, { preserveScroll: true })}>
                                {closed ? 'Reopen request' : 'Close request'}
                            </Button>
                        </Panel>
                    </aside>
                </div>
            </div>
        </AppLayout>
    );
}
