import { Head, router } from '@inertiajs/react';
import { useAuthStore } from '@/Stores/useAuthStore';
import { Button } from '@/components/ui/button';

export default function Dashboard() {
    const theme = useAuthStore((s) => s.theme);
    const setTheme = useAuthStore((s) => s.setTheme);

    return (
        <>
            <Head title="Dashboard" />
            <div className="min-h-screen bg-slate-50 dark:bg-slate-950 flex items-center justify-center">
                <div className="text-center space-y-4">
                    <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 shadow-lg">
                        <svg className="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                    <h1 className="text-3xl font-bold text-slate-900 dark:text-white">
                        Genius SMS Dashboard
                    </h1>
                    <p className="text-slate-500 dark:text-slate-400">
                        Sprint 1 — Login working. Dashboard coming in Sprint 3+.
                    </p>
                    <div className="flex items-center justify-center gap-3 pt-2">
                        <Button
                            variant="outline"
                            onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}
                        >
                            Toggle {theme === 'dark' ? 'Light' : 'Dark'} Mode
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => router.post('/logout')}
                        >
                            Logout
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}
