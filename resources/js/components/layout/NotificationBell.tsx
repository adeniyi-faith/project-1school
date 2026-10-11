import { Link } from '@inertiajs/react';
import { Bell, ChevronDown } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface Item { key: string; title: string; body: string | null; when: string | null; unread: boolean; kind: string }

/** The bell: tap it to read the latest notices right there; tap a notice to read all of it. */
export default function NotificationBell({ feedUrl, allHref, buttonClass }: { feedUrl: string; allHref: string; buttonClass: string }) {
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState<Item[] | null>(null);
    const [unread, setUnread] = useState(0);
    const [failed, setFailed] = useState(false);
    const [openKey, setOpenKey] = useState<string | null>(null);
    const box = useRef<HTMLDivElement>(null);

    function load() {
        fetch(feedUrl, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then((r) => (r.ok ? r.json() : Promise.reject()))
            .then((d) => { setItems(d.items); setUnread(d.unread); setFailed(false); })
            .catch(() => setFailed(true));
    }

    useEffect(load, [feedUrl]);

    useEffect(() => {
        if (!open) return;
        load();
        const away = (e: MouseEvent) => { if (box.current && !box.current.contains(e.target as Node)) setOpen(false); };
        const esc = (e: KeyboardEvent) => { if (e.key === 'Escape') setOpen(false); };
        document.addEventListener('mousedown', away);
        document.addEventListener('keydown', esc);
        return () => { document.removeEventListener('mousedown', away); document.removeEventListener('keydown', esc); };
    }, [open]);

    return (
        <div ref={box} className="sm:relative">
            <button type="button" onClick={() => setOpen((o) => !o)} className={`${buttonClass} relative`} aria-label={unread ? `Notifications, ${unread} unread` : 'Notifications'} aria-expanded={open}>
                <Bell className="size-[18px]" strokeWidth={1.75} />
                {unread > 0 && <span className="absolute right-1 top-1 grid min-w-4 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-semibold leading-4 text-white">{unread > 9 ? '9+' : unread}</span>}
            </button>
            {open && (
                <div className="fixed inset-x-3 top-16 z-50 flex max-h-[70vh] flex-col overflow-hidden rounded-xl border border-border bg-card text-card-foreground shadow-xl sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-2 sm:w-96">
                    <div className="flex items-center justify-between border-b border-border px-4 py-2.5">
                        <p className="text-sm font-semibold">Notifications</p>
                        <Link href={allHref} className="text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400" onClick={() => setOpen(false)}>See all</Link>
                    </div>
                    <div className="overflow-y-auto">
                        {failed && <p className="px-4 py-6 text-center text-sm text-muted-foreground">Could not load notices. Try again in a moment.</p>}
                        {!failed && items === null && <p className="px-4 py-6 text-center text-sm text-muted-foreground">Loading…</p>}
                        {!failed && items?.length === 0 && <p className="px-4 py-6 text-center text-sm text-muted-foreground">Nothing new right now.</p>}
                        {items?.map((n) => {
                            const expanded = openKey === n.key;
                            return (
                                <button key={n.key} type="button" onClick={() => setOpenKey(expanded ? null : n.key)} className="block w-full border-b border-border/60 px-4 py-3 text-left last:border-0 hover:bg-muted/50" aria-expanded={expanded}>
                                    <span className="flex items-start gap-2">
                                        {n.unread && <span className="mt-1.5 size-2 shrink-0 rounded-full bg-indigo-600" aria-label="Unread" />}
                                        <span className="min-w-0 flex-1">
                                            <span className="block text-[13.5px] font-medium leading-snug">{n.title}</span>
                                            {n.body && <span className={`mt-0.5 block whitespace-pre-line text-[13px] text-muted-foreground ${expanded ? '' : 'line-clamp-2'}`}>{n.body}</span>}
                                            <span className="mt-1 block text-[11.5px] text-muted-foreground">{n.kind}{n.when ? ` · ${n.when}` : ''}</span>
                                        </span>
                                        <ChevronDown className={`mt-1 size-4 shrink-0 text-muted-foreground transition-transform ${expanded ? 'rotate-180' : ''}`} aria-hidden="true" />
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}
