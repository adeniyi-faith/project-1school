import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import { Megaphone, Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { EmptyState, PageHeader, Panel, Pill } from '@/components/app/kit';

interface Announcement {
    id: number; title: string; body: string; kind: 'notice' | 'maintenance'; audience: 'all' | 'package' | 'schools'; audience_label: string;
    package_id: number | null; school_ids: number[]; show_banner: boolean; send_bell: boolean; send_email: boolean;
    starts_at: string | null; ends_at: string | null; status: 'draft' | 'published' | 'ended'; live: boolean; published_at: string | null;
    bell_count: number; email_count: number; email_failed: number;
}
interface Props {
    announcements: { data: Announcement[]; links: { url: string | null; label: string; active: boolean }[] };
    packages: { id: number; name: string }[];
    schools: { id: number; name: string }[];
}

const ZONE = 'Africa/Lagos';
const toLocal = (iso: string | null) => (iso ? new Date(iso).toLocaleString('sv-SE', { timeZone: ZONE }).replace(' ', 'T').slice(0, 16) : '');
const show = (iso: string | null) => (iso ? new Date(iso).toLocaleString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit', timeZone: ZONE }) : '');
const pageLabel = (label: string) => (/previous/i.test(label) ? 'Previous' : /next/i.test(label) ? 'Next' : label);

const blank = { title: '', body: '', kind: 'notice', audience: 'all', package_id: '', school_ids: [] as number[], show_banner: true, send_bell: false, send_email: false, starts_at: '', ends_at: '' };

export default function Announcements({ announcements, packages, schools }: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Announcement | null>(null);
    const [find, setFind] = useState('');
    const form = useForm<typeof blank & { publish: boolean }>({ ...blank, publish: false });

    const openNew = () => { setEditing(null); form.setData({ ...blank, publish: false }); form.clearErrors(); setOpen(true); };
    const openEdit = (a: Announcement) => {
        setEditing(a);
        form.setData({ title: a.title, body: a.body, kind: a.kind, audience: a.audience, package_id: a.package_id ? String(a.package_id) : '', school_ids: a.school_ids,
            show_banner: a.show_banner, send_bell: a.send_bell, send_email: a.send_email, starts_at: toLocal(a.starts_at), ends_at: toLocal(a.ends_at), publish: false });
        form.clearErrors(); setOpen(true);
    };
    const save = (publish: boolean) => {
        if (publish && !confirm('Publish now? Bell notices and emails cannot be taken back once sent.')) return;
        form.transform((d) => ({ ...d, publish }));
        const opts = { preserveScroll: true, onSuccess: () => setOpen(false) };
        if (editing) form.put(`/super-admin/announcements/${editing.id}`, opts); else form.post('/super-admin/announcements', opts);
    };
    const toggleSchool = (id: number) => form.setData('school_ids', form.data.school_ids.includes(id) ? form.data.school_ids.filter((x) => x !== id) : [...form.data.school_ids, id]);
    const add = <Button onClick={openNew} className="bg-indigo-600 text-white hover:bg-indigo-700"><Plus className="size-4" /> New announcement</Button>;
    const maintenance = form.data.kind === 'maintenance';
    const check = (key: 'show_banner' | 'send_bell' | 'send_email', label: string) => (
        <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.data[key]} onChange={(e) => form.setData(key, e.target.checked)} /> {label}</label>
    );
    const select = 'mt-1 w-full h-9 rounded-lg border border-input bg-transparent px-2 text-sm dark:bg-slate-900';
    const err = (k: string) => (form.errors as Record<string, string | undefined>)[k] && <p className="text-xs text-red-600 mt-1">{(form.errors as Record<string, string>)[k]}</p>;

    return (
        <AppLayout title="Announcements">
            <div className="space-y-5">
                <PageHeader title="Announcements" description="Tell schools something: a banner across their screens, a bell notice, an email, or all three. Use Planned maintenance to warn them before downtime."
                    actions={add} />
                <div className="md:hidden">{add}</div>

                <Panel flush>
                    {announcements.data.length === 0 ? (
                        <EmptyState icon={Megaphone} title="Nothing sent yet" text="Write your first announcement. It stays a draft until you publish it." />
                    ) : (
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {announcements.data.map((a) => (
                                <li key={a.id} className="px-4 py-4 sm:px-5">
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="text-sm font-medium text-slate-900 dark:text-white break-words">{a.title}</p>
                                            <p className="mt-0.5 text-sm text-slate-600 dark:text-slate-300 break-words line-clamp-2">{a.body}</p>
                                            <p className="mt-1.5 text-xs text-slate-500">
                                                {a.audience_label}
                                                {a.kind === 'maintenance' && a.starts_at && a.ends_at ? ` · work ${show(a.starts_at)} to ${show(a.ends_at)}` : ''}
                                                {a.kind === 'notice' && (a.starts_at || a.ends_at) ? ` · shows ${a.starts_at ? 'from ' + show(a.starts_at) : ''}${a.ends_at ? ' until ' + show(a.ends_at) : ''}` : ''}
                                            </p>
                                            <p className="mt-0.5 text-xs text-slate-500">
                                                {[a.show_banner && 'Banner', a.send_bell && (a.status === 'draft' ? 'Bell' : `Bell to ${a.bell_count}`), a.send_email && (a.status === 'draft' ? 'Email' : `Email to ${a.email_count}${a.email_failed ? `, ${a.email_failed} failed` : ''}`)].filter(Boolean).join(' · ')}
                                            </p>
                                        </div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            {a.kind === 'maintenance' && <Pill tone="warn">Maintenance</Pill>}
                                            <Pill tone={a.status === 'draft' ? 'neutral' : a.live ? 'good' : 'info'}>{a.status === 'draft' ? 'Draft' : a.live ? 'Live' : 'Finished'}</Pill>
                                        </div>
                                    </div>
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        {a.status === 'draft' && <>
                                            <Button size="sm" className="bg-indigo-600 text-white hover:bg-indigo-700" onClick={() => { if (confirm('Publish now? Bell notices and emails cannot be taken back once sent.')) router.post(`/super-admin/announcements/${a.id}/publish`, {}, { preserveScroll: true }); }}>Publish</Button>
                                            <Button size="sm" variant="outline" onClick={() => openEdit(a)}>Edit</Button>
                                        </>}
                                        {a.status === 'published' && a.live && <Button size="sm" variant="outline" onClick={() => { if (confirm('End this now? The banner disappears. Bell notices and emails already sent stay sent.')) router.patch(`/super-admin/announcements/${a.id}/end`, {}, { preserveScroll: true }); }}>End now</Button>}
                                        {a.status !== 'published' && <Button size="sm" variant="ghost" className="text-red-600" onClick={() => { if (confirm('Delete this announcement?')) router.delete(`/super-admin/announcements/${a.id}`, { preserveScroll: true }); }}>Delete</Button>}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </Panel>

                {announcements.links.length > 3 && (
                    <nav className="flex flex-wrap gap-1" aria-label="Pages">
                        {announcements.links.map((l, i) => l.url ? <Link key={i} href={l.url} preserveScroll className={`px-3 py-1.5 rounded-md text-sm ${l.active ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-white/[0.06]'}`}>{pageLabel(l.label)}</Link> : null)}
                    </nav>
                )}
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader><DialogTitle>{editing ? 'Edit draft' : 'New announcement'}</DialogTitle></DialogHeader>
                    <div className="space-y-3">
                        <div>
                            <label htmlFor="a-title" className="text-sm font-medium">Title</label>
                            <Input id="a-title" value={form.data.title} maxLength={150} onChange={(e) => form.setData('title', e.target.value)} />
                            {err('title')}
                        </div>
                        <div>
                            <label htmlFor="a-body" className="text-sm font-medium">Message</label>
                            <Textarea id="a-body" rows={4} value={form.data.body} maxLength={2000} onChange={(e) => form.setData('body', e.target.value)} />
                            {err('body')}
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label htmlFor="a-kind" className="text-sm font-medium">Kind</label>
                                <select id="a-kind" value={form.data.kind} onChange={(e) => form.setData('kind', e.target.value)} className={select}>
                                    <option value="notice">Notice</option>
                                    <option value="maintenance">Planned maintenance</option>
                                </select>
                            </div>
                            <div>
                                <label htmlFor="a-aud" className="text-sm font-medium">Who gets it</label>
                                <select id="a-aud" value={form.data.audience} onChange={(e) => form.setData('audience', e.target.value)} className={select}>
                                    <option value="all">Every school</option>
                                    <option value="package">Schools on one plan</option>
                                    <option value="schools">Chosen schools</option>
                                </select>
                            </div>
                        </div>
                        {form.data.audience === 'package' && (
                            <div>
                                <label htmlFor="a-pkg" className="text-sm font-medium">Plan</label>
                                <select id="a-pkg" value={form.data.package_id} onChange={(e) => form.setData('package_id', e.target.value)} className={select}>
                                    <option value="">Choose a plan</option>
                                    {packages.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                                </select>
                                {err('package_id')}
                            </div>
                        )}
                        {form.data.audience === 'schools' && (
                            <div>
                                <label htmlFor="a-find" className="text-sm font-medium">Schools ({form.data.school_ids.length} chosen)</label>
                                <Input id="a-find" value={find} placeholder="Find a school" onChange={(e) => setFind(e.target.value)} className="mt-1 h-9" />
                                <div className="mt-2 max-h-40 overflow-y-auto rounded-lg border border-slate-200 dark:border-white/10 p-2 space-y-1">
                                    {schools.filter((s) => s.name.toLowerCase().includes(find.toLowerCase())).map((s) => (
                                        <label key={s.id} className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.data.school_ids.includes(s.id)} onChange={() => toggleSchool(s.id)} /> {s.name}</label>
                                    ))}
                                </div>
                                {err('school_ids')}
                            </div>
                        )}
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label htmlFor="a-start" className="text-sm font-medium">{maintenance ? 'Work starts' : 'Show from (optional)'}</label>
                                <Input id="a-start" type="datetime-local" value={form.data.starts_at} onChange={(e) => form.setData('starts_at', e.target.value)} className="mt-1 h-9" />
                                {err('starts_at')}
                            </div>
                            <div>
                                <label htmlFor="a-end" className="text-sm font-medium">{maintenance ? 'Work ends' : 'Show until (optional)'}</label>
                                <Input id="a-end" type="datetime-local" value={form.data.ends_at} onChange={(e) => form.setData('ends_at', e.target.value)} className="mt-1 h-9" />
                                {err('ends_at')}
                            </div>
                        </div>
                        <p className="text-xs text-slate-500">Times are Nigerian time.{maintenance ? ' The banner shows from the moment you publish until the work ends.' : ''}</p>
                        <fieldset className="space-y-1.5">
                            <legend className="text-sm font-medium">How to send it</legend>
                            {check('show_banner', 'Banner across every page')}
                            {check('send_bell', 'Bell notice to school admins and principals')}
                            {check('send_email', 'Email to school admins and principals')}
                        </fieldset>
                    </div>
                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
                        <Button type="button" variant="secondary" disabled={form.processing || !form.data.title.trim() || !form.data.body.trim()} onClick={() => save(false)}>Save draft</Button>
                        <Button type="button" disabled={form.processing || !form.data.title.trim() || !form.data.body.trim()} onClick={() => save(true)} className="bg-indigo-600 text-white hover:bg-indigo-700">Publish now</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
