import type { CommentBankEntry } from '@/Types';
import { ordinal } from '@/lib/format';

/** Saved comments that fit an average, best range first */
export function fittingComments(bank: CommentBankEntry[], writer: string, average: number | null): CommentBankEntry[] {
    if (average === null) return [];
    return bank.filter(c => c.writer_permission === writer && average >= Number(c.min_average) && average <= Number(c.max_average));
}

/** Fill the blanks ({name}, {he_she} ...) the same way the server does */
export function renderComment(text: string, s: { first_name: string; name: string; gender: string | null; average: number | null; position: number | null; class_size: number | null }): string {
    const female = s.gender === 'female';
    const male = s.gender === 'male';
    const avg = s.average === null ? '' : `${Number(Number(s.average).toFixed(2))}%`;
    const pos = s.position ? `${ordinal(s.position)}${s.class_size ? ` of ${s.class_size}` : ''}` : '';
    const blanks: Record<string, string> = {
        '{name}': s.first_name,
        '{full_name}': s.name,
        '{average}': avg,
        '{position}': pos,
        '{he_she}': female ? 'she' : male ? 'he' : 'they',
        '{him_her}': female ? 'her' : male ? 'him' : 'them',
        '{his_her}': female ? 'her' : male ? 'his' : 'their',
    };
    return text
        .replace(/\{(name|full_name|average|position|he_she|him_her|his_her)\}/g, m => blanks[m] ?? m)
        // "{he_she} works hard" starts a sentence, so it should read "She works hard"
        .replace(/(^|[.!?]\s+)(\p{Ll})/gu, (_m, before: string, letter: string) => before + letter.toUpperCase());
}
