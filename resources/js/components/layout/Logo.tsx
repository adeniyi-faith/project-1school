import { cn } from '@/lib/utils';

/**
 * The Adino wordmark: "Adino" large in a refined serif with "School Systems" small and spaced
 * out underneath. It takes its colour from the text around it, so it works on dark and light.
 */
export function Wordmark({ className, size = 'md' }: { className?: string; size?: 'sm' | 'md' | 'lg' }) {
    const sizes = { sm: 'text-[13px]', md: 'text-[15px]', lg: 'text-[19px]' } as const;
    return (
        <span className={cn('inline-flex flex-col leading-none', sizes[size], className)} role="img" aria-label="Adino School Systems">
            <span aria-hidden="true" className="font-[Fraunces,Georgia,'Times_New_Roman',serif] text-[1.65em] font-semibold leading-[0.95] tracking-[-0.025em]" style={{ fontVariationSettings: '"opsz" 72' }}>
                Adino
            </span>
            <span aria-hidden="true" className="mt-[0.5em] pl-[0.1em] text-[0.46em] font-semibold uppercase tracking-[0.34em] opacity-70">
                School Systems
            </span>
        </span>
    );
}

/** Just the first letter, set in the same serif, for the narrow collapsed menu. */
export function WordmarkLetter({ className }: { className?: string }) {
    return (
        <span className={cn('font-[Fraunces,Georgia,serif] text-[26px] font-semibold leading-none tracking-tight', className)} aria-label="Adino" role="img">A</span>
    );
}

export function initialsOf(name: string | undefined | null) {
    if (!name) return 'U';
    return name.trim().split(/\s+/).map((n) => n[0]).join('').slice(0, 2).toUpperCase();
}
