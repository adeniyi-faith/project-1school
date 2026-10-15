import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import type { FormDataConvertible } from '@inertiajs/core';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Panel } from '@/components/app/kit';
import { PreviewOverlay } from '@/components/app/PreviewOverlay';
import { appendDeep, usePreview } from '@/lib/preview';
import { ErrorList, FieldInput, GooglePreview, SaveBar, Switch, WebsiteLayout } from '@/components/website/kit';
import { ArrowDown, ArrowLeft, ArrowUp, ChevronDown, Copy, Eye, EyeOff, Plus, Trash2 } from 'lucide-react';
import type { Block, BlockType, MediaItem, SitePage, WebsiteShell } from '@/Types/website';

interface Props {
    shell: WebsiteShell; page: SitePage; blockTypes: BlockType[]; media: MediaItem[];
    parentOptions: { id: number; title: string }[]; hasChildren: boolean;
    site: { tagline: string | null; theme: string };
    school: { name: string; phone: string | null; address: string | null };
}

function wordsIn(blocks: Block[]) {
    const text = blocks.filter(b => !b.hidden).map(b => [b.heading, b.subheading, b.body, b.intro].filter(Boolean).join(' ')).join(' ');
    return text.split(/\s+/).filter(Boolean).length;
}

function summary(b: Block) {
    const t = String(b.heading ?? '') || String(b.body ?? b.subheading ?? '');
    return t.length > 70 ? `${t.slice(0, 70)}…` : t;
}

export default function WebsitePageEdit({ shell, page, blockTypes, media, site, school, parentOptions, hasChildren }: Props) {
    const canEdit = shell.canEdit;
    const form = useForm({
        title: page.title, slug: page.slug, seo_title: page.seo_title ?? '', seo_description: page.seo_description ?? '',
        is_published: page.is_published, is_home: page.is_home, show_in_menu: page.show_in_menu, parent_id: page.parent_id ? String(page.parent_id) : '',
    });
    // the sections live beside the form (they are too deeply nested for the form helper's types) and are added when saving
    const [blocks, setBlocks] = useState<Block[]>(page.blocks ?? []);
    const [savedBlocks, setSavedBlocks] = useState(() => JSON.stringify(page.blocks ?? []));
    const dirty = form.isDirty || JSON.stringify(blocks) !== savedBlocks;
    const { state, show, close } = usePreview();
    const [open, setOpen] = useState<number | null>(page.blocks?.length ? 0 : null);
    const [adding, setAdding] = useState(false);
    const d = form.data;
    const typeOf = (t: string) => blockTypes.find(b => b.type === t);
    const patch = (i: number, p: Partial<Block>) => setBlocks(blocks.map((b, j) => (j === i ? { ...b, ...p } : b)));
    const move = (i: number, by: number) => {
        const next = [...blocks]; const j = i + by;
        if (j < 0 || j >= next.length) return;
        [next[i], next[j]] = [next[j], next[i]];
        setBlocks(next); setOpen(open === i ? j : open === j ? i : open);
    };
    const duplicate = (i: number) => { const next = [...blocks]; next.splice(i + 1, 0, structuredClone(blocks[i])); setBlocks(next); setOpen(i + 1); };

    function preview() {
        const fd = new FormData();
        fd.append('page_id', String(page.id));
        appendDeep(fd, 'page', { title: d.title, seo_title: d.seo_title, seo_description: d.seo_description, has_blocks: true, blocks });
        show('/school/website/preview', fd, `Preview: ${d.title}`);
    }
    const save = () => {
        form.transform(data => ({ ...data, blocks }));
        form.put(`/school/website/pages/${page.id}`, { preserveScroll: true, onSuccess: () => { form.setDefaults(); setSavedBlocks(JSON.stringify(blocks)); } });
    };

    // what Google will show
    const autoTitle = d.is_home ? `${school.name}${site.tagline ? ` – ${site.tagline}` : ''}` : `${d.title} | ${school.name}`;
    const title = d.seo_title.trim() || autoTitle;
    const firstText = blocks.find(b => !b.hidden && (b.body || b.subheading));
    const auto = String(firstText?.body ?? firstText?.subheading ?? '').replace(/[#*>_[\]()-]/g, '').replace(/\s+/g, ' ').trim().slice(0, 155);
    const description = d.seo_description.trim() || auto;
    const words = wordsIn(blocks);
    const checks = [
        { ok: title.length >= 25 && title.length <= 65, text: `Page title is ${title.length} characters`, tip: title.length < 25 ? 'A little short. Aim for 30 to 60 characters, with what the school is and where.' : 'Long titles get cut off in Google. Aim for 30 to 60 characters.' },
        { ok: description.length >= 70 && description.length <= 160, text: `Description is ${description.length} characters`, tip: 'Write one or two friendly sentences, 70 to 160 characters, that make a parent want to click.' },
        { ok: blocks.some(b => !b.hidden && b.heading), text: 'The page has a main heading', tip: 'Add a heading to your first section. Google uses it to understand the page.' },
        { ok: words >= 80, text: `${words} words of text`, tip: 'Pages with a little more real writing (80+ words) are easier to find.' },
        { ok: !!school.address && !!school.phone, text: 'School address and phone are filled in', tip: 'Add them in Settings. Parents and Google use them to find you.' },
    ];

    return (
        <WebsiteLayout shell={shell} wide title={d.title || 'Page'} crumb={[{ label: 'Pages', href: '/school/website/pages' }, { label: d.title }]}
            description={<Link href="/school/website/pages" className="inline-flex items-center gap-1 hover:text-slate-800"><ArrowLeft className="size-3.5" /> All pages</Link>}>
            <div className="grid gap-5 lg:grid-cols-[1fr_320px]">
                <div className="min-w-0 space-y-4">
                    <Panel title="Sections" description="Your page is built from sections, top to bottom. Tap one to change its words and pictures.">
                        <div className="space-y-2">
                            {blocks.length === 0 && <p className="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-white/15">This page is empty. Add your first section below.</p>}
                            {blocks.map((block, i) => {
                                const t = typeOf(block.type);
                                if (!t) return null;
                                const isOpen = open === i;
                                return (
                                    <div key={i} className={`rounded-xl border bg-white dark:bg-slate-900 ${isOpen ? 'border-indigo-300 shadow-sm dark:border-indigo-400/40' : 'border-slate-200 dark:border-white/10'} ${block.hidden ? 'opacity-60' : ''}`}>
                                        <div className="flex items-center gap-2 p-3">
                                            <button type="button" className="flex min-w-0 flex-1 items-center gap-3 text-left" aria-expanded={isOpen} onClick={() => setOpen(isOpen ? null : i)}>
                                                <span className="grid size-7 shrink-0 place-items-center rounded-md bg-slate-100 text-xs font-semibold text-slate-500 dark:bg-white/10">{i + 1}</span>
                                                <span className="min-w-0"><span className="block text-sm font-semibold">{t.label}{block.hidden && <span className="ml-2 text-xs font-normal text-slate-500">(hidden)</span>}</span><span className="block truncate text-xs text-slate-500">{summary(block) || t.help}</span></span>
                                                <ChevronDown className={`ml-auto size-4 shrink-0 text-slate-400 transition-transform ${isOpen ? 'rotate-180' : ''}`} />
                                            </button>
                                        </div>
                                        {isOpen && (
                                            <div className="space-y-4 border-t border-slate-100 p-4 dark:border-white/[0.06]">
                                                <p className="text-xs text-slate-500">{t.help}</p>
                                                {t.fields.map(f => <FieldInput key={f.key} field={f} disabled={!canEdit} media={media} value={block[f.key]} onChange={v => patch(i, { [f.key]: v as FormDataConvertible })} />)}
                                                {canEdit && (
                                                    <div className="flex flex-wrap gap-1 border-t border-slate-100 pt-3 dark:border-white/[0.06]">
                                                        <Button type="button" variant="ghost" size="sm" disabled={i === 0} onClick={() => move(i, -1)}><ArrowUp className="size-3.5" /> Move up</Button>
                                                        <Button type="button" variant="ghost" size="sm" disabled={i === blocks.length - 1} onClick={() => move(i, 1)}><ArrowDown className="size-3.5" /> Move down</Button>
                                                        <Button type="button" variant="ghost" size="sm" onClick={() => patch(i, { hidden: !block.hidden })}>{block.hidden ? <><Eye className="size-3.5" /> Show</> : <><EyeOff className="size-3.5" /> Hide</>}</Button>
                                                        <Button type="button" variant="ghost" size="sm" disabled={blocks.length >= 30} onClick={() => duplicate(i)}><Copy className="size-3.5" /> Copy</Button>
                                                        <Button type="button" variant="ghost" size="sm" className="text-red-600" onClick={() => { if (confirm('Remove this section from the page?')) { setBlocks(blocks.filter((_, j) => j !== i)); setOpen(null); } }}><Trash2 className="size-3.5" /> Remove</Button>
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>

                        {canEdit && (
                            <div className="mt-4">
                                {!adding ? (
                                    <Button type="button" variant="outline" onClick={() => setAdding(true)} disabled={blocks.length >= 30}><Plus className="size-4" /> Add a section</Button>
                                ) : (
                                    <div className="space-y-3 rounded-xl bg-slate-50 p-3 dark:bg-white/5">
                                        <div className="flex items-center justify-between"><p className="text-sm font-semibold">What would you like to add?</p><button type="button" className="text-sm text-slate-500 hover:underline" onClick={() => setAdding(false)}>Cancel</button></div>
                                        <div className="grid gap-2 sm:grid-cols-2">
                                            {blockTypes.map(t => (
                                                <button key={t.type} type="button" className="rounded-lg border border-slate-200 bg-white p-3 text-left hover:border-indigo-300 dark:border-white/10 dark:bg-slate-900"
                                                    onClick={() => { setBlocks([...blocks, { ...(t.default as Record<string, FormDataConvertible>), type: t.type, hidden: false }]); setOpen(blocks.length); setAdding(false); }}>
                                                    <span className="block text-sm font-semibold">{t.label}</span><span className="block text-xs text-slate-500">{t.help}</span>
                                                </button>
                                            ))}
                                            <div aria-disabled="true" className="rounded-lg border border-dashed border-slate-300 bg-slate-50/60 p-3 text-left opacity-80 dark:border-white/15 dark:bg-white/5">
                                                <span className="flex items-center gap-2 text-sm font-semibold">Video <span className="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800 dark:bg-amber-400/20 dark:text-amber-200">Coming soon</span></span>
                                                <span className="block text-xs text-slate-500">Play a school film or event video right on the page. We are still building this one.</span>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </Panel>
                </div>

                <aside className="min-w-0 space-y-4">
                    <Panel title="Page details">
                        <div className="space-y-3 text-sm">
                            <label className="block space-y-1"><span className="font-medium">Page name</span><Input disabled={!canEdit} maxLength={120} value={d.title} onChange={e => form.setData('title', e.target.value)} /></label>
                            <label className="block space-y-1"><span className="font-medium">Web address</span>
                                <div className="flex items-center overflow-hidden rounded-lg border border-input">
                                    <span className="shrink-0 bg-slate-50 px-2 py-2 text-xs text-slate-500 dark:bg-white/5">/</span>
                                    <input aria-label="Web address ending" disabled={!canEdit || d.is_home} className="min-w-0 flex-1 bg-transparent px-2 py-2 text-sm outline-none" value={d.is_home ? '(home page)' : d.slug} onChange={e => form.setData('slug', e.target.value.toLowerCase().replace(/[^a-z0-9_-]/g, '-'))} />
                                </div>
                            </label>
                            <label className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3 dark:border-white/10">Visible to visitors <Switch label="Visible to visitors" disabled={!canEdit || d.is_home} on={d.is_published || d.is_home} onChange={v => form.setData('is_published', v)} /></label>
                            <label className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3 dark:border-white/10">Show in the menu <Switch label="Show in the menu" disabled={!canEdit} on={d.show_in_menu} onChange={v => form.setData('show_in_menu', v)} /></label>
                            {d.show_in_menu && !d.is_home && !hasChildren && parentOptions.length > 0 && (
                                <label className="block space-y-1"><span className="font-medium">Show under</span>
                                    <select disabled={!canEdit} className="w-full rounded-lg border border-input bg-transparent px-2 py-2 text-sm" value={d.parent_id} onChange={e => form.setData('parent_id', e.target.value)}>
                                        <option value="">Nothing (top of the menu)</option>
                                        {parentOptions.map(o => <option key={o.id} value={o.id}>{o.title}</option>)}
                                    </select>
                                    <span className="block text-xs text-slate-500">Pick a page to make this one appear in a drop-down under it.</span>
                                </label>
                            )}
                            <label className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3 dark:border-white/10">Home page <Switch label="Home page" disabled={!canEdit || page.is_home} on={d.is_home} onChange={v => form.setData('is_home', v)} /></label>
                        </div>
                    </Panel>
                    <Panel title="Show up on Google" description="We fill this in for you. Change it if you like.">
                        <GooglePreview url={d.is_home ? shell.publicUrl : `${shell.publicUrl}/${d.slug}`} title={title} description={description} checks={checks}>
                            <label className="block space-y-1 text-sm"><span className="font-medium">Title in Google <span className="font-normal text-slate-500">({title.length}/60)</span></span>
                                <Input disabled={!canEdit} maxLength={80} placeholder={autoTitle} value={d.seo_title} onChange={e => form.setData('seo_title', e.target.value)} /></label>
                            <label className="block space-y-1 text-sm"><span className="font-medium">Description in Google <span className="font-normal text-slate-500">({description.length}/160)</span></span>
                                <Textarea disabled={!canEdit} maxLength={200} placeholder={auto || 'One or two sentences about this page'} value={d.seo_description} onChange={e => form.setData('seo_description', e.target.value)} /></label>
                        </GooglePreview>
                    </Panel>
                </aside>
            </div>

            <ErrorList errors={form.errors} />
            <SaveBar dirty={dirty}>
                <Button type="button" variant="outline" onClick={preview}><Eye className="size-4" /> Preview</Button>
                {canEdit && <Button type="button" disabled={form.processing || !dirty} onClick={save} className="bg-indigo-600 text-white hover:bg-indigo-700">{form.processing ? 'Saving…' : 'Save page'}</Button>}
            </SaveBar>
            {canEdit && !page.is_home && <div className="flex justify-end"><button type="button" className="text-sm text-red-600 hover:underline" onClick={() => { if (confirm(`Delete the page "${page.title}"?`)) router.delete(`/school/website/pages/${page.id}`); }}>Delete this page</button></div>}
            <PreviewOverlay state={state} onClose={close} />
        </WebsiteLayout>
    );
}
