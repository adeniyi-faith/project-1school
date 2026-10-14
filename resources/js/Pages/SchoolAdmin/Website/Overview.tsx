import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Panel } from '@/components/app/kit';
import { StatusPill, Switch, WebsiteLayout } from '@/components/website/kit';
import { ScaledSite } from '@/components/website/ScaledSite';
import { CheckCircle2, Circle, FileText, Images, Newspaper, Palette, Plus } from 'lucide-react';
import type { WebsiteShell } from '@/Types/website';

interface Props {
    shell: WebsiteShell; theme: string; homeId: number | null; sitemapUrl: string;
    counts: { pages: number; published: number; drafts: number; media: number };
    recent: { id: number; title: string; status: string; category: string; updated_at: string }[];
    checklist: { key: string; done: boolean; label: string; href: string }[];
}

const tile = 'flex items-center gap-3 rounded-lg border border-slate-200 p-3 text-sm hover:border-indigo-300 dark:border-white/10';

export default function WebsiteOverview({ shell, theme, counts, recent, checklist, homeId }: Props) {
    const [copied, setCopied] = useState(false);
    const done = checklist.filter(c => c.done).length;

    return (
        <WebsiteLayout shell={shell} title="Your website" description="Everything about your school's public website in one place: pages, news, look and pictures.">
            <div className="grid gap-5 lg:grid-cols-[1fr_340px]">
                <div className="min-w-0 space-y-5">
                    <Panel>
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div className="min-w-0 space-y-1">
                                <p className="text-sm font-semibold">{shell.enabled ? 'Your website is live' : 'Your website is off'}</p>
                                <p className="flex flex-wrap items-center gap-x-2 text-sm">
                                    <a className="break-all text-indigo-600 hover:underline" href={shell.publicUrl} target="_blank" rel="noreferrer">{shell.publicUrl}</a>
                                    <button type="button" className="text-xs text-slate-500 underline" onClick={() => { navigator.clipboard?.writeText(shell.publicUrl); setCopied(true); setTimeout(() => setCopied(false), 1500); }}>{copied ? 'Copied' : 'Copy link'}</button>
                                </p>
                                <p className="text-xs text-slate-500">{shell.enabled ? 'Anyone with the link, and Google, can see it.' : 'Visitors see "not found" until you switch it on.'}</p>
                            </div>
                            {shell.canEdit && <label className="flex items-center gap-2 text-sm font-medium">{shell.enabled ? 'On' : 'Off'} <Switch label="Website is on" on={shell.enabled} onChange={v => router.put('/school/website', { enabled: v }, { preserveScroll: true })} /></label>}
                        </div>
                    </Panel>

                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        {[
                            { href: '/school/website/pages', icon: FileText, n: counts.pages, label: 'Pages' },
                            { href: '/school/website/posts?status=published', icon: Newspaper, n: counts.published, label: 'Stories live' },
                            { href: '/school/website/posts?status=draft', icon: Newspaper, n: counts.drafts, label: 'Drafts' },
                            { href: '/school/website/media', icon: Images, n: counts.media, label: 'Pictures' },
                        ].map(c => (
                            <Link key={c.label} href={c.href} className="rounded-xl border border-slate-200 bg-white p-4 hover:border-indigo-300 dark:border-white/10 dark:bg-slate-900">
                                <c.icon className="size-4 text-indigo-500" />
                                <p className="mt-2 text-2xl font-semibold tabular-nums">{c.n}</p>
                                <p className="text-xs text-slate-500">{c.label}</p>
                            </Link>
                        ))}
                    </div>

                    <Panel title="Quick actions">
                        <div className="grid gap-2 sm:grid-cols-2">
                            {shell.canEdit && <Link href="/school/website/posts/create" className={tile}><Plus className="size-4 text-indigo-500" /> Write a news story</Link>}
                            {shell.canEdit && <Link href="/school/website/posts/create?category=events" className={tile}><Plus className="size-4 text-indigo-500" /> Add an event</Link>}
                            {homeId && <Link href={`/school/website/pages/${homeId}`} className={tile}><FileText className="size-4 text-indigo-500" /> Edit the home page</Link>}
                            <Link href="/school/website/themes" className={tile}><Palette className="size-4 text-indigo-500" /> Change the look (now {theme})</Link>
                        </div>
                    </Panel>

                    <Panel title="Latest stories" action={<Link href="/school/website/posts" className="text-indigo-600 hover:underline">See all</Link>} flush>
                        {recent.length === 0 ? (
                            <p className="px-5 pb-5 text-sm text-slate-500">No stories yet. News keeps your website fresh and helps Google find you.</p>
                        ) : (
                            <ul className="divide-y divide-slate-100 text-sm dark:divide-white/[0.06]">
                                {recent.map(p => (
                                    <li key={p.id}><Link href={`/school/website/posts/${p.id}`} className="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50 dark:hover:bg-white/5"><span className="min-w-0 truncate font-medium">{p.title}</span><StatusPill status={p.status} /></Link></li>
                                ))}
                            </ul>
                        )}
                    </Panel>
                </div>

                <div className="min-w-0 space-y-5">
                    <Panel title="Get your website ready" description={`${done} of ${checklist.length} done`}>
                        <div className="mb-3 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10"><div className="h-full rounded-full bg-emerald-500 transition-all" style={{ width: `${(done / checklist.length) * 100}%` }} /></div>
                        <ul className="space-y-1">
                            {checklist.map(c => (
                                <li key={c.key}>
                                    <Link href={c.href} className="flex items-center gap-2.5 rounded-lg px-2 py-2 text-sm hover:bg-slate-50 dark:hover:bg-white/5">
                                        {c.done ? <CheckCircle2 className="size-4 shrink-0 text-emerald-500" /> : <Circle className="size-4 shrink-0 text-slate-300" />}
                                        <span className={c.done ? 'text-slate-400 line-through' : ''}>{c.label}</span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </Panel>
                    <Panel title="How it looks now">
                        <ScaledSite src="/school/website/themes/current/sample" title="Your home page" width={1280} height={300} />
                    </Panel>
                </div>
            </div>
        </WebsiteLayout>
    );
}
