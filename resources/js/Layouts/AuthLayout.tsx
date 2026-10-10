import { useEffect } from 'react';
import { useAuthStore } from '@/Stores/useAuthStore';

interface AuthLayoutProps {
    children: React.ReactNode;
}

export default function AuthLayout({ children }: AuthLayoutProps) {
    const theme = useAuthStore((s) => s.theme);

    useEffect(() => {
        const root = document.documentElement;
        if (theme === 'dark') {
            root.classList.add('dark');
        } else if (theme === 'light') {
            root.classList.remove('dark');
        } else {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            prefersDark ? root.classList.add('dark') : root.classList.remove('dark');
        }
    }, [theme]);

    return <div className="min-h-dvh bg-background">{children}</div>;
}
