import { useEffect } from 'react';
import { labelCells } from '@/components/ui/table';

/**
 * Some pages draw a plain <table> instead of our Table component. On phones those get the same
 * "stacked rows" treatment. A grid that must stay a grid (marks sheet, timetable) opts out with
 * the attribute data-no-stack.
 */
export function StackTables() {
    useEffect(() => {
        let frame = 0;
        const apply = () => {
            document.querySelectorAll<HTMLTableElement>('table:not([data-slot="table"]):not([data-no-stack])').forEach((table) => {
                const wrap = table.parentElement;
                if (wrap && wrap.dataset.stack === undefined) wrap.dataset.stack = '';
                labelCells(table);
            });
        };
        apply();
        const observer = new MutationObserver(() => {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(apply);
        });
        observer.observe(document.body, { childList: true, subtree: true });
        return () => {
            cancelAnimationFrame(frame);
            observer.disconnect();
        };
    }, []);

    return null;
}
