import AppLayout from '@/Layouts/AppLayout';
import { useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Package, Plus, Edit, Trash2, CheckSquare, Square } from 'lucide-react';

interface PkgModule { module_slug: string; }
interface Pkg {
    id: number; name: string; slug: string; description: string | null;
    price_monthly: string; price_yearly: string; max_students: number;
    max_staff: number; storage_gb: number; is_active: boolean;
    subscriptions_count: number; modules: PkgModule[];
}
interface Props { packages: Pkg[]; availableModules: string[]; }

const moduleLabel = (s: string) => s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

export default function PackagesIndex({ packages, availableModules }: Props) {
    const [showModal, setShowModal] = useState(false);
    const [editPkg, setEditPkg]     = useState<Pkg | null>(null);
    const [delPkg, setDelPkg]       = useState<Pkg | null>(null);

    const form = useForm({
        name: '', description: '', price_monthly: '', price_yearly: '',
        max_students: '0', max_staff: '0', storage_gb: '5',
        is_active: true, modules: [] as string[],
    });

    function openCreate() {
        form.reset(); form.clearErrors(); setEditPkg(null); setShowModal(true);
    }
    function openEdit(p: Pkg) {
        form.setData({
            name: p.name, description: p.description ?? '',
            price_monthly: p.price_monthly, price_yearly: p.price_yearly,
            max_students: String(p.max_students), max_staff: String(p.max_staff),
            storage_gb: String(p.storage_gb), is_active: p.is_active,
            modules: p.modules.map(m => m.module_slug),
        });
        form.clearErrors(); setEditPkg(p); setShowModal(true);
    }
    function toggleModule(slug: string) {
        const mods = form.data.modules.includes(slug)
            ? form.data.modules.filter(m => m !== slug)
            : [...form.data.modules, slug];
        form.setData('modules', mods);
    }
    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (editPkg) {
            form.put(`/super-admin/packages/${editPkg.id}`, { onSuccess: () => setShowModal(false) });
        } else {
            form.post('/super-admin/packages', { onSuccess: () => setShowModal(false) });
        }
    }
    function confirmDelete() {
        if (!delPkg) return;
        router.delete(`/super-admin/packages/${delPkg.id}`, { onSuccess: () => setDelPkg(null) });
    }

    return (
        <AppLayout title="Packages">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Subscription Packages</h1>
                        <p className="text-sm text-slate-500 mt-0.5">Define plans, pricing, and module access</p>
                    </div>
                    <Button onClick={openCreate} className="gap-2"><Plus className="w-4 h-4" /> New Package</Button>
                </div>

                <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    {packages.length === 0 && (
                        <Card className="col-span-3"><CardContent className="py-12 text-center text-slate-400">No packages yet. Create your first package.</CardContent></Card>
                    )}
                    {packages.map(p => (
                        <Card key={p.id} className={!p.is_active ? 'opacity-60' : ''}>
                            <CardHeader className="pb-3">
                                <div className="flex items-start justify-between gap-2">
                                    <div>
                                        <CardTitle className="text-lg">{p.name}</CardTitle>
                                        <p className="text-xs text-slate-400 font-mono mt-0.5">{p.slug}</p>
                                    </div>
                                    <Badge variant={p.is_active ? 'default' : 'secondary'}>{p.is_active ? 'Active' : 'Inactive'}</Badge>
                                </div>
                                {p.description && <p className="text-sm text-slate-500 mt-1">{p.description}</p>}
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="grid grid-cols-2 gap-2 text-sm">
                                    <div className="bg-slate-50 dark:bg-slate-800 rounded p-2">
                                        <p className="text-xs text-slate-400">Monthly</p>
                                        <p className="font-semibold">${p.price_monthly}</p>
                                    </div>
                                    <div className="bg-slate-50 dark:bg-slate-800 rounded p-2">
                                        <p className="text-xs text-slate-400">Yearly</p>
                                        <p className="font-semibold">${p.price_yearly}</p>
                                    </div>
                                </div>
                                <div className="text-xs text-slate-500 flex gap-3">
                                    <span>Students: {p.max_students || '∞'}</span>
                                    <span>Staff: {p.max_staff || '∞'}</span>
                                    <span>Storage: {p.storage_gb}GB</span>
                                </div>
                                {p.modules.length > 0 && (
                                    <div className="flex flex-wrap gap-1">
                                        {p.modules.map(m => (
                                            <span key={m.module_slug} className="text-xs px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-300">
                                                {moduleLabel(m.module_slug)}
                                            </span>
                                        ))}
                                    </div>
                                )}
                                <p className="text-xs text-slate-400">{p.subscriptions_count} school{p.subscriptions_count !== 1 ? 's' : ''} subscribed</p>
                                <div className="flex gap-2 pt-1">
                                    <Button size="sm" variant="outline" className="flex-1 gap-1" onClick={() => openEdit(p)}>
                                        <Edit className="w-3.5 h-3.5" /> Edit
                                    </Button>
                                    <Button size="sm" variant="outline" className="gap-1 text-red-600 hover:text-red-700" onClick={() => setDelPkg(p)}>
                                        <Trash2 className="w-3.5 h-3.5" />
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>

            {/* Create/Edit Modal */}
            <Dialog open={showModal} onOpenChange={setShowModal}>
                <DialogContent className="max-w-xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader><DialogTitle>{editPkg ? 'Edit Package' : 'New Package'}</DialogTitle></DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label>Package Name *</Label>
                            <Input value={form.data.name} onChange={e => form.setData('name', e.target.value)} placeholder="e.g. Gold" />
                            {form.errors.name && <p className="text-xs text-red-500 mt-1">{form.errors.name}</p>}
                        </div>
                        <div>
                            <Label>Description</Label>
                            <Input value={form.data.description} onChange={e => form.setData('description', e.target.value)} placeholder="Brief description" />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Monthly Price ($) *</Label>
                                <Input type="number" step="0.01" value={form.data.price_monthly} onChange={e => form.setData('price_monthly', e.target.value)} />
                                {form.errors.price_monthly && <p className="text-xs text-red-500 mt-1">{form.errors.price_monthly}</p>}
                            </div>
                            <div>
                                <Label>Yearly Price ($) *</Label>
                                <Input type="number" step="0.01" value={form.data.price_yearly} onChange={e => form.setData('price_yearly', e.target.value)} />
                            </div>
                        </div>
                        <div className="grid grid-cols-3 gap-4">
                            <div>
                                <Label>Max Students</Label>
                                <Input type="number" value={form.data.max_students} onChange={e => form.setData('max_students', e.target.value)} placeholder="0 = unlimited" />
                            </div>
                            <div>
                                <Label>Max Staff</Label>
                                <Input type="number" value={form.data.max_staff} onChange={e => form.setData('max_staff', e.target.value)} placeholder="0 = unlimited" />
                            </div>
                            <div>
                                <Label>Storage (GB)</Label>
                                <Input type="number" value={form.data.storage_gb} onChange={e => form.setData('storage_gb', e.target.value)} />
                            </div>
                        </div>
                        <div>
                            <Label>Included Modules</Label>
                            <div className="grid grid-cols-2 gap-2 mt-1">
                                {availableModules.map(slug => {
                                    const checked = form.data.modules.includes(slug);
                                    return (
                                        <button key={slug} type="button" onClick={() => toggleModule(slug)}
                                            className={`flex items-center gap-2 px-3 py-2 rounded border text-sm transition-colors ${checked ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400'}`}>
                                            {checked ? <CheckSquare className="w-4 h-4 shrink-0" /> : <Square className="w-4 h-4 shrink-0" />}
                                            {moduleLabel(slug)}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <input type="checkbox" id="is_active" checked={form.data.is_active}
                                onChange={e => form.setData('is_active', e.target.checked)} className="rounded" />
                            <label htmlFor="is_active" className="text-sm">Active (visible for assignment)</label>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setShowModal(false)}>Cancel</Button>
                            <Button type="submit" disabled={form.processing}>{editPkg ? 'Save Changes' : 'Create Package'}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Confirm */}
            <Dialog open={!!delPkg} onOpenChange={open => !open && setDelPkg(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader><DialogTitle>Delete Package</DialogTitle></DialogHeader>
                    <p className="text-sm text-slate-600 dark:text-slate-400">
                        Delete <strong>{delPkg?.name}</strong>? This cannot be undone and will fail if schools are subscribed.
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDelPkg(null)}>Cancel</Button>
                        <Button className="bg-red-600 hover:bg-red-700 text-white" onClick={confirmDelete}>Delete</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
