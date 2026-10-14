import { useMemo, useRef, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { PageHeader, Panel, Pill, Segmented } from '@/components/app/kit';
import { PreviewOverlay } from '@/components/app/PreviewOverlay';
import { appendDeep, usePreview } from '@/lib/preview';
import { ArrowDown, ArrowLeft, ArrowUp, Check, ChevronDown, ExternalLink, Eye, ImagePlus, Plus, Trash2, X } from 'lucide-react';

/* ---------- types ---------- */
interface Field { key: string; label: string; kind: 'text' | 'textarea' | 'select' | 'number' | 'image' | 'list'; options?: Record<string, string>; min?: number; max?: number; limit?: number; noun?: string; hint?: string; fields?: Field[] }
interface BlockType { type: string; label: string; help: string; fields: Field[]; default: Record<string, unknown> }
type Block = { type: string; hidden?: boolean } & Record<string, unknown>;
interface Page { id: number; slug: string; title: string; seo_title: string | null; seo_description: string | null; blocks: Block[] | null; is_published: boolean; is_home: boolean; show_in_menu: boolean }
interface Theme { key: string; label: string; note: string; brand: string; brand2: string; bg: string; soft: string; serif: boolean }
interface Site { enabled: boolean; theme: string; color: string | null; color2: string | null; tagline: string | null; meta_description: string | null; footer_text: string | null; social: Record<string, string> }
interface Props {
    site: Site; pages: Page[]; themes: Theme[]; socialNames: Record<string, string>; blockTypes: BlockType[]; publicUrl: string; canEdit: boolean;
    school: { name: string; phone: string | null; email: string | null; address: string | null; logo: boolean };
}

const selectClass = 'h-10 w-full rounded-lg border border-input bg-transparent px-2.5 text-sm dark:bg-input/30';
const csrf = () => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

async function uploadImage(file: File): Promise<string> {
    const body = new FormData();
    body.append('image', file);
    const res = await fetch('/school/website/images', { method: 'POST', body, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' } });
    if (!res.ok) throw new Error(res.status === 422 ? 'That file is not a picture we can use. Try a JPG, PNG or WebP under 10 MB.' : 'The picture could not be uploaded. Please try again.');
    return (await res.json()).path as string;
}

/* ---------- small pieces ---------- */
function Switch({ on, onChange, label, disabled }: { on: boolean; onChange: (v: boolean) => void; label: string; disabled?: boolean }) {
    return (
        <button type="button" role="switch" aria-checked={on} aria-label={label} disabled={disabled} onClick={() => onChange(!on)}
            className={`relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors disabled:opacity-50 ${on ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-white/20'}`}>
            <span className={`inline-block size-5 rounded-full bg-white shadow transition-transform ${on ? 'translate-x-5' : 'translate-x-0.5'}`} />
        </button>
    );
}

function ImagePicker({ value, onChange, disabled, label }: { value: string; onChange: (path: string) => void; disabled: boolean; label: string }) {
    const input = useRef<HTMLInputElement>(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');

    async function pick(file?: File) {
        if (!file) return;
        setBusy(true); setError('');
        try { onChange(await uploadImage(file)); } catch (e) { setError((e as Error).message); } finally { setBusy(false); if (input.current) input.current.value = ''; }
    }

    return (
        <div className="space-y-1.5">
            <div className="flex items-center gap-3">
                {value ? <img src={`/storage/${value}`} alt="" className="h-16 w-24 rounded-lg border border-slate-200 object-cover dark:border-white/10" /> : <div className="grid h-16 w-24 place-items-center rounded-lg border border-dashed border-slate-300 text-slate-400 dark:border-white/20"><ImagePlus className="size-5" /></div>}
                {!disabled && (
                    <div className="flex flex-wrap gap-2">
                        <Button type="button" variant="outline" size="sm" disabled={busy} onClick={() => input.current?.click()}>{busy ? 'Uploading…' : value ? 'Change picture' : 'Choose a picture'}</Button>
                        {value && <Button type="button" variant="ghost" size="sm" onClick={() => onChange('')}><X className="size-3.5" /> Remove</Button>}
                    </div>
                )}
                <input ref={input} type="file" accept="image/png,image/jpeg,image/webp" className="sr-only" aria-label={label} onChange={e => pick(e.target.files?.[0])} />
            </div>
            {error && <p className="text-xs text-red-600">{error}</p>}
        </div>
    );
}

function FieldInput({ field, value, onChange, disabled }: { field: Field; value: unknown; onChange: (v: unknown) => void; disabled: boolean }) {
    if (field.kind === 'list') {
        const rows = (value as Record<string, unknown>[]) ?? [];
        const noun = field.noun ?? 'item';
        return (
            <div className="space-y-2">
                <p className="text-sm font-medium text-slate-700 dark:text-slate-300">{field.label}</p>
                {rows.map((row, i) => (
                    <div key={i} className="space-y-3 rounded-lg border border-slate-200 p-3 dark:border-white/10">
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{noun} {i + 1}</p>
                        {field.fields!.map(sub => (
                            <FieldInput key={sub.key} field={sub} disabled={disabled} value={row[sub.key]} onChange={v => onChange(rows.map((r, j) => (j === i ? { ...r, [sub.key]: v } : r)))} />
                        ))}
                        {!disabled && <button type="button" className="text-xs text-red-600 hover:underline" onClick={() => onChange(rows.filter((_, j) => j !== i))}>Remove this {noun}</button>}
                    </div>
                ))}
                {!disabled && rows.length < (field.limit ?? 8) && (
                    <Button type="button" variant="outline" size="sm" onClick={() => onChange([...rows, Object.fromEntries(field.fields!.map(s => [s.key, '']))])}><Plus className="size-3.5" /> Add a {noun}</Button>
                )}
            </div>
        );
    }

    return (
        <div className="space-y-1 text-sm">
            <span className="block font-medium text-slate-700 dark:text-slate-300">{field.label}</span>
            {field.kind === 'image' ? (
                <ImagePicker value={String(value ?? '')} onChange={onChange} disabled={disabled} label={field.label} />
            ) : field.kind === 'textarea' ? (
                <Textarea aria-label={field.label} disabled={disabled} value={String(value ?? '')} maxLength={field.max} onChange={e => onChange(e.target.value)} />
            ) : field.kind === 'select' ? (
                <select aria-label={field.label} disabled={disabled} className={selectClass} value={String(value ?? '')} onChange={e => onChange(e.target.value)}>
                    {Object.entries(field.options ?? {}).map(([k, label]) => <option key={k} value={k}>{label}</option>)}
                </select>
            ) : field.kind === 'number' ? (
                <Input aria-label={field.label} disabled={disabled} type="number" min={field.min} max={field.max} value={String(value ?? field.min ?? 1)} onChange={e => onChange(Number(e.target.value))} />
            ) : (
                <Input aria-label={field.label} disabled={disabled} value={String(value ?? '')} maxLength={field.max} onChange={e => onChange(e.target.value)} />
            )}
            {field.hint && <p className="text-xs text-slate-500">{field.hint}</p>}
        </div>
    );
}

/* ---------- search engine (SEO) helper ---------- */
function wordsIn(blocks: Block[]) {
    const text = blocks.filter(b => !b.hidden).map(b => [b.heading, b.subheading, b.body, b.intro].filter(Boolean).join(' ')).join(' ');
    return text.split(/\s+/).filter(Boolean).length;
}

function SeoPanel({ page, form, schoolName, publicUrl, tagline, school, canEdit }: { page: Page; form: { data: { title: string; seo_title: string; seo_description: string; blocks: Block[]; slug: string; is_home: boolean }; setData: (k: string, v: unknown) => void }; schoolName: string; publicUrl: string; tagline: string; school: Props['school']; canEdit: boolean }) {
    const d = form.data;
    const autoTitle = d.is_home ? `${schoolName}${tagline ? ` – ${tagline}` : ''}` : `${d.title} | ${schoolName}`;
    const title = d.seo_title.trim() || autoTitle;
    const firstText = d.blocks.find(b => !b.hidden && (b.body || b.subheading));
    const auto = String(firstText?.body ?? firstText?.subheading ?? '').replace(/\s+/g, ' ').slice(0, 155);
    const description = d.seo_description.trim() || auto;
    const url = d.is_home ? publicUrl : `${publicUrl}/${d.slug}`;
    const hasHeading = d.blocks.some(b => !b.hidden && b.heading);
    const words = wordsIn(d.blocks);

    const checks = [
        { ok: title.length >= 25 && title.length <= 65, text: `Page title is ${title.length} characters`, tip: title.length < 25 ? 'A little short. Aim for 30 to 60 characters, with what the school is and where.' : 'Long titles get cut off in Google. Aim for 30 to 60 characters.' },
        { ok: description.length >= 70 && description.length <= 160, text: `Description is ${description.length} characters`, tip: 'Write one or two friendly sentences, 70 to 160 characters, that make a parent want to click.' },
        { ok: hasHeading, text: 'The page has a main heading', tip: 'Add a heading to your first section. Google uses it to understand the page.' },
        { ok: words >= 80, text: `${words} words of text`, tip: 'Pages with a little more real writing (80+ words) are easier to find.' },
        { ok: !!school.address && !!school.phone, text: 'School address and phone are filled in', tip: 'Add them in Settings. Parents and Google use them to find you.' },
    ];

    return (
        <Panel title="Show up on Google" description="This is how your page looks in search results. We fill it in for you, and you can change it.">
            <div className="space-y-4 text-sm">
                <div className="rounded-lg border border-slate-200 p-3 dark:border-white/10" aria-label="Google preview">
                    <p className="truncate text-xs text-emerald-700 dark:text-emerald-400">{url}</p>
                    <p className="truncate text-lg text-blue-700 dark:text-blue-400">{title}</p>
                    <p className="line-clamp-2 text-slate-600 dark:text-slate-300">{description || 'No description yet.'}</p>
                </div>
                <label className="block space-y-1"><span className="font-medium">Title in Google <span className="font-normal text-slate-500">({title.length}/60)</span></span>
                    <Input disabled={!canEdit} maxLength={80} placeholder={autoTitle} value={d.seo_title} onChange={e => form.setData('seo_title', e.target.value)} /></label>
                <label className="block space-y-1"><span className="font-medium">Description in Google <span className="font-normal text-slate-500">({description.length}/160)</span></span>
                    <Textarea disabled={!canEdit} maxLength={200} placeholder={auto || 'One or two sentences about this page'} value={d.seo_description} onChange={e => form.setData('seo_description', e.target.value)} /></label>
                <ul className="space-y-1.5">
                    {checks.map(c => (
                        <li key={c.text} className="flex items-start gap-2">
                            <span className={`mt-0.5 grid size-4 shrink-0 place-items-center rounded-full text-white ${c.ok ? 'bg-emerald-500' : 'bg-amber-500'}`}>{c.ok ? <Check className="size-3" /> : <span className="text-[10px] font-bold">!</span>}</span>
                            <span>{c.text}{!c.ok && <span className="block text-xs text-slate-500">{c.tip}</span>}</span>
                        </li>
                    ))}
                </ul>
                <p className="text-xs text-slate-500">We also add the things search engines look for behind the scenes: a sitemap, share cards for WhatsApp and Facebook, your address and phone as school details, and your questions and answers.</p>
            </div>
        </Panel>
    );
}

/* ---------- one page's editor ---------- */
function PageEditor({ page, props, onClose }: { page: Page; props: Props; onClose: () => void }) {
    const { blockTypes, canEdit, publicUrl, school, site } = props;
    const form = useForm({ title: page.title, slug: page.slug, seo_title: page.seo_title ?? '', seo_description: page.seo_description ?? '', is_published: page.is_published, is_home: page.is_home, show_in_menu: page.show_in_menu, blocks: (page.blocks ?? []) as Block[] });
    const { state, show, close } = usePreview();
    const [open, setOpen] = useState<number | null>(null);
    const [adding, setAdding] = useState(false);
    const blocks = form.data.blocks;
    const typeOf = (t: string) => blockTypes.find(b => b.type === t);
    const setBlocks = (next: Block[]) => form.setData('blocks', next);
    const patch = (i: number, p: Partial<Block>) => setBlocks(blocks.map((b, j) => (j === i ? { ...b, ...p } : b)));
    const move = (i: number, by: number) => {
        const next = [...blocks]; const j = i + by;
        if (j < 0 || j >= next.length) return;
        [next[i], next[j]] = [next[j], next[i]];
        setBlocks(next); setOpen(open === i ? j : open === j ? i : open);
    };

    function preview() {
        const fd = new FormData();
        fd.append('page_id', String(page.id));
        appendDeep(fd, 'site', site);
        appendDeep(fd, 'page', { title: form.data.title, seo_title: form.data.seo_title, seo_description: form.data.seo_description, has_blocks: true, blocks });
        show('/school/website/preview', fd, `Preview: ${form.data.title}`);
    }

    function summary(b: Block) {
        const t = String(b.heading ?? b.question ?? '') || String(b.body ?? b.subheading ?? '');
        return t.length > 60 ? `${t.slice(0, 60)}…` : t;
    }

    return (
        <div className="space-y-4">
            <button type="button" onClick={onClose} className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800"><ArrowLeft className="size-4" /> All pages</button>

            <Panel title="About this page">
                <div className="space-y-4 text-sm">
                    <div className="grid gap-3 sm:grid-cols-2">
                        <label className="block space-y-1"><span className="font-medium">Page name</span><Input disabled={!canEdit} value={form.data.title} onChange={e => form.setData('title', e.target.value)} /></label>
                        <label className="block space-y-1"><span className="font-medium">Web address ending</span><Input disabled={!canEdit || form.data.is_home} value={form.data.is_home ? '(home page)' : form.data.slug} onChange={e => form.setData('slug', e.target.value)} /></label>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-3">
                        <label className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3 dark:border-white/10">Visible to visitors <Switch label="Visible to visitors" disabled={!canEdit || form.data.is_home} on={form.data.is_published || form.data.is_home} onChange={v => form.setData('is_published', v)} /></label>
                        <label className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3 dark:border-white/10">Show in menu <Switch label="Show in menu" disabled={!canEdit} on={form.data.show_in_menu} onChange={v => form.setData('show_in_menu', v)} /></label>
                        <label className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3 dark:border-white/10">Home page <Switch label="Home page" disabled={!canEdit || page.is_home} on={form.data.is_home} onChange={v => form.setData('is_home', v)} /></label>
                    </div>
                    {Object.values(form.errors).map((m, i) => <p key={i} className="text-red-600">{m}</p>)}
                </div>
            </Panel>

            <Panel title="Sections" description="Your page is built from sections, from top to bottom. Tap a section to change it. Use the switch to hide one without losing it.">
                <div className="space-y-2">
                    {blocks.length === 0 && <p className="text-sm text-slate-500">This page is empty. Add your first section below.</p>}
                    {blocks.map((block, i) => {
                        const t = typeOf(block.type);
                        if (!t) return null;
                        const isOpen = open === i;
                        return (
                            <div key={i} className={`rounded-xl border ${isOpen ? 'border-indigo-300 dark:border-indigo-400/40' : 'border-slate-200 dark:border-white/10'} ${block.hidden ? 'opacity-60' : ''}`}>
                                <div className="flex items-center gap-2 p-3">
                                    <button type="button" className="flex min-w-0 flex-1 items-center gap-3 text-left" aria-expanded={isOpen} onClick={() => setOpen(isOpen ? null : i)}>
                                        <ChevronDown className={`size-4 shrink-0 text-slate-400 transition-transform ${isOpen ? 'rotate-180' : ''}`} />
                                        <span className="min-w-0"><span className="block text-sm font-semibold">{t.label}</span><span className="block truncate text-xs text-slate-500">{summary(block) || t.help}</span></span>
                                    </button>
                                    {canEdit && (
                                        <div className="flex shrink-0 items-center gap-1">
                                            <Switch label={`Show ${t.label}`} on={!block.hidden} onChange={v => patch(i, { hidden: !v })} />
                                            <Button type="button" variant="ghost" size="icon-sm" aria-label="Move up" disabled={i === 0} onClick={() => move(i, -1)}><ArrowUp className="size-4" /></Button>
                                            <Button type="button" variant="ghost" size="icon-sm" aria-label="Move down" disabled={i === blocks.length - 1} onClick={() => move(i, 1)}><ArrowDown className="size-4" /></Button>
                                        </div>
                                    )}
                                </div>
                                {isOpen && (
                                    <div className="space-y-4 border-t border-slate-100 p-4 dark:border-white/[0.06]">
                                        <p className="text-xs text-slate-500">{t.help}</p>
                                        {t.fields.map(f => <FieldInput key={f.key} field={f} disabled={!canEdit} value={block[f.key]} onChange={v => patch(i, { [f.key]: v })} />)}
                                        {canEdit && <button type="button" className="inline-flex items-center gap-1 text-sm text-red-600 hover:underline" onClick={() => { if (confirm('Remove this section from the page?')) { setBlocks(blocks.filter((_, j) => j !== i)); setOpen(null); } }}><Trash2 className="size-3.5" /> Remove this section</button>}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>

                {canEdit && (
                    <div className="mt-4">
                        {!adding ? (
                            <Button type="button" variant="outline" onClick={() => setAdding(true)}><Plus className="size-4" /> Add a section</Button>
                        ) : (
                            <div className="space-y-3">
                                <div className="flex items-center justify-between"><p className="text-sm font-semibold">What would you like to add?</p><button type="button" className="text-sm text-slate-500 hover:underline" onClick={() => setAdding(false)}>Cancel</button></div>
                                <div className="grid gap-2 sm:grid-cols-2">
                                    {blockTypes.map(t => (
                                        <button key={t.type} type="button" className="rounded-lg border border-slate-200 p-3 text-left hover:border-indigo-300 hover:bg-indigo-50/50 dark:border-white/10 dark:hover:bg-white/5"
                                            onClick={() => { setBlocks([...blocks, { type: t.type, hidden: false, ...t.default }]); setOpen(blocks.length); setAdding(false); }}>
                                            <span className="block text-sm font-semibold">{t.label}</span><span className="block text-xs text-slate-500">{t.help}</span>
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </Panel>

            <SeoPanel page={page} form={form as never} schoolName={school.name} publicUrl={publicUrl} tagline={site.tagline ?? ''} school={school} canEdit={canEdit} />

            <div className="sticky bottom-16 z-10 -mx-1 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur md:bottom-3 dark:border-white/10 dark:bg-slate-900/95">
                <span className="text-sm text-slate-500">{form.isDirty ? 'You have changes that are not saved yet.' : 'Everything is saved.'}</span>
                <div className="flex gap-2">
                    <Button type="button" variant="outline" onClick={preview}><Eye className="size-4" /> Preview</Button>
                    {canEdit && <Button type="button" disabled={form.processing || !form.isDirty} onClick={() => form.put(`/school/website/pages/${page.id}`, { preserveScroll: true, onSuccess: () => form.setDefaults() })} className="bg-indigo-600 text-white hover:bg-indigo-700">{form.processing ? 'Saving…' : 'Save page'}</Button>}
                </div>
            </div>
            <PreviewOverlay state={state} onClose={close} />
        </div>
    );
}

/* ---------- look, contact and pages ---------- */
function ThemeMock({ t, brand, brand2, active }: { t: Theme; brand: string; brand2: string; active: boolean }) {
    const hdr = t.key === 'heritage' ? brand2 : t.bg;
    return (
        <div className={`overflow-hidden rounded-xl border-2 ${active ? 'border-indigo-500' : 'border-slate-200 dark:border-white/10'}`} style={{ background: t.bg }} aria-hidden>
            <div className="flex items-center gap-1.5 px-2 py-1.5" style={{ background: hdr }}><i className="size-2 rounded-full" style={{ background: brand }} /><i className="h-1.5 w-8 rounded" style={{ background: brand }} /><i className="ml-auto h-1.5 w-6 rounded bg-black/20" /></div>
            <div className="px-2.5 py-4" style={{ background: `linear-gradient(135deg, ${brand}, ${brand}cc)` }}>
                <i className={`block h-2.5 w-3/4 rounded bg-white/90 ${t.serif ? '' : 'h-3 font-black'}`} /><i className="mt-1.5 block h-1.5 w-1/2 rounded bg-white/60" />
                <i className="mt-3 block h-3 w-12 rounded-full" style={{ background: brand2 }} />
            </div>
            <div className="grid grid-cols-3 gap-1.5 p-2" style={{ background: t.soft }}>{[0, 1, 2].map(i => <i key={i} className="h-6 rounded bg-white shadow-sm" />)}</div>
        </div>
    );
}

export default function WebsiteIndex(props: Props) {
    const { site, pages, themes, publicUrl, canEdit, school, socialNames } = props;
    const form = useForm({ enabled: site.enabled, theme: site.theme, color: site.color ?? '', color2: site.color2 ?? '', tagline: site.tagline ?? '', meta_description: site.meta_description ?? '', footer_text: site.footer_text ?? '', social: site.social });
    const add = useForm({ title: '' });
    const [tab, setTab] = useState<'pages' | 'look' | 'contact'>('pages');
    const [editing, setEditing] = useState<number | null>(null);
    const [copied, setCopied] = useState(false);
    const { state, show, close } = usePreview();
    const open = pages.find(p => p.id === editing) ?? null;
    const theme = themes.find(t => t.key === form.data.theme) ?? themes[0];
    const brand = form.data.color || theme.brand;
    const brand2 = form.data.color2 || theme.brand2;
    const d = form.data;
    // the editor works with what is on screen right now, so a preview shows unsaved looks too
    const liveSite = useMemo(() => ({ ...site, theme: d.theme, color: d.color || null, color2: d.color2 || null, tagline: d.tagline, footer_text: d.footer_text, meta_description: d.meta_description, social: d.social }), [site, d]);

    const save = () => form.put('/school/website', { preserveScroll: true, onSuccess: () => form.setDefaults() });
    function previewHome() {
        const home = pages.find(p => p.is_home) ?? pages[0];
        const fd = new FormData();
        fd.append('page_id', String(home.id));
        appendDeep(fd, 'site', liveSite);
        show('/school/website/preview', fd, 'Preview: your website');
    }

    return (
        <AppLayout breadcrumbs={[{ label: 'Settings', href: '/school/settings' }, { label: 'Website' }]}>
            <div className="mx-auto max-w-3xl space-y-5 pb-28 md:pb-6">
                <PageHeader title="Website" description="Your school's public website. Pick a look, fill in your pages, and switch it on when you are ready." />

                <Panel>
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div className="min-w-0 space-y-1">
                            <div className="flex items-center gap-2"><span className="text-sm font-semibold">{d.enabled ? 'Your website is live' : 'Your website is off'}</span><Pill tone={d.enabled ? 'good' : 'neutral'}>{d.enabled ? 'Live' : 'Off'}</Pill></div>
                            <p className="flex flex-wrap items-center gap-x-2 text-sm text-slate-500">
                                <a className="inline-flex items-center gap-1 break-all text-indigo-600 hover:underline" href={publicUrl} target="_blank" rel="noreferrer">{publicUrl} <ExternalLink className="size-3.5 shrink-0" /></a>
                                <button type="button" className="text-xs underline" onClick={() => { navigator.clipboard?.writeText(publicUrl); setCopied(true); setTimeout(() => setCopied(false), 1500); }}>{copied ? 'Copied' : 'Copy link'}</button>
                            </p>
                            {!d.enabled && <p className="text-xs text-slate-500">Visitors see "not found" until you switch it on.</p>}
                        </div>
                        <div className="flex items-center gap-3">
                            <Button type="button" variant="outline" onClick={previewHome}><Eye className="size-4" /> Preview</Button>
                            {canEdit && <label className="flex items-center gap-2 text-sm font-medium">On <Switch label="Website is on" on={d.enabled} onChange={v => { form.setData('enabled', v); }} /></label>}
                        </div>
                    </div>
                    {canEdit && form.isDirty && <div className="mt-3 flex items-center justify-between gap-2 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200"><span>You have changes that are not saved yet.</span><Button type="button" size="sm" disabled={form.processing} onClick={save} className="bg-indigo-600 text-white hover:bg-indigo-700">Save</Button></div>}
                    {Object.values(form.errors).map((m, i) => <p key={i} className="mt-2 text-sm text-red-600">{m}</p>)}
                </Panel>

                <Segmented label="Website sections" value={tab} onChange={v => { setTab(v); setEditing(null); }} options={[{ value: 'pages', label: 'Pages' }, { value: 'look', label: 'Look and colours' }, { value: 'contact', label: 'Contact and social' }]} />

                {tab === 'look' && (
                    <Panel title="Choose a look" description="You can change the colours to match your school.">
                        <div className="space-y-5 text-sm">
                            <div className="grid gap-3 sm:grid-cols-3">
                                {themes.map(t => (
                                    <button type="button" key={t.key} disabled={!canEdit} onClick={() => form.setData('theme', t.key)} className="space-y-2 text-left" aria-pressed={d.theme === t.key}>
                                        <ThemeMock t={t} brand={d.color || t.brand} brand2={d.color2 || t.brand2} active={d.theme === t.key} />
                                        <span className="block font-semibold">{t.label}</span><span className="block text-xs text-slate-500">{t.note}</span>
                                    </button>
                                ))}
                            </div>
                            <div className="grid gap-3 sm:grid-cols-2">
                                {([['color', 'Main colour', brand], ['color2', 'Second colour', brand2]] as const).map(([key, label, shown]) => (
                                    <div key={key} className="space-y-1"><span className="block font-medium">{label}</span>
                                        <div className="flex items-center gap-2">
                                            <input type="color" aria-label={label} disabled={!canEdit} value={shown} onChange={e => form.setData(key, e.target.value)} className="h-10 w-14 rounded border border-input" />
                                            <Input disabled={!canEdit} placeholder={theme[key === 'color' ? 'brand' : 'brand2']} maxLength={7} value={d[key]} onChange={e => form.setData(key, e.target.value)} />
                                            {d[key] && canEdit && <button type="button" className="text-xs underline" onClick={() => form.setData(key, '')}>Reset</button>}
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <label className="block space-y-1"><span className="font-medium">Motto or tagline</span><Input disabled={!canEdit} maxLength={160} value={d.tagline} onChange={e => form.setData('tagline', e.target.value)} /></label>
                            <label className="block space-y-1"><span className="font-medium">Line at the bottom of every page</span><Input disabled={!canEdit} maxLength={300} value={d.footer_text} onChange={e => form.setData('footer_text', e.target.value)} /></label>
                            <label className="block space-y-1"><span className="font-medium">Short description of your school for Google</span><Textarea disabled={!canEdit} maxLength={300} value={d.meta_description} onChange={e => form.setData('meta_description', e.target.value)} /><span className="block text-xs text-slate-500">Used on any page that has no description of its own.</span></label>
                            {!school.logo && <p className="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600 dark:bg-white/5 dark:text-slate-300">Tip: add your school logo in Settings and it appears in the header, the footer and Google.</p>}
                        </div>
                    </Panel>
                )}

                {tab === 'contact' && (
                    <Panel title="Contact and social media" description="Your address, phone and email come from your school profile in Settings. Add your social pages here and they show in the footer.">
                        <div className="space-y-4 text-sm">
                            <ul className="space-y-1 rounded-lg bg-slate-50 p-3 dark:bg-white/5">
                                <li>Address: {school.address || <span className="text-amber-700">not added yet</span>}</li>
                                <li>Phone: {school.phone || <span className="text-amber-700">not added yet</span>}</li>
                                <li>Email: {school.email || <span className="text-amber-700">not added yet</span>}</li>
                            </ul>
                            <div className="grid gap-3 sm:grid-cols-2">
                                {Object.entries(socialNames).map(([key, label]) => (
                                    <label key={key} className="block space-y-1"><span className="font-medium">{label}</span>
                                        <Input disabled={!canEdit} placeholder="https://…" value={d.social[key] ?? ''} onChange={e => form.setData('social', { ...d.social, [key]: e.target.value })} /></label>
                                ))}
                            </div>
                        </div>
                    </Panel>
                )}

                {(tab === 'look' || tab === 'contact') && canEdit && <div className="flex justify-end"><Button type="button" disabled={form.processing || !form.isDirty} onClick={save} className="bg-indigo-600 text-white hover:bg-indigo-700">{form.processing ? 'Saving…' : 'Save'}</Button></div>}

                {tab === 'pages' && !open && (
                    <Panel title="Pages" flush>
                        <ul className="divide-y divide-slate-100 text-sm dark:divide-white/[0.06]">
                            {pages.map(p => (
                                <li key={p.id} className="flex flex-wrap items-center justify-between gap-2 px-5 py-3">
                                    <button type="button" className="flex items-center gap-2 text-left" onClick={() => setEditing(p.id)}>
                                        <span className="font-medium">{p.title}</span>
                                        {p.is_home && <Pill tone="good">Home</Pill>}
                                        {!p.is_published && <Pill tone="neutral">Hidden</Pill>}
                                    </button>
                                    <div className="flex gap-2">
                                        <Button type="button" size="sm" variant="outline" onClick={() => setEditing(p.id)}>{canEdit ? 'Edit' : 'View'}</Button>
                                        {canEdit && !p.is_home && <Button type="button" size="sm" variant="outline" aria-label={`Delete ${p.title}`} onClick={() => { if (confirm(`Delete the page "${p.title}"?`)) router.delete(`/school/website/pages/${p.id}`, { preserveScroll: true }); }}><Trash2 className="size-3.5" /></Button>}
                                    </div>
                                </li>
                            ))}
                        </ul>
                        {canEdit && (
                            <form onSubmit={e => { e.preventDefault(); add.post('/school/website/pages', { preserveScroll: true, onSuccess: () => add.reset() }); }} className="flex gap-2 border-t border-slate-100 px-5 py-3 dark:border-white/[0.06]">
                                <Input aria-label="New page name" placeholder="New page, for example Fees" value={add.data.title} onChange={e => add.setData('title', e.target.value)} />
                                <Button type="submit" disabled={add.processing || !add.data.title.trim()}>Add page</Button>
                            </form>
                        )}
                    </Panel>
                )}

                {tab === 'pages' && open && <PageEditor key={open.id} page={open} props={{ ...props, site: liveSite }} onClose={() => setEditing(null)} />}
                <PreviewOverlay state={state} onClose={close} />
            </div>
        </AppLayout>
    );
}
