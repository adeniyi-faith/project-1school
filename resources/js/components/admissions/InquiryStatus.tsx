import { Pill } from '@/components/app/kit';
import type { InquiryStatus } from '@/Types';

const TONE = { new: 'info', follow_up: 'warn', accepted: 'good', admitted: 'good', dropped: 'neutral' } as const;

export const INQUIRY_STATUS_LABELS: Record<InquiryStatus, string> = {
    new: 'New', follow_up: 'Following up', accepted: 'Place offered', admitted: 'Enrolled', dropped: 'Closed',
};

export function InquiryStatusPill({ status }: { status: InquiryStatus }) {
    return <Pill tone={TONE[status] ?? 'neutral'}>{INQUIRY_STATUS_LABELS[status] ?? status}</Pill>;
}
