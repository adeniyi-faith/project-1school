import { Pill } from '@/components/app/kit';

export const when = (iso: string | null): string => {
    if (!iso) return '—';
    const mins = Math.floor((Date.now() - new Date(iso).getTime()) / 60000);
    if (mins < 1) return 'Just now';
    if (mins < 60) return `${mins} min ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs} hr ago`;
    const days = Math.floor(hrs / 24);
    return days < 31 ? `${days} day${days === 1 ? '' : 's'} ago` : new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
};

/** school = how the school should read the status; otherwise how the platform team reads it */
export function StatusPill({ status, school = false }: { status: string; school?: boolean }) {
    if (status === 'closed') return <Pill tone="neutral">Closed</Pill>;
    if (status === 'answered') return <Pill tone="good">{school ? 'Answered' : 'Waiting for school'}</Pill>;
    return <Pill tone="warn">{school ? 'Waiting for the team' : 'Needs a reply'}</Pill>;
}

export interface ThreadMessage { id: number; body: string; from_staff: boolean; internal?: boolean; author: string; at: string | null }

export function Thread({ messages }: { messages: ThreadMessage[] }) {
    return (
        <ol className="space-y-3">
            {messages.map((m) => (
                <li key={m.id} className={`rounded-xl border p-4 min-w-0 ${m.internal
                    ? 'border-amber-200 bg-amber-50 dark:border-amber-500/20 dark:bg-amber-500/10'
                    : m.from_staff
                        ? 'border-indigo-100 bg-indigo-50/60 dark:border-indigo-500/20 dark:bg-indigo-500/10'
                        : 'border-slate-200 bg-white dark:border-white/[0.08] dark:bg-slate-900'}`}>
                    <p className="text-xs text-slate-500 mb-1.5">
                        <span className="font-medium text-slate-700 dark:text-slate-200">{m.author}</span>
                        {m.internal && <span className="ml-2 text-amber-700 dark:text-amber-300">Private note, the school cannot see this</span>}
                        <span> · {when(m.at)}</span>
                    </p>
                    <p className="text-sm text-slate-800 dark:text-slate-100 whitespace-pre-wrap break-words">{m.body}</p>
                </li>
            ))}
        </ol>
    );
}
