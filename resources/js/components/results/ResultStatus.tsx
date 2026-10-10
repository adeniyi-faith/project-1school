import { Pill } from '@/components/app/kit';
import type { ResultStatus } from '@/Types';

const STATUS: Record<ResultStatus, { label: string; tone: 'neutral' | 'warn' | 'info' | 'good' }> = {
    draft: { label: 'Draft', tone: 'neutral' },
    submitted: { label: 'Waiting for approval', tone: 'warn' },
    approved: { label: 'Approved', tone: 'info' },
    published: { label: 'Published', tone: 'good' },
    locked: { label: 'Locked', tone: 'good' },
};

export function ResultStatusPill({ status }: { status: ResultStatus }) {
    const s = STATUS[status] ?? STATUS.draft;
    return <Pill tone={s.tone}>{s.label}</Pill>;
}
