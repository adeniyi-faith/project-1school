import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { FieldError, FormField } from '@/components/app/FormField';

describe('FieldError', () => {
    it('shows the message', () => {
        render(<FieldError>The amount is too big.</FieldError>);
        expect(screen.getByRole('alert')).toHaveTextContent('The amount is too big.');
    });

    it('shows nothing when there is no error', () => {
        const { container } = render(<FieldError>{undefined}</FieldError>);
        expect(container).toBeEmptyDOMElement();
    });
});

describe('FormField', () => {
    it('joins the label to its box', () => {
        render(<FormField label="Amount" htmlFor="amount"><input id="amount" /></FormField>);
        expect(screen.getByLabelText('Amount')).toBeInTheDocument();
    });

    it('shows the hint until there is an error, then only the error', () => {
        const { rerender } = render(<FormField label="Amount" htmlFor="a" hint="Up to ₦5,000"><input id="a" /></FormField>);
        expect(screen.getByText('Up to ₦5,000')).toBeInTheDocument();

        rerender(<FormField label="Amount" htmlFor="a" hint="Up to ₦5,000" error="Too much"><input id="a" /></FormField>);
        expect(screen.queryByText('Up to ₦5,000')).not.toBeInTheDocument();
        expect(screen.getByRole('alert')).toHaveTextContent('Too much');
    });
});
