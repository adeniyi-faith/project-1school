import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Pill } from '@/components/app/kit';
import { Ban, Download } from 'lucide-react';
import type { CertificateRow } from '@/Types';

/** Certificates with Download and (for staff who issue them) Revoke */
export function CertificateList({ items, canRevoke, showStudent = false }: { items: CertificateRow[]; canRevoke: boolean; showStudent?: boolean }) {
    const [revoking, setRevoking] = useState<CertificateRow | null>(null);
    const [reason, setReason] = useState('');
    const [error, setError] = useState<string | null>(null);

    function revoke() {
        if (!revoking) return;
        router.post(`/school/certificates/${revoking.id}/revoke`, { reason }, {
            preserveScroll: true,
            onSuccess: () => { setRevoking(null); setReason(''); setError(null); },
            onError: e => setError(e.reason ?? 'Could not revoke this certificate.'),
        });
    }

    return (
        <>
            <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                {items.map(c => (
                    <li key={c.id} className="flex flex-wrap items-center gap-3 py-3">
                        <div className="min-w-0 flex-1">
                            <p className="text-sm font-medium text-slate-900 dark:text-white">
                                {c.title} · {c.serial}
                                {showStudent && <span className="font-normal text-slate-500"> · {c.student}{c.admission_no ? ` (${c.admission_no})` : ''}</span>}
                            </p>
                            <p className="text-xs text-slate-500">
                                Issued {c.issued_on ? new Date(c.issued_on).toLocaleDateString() : ''}{c.issued_by ? ` by ${c.issued_by}` : ''} · check code {c.verify_code}
                            </p>
                            {c.revoked && c.revoke_reason && <p className="mt-0.5 text-xs text-red-600">Revoked: {c.revoke_reason}</p>}
                        </div>
                        {c.revoked ? <Pill tone="bad">Revoked</Pill> : <Pill tone="good">Valid</Pill>}
                        <a href={`/school/certificates/${c.id}/pdf`} target="_blank" rel="noopener" className="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 px-3 text-sm text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:text-slate-200">
                            <Download className="size-3.5" /> PDF
                        </a>
                        {canRevoke && !c.revoked && (
                            <Button variant="ghost" size="sm" onClick={() => { setRevoking(c); setError(null); }} className="text-red-600"><Ban className="size-3.5" /> Revoke</Button>
                        )}
                    </li>
                ))}
            </ul>

            <Dialog open={revoking !== null} onOpenChange={o => !o && setRevoking(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Revoke {revoking?.serial}?</DialogTitle></DialogHeader>
                    <p className="text-sm text-slate-600 dark:text-slate-300">
                        The certificate stays on record, but anyone who checks its code will see that it is no longer valid. To correct a mistake, revoke it and issue a new one.
                    </p>
                    <div className="space-y-1.5">
                        <Label htmlFor="revoke-reason">Reason</Label>
                        <Textarea id="revoke-reason" rows={2} maxLength={255} value={reason} onChange={e => setReason(e.target.value)} placeholder="e.g. Wrong leaving date" />
                        {error && <p className="text-xs text-red-500">{error}</p>}
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setRevoking(null)}>Cancel</Button>
                        <Button onClick={revoke} disabled={!reason.trim()} className="bg-red-600 text-white hover:bg-red-700">Revoke</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
