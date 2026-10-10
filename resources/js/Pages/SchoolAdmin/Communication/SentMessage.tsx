import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { EmptyState, PageHeader, Panel, Pill, Segmented, StatStrip } from '@/components/app/kit';
import { ArrowLeft, RotateCcw, Search, Users } from 'lucide-react';
import type { MessageBatchRow, MessageDeliveryRow } from '@/Types';
import { sentDate } from '@/components/communication/SentParts';

interface Props {
    batch: MessageBatchRow & { body: string };
    deliveries: { data: MessageDeliveryRow[]; total: number; from: number | null; to: number | null; prev_page_url: string | null; next_page_url: string | null };
    filters: { status: string | null; search: string };
    canRetry: boolean;
}

type StatusFilter = 'all' | MessageDeliveryRow['status'];

const STATUS: Record<MessageDeliveryRow['status'], { label: string; tone: 'good' | 'bad' | 'warn' | 'info' }> = {
    sent: { label: 'Sent', tone: 'good' },
    failed: { label: 'Failed', tone: 'bad' },
    not_sent: { label: 'No contact', tone: 'warn' },
    queued: { label: 'Waiting', tone: 'info' },
};

export default function SentMessage({ batch, deliveries, filters, canRetry }: Props) {
    const [search, setSearch] = useState(filters.search);
    const status = (filters.status ?? 'all') as StatusFilter;
    const url = `/school/communication/sent/${batch.id}`;
    const c = batch.counts;
    const sms = batch.channel === 'sms';

    function apply(next: { status?: StatusFilter; search?: string }) {
        const s = next.status ?? status;
        const q = next.search ?? search;
        router.get(url, { ...(s !== 'all' ? { status: s } : {}), ...(q ? { search: q } : {}) }, { preserveState: true, preserveScroll: true });
    }

    const backLink = (
        <Link href="/school/communication/sent" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
            <ArrowLeft className="size-4" /> Sent messages
        </Link>
    );

    return (
        <AppLayout breadcrumbs={[{ label: 'Communication' }, { label: 'Sent messages', href: '/school/communication/sent' }, { label: batch.subject ?? (sms ? 'SMS' : 'Email') }]}>
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader
                    title={batch.subject ?? (sms ? 'SMS blast' : 'Email blast')}
                    description={`${sms ? 'SMS' : 'Email'} to ${batch.audience.toLowerCase()}, ${sentDate(batch.created_at)}${batch.sent_by ? ` by ${batch.sent_by}` : ''}.`}
                    actions={backLink}
                />
                <div className="md:hidden">{backLink}</div>

                <StatStrip items={[
                    { label: 'Sent', value: c.sent, tone: 'good' },
                    { label: 'Failed', value: c.failed, tone: c.failed ? 'bad' : 'default', hint: c.failed ? 'Can be tried again' : undefined },
                    { label: 'No phone or email', value: c.not_sent, tone: c.not_sent ? 'warn' : 'default', hint: c.not_sent ? 'Add it on their record' : undefined },
                    { label: 'Waiting', value: c.queued },
                ]} />

                {c.failed > 0 && canRetry && (
                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
                        <span>{c.failed} {c.failed === 1 ? 'message' : 'messages'} did not go out. The reason is shown next to each person.</span>
                        <Button size="sm" variant="outline" onClick={() => router.post(`${url}/retry`, {}, { preserveScroll: true })}>
                            <RotateCcw className="size-3.5" /> Try the failed ones again
                        </Button>
                    </div>
                )}

                <Panel title="Message">
                    <p className="whitespace-pre-line text-sm text-slate-700 dark:text-slate-300">{batch.body}</p>
                </Panel>

                <div className="flex flex-wrap items-center gap-2">
                    <Segmented label="Show" value={status} onChange={v => apply({ status: v })} options={[
                        { value: 'all', label: 'Everyone', count: batch.total },
                        { value: 'sent', label: 'Sent', count: c.sent },
                        { value: 'failed', label: 'Failed', count: c.failed },
                        { value: 'not_sent', label: 'No contact', count: c.not_sent },
                    ]} />
                    <form onSubmit={e => { e.preventDefault(); apply({ search }); }} className="relative min-w-52 flex-1">
                        <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                        <Input value={search} onChange={e => setSearch(e.target.value)} placeholder={`Search by name or ${sms ? 'phone number' : 'email'}`} className="pl-9" />
                    </form>
                </div>

                <Panel flush>
                    {deliveries.data.length === 0 ? (
                        <div className="px-5 pb-5"><EmptyState icon={Users} title="Nobody here" text="Nobody matches this filter." /></div>
                    ) : (
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {deliveries.data.map(d => (
                                <li key={d.id} className="flex flex-wrap items-start justify-between gap-x-4 gap-y-1 px-5 py-3 text-sm">
                                    <div className="min-w-0">
                                        <p className="font-medium text-slate-900 dark:text-white">{d.recipient_name}</p>
                                        <p className="text-slate-500">{d.to ?? '—'}{d.attempts > 1 ? ` · tried ${d.attempts} times` : ''}</p>
                                        {d.error && <p className="mt-0.5 text-xs text-slate-500">{d.error}</p>}
                                    </div>
                                    <div className="text-right">
                                        <Pill tone={STATUS[d.status].tone}>{STATUS[d.status].label}</Pill>
                                        {d.sent_at && <p className="mt-1 text-xs text-slate-500">{sentDate(d.sent_at)}</p>}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                    {(deliveries.prev_page_url || deliveries.next_page_url) && (
                        <div className="flex items-center justify-between px-5 py-3 text-sm text-slate-500">
                            <span>{deliveries.from}–{deliveries.to} of {deliveries.total}</span>
                            <div className="flex gap-2">
                                {deliveries.prev_page_url && <Button variant="outline" size="sm" onClick={() => router.get(deliveries.prev_page_url!)}>Previous</Button>}
                                {deliveries.next_page_url && <Button variant="outline" size="sm" onClick={() => router.get(deliveries.next_page_url!)}>Next</Button>}
                            </div>
                        </div>
                    )}
                </Panel>
            </div>
        </AppLayout>
    );
}
