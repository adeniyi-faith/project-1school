import { router, usePage } from '@inertiajs/react';
import { Moon, Sun, LogOut, User, Menu, KeyRound } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem,
    DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { useAuthStore } from '@/Stores/useAuthStore';
import { useUIStore } from '@/Stores/useUIStore';
import { useEffect } from 'react';
import type { PageProps } from '@/Types';

const roleColors: Record<string, string> = {
    'super-admin':   'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
    'school-admin':  'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
    'principal':     'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    'teacher':       'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
    'accountant':    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    'librarian':     'bg-teal-100 text-teal-700 dark:bg-teal-950 dark:text-teal-300',
    'student':       'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    'parent':        'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300',
};

interface TopbarProps {
    title?: string;
    breadcrumbs?: { label: string; href?: string }[];
}

export default function Topbar({ title, breadcrumbs }: TopbarProps) {
    const { auth } = usePage<PageProps>().props;
    const { theme, setTheme } = useAuthStore();
    const { toggleSidebar } = useUIStore();

    const user = auth.user;
    const initials = user?.name.split(' ').map((n) => n[0]).join('').slice(0, 2).toUpperCase() ?? 'U';
    const roleLabel = user?.role?.replace(/-/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) ?? '';
    const roleClass = roleColors[user?.role ?? ''] ?? 'bg-slate-100 text-slate-700';

    useEffect(() => {
        const root = document.documentElement;
        if (theme === 'dark') root.classList.add('dark');
        else if (theme === 'light') root.classList.remove('dark');
        else {
            window.matchMedia('(prefers-color-scheme: dark)').matches
                ? root.classList.add('dark')
                : root.classList.remove('dark');
        }
    }, [theme]);

    return (
        <header className="h-14 flex items-center justify-between px-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 shrink-0">
            {/* Left */}
            <div className="flex items-center gap-3">
                <Button variant="ghost" size="icon" className="md:hidden" onClick={toggleSidebar}>
                    <Menu className="w-5 h-5" />
                </Button>
                {breadcrumbs && breadcrumbs.length > 0 ? (
                    <nav className="flex items-center gap-1.5 text-sm">
                        {breadcrumbs.map((crumb, i) => (
                            <span key={i} className="flex items-center gap-1.5">
                                {i > 0 && <span className="text-slate-300 dark:text-slate-700">/</span>}
                                {crumb.href ? (
                                    <a href={crumb.href} className="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition-colors">
                                        {crumb.label}
                                    </a>
                                ) : (
                                    <span className="text-slate-900 dark:text-white font-medium">{crumb.label}</span>
                                )}
                            </span>
                        ))}
                    </nav>
                ) : title ? (
                    <h1 className="text-sm font-semibold text-slate-900 dark:text-white">{title}</h1>
                ) : null}
            </div>

            {/* Right */}
            <div className="flex items-center gap-2">
                {/* Theme toggle */}
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}
                    className="text-slate-500 dark:text-slate-400"
                >
                    {theme === 'dark' ? <Sun className="w-4 h-4" /> : <Moon className="w-4 h-4" />}
                </Button>

                {/* User menu */}
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button className="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <Avatar className="w-7 h-7">
                                <AvatarImage src={user?.avatar ?? undefined} />
                                <AvatarFallback className="text-xs bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                    {initials}
                                </AvatarFallback>
                            </Avatar>
                            <div className="hidden sm:flex flex-col items-start">
                                <span className="text-xs font-medium text-slate-900 dark:text-white leading-none">{user?.name}</span>
                                <span className={`text-[10px] mt-0.5 px-1 rounded font-medium ${roleClass}`}>{roleLabel}</span>
                            </div>
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-48">
                        <DropdownMenuGroup>
                            <DropdownMenuLabel className="font-normal">
                                <p className="text-sm font-medium">{user?.name}</p>
                                <p className="text-xs text-slate-500 truncate">{user?.email}</p>
                            </DropdownMenuLabel>
                        </DropdownMenuGroup>
                        <DropdownMenuSeparator />
                        <DropdownMenuGroup>
                            <DropdownMenuItem onClick={() => { window.location.href = '/profile'; }}>
                                <User className="w-4 h-4 mr-2" /> Profile
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => { window.location.href = '/password/change'; }}>
                                <KeyRound className="w-4 h-4 mr-2" /> Change Password
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                        <DropdownMenuSeparator />
                        <DropdownMenuGroup>
                            <DropdownMenuItem
                                className="text-red-600 dark:text-red-400 cursor-pointer"
                                onClick={() => router.post('/logout')}
                            >
                                <LogOut className="w-4 h-4 mr-2" /> Logout
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}
