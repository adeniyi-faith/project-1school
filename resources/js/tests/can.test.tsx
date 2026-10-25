import { renderHook } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useCan } from '@/lib/can';

const usePage = vi.fn();
vi.mock('@inertiajs/react', () => ({ usePage: () => usePage() }));

function permissions(list: string[] | undefined) {
    usePage.mockReturnValue({ props: { auth: list === undefined ? undefined : { permissions: list } } });
    return renderHook(() => useCan()).result.current;
}

describe('useCan', () => {
    beforeEach(() => usePage.mockReset());

    it('is true only for permissions the person holds', () => {
        const can = permissions(['students.view', 'students.create']);
        expect(can('students.create')).toBe(true);
        expect(can('students.delete')).toBe(false);
    });

    it('lets a person with the all-access mark do everything', () => {
        expect(permissions(['*'])('anything.at.all')).toBe(true);
    });

    it('says no to everything when nobody is signed in', () => {
        expect(permissions(undefined)('students.view')).toBe(false);
    });
});
