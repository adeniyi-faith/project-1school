import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import {
    CreditCard, HardDrive, Mail, Clock, ListChecks, AlertTriangle, StickyNote, Pin, Trash2, Check, Circle, X, LifeBuoy,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { PageProps } from '@/Types';

export interface Overview {
    logins: {
        last_at: string | null; last_by: string | null; active_7days: number; never: number;
        admins: { name: string; email: string; last_login_at: string | null }[];
    };
    payments: { total: number; recent: { id: number; package: string; amount: number; method: string | null; date: string | null; status: string }[] };
    storage: { bytes: number };
    contact: { email: string | null; has_email: boolean; domain: string | null; messages_30d: number; failed_30d: number };
    checklist: { items: { key: string; label: string; done: boolean }[]; done: number; total: number };
    problems: { kind: string; text: string; at: string | null }[];
    notes: { id: number; body: string; pinned: boolean; author: string; at: string }[];
    support: { open: number; answered: number; latest: { id: number; subject: string; status: string; urgent: boolean }[] };
}

interface Props extends PageProps {
    school: { id: number; name: string; email: string | null; tags: string[] | null; account_manager_id: number | null };
    overview: Overview;
    managers: { id: number; name: string }[];
}

const naira = (n: number) => '₦' + n.toLocaleString('en-NG', { maximumFractionDigits: 2 });

function ago(iso: string | null): string {
    if (!iso) return 'Never';
    const mins = Math.floor((Date.now() - new Date(iso).getTime()) / 60000);
    if (mins < 1) return 'Just now';
    if (mins < 60) return `${mins} min ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs} hr ago`;
    const days = Math.floor(hrs / 24);
    return days < 31 ? `${days} day${days === 1 ? '' : 's'} ago` : new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function bytesText(b: number): string {
    if (b < 1024 * 1024) return `${Math.round(b / 1024)} KB`;
    if (b < 1024 ** 3) return `${(b / 1024 ** 2).toFixed(1)} MB`;
    return `${(b / 1024 ** 3).toFixed(2)} GB`;
}

export function Panel({ title, icon: Icon, children, className = '' }: { title: string; icon: React.ElementType; children: React.ReactNode; className?: string }) {
    return (
        <Card className={`dark:bg-slate-900 border-slate-200 dark:border-slate-800 ${className}`}>
            <CardHeader className="pb-3"><CardTitle className="text-sm flex items-center gap-2"><Icon className="w-4 h-4" /> {title}</CardTitle></CardHeader>
            <CardContent className="text-sm">{children}</CardContent>
        </Card>
    );
}

export function Bar({ value, max, warn = 0.85 }: { value: number; max: number; warn?: number }) {
    const pct = max > 0 ? Math.min(100, (value / max) * 100) : 0;
    const colour = max <= 0 || pct >= 100 ? 'bg-red-500' : pct >= warn * 100 ? 'bg-amber-500' : 'bg-indigo-500';
    return (
        <div className="h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden" role="progressbar" aria-valuenow={Math.round(pct)} aria-valuemin={0} aria-valuemax={100}>
            <div className={`h-full rounded-full ${colour}`} style={{ width: `${pct}%` }} />
        </div>
    );
}

export function Row({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="flex items-start justify-between gap-3 py-1.5">
            <span className="text-slate-500 dark:text-slate-400 shrink-0">{label}</span>
            <span className="text-right text-slate-800 dark:text-slate-200 min-w-0 break-words">{children}</span>
        </div>
    );
}

/** School 360: the facts below the control centre: payments, storage, email, sign-ins, setup, problems, support, tags, notes */
export default function SchoolOverviewPanels() {
    const { school, overview: o, managers } = usePage<Props>().props;
    const [tags, setTags] = useState<string[]>(school.tags ?? []);
    const [tagText, setTagText] = useState('');
    const [manager, setManager] = useState<string>(school.account_manager_id ? String(school.account_manager_id) : '');
    const [note, setNote] = useState('');
    const [pinNote, setPinNote] = useState(false);

    const addTag = () => {
        const t = tagText.trim().toLowerCase();
        if (t && !tags.includes(t) && tags.length < 10) setTags([...tags, t]);
        setTagText('');
    };
    const saveProfile = () => router.patch(`/super-admin/schools/${school.id}/profile`, { tags, account_manager_id: manager || null }, { preserveScroll: true });
    const profileChanged = JSON.stringify(tags) !== JSON.stringify(school.tags ?? []) || manager !== (school.account_manager_id ? String(school.account_manager_id) : '');
    const saveNote = () => {
        if (!note.trim()) return;
        router.post(`/super-admin/schools/${school.id}/notes`, { body: note, pinned: pinNote }, { preserveScroll: true, onSuccess: () => { setNote(''); setPinNote(false); } });
    };

    return (
        <section aria-label="School 360" className="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Panel title="Payments" icon={CreditCard}>
                <p className="text-lg font-semibold text-slate-900 dark:text-white">{naira(o.payments.total)} <span className="text-sm font-normal text-slate-500">paid in total</span></p>
                {o.payments.recent.length === 0 ? (
                    <p className="text-slate-500 mt-2">No subscription payments recorded.</p>
                ) : (
                    <ul className="mt-2 divide-y divide-slate-100 dark:divide-slate-800">
                        {o.payments.recent.map((p) => (
                            <li key={p.id} className="flex items-center justify-between gap-3 py-1.5">
                                <span className="min-w-0 truncate text-slate-600 dark:text-slate-300">{p.package} · {p.date ? new Date(p.date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'}</span>
                                <span className="font-medium text-slate-900 dark:text-white shrink-0">{naira(p.amount)}</span>
                            </li>
                        ))}
                    </ul>
                )}
            </Panel>

            <Panel title="Storage used" icon={HardDrive}>
                <span className="text-lg font-semibold text-slate-900 dark:text-white">{bytesText(o.storage.bytes)}</span>
                <p className="text-xs text-slate-500 mt-2">Counts uploaded student and staff documents. Photos and other files are not counted yet.</p>
            </Panel>

            <Panel title="Domain and email" icon={Mail}>
                <Row label="School email">{o.contact.email ?? <span className="text-amber-600">Not set</span>}</Row>
                <Row label="Own web address">{o.contact.domain ?? <span className="text-slate-500">Not connected (not built yet)</span>}</Row>
                <Row label="Messages, last 30 days">{o.contact.messages_30d}</Row>
                <Row label="Failed to send"><span className={o.contact.failed_30d > 0 ? 'text-red-600 dark:text-red-400 font-medium' : ''}>{o.contact.failed_30d}</span></Row>
            </Panel>

            <Panel title="Sign-ins" icon={Clock}>
                <Row label="Signed in, last 7 days">{o.logins.active_7days} {o.logins.active_7days === 1 ? 'person' : 'people'}</Row>
                <Row label="Never signed in">{o.logins.never}</Row>
                <Row label="Last sign-in by">{o.logins.last_by ?? '—'}</Row>
                {o.logins.admins.length > 0 && (
                    <div className="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <p className="text-xs text-slate-500 mb-1">School admins</p>
                        {o.logins.admins.map((a) => (
                            <div key={a.email} className="flex items-center justify-between gap-3 py-0.5">
                                <span className="min-w-0 truncate text-slate-700 dark:text-slate-300" title={a.email}>{a.name}</span>
                                <span className="text-xs text-slate-500 shrink-0">{ago(a.last_login_at)}</span>
                            </div>
                        ))}
                    </div>
                )}
            </Panel>

            <Panel title={`Setup checklist · ${o.checklist.done} of ${o.checklist.total}`} icon={ListChecks}>
                <Bar value={o.checklist.done} max={o.checklist.total} warn={2} />
                <ul className="mt-3 space-y-1.5">
                    {o.checklist.items.map((i) => (
                        <li key={i.key} className="flex items-start gap-2">
                            {i.done
                                ? <Check className="w-4 h-4 mt-0.5 shrink-0 text-emerald-500" aria-label="Done" />
                                : <Circle className="w-4 h-4 mt-0.5 shrink-0 text-slate-300 dark:text-slate-600" aria-label="Not done" />}
                            <span className={i.done ? 'text-slate-500' : 'text-slate-800 dark:text-slate-200'}>{i.label}</span>
                        </li>
                    ))}
                </ul>
            </Panel>

            <Panel title="Recent problems" icon={AlertTriangle}>
                {o.problems.length === 0 ? (
                    <p className="text-slate-500">Nothing has gone wrong lately.</p>
                ) : (
                    <ul className="space-y-2">
                        {o.problems.map((p, i) => (
                            <li key={i}>
                                <span className="inline-block text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400 mr-2">{p.kind}</span>
                                <span className="text-slate-700 dark:text-slate-300 break-words">{p.text}</span>
                                <span className="text-xs text-slate-400"> · {ago(p.at)}</span>
                            </li>
                        ))}
                    </ul>
                )}
                <p className="text-xs text-slate-500 mt-3">Shows failed AI requests, failed messages and online payments that need a look.</p>
            </Panel>

            <Panel title="Support requests" icon={LifeBuoy}>
                <Row label="Waiting for us"><span className={o.support.open > 0 ? 'text-amber-600 dark:text-amber-400 font-medium' : ''}>{o.support.open}</span></Row>
                <Row label="Waiting for the school">{o.support.answered}</Row>
                {o.support.latest.length > 0 && (
                    <ul className="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 space-y-1">
                        {o.support.latest.map((t) => (
                            <li key={t.id} className="text-sm truncate">
                                <Link href={`/super-admin/support/${t.id}`} className="text-indigo-600 hover:underline">{t.urgent && t.status === 'open' ? '! ' : ''}{t.subject}</Link>
                            </li>
                        ))}
                    </ul>
                )}
                <Link href="/super-admin/support" className="mt-2 inline-block text-xs text-indigo-600 underline">Open the inbox</Link>
            </Panel>

            <Panel title="Tags and account manager" icon={StickyNote}>
                <label className="text-xs text-slate-500" htmlFor="manager">Account manager</label>
                <select id="manager" value={manager} onChange={(e) => setManager(e.target.value)}
                    className="mt-1 mb-3 w-full h-9 rounded-lg border border-input bg-transparent px-2.5 text-sm dark:bg-slate-900">
                    <option value="">Nobody yet</option>
                    {managers.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                </select>
                <label className="text-xs text-slate-500" htmlFor="tag">Tags</label>
                <div className="flex flex-wrap gap-1.5 my-1.5">
                    {tags.map((t) => (
                        <span key={t} className="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">
                            {t}
                            <button type="button" onClick={() => setTags(tags.filter((x) => x !== t))} aria-label={`Remove tag ${t}`}><X className="w-3 h-3" /></button>
                        </span>
                    ))}
                    {tags.length === 0 && <span className="text-xs text-slate-400">No tags</span>}
                </div>
                <div className="flex gap-2">
                    <Input id="tag" value={tagText} placeholder="e.g. vip, slow-payer" maxLength={30} className="h-9"
                        onChange={(e) => setTagText(e.target.value)} onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); addTag(); } }} />
                    <Button type="button" size="sm" variant="secondary" onClick={addTag}>Add</Button>
                </div>
                <Button type="button" size="sm" className="mt-3 bg-indigo-600 hover:bg-indigo-700 text-white" disabled={!profileChanged} onClick={saveProfile}>Save</Button>
            </Panel>

            <Panel title="Internal notes" icon={StickyNote} className="md:col-span-2">
                <p className="text-xs text-slate-500 mb-2">Only the platform team can see these. The school never does.</p>
                <Textarea value={note} onChange={(e) => setNote(e.target.value)} maxLength={2000} placeholder="Write a note, for example what was agreed on a call…" rows={3} />
                <div className="flex flex-wrap items-center justify-between gap-2 mt-2">
                    <label className="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                        <input type="checkbox" checked={pinNote} onChange={(e) => setPinNote(e.target.checked)} /> Pin to top
                    </label>
                    <Button size="sm" className="bg-indigo-600 hover:bg-indigo-700 text-white" disabled={!note.trim()} onClick={saveNote}>Add note</Button>
                </div>
                <ul className="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                    {o.notes.length === 0 && <li className="text-slate-500 py-2">No notes yet.</li>}
                    {o.notes.map((n) => (
                        <li key={n.id} className="py-3 flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <p className="text-slate-800 dark:text-slate-200 whitespace-pre-wrap break-words">{n.pinned && <Pin className="inline w-3 h-3 mr-1 text-amber-500" aria-label="Pinned" />}{n.body}</p>
                                <p className="text-xs text-slate-400 mt-1">{n.author} · {ago(n.at)}</p>
                            </div>
                            <div className="flex gap-1 shrink-0">
                                <Button size="icon" variant="ghost" aria-label={n.pinned ? 'Unpin note' : 'Pin note'} onClick={() => router.patch(`/super-admin/schools/${school.id}/notes/${n.id}/pin`, {}, { preserveScroll: true })}><Pin className="w-4 h-4" /></Button>
                                <Button size="icon" variant="ghost" aria-label="Delete note" className="text-red-500" onClick={() => { if (confirm('Delete this note?')) router.delete(`/super-admin/schools/${school.id}/notes/${n.id}`, { preserveScroll: true }); }}><Trash2 className="w-4 h-4" /></Button>
                            </div>
                        </li>
                    ))}
                </ul>
            </Panel>
        </section>
    );
}
