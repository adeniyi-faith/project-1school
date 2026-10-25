import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { EmptyState, PageHeader, Panel, Pill, Segmented } from '@/components/app/kit';
import { MessageSquare } from 'lucide-react';

interface Child { id: number; full_name: string; class: string | null; has_teacher: boolean; }
interface Sent { id: number; subject: string | null; body: string; to: string | null; when: string | null; }
interface Reply { id: number; subject: string | null; body: string; from: string | null; when: string | null; unread: boolean; }
interface Props { linked: boolean; children: Child[]; sent: Sent[]; replies: Reply[]; }

const selectClass = 'h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm dark:border-white/10 dark:bg-slate-900';

function today() { return new Date().toISOString().slice(0, 10); }

function MessageForm({ children }: { children: Child[] }) {
    const form = useForm({ student_id: String(children[0]?.id ?? ''), to: 'office', subject: '', body: '' });
    const child = children.find(c => String(c.id) === form.data.student_id);

    return (
        <form
            onSubmit={e => { e.preventDefault(); form.post('/school/parent/messages', { preserveScroll: true, onSuccess: () => form.reset('subject', 'body') }); }}
            className="space-y-4"
        >
            {children.length > 1 && (
                <div>
                    <Label htmlFor="msg-child">About which child?</Label>
                    <select id="msg-child" className={selectClass} value={form.data.student_id} onChange={e => form.setData('student_id', e.target.value)}>
                        {children.map(c => <option key={c.id} value={c.id}>{c.full_name}{c.class ? ` (${c.class})` : ''}</option>)}
                    </select>
                </div>
            )}
            <div>
                <Label htmlFor="msg-to">Send to</Label>
                <select id="msg-to" className={selectClass} value={form.data.to} onChange={e => form.setData('to', e.target.value)}>
                    <option value="office">The school office</option>
                    <option value="teacher" disabled={child ? !child.has_teacher : false}>
                        {child?.full_name ? `${child.full_name}'s class teacher` : 'Class teacher'}{child && !child.has_teacher ? ' (none set yet)' : ''}
                    </option>
                </select>
                {form.errors.to && <p className="mt-1 text-xs text-red-600">{form.errors.to}</p>}
            </div>
            <div>
                <Label htmlFor="msg-subject">Subject (optional)</Label>
                <Input id="msg-subject" value={form.data.subject} onChange={e => form.setData('subject', e.target.value)} maxLength={150} />
            </div>
            <div>
                <Label htmlFor="msg-body">Your message</Label>
                <Textarea id="msg-body" rows={5} value={form.data.body} onChange={e => form.setData('body', e.target.value)} maxLength={3000} />
                {form.errors.body && <p className="mt-1 text-xs text-red-600">{form.errors.body}</p>}
            </div>
            <Button type="submit" disabled={form.processing || !form.data.body.trim()} className="min-h-11">Send message</Button>
        </form>
    );
}

function AbsentForm({ children }: { children: Child[] }) {
    const form = useForm({ student_id: String(children[0]?.id ?? ''), date: today(), reason: '' });

    return (
        <form
            onSubmit={e => { e.preventDefault(); form.post('/school/parent/absent', { preserveScroll: true, onSuccess: () => form.reset('reason') }); }}
            className="space-y-4"
        >
            {children.length > 1 && (
                <div>
                    <Label htmlFor="abs-child">Which child?</Label>
                    <select id="abs-child" className={selectClass} value={form.data.student_id} onChange={e => form.setData('student_id', e.target.value)}>
                        {children.map(c => <option key={c.id} value={c.id}>{c.full_name}{c.class ? ` (${c.class})` : ''}</option>)}
                    </select>
                </div>
            )}
            <div>
                <Label htmlFor="abs-date">Day of absence</Label>
                <Input id="abs-date" type="date" min={today()} value={form.data.date} onChange={e => form.setData('date', e.target.value)} />
                {form.errors.date && <p className="mt-1 text-xs text-red-600">{form.errors.date}</p>}
            </div>
            <div>
                <Label htmlFor="abs-reason">Reason (optional)</Label>
                <Textarea id="abs-reason" rows={3} value={form.data.reason} onChange={e => form.setData('reason', e.target.value)} maxLength={1000} />
            </div>
            <Button type="submit" disabled={form.processing || !form.data.date} className="min-h-11">Tell the school</Button>
            <p className="text-xs text-slate-500">This is a note only. It does not change the attendance register.</p>
        </form>
    );
}

export default function ParentMessages({ linked, children, sent, replies }: Props) {
    const [tab, setTab] = useState<'write' | 'absent'>('write');

    if (!linked) {
        return (
            <AppLayout title="Message the School">
                <Panel><EmptyState icon={MessageSquare} title="Account not linked" text="Your account hasn't been linked to a guardian record yet. Please contact the school office." /></Panel>
            </AppLayout>
        );
    }

    return (
        <AppLayout title="Message the School">
            <div className="space-y-6">
                <PageHeader title="Message the school" description="Write to the office or your child's class teacher, or let the school know your child will be absent." />

                <Panel>
                    <div className="space-y-4">
                        <Segmented label="What do you want to do?" value={tab} onChange={setTab} options={[{ value: 'write', label: 'Write a message' }, { value: 'absent', label: 'My child will be absent' }]} />
                        {children.length === 0
                            ? <p className="text-sm text-slate-500">No children are linked to your account yet.</p>
                            : tab === 'write' ? <MessageForm children={children} /> : <AbsentForm children={children} />}
                    </div>
                </Panel>

                <Panel title="Replies from the school" flush>
                    {replies.length === 0
                        ? <p className="px-5 py-8 text-center text-sm text-slate-500">No replies yet.</p>
                        : <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {replies.map(m => (
                                <li key={m.id} className="px-5 py-3.5">
                                    <div className="flex items-center gap-2">
                                        <p className="text-sm font-medium text-slate-900 dark:text-white">{m.subject || 'Message'}</p>
                                        {m.unread && <Pill tone="info">New</Pill>}
                                    </div>
                                    <p className="mt-0.5 whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{m.body}</p>
                                    <p className="mt-1 text-xs text-slate-400">From {m.from} · {m.when}</p>
                                </li>
                            ))}
                        </ul>}
                </Panel>

                {sent.length > 0 && (
                    <Panel title="What you sent" flush>
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {sent.map(m => (
                                <li key={m.id} className="px-5 py-3.5">
                                    <p className="text-sm font-medium text-slate-900 dark:text-white">{m.subject || 'Message'}</p>
                                    <p className="mt-0.5 whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{m.body}</p>
                                    <p className="mt-1 text-xs text-slate-400">To {m.to} · {m.when}</p>
                                </li>
                            ))}
                        </ul>
                    </Panel>
                )}
            </div>
        </AppLayout>
    );
}
