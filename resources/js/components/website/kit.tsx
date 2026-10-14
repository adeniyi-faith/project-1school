import { useRef, useState, type ReactNode } from 'react';
import { Link, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Pill } from '@/components/app/kit';
import { Bold, Check, ExternalLink, Heading2, ImagePlus, Italic, Link2, List, ListOrdered, Plus, Quote, Upload, X } from 'lucide-react';
import type { Field, MediaItem, WebsiteShell } from '@/Types/website';

export const selectClass = 'h-10 w-full rounded-lg border border-input bg-transparent px-2.5 text-sm dark:bg-input/30';
const csrf = () => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
export const imageUrl = (path: string) => `/storage/${path}`;

/** Sends one picture to the school's picture library and answers with what was stored */
export async function uploadImage(file: File): Promise<MediaItem> {
    const body = new FormData();
    body.append('image', file);
    const res = await fetch('/school/website/media', { method: 'POST', body, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' } });
    if (res.status === 413) throw new Error('That picture is too big. Try one under 10 MB.');
    if (res.status === 419) throw new Error('This page has been open too long. Reload it, then try again.');
    if (!res.ok) throw new Error(res.status === 422 ? 'That file is not a picture we can use. Try a JPG, PNG or WebP under 10 MB.' : 'The picture could not be uploaded. Please try again.');
    return (await res.json()) as MediaItem;
}

const TABS = [
    { href: '/school/website', label: 'Overview', exact: true },
    { href: '/school/website/pages', label: 'Pages' },
    { href: '/school/website/posts', label: 'News & events' },
    { href: '/school/website/themes', label: 'Themes' },
    { href: '/school/website/media', label: 'Pictures' },
    { href: '/school/website/settings', label: 'Settings' },
];

/** The frame around every website screen: title, live status, link to the site and the website tabs */
export function WebsiteLayout({ shell, title, description, actions, crumb, children, wide }: { shell: WebsiteShell; title: string; description?: ReactNode; actions?: ReactNode; crumb?: { label: string; href?: string }[]; children: ReactNode; wide?: boolean }) {
    const { url } = usePage();
    const path = url.split('?')[0];
    return (
        <AppLayout breadcrumbs={[{ label: 'Website', href: '/school/website' }, ...(crumb ?? [])]}>
            <div className={`mx-auto space-y-5 pb-28 md:pb-8 ${wide ? 'max-w-6xl' : 'max-w-5xl'}`}>
                <nav aria-label="Website" className="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                    <div className="flex min-w-max gap-1 border-b border-slate-200 dark:border-white/10">
                        {TABS.map(t => {
                            const active = t.exact ? path === t.href : path.startsWith(t.href);
                            return (
                                <Link key={t.href} href={t.href} aria-current={active ? 'page' : undefined}
                                    className={`-mb-px border-b-2 px-3 py-2.5 text-sm font-medium transition-colors ${active ? 'border-indigo-600 text-indigo-700 dark:border-indigo-400 dark:text-indigo-300' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-white'}`}>
                                    {t.label}
                                </Link>
                            );
                        })}
                    </div>
                </nav>
                <header className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0">
                        <h1 className="text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl dark:text-white">{title}</h1>
                        {description && <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{description}</p>}
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        {actions}
                        <a href={shell.publicUrl} target="_blank" rel="noreferrer" className="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 px-3 text-sm font-medium hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5">
                            <span className={`size-2 rounded-full ${shell.enabled ? 'bg-emerald-500' : 'bg-slate-400'}`} aria-hidden />
                            {shell.enabled ? 'View site' : 'Site is off'} <ExternalLink className="size-3.5" />
                        </a>
                    </div>
                </header>
                {children}
            </div>
        </AppLayout>
    );
}

export function Switch({ on, onChange, label, disabled }: { on: boolean; onChange: (v: boolean) => void; label: string; disabled?: boolean }) {
    return (
        <button type="button" role="switch" aria-checked={on} aria-label={label} disabled={disabled} onClick={() => onChange(!on)}
            className={`relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors disabled:opacity-50 ${on ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-white/20'}`}>
            <span className={`inline-block size-5 rounded-full bg-white shadow transition-transform ${on ? 'translate-x-5' : 'translate-x-0.5'}`} />
        </button>
    );
}

export function StatusPill({ status }: { status: string }) {
    if (status === 'published') return <Pill tone="good">Published</Pill>;
    if (status === 'scheduled') return <Pill tone="warn">Scheduled</Pill>;
    return <Pill tone="neutral">Draft</Pill>;
}

/** The picture library in a pop-up: pick one already uploaded, or upload a new one */
export function MediaDialog({ open, onClose, media, onPick }: { open: boolean; onClose: () => void; media: MediaItem[]; onPick: (m: MediaItem) => void }) {
    const [items, setItems] = useState(media);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const input = useRef<HTMLInputElement>(null);

    async function upload(file?: File) {
        if (!file) return;
        setBusy(true); setError('');
        try { const m = await uploadImage(file); setItems(list => [m, ...list]); onPick(m); onClose(); } catch (e) { setError((e as Error).message); } finally { setBusy(false); if (input.current) input.current.value = ''; }
    }

    return (
        <Dialog open={open} onOpenChange={v => !v && onClose()}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader><DialogTitle>Choose a picture</DialogTitle></DialogHeader>
                <div className="space-y-3">
                    <div className="flex flex-wrap items-center gap-2">
                        <Button type="button" disabled={busy} onClick={() => input.current?.click()} className="bg-indigo-600 text-white hover:bg-indigo-700"><Upload className="size-4" /> {busy ? 'Uploading…' : 'Upload a new picture'}</Button>
                        <span className="text-xs text-slate-500">JPG, PNG or WebP. Big phone photos are made smaller for you.</span>
                        <input ref={input} type="file" accept="image/png,image/jpeg,image/webp" className="sr-only" aria-label="Upload a picture" onChange={e => upload(e.target.files?.[0])} />
                    </div>
                    {error && <p className="text-sm text-red-600">{error}</p>}
                    {items.length === 0 ? (
                        <p className="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-white/15">Your picture library is empty. Upload your first picture.</p>
                    ) : (
                        <div className="grid max-h-[55vh] grid-cols-2 gap-2 overflow-y-auto sm:grid-cols-4">
                            {items.map(m => (
                                <button key={m.id} type="button" onClick={() => { onPick(m); onClose(); }} className="group overflow-hidden rounded-lg border border-slate-200 text-left hover:border-indigo-400 dark:border-white/10">
                                    <img src={imageUrl(m.path)} alt={m.alt ?? ''} loading="lazy" className="aspect-[4/3] w-full object-cover" />
                                    <span className="block truncate px-2 py-1 text-xs text-slate-600 dark:text-slate-300">{m.name}</span>
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}

export function ImagePicker({ value, onChange, disabled, label, media, wide }: { value: string; onChange: (path: string) => void; disabled: boolean; label: string; media: MediaItem[]; wide?: boolean }) {
    const [open, setOpen] = useState(false);
    return (
        <div className={wide ? 'space-y-2' : 'flex items-center gap-3'}>
            {value
                ? <img src={imageUrl(value)} alt="" className={wide ? 'aspect-[16/9] w-full rounded-lg border border-slate-200 object-cover dark:border-white/10' : 'h-16 w-24 rounded-lg border border-slate-200 object-cover dark:border-white/10'} />
                : <button type="button" disabled={disabled} onClick={() => setOpen(true)} aria-label={label} className={`grid place-items-center rounded-lg border border-dashed border-slate-300 text-slate-400 hover:border-indigo-400 hover:text-indigo-500 dark:border-white/20 ${wide ? 'aspect-[16/9] w-full' : 'h-16 w-24'}`}><ImagePlus className="size-5" /></button>}
            {!disabled && (
                <div className="flex flex-wrap gap-2">
                    <Button type="button" variant="outline" size="sm" onClick={() => setOpen(true)}>{value ? 'Change picture' : 'Choose a picture'}</Button>
                    {value && <Button type="button" variant="ghost" size="sm" onClick={() => onChange('')}><X className="size-3.5" /> Remove</Button>}
                </div>
            )}
            <MediaDialog open={open} onClose={() => setOpen(false)} media={media} onPick={m => onChange(m.path)} />
        </div>
    );
}

/**
 * A writing box with simple buttons (heading, bold, list, link ...). It writes the light marks the website understands
 * (## heading, **bold**, - list), so people never see code and nothing unsafe can be typed in.
 */
export function RichText({ value, onChange, disabled, label, max, rows = 10 }: { value: string; onChange: (v: string) => void; disabled?: boolean; label: string; max?: number; rows?: number }) {
    const ref = useRef<HTMLTextAreaElement>(null);

    function wrap(before: string, after = before, placeholder = 'text') {
        const el = ref.current; if (!el) return;
        const { selectionStart: s, selectionEnd: e } = el;
        const picked = value.slice(s, e) || placeholder;
        const next = value.slice(0, s) + before + picked + after + value.slice(e);
        onChange(next);
        requestAnimationFrame(() => { el.focus(); el.setSelectionRange(s + before.length, s + before.length + picked.length); });
    }
    function lines(prefix: (i: number) => string) {
        const el = ref.current; if (!el) return;
        const s = value.lastIndexOf('\n', el.selectionStart - 1) + 1;
        const eIdx = value.indexOf('\n', el.selectionEnd);
        const e = eIdx === -1 ? value.length : eIdx;
        const block = value.slice(s, e) || 'text';
        const changed = block.split('\n').map((l, i) => prefix(i) + l.replace(/^(#{2,3} |- |\d+\. |> )/, '')).join('\n');
        const before = s > 0 && value[s - 2] !== '\n' && value[s - 1] === '\n' ? '\n' : '';
        onChange(value.slice(0, s) + before + changed + value.slice(e));
        requestAnimationFrame(() => el.focus());
    }
    function link() {
        const url = window.prompt('Paste the web address (starting with https://)', 'https://');
        if (url && /^(https?:\/\/|mailto:|tel:|\/)/.test(url)) wrap('[', `](${url})`, 'link text');
    }

    const tools = [
        { icon: Heading2, label: 'Heading', run: () => lines(() => '## ') },
        { icon: Bold, label: 'Bold', run: () => wrap('**') },
        { icon: Italic, label: 'Slanted', run: () => wrap('*') },
        { icon: List, label: 'Bullet list', run: () => lines(() => '- ') },
        { icon: ListOrdered, label: 'Numbered list', run: () => lines(i => `${i + 1}. `) },
        { icon: Quote, label: 'Quote', run: () => lines(() => '> ') },
        { icon: Link2, label: 'Link', run: link },
    ];

    return (
        <div className="overflow-hidden rounded-lg border border-input focus-within:ring-2 focus-within:ring-indigo-500/30">
            {!disabled && (
                <div className="flex flex-wrap gap-0.5 border-b border-slate-200 bg-slate-50 p-1 dark:border-white/10 dark:bg-white/5" role="toolbar" aria-label={`${label} formatting`}>
                    {tools.map(t => (
                        <button key={t.label} type="button" title={t.label} aria-label={t.label} onMouseDown={e => e.preventDefault()} onClick={t.run}
                            className="grid size-8 place-items-center rounded-md text-slate-600 hover:bg-white hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/10"><t.icon className="size-4" /></button>
                    ))}
                </div>
            )}
            <textarea ref={ref} aria-label={label} disabled={disabled} rows={rows} maxLength={max} value={value} onChange={e => onChange(e.target.value)}
                className="block w-full resize-y bg-transparent px-3 py-2 text-sm leading-relaxed outline-none" />
        </div>
    );
}

export function FieldInput({ field, value, onChange, disabled, media }: { field: Field; value: unknown; onChange: (v: unknown) => void; disabled: boolean; media: MediaItem[] }) {
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
                            <FieldInput key={sub.key} field={sub} disabled={disabled} media={media} value={row[sub.key]} onChange={v => onChange(rows.map((r, j) => (j === i ? { ...r, [sub.key]: v } : r)))} />
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
                <ImagePicker value={String(value ?? '')} onChange={onChange} disabled={disabled} label={field.label} media={media} />
            ) : field.kind === 'textarea' && field.format ? (
                <RichText label={field.label} disabled={disabled} value={String(value ?? '')} max={field.max} onChange={onChange} />
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

/** How the page or story will look in Google, with simple checks that say what to improve */
export function GooglePreview({ url, title, description, checks, children }: { url: string; title: string; description: string; checks: { ok: boolean; text: string; tip: string }[]; children?: ReactNode }) {
    return (
        <div className="space-y-4 text-sm">
            <div className="rounded-lg border border-slate-200 p-3 dark:border-white/10" aria-label="Google preview">
                <p className="truncate text-xs text-emerald-700 dark:text-emerald-400">{url}</p>
                <p className="truncate text-lg text-blue-700 dark:text-blue-400">{title}</p>
                <p className="line-clamp-2 text-slate-600 dark:text-slate-300">{description || 'No description yet.'}</p>
            </div>
            {children}
            <ul className="space-y-1.5">
                {checks.map(c => (
                    <li key={c.text} className="flex items-start gap-2">
                        <span className={`mt-0.5 grid size-4 shrink-0 place-items-center rounded-full text-white ${c.ok ? 'bg-emerald-500' : 'bg-amber-500'}`}>{c.ok ? <Check className="size-3" /> : <span className="text-[10px] font-bold">!</span>}</span>
                        <span>{c.text}{!c.ok && <span className="block text-xs text-slate-500">{c.tip}</span>}</span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

/** The bar that stays at the bottom while editing: unsaved state and the main buttons */
export function SaveBar({ dirty, children }: { dirty: boolean; children: ReactNode }) {
    return (
        <div className="sticky bottom-16 z-10 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur md:bottom-3 dark:border-white/10 dark:bg-slate-900/95">
            <span className="text-sm text-slate-500">{dirty ? 'You have changes that are not saved yet.' : 'Everything is saved.'}</span>
            <div className="flex flex-wrap gap-2">{children}</div>
        </div>
    );
}

export const ErrorList = ({ errors }: { errors: Record<string, string> }) =>
    Object.keys(errors).length ? <div className="space-y-1 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300">{Object.values(errors).map((m, i) => <p key={i}>{m}</p>)}</div> : null;
