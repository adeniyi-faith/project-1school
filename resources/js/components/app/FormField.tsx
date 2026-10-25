import type { ReactNode } from 'react';
import { Label } from '@/components/ui/label';

/** The red line under a form control. Shows nothing when there is no error. */
export function FieldError({ children }: { children?: string | null }) {
    return children ? <p role="alert" className="text-xs text-red-500">{children}</p> : null;
}

/** A label, one control, a hint and its error message, stacked the same way on every form. */
export function FormField({ label, htmlFor, error, hint, children, className }: {
    label: string; htmlFor?: string; error?: string | null; hint?: ReactNode; children: ReactNode; className?: string;
}) {
    return (
        <div className={className ?? 'space-y-1.5'}>
            <Label htmlFor={htmlFor}>{label}</Label>
            {children}
            {hint && !error && <p className="text-xs text-slate-500">{hint}</p>}
            <FieldError>{error}</FieldError>
        </div>
    );
}
