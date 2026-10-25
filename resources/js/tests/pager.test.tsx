import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { Pager } from '@/components/app/Pager';

const get = vi.fn();
vi.mock('@inertiajs/react', () => ({ router: { get: (...a: unknown[]) => get(...a) } }));

const meta = { from: 51, to: 100, total: 230, last_page: 5 };

describe('Pager', () => {
    beforeEach(() => get.mockReset());

    it('shows where you are in the list', () => {
        render(<Pager meta={meta} links={{ prev: '/x?page=1', next: '/x?page=3' }} />);
        expect(screen.getByText('51–100 of 230')).toBeInTheDocument();
    });

    it('goes to the next and previous pages', async () => {
        render(<Pager meta={meta} links={{ prev: '/x?page=1', next: '/x?page=3' }} />);
        await userEvent.click(screen.getByRole('button', { name: 'Next' }));
        await userEvent.click(screen.getByRole('button', { name: 'Previous' }));
        expect(get).toHaveBeenNthCalledWith(1, '/x?page=3');
        expect(get).toHaveBeenNthCalledWith(2, '/x?page=1');
    });

    it('switches off the button that has nowhere to go', () => {
        render(<Pager meta={{ ...meta, from: 1 }} links={{ prev: null, next: '/x?page=2' }} />);
        expect(screen.getByRole('button', { name: 'Previous' })).toBeDisabled();
        expect(screen.getByRole('button', { name: 'Next' })).toBeEnabled();
    });

    it('shows nothing when everything fits on one page', () => {
        const { container } = render(<Pager meta={{ from: 1, to: 12, total: 12, last_page: 1 }} links={{ prev: null, next: null }} />);
        expect(container).toBeEmptyDOMElement();
    });

    it('works out one page from the links when the page count is not sent', () => {
        const { container } = render(<Pager meta={{ from: 1, to: 5, total: 5 }} links={{ prev: null, next: null }} />);
        expect(container).toBeEmptyDOMElement();
    });
});
