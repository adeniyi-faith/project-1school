import { useEffect, useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PageHeader, Panel, Pill } from '@/components/app/kit';
import { PreviewOverlay } from '@/components/app/PreviewOverlay';
import { appendDeep, usePreview } from '@/lib/preview';
import { ArrowLeft, Eye, Plus, Trash2 } from 'lucide-react';

type Orientation = 'portrait' | 'landscape';
type Template = 'classic' | 'modern' | 'band';
interface Design {
    id: number; name: string; orientation: Orientation; template: Template;
    primary_color: string | null; accent_color: string | null; title: string;
    show: Record<string, boolean>; back_text: string | null; signer_id: number | null;
    valid_months: number | null; is_default: boolean;
}
interface Props {
    designs: Design[];
    signers: { id: number; label: string; has_signature: boolean }[];
    switches: string[];
    can: { edit: boolean };
}

const SWITCH_LABELS: Record<string, string> = {
    logo: 'School logo', class: 'Class and arm', house: 'House', date_of_birth: 'Date of birth', blood_group: 'Blood group',
    gender: 'Gender', guardian_phone: "Parent's phone (for emergencies)", address: 'Home address', admission_date: 'Date admitted',
    valid_until: '“Valid until” date', qr: 'QR code (for scanning)', signature: 'Signature', back: 'Print a back side',
};
const TEMPLATES: { value: Template; label: string; hint: string }[] = [
    { value: 'classic', label: 'Classic', hint: 'Coloured header with the school name, photo on the left' },
    { value: 'modern', label: 'Modern', hint: 'Large photo in the middle, clean white body' },
    { value: 'band', label: 'Side band', hint: 'Coloured strip down one side' },
];
const blank = (): Design => ({
    id: 0, name: 'New design', orientation: 'portrait', template: 'modern', primary_color: null, accent_color: null,
    title: 'STUDENT ID CARD', show: {}, back_text: null, signer_id: null, valid_months: null, is_default: false,
});

export default function IdCardDesigns({ designs, signers, switches, can }: Props) {
    const [current, setCurrent] = useState<Design>(designs.find(d => d.is_default) ?? designs[0] ?? blank());
    // a removed design falls back to the default one
    useEffect(() => {
        if (current.id && !designs.some(d => d.id === current.id)) setCurrent(designs.find(d => d.is_default) ?? designs[0] ?? blank());
    }, [designs, current.id]);
    return (
        <AppLayout title="ID card designs">
            <div className="space-y-6">
                <Link href="/school/id-cards" className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                    <ArrowLeft className="size-4" /> ID cards
                </Link>
                <PageHeader title="ID card designs" description="How the cards look. Cards are the standard bank-card size (85.6 × 54 mm), upright or sideways, and print on A4 paper to cut out or on a PVC card printer." />
                <div className="flex flex-wrap gap-2">
                    {designs.map(d => (
                        <button key={d.id} type="button" onClick={() => setCurrent(d)}
                            className={`inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm ${current.id === d.id ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300' : 'border-slate-200 dark:border-white/10'}`}>
                            {d.name} {d.is_default && <Pill tone="info">Default</Pill>}
                        </button>
                    ))}
                    {can.edit && <Button variant="outline" size="sm" onClick={() => setCurrent(blank())}><Plus className="size-4" /> New design</Button>}
                </div>
                <DesignForm key={current.id || 'new'} design={current} signers={signers} switches={switches} canEdit={can.edit} canDelete={designs.length > 1}
                    onCreated={list => setCurrent(list.reduce((a, b) => (b.id > a.id ? b : a), list[0]))} />
            </div>
        </AppLayout>
    );
}

function DesignForm({ design, signers, switches, canEdit, canDelete, onCreated }: { design: Design; signers: Props['signers']; switches: string[]; canEdit: boolean; canDelete: boolean; onCreated: (designs: Design[]) => void }) {
    const form = useForm({
        name: design.name, orientation: design.orientation, template: design.template,
        primary_color: design.primary_color, accent_color: design.accent_color, title: design.title,
        show: Object.fromEntries(switches.map(k => [k, design.show[k] ?? !['gender', 'address', 'admission_date'].includes(k)])) as Record<string, boolean>,
        back_text: design.back_text ?? '', signer_id: design.signer_id ? String(design.signer_id) : '',
        valid_months: design.valid_months ? String(design.valid_months) : '', is_default: design.is_default,
    });
    const [trial, setTrial] = useState<{ school_logo: File | null; photo: File | null }>({ school_logo: null, photo: null });
    const tryOut = usePreview();
    const disabled = !canEdit;
    const err = form.errors as Record<string, string | undefined>;

    function preview() {
        const body = new FormData();
        Object.entries({ ...form.data, ...trial }).forEach(([k, v]) => appendDeep(body, k, v ?? ''));
        void tryOut.show('/school/id-cards/designs/try', body, 'Preview: ID card');
    }

    function save(e: React.FormEvent) {
        e.preventDefault();
        form.transform(d => ({ ...d, signer_id: d.signer_id || null, valid_months: d.valid_months || null }));
        if (design.id) form.put(`/school/id-cards/designs/${design.id}`, { preserveScroll: true });
        else form.post('/school/id-cards/designs', { preserveScroll: true, onSuccess: page => onCreated((page.props as unknown as Props).designs) });
    }

    const colour = (key: 'primary_color' | 'accent_color', label: string) => (
        <div className="space-y-1.5">
            <Label htmlFor={key}>{label}</Label>
            <div className="flex items-center gap-2">
                <input id={key} type="color" disabled={disabled} value={form.data[key] ?? '#1e3a8a'} onChange={e => form.setData(key, e.target.value)} className="h-9 w-14 cursor-pointer rounded-lg border border-slate-200 dark:border-white/10" />
                {form.data[key] ? (
                    !disabled && <button type="button" className="text-xs text-slate-500 underline" onClick={() => form.setData(key, null)}>Use report card colour</button>
                ) : <span className="text-xs text-slate-500">Same as the report card</span>}
            </div>
        </div>
    );

    return (
        <form onSubmit={save} className="space-y-6">
            <PreviewOverlay state={tryOut.state} onClose={tryOut.close} />
            <div className="grid gap-6 lg:grid-cols-2">
                <Panel title="Shape and style">
                    <div className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="d-name">Design name</Label>
                            <Input id="d-name" disabled={disabled} value={form.data.name} onChange={e => form.setData('name', e.target.value)} />
                            {err.name && <p className="text-xs text-red-500">{err.name}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label>Which way up</Label>
                            <div className="flex gap-3">
                                {(['portrait', 'landscape'] as const).map(o => (
                                    <button key={o} type="button" disabled={disabled} onClick={() => form.setData('orientation', o)}
                                        className={`flex flex-1 flex-col items-center gap-2 rounded-lg border p-3 text-sm ${form.data.orientation === o ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-500/10' : 'border-slate-200 dark:border-white/10'}`}>
                                        <span className={`rounded border-2 border-slate-400 ${o === 'portrait' ? 'h-12 w-8' : 'h-8 w-12'}`} />
                                        {o === 'portrait' ? 'Upright (portrait)' : 'Sideways (landscape)'}
                                    </button>
                                ))}
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Layout</Label>
                            <div className="space-y-2">
                                {TEMPLATES.map(t => (
                                    <label key={t.value} className={`flex cursor-pointer items-start gap-2 rounded-lg border p-2.5 text-sm ${form.data.template === t.value ? 'border-indigo-600' : 'border-slate-200 dark:border-white/10'}`}>
                                        <input type="radio" name="template" disabled={disabled} checked={form.data.template === t.value} onChange={() => form.setData('template', t.value)} className="mt-0.5" />
                                        <span><span className="font-medium">{t.label}</span><span className="block text-xs text-slate-500">{t.hint}</span></span>
                                    </label>
                                ))}
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            {colour('primary_color', 'Main colour')}
                            {colour('accent_color', 'Second colour')}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="d-title">Card title</Label>
                            <Input id="d-title" disabled={disabled} value={form.data.title} onChange={e => form.setData('title', e.target.value)} />
                        </div>
                    </div>
                </Panel>

                <div className="space-y-6">
                    <Panel title="What the card shows">
                        <p className="mb-3 text-xs text-slate-500">The photo, name and admission number are always on the card.</p>
                        <div className="grid gap-2 sm:grid-cols-2">
                            {switches.map(k => (
                                <label key={k} className="flex items-center gap-2 text-sm">
                                    <input type="checkbox" disabled={disabled} checked={!!form.data.show[k]} onChange={e => form.setData('show', { ...form.data.show, [k]: e.target.checked })} />
                                    {SWITCH_LABELS[k] ?? k}
                                </label>
                            ))}
                        </div>
                    </Panel>
                    <Panel title="Back, signature and expiry">
                        <div className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="d-back">Words on the back</Label>
                                <textarea id="d-back" rows={3} disabled={disabled} className="w-full rounded-lg border border-slate-200 bg-transparent px-3 py-2 text-sm dark:border-white/10"
                                    value={form.data.back_text} onChange={e => form.setData('back_text', e.target.value)}
                                    placeholder="e.g. This card is the property of the school. If found, please return it to the school or call the number below." />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="d-signer">Whose signature</Label>
                                <select id="d-signer" disabled={disabled} className="h-9 w-full rounded-lg border border-slate-200 bg-transparent px-2 text-sm dark:border-white/10 dark:bg-slate-900"
                                    value={form.data.signer_id} onChange={e => form.setData('signer_id', e.target.value)}>
                                    <option value="">No signature</option>
                                    {signers.map(s => <option key={s.id} value={s.id}>{s.label}{s.has_signature ? '' : ' (no signature uploaded)'}</option>)}
                                </select>
                                <p className="text-xs text-slate-500">Signatures come from the report card designs page.</p>
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="d-valid">Card lasts for (months)</Label>
                                <Input id="d-valid" type="number" min={1} max={72} disabled={disabled} value={form.data.valid_months} onChange={e => form.setData('valid_months', e.target.value)} placeholder="Empty = until the end of this school year" />
                                {err.valid_months && <p className="text-xs text-red-500">{err.valid_months}</p>}
                            </div>
                            <label className="flex items-center gap-2 text-sm">
                                <input type="checkbox" disabled={disabled} checked={form.data.is_default} onChange={e => form.setData('is_default', e.target.checked)} />
                                Use this design unless another is chosen
                            </label>
                        </div>
                    </Panel>
                </div>
            </div>

            <Panel>
                <p className="font-medium text-slate-900 dark:text-white">Try a logo or photo</p>
                <p className="mb-3 mt-1 text-xs text-slate-500">Pick a picture to see how it looks on the preview. Nothing is uploaded or saved.</p>
                <div className="grid gap-3 sm:grid-cols-2">
                    {([['school_logo', 'School logo'], ['photo', 'Student photo']] as const).map(([key, label]) => (
                        <div key={key} className="space-y-1.5">
                            <Label htmlFor={`try-${key}`}>{label}</Label>
                            <input id={`try-${key}`} type="file" accept="image/png,image/jpeg" className="block w-full text-xs" onChange={e => setTrial(t => ({ ...t, [key]: e.target.files?.[0] ?? null }))} />
                        </div>
                    ))}
                </div>
            </Panel>

            <div className="flex flex-wrap items-center justify-end gap-2">
                {canEdit && design.id > 0 && canDelete && (
                    <Button type="button" variant="ghost" className="mr-auto text-red-600" onClick={() => confirm(`Remove the "${design.name}" design?`) && router.delete(`/school/id-cards/designs/${design.id}`)}>
                        <Trash2 className="size-4" /> Remove design
                    </Button>
                )}
                <Button type="button" variant="outline" onClick={preview}><Eye className="size-4" /> Preview</Button>
                {canEdit && <Button type="submit" disabled={form.processing || (design.id > 0 && !form.isDirty)} className="bg-indigo-600 text-white hover:bg-indigo-700">Save design</Button>}
            </div>
        </form>
    );
}
