import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Copy, Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import type { PageProps } from '@/Types';

/**
 * Shows new sign-in details once, right after a login was made or a password was reset.
 * The password is never stored in readable form, so this is the only time it can be read.
 */
export default function LoginDetailsDialog() {
    const { flash } = usePage<PageProps>().props;
    const [rows, setRows] = useState<NonNullable<PageProps['flash']['login_details']>>([]);
    const [copied, setCopied] = useState(false);

    useEffect(() => {
        if (flash?.login_details?.length) {
            setRows(flash.login_details);
            setCopied(false);
        }
    }, [flash]);

    const text = rows.map(r => `${r.name}\nEmail: ${r.email}\nPassword: ${r.password}`).join('\n\n');

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(text);
            setCopied(true);
        } catch {
            /* the details stay visible to copy by hand */
        }
    };

    return (
        <Dialog open={rows.length > 0} onOpenChange={(o) => { if (!o) setRows([]); }}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Sign-in details</DialogTitle>
                    <DialogDescription>Copy these now and give them to the person. For safety the password cannot be shown again, but you can always make a new one.</DialogDescription>
                </DialogHeader>
                <div className="space-y-3">
                    {rows.map((r, i) => (
                        <div key={i} className="rounded-lg border border-slate-200 dark:border-slate-800 p-3 text-sm">
                            <p className="font-semibold text-slate-900 dark:text-white">{r.name}</p>
                            <p className="mt-1 text-slate-600 dark:text-slate-300">Email: <span className="font-mono">{r.email}</span></p>
                            <p className="text-slate-600 dark:text-slate-300">Password: <span className="font-mono font-semibold">{r.password}</span></p>
                        </div>
                    ))}
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={copy} className="inline-flex items-center gap-2">
                        {copied ? <Check className="w-4 h-4" /> : <Copy className="w-4 h-4" />} {copied ? 'Copied' : 'Copy'}
                    </Button>
                    <Button onClick={() => setRows([])} className="bg-indigo-600 hover:bg-indigo-700 text-white">Done</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
