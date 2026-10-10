import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import AuthLayout from '@/Layouts/AuthLayout';
import { LogOut } from 'lucide-react';

/** The frame around the before and after pages of a test: the portal for students, a plain page for entrance-exam applicants */
export default function CbtShell({ title, person, children }: { title: string; person?: { name: string; number: string | null }; children: React.ReactNode }) {
    if (!person) return <AppLayout title={title}>{children}</AppLayout>;
    return (
        <AuthLayout>
            <Head title={title} />
            <header className="border-b border-slate-200 bg-white dark:border-white/10 dark:bg-slate-950">
                <div className="mx-auto flex max-w-2xl items-center justify-between gap-3 px-4 py-3">
                    <p className="text-sm"><span className="font-medium text-slate-900 dark:text-white">{person.name}</span> <span className="text-slate-500">· Exam no. {person.number}</span></p>
                    <button type="button" onClick={() => router.post('/exam/logout')} className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800 dark:hover:text-slate-200">
                        <LogOut className="size-4" /> Log out
                    </button>
                </div>
            </header>
            <main className="px-4 py-8">{children}</main>
        </AuthLayout>
    );
}
