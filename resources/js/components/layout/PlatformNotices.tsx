import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Megaphone, Wrench, X } from 'lucide-react';
import type { PageProps } from '@/Types';

const KEY = 'dismissed-platform-notices';

const read = (): number[] => {
    try { return JSON.parse(localStorage.getItem(KEY) ?? '[]'); } catch { return []; }
};

const dateTime = (iso: string) => new Date(iso).toLocaleString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit', timeZone: 'Africa/Lagos' });

/** Banners from the platform team. Each person can hide a notice for themselves on their own device. */
export default function PlatformNotices() {
    const { platform_notices } = usePage<PageProps>().props;
    const [hidden, setHidden] = useState<number[]>([]);
    useEffect(() => setHidden(read()), []);

    const shown = (platform_notices ?? []).filter((n) => !hidden.includes(n.id));
    if (shown.length === 0) return null;

    const dismiss = (id: number) => {
        const next = [...hidden, id];
        setHidden(next);
        try { localStorage.setItem(KEY, JSON.stringify(next.slice(-50))); } catch { /* private window: it just comes back next time */ }
    };

    return (
        <div role="region" aria-label="Notices from the platform team">
            {shown.map((n) => {
                const maintenance = n.kind === 'maintenance';
                const Icon = maintenance ? Wrench : Megaphone;
                return (
                    <div key={n.id} className={`flex items-start gap-2.5 px-3 py-2 text-[13px] leading-snug sm:px-4 ${maintenance ? 'bg-amber-100 text-amber-950 dark:bg-amber-500/20 dark:text-amber-100' : 'bg-sky-100 text-sky-950 dark:bg-sky-500/20 dark:text-sky-100'}`}>
                        <Icon className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                        <p className="min-w-0 flex-1 break-words">
                            <b className="font-semibold">{n.title}.</b> {n.body}
                            {maintenance && n.starts_at && n.ends_at && <span className="font-medium"> From {dateTime(n.starts_at)} to {dateTime(n.ends_at)} (Nigerian time).</span>}
                        </p>
                        <button type="button" onClick={() => dismiss(n.id)} aria-label="Hide this notice" className="shrink-0 rounded p-0.5 hover:bg-black/10"><X className="size-4" /></button>
                    </div>
                );
            })}
        </div>
    );
}
