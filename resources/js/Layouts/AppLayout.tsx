import { useEffect, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import Sidebar from '@/components/layout/Sidebar';
import Topbar from '@/components/layout/Topbar';
import MobileNav from '@/components/layout/MobileNav';
import { StackTables } from '@/components/layout/StackTables';
import PageProgress from '@/components/layout/PageProgress';
import LoginDetailsDialog from '@/components/layout/LoginDetailsDialog';
import { useAuthStore } from '@/Stores/useAuthStore';
import type { PageProps } from '@/Types';

interface AppLayoutProps {
    children: React.ReactNode;
    title?: string;
    breadcrumbs?: { label: string; href?: string }[];
}

/** The orange bar shown while a platform person is signed in as a school login: who, time left, and the way back */
function ImpersonationBar({ info }: { info: NonNullable<PageProps['impersonating']> }) {
    const [left, setLeft] = useState(() => Math.max(0, Math.floor((new Date(info.until).getTime() - Date.now()) / 1000)));
    useEffect(() => {
        const t = setInterval(() => setLeft(Math.max(0, Math.floor((new Date(info.until).getTime() - Date.now()) / 1000))), 1000);
        return () => clearInterval(t);
    }, [info.until]);
    useEffect(() => { if (left === 0) router.post('/impersonation/stop'); }, [left]);
    const mm = String(Math.floor(left / 60)).padStart(2, '0');
    const ss = String(left % 60).padStart(2, '0');

    return (
        <div role="status" className="flex items-center justify-between gap-3 bg-orange-600 px-3 py-1 text-[12px] leading-tight text-white sm:justify-center sm:px-4 sm:py-1.5 sm:text-[13px]">
            <span className="min-w-0 truncate">
                <span className="hidden sm:inline">You are signed in as </span><span className="sm:hidden">As </span><b className="font-semibold">{info.as}</b>
                <span className="hidden sm:inline">{info.read_only ? ' (look only)' : ' (changes allowed)'}</span> · {mm}:{ss}<span className="hidden sm:inline"> left</span>
            </span>
            <button type="button" onClick={() => router.post('/impersonation/stop')} className="shrink-0 font-semibold underline underline-offset-2 hover:text-orange-100">Back<span className="hidden sm:inline"> to my account</span></button>
        </div>
    );
}

export default function AppLayout({ children, title, breadcrumbs }: AppLayoutProps) {
    const { flash, faviconUrl, demo, acting_school, impersonating } = usePage<PageProps>().props;
    const theme = useAuthStore((s) => s.theme);

    // Favicon sync
    useEffect(() => {
        const link = document.getElementById('app-favicon') as HTMLLinkElement | null
            ?? document.querySelector("link[rel~='icon']") as HTMLLinkElement | null;
        if (link) {
            link.href = faviconUrl ?? '/favicon.ico';
        }
    }, [faviconUrl]);

    // Dark mode sync
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

    // Flash messages
    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
        if (flash?.info) toast.info(flash.info);
    }, [flash]);

    return (
        <div className="flex h-dvh overflow-hidden bg-background">
            <PageProgress />
            <LoginDetailsDialog />
            <a href="#main" className="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-card focus:px-3 focus:py-2 focus:text-sm focus:shadow-lg">
                Skip to content
            </a>

            {/* Sidebar (tablet and up) */}
            <div className="hidden md:flex">
                <Sidebar />
            </div>

            {/* Main */}
            <div className="flex min-w-0 flex-1 flex-col overflow-hidden">
                {demo && (
                    <div className="flex items-center justify-between gap-3 bg-indigo-600 px-3 py-1 text-[12px] leading-tight text-white sm:justify-center sm:px-4 sm:py-1.5 sm:text-[13px]">
                        <span className="min-w-0 truncate">Demo as <b className="font-semibold">{demo.label}</b><span className="hidden sm:inline">. Nothing can be changed.</span></span>
                        <a href="/demo" className="shrink-0 font-semibold underline underline-offset-2 hover:text-indigo-100">Switch<span className="hidden sm:inline"> account type</span></a>
                    </div>
                )}
                {impersonating && <ImpersonationBar info={impersonating} />}
                {acting_school && (
                    <div className="flex items-center justify-between gap-3 bg-amber-500 px-3 py-1 text-[12px] leading-tight text-amber-950 sm:justify-center sm:px-4 sm:py-1.5 sm:text-[13px]">
                        <span className="min-w-0 truncate">
                            {acting_school.read_only ? 'Looking around' : 'Working inside'} <b className="font-semibold">{acting_school.name}</b><span className="hidden sm:inline"> as {acting_school.as}</span>
                            {acting_school.read_only && <span className="hidden sm:inline">. You can look, but not change anything.</span>}
                        </span>
                        <button type="button" onClick={() => router.post('/super-admin/leave-school')} className="shrink-0 font-semibold underline underline-offset-2 hover:text-amber-900">Back to platform</button>
                    </div>
                )}
                <Topbar title={title} breadcrumbs={breadcrumbs} />
                <main id="main" className="scroll-quiet flex-1 overflow-y-auto px-4 pb-28 pt-5 md:px-8 md:pb-10 md:pt-7">
                    <div className="mx-auto w-full max-w-[1400px]">{children}</div>
                    <p className="mx-auto mt-10 w-full max-w-[1400px] text-center text-xs text-muted-foreground">Powered by Adino Systems</p>
                </main>
            </div>

            <MobileNav />
            <StackTables />
        </div>
    );
}
