/** Naira formatting used across fees, payroll, library fines and inventory. */
const nairaFull = new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', maximumFractionDigits: 0 });
const nairaFine = new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', minimumFractionDigits: 2, maximumFractionDigits: 2 });

export function naira(value: number | string | null | undefined, opts: { kobo?: boolean } = {}): string {
    const n = Number(value ?? 0);
    if (Number.isNaN(n)) return '₦0';
    return (opts.kobo ? nairaFine : nairaFull).format(n);
}

/** 5,440,000 -> ₦5.44M, 820,000 -> ₦820K. For tight spots such as dashboard cards and chart axes. */
export function nairaCompact(value: number): string {
    const n = Number(value) || 0;
    const abs = Math.abs(n);
    const sign = n < 0 ? '-' : '';
    if (abs >= 1_000_000_000) return `${sign}₦${trim(abs / 1_000_000_000)}B`;
    if (abs >= 1_000_000) return `${sign}₦${trim(abs / 1_000_000)}M`;
    if (abs >= 10_000) return `${sign}₦${trim(abs / 1_000, 0)}K`;
    return `${sign}₦${Math.round(abs).toLocaleString('en-NG')}`;
}

function trim(n: number, digits = 2) {
    return Number(n.toFixed(digits)).toString();
}

export function initials(name: string | null | undefined): string {
    if (!name) return '?';
    return name.trim().split(/\s+/).map((w) => w[0]).slice(0, 2).join('').toUpperCase();
}

/** 1 -> 1st, 2 -> 2nd, 3 -> 3rd, 11 -> 11th, 22 -> 22nd. For class positions. */
export function ordinal(n: number | null | undefined): string {
    if (n === null || n === undefined) return '—';
    const rem100 = n % 100;
    if (rem100 >= 11 && rem100 <= 13) return `${n}th`;
    return n + ({ 1: 'st', 2: 'nd', 3: 'rd' }[n % 10] ?? 'th');
}
