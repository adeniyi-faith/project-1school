import { Pill } from '@/components/app/kit';
import type { MessageBatchRow } from '@/Types';

/** A date and time like "10 Oct 2026, 18:30" */
export function sentDate(iso: string | null): string {
    return iso ? new Date(iso).toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';
}

export function BatchCounts({ counts }: { counts: MessageBatchRow['counts'] }) {
    return (
        <div className="flex flex-wrap gap-1.5">
            {counts.sent > 0 && <Pill tone="good">{counts.sent} sent</Pill>}
            {counts.failed > 0 && <Pill tone="bad">{counts.failed} failed</Pill>}
            {counts.not_sent > 0 && <Pill tone="warn">{counts.not_sent} no contact</Pill>}
            {counts.queued > 0 && <Pill tone="info">{counts.queued} waiting</Pill>}
        </div>
    );
}
