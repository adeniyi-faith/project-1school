import { Head, router } from '@inertiajs/react';
import { Fragment, useState } from 'react';
import { ChevronDown, ChevronRight, Download, History, Search } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface Change { what: string; fields: Record<string, { from: string | null; to: string | null }> }
interface Line {
    id: number; at: string; who: string; email: string | null; role: string | null; action: string; description: string;
    school: string | null; status: number | null; ip: string | null; device: string; method: string | null; path: string | null;
    route: string | null; fields: string[]; changes: Change[];
}
interface Props {
    enabled: boolean;
    lines: { data: Line[]; meta: { total: number; current_page: number; last_page: number; from: number | null; to: number | null }; links: { prev: string | null; next: string | null } };
    filters: { user_id?: string; action?: string; school?: string; from?: string; to?: string; q?: string };
    actions: { key: string; label: string }[];
    people: { id: number; name: string }[];
}

const tone: Record<string, string> = {
    changed: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300',
    refused: 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-400',
    enter_school: 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
    leave_school: 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
    approval_requested: 'bg-violet-100 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300',
    viewed: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
};
const select = 'h-9 w-full rounded-lg border border-input bg-background px-2.5 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring';

const when = (iso: string) => new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });

export default function AuditIndex({ enabled, lines, filters, actions, people }: Props) {
    const [f, setF] = useState({ user_id: filters.user_id ?? '', action: filters.action ?? '', school: filters.school ?? '', from: filters.from ?? '', to: filters.to ?? '', q: filters.q ?? '' });
    const [open, setOpen] = useState<number | null>(null);
    const label = (key: string) => actions.find((a) => a.key === key)?.label ?? key;
    const clean = () => Object.fromEntries(Object.entries(f).filter(([, v]) => v !== ''));
    const apply = (e?: React.FormEvent) => { e?.preventDefault(); router.get('/super-admin/audit', clean(), { preserveState: true, replace: true }); };
    const exportUrl = '/super-admin/audit/export?' + new URLSearchParams(clean()).toString();

    return (
        <AppLayout breadcrumbs={[{ label: 'Super Admin' }, { label: 'Staff Activity' }]}>
            <Head title="Staff Activity" />

            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="text-xl font-bold text-slate-900 dark:text-white">Staff activity</h1>
                    <p className="mt-0.5 max-w-2xl text-sm text-slate-500 dark:text-slate-400">Who signed in, which school they entered, what they changed, when, and from where. Lines can never be edited or removed.</p>
                </div>
                <Button asChild variant="outline" className="gap-2"><a href={exportUrl}><Download className="size-4" /> Download as spreadsheet</a></Button>
            </div>

            {!enabled && (
                <div className="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                    Recording is switched off on this site (<code>PLATFORM_AUDIT=false</code>), so new actions are not written down. Older lines are still shown.
                </div>
            )}

            <form onSubmit={apply} className="mb-4 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4 dark:border-slate-800 dark:bg-slate-900">
                <label className="text-xs font-medium text-slate-500">Person
                    <select className={select + ' mt-1'} value={f.user_id} onChange={(e) => setF({ ...f, user_id: e.target.value })}>
                        <option value="">Everyone</option>
                        {people.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                    </select>
                </label>
                <label className="text-xs font-medium text-slate-500">What they did
                    <select className={select + ' mt-1'} value={f.action} onChange={(e) => setF({ ...f, action: e.target.value })}>
                        <option value="">Anything</option>
                        {actions.map((a) => <option key={a.key} value={a.key}>{a.label}</option>)}
                    </select>
                </label>
                <label className="text-xs font-medium text-slate-500">School
                    <Input className="mt-1 h-9" placeholder="Part of the school name" value={f.school} onChange={(e) => setF({ ...f, school: e.target.value })} />
                </label>
                <label className="text-xs font-medium text-slate-500">Words
                    <Input className="mt-1 h-9" placeholder="Name, page, IP address…" value={f.q} onChange={(e) => setF({ ...f, q: e.target.value })} />
                </label>
                <label className="text-xs font-medium text-slate-500">From
                    <Input type="date" className="mt-1 h-9" value={f.from} onChange={(e) => setF({ ...f, from: e.target.value })} />
                </label>
                <label className="text-xs font-medium text-slate-500">To
                    <Input type="date" className="mt-1 h-9" value={f.to} onChange={(e) => setF({ ...f, to: e.target.value })} />
                </label>
                <div className="flex items-end gap-2 sm:col-span-2">
                    <Button type="submit" className="gap-2"><Search className="size-4" /> Show</Button>
                    <Button type="button" variant="outline" onClick={() => { setF({ user_id: '', action: '', school: '', from: '', to: '', q: '' }); router.get('/super-admin/audit'); }}>Clear</Button>
                </div>
            </form>

            {lines.data.length === 0 ? (
                <div className="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900">
                    <History className="mx-auto size-8 text-slate-300" />
                    <p className="mt-3 text-sm font-medium text-slate-900 dark:text-white">Nothing to show</p>
                    <p className="mx-auto mt-1 max-w-sm text-sm text-slate-500">Actions by the platform team will appear here as they happen.</p>
                </div>
            ) : (
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                    <div className="hidden grid-cols-[11rem_12rem_1fr_11rem_10rem] gap-3 border-b border-slate-200 px-4 py-2.5 text-xs font-semibold text-slate-500 md:grid dark:border-slate-800">
                        <span>When</span><span>Who</span><span>What</span><span>School</span><span>From</span>
                    </div>
                    {lines.data.map((l) => {
                        const expandable = l.fields.length > 0 || l.changes.length > 0 || !!l.path;
                        return (
                            <Fragment key={l.id}>
                                <div className="grid gap-1 border-b border-slate-100 px-4 py-3 text-sm last:border-0 md:grid-cols-[11rem_12rem_1fr_11rem_10rem] md:gap-3 dark:border-slate-800">
                                    <span className="text-xs text-slate-500">{when(l.at)}</span>
                                    <span className="min-w-0"><span className="block truncate font-medium text-slate-900 dark:text-white">{l.who}</span><span className="block truncate text-xs text-slate-500">{l.role}</span></span>
                                    <span className="min-w-0">
                                        <span className={`mr-2 inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${tone[l.action] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'}`}>{label(l.action)}</span>
                                        <span className="break-words text-slate-700 dark:text-slate-300">{l.description}</span>
                                        {l.status !== null && l.status >= 400 && <span className="ml-2 text-xs font-medium text-red-600">Not done ({l.status})</span>}
                                        {expandable && (
                                            <button type="button" onClick={() => setOpen(open === l.id ? null : l.id)} className="ml-2 inline-flex items-center text-xs text-indigo-600 hover:underline" aria-expanded={open === l.id}>
                                                {open === l.id ? <ChevronDown className="size-3.5" /> : <ChevronRight className="size-3.5" />} Details
                                            </button>
                                        )}
                                    </span>
                                    <span className="truncate text-slate-600 dark:text-slate-400">{l.school ?? '—'}</span>
                                    <span className="min-w-0 text-xs text-slate-500"><span className="block truncate">{l.ip ?? '—'}</span><span className="block truncate">{l.device}</span></span>
                                </div>
                                {open === l.id && (
                                    <div className="space-y-2 border-b border-slate-100 bg-slate-50 px-4 py-3 text-xs text-slate-600 dark:border-slate-800 dark:bg-slate-950/40 dark:text-slate-400">
                                        {l.path && <p><span className="font-medium">Page:</span> {l.method} {l.path}</p>}
                                        {l.fields.length > 0 && <p><span className="font-medium">Fields sent:</span> {l.fields.join(', ')}</p>}
                                        {l.changes.map((c, i) => (
                                            <div key={i}>
                                                <p className="font-medium">{c.what}</p>
                                                {Object.entries(c.fields).map(([k, v]) => (
                                                    <p key={k} className="pl-3 break-words">{k}: <span className="text-slate-400 line-through">{v.from ?? '—'}</span> → <span className="text-slate-800 dark:text-slate-200">{v.to ?? '—'}</span></p>
                                                ))}
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </Fragment>
                        );
                    })}
                    <div className="flex items-center justify-between px-4 py-3 text-xs text-slate-500">
                        <span>{lines.meta.from}–{lines.meta.to} of {lines.meta.total}</span>
                        <span className="flex gap-1">
                            <Button size="sm" variant="outline" disabled={!lines.links.prev} onClick={() => lines.links.prev && router.get(lines.links.prev)}>Previous</Button>
                            <Button size="sm" variant="outline" disabled={!lines.links.next} onClick={() => lines.links.next && router.get(lines.links.next)}>Next</Button>
                        </span>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
