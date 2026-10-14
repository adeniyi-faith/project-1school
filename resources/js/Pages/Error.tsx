import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { AlertTriangle, ShieldAlert } from 'lucide-react';

/** Shown instead of Inertia's blank white pop-up when a page cannot be opened, or the person is not allowed to */
export default function ErrorPage({ message, title, kind = 'denied' }: { message: string; title?: string; kind?: 'denied' | 'error' }) {
    const Icon = kind === 'denied' ? ShieldAlert : AlertTriangle;

    return (
        <AppLayout breadcrumbs={[{ label: title ?? 'Not allowed' }]}>
            <div className="mx-auto flex max-w-md flex-col items-center gap-3 py-16 text-center">
                <Icon className="size-10 text-amber-600" aria-hidden="true" />
                <h1 className="text-xl font-semibold text-slate-900 dark:text-white">{message}</h1>
                {kind === 'denied' && <p className="text-sm text-slate-500">If you think you should be able to, ask your school admin to change your access.</p>}
                <button type="button" onClick={() => window.history.back()} className="mt-2 inline-flex h-9 items-center rounded-lg border border-slate-200 px-4 text-sm font-medium hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5">Go back</button>
                <Link href="/" className="text-sm text-indigo-600 hover:underline">Go to the start page</Link>
            </div>
        </AppLayout>
    );
}
