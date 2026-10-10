import { Link, usePage } from '@inertiajs/react';
import { ChevronsLeft, ChevronsRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useUIStore } from '@/Stores/useUIStore';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { LogoMark, initialsOf } from '@/components/layout/Logo';
import { isNavActive, navGroupsForRole, type NavItem } from '@/components/layout/navigation';
import type { PageProps } from '@/Types';

function NavLink({ item, collapsed, onNavigate }: { item: NavItem; collapsed: boolean; onNavigate?: () => void }) {
    const { url } = usePage();
    const isActive = isNavActive(url, item.href, item.exact);

    const link = (
        <Link
            href={item.href}
            onClick={onNavigate}
            aria-current={isActive ? 'page' : undefined}
            className={cn(
                'group relative flex h-9 items-center gap-3 rounded-lg px-3 text-[13px] font-medium outline-none transition-colors',
                'focus-visible:ring-2 focus-visible:ring-indigo-400/70',
                isActive
                    ? 'bg-white/[0.09] text-white'
                    : 'text-sidebar-foreground hover:bg-white/[0.05] hover:text-white',
                collapsed && 'justify-center px-0',
            )}
        >
            {isActive && <span className="absolute -left-2 top-2 bottom-2 w-[3px] rounded-full bg-indigo-400" aria-hidden="true" />}
            <item.icon
                strokeWidth={1.75}
                className={cn('size-[17px] shrink-0 transition-colors', isActive ? 'text-indigo-300' : 'text-white/45 group-hover:text-white/80')}
            />
            {!collapsed && <span className="truncate">{item.label}</span>}
        </Link>
    );

    if (collapsed) {
        return (
            <Tooltip>
                <TooltipTrigger asChild>{link}</TooltipTrigger>
                <TooltipContent side="right">{item.label}</TooltipContent>
            </Tooltip>
        );
    }
    return link;
}

interface SidebarProps {
    /** Render as the full-width phone drawer (never collapsed, no collapse button). */
    drawer?: boolean;
    onNavigate?: () => void;
}

export default function Sidebar({ drawer = false, onNavigate }: SidebarProps) {
    const { auth, school } = usePage<PageProps>().props;
    const { sidebarCollapsed, toggleCollapsed } = useUIStore();
    const collapsed = drawer ? false : sidebarCollapsed;
    const role = auth.user?.role ?? '';
    const roleLabel = role.replace(/-/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    const groups = navGroupsForRole(role);

    return (
        <TooltipProvider delayDuration={0}>
            <aside
                className={cn(
                    'relative flex h-full shrink-0 flex-col bg-sidebar text-sidebar-foreground',
                    drawer ? 'w-full' : 'border-r border-sidebar-border transition-[width] duration-200',
                    !drawer && (collapsed ? 'w-[68px]' : 'w-64'),
                )}
            >
                <div className={cn('flex h-14 shrink-0 items-center gap-2.5 px-5', collapsed && 'justify-center px-0')}>
                    <LogoMark />
                    {!collapsed && <span className="text-[15px] font-semibold tracking-tight text-white">SchoolRuns</span>}
                </div>

                {!collapsed && (
                    <div className="mx-3 mb-2 flex items-center gap-2.5 rounded-xl border border-sidebar-border bg-white/[0.04] px-3 py-2.5">
                        <span className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-amber-300 to-amber-500 text-[11px] font-bold text-amber-950">
                            {initialsOf(school?.name ?? 'SR')}
                        </span>
                        <span className="min-w-0 leading-tight">
                            <span className="block truncate text-[13px] font-medium text-white">{school?.name ?? 'SchoolRuns'}</span>
                            <span className="block truncate text-[11px] text-white/45">{roleLabel}</span>
                        </span>
                    </div>
                )}

                <nav className="scroll-quiet flex-1 space-y-5 overflow-y-auto px-3 pb-4 pt-2" aria-label="Main">
                    {groups.map((group) => (
                        <div key={group.title}>
                            {!collapsed && (
                                <p className="mb-1.5 px-3 text-[10.5px] font-semibold uppercase tracking-[0.1em] text-white/35">
                                    {group.title}
                                </p>
                            )}
                            <div className="space-y-0.5">
                                {group.items.map((item) => (
                                    <NavLink key={item.href} item={item} collapsed={collapsed} onNavigate={onNavigate} />
                                ))}
                            </div>
                        </div>
                    ))}
                </nav>

                {!drawer && (
                    <button
                        onClick={toggleCollapsed}
                        className="flex h-11 shrink-0 items-center gap-2 border-t border-sidebar-border px-5 text-xs font-medium text-white/45 outline-none transition-colors hover:text-white focus-visible:text-white"
                        aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                    >
                        {collapsed ? <ChevronsRight className="mx-auto size-4" /> : <><ChevronsLeft className="size-4" /> Collapse</>}
                    </button>
                )}
            </aside>
        </TooltipProvider>
    );
}
