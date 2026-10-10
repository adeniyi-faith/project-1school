import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import Sidebar from '@/components/layout/Sidebar';
import Topbar from '@/components/layout/Topbar';
import MobileNav from '@/components/layout/MobileNav';
import PageProgress from '@/components/layout/PageProgress';
import { useAuthStore } from '@/Stores/useAuthStore';
import type { PageProps } from '@/Types';

interface AppLayoutProps {
    children: React.ReactNode;
    title?: string;
    breadcrumbs?: { label: string; href?: string }[];
}

export default function AppLayout({ children, title, breadcrumbs }: AppLayoutProps) {
    const { flash, faviconUrl } = usePage<PageProps>().props;
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
            <a href="#main" className="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-card focus:px-3 focus:py-2 focus:text-sm focus:shadow-lg">
                Skip to content
            </a>

            {/* Sidebar (tablet and up) */}
            <div className="hidden md:flex">
                <Sidebar />
            </div>

            {/* Main */}
            <div className="flex min-w-0 flex-1 flex-col overflow-hidden">
                <Topbar title={title} breadcrumbs={breadcrumbs} />
                <main id="main" className="scroll-quiet flex-1 overflow-y-auto px-4 pb-28 pt-5 md:px-8 md:pb-10 md:pt-7">
                    <div className="mx-auto w-full max-w-[1400px]">{children}</div>
                </main>
            </div>

            <MobileNav />
        </div>
    );
}
