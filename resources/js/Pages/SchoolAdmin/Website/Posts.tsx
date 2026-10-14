import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Button, buttonVariants } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { EmptyState, Panel } from '@/components/app/kit';
import { StatusPill, WebsiteLayout, selectClass } from '@/components/website/kit';
import { CalendarDays, ExternalLink, Newspaper, Plus, Search, Star } from 'lucide-react';
import type { PostRow, WebsiteShell } from '@/Types/website';

interface Paged<T> { data: T[]; links: { url: string | null; label: string; active: boolean }[]; total: number; from: number | null; to: number | null }
interface Props {
    shell: WebsiteShell; posts: Paged<PostRow>; categories: Record<string, string>; newsUrl: string;
    filters: { status: string | null; category: string | null; q: string };
    counts: { all: number; draft: number; published: number; scheduled: number };
}

const fmt = (iso: string | null) => (iso ? new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '');

export default function WebsitePosts({ shell, posts, categories, filters, counts, newsUrl }: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const go = (patch: Partial<Props['filters']>) => router.get('/school/website/posts', Object.fromEntries(Object.entries({ ...filters, ...patch }).filter(([, v]) => v)), { preserveState: true, preserveScroll: true });
    const tabs = [
        { key: null, label: 'All', n: counts.all },
        { key: 'published', label: 'Live', n: counts.published },
        { key: 'scheduled', label: 'Scheduled', n: counts.scheduled },
        { key: 'draft', label: 'Drafts', n: counts.draft },
    ];

    return (
        <WebsiteLayout shell={shell} title="News & events" crumb={[{ label: 'News & events' }]}
            description={<>Stories, events and achievements. Published ones appear on your <a className="text-indigo-600 hover:underline" href={newsUrl} target="_blank" rel="noreferrer">news page</a>, in the Latest news section and in Google.</>}
            actions={shell.canEdit && <Link href="/school/website/posts/create" className={`${buttonVariants()} bg-indigo-600 text-white hover:bg-indigo-700`}><Plus className="size-4" /> New story</Link>}>

            <div className="flex flex-wrap items-center gap-2">
                <div className="flex flex-wrap gap-1 rounded-lg bg-slate-100 p-1 dark:bg-white/5">
                    {tabs.map(t => (
                        <button key={t.label} type="button" onClick={() => go({ status: t.key })} aria-pressed={(filters.status ?? null) === t.key}
                            className={`rounded-md px-3 py-1.5 text-sm font-medium ${(filters.status ?? null) === t.key ? 'bg-white shadow-sm dark:bg-slate-800' : 'text-slate-500 hover:text-slate-800'}`}>{t.label} <span className="text-xs text-slate-400">{t.n}</span></button>
                    ))}
                </div>
                <select aria-label="Kind" className={`${selectClass} w-auto`} value={filters.category ?? ''} onChange={e => go({ category: e.target.value || null })}>
                    <option value="">Every kind</option>
                    {Object.entries(categories).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                </select>
                <form className="flex min-w-[200px] flex-1 gap-2" onSubmit={e => { e.preventDefault(); go({ q }); }}>
                    <Input aria-label="Search stories" placeholder="Search by title" value={q} onChange={e => setQ(e.target.value)} />
                    <Button type="submit" variant="outline" size="icon" aria-label="Search"><Search className="size-4" /></Button>
                </form>
            </div>

            {posts.data.length === 0 ? (
                <Panel>
                    <EmptyState icon={Newspaper} title={counts.all ? 'Nothing matches' : 'No stories yet'} text={counts.all ? 'Try another filter.' : 'Share sports day results, a new lab, exam successes or the next open day. Fresh news helps parents and Google find you.'}
                        action={shell.canEdit && !counts.all ? <Link href="/school/website/posts/create" className={`${buttonVariants()} bg-indigo-600 text-white hover:bg-indigo-700`}><Plus className="size-4" /> Write your first story</Link> : undefined} />
                </Panel>
            ) : (
                <Panel flush>
                    <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                        {posts.data.map(p => (
                            <li key={p.id} className="flex items-center gap-3 px-4 py-3 sm:px-5">
                                <Link href={`/school/website/posts/${p.id}`} className="flex min-w-0 flex-1 items-center gap-3">
                                    {p.cover
                                        ? <img src={p.cover} alt="" loading="lazy" className="h-14 w-20 shrink-0 rounded-lg object-cover" />
                                        : <span className="grid h-14 w-20 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-400 dark:bg-white/5"><Newspaper className="size-5" /></span>}
                                    <span className="min-w-0">
                                        <span className="flex items-center gap-1.5 font-medium">{p.is_featured && <Star className="size-3.5 shrink-0 fill-amber-400 text-amber-400" aria-label="Featured" />}<span className="truncate">{p.title}</span></span>
                                        <span className="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                                            <StatusPill status={p.status} /><span>{p.categoryLabel}</span>
                                            {p.event_date && <span className="inline-flex items-center gap-1"><CalendarDays className="size-3" /> {fmt(p.event_date)}</span>}
                                            <span>{p.status === 'draft' ? `Edited ${fmt(p.updated_at)}` : fmt(p.published_at)}</span>
                                            {p.author && <span className="hidden sm:inline">by {p.author}</span>}
                                        </span>
                                    </span>
                                </Link>
                                {p.status === 'published' && <a href={p.url} target="_blank" rel="noreferrer" aria-label={`Open ${p.title} on the website`} className={buttonVariants({ size: 'icon-sm', variant: 'ghost' })}><ExternalLink className="size-4" /></a>}
                            </li>
                        ))}
                    </ul>
                    {posts.links.length > 3 && (
                        <div className="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 px-4 py-3 text-sm dark:border-white/[0.06]">
                            <span className="text-slate-500">{posts.from}–{posts.to} of {posts.total}</span>
                            <div className="flex flex-wrap gap-1">
                                {posts.links.map((l, i) => (
                                    <button key={i} type="button" disabled={!l.url} onClick={() => l.url && router.get(l.url, {}, { preserveScroll: true })}
                                        className={`min-w-8 rounded-md px-2 py-1 ${l.active ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100 disabled:opacity-40 dark:hover:bg-white/5'}`}>
                                        {l.label.replace('&laquo;', '‹').replace('&raquo;', '›').replace(' Previous', '').replace('Next ', '')}
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}
                </Panel>
            )}
        </WebsiteLayout>
    );
}
