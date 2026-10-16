import { useEffect, useRef, useState } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Panel, Pill } from '@/components/app/kit';
import { WebsiteLayout, selectClass } from '@/components/website/kit';
import type { WebsiteShell } from '@/Types/website';

interface Campaign { id: number; subject: string; preheader: string | null; body: string; audience: string; status: string; total: number; sent: number; failed: number; skipped: number }
interface Props {
    shell: WebsiteShell; campaign: Campaign; audiences: Record<string, string>; audienceCounts: Record<string, number>;
    sender: { from: string; limit: number; sent_today: number } | null; canSend: boolean; autostart: boolean;
    deliveries: { email: string; status: string; error: string | null; sent_at: string | null }[]; email: string;
}

const csrf = () => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
const label: Record<string, string> = { sent: 'Sent', failed: 'Not delivered', unsure: 'Unsure', queued: 'Waiting', sending: 'Sending', skipped: 'Had left' };

export default function NewsletterCampaign({ shell, campaign, audiences, audienceCounts, sender, canSend, autostart, deliveries, email }: Props) {
    const errors = usePage().props.errors as Record<string, string>;
    const draft = campaign.status === 'draft';
    const form = useForm({ subject: campaign.subject, preheader: campaign.preheader ?? '', body: campaign.body, audience: campaign.audience });
    const [progress, setProgress] = useState<{ sent: number; failed: number; remaining: number; status: string } | null>(null);
    const [problem, setProblem] = useState('');
    const [preview, setPreview] = useState('');
    const running = useRef(false);
    const count = audienceCounts[form.data.audience] ?? 0;

    // the page asks for the next few emails again and again until none are left
    async function run() {
        if (running.current) return;
        running.current = true;
        try {
            for (;;) {
                const res = await fetch(`/school/website/newsletter/campaigns/${campaign.id}/step`, { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) { setProblem(res.status === 419 ? 'This page has been open too long. Reload it; sending carries on from where it stopped.' : 'Sending paused because of an error. Reload this page to continue; nobody will get a second copy.'); break; }
                const p = await res.json();
                setProgress(p);
                if (p.remaining === 0) { router.reload({ only: ['campaign', 'deliveries'] }); break; }
            }
        } catch { setProblem('The connection dropped. Reload this page to continue; nobody will get a second copy.'); } finally { running.current = false; }
    }
    useEffect(() => { if (autostart || (campaign.status === 'sending' && canSend)) void run(); /* eslint-disable-next-line react-hooks/exhaustive-deps */ }, []);

    // live preview of the email, drawn by the server so it always matches what readers get
    useEffect(() => {
        const t = setTimeout(async () => {
            const body = new FormData();
            body.append('subject', form.data.subject); body.append('preheader', form.data.preheader); body.append('body', form.data.body);
            const res = await fetch('/school/website/newsletter/preview', { method: 'POST', body, credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': csrf() } });
            if (res.ok) setPreview(await res.text());
        }, 500);
        return () => clearTimeout(t);
    }, [form.data.subject, form.data.preheader, form.data.body]);

    const send = () => {
        if (!confirm(`Send "${form.data.subject}" to ${count} ${count === 1 ? 'person' : 'people'}? This cannot be undone.`)) return;
        form.post(`/school/website/newsletter/campaigns/${campaign.id}`, { preserveScroll: true, onSuccess: () => router.post(`/school/website/newsletter/campaigns/${campaign.id}/send`) });
    };

    const shown = progress ?? { sent: campaign.sent, failed: campaign.failed, remaining: Math.max(0, campaign.total - campaign.sent - campaign.failed - campaign.skipped), status: campaign.status };
    const done = campaign.total > 0 ? campaign.total - shown.remaining : 0;

    return (
        <WebsiteLayout shell={shell} wide title={draft ? 'Write a newsletter' : campaign.subject} crumb={[{ label: 'Extensions', href: '/school/website/extensions' }, { label: 'Newsletter', href: '/school/website/newsletter?tab=newsletters' }, { label: draft ? 'Draft' : 'Sent' }]}
            actions={<Pill tone={campaign.status === 'sent' ? 'good' : campaign.status === 'sending' ? 'warn' : 'neutral'}>{campaign.status === 'sent' ? 'Sent' : campaign.status === 'sending' ? 'Sending' : 'Draft'}</Pill>}>
            {(errors.send || errors.test || errors.subject) && <p role="alert" className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{errors.send || errors.test || errors.subject}</p>}
            {!sender && <p className="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">Set up how emails are sent (Newsletter &gt; Email service) before you send.</p>}

            {campaign.status !== 'draft' && (
                <Panel title={shown.remaining > 0 ? 'Sending…' : 'Report'}>
                    <div className="space-y-3 text-sm">
                        {campaign.total > 0 && <div className="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-white/10" role="progressbar" aria-valuenow={done} aria-valuemax={campaign.total}><div className="h-full bg-emerald-500 transition-all" style={{ width: `${(done / campaign.total) * 100}%` }} /></div>}
                        <p><strong>{shown.sent}</strong> sent{shown.failed > 0 && <>, <strong className="text-red-600">{shown.failed}</strong> not delivered</>}{shown.remaining > 0 && <>, {shown.remaining} still to go. Keep this page open.</>}</p>
                        {problem && <p role="alert" className="text-red-600">{problem}</p>}
                        {shown.remaining === 0 && shown.failed > 0 && canSend && <Button variant="outline" onClick={() => router.post(`/school/website/newsletter/campaigns/${campaign.id}/retry`)}>Try the failed ones again</Button>}
                        <p className="text-xs text-slate-500">"Sent" means the email service accepted it. We do not track opens or clicks yet. Someone who unsubscribed after sending started is skipped.</p>
                    </div>
                </Panel>
            )}

            <div className="grid gap-5 lg:grid-cols-2">
                <div className="space-y-3">
                    <label className="block space-y-1 text-sm"><span className="font-medium">Subject</span><Input disabled={!draft} maxLength={150} value={form.data.subject} onChange={e => form.setData('subject', e.target.value)} /></label>
                    <label className="block space-y-1 text-sm"><span className="font-medium">Preview line (optional)</span><Input disabled={!draft} maxLength={150} value={form.data.preheader} onChange={e => form.setData('preheader', e.target.value)} /><span className="block text-xs text-slate-500">The grey text some inboxes show beside the subject.</span></label>
                    <label className="block space-y-1 text-sm"><span className="font-medium">Message</span><Textarea disabled={!draft} rows={14} value={form.data.body} onChange={e => form.setData('body', e.target.value)} />
                        <span className="block text-xs text-slate-500">Leave a blank line between paragraphs. ## starts a heading, - starts a list, **bold**, [link words](https://address).</span></label>
                    <label className="block space-y-1 text-sm"><span className="font-medium">Send to</span>
                        <select disabled={!draft} className={selectClass} value={form.data.audience} onChange={e => form.setData('audience', e.target.value)}>{Object.entries(audiences).map(([k, l]) => <option key={k} value={k}>{l} ({audienceCounts[k] ?? 0})</option>)}</select></label>
                    {draft && canSend && (
                        <div className="flex flex-wrap gap-2">
                            <Button disabled={form.processing} variant="outline" onClick={() => form.post(`/school/website/newsletter/campaigns/${campaign.id}`, { preserveScroll: true })}>Save draft</Button>
                            <Button disabled={form.processing || !sender} variant="outline" onClick={() => form.post(`/school/website/newsletter/campaigns/${campaign.id}`, { preserveScroll: true, onSuccess: () => router.post(`/school/website/newsletter/campaigns/${campaign.id}/test`, {}, { preserveScroll: true }) })}>Send a test to {email}</Button>
                            <Button disabled={form.processing || !sender || count === 0} onClick={send} className="bg-indigo-600 text-white hover:bg-indigo-700">Send to {count} {count === 1 ? 'person' : 'people'}</Button>
                            <Button variant="ghost" className="text-red-600" onClick={() => confirm('Delete this draft?') && router.post(`/school/website/newsletter/campaigns/${campaign.id}/delete`)}>Delete draft</Button>
                        </div>
                    )}
                    {draft && sender && <p className="text-xs text-slate-500">Sending from {sender.from}. Today: {sender.sent_today} of {sender.limit} emails.</p>}
                </div>
                <div>
                    <p className="mb-1 text-sm font-medium">How it will look</p>
                    <iframe title="Email preview" sandbox="" srcDoc={preview} className="h-[34rem] w-full rounded-xl border border-slate-200 bg-white dark:border-white/10" />
                </div>
            </div>

            {campaign.status !== 'draft' && deliveries.length > 0 && (
                <Panel title="Who it went to">
                    <ul className="max-h-96 divide-y divide-slate-100 overflow-y-auto text-sm dark:divide-white/5">
                        {deliveries.map((d, i) => <li key={i} className="flex flex-wrap items-center justify-between gap-2 py-2"><span className="truncate">{d.email}</span><span className={d.status === 'failed' || d.status === 'unsure' ? 'text-red-600' : 'text-slate-500'}>{label[d.status] ?? d.status}{d.error ? ` — ${d.error}` : ''}</span></li>)}
                    </ul>
                </Panel>
            )}
        </WebsiteLayout>
    );
}
