import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { useEffect } from 'react';

export interface ClubFields { id?: number; name: string; description: string | null; meets: string | null; patron_id: number | null; is_active: boolean }

/** Add or edit a club: name, what it does, when it meets and the teacher in charge */
export function ClubDialog({ club, open, onClose, staff }: { club: ClubFields | null; open: boolean; onClose: () => void; staff: { id: number; name: string }[] }) {
    const form = useForm({ name: '', description: '', meets: '', patron_id: '', is_active: true });

    useEffect(() => {
        if (!open) return;
        form.clearErrors();
        form.setData({
            name: club?.name ?? '', description: club?.description ?? '', meets: club?.meets ?? '',
            patron_id: club?.patron_id ? String(club.patron_id) : '', is_active: club?.is_active ?? true,
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, club?.id]);

    function save(e: React.FormEvent) {
        e.preventDefault();
        form.transform(d => ({ ...d, patron_id: d.patron_id || null }));
        if (club?.id) form.put(`/school/clubs/${club.id}`, { preserveScroll: true, onSuccess: onClose });
        else form.post('/school/clubs', { onSuccess: onClose });
    }

    return (
        <Dialog open={open} onOpenChange={o => !o && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader><DialogTitle>{club?.id ? 'Edit club' : 'New club'}</DialogTitle></DialogHeader>
                <form onSubmit={save} className="space-y-4 pt-2">
                    <div className="space-y-1.5">
                        <Label htmlFor="c-name">Name</Label>
                        <Input id="c-name" value={form.data.name} onChange={e => form.setData('name', e.target.value)} placeholder="e.g. JETS Club" />
                        {form.errors.name && <p className="text-xs text-red-500">{form.errors.name}</p>}
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="c-desc">What it does (optional)</Label>
                        <Input id="c-desc" value={form.data.description} onChange={e => form.setData('description', e.target.value)} />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="c-meets">When it meets (optional)</Label>
                        <Input id="c-meets" value={form.data.meets} onChange={e => form.setData('meets', e.target.value)} placeholder="e.g. Wednesdays, 2:30pm" />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="c-patron">Teacher in charge</Label>
                        <select id="c-patron" className="h-9 w-full rounded-lg border border-slate-200 bg-transparent px-2 text-sm dark:border-white/10 dark:bg-slate-900" value={form.data.patron_id} onChange={e => form.setData('patron_id', e.target.value)}>
                            <option value="">Nobody yet</option>
                            {staff.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                        </select>
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={form.data.is_active} onChange={e => form.setData('is_active', e.target.checked)} />
                        Running this year
                    </label>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>Cancel</Button>
                        <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Save</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
