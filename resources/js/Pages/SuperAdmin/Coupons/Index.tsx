import AppLayout from '@/Layouts/AppLayout';
import { useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Tag, Plus, Edit, Trash2 } from 'lucide-react';

interface Coupon {
    id: number; code: string; type: 'percent' | 'fixed'; value: string;
    expires_at: string | null; max_uses: number; used_count: number;
    is_active: boolean; description: string | null;
}
interface Props { coupons: Coupon[]; }

export default function CouponsIndex({ coupons }: Props) {
    const [showModal, setShowModal] = useState(false);
    const [editCoupon, setEditCoupon] = useState<Coupon | null>(null);
    const [delCoupon, setDelCoupon]   = useState<Coupon | null>(null);

    const form = useForm({
        code: '', type: 'percent' as 'percent' | 'fixed', value: '',
        expires_at: '', max_uses: '0', is_active: true, description: '',
    });

    function openCreate() { form.reset(); form.clearErrors(); setEditCoupon(null); setShowModal(true); }
    function openEdit(c: Coupon) {
        form.setData({
            code: c.code, type: c.type, value: c.value,
            expires_at: c.expires_at ?? '', max_uses: String(c.max_uses),
            is_active: c.is_active, description: c.description ?? '',
        });
        form.clearErrors(); setEditCoupon(c); setShowModal(true);
    }
    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (editCoupon) {
            form.put(`/super-admin/coupons/${editCoupon.id}`, { onSuccess: () => setShowModal(false) });
        } else {
            form.post('/super-admin/coupons', { onSuccess: () => setShowModal(false) });
        }
    }
    function confirmDelete() {
        if (!delCoupon) return;
        router.delete(`/super-admin/coupons/${delCoupon.id}`, { onSuccess: () => setDelCoupon(null) });
    }

    return (
        <AppLayout title="Coupons">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Discount Coupons</h1>
                        <p className="text-sm text-slate-500 mt-0.5">Create and manage subscription discount codes</p>
                    </div>
                    <Button onClick={openCreate} className="gap-2"><Plus className="w-4 h-4" /> New Coupon</Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Tag className="w-4 h-4 text-indigo-500" /> Coupons ({coupons.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-200 dark:border-slate-700 text-xs uppercase text-slate-500">
                                        <th className="text-left py-3 px-4 font-medium">Code</th>
                                        <th className="text-left py-3 px-4 font-medium">Discount</th>
                                        <th className="text-left py-3 px-4 font-medium">Uses</th>
                                        <th className="text-left py-3 px-4 font-medium">Expires</th>
                                        <th className="text-left py-3 px-4 font-medium">Status</th>
                                        <th className="text-right py-3 px-4 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {coupons.length === 0 && (
                                        <tr><td colSpan={6} className="text-center py-10 text-slate-400">No coupons yet.</td></tr>
                                    )}
                                    {coupons.map(c => (
                                        <tr key={c.id} className="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                            <td className="py-3 px-4 font-mono font-semibold text-indigo-600 dark:text-indigo-400">{c.code}</td>
                                            <td className="py-3 px-4">
                                                {c.type === 'percent' ? `${c.value}%` : `$${c.value}`}
                                            </td>
                                            <td className="py-3 px-4 text-slate-500">
                                                {c.used_count} / {c.max_uses === 0 ? '∞' : c.max_uses}
                                            </td>
                                            <td className="py-3 px-4 text-slate-500 text-xs">
                                                {c.expires_at ? new Date(c.expires_at).toLocaleDateString() : '—'}
                                            </td>
                                            <td className="py-3 px-4">
                                                <Badge variant={c.is_active ? 'default' : 'secondary'}>{c.is_active ? 'Active' : 'Inactive'}</Badge>
                                            </td>
                                            <td className="py-3 px-4">
                                                <div className="flex items-center justify-end gap-1">
                                                    <button onClick={() => openEdit(c)} className="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-indigo-600">
                                                        <Edit className="w-3.5 h-3.5" />
                                                    </button>
                                                    <button onClick={() => setDelCoupon(c)} className="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-red-600">
                                                        <Trash2 className="w-3.5 h-3.5" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Modal */}
            <Dialog open={showModal} onOpenChange={setShowModal}>
                <DialogContent className="max-w-md">
                    <DialogHeader><DialogTitle>{editCoupon ? 'Edit Coupon' : 'New Coupon'}</DialogTitle></DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label>Coupon Code *</Label>
                            <Input value={form.data.code} onChange={e => form.setData('code', e.target.value.toUpperCase())} placeholder="SUMMER25" className="font-mono" />
                            {form.errors.code && <p className="text-xs text-red-500 mt-1">{form.errors.code}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Discount Type *</Label>
                                <Select value={form.data.type} onValueChange={v => form.setData('type', v as 'percent' | 'fixed')}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="percent">Percentage (%)</SelectItem>
                                        <SelectItem value="fixed">Fixed Amount ($)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>Value *</Label>
                                <Input type="number" step="0.01" value={form.data.value} onChange={e => form.setData('value', e.target.value)} placeholder={form.data.type === 'percent' ? '25' : '10.00'} />
                                {form.errors.value && <p className="text-xs text-red-500 mt-1">{form.errors.value}</p>}
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Max Uses (0 = unlimited)</Label>
                                <Input type="number" value={form.data.max_uses} onChange={e => form.setData('max_uses', e.target.value)} />
                            </div>
                            <div>
                                <Label>Expires At</Label>
                                <Input type="date" value={form.data.expires_at} onChange={e => form.setData('expires_at', e.target.value)} />
                            </div>
                        </div>
                        <div>
                            <Label>Description</Label>
                            <Input value={form.data.description} onChange={e => form.setData('description', e.target.value)} placeholder="Optional note" />
                        </div>
                        <div className="flex items-center gap-2">
                            <input type="checkbox" id="coupon_active" checked={form.data.is_active}
                                onChange={e => form.setData('is_active', e.target.checked)} className="rounded" />
                            <label htmlFor="coupon_active" className="text-sm">Active</label>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setShowModal(false)}>Cancel</Button>
                            <Button type="submit" disabled={form.processing}>{editCoupon ? 'Save Changes' : 'Create'}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Confirm */}
            <Dialog open={!!delCoupon} onOpenChange={open => !open && setDelCoupon(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader><DialogTitle>Delete Coupon</DialogTitle></DialogHeader>
                    <p className="text-sm text-slate-600 dark:text-slate-400">Delete coupon <strong className="font-mono">{delCoupon?.code}</strong>? This cannot be undone.</p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDelCoupon(null)}>Cancel</Button>
                        <Button className="bg-red-600 hover:bg-red-700 text-white" onClick={confirmDelete}>Delete</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
