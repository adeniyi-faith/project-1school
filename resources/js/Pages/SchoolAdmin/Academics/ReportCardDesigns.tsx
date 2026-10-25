import { useEffect, useRef, useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { PageHeader, Panel, Pill } from '@/components/app/kit';
import { cn } from '@/lib/utils';
import { appendDeep, usePreview } from '@/lib/preview';
import { PreviewOverlay } from '@/components/app/PreviewOverlay';
import { LogoTip } from '@/components/app/LogoTip';
import { ArrowDown, ArrowUp, Eye, Plus, Trash2 } from 'lucide-react';
import type { ReportCardDesignRow, ReportCardSignerRow } from '@/Types';

interface ClassRow { id: number; name: string; report_card_design_id: number | null }

interface Props {
    designs: ReportCardDesignRow[];
    classes: ClassRow[];
    templates: ReportCardDesignRow['template'][];
    writers: { value: string; label: string }[];
    optionLabels: Record<'header' | 'results' | 'extras', Record<string, string>>;
    canEdit: boolean;
}

type SignerForm = Omit<ReportCardSignerRow, 'has_signature'> & { signature: File | null; remove_signature: boolean; has_signature: boolean };
type DesignForm = {
    name: string; is_default: boolean; template: ReportCardDesignRow['template'];
    primary_color: string; accent_color: string; font_size: ReportCardDesignRow['font_size']; paper: ReportCardDesignRow['paper'];
    term_title: string; session_title: string; footer_note: string;
    options: Record<string, boolean>;
    stamp: File | null; remove_stamp: boolean;
    signers: SignerForm[];
};

const TEMPLATE_INFO: Record<ReportCardDesignRow['template'], { label: string; text: string }> = {
    classic: { label: 'Classic', text: 'Logo on the left, coloured table headings, boxed tables' },
    modern: { label: 'Modern', text: 'Centred header, light tables with coloured lines' },
    compact: { label: 'Compact', text: 'Smaller and tighter, fits many subjects on one page' },
    banner: { label: 'Banner', text: 'Bold coloured header band, striped tables, strong headings' },
    elegant: { label: 'Elegant', text: 'Framed page, serif lettering, thin lines. Looks like a certificate' },
};
const SIGNER_TITLES = ['Class Teacher', 'Form Master', 'Form Mistress', 'Head Teacher', 'Headmaster', 'Headmistress', 'Principal', 'Vice Principal', 'Academic Director', 'Head of Department', 'Proprietor', 'Proprietress', 'Administrator', 'Guidance Counsellor'];
const SIZE_ITEMS = [{ value: 'small', label: 'Small' }, { value: 'normal', label: 'Normal' }, { value: 'large', label: 'Large' }];
const PAPER_ITEMS = [{ value: 'a4', label: 'A4' }, { value: 'letter', label: 'Letter' }];
const GROUP_TITLES = { header: 'Top of the card', results: 'Results', extras: 'Other parts' } as const;

function toForm(d: ReportCardDesignRow): DesignForm {
    return {
        name: d.name, is_default: d.is_default, template: d.template, primary_color: d.primary_color, accent_color: d.accent_color,
        font_size: d.font_size, paper: d.paper, term_title: d.term_title, session_title: d.session_title, footer_note: d.footer_note ?? '',
        options: { ...d.options }, stamp: null, remove_stamp: false,
        signers: d.signers.map(s => ({ ...s, name: s.name ?? '', signature: null, remove_signature: false })),
    };
}

export default function ReportCardDesigns({ designs, classes, templates, writers, optionLabels, canEdit }: Props) {
    const params = new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '');
    const [selectedId, setSelectedId] = useState<number>(Number(params.get('design')) || designs[0]?.id);
    const selected = designs.find(d => d.id === selectedId) ?? designs[0];
    const [newOpen, setNewOpen] = useState(false);
    const newForm = useForm<{ name: string; copy_from: string }>({ name: '', copy_from: String(selected?.id ?? '') });

    const usage = (id: number) => classes.filter(c => c.report_card_design_id === id || (c.report_card_design_id === null && designs.find(d => d.id === id)?.is_default)).length;

    return (
        <AppLayout breadcrumbs={[{ label: 'Academic' }, { label: 'Report card designs' }]}>
            <div className="space-y-6">
                <PageHeader
                    title="Report card designs"
                    description="Choose how report cards look: layout, colours, what shows, and who comments and signs. Give different designs to different classes, for example one for Primary and one for Secondary."
                    actions={canEdit && <Button onClick={() => setNewOpen(true)} className="bg-indigo-600 text-white hover:bg-indigo-700"><Plus className="size-4" /> New design</Button>}
                />
                {canEdit && <div className="md:hidden"><Button onClick={() => setNewOpen(true)} className="bg-indigo-600 text-white hover:bg-indigo-700"><Plus className="size-4" /> New design</Button></div>}

                <div className="grid gap-6 lg:grid-cols-[16rem_1fr]">
                    <div className="space-y-2">
                        {designs.map(d => (
                            <button key={d.id} type="button" onClick={() => setSelectedId(d.id)}
                                className={cn('w-full rounded-xl border p-3 text-left transition-colors', d.id === selected?.id
                                    ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-500/10'
                                    : 'border-slate-200 bg-white hover:bg-slate-50 dark:border-white/10 dark:bg-slate-900 dark:hover:bg-white/[0.04]')}>
                                <div className="flex items-center justify-between gap-2">
                                    <span className="font-medium text-slate-900 dark:text-white">{d.name}</span>
                                    {d.is_default && <Pill tone="info">Default</Pill>}
                                </div>
                                <CardThumb designId={d.id} className="mt-2 max-w-[9rem]" />
                                <div className="mt-2 flex items-center gap-2 text-xs text-slate-500">
                                    <span className="size-3.5 rounded-full" style={{ background: d.primary_color }} />
                                    <span className="size-3.5 rounded-full" style={{ background: d.accent_color }} />
                                    {TEMPLATE_INFO[d.template].label} · {usage(d.id)} class{usage(d.id) === 1 ? '' : 'es'}
                                </div>
                            </button>
                        ))}
                    </div>

                    {selected && <DesignEditor key={selected.id} design={selected} classes={classes} designs={designs} templates={templates} writers={writers} optionLabels={optionLabels} canEdit={canEdit} />}
                </div>
            </div>

            <Dialog open={newOpen} onOpenChange={setNewOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>New design</DialogTitle></DialogHeader>
                    <form onSubmit={e => { e.preventDefault(); newForm.post('/school/academics/report-card-designs', { onSuccess: () => { setNewOpen(false); newForm.reset('name'); } }); }} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="new-name">Name</Label>
                            <Input id="new-name" value={newForm.data.name} onChange={e => newForm.setData('name', e.target.value)} placeholder="e.g. Primary section" />
                            {newForm.errors.name && <p className="text-xs text-red-500">{newForm.errors.name}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label>Start from a copy of</Label>
                            <Select items={designs.map(d => ({ value: String(d.id), label: d.name }))} value={newForm.data.copy_from} onValueChange={v => newForm.setData('copy_from', v ?? '')}>
                                <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>{designs.map(d => <SelectItem key={d.id} value={String(d.id)}>{d.name}</SelectItem>)}</SelectContent>
                            </Select>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setNewOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={newForm.processing || !newForm.data.name.trim()} className="bg-indigo-600 text-white hover:bg-indigo-700">Create</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

function DesignEditor({ design, classes, designs, templates, writers, optionLabels, canEdit }: {
    design: ReportCardDesignRow; classes: ClassRow[]; designs: ReportCardDesignRow[]; templates: ReportCardDesignRow['template'][];
    writers: Props['writers']; optionLabels: Props['optionLabels']; canEdit: boolean;
}) {
    const form = useForm<DesignForm>(toForm(design));
    const { data, setData, errors } = form;
    const base = `/school/academics/report-card-designs/${design.id}`;
    const [classIds, setClassIds] = useState<number[]>(classes.filter(c => c.report_card_design_id === design.id).map(c => c.id));
    const [confirmDelete, setConfirmDelete] = useState(false);
    // Preview what is on screen now, even before saving. Pictures picked but not saved show too.
    const [trialLogo, setTrialLogo] = useState<File | null>(null);
    const tryOut = usePreview();
    function preview(type: 'term' | 'session') {
        const body = new FormData();
        appendDeep(body, 'type', type);
        (['template', 'primary_color', 'accent_color', 'font_size', 'paper', 'term_title', 'session_title', 'footer_note', 'options', 'remove_stamp'] as const).forEach(k => appendDeep(body, k, data[k] ?? ''));
        if (data.stamp) body.append('stamp', data.stamp);
        if (trialLogo) body.append('school_logo', trialLogo);
        data.signers.forEach((s, i) => {
            (['id', 'label', 'name', 'has_comment', 'remove_signature'] as const).forEach(k => appendDeep(body, `signers[${i}][${k}]`, s[k]));
            if (s.signature) body.append(`signers[${i}][signature]`, s.signature);
        });
        void tryOut.show(`${base}/try`, body, type === 'session' ? 'Preview: full-year card' : 'Preview: term card');
    }


    // After a save, the page data changes; start the form again from what was saved
    useEffect(() => { form.setDefaults(toForm(design)); form.reset(); }, [design]); // eslint-disable-line react-hooks/exhaustive-deps

    function setSigner(i: number, patch: Partial<SignerForm>) {
        setData('signers', data.signers.map((s, j) => (j === i ? { ...s, ...patch } : s)));
    }
    function moveSigner(i: number, by: number) {
        const list = [...data.signers];
        const [item] = list.splice(i, 1);
        list.splice(i + by, 0, item);
        setData('signers', list);
    }

    function save(e: React.FormEvent) {
        e.preventDefault();
        form.transform(d => ({
            ...d,
            signers: d.signers.map(s => ({ id: s.id, label: s.label, name: s.name, has_comment: s.has_comment, writer_permission: s.writer_permission, signature: s.signature, remove_signature: s.remove_signature })),
        }));
        form.post(base, { forceFormData: true, preserveScroll: true });
    }

    const err = (key: string) => (errors as Record<string, string>)[key];
    const others = (c: ClassRow) => c.report_card_design_id !== null && c.report_card_design_id !== design.id ? designs.find(d => d.id === c.report_card_design_id)?.name : null;
    const disabled = !canEdit;

    return (
        <form onSubmit={save} className="space-y-6">
            <PreviewOverlay state={tryOut.state} onClose={tryOut.close} />
            {/* One part at a time. keepMounted keeps picked-but-unsaved pictures while the tab is hidden. */}
            <Tabs defaultValue="look" className="gap-5">
                <TabsList className="w-full sm:w-auto">
                    <TabsTrigger value="look">Look</TabsTrigger>
                    <TabsTrigger value="shows">What shows</TabsTrigger>
                    <TabsTrigger value="signers">Comments &amp; signatures</TabsTrigger>
                    <TabsTrigger value="classes">Classes</TabsTrigger>
                </TabsList>

            <TabsContent value="look" keepMounted className="space-y-6">
            <Panel title="Look">
                <div className="space-y-5">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="d-name">Name</Label>
                            <Input id="d-name" value={data.name} disabled={disabled} onChange={e => setData('name', e.target.value)} />
                            {err('name') && <p className="text-xs text-red-500">{err('name')}</p>}
                        </div>
                        <label className="flex items-center gap-2 self-end pb-2 text-sm">
                            <Checkbox checked={data.is_default} disabled={disabled || design.is_default} onCheckedChange={v => setData('is_default', !!v)} />
                            Default design (used by every class not given another one)
                        </label>
                    </div>

                    <div className="space-y-1.5">
                        <Label>Layout</Label>
                        <div className="grid gap-3 sm:grid-cols-3">
                            {templates.map(t => (
                                <button key={t} type="button" disabled={disabled} onClick={() => setData('template', t)}
                                    className={cn('rounded-xl border p-3 text-left', data.template === t ? 'border-indigo-600 ring-2 ring-indigo-600/20' : 'border-slate-200 dark:border-white/10')}>
                                    <CardThumb designId={design.id} template={t} primary={data.primary_color} accent={data.accent_color} />
                                    <p className="mt-2 text-sm font-medium text-slate-900 dark:text-white">{TEMPLATE_INFO[t].label}</p>
                                    <p className="text-xs text-slate-500">{TEMPLATE_INFO[t].text}</p>
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-4">
                        {(['primary_color', 'accent_color'] as const).map(key => (
                            <div key={key} className="space-y-1.5">
                                <Label htmlFor={key}>{key === 'primary_color' ? 'Main colour' : 'Second colour'}</Label>
                                <div className="flex gap-2">
                                    <input id={key} type="color" value={data[key]} disabled={disabled} onChange={e => setData(key, e.target.value)} className="h-9 w-12 cursor-pointer rounded border border-slate-200 bg-white p-0.5 dark:border-white/10" />
                                    <Input value={data[key]} disabled={disabled} onChange={e => setData(key, e.target.value)} className="font-mono" maxLength={7} />
                                </div>
                                {err(key) && <p className="text-xs text-red-500">{err(key)}</p>}
                            </div>
                        ))}
                        <div className="space-y-1.5">
                            <Label>Text size</Label>
                            <Select items={SIZE_ITEMS} value={data.font_size} disabled={disabled} onValueChange={v => setData('font_size', (v ?? 'normal') as DesignForm['font_size'])}>
                                <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>{SIZE_ITEMS.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Paper</Label>
                            <Select items={PAPER_ITEMS} value={data.paper} disabled={disabled} onValueChange={v => setData('paper', (v ?? 'a4') as DesignForm['paper'])}>
                                <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>{PAPER_ITEMS.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="term-title">Term card title</Label>
                            <Input id="term-title" value={data.term_title} disabled={disabled} onChange={e => setData('term_title', e.target.value)} placeholder="Report Card" />
                            <p className="text-xs text-slate-500">Printed after the term name, e.g. "First Term {data.term_title || 'Report Card'}".</p>
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="session-title">Full-year card title</Label>
                            <Input id="session-title" value={data.session_title} disabled={disabled} onChange={e => setData('session_title', e.target.value)} placeholder="Full-Year Report Card" />
                        </div>
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="footer">Note at the bottom (optional)</Label>
                            <Input id="footer" value={data.footer_note} disabled={disabled} onChange={e => setData('footer_note', e.target.value)} placeholder="e.g. This report is not valid without the school stamp." />
                        </div>
                    </div>
                </div>
            </Panel>
            </TabsContent>

            <TabsContent value="shows" keepMounted className="space-y-6">
            <Panel title="What shows on the card">
                <div className="grid gap-6 sm:grid-cols-3">
                    {(Object.keys(optionLabels) as (keyof Props['optionLabels'])[]).map(group => (
                        <div key={group}>
                            <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{GROUP_TITLES[group]}</p>
                            <div className="space-y-2">
                                {Object.entries(optionLabels[group]).map(([key, label]) => (
                                    <label key={key} className="flex items-start gap-2 text-sm">
                                        <Checkbox checked={!!data.options[key]} disabled={disabled} onCheckedChange={v => setData('options', { ...data.options, [key]: !!v })} className="mt-0.5" />
                                        {label}
                                    </label>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
                {data.options.show_photo && <p className="mt-4 text-xs text-slate-500">Add photos on each student's profile, or many at once from the Students page (name each file after the admission number).</p>}
            </Panel>
            </TabsContent>

            <TabsContent value="signers" keepMounted className="space-y-6">
            <Panel title="Who comments and signs" description="Use the titles your school uses. Each one gets a comment box (if ticked) and a signature line. Upload a signature image to print it on every card.">
                <div className="space-y-3">
                    <datalist id="signer-titles">{SIGNER_TITLES.map(t => <option key={t} value={t} />)}</datalist>
                    {data.signers.map((s, i) => (
                        <div key={s.id ?? `new-${i}`} className="rounded-xl border border-slate-200 p-3 dark:border-white/10">
                            <div className="grid gap-3 md:grid-cols-[1fr_1fr_14rem]">
                                <div className="space-y-1">
                                    <Label className="text-xs">Title</Label>
                                    <Input list="signer-titles" value={s.label} disabled={disabled} onChange={e => setSigner(i, { label: e.target.value })} placeholder="e.g. Head Teacher" />
                                    {err(`signers.${i}.label`) && <p className="text-xs text-red-500">{err(`signers.${i}.label`)}</p>}
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-xs">Name printed under the signature (optional)</Label>
                                    <Input value={s.name ?? ''} disabled={disabled} onChange={e => setSigner(i, { name: e.target.value })} placeholder="e.g. Mrs A. Bello" />
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-xs">Who writes the comment</Label>
                                    <Select items={writers} value={s.writer_permission} disabled={disabled || !s.has_comment} onValueChange={v => setSigner(i, { writer_permission: v ?? 'marks.entry' })}>
                                        <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                        <SelectContent>{writers.map(w => <SelectItem key={w.value} value={w.value}>{w.label}</SelectItem>)}</SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div className="mt-3 flex flex-wrap items-center gap-4 text-sm">
                                <label className="flex items-center gap-2">
                                    <Checkbox checked={s.has_comment} disabled={disabled} onCheckedChange={v => setSigner(i, { has_comment: !!v })} /> Writes a comment
                                </label>
                                <div className="flex min-w-0 max-w-full flex-wrap items-center gap-2">
                                    <span className="text-slate-500">Signature:</span>
                                    {s.has_signature && !s.remove_signature && !s.signature && <Pill tone="good">Uploaded</Pill>}
                                    {!disabled && <input type="file" accept="image/png,image/jpeg" className="w-full max-w-full text-xs sm:w-auto" onChange={e => setSigner(i, { signature: e.target.files?.[0] ?? null, remove_signature: false })} />}
                                    {s.has_signature && !s.remove_signature && !disabled && <Button type="button" variant="ghost" size="sm" onClick={() => setSigner(i, { remove_signature: true, signature: null })}>Remove</Button>}
                                </div>
                                {err(`signers.${i}.signature`) && <p className="text-xs text-red-500">{err(`signers.${i}.signature`)}</p>}
                                {!disabled && (
                                    <div className="ml-auto flex gap-1">
                                        <Button type="button" variant="ghost" size="icon" disabled={i === 0} onClick={() => moveSigner(i, -1)} aria-label="Move up"><ArrowUp className="size-4" /></Button>
                                        <Button type="button" variant="ghost" size="icon" disabled={i === data.signers.length - 1} onClick={() => moveSigner(i, 1)} aria-label="Move down"><ArrowDown className="size-4" /></Button>
                                        <Button type="button" variant="ghost" size="icon" onClick={() => setData('signers', data.signers.filter((_, j) => j !== i))} aria-label="Remove signer"><Trash2 className="size-4 text-red-500" /></Button>
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}
                    {!disabled && data.signers.length < 6 && (
                        <Button type="button" variant="outline" size="sm" onClick={() => setData('signers', [...data.signers, { id: null, label: '', name: '', has_comment: true, writer_permission: 'results.publish', signature: null, remove_signature: false, has_signature: false }])}>
                            <Plus className="size-4" /> Add someone
                        </Button>
                    )}
                    {design.signers.some(s => !data.signers.find(x => x.id === s.id)) && (
                        <p className="text-xs text-amber-700 dark:text-amber-400">Removing someone also removes the comments already written under their title.</p>
                    )}

                    <div className="flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3 text-sm dark:border-white/[0.06]">
                        <span className="text-slate-500">School stamp:</span>
                        {design.has_stamp && !data.remove_stamp && !data.stamp && <Pill tone="good">Uploaded</Pill>}
                        {!disabled && <input type="file" accept="image/png,image/jpeg" className="text-xs" onChange={e => setData(d => ({ ...d, stamp: e.target.files?.[0] ?? null, remove_stamp: false }))} />}
                        {design.has_stamp && !data.remove_stamp && !disabled && <Button type="button" variant="ghost" size="sm" onClick={() => setData(d => ({ ...d, remove_stamp: true, stamp: null }))}>Remove</Button>}
                        {err('stamp') && <p className="text-xs text-red-500">{err('stamp')}</p>}
                    </div>

                    <LogoTip />
                    <div className="flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3 text-sm dark:border-white/[0.06]">
                        <span className="text-slate-500">Try another school logo:</span>
                        <input type="file" accept="image/png,image/jpeg" className="text-xs" onChange={e => setTrialLogo(e.target.files?.[0] ?? null)} />
                    </div>
                    <p className="text-xs text-slate-500">The preview shows everything chosen on this page, including a stamp, signature or logo you have picked but not saved. Previewing never saves or uploads anything, so you can try pictures freely, even on the demo.</p>
                </div>
            </Panel>
            </TabsContent>

            <TabsContent value="classes" keepMounted className="space-y-6">
            <Panel title="Classes using this design" description={design.is_default ? 'Classes not ticked here, and not given another design, also use this one because it is the default.' : 'Classes not ticked use the default design.'}>
                <div className="flex flex-wrap gap-3">
                    {classes.map(c => (
                        <label key={c.id} className="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-1.5 text-sm dark:border-white/10">
                            <Checkbox checked={classIds.includes(c.id)} disabled={disabled}
                                onCheckedChange={v => setClassIds(ids => v ? [...ids, c.id] : ids.filter(x => x !== c.id))} />
                            {c.name}
                            {others(c) && !classIds.includes(c.id) && <span className="text-xs text-slate-400">({others(c)})</span>}
                        </label>
                    ))}
                </div>
                {canEdit && (
                    <div className="mt-4 flex justify-end">
                        <Button type="button" variant="outline" onClick={() => router.post(`${base}/classes`, { class_ids: classIds }, { preserveScroll: true })}>Save classes</Button>
                    </div>
                )}
            </Panel>
            </TabsContent>
            </Tabs>

            <div className="flex flex-wrap items-center justify-between gap-2">
                {canEdit && !design.is_default ? (
                    <Button type="button" variant="ghost" className="text-red-600" onClick={() => setConfirmDelete(true)}><Trash2 className="size-4" /> Delete design</Button>
                ) : <span />}
                <div className="flex flex-wrap gap-2">
                    <Button type="button" variant="outline" onClick={() => preview('term')}><Eye className="size-4" /> Preview term card</Button>
                    <Button type="button" variant="outline" onClick={() => preview('session')}><Eye className="size-4" /> Preview full year</Button>
                    {canEdit && <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Save design</Button>}
                </div>
            </div>

            <p className="text-sm text-slate-500">
                Comments are written on each class's results page under <Link href="/school/results" className="text-indigo-600 hover:underline dark:text-indigo-400">Term results</Link> → Report cards.
            </p>

            <Dialog open={confirmDelete} onOpenChange={setConfirmDelete}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Delete "{design.name}"?</DialogTitle></DialogHeader>
                    <p className="text-sm text-slate-500">Its classes will use the default design. Comments written under its signers are removed.</p>
                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={() => setConfirmDelete(false)}>Keep it</Button>
                        <Button type="button" className="bg-red-600 text-white hover:bg-red-700" onClick={() => router.delete(base, { onFinish: () => setConfirmDelete(false) })}>Delete</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </form>
    );
}

/**
 * A small live picture of a real sample card, made by the same page that makes the real report card,
 * so it shows exactly the layout, colours and parts. Colours are picked up a moment after they stop changing.
 */
function CardThumb({ designId, template, primary, accent, className }: { designId: number; template?: string; primary?: string; accent?: string; className?: string }) {
    const box = useRef<HTMLDivElement>(null);
    const [scale, setScale] = useState(0.3);
    const [colours, setColours] = useState({ primary, accent });
    useEffect(() => {
        const t = setTimeout(() => setColours({ primary, accent }), 500);
        return () => clearTimeout(t);
    }, [primary, accent]);
    useEffect(() => {
        const el = box.current;
        if (!el) return;
        const fit = () => setScale(el.clientWidth / 794);
        fit();
        const ro = new ResizeObserver(fit);
        ro.observe(el);
        return () => ro.disconnect();
    }, []);

    const q = new URLSearchParams();
    if (template) q.set('template', template);
    if (template && colours.primary && /^#[0-9a-fA-F]{6}$/.test(colours.primary)) q.set('primary_color', colours.primary);
    if (template && colours.accent && /^#[0-9a-fA-F]{6}$/.test(colours.accent)) q.set('accent_color', colours.accent);

    return (
        <div ref={box} className={cn('relative w-full overflow-hidden rounded-md border border-slate-200 bg-white dark:border-white/10', className)} style={{ aspectRatio: '794 / 1123' }}>
            <iframe title="Sample report card" loading="lazy" tabIndex={-1} aria-hidden="true" scrolling="no"
                src={`/school/academics/report-card-designs/${designId}/thumb?${q.toString()}`}
                className="pointer-events-none absolute left-0 top-0 border-0"
                style={{ width: 794, height: 1123, transform: `scale(${scale})`, transformOrigin: 'top left' }} />
        </div>
    );
}
