import { Pill } from '@/components/app/kit';
import type { InvoiceStatus as Status, PaymentMethod } from '@/Types';

const TONE = { unpaid: 'bad', partial: 'warn', paid: 'good', void: 'neutral' } as const;
const LABEL: Record<Status, string> = { unpaid: 'Unpaid', partial: 'Part paid', paid: 'Paid', void: 'Cancelled' };

export function InvoiceStatus({ status }: { status: Status }) {
    return <Pill tone={TONE[status]}>{LABEL[status]}</Pill>;
}

export const METHOD_LABELS: Record<PaymentMethod, string> = {
    cash: 'Cash', bank_transfer: 'Bank transfer', pos: 'POS', card: 'Card', online: 'Online', ussd: 'USSD',
};
