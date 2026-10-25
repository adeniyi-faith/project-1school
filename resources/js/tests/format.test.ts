import { describe, expect, it } from 'vitest';
import { initials, naira, nairaCompact, ordinal } from '@/lib/format';

describe('naira', () => {
    it('shows whole naira without kobo', () => {
        expect(naira(150000)).toBe('₦150,000');
    });

    it('never hides kobo that is really there', () => {
        expect(naira(98999.45)).toBe('₦98,999.45');
        expect(naira(0.45)).toBe('₦0.45');
    });

    it('can be forced to show kobo', () => {
        expect(naira(1500, { kobo: true })).toBe('₦1,500.00');
    });

    it('treats empty or broken values as zero', () => {
        expect(naira(null)).toBe('₦0');
        expect(naira(undefined)).toBe('₦0');
        expect(naira('abc')).toBe('₦0');
    });
});

describe('nairaCompact', () => {
    it('shortens big amounts', () => {
        expect(nairaCompact(5_440_000)).toBe('₦5.44M');
        expect(nairaCompact(820_000)).toBe('₦820K');
        expect(nairaCompact(2_500_000_000)).toBe('₦2.5B');
    });

    it('keeps small amounts whole and keeps the minus sign', () => {
        expect(nairaCompact(9_500)).toBe('₦9,500');
        expect(nairaCompact(-820_000)).toBe('-₦820K');
    });
});

describe('ordinal', () => {
    it('uses st, nd, rd and th correctly, including the teens', () => {
        expect([1, 2, 3, 4, 11, 12, 13, 21, 22, 23, 101, 111].map(ordinal)).toEqual(
            ['1st', '2nd', '3rd', '4th', '11th', '12th', '13th', '21st', '22nd', '23rd', '101st', '111th'],
        );
    });

    it('shows a dash when there is no position', () => {
        expect(ordinal(null)).toBe('—');
    });
});

describe('initials', () => {
    it('takes the first letters of the first two words', () => {
        expect(initials('Adaeze Chioma Okafor')).toBe('AC');
        expect(initials('  ade  ')).toBe('A');
    });

    it('shows a question mark for no name', () => {
        expect(initials('')).toBe('?');
        expect(initials(null)).toBe('?');
    });
});
