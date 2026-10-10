import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { EmptyState, PageHeader, Panel, Segmented } from '@/components/app/kit';
import { BatchCounts, sentDate } from '@/components/communication/SentParts';
import { History, Mail, MessageSquare, Send } from 'lucide-react';
import type { MessageBatchRow } from '@/Types';

interface Props {
    batches: { data: MessageBatchRow[]; total: number; from: number | null; to: number | null; prev_page_url: string | null; next_page_url: string | null };
    filters: { channel: string | null };
}

export default function SentMessages({ batches, filters }: Props) {
    const channel = (filters.channel ?? 'all') as 'all' | 'sms' | 'email';
    const newLink = (
        <Link href="/school/communication/blast" className="inline-flex h-9 items-center gap-2 rounded-lg bg-indigo-600 px-3.5 text-sm font-medium text-white hover:bg-indigo-700">
            <Send className="size-4" /> New blast
        </Link>
    );

    return (
        <AppLayout breadcrumbs={[{ label: 'Communication' }, { label: 'Sent messages' }]}>
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader title="Sent messages" description="Every SMS and email blast, and for each one who it reached, who it failed for, and who has no phone number or email on record." actions={newLink} />
                <div className="md:hidden">{newLink}</div>

                <Segmented label="Show" value={channel} onChange={v => router.get('/school/communication/sent', v === 'all' ? {} : { channel: v }, { preserveState: true })}
                    options={[{ value: 'all', label: 'All' }, { value: 'sms', label: 'SMS' }, { value: 'email', label: 'Email' }]} />

                <Panel flush>
                    {batches.data.length === 0 ? (
                        <div className="px-5 pb-5"><EmptyState icon={History} title="Nothing sent yet" text="Messages you send from SMS / Email Blast appear here." /></div>
                    ) : (
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {batches.data.map(b => (
                                <li key={b.id}>
                                    <Link href={`/school/communication/sent/${b.id}`} className="flex gap-3 px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-white/[0.03]">
                                        <span className="mt-0.5 text-slate-400">{b.channel === 'sms' ? <MessageSquare className="size-4" /> : <Mail className="size-4" />}</span>
                                        <span className="min-w-0 flex-1 space-y-1">
                                            <span className="flex flex-wrap items-baseline justify-between gap-x-3">
                                                <span className="font-medium text-slate-900 dark:text-white">{b.subject ?? b.preview}</span>
                                                <span className="text-xs text-slate-500">{sentDate(b.created_at)}</span>
                                            </span>
                                            <span className="block text-sm text-slate-500">{b.audience} · {b.total} {b.total === 1 ? 'person' : 'people'}{b.sent_by ? ` · by ${b.sent_by}` : ''}</span>
                                            <BatchCounts counts={b.counts} />
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                    {(batches.prev_page_url || batches.next_page_url) && (
                        <div className="flex items-center justify-between px-5 py-3 text-sm text-slate-500">
                            <span>{batches.from}–{batches.to} of {batches.total}</span>
                            <div className="flex gap-2">
                                {batches.prev_page_url && <Button variant="outline" size="sm" onClick={() => router.get(batches.prev_page_url!)}>Previous</Button>}
                                {batches.next_page_url && <Button variant="outline" size="sm" onClick={() => router.get(batches.next_page_url!)}>Next</Button>}
                            </div>
                        </div>
                    )}
                </Panel>
            </div>
        </AppLayout>
    );
}
