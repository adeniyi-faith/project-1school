import { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Panel } from '@/components/app/kit';
import { Switch, WebsiteLayout } from '@/components/website/kit';
import type { WebsiteShell } from '@/Types/website';

interface Props {
    shell: WebsiteShell;
    dev: { custom_css: string; ga_id: string; verify_google: string; verify_bing: string };
    webhook: { url: string; events: string[]; is_active: boolean; last_status: number | null; last_error: string | null; last_sent_at: string | null } | null;
    eventNames: Record<string, string>;
    newSecret?: string | null;
    tokens: { id: number; name: string; last_four: string; last_used_at: string | null }[];
    newToken?: string | null;
    urls: { site: string; news: string; api: string; docs: string };
    customThemes: { key: string; label: string; base: string }[];
    customBlocks: { type: string; label: string }[];
}

const row = 'flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3 dark:border-white/10';
const code = 'block break-all rounded-md bg-slate-100 px-2 py-1.5 font-mono text-xs dark:bg-white/10';

function Copy({ value }: { value: string }) {
    const [done, setDone] = useState(false);
    return (
        <div className="flex items-start gap-2">
            <code className={`${code} flex-1`}>{value}</code>
            <Button type="button" variant="outline" size="sm" onClick={() => { void navigator.clipboard?.writeText(value); setDone(true); }}>{done ? 'Copied' : 'Copy'}</Button>
        </div>
    );
}

export default function Developer({ shell, dev, webhook, eventNames, newSecret, tokens, newToken, urls, customThemes, customBlocks }: Props) {
    const canEdit = shell.canEdit;
    const settings = useForm({ custom_css: dev.custom_css, ga_id: dev.ga_id, verify_google: dev.verify_google, verify_bing: dev.verify_bing });
    const hook = useForm({ url: webhook?.url ?? '', events: webhook?.events ?? Object.keys(eventNames), is_active: webhook?.is_active ?? true });
    const tokenForm = useForm({ name: '' });
    const importForm = useForm<{ file: File | null; replace: boolean; apply_settings: boolean }>({ file: null, replace: false, apply_settings: false });

    const toggleEvent = (key: string, on: boolean) => hook.setData('events', on ? [...hook.data.events, key] : hook.data.events.filter(e => e !== key));

    return (
        <WebsiteLayout shell={shell} title="Developer" crumb={[{ label: 'Developer' }]} description="For the person who looks after your website's technical side. You do not need this to run a good website.">
            <Panel title="Your site as data" description="Free, public links your developer can use to show your school's content in another website or an app. The Guides area on the SchoolRuns front page explains them.">
                <div className="space-y-2 text-sm">
                    <p className="text-xs text-slate-500">Whole site: pages, fees, term dates, team and events</p><Copy value={urls.site} />
                    <p className="text-xs text-slate-500">News and events (add ?category=events to filter)</p><Copy value={urls.news} />
                </div>
            </Panel>

            <Panel title="Extra CSS and analytics" description="Small changes to the look, and tools that need a tag on every page.">
                <div className="space-y-3 text-sm">
                    <label className="block space-y-1"><span className="font-medium">Extra CSS</span>
                        <Textarea disabled={!canEdit} rows={8} className="font-mono text-xs" placeholder={'.btn { border-radius: 999px; }'} value={settings.data.custom_css} onChange={e => settings.setData('custom_css', e.target.value)} />
                        <span className="block text-xs text-slate-500">Changes the look of your website only. For safety, tags, @import and anything that could run code are removed when you save.</span></label>
                    <div className="grid gap-3 sm:grid-cols-3">
                        <label className="block space-y-1"><span className="font-medium">Google Analytics ID</span><Input disabled={!canEdit} placeholder="G-ABC123XYZ" value={settings.data.ga_id} onChange={e => settings.setData('ga_id', e.target.value)} /></label>
                        <label className="block space-y-1"><span className="font-medium">Google Search Console code</span><Input disabled={!canEdit} placeholder="the long code from the tag" value={settings.data.verify_google} onChange={e => settings.setData('verify_google', e.target.value)} /></label>
                        <label className="block space-y-1"><span className="font-medium">Bing Webmaster code</span><Input disabled={!canEdit} value={settings.data.verify_bing} onChange={e => settings.setData('verify_bing', e.target.value)} /></label>
                    </div>
                    {Object.values(settings.errors).map((m, i) => <p key={i} className="text-xs text-red-600">{m}</p>)}
                    {canEdit && <Button type="button" disabled={settings.processing} onClick={() => settings.put('/school/website/developer/settings', { preserveScroll: true })} className="bg-indigo-600 text-white hover:bg-indigo-700">Save</Button>}
                </div>
            </Panel>

            <Panel title="Webhook" description="We send a small message to your own address whenever something happens, so a spreadsheet, CRM or automation tool can react at once.">
                <div className="space-y-3 text-sm">
                    {newSecret && <div className="space-y-1 rounded-lg border border-amber-300 bg-amber-50 p-3 dark:bg-amber-500/10"><p className="font-medium">Signing secret (shown only once)</p><Copy value={newSecret} /><p className="text-xs text-slate-600 dark:text-slate-300">Every message carries a signature made with this secret. Check it before you trust a message.</p></div>}
                    <label className="block space-y-1"><span className="font-medium">Address that receives the messages</span><Input disabled={!canEdit} placeholder="https://example.com/hooks/schoolruns" value={hook.data.url} onChange={e => hook.setData('url', e.target.value)} /></label>
                    <div className="space-y-2">{Object.entries(eventNames).map(([k, label]) => (
                        <label key={k} className={row}><span>{label} <span className="block font-mono text-xs text-slate-500">{k}</span></span>
                            <input type="checkbox" className="size-4" disabled={!canEdit} checked={hook.data.events.includes(k)} onChange={e => toggleEvent(k, e.target.checked)} /></label>
                    ))}</div>
                    {webhook && <label className={row}><span>Sending is on</span><Switch label="Webhook on" disabled={!canEdit} on={hook.data.is_active} onChange={v => hook.setData('is_active', v)} /></label>}
                    {webhook?.last_sent_at && <p className={webhook.last_error ? 'text-xs text-red-600' : 'text-xs text-slate-500'}>Last message: {webhook.last_error ?? `delivered (${webhook.last_status})`}</p>}
                    {hook.errors.url && <p className="text-xs text-red-600">{hook.errors.url}</p>}
                    {canEdit && (
                        <div className="flex flex-wrap gap-2">
                            <Button type="button" disabled={hook.processing || !hook.data.url} onClick={() => hook.post('/school/website/developer/webhook', { preserveScroll: true })} className="bg-indigo-600 text-white hover:bg-indigo-700">{webhook ? 'Save webhook' : 'Turn on webhook'}</Button>
                            {webhook && <Button type="button" variant="outline" onClick={() => router.post('/school/website/developer/webhook/test', {}, { preserveScroll: true })}>Send a test</Button>}
                            {webhook && <Button type="button" variant="outline" onClick={() => { if (confirm('Remove the webhook?')) router.post('/school/website/developer/webhook/remove', {}, { preserveScroll: true }); }}>Remove</Button>}
                        </div>
                    )}
                </div>
            </Panel>

            <Panel title="Read-only key" description="Lets your own script read your inquiries. It cannot change anything.">
                <div className="space-y-3 text-sm">
                    {newToken && <div className="space-y-1 rounded-lg border border-amber-300 bg-amber-50 p-3 dark:bg-amber-500/10"><p className="font-medium">Your new key (shown only once)</p><Copy value={newToken} /></div>}
                    <p className="text-xs text-slate-500">Send it as <code>Authorization: Bearer your-key</code> to <code>{urls.api}/inquiries</code>.</p>
                    {tokens.map(t => (
                        <div key={t.id} className={row}><span>{t.name} <span className="block text-xs text-slate-500">ends in {t.last_four} · {t.last_used_at ? `last used ${new Date(t.last_used_at).toLocaleDateString('en-NG')}` : 'never used'}</span></span>
                            {canEdit && <Button type="button" variant="outline" size="sm" onClick={() => { if (confirm(`Revoke "${t.name}"? Anything using it stops working.`)) router.post(`/school/website/developer/tokens/${t.id}/revoke`, {}, { preserveScroll: true }); }}>Revoke</Button>}</div>
                    ))}
                    {canEdit && tokens.length < 5 && (
                        <div className="flex gap-2"><Input placeholder="What is it for? (for example Google Sheet)" maxLength={80} value={tokenForm.data.name} onChange={e => tokenForm.setData('name', e.target.value)} />
                            <Button type="button" disabled={!tokenForm.data.name || tokenForm.processing} onClick={() => tokenForm.post('/school/website/developer/tokens', { preserveScroll: true, onSuccess: () => tokenForm.reset() })}>Make a key</Button></div>
                    )}
                </div>
            </Panel>

            <Panel title="Back up or move your website" description="Download your pages, look and settings as one file. Import it here, or into another school.">
                <div className="space-y-3 text-sm">
                    <a className="inline-flex h-9 items-center rounded-md border border-slate-200 px-3 font-medium hover:bg-slate-50 dark:border-white/10" href="/school/website/export">Download my website</a>
                    {canEdit && (
                        <div className="space-y-2 border-t border-slate-100 pt-3 dark:border-white/10">
                            <input type="file" accept="application/json,.json" onChange={e => importForm.setData('file', e.target.files?.[0] ?? null)} />
                            <label className="flex items-center gap-2"><input type="checkbox" className="size-4" checked={importForm.data.replace} onChange={e => importForm.setData('replace', e.target.checked)} />Replace pages that already use the same address</label>
                            <label className="flex items-center gap-2"><input type="checkbox" className="size-4" checked={importForm.data.apply_settings} onChange={e => importForm.setData('apply_settings', e.target.checked)} />Also apply the look, colours and settings from the file</label>
                            <p className="text-xs text-slate-500">Pictures from another school are left out; add your own afterwards.</p>
                            {importForm.errors.file && <p className="text-xs text-red-600">{importForm.errors.file}</p>}
                            <Button type="button" disabled={!importForm.data.file || importForm.processing} onClick={() => importForm.post('/school/website/import', { forceFormData: true, preserveScroll: true })}>Import</Button>
                        </div>
                    )}
                </div>
            </Panel>

            {(customThemes.length > 0 || customBlocks.length > 0) && (
                <Panel title="Added by your developer" description="Themes and sections installed on this platform.">
                    <ul className="space-y-1 text-sm">{customThemes.map(t => <li key={t.key}>Theme: {t.label} <span className="text-xs text-slate-500">(built on {t.base})</span></li>)}{customBlocks.map(b => <li key={b.type}>Section: {b.label}</li>)}</ul>
                </Panel>
            )}
        </WebsiteLayout>
    );
}
