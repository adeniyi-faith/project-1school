import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { ShieldAlert } from 'lucide-react';

/** Shown instead of a blank pop-up when the person is not allowed to open or do something */
export default function ErrorPage({ message }: { message: string }) {
    return (
        <AppLayout breadcrumbs={[{ label: 'Not allowed' }]}>
            <div className="mx-auto flex max-w-md flex-col items-center gap-3 py-16 text-center">
                <ShieldAlert className="size-10 text-amber-600" aria-hidden="true" />
                <h1 className="text-xl font-semibold text-slate-900 dark:text-white">{message}</h1>
                <p className="text-sm text-slate-500">If you think you should be able to, ask your school admin to change your access.</p>
                <button type="button" onClick={() => window.history.back()} className="mt-2 inline-flex h-9 items-center rounded-lg border border-slate-200 px-4 text-sm font-medium hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5">Go back</button>
                <Link href="/" className="text-sm text-indigo-600 hover:underline">Go to the start page</Link>
            </div>
        </AppLayout>
    );
}
