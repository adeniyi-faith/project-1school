import { describe, expect, it } from 'vitest';
import { inScope } from '@/lib/scope';

describe('inScope', () => {
    it('matches topics whatever the capitals or punctuation', () => {
        expect(inScope('Number Bases', ['number bases'])).toBe(true);
        expect(inScope('Number-bases', ['Number bases'])).toBe(true);
    });

    it('matches a short topic inside a longer one, either way round', () => {
        expect(inScope('Number bases', ['Number bases and numerals'])).toBe(true);
        expect(inScope('Number bases and numerals', ['Number bases'])).toBe(true);
    });

    it('does not match other topics or empty ones', () => {
        expect(inScope('Algebra', ['Geometry'])).toBe(false);
        expect(inScope('', ['Geometry'])).toBe(false);
        expect(inScope(null, ['Geometry'])).toBe(false);
        expect(inScope('Algebra', [''])).toBe(false);
    });
});
