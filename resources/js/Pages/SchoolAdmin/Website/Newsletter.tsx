import { useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Panel, Pill } from '@/components/app/kit';
import { WebsiteLayout, selectClass } from '@/components/website/kit';
import type { WebsiteShell } from '@/Types/website';

interface Sub { id: number; email: string; name: string | null; status: string; interests: string[]; consented_at: string | null; confirmed_at: string | null; unsubscribed_at: string | null; consent_page: string | null }
interface Paged<T> { data: T[]; links: { url: string | null; label: string; active: boolean }[]; total: number }
interface Campaign { id: number; subject: string; status: string; total: number; sent: number; failed: number; skipped: number; started_at: string | null; created_at: string | null }
interface Field { key: string; label: string; kind: string; secret: boolean; hint?: string; options?: Record<string, string>; default?: string }
interface ProviderInfo { key: string; label: string; blurb: string; fields: Field[] }
interface Settings { mode: 'platform' | 'own'; provider: string; from_name: string; from_email: string; reply_to: string; daily_limit: string; header_text: string; footer_text: string; colour: string; config: Record<string, Record<string, string | null>> }
interface Props {
    shell: WebsiteShell; subscribers: Paged<Sub>; counts: { confirmed: number; pending: number; unsubscribed: number }; filters: { status: string; q: string };
    interests: Record<string, string>; campaigns: Campaign[];
    sender: { source: string; service: string; from: string; reply_to: string | null; limit: number; sent_today: number } | null;
    canSettings: boolean; canEditSettings: boolean; canSend: boolean; settings: Settings; providers: ProviderInfo[];
    platform: { allowed: boolean; service: string | null; from: string | null; usable: boolean }; schoolName: string; consentText: string;
}

const when = (iso: string | null) => (iso ? new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' }) : '—');
const statusLabel: Record<string, string> = { confirmed: 'Confirmed', pending: 'Waiting to confirm', unsubscribed: 'Unsubscribed' };
const tone = (s: string) => (s === 'confirmed' ? 'good' : s === 'pending' ? 'warn' : 'neutral') as 'good' | 'warn' | 'neutral';

export default function Newsletter(props: Props) {
    const { shell, subscribers, counts, filters, interests, campaigns, sender, canSettings, canSend, platform } = props;
    const initial = typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('tab') : null;
    const [tab, setTab] = useState<'subscribers' | 'newsletters' | 'service'>(initial === 'newsletters' ? 'newsletters' : initial === 'service' || !sender ? 'service' : 'subscribers');
    const [q, setQ] = useState(filters.q);
    const create = useForm({ subject: '', body: '' });

    const tabs = [['subscribers', `Subscribers (${counts.confirmed})`], ['newsletters', 'Newsletters'], ...(canSettings ? [['service', 'Email service']] : [])] as [typeof tab, string][];
    const go = (extra: Record<string, string>) => router.get('/school/website/newsletter', { tab: 'subscribers', ...extra }, { preserveState: true, replace: true });

    return (
        <WebsiteLayout shell={shell} title="Newsletter" crumb={[{ label: 'Extensions', href: '/school/website/extensions' }, { label: 'Newsletter' }]}
            description="People who signed up on your website, the newsletters you sent them, and how the emails are sent.">
            {!sender && <p role="status" className="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">Newsletters cannot be sent yet. {canSettings ? 'Choose how emails are sent under Email service. The sign-up box stays hidden on your website until then.' : 'Ask your school administrator to set up the email service.'}</p>}
            <div className="flex gap-1 overflow-x-auto border-b border-slate-200 dark:border-white/10" role="tablist">
                {tabs.map(([k, label]) => (
                    <button key={k} role="tab" aria-selected={tab === k} onClick={() => setTab(k)} className={`-mb-px whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium ${tab === k ? 'border-indigo-600 text-indigo-700 dark:border-indigo-400 dark:text-indigo-300' : 'border-transparent text-slate-500'}`}>{label}</button>
                ))}
            </div>

            {tab === 'subscribers' && (
                <div className="space-y-4">
                    <div className="grid grid-cols-3 gap-3 text-center">
                        {(['confirmed', 'pending', 'unsubscribed'] as const).map(k => (
                            <button key={k} onClick={() => go({ status: filters.status === k ? '' : k })} className={`rounded-xl border p-3 text-left ${filters.status === k ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10' : 'border-slate-200 dark:border-white/10'}`}>
                                <p className="text-2xl font-semibold">{counts[k]}</p><p className="text-xs text-slate-500">{statusLabel[k]}</p>
                            </button>
                        ))}
                    </div>
                    <p className="text-xs text-slate-500">Only people who confirmed from the email we sent them ever receive a newsletter.</p>
                    <form className="flex flex-wrap gap-2" onSubmit={e => { e.preventDefault(); go({ q, status: filters.status }); }}>
                        <Input className="max-w-xs" placeholder="Search name or email" aria-label="Search subscribers" value={q} onChange={e => setQ(e.target.value)} />
                        <Button type="submit" variant="outline">Search</Button>
                        <a href="/school/website/newsletter/export" className="inline-flex h-9 items-center rounded-lg border border-slate-200 px-3 text-sm font-medium hover:bg-slate-50 dark:border-white/10">Download as Excel</a>
                    </form>
                    {subscribers.data.length === 0 ? <p className="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-white/15">{filters.q || filters.status ? 'Nobody matches.' : 'Nobody has signed up yet. Add the "Newsletter sign-up" section to a page, such as your home page.'}</p> : (
                        <ul className="divide-y divide-slate-100 rounded-xl border border-slate-200 dark:divide-white/5 dark:border-white/10">
                            {subscribers.data.map(s => (
                                <li key={s.id} className="flex flex-wrap items-center justify-between gap-2 p-3 text-sm">
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">{s.name ? `${s.name} · ` : ''}{s.email}</p>
                                        <p className="text-xs text-slate-500">Agreed {when(s.consented_at)}{s.confirmed_at ? ` · confirmed ${when(s.confirmed_at)}` : ''}{s.unsubscribed_at ? ` · left ${when(s.unsubscribed_at)}` : ''}{s.interests.length ? ` · ${s.interests.map(i => interests[i] ?? i).join(', ')}` : ''}</p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Pill tone={tone(s.status)}>{statusLabel[s.status] ?? s.status}</Pill>
                                        {canSend && <button className="text-xs text-red-600 underline" onClick={() => confirm(`Remove ${s.email} from the list for good? This also removes the record of their consent.`) && router.post(`/school/website/newsletter/subscribers/${s.id}/remove`, {}, { preserveScroll: true })}>Remove</button>}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                    {subscribers.links.length > 3 && <div className="flex flex-wrap gap-1">{subscribers.links.map((l, i) => l.url ? <Link key={i} href={l.url} className={`rounded px-2.5 py-1 text-sm ${l.active ? 'bg-indigo-600 text-white' : 'border border-slate-200 dark:border-white/10'}`}>{l.label.replace('&laquo;', '«').replace('&raquo;', '»')}</Link> : null)}</div>}
                </div>
            )}

            {tab === 'newsletters' && (
                <div className="space-y-4">
                    {canSend && sender && (
                        <Panel title="Write a newsletter" description="Start with a subject and some text. You can preview it, send yourself a test, and choose who gets it on the next screen.">
                            <form className="space-y-3" onSubmit={e => { e.preventDefault(); create.post('/school/website/newsletter/campaigns'); }}>
                                <Input placeholder="Subject" aria-label="Subject" maxLength={150} value={create.data.subject} onChange={e => create.setData('subject', e.target.value)} />
                                <Button type="submit" disabled={create.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Start writing</Button>
                            </form>
                        </Panel>
                    )}
                    {campaigns.length === 0 ? <p className="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-white/15">You have not written a newsletter yet.</p> : (
                        <ul className="divide-y divide-slate-100 rounded-xl border border-slate-200 dark:divide-white/5 dark:border-white/10">
                            {campaigns.map(c => (
                                <li key={c.id}><Link href={`/school/website/newsletter/campaigns/${c.id}`} className="flex flex-wrap items-center justify-between gap-2 p-3 text-sm hover:bg-slate-50 dark:hover:bg-white/5">
                                    <div className="min-w-0"><p className="truncate font-medium">{c.subject}</p>
                                        <p className="text-xs text-slate-500">{c.status === 'draft' ? `Draft · ${when(c.created_at)}` : `${c.sent} sent${c.failed ? `, ${c.failed} not delivered` : ''}${c.skipped ? `, ${c.skipped} had left` : ''} · ${when(c.started_at)}`}</p></div>
                                    <Pill tone={c.status === 'sent' ? 'good' : c.status === 'sending' ? 'warn' : 'neutral'}>{c.status === 'sent' ? 'Sent' : c.status === 'sending' ? 'Sending' : 'Draft'}</Pill>
                                </Link></li>
                            ))}
                        </ul>
                    )}
                </div>
            )}

            {tab === 'service' && canSettings && <ServiceTab {...props} />}
        </WebsiteLayout>
    );
}

function ServiceTab({ settings, providers, platform, sender, canEditSettings, schoolName, consentText }: Props) {
    const errors = usePage().props.errors as Record<string, string>;
    const form = useForm({ ...settings });
    const provider = providers.find(p => p.key === form.data.provider) ?? providers[0];
    const setConfig = (key: string, value: string) => form.setData('config', { ...form.data.config, [form.data.provider]: { ...form.data.config[form.data.provider], [key]: value } });
    const own = form.data.mode === 'own';
    const disabled = !canEditSettings;

    return (
        <div className="space-y-4">
            {sender && <p className="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">Sending now through <strong>{sender.service}</strong> ({sender.source === 'platform' ? 'the platform\'s service' : 'your own account'}), from {sender.from}. Today: {sender.sent_today} of {sender.limit} emails.</p>}
            <Panel title="Which service sends your emails?">
                <div className="space-y-3 text-sm">
                    <label className={`flex gap-3 rounded-lg border p-3 ${!own ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10' : 'border-slate-200 dark:border-white/10'} ${!platform.allowed ? 'opacity-60' : ''}`}>
                        <input type="radio" name="mode" disabled={disabled || !platform.allowed} checked={!own} onChange={() => form.setData('mode', 'platform')} />
                        <span><strong>Use the platform's service</strong> (nothing to set up)<span className="block text-xs text-slate-500">{platform.allowed ? `Emails go out through ${platform.service}, with ${schoolName} as the sender name. Replies reach your school's email.` : 'Not part of your school\'s plan.'}</span></span>
                    </label>
                    <label className={`flex gap-3 rounded-lg border p-3 ${own ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10' : 'border-slate-200 dark:border-white/10'}`}>
                        <input type="radio" name="mode" disabled={disabled} checked={own} onChange={() => form.setData('mode', 'own')} />
                        <span><strong>Use my own account</strong><span className="block text-xs text-slate-500">Send from your own address through Brevo or any mailbox. Best for a big list or your own sender address.</span></span>
                    </label>
                    {own && (
                        <div className="space-y-3 rounded-lg border border-slate-200 p-3 dark:border-white/10">
                            <div className="grid gap-2 sm:grid-cols-2">
                                {providers.map(p => (
                                    <label key={p.key} className={`flex cursor-pointer gap-2 rounded-lg border p-3 ${form.data.provider === p.key ? 'border-indigo-500' : 'border-slate-200 dark:border-white/10'}`}>
                                        <input type="radio" name="provider" disabled={disabled} checked={form.data.provider === p.key} onChange={() => form.setData('provider', p.key)} />
                                        <span><strong>{p.label}</strong>{p.key === 'brevo' && <span className="ml-1.5 rounded bg-emerald-100 px-1.5 text-xs text-emerald-800">Recommended</span>}<span className="block text-xs text-slate-500">{p.blurb}</span></span>
                                    </label>
                                ))}
                            </div>
                            <div className="grid gap-3 sm:grid-cols-2">
                                {provider.fields.map(f => (
                                    <label key={f.key} className="block space-y-1"><span className="font-medium">{f.label}</span>
                                        {f.kind === 'select' ? (
                                            <select disabled={disabled} className={selectClass} value={form.data.config[provider.key]?.[f.key] || f.default || ''} onChange={e => setConfig(f.key, e.target.value)}>{Object.entries(f.options ?? {}).map(([v, l]) => <option key={v} value={v}>{l}</option>)}</select>
                                        ) : (
                                            <Input disabled={disabled} type={f.kind === 'password' ? 'password' : f.kind === 'number' ? 'number' : 'text'} autoComplete="off" placeholder={f.secret && settings.config[provider.key]?.[f.key] ? `Saved (${settings.config[provider.key][f.key]}). Type to replace.` : ''}
                                                value={f.secret && form.data.config[provider.key]?.[f.key]?.startsWith('••••') ? '' : (form.data.config[provider.key]?.[f.key] ?? f.default ?? '')} onChange={e => setConfig(f.key, e.target.value)} />
                                        )}
                                        {f.hint && <span className="block text-xs text-slate-500">{f.hint}</span>}</label>
                                ))}
                            </div>
                            <label className="block space-y-1"><span className="font-medium">Send from this email address</span><Input disabled={disabled} type="email" value={form.data.from_email} onChange={e => form.setData('from_email', e.target.value)} placeholder="news@yourschool.com" /><span className="block text-xs text-slate-500">It must be an address you have verified with your service, or the emails will be refused.</span></label>
                            <label className="block space-y-1"><span className="font-medium">Most you want to send in one day</span><Input disabled={disabled} type="number" min={1} value={form.data.daily_limit} onChange={e => form.setData('daily_limit', e.target.value)} placeholder="300" /><span className="block text-xs text-slate-500">A safety limit. Brevo's free plan allows about 300 a day.</span></label>
                        </div>
                    )}
                    {(errors.config || errors.mode || errors.from_email) && <p role="alert" className="text-sm text-red-600">{errors.config || errors.mode || errors.from_email}</p>}
                </div>
            </Panel>

            <Panel title="What your readers see" description="These apply whichever service you use.">
                <div className="grid gap-3 text-sm sm:grid-cols-2">
                    <label className="block space-y-1"><span className="font-medium">Sender name</span><Input disabled={disabled} maxLength={100} placeholder={schoolName} value={form.data.from_name} onChange={e => form.setData('from_name', e.target.value)} /><span className="block text-xs text-slate-500">Shown in the inbox as who the email is from. Empty means your school's name.</span></label>
                    <label className="block space-y-1"><span className="font-medium">Replies go to</span><Input disabled={disabled} type="email" value={form.data.reply_to} onChange={e => form.setData('reply_to', e.target.value)} placeholder="Your school's email" /><span className="block text-xs text-slate-500">When a parent presses Reply.</span></label>
                    <label className="block space-y-1"><span className="font-medium">Heading at the top of each email</span><Input disabled={disabled} maxLength={100} placeholder={schoolName} value={form.data.header_text} onChange={e => form.setData('header_text', e.target.value)} /></label>
                    <label className="block space-y-1"><span className="font-medium">Colour of the top band</span>
                        <span className="flex items-center gap-2"><input disabled={disabled} type="color" aria-label="Pick a colour" className="h-10 w-12 rounded border" value={/^#[0-9a-fA-F]{6}$/.test(form.data.colour) ? form.data.colour : '#1e3a8a'} onChange={e => form.setData('colour', e.target.value)} /><Input disabled={disabled} placeholder="Your website colour" value={form.data.colour} onChange={e => form.setData('colour', e.target.value)} /></span>
                        {errors.colour && <span className="block text-xs text-red-600">{errors.colour}</span>}</label>
                    <label className="block space-y-1 sm:col-span-2"><span className="font-medium">Extra line at the bottom (optional)</span><Input disabled={disabled} maxLength={300} value={form.data.footer_text} onChange={e => form.setData('footer_text', e.target.value)} placeholder="For example: Reg. No. 12345" /><span className="block text-xs text-slate-500">Your school logo, name and address are added automatically, plus the unsubscribe link.</span></label>
                </div>
                <p className="mt-3 text-xs text-slate-500">Sign-up tick box wording (fixed, recorded with every sign-up): “{consentText}”</p>
            </Panel>
            {canEditSettings && <Button disabled={form.processing} onClick={() => form.post('/school/website/newsletter/settings', { preserveScroll: true })} className="bg-indigo-600 text-white hover:bg-indigo-700">{form.processing ? 'Checking…' : own ? 'Save and check it works' : 'Save'}</Button>}
        </div>
    );
}
