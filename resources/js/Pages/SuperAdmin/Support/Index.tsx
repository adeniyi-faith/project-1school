import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { LifeBuoy, Search } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { EmptyState, PageHeader, Panel, Pill, Segmented } from '@/components/app/kit';
import { StatusPill, when } from '@/components/support/helpers';

interface Ticket {
    id: number; subject: string; school: string | null; school_id: number; category: string; priority: string; status: string;
    by: string | null; assignee: string | null; last_reply_at: string | null;
}
interface Props {
    tickets: { data: Ticket[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: { status: string; assigned: string; search: string };
    counts: { open: number; answered: number; closed: number };
}

const pageLabel = (label: string) => (/previous/i.test(label) ? 'Previous' : /next/i.test(label) ? 'Next' : label);

export default function SupportInbox({ tickets, filters, counts }: Props) {
    const [search, setSearch] = useState(filters.search);
    const go = (extra: Record<string, string>) => router.get('/super-admin/support', { ...filters, ...extra }, { preserveState: true, replace: true });

    return (
        <AppLayout title="Support inbox">
            <div className="space-y-5">
                <PageHeader title="Support inbox" description="Requests schools send from their dashboard. Urgent ones waiting for us are at the top." />

                <div className="flex flex-wrap items-center gap-3">
                    <Segmented label="Status" value={filters.status} onChange={(v) => go({ status: v })} options={[
                        { value: 'active', label: 'Not closed', count: counts.open + counts.answered },
                        { value: 'open', label: 'Needs a reply', count: counts.open },
                        { value: 'answered', label: 'Waiting for school', count: counts.answered },
                        { value: 'closed', label: 'Closed', count: counts.closed },
                    ]} />
                    <Segmented label="Assigned to" value={filters.assigned} onChange={(v) => go({ assigned: v })} options={[
                        { value: 'all', label: 'Anyone' }, { value: 'me', label: 'Me' }, { value: 'none', label: 'Nobody' },
                    ]} />
                    <form onSubmit={(e) => { e.preventDefault(); go({ search }); }} className="flex items-center gap-2 grow sm:grow-0">
                        <div className="relative grow">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-slate-400" />
                            <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Subject or school" className="pl-9 h-9 sm:w-60" aria-label="Search requests" />
                        </div>
                        <Button type="submit" size="sm" variant="secondary">Search</Button>
                    </form>
                </div>

                <Panel flush>
                    {tickets.data.length === 0 ? (
                        <EmptyState icon={LifeBuoy} title="Nothing here" text="No requests match these filters." />
                    ) : (
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {tickets.data.map((t) => (
                                <li key={t.id}>
                                    <Link href={`/super-admin/support/${t.id}`} className="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-3.5 hover:bg-slate-50 dark:hover:bg-white/[0.03] sm:px-5">
                                        <span className="min-w-0">
                                            <span className="block truncate text-sm font-medium text-slate-900 dark:text-white">{t.subject}</span>
                                            <span className="block text-xs text-slate-500">{t.school} · {t.category} · {when(t.last_reply_at)} · {t.assignee ? `with ${t.assignee}` : 'not assigned'}</span>
                                        </span>
                                        <span className="flex items-center gap-2">
                                            {t.priority === 'urgent' && t.status !== 'closed' && <Pill tone="bad">Urgent</Pill>}
                                            <StatusPill status={t.status} />
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </Panel>

                {tickets.links.length > 3 && (
                    <nav className="flex flex-wrap gap-1" aria-label="Pages">
                        {tickets.links.map((l, i) => l.url
                            ? <Link key={i} href={l.url} preserveState className={`px-3 py-1.5 rounded-md text-sm ${l.active ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-white/[0.06]'}`}>{pageLabel(l.label)}</Link>
                            : null)}
                    </nav>
                )}
            </div>
        </AppLayout>
    );
}
