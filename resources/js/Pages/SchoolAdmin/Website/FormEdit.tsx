import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Panel } from '@/components/app/kit';
import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react';
import { ErrorList, SaveBar, Switch, WebsiteLayout, selectClass } from '@/components/website/kit';
import type { WebsiteShell } from '@/Types/website';

interface ShowIf { key: string; value: string }
interface FieldRow { key: string; label: string; type: string; required: boolean; help: string; placeholder: string; options: string[]; half: boolean; show_if: ShowIf | null; maps_to: string }
interface MapInfo { key: string; label: string; types: string[] }
interface Consent { text: string; required: boolean }
interface Preset { label: string; text: string; required: boolean }
interface Props {
    shell: WebsiteShell; canManage: boolean; types: Record<string, string>; choiceTypes: string[]; limits: { fields: number; options: number; consents: number };
    presets: Preset[]; shareUrl: string; answers: number; newsletterReady: boolean; maps: MapInfo[]; controls: string[]; canInquiry: boolean; fileInfo: { types: string; mb: number; max: number };
    form: { id: number; name: string; intro: string; fields: FieldRow[]; consents: Consent[]; button_text: string; success_message: string; notify_email: string; newsletter_optin: boolean; send_to_inquiries: boolean; active: boolean };
}
type Row = FieldRow & { optionsText: string };

const row = 'flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3 text-sm dark:border-white/10';
const textTypes = ['text', 'textarea', 'email', 'phone', 'number'];

export default function FormEdit({ shell, form: f, types, choiceTypes, limits, presets, shareUrl, answers, newsletterReady, maps, controls, canInquiry, fileInfo, canManage }: Props) {
    const [copied, setCopied] = useState(false);
    const form = useForm({
        name: f.name, intro: f.intro, button_text: f.button_text, success_message: f.success_message, notify_email: f.notify_email, newsletter_optin: f.newsletter_optin, send_to_inquiries: f.send_to_inquiries, active: f.active,
        fields: f.fields.map(x => ({ ...x, optionsText: x.options.join('\n') })) as Row[], consents: f.consents as Consent[],
    });
    const d = form.data;
    const locked = !canManage;

    const setField = (i: number, patch: Partial<Row>) => form.setData('fields', d.fields.map((x, n) => (n === i ? { ...x, ...patch } : x)));
    const move = (i: number, by: number) => {
        const next = [...d.fields]; const j = i + by;
        if (j < 0 || j >= next.length) return;
        [next[i], next[j]] = [next[j], next[i]]; form.setData('fields', next);
    };
    const addField = () => form.setData('fields', [...d.fields, { key: '', label: '', type: 'text', required: false, help: '', placeholder: '', options: [], half: false, show_if: null, maps_to: '', optionsText: '' }]);
    const setConsent = (i: number, patch: Partial<Consent>) => form.setData('consents', d.consents.map((x, n) => (n === i ? { ...x, ...patch } : x)));

    const choicesOf = (x: Row) => x.optionsText.split(/\r?\n/).map(o => o.trim()).filter(Boolean);
    const controlsFor = (i: number) => d.fields.slice(0, i).filter(c => c.key && controls.includes(c.type));
    const save = () => {
        form.transform(data => ({ ...data, fields: data.fields.map(x => ({ ...x, options: x.optionsText.split(/\r?\n/) })) }));
        form.post(`/school/website/forms/${f.id}`, {
            preserveScroll: true,
            // take the saved questions back (new ones now have their permanent keys, which conditions point at)
            onSuccess: page => {
                const saved = (page.props as unknown as { form: Props['form'] }).form;
                const fields = saved.fields.map(x => ({ ...x, optionsText: x.options.join('\n') }));
                form.setData('fields', fields);
                form.setDefaults({ ...form.data, fields });
            },
        });
    };
    const copy = async () => { try { await navigator.clipboard.writeText(shareUrl); setCopied(true); setTimeout(() => setCopied(false), 2000); } catch { /* the address is shown, it can be copied by hand */ } };

    return (
        <WebsiteLayout shell={shell} title={f.name} crumb={[{ label: 'Extensions', href: '/school/website/extensions' }, { label: 'Forms', href: '/school/website/forms' }, { label: 'Edit' }]}
            description="Change the questions, the privacy ticks and what happens when someone sends the form.">
            <ErrorList errors={form.errors as Record<string, string>} />

            <Panel title="About this form">
                <div className="space-y-3 text-sm">
                    <label className="block space-y-1"><span className="font-medium">Name</span><Input disabled={locked} maxLength={120} value={d.name} onChange={e => form.setData('name', e.target.value)} /></label>
                    <label className="block space-y-1"><span className="font-medium">A sentence above the questions</span><Input disabled={locked} maxLength={400} value={d.intro} onChange={e => form.setData('intro', e.target.value)} /></label>
                    <label className={row}><span>Open for answers <span className="block text-xs text-slate-500">When closed, the form disappears from your website and cannot be sent. Answers already received are kept.</span></span><Switch label="Form open" disabled={locked} on={d.active} onChange={v => form.setData('active', v)} /></label>
                </div>
            </Panel>

            <Panel title="Questions" description={`Up to ${limits.fields} questions. Visitors see them in this order.`}>
                <div className="space-y-3">
                    {d.fields.map((x, i) => (
                        <div key={x.key || `new-${i}`} className="space-y-3 rounded-xl border border-slate-200 p-3 dark:border-white/10">
                            <div className="grid gap-2 sm:grid-cols-[1fr_12rem]">
                                <label className="block space-y-1 text-sm"><span className="font-medium">Question {i + 1}</span><Input disabled={locked} maxLength={120} placeholder="For example: Your name" value={x.label} onChange={e => setField(i, { label: e.target.value })} /></label>
                                <label className="block space-y-1 text-sm"><span className="font-medium">Kind of answer</span>
                                    <select disabled={locked} className={selectClass} value={x.type} onChange={e => setField(i, { type: e.target.value })}>{Object.entries(types).map(([k, label]) => <option key={k} value={k}>{label}</option>)}</select>
                                </label>
                            </div>
                            {choiceTypes.includes(x.type) && (
                                <label className="block space-y-1 text-sm"><span className="font-medium">Choices (one on each line, at least two)</span>
                                    <Textarea disabled={locked} rows={4} value={x.optionsText} onChange={e => setField(i, { optionsText: e.target.value })} />
                                </label>
                            )}
                            <div className="grid gap-2 sm:grid-cols-2">
                                <label className="block space-y-1 text-sm"><span className="font-medium">Help text (optional)</span><Input disabled={locked} maxLength={200} value={x.help} onChange={e => setField(i, { help: e.target.value })} /></label>
                                {textTypes.includes(x.type) && <label className="block space-y-1 text-sm"><span className="font-medium">Example inside the box (optional)</span><Input disabled={locked} maxLength={80} value={x.placeholder} onChange={e => setField(i, { placeholder: e.target.value })} /></label>}
                            </div>
                            {x.type === 'file' && <p className="text-xs text-slate-500">Visitors can upload {fileInfo.types} files up to {fileInfo.mb} MB. Files are kept privately and only staff who can see forms can open them. They are not scanned for viruses, so open them with care. A form can have up to {fileInfo.max} file questions.</p>}
                            {(() => {
                                const options = controlsFor(i);
                                const ctl = d.fields.find(c => c.key && c.key === x.show_if?.key);
                                const values = ctl ? choicesOf(ctl) : [];
                                return (
                                    <div className="space-y-2 text-sm">
                                        <label className="block space-y-1"><span className="font-medium">Show this question only when</span>
                                            <select disabled={locked} className={selectClass} value={x.show_if?.key ?? ''} onChange={e => { const key = e.target.value; const c = d.fields.find(z => z.key === key); setField(i, { show_if: key ? { key, value: c && c.type !== 'tick' ? (choicesOf(c)[0] ?? '') : 'yes' } : null }); }}>
                                                <option value="">Always show it</option>
                                                {options.map(c => <option key={c.key} value={c.key}>{c.label || 'Untitled question'}</option>)}
                                            </select>
                                        </label>
                                        {x.show_if && ctl && ctl.type !== 'tick' && (
                                            <label className="block space-y-1"><span className="font-medium">is answered with</span>
                                                <select disabled={locked} className={selectClass} value={x.show_if.value} onChange={e => setField(i, { show_if: { key: x.show_if!.key, value: e.target.value } })}>
                                                    {values.map(v => <option key={v} value={v}>{v}</option>)}
                                                </select>
                                            </label>
                                        )}
                                        {x.show_if && ctl && ctl.type === 'tick' && <p className="text-xs text-slate-500">…is ticked.</p>}
                                        {options.length === 0 && !x.show_if && <p className="text-xs text-slate-500">To use this, add a drop-down, pick-one, tick or single-tick question above this one and press Save first.</p>}
                                    </div>
                                );
                            })()}
                            {d.send_to_inquiries && (
                                <label className="block space-y-1 text-sm"><span className="font-medium">Goes into the Admissions inquiry as</span>
                                    <select disabled={locked} className={selectClass} value={x.maps_to} onChange={e => setField(i, { maps_to: e.target.value })}>
                                        <option value="">Only written in the notes</option>
                                        {maps.filter(m => m.types.includes(x.type)).map(m => <option key={m.key} value={m.key}>{m.label}</option>)}
                                    </select>
                                </label>
                            )}
                            <div className="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                                <label className="flex items-center gap-2">{x.type === 'tick' ? 'Must be ticked' : 'Must be answered'} <Switch label="Required" disabled={locked} on={x.required} onChange={v => setField(i, { required: v })} /></label>
                                {x.type !== 'textarea' && x.type !== 'checkboxes' && <label className="flex items-center gap-2">Half width on large screens <Switch label="Half width" disabled={locked} on={x.half} onChange={v => setField(i, { half: v })} /></label>}
                                {!locked && (
                                    <span className="ml-auto flex gap-1">
                                        <Button type="button" size="icon-sm" variant="ghost" aria-label="Move up" disabled={i === 0} onClick={() => move(i, -1)}><ArrowUp className="size-4" /></Button>
                                        <Button type="button" size="icon-sm" variant="ghost" aria-label="Move down" disabled={i === d.fields.length - 1} onClick={() => move(i, 1)}><ArrowDown className="size-4" /></Button>
                                        <Button type="button" size="icon-sm" variant="ghost" aria-label={`Remove question ${i + 1}`} onClick={() => form.setData('fields', d.fields.filter((_, n) => n !== i))}><Trash2 className="size-4 text-red-600" /></Button>
                                    </span>
                                )}
                            </div>
                        </div>
                    ))}
                    {!locked && d.fields.length < limits.fields && <Button type="button" variant="outline" onClick={addField}><Plus className="size-4" /> Add a question</Button>}
                </div>
            </Panel>

            <Panel title="Privacy ticks" description={`Boxes the visitor must tick before sending (up to ${limits.consents}). We keep the exact wording they agreed to with every answer.`}>
                <div className="space-y-3">
                    {d.consents.map((c, i) => (
                        <div key={i} className="space-y-2 rounded-xl border border-slate-200 p-3 dark:border-white/10">
                            <Textarea aria-label={`Privacy tick ${i + 1}`} disabled={locked} rows={2} maxLength={300} value={c.text} onChange={e => setConsent(i, { text: e.target.value })} />
                            <div className="flex flex-wrap items-center justify-between gap-2 text-sm">
                                <label className="flex items-center gap-2">The form cannot be sent without it <Switch label="Required" disabled={locked} on={c.required} onChange={v => setConsent(i, { required: v })} /></label>
                                {!locked && <Button type="button" size="sm" variant="ghost" onClick={() => form.setData('consents', d.consents.filter((_, n) => n !== i))}><Trash2 className="size-3.5 text-red-600" /> Remove</Button>}
                            </div>
                        </div>
                    ))}
                    {!locked && d.consents.length < limits.consents && (
                        <div className="space-y-2">
                            <p className="text-xs text-slate-500">Add one with a tap, then change the wording if you like. The words "Privacy Policy" become a link to your Privacy Policy page.</p>
                            <div className="flex flex-wrap gap-2">
                                {presets.map(p => <Button key={p.label} type="button" size="sm" variant="outline" onClick={() => form.setData('consents', [...d.consents, { text: p.text, required: p.required }])}><Plus className="size-3.5" /> {p.label}</Button>)}
                                <Button type="button" size="sm" variant="outline" onClick={() => form.setData('consents', [...d.consents, { text: '', required: false }])}><Plus className="size-3.5" /> Write my own</Button>
                            </div>
                        </div>
                    )}
                </div>
            </Panel>

            <Panel title="After someone sends it">
                <div className="space-y-3 text-sm">
                    <label className="block space-y-1"><span className="font-medium">Button words</span><Input disabled={locked} maxLength={40} value={d.button_text} onChange={e => form.setData('button_text', e.target.value)} /></label>
                    <label className="block space-y-1"><span className="font-medium">Thank-you message</span><Input disabled={locked} maxLength={300} value={d.success_message} onChange={e => form.setData('success_message', e.target.value)} /></label>
                    <label className="block space-y-1"><span className="font-medium">Also email each answer to (optional)</span><Input disabled={locked} type="email" maxLength={150} placeholder="office@yourschool.com" value={d.notify_email} onChange={e => form.setData('notify_email', e.target.value)} />
                        <span className="block text-xs text-slate-500">Staff who can see forms always get a bell notice. This adds an email to one address you choose.</span></label>
                    <label className={row}><span>Also add each answer to Admissions inquiries <span className="block text-xs text-slate-500">{canInquiry ? 'The office then follows it up in the usual place, with a bell notice. Mark which question is the parent\'s name and which is the phone number (both must be answered). Other answers are written in the inquiry notes.' : 'Only people who can see students can switch this on.'}</span></span><Switch label="Add answers to Admissions" disabled={locked || !canInquiry} on={d.send_to_inquiries} onChange={v => form.setData('send_to_inquiries', v)} /></label>
                    <label className={row}><span>Offer "Also send me the newsletter" <span className="block text-xs text-slate-500">{newsletterReady ? 'Shows an unticked box. It needs an "Email address" question. People are added only after they confirm from an email.' : 'Needs the Newsletter extension switched on and an email service set up.'}</span></span><Switch label="Offer the newsletter" disabled={locked || !newsletterReady} on={d.newsletter_optin} onChange={v => form.setData('newsletter_optin', v)} /></label>
                </div>
            </Panel>

            <Panel title="Show it on your website">
                <div className="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                    <p><strong>On a page:</strong> open Pages, edit a page, press "Add a section" and choose "Custom form", then pick this form.</p>
                    <p><strong>On its own address,</strong> to share in a message or an email:</p>
                    <div className="flex flex-wrap items-center gap-2"><code className="break-all rounded bg-slate-100 px-2 py-1 text-xs dark:bg-white/10">{shareUrl}</code><Button type="button" size="sm" variant="outline" onClick={copy}>{copied ? 'Copied' : 'Copy'}</Button></div>
                    <p className="text-xs text-slate-500">To change how it looks, use the Extra CSS box in Developer. Every form is wrapped in <code>.sf</code> and this one also has <code>.sf-{f.id}</code>; each question has <code>.sf-field</code> and <code>data-field</code>.</p>
                </div>
            </Panel>

            {canManage && (
                <SaveBar dirty={form.isDirty}>
                    <Button type="button" variant="outline" className="text-red-600" onClick={() => { if (confirm(`Delete "${f.name}" and its ${answers} answer${answers === 1 ? '' : 's'}? This cannot be undone. Download the answers first if you need them.`)) router.post(`/school/website/forms/${f.id}/delete`); }}>Delete form</Button>
                    <Link href={`/school/website/forms/${f.id}/answers`} className="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 px-3 text-sm font-medium dark:border-white/10">Answers ({answers})</Link>
                    <Button type="button" disabled={form.processing || !form.isDirty} onClick={save} className="bg-indigo-600 text-white hover:bg-indigo-700">{form.processing ? 'Saving…' : 'Save'}</Button>
                </SaveBar>
            )}
        </WebsiteLayout>
    );
}
