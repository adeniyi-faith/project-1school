import { DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ChevronDown, FileSpreadsheet } from 'lucide-react';

interface Link { label: string; href: string }

/** A button that opens a short list of broadsheet downloads (PDF and Excel) */
export function BroadsheetMenu({ className, groups }: { className: string; groups: { title: string; links: Link[] }[] }) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger className={className}>
                <FileSpreadsheet className="size-4" /> Broadsheet <ChevronDown className="size-3.5 opacity-60" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-56">
                {groups.map((g, i) => (
                    <DropdownMenuGroup key={g.title}>
                        {i > 0 && <DropdownMenuSeparator />}
                        <DropdownMenuLabel>{g.title}</DropdownMenuLabel>
                        {g.links.map(l => (
                            <DropdownMenuItem key={l.href} asChild>
                                <a href={l.href} target={l.href.includes('xlsx') ? undefined : '_blank'} rel="noopener">{l.label}</a>
                            </DropdownMenuItem>
                        ))}
                    </DropdownMenuGroup>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
