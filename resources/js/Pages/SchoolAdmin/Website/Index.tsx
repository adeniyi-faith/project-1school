import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { PageHeader, Panel, Pill } from '@/components/app/kit';
import { ArrowDown, ArrowUp, ExternalLink, Plus, Trash2 } from 'lucide-react';

interface Field { key: string; label: string; kind: 'text' | 'textarea' | 'select' | 'number' | 'list'; options?: Record<string, string>; min?: number; max?: number; limit?: number; fields?: Field[] }
interface BlockType { type: string; label: string; help: string; fields: Field[]; default: Record<string, unknown> }
type Block = { type: string } & Record<string, unknown>;
interface Page { id: number; slug: string; title: string; blocks: Block[] | null; is_published: boolean; is_home: boolean; show_in_menu: boolean }
interface Props {
    site: { enabled: boolean; theme: string; color: string | null; tagline: string | null; meta_description: string | null; footer_text: string | null };
    pages: Page[];
    themes: { key: string; label: string; note: string; accent: string }[];
    blockTypes: BlockType[];
    publicUrl: string;
    canEdit: boolean;
}

const selectClass = 'h-9 w-full rounded-lg border border-input bg-transparent px-2.5 text-sm dark:bg-input/30';

/** One box of a block (a line of text, a longer text, a choice, a number or a repeating list) */
function FieldInput({ field, value, onChange, disabled }: { field: Field; value: unknown; onChange: (v: unknown) => void; disabled: boolean }) {
    if (field.kind === 'list') {
        const rows = (value as Record<string, unknown>[]) ?? [];
        return (
            <div className="space-y-2">
                {rows.map((row, i) => (
                    <div key={i} className="space-y-2 rounded-lg border border-slate-200 p-3 dark:border-white/10">
                        {field.fields!.map(sub => (
                            <FieldInput key={sub.key} field={sub} disabled={disabled} value={row[sub.key]} onChange={v => onChange(rows.map((r, j) => (j === i ? { ...r, [sub.key]: v } : r)))} />
                        ))}
                        {!disabled && <button type="button" className="text-xs text-red-600 hover:underline" onClick={() => onChange(rows.filter((_, j) => j !== i))}>Remove this box</button>}
                    </div>
                ))}
                {!disabled && rows.length < (field.limit ?? 8) && (
                    <Button type="button" variant="outline" size="sm" onClick={() => onChange([...rows, Object.fromEntries(field.fields!.map(s => [s.key, '']))])}><Plus className="size-3.5" /> Add a box</Button>
                )}
            </div>
        );
    }

    return (
        <label className="block space-y-1 text-sm">
            <span className="font-medium text-slate-700 dark:text-slate-300">{field.label}</span>
            {field.kind === 'textarea' ? (
                <Textarea disabled={disabled} value={String(value ?? '')} maxLength={field.max} onChange={e => onChange(e.target.value)} />
            ) : field.kind === 'select' ? (
                <select disabled={disabled} className={selectClass} value={String(value ?? '')} onChange={e => onChange(e.target.value)}>
                    {Object.entries(field.options ?? {}).map(([k, label]) => <option key={k} value={k}>{label}</option>)}
                </select>
            ) : field.kind === 'number' ? (
                <Input disabled={disabled} type="number" min={field.min} max={field.max} value={String(value ?? field.min ?? 1)} onChange={e => onChange(Number(e.target.value))} />
            ) : (
                <Input disabled={disabled} value={String(value ?? '')} maxLength={field.max} onChange={e => onChange(e.target.value)} />
            )}
        </label>
    );
}

function PageEditor({ page, blockTypes, canEdit, onClose }: { page: Page; blockTypes: BlockType[]; canEdit: boolean; onClose: () => void }) {
    const form = useForm({ title: page.title, slug: page.slug, is_published: page.is_published, is_home: page.is_home, show_in_menu: page.show_in_menu, blocks: (page.blocks ?? []) as Block[] });
    const [adding, setAdding] = useState(blockTypes[0].type);
    const blocks = form.data.blocks;
    const typeOf = (t: string) => blockTypes.find(b => b.type === t);

    const setBlocks = (next: Block[]) => form.setData('blocks', next);
    const move = (i: number, by: number) => {
        const next = [...blocks];
        const j = i + by;
        if (j < 0 || j >= next.length) return;
        [next[i], next[j]] = [next[j], next[i]];
        setBlocks(next);
    };

    return (
        <Panel title={`Editing: ${page.title}`} action={<button type="button" onClick={onClose} className="text-slate-500 hover:underline">Close</button>}>
            <form onSubmit={e => { e.preventDefault(); form.put(`/school/website/pages/${page.id}`, { preserveScroll: true }); }} className="space-y-5">
                <div className="grid gap-3 sm:grid-cols-2">
                    <label className="block space-y-1 text-sm"><span className="font-medium">Page title</span><Input disabled={!canEdit} value={form.data.title} onChange={e => form.setData('title', e.target.value)} /></label>
                    <label className="block space-y-1 text-sm"><span className="font-medium">Address ending</span><Input disabled={!canEdit || page.is_home} value={form.data.slug} onChange={e => form.setData('slug', e.target.value)} /></label>
                </div>
                <div className="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                    <label className="flex items-center gap-2"><Checkbox disabled={!canEdit || form.data.is_home} checked={form.data.is_published} onCheckedChange={v => form.setData('is_published', !!v)} /> Visible to visitors</label>
                    <label className="flex items-center gap-2"><Checkbox disabled={!canEdit} checked={form.data.show_in_menu} onCheckedChange={v => form.setData('show_in_menu', !!v)} /> Show in the menu</label>
                    <label className="flex items-center gap-2"><Checkbox disabled={!canEdit || page.is_home} checked={form.data.is_home} onCheckedChange={v => form.setData('is_home', !!v)} /> This is the home page</label>
                </div>
                {Object.values(form.errors).map((m, i) => <p key={i} className="text-sm text-red-600">{m}</p>)}

                <div className="space-y-3">
                    {blocks.length === 0 && <p className="text-sm text-slate-500">This page is empty. Add a block below.</p>}
                    {blocks.map((block, i) => {
                        const t = typeOf(block.type);
                        if (!t) return null;
                        return (
                            <div key={i} className="space-y-3 rounded-xl border border-slate-200 p-4 dark:border-white/10">
                                <div className="flex items-center justify-between gap-2">
                                    <div><p className="text-sm font-semibold">{t.label}</p><p className="text-xs text-slate-500">{t.help}</p></div>
                                    {canEdit && (
                                        <div className="flex gap-1">
                                            <Button type="button" variant="outline" size="icon-sm" aria-label="Move up" disabled={i === 0} onClick={() => move(i, -1)}><ArrowUp className="size-4" /></Button>
                                            <Button type="button" variant="outline" size="icon-sm" aria-label="Move down" disabled={i === blocks.length - 1} onClick={() => move(i, 1)}><ArrowDown className="size-4" /></Button>
                                            <Button type="button" variant="outline" size="icon-sm" aria-label="Remove block" onClick={() => setBlocks(blocks.filter((_, j) => j !== i))}><Trash2 className="size-4" /></Button>
                                        </div>
                                    )}
                                </div>
                                {t.fields.map(f => (
                                    <FieldInput key={f.key} field={f} disabled={!canEdit} value={block[f.key]} onChange={v => setBlocks(blocks.map((b, j) => (j === i ? { ...b, [f.key]: v } : b)))} />
                                ))}
                            </div>
                        );
                    })}
                </div>

                {canEdit && (
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex items-center gap-2">
                            <select aria-label="Kind of block" className={`${selectClass} w-auto`} value={adding} onChange={e => setAdding(e.target.value)}>
                                {blockTypes.map(b => <option key={b.type} value={b.type}>{b.label}</option>)}
                            </select>
                            <Button type="button" variant="outline" onClick={() => setBlocks([...blocks, { type: adding, ...(typeOf(adding)!.default) }])}><Plus className="size-4" /> Add block</Button>
                        </div>
                        <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Save page</Button>
                    </div>
                )}
            </form>
        </Panel>
    );
}

/** The school admin's website editor */
export default function WebsiteIndex({ site, pages, themes, blockTypes, publicUrl, canEdit }: Props) {
    const form = useForm({ enabled: site.enabled, theme: site.theme, color: site.color ?? '', tagline: site.tagline ?? '', meta_description: site.meta_description ?? '', footer_text: site.footer_text ?? '' });
    const add = useForm({ title: '' });
    const [editing, setEditing] = useState<number | null>(null);
    const open = pages.find(p => p.id === editing) ?? null;

    return (
        <AppLayout breadcrumbs={[{ label: 'Settings', href: '/school/settings' }, { label: 'Website' }]}>
            <div className="mx-auto max-w-3xl space-y-6 pb-24 md:pb-0">
                <PageHeader title="Website" description="Give your school a public website in minutes. Pick a look, edit the pages, and switch it on when you are ready." />

                <form onSubmit={e => { e.preventDefault(); form.put('/school/website', { preserveScroll: true }); }}>
                    <Panel title="Your site" description={canEdit ? undefined : 'Only an admin can change these.'}>
                        <div className="space-y-4 text-sm">
                            <label className="flex items-center gap-2 font-medium"><Checkbox disabled={!canEdit} checked={form.data.enabled} onCheckedChange={v => form.setData('enabled', !!v)} /> Website is on</label>
                            <p className="flex flex-wrap items-center gap-2 text-slate-500">Address: <a className="inline-flex items-center gap-1 text-indigo-600 hover:underline" href={publicUrl} target="_blank" rel="noreferrer">{publicUrl} <ExternalLink className="size-3.5" /></a>
                                {!form.data.enabled && <Pill tone="neutral">Off: visitors see "not found"</Pill>}</p>

                            <div>
                                <p className="mb-2 font-medium">Look</p>
                                <div className="grid gap-2 sm:grid-cols-3">
                                    {themes.map(t => (
                                        <button type="button" disabled={!canEdit} key={t.key} onClick={() => form.setData('theme', t.key)}
                                            className={`rounded-lg border p-3 text-left ${form.data.theme === t.key ? 'border-indigo-500 ring-2 ring-indigo-200 dark:ring-indigo-500/30' : 'border-slate-200 dark:border-white/10'}`}>
                                            <span className="mb-2 block h-2 rounded-full" style={{ background: t.accent }} />
                                            <span className="block font-medium">{t.label}</span><span className="text-xs text-slate-500">{t.note}</span>
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <label className="block space-y-1"><span className="font-medium">Your own colour (optional)</span>
                                    <div className="flex items-center gap-2">
                                        <input type="color" aria-label="Pick a colour" disabled={!canEdit} value={form.data.color || '#1d4ed8'} onChange={e => form.setData('color', e.target.value)} className="h-9 w-12 rounded border border-input" />
                                        <Input disabled={!canEdit} placeholder="#1d4ed8" maxLength={7} value={form.data.color} onChange={e => form.setData('color', e.target.value)} />
                                    </div>
                                </label>
                                <label className="block space-y-1"><span className="font-medium">Motto or tagline</span><Input disabled={!canEdit} maxLength={160} value={form.data.tagline} onChange={e => form.setData('tagline', e.target.value)} /></label>
                            </div>
                            <label className="block space-y-1"><span className="font-medium">Short description for Google</span><Textarea disabled={!canEdit} maxLength={300} value={form.data.meta_description} onChange={e => form.setData('meta_description', e.target.value)} /></label>
                            <label className="block space-y-1"><span className="font-medium">Line at the bottom of every page</span><Input disabled={!canEdit} maxLength={300} value={form.data.footer_text} onChange={e => form.setData('footer_text', e.target.value)} /></label>
                            {Object.values(form.errors).map((m, i) => <p key={i} className="text-red-600">{m}</p>)}
                        </div>
                        {canEdit && <div className="mt-4 flex justify-end"><Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Save</Button></div>}
                    </Panel>
                </form>

                <Panel title="Pages" flush>
                    <ul className="divide-y divide-slate-100 text-sm dark:divide-white/[0.06]">
                        {pages.map(p => (
                            <li key={p.id} className="flex flex-wrap items-center justify-between gap-2 px-5 py-2.5">
                                <div className="flex items-center gap-2">
                                    <span className="font-medium">{p.title}</span>
                                    {p.is_home && <Pill tone="good">Home</Pill>}
                                    {!p.is_published && <Pill tone="neutral">Hidden</Pill>}
                                </div>
                                <div className="flex gap-2">
                                    <Button type="button" size="sm" variant="outline" onClick={() => setEditing(p.id)}>{canEdit ? 'Edit' : 'View'}</Button>
                                    {canEdit && !p.is_home && <Button type="button" size="sm" variant="outline" onClick={() => { if (confirm(`Delete the page "${p.title}"?`)) router.delete(`/school/website/pages/${p.id}`, { preserveScroll: true }); }}><Trash2 className="size-3.5" /></Button>}
                                </div>
                            </li>
                        ))}
                    </ul>
                    {canEdit && (
                        <form onSubmit={e => { e.preventDefault(); add.post('/school/website/pages', { preserveScroll: true, onSuccess: () => add.reset() }); }} className="flex gap-2 border-t border-slate-100 px-5 py-3 dark:border-white/[0.06]">
                            <Input aria-label="New page title" placeholder="New page, for example Admissions" value={add.data.title} onChange={e => add.setData('title', e.target.value)} />
                            <Button type="submit" disabled={add.processing || !add.data.title.trim()}>Add page</Button>
                        </form>
                    )}
                </Panel>

                {open && <PageEditor key={open.id} page={open} blockTypes={blockTypes} canEdit={canEdit} onClose={() => setEditing(null)} />}
            </div>
        </AppLayout>
    );
}
