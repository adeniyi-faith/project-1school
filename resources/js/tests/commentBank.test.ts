import { describe, expect, it } from 'vitest';
import { renderComment } from '@/lib/commentBank';

const girl = { first_name: 'Ada', name: 'Ada Obi', gender: 'female', average: 72.456, position: 2, class_size: 30 };

describe('renderComment', () => {
    it('fills the blanks like the server does', () => {
        expect(renderComment('{name} scored {average}, {position}.', girl)).toBe('Ada scored 72.46%, 2nd of 30.');
    });

    it('uses the right pronouns', () => {
        expect(renderComment('We are proud of {him_her}. {he_she} works hard.', girl)).toBe('We are proud of her. She works hard.');
        expect(renderComment('{he_she} works hard.', { ...girl, gender: 'male' })).toBe('He works hard.');
        expect(renderComment('{his_her} effort', { ...girl, gender: null })).toBe('Their effort');
    });

    it('leaves unknown blanks alone', () => {
        expect(renderComment('Hello {something}', girl)).toBe('Hello {something}');
    });

    it('leaves the average empty when there is none', () => {
        expect(renderComment('Average: {average}', { ...girl, average: null })).toBe('Average: ');
    });
});
