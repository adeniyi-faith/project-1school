import type { ReactNode } from 'react';
import { MoreHorizontal } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

export interface RowAction {
    label: string;
    icon?: ReactNode;
    onClick: () => void;
    /** Red text, for delete and similar */
    danger?: boolean;
}

/**
 * One "..." button per list row that opens Edit, Delete and so on.
 * Keeps long lists to one button per row instead of one per action.
 */
export function RowActions({ label, actions }: { label: string; actions: RowAction[] }) {
    if (actions.length === 0) return null;
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" aria-label={label}><MoreHorizontal className="size-4" /></Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {actions.map(a => (
                    <DropdownMenuItem key={a.label} onClick={a.onClick}
                        className={a.danger ? 'flex items-center gap-2 text-sm text-red-600 focus:text-red-600' : 'flex items-center gap-2 text-sm'}>
                        {a.icon} {a.label}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
