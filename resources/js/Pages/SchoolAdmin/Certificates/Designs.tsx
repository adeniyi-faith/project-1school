import { useRef, useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { PageHeader, Panel } from '@/components/app/kit';
import { ArrowLeft, Eye, RotateCcw } from 'lucide-react';

type CertType = 'testimonial' | 'transfer';
type Border = 'classic' | 'ornate' | 'modern' | 'simple';

interface Template {
    type: CertType;
    name: string;
    border: Border;
    primary_color: string | null;
    accent_color: string | null;
    title: string;
    body: string;
    closing: string | null;
    show_details: boolean;
    show_qr: boolean;
    show_watermark: boolean;
}

interface Props {
    templates: Template[];
    defaults: Record<CertType, { title: string; body: string; closing: string }>;
    borders: Border[];
    blanks: string[];
    schoolColors: { primary: string; accent: string };
    canEdit: boolean;
}

const BORDER_NAMES: Record<Border, string> = { classic: 'Classic', ornate: 'Ornate', modern: 'Modern', simple: 'Simple' };

export default function CertificateDesigns({ templates, defaults, borders, blanks, schoolColors, canEdit }: Props) {
    const [active, setActive] = useState<CertType>(templates[0]?.type ?? 'testimonial');
    const template = templates.find(t => t.type === active)!;

    return (
        <AppLayout breadcrumbs={[{ label: 'Students', href: '/school/students' }, { label: 'Certificates', href: '/school/certificates' }, { label: 'Designs' }]}>
            <div className="mx-auto max-w-4xl space-y-6 pb-24 md:pb-0">
                <PageHeader
                    title="Certificate designs"
                    description="Choose how testimonials and transfer certificates look and what they say. The logo, stamp and signatures come from the Report Card Designs page."
                    actions={<Link href="/school/certificates" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200"><ArrowLeft className="size-4" /> Certificates</Link>}
                />

                <div className="flex gap-1 border-b border-slate-200 dark:border-slate-800">
                    {templates.map(t => (
                        <button
                            key={t.type}
                            type="button"
                            onClick={() => setActive(t.type)}
                            className={`border-b-2 px-4 py-2 text-sm font-medium ${active === t.type ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700'}`}
                        >{t.name}</button>
                    ))}
                </div>

                {/* key: a fresh form for each certificate type */}
                <Editor key={template.type} template={template} standard={defaults[template.type]} borders={borders} blanks={blanks} schoolColors={schoolColors} canEdit={canEdit} />
            </div>
        </AppLayout>
    );
}

function Editor({ template, standard, borders, blanks, schoolColors, canEdit }: {
    template: Template; standard: { title: string; body: string; closing: string }; borders: Border[]; blanks: string[]; schoolColors: { primary: string; accent: string }; canEdit: boolean;
}) {
    const form = useForm({
        border: template.border,
        primary_color: template.primary_color,
        accent_color: template.accent_color,
        title: template.title,
        body: template.body,
        closing: template.closing ?? '',
        show_details: template.show_details,
        show_qr: template.show_qr,
        show_watermark: template.show_watermark,
    });
    const bodyRef = useRef<HTMLTextAreaElement>(null);
    const primary = form.data.primary_color ?? schoolColors.primary;
    const accent = form.data.accent_color ?? schoolColors.accent;
    const disabled = !canEdit;

    function insert(blank: string) {
        const el = bodyRef.current;
        const start = el?.selectionStart ?? form.data.body.length;
        const end = el?.selectionEnd ?? start;
        form.setData('body', form.data.body.slice(0, start) + blank + form.data.body.slice(end));
        requestAnimationFrame(() => { el?.focus(); el?.setSelectionRange(start + blank.length, start + blank.length); });
    }

    function save(e: React.FormEvent) {
        e.preventDefault();
        form.post(`/school/certificates/designs/${template.type}`, { preserveScroll: true });
    }

    return (
        <form onSubmit={save} className="space-y-6">
            <Panel>
                <p className="mb-3 font-medium text-slate-900 dark:text-white">Border</p>
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    {borders.map(b => (
                        <button
                            key={b}
                            type="button"
                            disabled={disabled}
                            onClick={() => form.setData('border', b)}
                            className={`rounded-lg border p-2 text-left text-sm ${form.data.border === b ? 'border-indigo-500 ring-2 ring-indigo-500/30' : 'border-slate-200 dark:border-white/10'}`}
                        >
                            <BorderThumb border={b} primary={primary} accent={accent} />
                            <span className="mt-1.5 block text-center font-medium">{BORDER_NAMES[b]}</span>
                        </button>
                    ))}
                </div>

                <div className="mt-5 grid gap-4 sm:grid-cols-2">
                    {(['primary_color', 'accent_color'] as const).map(key => {
                        const fallback = key === 'primary_color' ? schoolColors.primary : schoolColors.accent;
                        const value = form.data[key];
                        return (
                            <div key={key} className="space-y-1.5">
                                <Label htmlFor={key}>{key === 'primary_color' ? 'Main colour' : 'Second colour'}</Label>
                                <div className="flex items-center gap-2">
                                    <input id={key} type="color" disabled={disabled} value={value ?? fallback} onChange={e => form.setData(key, e.target.value)} className="h-9 w-12 cursor-pointer rounded border border-slate-200 dark:border-white/10" />
                                    {value ? (
                                        <button type="button" disabled={disabled} onClick={() => form.setData(key, null)} className="text-xs text-indigo-600 hover:underline dark:text-indigo-400">Use the report card colour</button>
                                    ) : (
                                        <span className="text-xs text-slate-500">Same as the report card</span>
                                    )}
                                </div>
                                {form.errors[key] && <p className="text-xs text-red-500">{form.errors[key]}</p>}
                            </div>
                        );
                    })}
                </div>
            </Panel>

            <Panel>
                <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <p className="font-medium text-slate-900 dark:text-white">Wording</p>
                    {canEdit && (
                        <Button type="button" variant="ghost" size="sm" onClick={() => form.setData({ ...form.data, title: standard.title, body: standard.body, closing: standard.closing })}>
                            <RotateCcw className="size-3.5" /> Use the standard wording
                        </Button>
                    )}
                </div>
                <div className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="title">Title</Label>
                        <Input id="title" value={form.data.title} disabled={disabled} maxLength={80} onChange={e => form.setData('title', e.target.value)} />
                        {form.errors.title && <p className="text-xs text-red-500">{form.errors.title}</p>}
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="body">Main text</Label>
                        <Textarea id="body" ref={bodyRef} rows={8} value={form.data.body} disabled={disabled} maxLength={3000} onChange={e => form.setData('body', e.target.value)} />
                        {form.errors.body && <p className="text-xs text-red-500">{form.errors.body}</p>}
                        <p className="text-xs text-slate-500">Leave an empty line to start a new paragraph. Click a blank to add it where the cursor is; it is filled in for each student.</p>
                        <div className="flex flex-wrap gap-1.5">
                            {blanks.map(b => (
                                <button key={b} type="button" disabled={disabled} onClick={() => insert(b)} className="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 dark:bg-white/10 dark:text-slate-200">{b}</button>
                            ))}
                        </div>
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="closing">Closing line</Label>
                        <Input id="closing" value={form.data.closing} disabled={disabled} maxLength={300} onChange={e => form.setData('closing', e.target.value)} />
                    </div>
                </div>
            </Panel>

            <Panel>
                <p className="mb-3 font-medium text-slate-900 dark:text-white">What to show</p>
                <div className="space-y-2.5 text-sm">
                    {([
                        ['show_details', 'A table of details under the text (date of birth, conduct, offices held, clubs ...)'],
                        ['show_qr', 'A QR code that opens the "is this genuine?" check page when scanned'],
                        ['show_watermark', 'A faint school logo behind the text'],
                    ] as const).map(([key, label]) => (
                        <label key={key} className="flex items-start gap-2">
                            <Checkbox checked={form.data[key]} disabled={disabled} onCheckedChange={v => form.setData(key, !!v)} className="mt-0.5" />
                            {label}
                        </label>
                    ))}
                </div>
            </Panel>

            <div className="flex flex-wrap items-center justify-end gap-2">
                <a href={`/school/certificates/designs/${template.type}/preview`} target="_blank" rel="noopener" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                    <Eye className="size-4" /> Preview {form.isDirty ? '(save first to see changes)' : ''}
                </a>
                {canEdit && <Button type="submit" disabled={form.processing || !form.isDirty} className="bg-indigo-600 text-white hover:bg-indigo-700">Save design</Button>}
            </div>
        </form>
    );
}

/** A small drawing of each border style */
function BorderThumb({ border, primary, accent }: { border: Border; primary: string; accent: string }) {
    const lines = (
        <div className="space-y-1 p-2">
            <div className="mx-auto h-1.5 w-1/2 rounded" style={{ background: primary }} />
            <div className="mx-auto h-1 w-1/3 rounded" style={{ background: accent }} />
            <div className="h-0.5 w-full rounded bg-slate-200" />
            <div className="h-0.5 w-full rounded bg-slate-200" />
            <div className="h-0.5 w-4/5 rounded bg-slate-200" />
        </div>
    );
    if (border === 'modern') {
        return (
            <div className="relative aspect-[3/4] overflow-hidden rounded bg-white">
                <div className="absolute inset-y-0 left-0 w-2" style={{ background: primary }} />
                <div className="absolute left-2 right-0 top-0 h-0.5" style={{ background: accent }} />
                <div className="pl-2">{lines}</div>
            </div>
        );
    }
    if (border === 'ornate') {
        return (
            <div className="relative aspect-[3/4] rounded bg-white" style={{ border: `4px solid ${primary}` }}>
                <div className="absolute inset-1 border border-dashed" style={{ borderColor: accent }} />
                {['left-0 top-0', 'right-0 top-0', 'bottom-0 left-0', 'bottom-0 right-0'].map(pos => <div key={pos} className={`absolute size-2 ${pos}`} style={{ background: accent }} />)}
                <div className="p-1">{lines}</div>
            </div>
        );
    }
    if (border === 'simple') {
        return <div className="aspect-[3/4] rounded bg-white" style={{ border: `1px solid ${primary}` }}>{lines}</div>;
    }
    return (
        <div className="aspect-[3/4] rounded bg-white p-0.5" style={{ border: `3px double ${primary}` }}>
            <div className="h-full" style={{ border: `1px solid ${accent}` }}>{lines}</div>
        </div>
    );
}
