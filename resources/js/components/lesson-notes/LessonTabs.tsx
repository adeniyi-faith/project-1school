import { Link } from '@inertiajs/react';

const tabs = [
    { key: 'notes', label: 'Lesson notes', href: '/school/lesson-notes' },
    { key: 'schemes', label: 'Schemes of work', href: '/school/lesson-notes/schemes' },
    { key: 'formats', label: 'Formats', href: '/school/lesson-notes/formats' },
] as const;

/** The three parts of lesson planning, as links along the top */
export default function LessonTabs({ current }: { current: (typeof tabs)[number]['key'] }) {
    return (
        <nav aria-label="Lesson planning" className="flex gap-1 overflow-x-auto rounded-xl bg-slate-100 p-1 dark:bg-white/[0.06]">
            {tabs.map(t => (
                <Link
                    key={t.key}
                    href={t.href}
                    aria-current={current === t.key ? 'page' : undefined}
                    className={`whitespace-nowrap rounded-lg px-3.5 py-1.5 text-sm font-medium transition ${current === t.key ? 'bg-white text-slate-900 shadow-xs dark:bg-slate-800 dark:text-white' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'}`}
                >
                    {t.label}
                </Link>
            ))}
        </nav>
    );
}

export const statusLabel: Record<string, { label: string; tone: 'neutral' | 'info' | 'good' | 'bad' }> = {
    draft: { label: 'Draft', tone: 'neutral' },
    submitted: { label: 'Waiting for approval', tone: 'info' },
    approved: { label: 'Approved', tone: 'good' },
    rejected: { label: 'Sent back', tone: 'bad' },
};
