import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { ChevronDown, ChevronsLeft, ChevronsRight, Plus, Search } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useUIStore } from '@/Stores/useUIStore';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Wordmark, WordmarkLetter, initialsOf } from '@/components/layout/Logo';
import { isNavActive, navGroupsForRole, searchActions, searchKeywords, type NavGroup, type NavItem } from '@/components/layout/navigation';
import type { PageProps } from '@/Types';

function NavLink({ item, collapsed, onNavigate }: { item: NavItem; collapsed: boolean; onNavigate?: () => void }) {
    const { url, props } = usePage<PageProps>();
    const waiting = (props.auth?.badges ?? {})[item.href] ?? 0;
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
            {waiting > 0 && (
                collapsed
                    ? <span className="absolute right-1.5 top-1.5 size-2 rounded-full bg-red-500" aria-label={`${waiting} waiting`} />
                    : <span className="ml-auto grid min-w-5 place-items-center rounded-full bg-red-500 px-1.5 text-[10.5px] font-semibold leading-5 text-white" aria-label={`${waiting} waiting`}>{waiting > 99 ? '99+' : waiting}</span>
            )}
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

/** One menu group. Rarely-used groups start folded; the group holding the open page is always shown. */
function Group({ group, collapsed, onNavigate }: { group: NavGroup; collapsed: boolean; onNavigate?: () => void }) {
    const { url, props } = usePage<PageProps>();
    const { groupOpen, setGroupOpen } = useUIStore();
    const holdsActivePage = group.items.some((i) => isNavActive(url, i.href, i.exact));
    const startsFolded = group.folded || !!group.foldedFor?.includes(props.auth.user?.role ?? '');
    const open = collapsed || holdsActivePage || (groupOpen[group.title] ?? !startsFolded);

    return (
        <div>
            {!collapsed && (
                <button
                    type="button"
                    onClick={() => setGroupOpen(group.title, !open)}
                    aria-expanded={open}
                    className="mb-1.5 flex w-full items-center justify-between rounded px-3 text-[10.5px] font-semibold uppercase tracking-[0.1em] text-white/35 outline-none transition-colors hover:text-white/70 focus-visible:ring-2 focus-visible:ring-indigo-400/70"
                >
                    <span>{group.title}</span>
                    <ChevronDown className={cn('size-3.5 transition-transform', !open && '-rotate-90')} aria-hidden="true" />
                </button>
            )}
            {open && (
                <div className="space-y-0.5">
                    {group.items.map((item) => (
                        <NavLink key={item.href} item={item} collapsed={collapsed} onNavigate={onNavigate} />
                    ))}
                </div>
            )}
        </div>
    );
}

/** A box that finds any page the person may open. Press / or Ctrl+K to start typing; Enter opens the first match. */
function MenuSearch({ groups, paths, onNavigate }: { groups: NavGroup[]; paths?: string[]; onNavigate?: () => void }) {
    const [query, setQuery] = useState('');
    const input = useRef<HTMLInputElement>(null);

    useEffect(() => {
        const onKey = (e: KeyboardEvent) => {
            const typing = /input|textarea|select/i.test((e.target as HTMLElement)?.tagName ?? '') || (e.target as HTMLElement)?.isContentEditable;
            if ((e.key === '/' && !typing) || ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k')) {
                e.preventDefault();
                input.current?.focus();
            }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, []);

    const q = query.trim().toLowerCase();
    const seen = new Set<string>();
    // Every word typed must appear in the page's name, its group, or the everyday words listed for it
    const words = q.split(/\s+/).filter(Boolean);
    const matches = (text: string) => words.every((w) => text.includes(w));
    const pages = groups.flatMap((g) => g.items.map((item) => ({ item, group: g.title })));
    const visible = new Set(pages.map((p) => p.item.href));
    const pageResults = q
        ? pages.filter(({ item, group }) => {
            if (seen.has(item.href)) return false;
            const hit = matches(`${item.label} ${group} ${searchKeywords[item.href] ?? ''}`.toLowerCase());
            if (hit) seen.add(item.href);
            return hit;
        })
        : [];
    // Jobs such as "Add a student" come first. They are offered only to people who can open the page they lead to.
    const inSchool = [...visible].some((h) => h.startsWith('/school'));
    const actionResults = q && inSchool
        ? searchActions
            .filter((a) => (paths ? paths.includes(a.href) : visible.has(a.href)) && matches(`${a.label} ${a.keywords}`.toLowerCase()))
            .map((a) => ({ item: { label: a.label, href: a.href, icon: Plus } as NavItem, group: 'Do' }))
        : [];
    const results = [...actionResults, ...pageResults];

    function go(href: string) {
        setQuery('');
        onNavigate?.();
        router.visit(href);
    }

    return (
        <div className="mx-3 mb-2">
            <div className="relative">
                <Search className="pointer-events-none absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-white/40" aria-hidden="true" />
                <input
                    ref={input}
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter' && results[0]) go(results[0].item.href);
                        if (e.key === 'Escape') { setQuery(''); input.current?.blur(); }
                    }}
                    placeholder="Find a page…  ( / )"
                    aria-label="Find a page"
                    className="h-8 w-full rounded-lg border border-sidebar-border bg-white/[0.04] pl-8 pr-2 text-[12.5px] text-white outline-none placeholder:text-white/35 focus-visible:ring-2 focus-visible:ring-indigo-400/70"
                />
            </div>
            {q && (
                <div className="mt-1.5 space-y-0.5" role="listbox" aria-label="Matching pages">
                    {results.length === 0 && <p className="px-3 py-2 text-[12px] text-white/45">No page matches “{query}”.</p>}
                    {results.map(({ item, group }) => (
                        <button
                            key={`${group}-${item.href}-${item.label}`}
                            type="button"
                            onClick={() => go(item.href)}
                            className="flex h-9 w-full items-center gap-3 rounded-lg px-3 text-left text-[13px] font-medium text-sidebar-foreground outline-none hover:bg-white/[0.05] hover:text-white focus-visible:ring-2 focus-visible:ring-indigo-400/70"
                        >
                            <item.icon strokeWidth={1.75} className="size-[17px] shrink-0 text-white/45" />
                            <span className="truncate">{item.label}</span>
                            <span className="ml-auto truncate text-[10.5px] uppercase tracking-wider text-white/30">{group}</span>
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

interface SidebarProps {
    /** Render as the full-width phone drawer (never collapsed, no collapse button). */
    drawer?: boolean;
    onNavigate?: () => void;
}

export default function Sidebar({ drawer = false, onNavigate }: SidebarProps) {
    const { auth, school, acting_school } = usePage<PageProps>().props;
    const { sidebarCollapsed, toggleCollapsed } = useUIStore();
    const collapsed = drawer ? false : sidebarCollapsed;
    const role = auth.user?.role ?? '';
    const roleLabel = role.replace(/-/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    const groups = navGroupsForRole(role, auth.paths, !!acting_school);

    return (
        <TooltipProvider delayDuration={0}>
            <aside
                className={cn(
                    'relative flex h-full shrink-0 flex-col bg-sidebar text-sidebar-foreground',
                    drawer ? 'w-full' : 'border-r border-sidebar-border transition-[width] duration-200',
                    !drawer && (collapsed ? 'w-[68px]' : 'w-64'),
                )}
            >
                <div className={cn('flex h-16 shrink-0 items-center gap-2.5 px-5', collapsed && 'justify-center px-0')}>
                    {collapsed ? <WordmarkLetter /> : <Wordmark size="lg" />}
                </div>

                {!collapsed && (
                    <div className="mx-3 mb-2 flex items-center gap-2.5 rounded-xl border border-sidebar-border bg-white/[0.04] px-3 py-2.5">
                        {school?.logo_url
                            ? <img src={school.logo_url} alt="" className="size-8 shrink-0 rounded-lg bg-white object-contain p-0.5" />
                            : <span className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-white/10 text-[11px] font-semibold tracking-wide text-white/80">
                                {initialsOf(acting_school?.name ?? school?.name ?? 'Adino')}
                            </span>}
                        <span className="min-w-0 leading-tight">
                            <span className="block truncate text-[13px] font-medium text-white">{acting_school?.name ?? school?.name ?? 'Adino School Systems'}</span>
                            <span className="block truncate text-[11px] text-white/45">{roleLabel}</span>
                        </span>
                    </div>
                )}

                {!collapsed && <MenuSearch groups={groups} paths={auth.paths} onNavigate={onNavigate} />}

                <nav className="scroll-quiet flex-1 space-y-5 overflow-y-auto px-3 pb-4 pt-2" aria-label="Main">
                    {groups.map((group) => (
                        <Group key={group.title} group={group} collapsed={collapsed} onNavigate={onNavigate} />
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
