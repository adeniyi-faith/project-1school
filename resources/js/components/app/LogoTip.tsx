import { Link } from '@inertiajs/react';
import { Lightbulb } from 'lucide-react';

/** A small hint shown wherever a school logo ends up on paper */
export function LogoTip({ className = '' }: { className?: string }) {
    return (
        <p className={`flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:bg-amber-500/10 dark:text-amber-200 ${className}`}>
            <Lightbulb className="mt-0.5 size-3.5 shrink-0" />
            <span>
                For the best result, use a school logo with a <strong>transparent background</strong> (a PNG file). A logo with a white box around it shows as a white patch on coloured headers and on the faint background mark.{' '}
                <Link href="/school/settings" className="font-medium underline">Change the logo in Settings</Link>
            </span>
        </p>
    );
}
