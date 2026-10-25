import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { PageHeader, Panel, Pill, StatStrip } from '@/components/app/kit';

describe('PageHeader', () => {
    it('shows the title and the one-line description', () => {
        render(<PageHeader title="Student ID cards" description="Make cards for the whole school." />);
        expect(screen.getByRole('heading', { name: 'Student ID cards' })).toBeInTheDocument();
        expect(screen.getByText('Make cards for the whole school.')).toBeInTheDocument();
    });
});

describe('Panel', () => {
    it('shows its title and content', () => {
        render(<Panel title="Status">All good</Panel>);
        expect(screen.getByRole('heading', { name: 'Status' })).toBeInTheDocument();
        expect(screen.getByText('All good')).toBeInTheDocument();
    });
});

describe('StatStrip', () => {
    it('shows every number with its label and hint', () => {
        render(<StatStrip items={[
            { label: 'Active students', value: 120 },
            { label: 'Need a card', value: 7, tone: 'warn', hint: 'Use “Make missing cards”' },
        ]} />);
        expect(screen.getByText('Active students')).toBeInTheDocument();
        expect(screen.getByText('120')).toBeInTheDocument();
        expect(screen.getByText('7')).toBeInTheDocument();
        expect(screen.getByText('Use “Make missing cards”')).toBeInTheDocument();
    });
});

describe('Pill', () => {
    it('shows its words', () => {
        render(<Pill tone="good">Default</Pill>);
        expect(screen.getByText('Default')).toBeInTheDocument();
    });
});
