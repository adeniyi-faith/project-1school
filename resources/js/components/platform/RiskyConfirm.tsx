import { router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { PageProps } from '@/Types';

interface Props {
    open: boolean;
    onClose: () => void;
    /** The address that deletes it */
    url: string;
    title: string;
    description: React.ReactNode;
    /** Shown on the red button */
    actionLabel?: string;
    /** When given, the person must type this name to go on */
    typeName?: string;
    /** This action can wait for a second person's approval when that is switched on */
    canNeedApproval?: boolean;
}

/**
 * The confirmation for a risky platform action: a written reason (kept in the staff activity log), the typed
 * name for the worst ones, and a note when a second person has to approve it first.
 */
export default function RiskyConfirm({ open, onClose, url, title, description, actionLabel = 'Delete', typeName, canNeedApproval = false }: Props) {
    const waits = usePage<PageProps>().props.risky?.approval === true && canNeedApproval;
    const [reason, setReason] = useState('');
    const [typed, setTyped] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        if (open) { setReason(''); setTyped(''); setErrors({}); }
    }, [open]);

    const go = () => {
        setBusy(true);
        router.delete(url, {
            data: { reason, confirm_name: typed },
            preserveScroll: true,
            onError: (e) => setErrors(e as Record<string, string>),
            onSuccess: () => onClose(),
            onFinish: () => setBusy(false),
        });
    };

    const ready = reason.trim().length >= 10 && (!typeName || typed.trim().length > 0);

    return (
        <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                <div className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="risky-reason">Why are you doing this?</Label>
                        <Textarea id="risky-reason" rows={3} value={reason} onChange={(e) => setReason(e.target.value)} placeholder="For example: the school asked us to close its account in writing on 12 October." />
                        <p className="text-xs text-slate-500">Required. It is written in the staff activity log next to your name.</p>
                        {errors.reason && <p className="text-xs font-medium text-red-600">{errors.reason}</p>}
                    </div>
                    {typeName && (
                        <div className="space-y-1.5">
                            <Label htmlFor="risky-name">Type <span className="font-semibold">{typeName}</span> to confirm</Label>
                            <Input id="risky-name" value={typed} onChange={(e) => setTyped(e.target.value)} autoComplete="off" />
                            {errors.confirm_name && <p className="text-xs font-medium text-red-600">{errors.confirm_name}</p>}
                        </div>
                    )}
                    {waits && (
                        <p className="rounded-lg bg-violet-50 p-3 text-sm text-violet-800 dark:bg-violet-950/40 dark:text-violet-200">
                            Another person has to approve this first. Pressing the button sends your request to them; nothing is changed yet.
                        </p>
                    )}
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>Cancel</Button>
                    <Button className="bg-red-600 text-white hover:bg-red-700" disabled={!ready || busy} onClick={go}>
                        <Trash2 className="mr-1.5 size-4" /> {waits ? 'Ask for approval' : actionLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
