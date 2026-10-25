import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export interface PagerMeta { from: number | null; to: number | null; total: number; last_page?: number }
export interface PagerLinks { prev: string | null; next: string | null }

/**
 * "1–50 of 230" with Previous and Next, for the bottom of a list. Shows nothing when everything fits on one page.
 * Give it the `meta` and `links` the controller sends for a paginated list.
 */
export function Pager({ meta, links, className }: { meta: PagerMeta; links: PagerLinks; className?: string }) {
    // Some lists do not send the page count; then the presence of a previous or next page says it all
    const onePage = meta.last_page !== undefined ? meta.last_page <= 1 : !links.prev && !links.next;
    if (onePage) return null;

    return (
        <div className={cn('flex items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 text-sm text-slate-500 sm:px-5 dark:border-white/[0.06]', className)}>
            <span className="tabular-nums">{meta.from}–{meta.to} of {meta.total}</span>
            <div className="flex gap-2">
                <Button variant="outline" size="sm" disabled={!links.prev} onClick={() => links.prev && router.get(links.prev)}>Previous</Button>
                <Button variant="outline" size="sm" disabled={!links.next} onClick={() => links.next && router.get(links.next)}>Next</Button>
            </div>
        </div>
    );
}
