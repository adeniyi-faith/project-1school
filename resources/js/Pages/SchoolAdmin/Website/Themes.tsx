import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Panel, Pill } from '@/components/app/kit';
import { ErrorList, SaveBar, WebsiteLayout } from '@/components/website/kit';
import { ScaledSite } from '@/components/website/ScaledSite';
import { Check, Eye, Monitor, Smartphone, X } from 'lucide-react';
import type { Palette, ThemeOption, WebsiteShell } from '@/Types/website';

interface Props {
    shell: WebsiteShell; themes: ThemeOption[]; palettes: Palette[]; logoColors?: string[];
    current: { theme: string; color: string | null; color2: string | null };
}

/** Waits until typing or dragging a colour stops before the theme pictures are drawn again */
function useSettled<T>(value: T, ms = 600): T {
    const [settled, setSettled] = useState(value);
    useEffect(() => { const t = setTimeout(() => setSettled(value), ms); return () => clearTimeout(t); }, [value, ms]);
    return settled;
}

const isHex = (c: string) => /^#[0-9a-fA-F]{6}$/.test(c);

function sampleUrl(theme: string, color: string, color2: string) {
    const q = new URLSearchParams();
    if (isHex(color)) q.set('color', color);
    if (isHex(color2)) q.set('color2', color2);
    const s = q.toString();
    return `/school/website/themes/${theme}/sample${s ? `?${s}` : ''}`;
}

function FullPreview({ src, title, onClose }: { src: string; title: string; onClose: () => void }) {
    const [phone, setPhone] = useState(false);
    useEffect(() => {
        const keys = (e: KeyboardEvent) => e.key === 'Escape' && onClose();
        const before = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        addEventListener('keydown', keys);
        return () => { document.body.style.overflow = before; removeEventListener('keydown', keys); };
    }, [onClose]);

    return (
        <div className="fixed inset-0 z-[100] flex flex-col bg-slate-900" role="dialog" aria-modal="true" aria-label={title}>
            <div className="flex items-center justify-between gap-3 px-3 py-2 text-white">
                <span className="truncate text-sm font-semibold">{title}</span>
                <div className="flex items-center gap-2">
                    <div className="hidden rounded-lg bg-white/10 p-0.5 sm:flex">
                        <button type="button" onClick={() => setPhone(false)} className={`inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs ${!phone ? 'bg-white text-slate-900' : ''}`}><Monitor className="size-3.5" /> Computer</button>
                        <button type="button" onClick={() => setPhone(true)} className={`inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs ${phone ? 'bg-white text-slate-900' : ''}`}><Smartphone className="size-3.5" /> Phone</button>
                    </div>
                    <button type="button" onClick={onClose} className="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-2 text-sm font-medium hover:bg-white/20"><X className="size-4" /> Close</button>
                </div>
            </div>
            <div className="flex min-h-0 flex-1 justify-center bg-slate-200 dark:bg-slate-800">
                <iframe title={title} src={src} className={`h-full border-0 bg-white ${phone ? 'my-4 h-[calc(100%-2rem)] w-[390px] rounded-[2rem] shadow-2xl ring-8 ring-slate-900' : 'w-full'}`} />
            </div>
        </div>
    );
}

export default function WebsiteThemes({ shell, themes, palettes, logoColors = [], current }: Props) {
    const form = useForm({ theme: current.theme, color: current.color ?? '', color2: current.color2 ?? '' });
    const [viewing, setViewing] = useState<string | null>(null);
    const d = form.data;
    const colors = useSettled(`${d.color}|${d.color2}`);
    const [c1, c2] = colors.split('|');
    const chosen = themes.find(t => t.key === d.theme) ?? themes[0];
    const save = () => form.put('/school/website/themes', { preserveScroll: true, onSuccess: () => form.setDefaults() });

    return (
        <WebsiteLayout shell={shell} wide title="Themes" crumb={[{ label: 'Themes' }]}
            description="Each theme is a complete design for your whole website. The pictures below are your own website, drawn live in each theme with the colours you pick.">
            <Panel title="Your colours" description="Pick a ready colour set, or choose your school colours. Every theme uses them. Text colours adjust so writing stays easy to read.">
                <div className="space-y-4 text-sm">
                    {logoColors.length > 0 && shell.canEdit && (
                        <div>
                            <p className="mb-1.5 text-xs text-slate-500">Colours from your school logo. Tap one for the main colour; tap the small circle beside it to use it as the second colour:</p>
                            <div className="flex flex-wrap gap-2">
                                {logoColors.map(c => (
                                    <span key={c} className="inline-flex items-center overflow-hidden rounded-full border border-slate-200 text-xs font-mono dark:border-white/20">
                                        <button type="button" onClick={() => form.setData(v => ({ ...v, color: c }))} className="flex items-center gap-1.5 py-1 pl-1.5 pr-2" title={`Use ${c} as main colour`}>
                                            <i className="size-4 rounded-full border border-white/60" style={{ background: c }} />{c}
                                        </button>
                                        <button type="button" onClick={() => form.setData(v => ({ ...v, color2: c }))} className="border-l border-slate-200 px-2 py-1 dark:border-white/20" title={`Use ${c} as second colour`} aria-label={`Use ${c} as second colour`}>
                                            <i className="block size-3 rounded-full" style={{ background: c }} />
                                        </button>
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}
                    <div className="flex flex-wrap gap-2">
                        {palettes.map(p => {
                            const on = d.color === p.color && d.color2 === p.color2;
                            return (
                                <button key={p.name} type="button" disabled={!shell.canEdit} onClick={() => form.setData(v => ({ ...v, color: p.color, color2: p.color2 }))} aria-pressed={on} title={p.name}
                                    className={`flex items-center gap-2 rounded-full border py-1 pl-1 pr-3 text-xs font-medium ${on ? 'border-indigo-500 ring-2 ring-indigo-500/30' : 'border-slate-200 hover:border-slate-400 dark:border-white/15'}`}>
                                    <span className="flex"><i className="size-5 rounded-full border border-white" style={{ background: p.color }} /><i className="-ml-1.5 size-5 rounded-full border border-white" style={{ background: p.color2 }} /></span>{p.name}
                                </button>
                            );
                        })}
                        {(d.color || d.color2) && shell.canEdit && <button type="button" onClick={() => form.setData(v => ({ ...v, color: '', color2: '' }))} className="rounded-full border border-dashed border-slate-300 px-3 py-1 text-xs text-slate-500 dark:border-white/20">Theme's own colours</button>}
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2">
                        {([['color', 'Main colour', 'Buttons, the big banner, links'], ['color2', 'Second colour', 'Highlights and, in Heritage, the header and footer']] as const).map(([key, label, help]) => (
                            <div key={key} className="space-y-1">
                                <span className="block font-medium">{label} <span className="font-normal text-slate-500">· {help}</span></span>
                                <div className="flex items-center gap-2">
                                    <input type="color" aria-label={label} disabled={!shell.canEdit} value={isHex(d[key]) ? d[key] : chosen[key === 'color' ? 'brand' : 'brand2']} onChange={e => form.setData(key, e.target.value)} className="h-10 w-14 shrink-0 cursor-pointer rounded border border-input" />
                                    <Input disabled={!shell.canEdit} placeholder={`${chosen[key === 'color' ? 'brand' : 'brand2']} (theme's own)`} maxLength={7} value={d[key]} onChange={e => form.setData(key, e.target.value.trim())} />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </Panel>

            <ErrorList errors={form.errors} />

            <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                {themes.map(t => {
                    const active = d.theme === t.key;
                    const saved = current.theme === t.key;
                    const src = sampleUrl(t.key, c1, c2);
                    return (
                        <article key={t.key} className={`overflow-hidden rounded-2xl border-2 bg-white transition-shadow dark:bg-slate-900 ${active ? 'border-indigo-500 shadow-lg shadow-indigo-500/10' : 'border-slate-200 dark:border-white/10'}`}>
                            <div className="relative bg-slate-100 p-3 pb-0 dark:bg-white/5">
                                <div className="overflow-hidden rounded-t-lg border border-b-0 border-slate-300 bg-white shadow-sm dark:border-white/20">
                                    <div className="flex items-center gap-1 border-b border-slate-200 bg-slate-50 px-2 py-1.5 dark:border-white/10 dark:bg-slate-800"><i className="size-2 rounded-full bg-red-400" /><i className="size-2 rounded-full bg-amber-400" /><i className="size-2 rounded-full bg-emerald-400" /></div>
                                    <ScaledSite src={src} title={`${t.label} on a computer`} width={1280} height={210} />
                                </div>
                                <div className="absolute bottom-0 right-3 w-[84px] overflow-hidden rounded-t-[14px] border-[3px] border-b-0 border-slate-900 bg-white shadow-xl">
                                    <ScaledSite src={src} title={`${t.label} on a phone`} width={390} height={150} />
                                </div>
                            </div>
                            <div className="space-y-3 p-4">
                                <div className="flex items-start justify-between gap-2">
                                    <div>
                                        <h2 className="flex items-center gap-2 font-semibold">{t.label} {saved && <Pill tone="good">In use</Pill>}</h2>
                                        <p className="mt-0.5 text-xs text-slate-500">{t.note}</p>
                                    </div>
                                    <span className="flex shrink-0"><i className="size-5 rounded-full border-2 border-white shadow" style={{ background: isHex(c1) ? c1 : t.brand }} /><i className="-ml-2 size-5 rounded-full border-2 border-white shadow" style={{ background: isHex(c2) ? c2 : t.brand2 }} /></span>
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {shell.canEdit && (
                                        <Button type="button" size="sm" disabled={active} onClick={() => form.setData('theme', t.key)} className={active ? '' : 'bg-indigo-600 text-white hover:bg-indigo-700'} variant={active ? 'outline' : 'default'}>
                                            {active ? <><Check className="size-3.5" /> Chosen</> : 'Use this theme'}
                                        </Button>
                                    )}
                                    <Button type="button" size="sm" variant="outline" onClick={() => setViewing(t.key)}><Eye className="size-3.5" /> Full preview</Button>
                                </div>
                            </div>
                        </article>
                    );
                })}
            </div>

            {shell.canEdit && form.isDirty && (
                <SaveBar dirty={form.isDirty}>
                    {form.isDirty && <Button type="button" variant="ghost" onClick={() => form.reset()}>Undo changes</Button>}
                    <Button type="button" disabled={form.processing || !form.isDirty} onClick={save} className="bg-indigo-600 text-white hover:bg-indigo-700">{form.processing ? 'Saving…' : `Save: ${chosen.label}`}</Button>
                </SaveBar>
            )}

            {viewing && <FullPreview src={sampleUrl(viewing, c1, c2)} title={`${themes.find(t => t.key === viewing)?.label} theme with your website`} onClose={() => setViewing(null)} />}
        </WebsiteLayout>
    );
}
