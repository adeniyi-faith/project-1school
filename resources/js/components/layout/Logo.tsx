import { cn } from '@/lib/utils';

/** SchoolRuns mark: a mortarboard on a rounded square. */
export function LogoMark({ className }: { className?: string }) {
    return (
        <span
            className={cn(
                'inline-flex size-8 shrink-0 items-center justify-center rounded-[10px] bg-gradient-to-b from-indigo-500 to-indigo-700 text-white shadow-[inset_0_1px_0_oklch(1_0_0/0.25),0_1px_2px_oklch(0.2_0.1_262/0.4)]',
                className,
            )}
            aria-hidden="true"
        >
            <svg viewBox="0 0 24 24" className="size-[18px]" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
                <path d="M22 10 12 5 2 10l10 5 10-5Z" />
                <path d="M6 12.2V17c2.8 2 9.2 2 12 0v-4.8" />
            </svg>
        </span>
    );
}

export function initialsOf(name: string | undefined | null) {
    if (!name) return 'U';
    return name.trim().split(/\s+/).map((n) => n[0]).join('').slice(0, 2).toUpperCase();
}
