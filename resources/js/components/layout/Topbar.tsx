import { router, usePage } from '@inertiajs/react';
import { ChevronDown, KeyRound, ShieldCheck, LogOut, Moon, PanelLeft, Sun, User } from 'lucide-react';
import { useEffect } from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem,
    DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import NotificationBell from '@/components/layout/NotificationBell';
import { Wordmark, initialsOf } from '@/components/layout/Logo';
import { useAuthStore } from '@/Stores/useAuthStore';
import { useUIStore } from '@/Stores/useUIStore';
import type { PageProps } from '@/Types';

interface TopbarProps {
    title?: string;
    breadcrumbs?: { label: string; href?: string }[];
}

const NOTIFY_HREF: Record<string, string> = {
    parent: '/school/parent/announcements',
    student: '/school/student/announcements',
};

const FEED_URL: Record<string, string> = {
    parent: '/school/parent/notifications/feed',
    student: '/school/student/notifications/feed',
};

const iconButton =
    'flex size-9 items-center justify-center rounded-lg text-muted-foreground outline-none transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring';

/** The top bar no longer repeats the page name: every page already shows its own heading. */
export default function Topbar(_props: TopbarProps) {
    const { auth, platform, acting_school } = usePage<PageProps>().props;
    const { theme, setTheme } = useAuthStore();
    const { sidebarCollapsed, toggleCollapsed } = useUIStore();

    const user = auth.user;
    const role = user?.role ?? '';
    const roleLabel = acting_school?.as ?? platform?.role ?? role.replace(/-/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    const notifyHref = NOTIFY_HREF[role] ?? (role === 'super-admin' || role === 'platform-staff' ? null : '/school/communication/notifications');

    useEffect(() => {
        const root = document.documentElement;
        if (theme === 'dark') root.classList.add('dark');
        else if (theme === 'light') root.classList.remove('dark');
        else if (window.matchMedia('(prefers-color-scheme: dark)').matches) root.classList.add('dark');
        else root.classList.remove('dark');
    }, [theme]);

    const isDark = theme === 'dark' || (theme === 'system' && typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches);

    return (
        <header className="sticky top-0 z-20 flex h-14 shrink-0 items-center gap-3 border-b border-border bg-card/85 px-4 backdrop-blur-xl md:px-6">
            <div className="flex items-center gap-2.5 md:hidden">
                <Wordmark size="sm" className="text-foreground" />
            </div>
            <button onClick={toggleCollapsed} className={`${iconButton} hidden md:flex`} aria-label={sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}>
                <PanelLeft className="size-[18px]" strokeWidth={1.75} />
            </button>

            <div className="flex-1" />

            <div className="flex items-center gap-1">
                <button onClick={() => setTheme(isDark ? 'light' : 'dark')} className={iconButton} aria-label={isDark ? 'Switch to light mode' : 'Switch to dark mode'}>
                    {isDark ? <Sun className="size-[18px]" strokeWidth={1.75} /> : <Moon className="size-[18px]" strokeWidth={1.75} />}
                </button>
                {notifyHref && (
                    <NotificationBell feedUrl={FEED_URL[role] ?? '/school/communication/notifications/feed'} allHref={notifyHref} buttonClass={iconButton} />
                )}

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button className="ml-1 flex items-center gap-2.5 rounded-lg py-1 pl-1 pr-2 outline-none transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring" aria-label="Account menu">
                            <Avatar className="size-8">
                                <AvatarImage src={user?.avatar ?? undefined} />
                                <AvatarFallback className="bg-indigo-600 text-[11px] font-semibold text-white">{initialsOf(user?.name)}</AvatarFallback>
                            </Avatar>
                            <span className="hidden flex-col items-start leading-tight sm:flex">
                                <span className="text-[13px] font-medium text-foreground">{user?.name}</span>
                                <span className="text-[11px] text-muted-foreground">{roleLabel}</span>
                            </span>
                            <ChevronDown className="hidden size-3.5 text-muted-foreground sm:block" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-56">
                        <DropdownMenuGroup>
                            <DropdownMenuLabel className="font-normal">
                                <p className="text-sm font-medium">{user?.name}</p>
                                <p className="truncate text-xs text-muted-foreground">{user?.email}</p>
                            </DropdownMenuLabel>
                        </DropdownMenuGroup>
                        <DropdownMenuSeparator />
                        <DropdownMenuGroup>
                            <DropdownMenuItem onClick={() => { window.location.href = '/profile'; }}>
                                <User className="mr-2 size-4" /> Profile
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => { window.location.href = '/password/change'; }}>
                                <KeyRound className="mr-2 size-4" /> Change password
                            </DropdownMenuItem>
                            {(platform || role === 'super-admin' || role === 'platform-staff') && (
                                <DropdownMenuItem onClick={() => { window.location.href = '/security'; }}>
                                    <ShieldCheck className="mr-2 size-4" /> My security
                                </DropdownMenuItem>
                            )}
                        </DropdownMenuGroup>
                        <DropdownMenuSeparator />
                        <DropdownMenuGroup>
                            <DropdownMenuItem className="cursor-pointer text-red-600 dark:text-red-400" onClick={() => router.post('/logout')}>
                                <LogOut className="mr-2 size-4" /> Sign out
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}
