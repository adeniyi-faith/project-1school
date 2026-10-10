import { Pill } from '@/components/app/kit';
import type { CbtAttemptStatus, CbtTestRow } from '@/Types';

/** "Mon 12 Oct, 9:00" */
export function examTime(iso: string | null): string {
    return iso ? new Date(iso).toLocaleString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' }) : '';
}

/** 754 seconds => "12:34"; over an hour => "1:02:34" */
export function clock(seconds: number): string {
    const s = Math.max(0, Math.floor(seconds));
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const pad = (n: number) => String(n).padStart(2, '0');
    return h ? `${h}:${pad(m)}:${pad(s % 60)}` : `${m}:${pad(s % 60)}`;
}

const TEST_STATUS: Record<CbtTestRow['status'], { label: string; tone: 'neutral' | 'good' | 'info' }> = {
    draft: { label: 'Draft', tone: 'neutral' },
    published: { label: 'Open', tone: 'good' },
    closed: { label: 'Closed', tone: 'info' },
};

export function CbtStatusPill({ status }: { status: CbtTestRow['status'] }) {
    return <Pill tone={TEST_STATUS[status].tone}>{TEST_STATUS[status].label}</Pill>;
}

const ATTEMPT_STATUS: Record<CbtAttemptStatus, { label: string; tone: 'neutral' | 'good' | 'info' | 'warn' }> = {
    not_started: { label: 'Not started', tone: 'neutral' },
    in_progress: { label: 'Writing', tone: 'info' },
    submitted: { label: 'Handed in', tone: 'good' },
    timed_out: { label: 'Time ran out', tone: 'warn' },
};

export function AttemptPill({ status }: { status: CbtAttemptStatus }) {
    return <Pill tone={ATTEMPT_STATUS[status].tone}>{ATTEMPT_STATUS[status].label}</Pill>;
}
