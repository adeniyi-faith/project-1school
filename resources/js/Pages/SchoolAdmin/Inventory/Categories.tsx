import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Plus, Pencil, Trash2, Tag } from 'lucide-react';
import type { PageProps } from '@/Types';

interface Category { id: number; name: string; description: string | null; items_count: number; }

interface Props { categories: Category[]; }

const empty = { name: '', description: '' };

export default function InventoryCategories({ categories }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen]   = useState(false);
    const [editing, setEditing] = useState<Category | null>(null);
    const [form, setForm]   = useState(empty);
    const [saving, setSaving] = useState(false);

    function openCreate() { setEditing(null); setForm(empty); setOpen(true); }
    function openEdit(c: Category) { setEditing(c); setForm({ name: c.name, description: c.description ?? '' }); setOpen(true); }

    function handleSave() {
        setSaving(true);
        const url  = editing ? `/school/inventory/categories/${editing.id}` : '/school/inventory/categories';
        const method = editing ? 'put' : 'post';
        router[method](url, form, {
            preserveScroll: true,
            onSuccess: () => { setOpen(false); setSaving(false); },
            onError:   () => setSaving(false),
        });
    }

    function handleDelete(id: number) {
        if (!confirm('Delete this category?')) return;
        router.delete(`/school/inventory/categories/${id}`, { preserveScroll: true });
    }

    return (
        <AppLayout title="Inventory Categories">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Inventory Categories</h1>
                        <p className="text-sm text-slate-500 mt-0.5">{categories.length} categories</p>
                    </div>
                    <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                        <Plus className="w-4 h-4" /> Add Category
                    </Button>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Name</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead className="text-center">Items</TableHead>
                                <TableHead className="w-24"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {categories.length === 0 ? (
                                <TableRow><TableCell colSpan={4} className="text-center py-16 text-slate-400">No categories yet.</TableCell></TableRow>
                            ) : categories.map(c => (
                                <TableRow key={c.id}>
                                    <TableCell className="font-medium text-slate-900 dark:text-white flex items-center gap-2">
                                        <Tag className="w-4 h-4 text-indigo-400" /> {c.name}
                                    </TableCell>
                                    <TableCell className="text-slate-500 text-sm">{c.description ?? '—'}</TableCell>
                                    <TableCell className="text-center text-sm text-slate-600">{c.items_count}</TableCell>
                                    <TableCell>
                                        <div className="flex gap-1">
                                            <Button size="sm" variant="ghost" onClick={() => openEdit(c)}><Pencil className="w-3.5 h-3.5" /></Button>
                                            <Button size="sm" variant="ghost" className="text-red-500 hover:text-red-700" onClick={() => handleDelete(c.id)}><Trash2 className="w-3.5 h-3.5" /></Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader><DialogTitle>{editing ? 'Edit' : 'Add'} Category</DialogTitle></DialogHeader>
                    <div className="space-y-4 mt-2">
                        <div className="space-y-1.5">
                            <Label>Name <span className="text-red-500">*</span></Label>
                            <Input value={form.name} onChange={e => setForm(p => ({ ...p, name: e.target.value }))} />
                        </div>
                        <div className="space-y-1.5">
                            <Label>Description</Label>
                            <Input value={form.description} onChange={e => setForm(p => ({ ...p, description: e.target.value }))} />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                        <Button onClick={handleSave} disabled={saving} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                            {saving ? 'Saving...' : 'Save'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
