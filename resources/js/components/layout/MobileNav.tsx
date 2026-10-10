import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, X } from 'lucide-react';
import { useEffect } from 'react';
import { cn } from '@/lib/utils';
import { useUIStore } from '@/Stores/useUIStore';
import Sidebar from '@/components/layout/Sidebar';
import { bottomTabs, isNavActive } from '@/components/layout/navigation';
import type { PageProps } from '@/Types';

/** Phone tab bar plus the full-menu drawer behind its "More" tab. Hidden from tablet width up. */
export default function MobileNav() {
    const { auth } = usePage<PageProps>().props;
    const { url } = usePage();
    const { mobileNavOpen, setMobileNavOpen } = useUIStore();
    const tabs = bottomTabs[auth.user?.role ?? ''] ?? bottomTabs.default;
    const onTab = tabs.some((t) => isNavActive(url, t.href, t.exact));

    useEffect(() => { setMobileNavOpen(false); }, [url, setMobileNavOpen]);

    useEffect(() => {
        if (!mobileNavOpen) return;
        const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') setMobileNavOpen(false); };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [mobileNavOpen, setMobileNavOpen]);

    return (
        <div className="md:hidden">
            <nav
                aria-label="Quick navigation"
                className="fixed inset-x-0 bottom-0 z-30 flex border-t border-border bg-card/90 px-2 pt-1.5 backdrop-blur-xl pb-[max(0.5rem,env(safe-area-inset-bottom))]"
            >
                {tabs.map((tab) => {
                    const active = isNavActive(url, tab.href, tab.exact);
                    return (
                        <Link key={tab.href} href={tab.href} aria-current={active ? 'page' : undefined} className="flex flex-1 flex-col items-center gap-0.5 py-1 text-[11px] font-medium outline-none">
                            <span className={cn('flex h-7 w-14 items-center justify-center rounded-full transition-colors', active ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200' : 'text-muted-foreground')}>
                                <tab.icon className="size-5" strokeWidth={active ? 2.1 : 1.75} />
                            </span>
                            <span className={active ? 'text-indigo-700 dark:text-indigo-200' : 'text-muted-foreground'}>{tab.label}</span>
                        </Link>
                    );
                })}
                <button onClick={() => setMobileNavOpen(true)} aria-haspopup="dialog" className="flex flex-1 flex-col items-center gap-0.5 py-1 text-[11px] font-medium outline-none">
                    <span className={cn('flex h-7 w-14 items-center justify-center rounded-full transition-colors', !onTab ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200' : 'text-muted-foreground')}>
                        <LayoutGrid className="size-5" strokeWidth={1.75} />
                    </span>
                    <span className={!onTab ? 'text-indigo-700 dark:text-indigo-200' : 'text-muted-foreground'}>More</span>
                </button>
            </nav>

            {mobileNavOpen && (
                <div className="fixed inset-0 z-40" role="dialog" aria-modal="true" aria-label="All sections">
                    <button className="absolute inset-0 bg-slate-950/50 backdrop-blur-[2px]" onClick={() => setMobileNavOpen(false)} aria-label="Close menu" />
                    <div className="absolute inset-y-0 left-0 w-[min(20rem,86vw)] shadow-2xl animate-in slide-in-from-left duration-200">
                        <Sidebar drawer onNavigate={() => setMobileNavOpen(false)} />
                        <button onClick={() => setMobileNavOpen(false)} className="absolute right-3 top-3 flex size-8 items-center justify-center rounded-lg text-white/60 hover:bg-white/10 hover:text-white" aria-label="Close menu">
                            <X className="size-4" />
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
