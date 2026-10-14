import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import { Button, buttonVariants } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Panel, Pill } from '@/components/app/kit';
import { ErrorList, Switch, WebsiteLayout } from '@/components/website/kit';
import { ArrowDown, ArrowUp, Copy, ExternalLink, FileText, Home, Plus, Trash2 } from 'lucide-react';
import type { PageRow, PageTemplate, WebsiteShell } from '@/Types/website';

interface Props { shell: WebsiteShell; pages: PageRow[]; templates: PageTemplate[] }

export default function WebsitePages({ shell, pages, templates }: Props) {
    const [adding, setAdding] = useState(false);
    const [order, setOrder] = useState(pages.map(p => p.id));
    const add = useForm({ title: '', template: 'blank' });
    const rows = order.map(id => pages.find(p => p.id === id)).filter((p): p is PageRow => !!p);
    const orderChanged = order.join() !== pages.map(p => p.id).join();
    const canEdit = shell.canEdit;

    const move = (i: number, by: number) => {
        const next = [...order]; const j = i + by;
        if (j < 0 || j >= next.length) return;
        [next[i], next[j]] = [next[j], next[i]];
        setOrder(next);
    };
    const toggle = (p: PageRow, key: 'is_published' | 'show_in_menu', v: boolean) => router.patch(`/school/website/pages/${p.id}`, { [key]: v }, { preserveScroll: true });
    const pickTemplate = (t: PageTemplate) => add.setData(d => ({ ...d, template: t.key, title: d.title && templates.some(x => x.label === d.title) || !d.title ? (t.key === 'blank' ? '' : t.label) : d.title }));

    return (
        <WebsiteLayout shell={shell} title="Pages" crumb={[{ label: 'Pages' }]}
            description="The pages of your website, in the order they show in the menu. Each page is made of sections you fill in."
            actions={canEdit && <Button onClick={() => setAdding(true)} className="bg-indigo-600 text-white hover:bg-indigo-700"><Plus className="size-4" /> New page</Button>}>

            {orderChanged && (
                <div className="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                    <span>The menu order has changed.</span>
                    <div className="flex gap-2"><Button size="sm" variant="ghost" onClick={() => setOrder(pages.map(p => p.id))}>Undo</Button><Button size="sm" className="bg-indigo-600 text-white hover:bg-indigo-700" onClick={() => router.put('/school/website/pages/order', { ids: order }, { preserveScroll: true })}>Save order</Button></div>
                </div>
            )}

            <Panel flush>
                <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                    {rows.map((p, i) => (
                        <li key={p.id} className="flex flex-wrap items-center gap-3 px-4 py-3 sm:px-5">
                            {canEdit && (
                                <div className="flex flex-col">
                                    <button type="button" aria-label={`Move ${p.title} up`} disabled={i === 0} onClick={() => move(i, -1)} className="rounded p-0.5 text-slate-400 hover:text-slate-800 disabled:opacity-30"><ArrowUp className="size-4" /></button>
                                    <button type="button" aria-label={`Move ${p.title} down`} disabled={i === rows.length - 1} onClick={() => move(i, 1)} className="rounded p-0.5 text-slate-400 hover:text-slate-800 disabled:opacity-30"><ArrowDown className="size-4" /></button>
                                </div>
                            )}
                            <span className="grid size-10 shrink-0 place-items-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300">{p.is_home ? <Home className="size-5" /> : <FileText className="size-5" />}</span>
                            <Link href={`/school/website/pages/${p.id}`} className="min-w-0 flex-1">
                                <span className="flex flex-wrap items-center gap-2 font-medium">{p.title}{p.is_home && <Pill tone="good">Home page</Pill>}{!p.is_published && <Pill tone="neutral">Hidden</Pill>}</span>
                                <span className="block truncate text-xs text-slate-500">/{p.is_home ? '' : p.slug} · {p.sections} {p.sections === 1 ? 'section' : 'sections'}</span>
                            </Link>
                            <div className="flex w-full flex-wrap items-center justify-end gap-3 sm:w-auto">
                                {canEdit && !p.is_home && <label className="flex items-center gap-1.5 text-xs text-slate-500">Visible <Switch label={`${p.title} visible`} on={p.is_published} onChange={v => toggle(p, 'is_published', v)} /></label>}
                                {canEdit && <label className="flex items-center gap-1.5 text-xs text-slate-500">In menu <Switch label={`${p.title} in menu`} on={p.show_in_menu} onChange={v => toggle(p, 'show_in_menu', v)} /></label>}
                                <div className="flex gap-1">
                                    <Link href={`/school/website/pages/${p.id}`} className={buttonVariants({ size: 'sm', variant: 'outline' })}>{canEdit ? 'Edit' : 'View'}</Link>
                                    {p.is_published && <a href={p.url} target="_blank" rel="noreferrer" aria-label={`Open ${p.title} on the website`} className={buttonVariants({ size: 'icon-sm', variant: 'ghost' })}><ExternalLink className="size-4" /></a>}
                                    {canEdit && <Button size="icon-sm" variant="ghost" aria-label={`Copy ${p.title}`} onClick={() => router.post(`/school/website/pages/${p.id}/copy`)}><Copy className="size-4" /></Button>}
                                    {canEdit && !p.is_home && <Button size="icon-sm" variant="ghost" aria-label={`Delete ${p.title}`} onClick={() => { if (confirm(`Delete the page "${p.title}"? Visitors will no longer see it.`)) router.delete(`/school/website/pages/${p.id}`, { preserveScroll: true }); }}><Trash2 className="size-4 text-red-600" /></Button>}
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            </Panel>
            <p className="text-xs text-slate-500">News and events have their own page on your website, made for you from your stories. Manage them under News & events.</p>

            <Dialog open={adding} onOpenChange={setAdding}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader><DialogTitle>Make a new page</DialogTitle></DialogHeader>
                    <form id="new-page" className="space-y-4" onSubmit={e => { e.preventDefault(); add.post('/school/website/pages'); }}>
                        <div>
                            <p className="mb-2 text-sm font-medium">Start from</p>
                            <div className="grid gap-2 sm:grid-cols-2">
                                {templates.map(t => (
                                    <button key={t.key} type="button" onClick={() => pickTemplate(t)} aria-pressed={add.data.template === t.key}
                                        className={`rounded-lg border p-3 text-left text-sm ${add.data.template === t.key ? 'border-indigo-500 bg-indigo-50/60 ring-2 ring-indigo-500/20 dark:bg-indigo-500/10' : 'border-slate-200 hover:border-slate-300 dark:border-white/10'}`}>
                                        <span className="block font-semibold">{t.label}</span><span className="block text-xs text-slate-500">{t.help}</span>
                                    </button>
                                ))}
                            </div>
                        </div>
                        <label className="block space-y-1 text-sm"><span className="font-medium">Page name</span><Input autoFocus placeholder="For example: Our Teachers" maxLength={120} value={add.data.title} onChange={e => add.setData('title', e.target.value)} /></label>
                        <ErrorList errors={add.errors} />
                    </form>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setAdding(false)}>Cancel</Button>
                        <Button type="submit" form="new-page" disabled={add.processing || !add.data.title.trim()} className="bg-indigo-600 text-white hover:bg-indigo-700">{add.processing ? 'Making…' : 'Make the page'}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </WebsiteLayout>
    );
}
