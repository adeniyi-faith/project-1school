import type { ElementType, ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { initials } from '@/lib/format';

/* ---------- Page header ---------- */
export function PageHeader({ title, description, actions, className }: { title: string; description?: ReactNode; actions?: ReactNode; className?: string }) {
    return (
        <div className={cn('flex flex-wrap items-end justify-between gap-x-6 gap-y-3', className)}>
            <div className="min-w-0">
                <h1 className="text-[1.65rem] font-semibold leading-tight tracking-[-0.03em] text-slate-900 dark:text-white">{title}</h1>
                {description && <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{description}</p>}
            </div>
            {actions && <div className="flex flex-wrap items-center gap-2 max-md:hidden">{actions}</div>}
        </div>
    );
}

/* ---------- Surfaces ---------- */
export function Panel({
    title, description, action, children, className, bodyClassName, flush,
}: {
    title?: ReactNode; description?: ReactNode; action?: ReactNode; children: ReactNode; className?: string; bodyClassName?: string; flush?: boolean;
}) {
    return (
        <section className={cn('min-w-0 rounded-xl border border-slate-200 bg-white shadow-xs dark:border-white/[0.08] dark:bg-slate-900', className)}>
            {(title || action) && (
                <header className="flex flex-wrap items-start justify-between gap-3 px-4 pt-4 sm:px-5">
                    <div className="min-w-0">
                        {title && <h2 className="text-[15px] font-semibold tracking-[-0.01em] text-slate-900 dark:text-white">{title}</h2>}
                        {description && <p className="mt-0.5 text-[13px] text-slate-500 dark:text-slate-400">{description}</p>}
                    </div>
                    {action && <div className="text-[13px] sm:shrink-0">{action}</div>}
                </header>
            )}
            <div className={cn(flush ? 'pt-3' : 'px-4 pb-5 pt-4 sm:px-5', bodyClassName)}>{children}</div>
        </section>
    );
}

/* ---------- Key numbers: one bordered strip with hairline dividers ---------- */
export type StatItem = { label: string; value: ReactNode; hint?: ReactNode; tone?: 'default' | 'good' | 'warn' | 'bad' };

const statTone = {
    default: 'text-slate-900 dark:text-white',
    good: 'text-emerald-700 dark:text-emerald-400',
    warn: 'text-amber-700 dark:text-amber-400',
    bad: 'text-red-700 dark:text-red-400',
};

export function StatStrip({ items, className }: { items: StatItem[]; className?: string }) {
    // Hairline dividers come from a 1px gap over a tinted background, so any column count stays clean.
    const cols = items.length >= 4 ? 'grid-cols-2 lg:grid-cols-4' : items.length === 3 ? 'grid-cols-2 sm:grid-cols-3' : 'grid-cols-2';
    return (
        <dl className={cn('grid gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 shadow-xs dark:border-white/[0.08] dark:bg-white/[0.08]', cols, className)}>
            {items.map((it) => (
                <div key={it.label} className="min-w-0 bg-white px-4 py-4 last:odd:max-sm:col-span-2 sm:px-5 dark:bg-slate-900">
                    <dt className="text-[13px] text-slate-500 dark:text-slate-400">{it.label}</dt>
                    <dd className={cn('mt-1.5 truncate text-2xl font-semibold leading-none tracking-[-0.03em] tabular-nums sm:text-[1.65rem]', statTone[it.tone ?? 'default'])}>{it.value}</dd>
                    {it.hint && <p className="mt-2 text-xs text-slate-500 dark:text-slate-400">{it.hint}</p>}
                </div>
            ))}
        </dl>
    );
}

/* ---------- Status pill ---------- */
const pillTone = {
    good: 'bg-emerald-50 text-emerald-700 ring-emerald-600/15 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-400/20',
    warn: 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-400/20',
    bad: 'bg-red-50 text-red-700 ring-red-600/15 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-400/20',
    info: 'bg-indigo-50 text-indigo-700 ring-indigo-600/15 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-400/20',
    neutral: 'bg-slate-100 text-slate-600 ring-slate-500/10 dark:bg-white/[0.06] dark:text-slate-300 dark:ring-white/10',
};

export function Pill({ tone = 'neutral', children, className }: { tone?: keyof typeof pillTone; children: ReactNode; className?: string }) {
    return (
        <span className={cn('inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset', pillTone[tone], className)}>
            <span className="size-1.5 rounded-full bg-current opacity-70" aria-hidden="true" />
            {children}
        </span>
    );
}

/* ---------- Avatar ---------- */
const avatarTones = [
    'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200',
    'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200',
    'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-200',
    'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-200',
    'bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-200',
    'bg-slate-200 text-slate-700 dark:bg-white/10 dark:text-slate-200',
];

export function PersonAvatar({ name, src, className }: { name: string; src?: string | null; className?: string }) {
    const tone = avatarTones[[...name].reduce((a, c) => a + c.charCodeAt(0), 0) % avatarTones.length];
    if (src) return <img src={src} alt="" className={cn('size-9 shrink-0 rounded-full object-cover', className)} />;
    return (
        <span className={cn('flex size-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold', tone, className)} aria-hidden="true">
            {initials(name)}
        </span>
    );
}

/* ---------- Empty state ---------- */
export function EmptyState({ icon: Icon, title, text, action }: { icon: ElementType; title: string; text?: string; action?: ReactNode }) {
    return (
        <div className="flex flex-col items-center px-6 py-16 text-center">
            <span className="mb-4 flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-white/[0.06]">
                <Icon className="size-5" strokeWidth={1.6} />
            </span>
            <p className="text-sm font-medium text-slate-900 dark:text-white">{title}</p>
            {text && <p className="mt-1 max-w-xs text-sm text-slate-500 dark:text-slate-400">{text}</p>}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}

/* ---------- Segmented control (links or buttons) ---------- */
export function Segmented<T extends string>({ value, onChange, options, label }: { value: T; onChange: (v: T) => void; options: { value: T; label: string; count?: number }[]; label: string }) {
    return (
        <div role="group" aria-label={label} className="inline-flex max-w-full gap-0.5 overflow-x-auto rounded-lg bg-slate-100 p-0.5 scroll-quiet dark:bg-white/[0.06]">
            {options.map((o) => (
                <button
                    key={o.value}
                    type="button"
                    aria-pressed={value === o.value}
                    onClick={() => onChange(o.value)}
                    className={cn(
                        'flex items-center gap-1.5 whitespace-nowrap rounded-md px-3 py-1.5 text-[13px] font-medium outline-none transition-colors focus-visible:ring-2 focus-visible:ring-indigo-400',
                        value === o.value
                            ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-800 dark:text-white'
                            : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200',
                    )}
                >
                    {o.label}
                    {o.count !== undefined && <span className="text-xs tabular-nums text-slate-400">{o.count}</span>}
                </button>
            ))}
        </div>
    );
}
