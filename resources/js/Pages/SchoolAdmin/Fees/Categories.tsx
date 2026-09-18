import { useState } from 'react';
import { useForm, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Plus, MoreHorizontal, Pencil, Trash2, ArrowLeft, Tag } from 'lucide-react';
import { Link } from '@inertiajs/react';
import type { PageProps } from '@/Types';

interface FeeCategory {
    id: number; name: string; type: string; description: string | null;
    is_active: boolean; fee_structures_count: number;
}

const TYPE_COLORS: Record<string, string> = {
    tuition:   'bg-indigo-100 text-indigo-700',
    transport: 'bg-blue-100 text-blue-700',
    library:   'bg-amber-100 text-amber-700',
    exam:      'bg-purple-100 text-purple-700',
    hostel:    'bg-teal-100 text-teal-700',
    sports:    'bg-green-100 text-green-700',
    other:     'bg-slate-100 text-slate-600',
};

const FEE_TYPES = ['tuition', 'transport', 'library', 'exam', 'hostel', 'sports', 'other'];

const emptyForm = { name: '', type: 'tuition', description: '', is_active: true };

export default function FeeCategories({ categories }: { categories: FeeCategory[] }) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<FeeCategory | null>(null);
    const { data, setData, post, put, processing, errors, reset } = useForm(emptyForm as any);

    function openCreate() { reset(); setEditing(null); setOpen(true); }
    function openEdit(c: FeeCategory) {
        setData({ name: c.name, type: c.type, description: c.description ?? '', is_active: c.is_active });
        setEditing(c); setOpen(true);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (editing) {
            put(`/school/fees/categories/${editing.id}`, { onSuccess: () => { setOpen(false); setEditing(null); reset(); } });
        } else {
            post('/school/fees/categories', { onSuccess: () => { setOpen(false); reset(); } });
        }
    }

    function handleDelete(c: FeeCategory) {
        if (!confirm(`Delete category "${c.name}"?`)) return;
        router.delete(`/school/fees/categories/${c.id}`);
    }

    return (
        <AppLayout title="Fee Categories">
            <div className="max-w-4xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link href="/school/fees/payments" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Fees
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Fee Categories</h1>
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
                                <TableHead>Type</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead className="text-center">Structures</TableHead>
                                <TableHead className="text-center">Status</TableHead>
                                <TableHead className="w-12"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {categories.length === 0 ? (
                                <TableRow><TableCell colSpan={6} className="text-center py-16 text-slate-400">No categories yet.</TableCell></TableRow>
                            ) : categories.map(c => (
                                <TableRow key={c.id}>
                                    <TableCell className="font-medium text-slate-900 dark:text-white">
                                        <div className="flex items-center gap-2">
                                            <Tag className="w-4 h-4 text-slate-400" />
                                            {c.name}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge className={`border-0 text-xs capitalize ${TYPE_COLORS[c.type] ?? ''}`}>{c.type}</Badge>
                                    </TableCell>
                                    <TableCell className="text-slate-500 text-sm">{c.description ?? '—'}</TableCell>
                                    <TableCell className="text-center">
                                        <span className="text-sm font-medium text-slate-700 dark:text-slate-300">{c.fee_structures_count}</span>
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <Badge className={`border-0 text-xs ${c.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'}`}>
                                            {c.is_active ? 'Active' : 'Inactive'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="icon" className="w-8 h-8"><MoreHorizontal className="w-4 h-4" /></Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem onClick={() => openEdit(c)} className="flex items-center gap-2 text-sm"><Pencil className="w-4 h-4" /> Edit</DropdownMenuItem>
                                                <DropdownMenuItem onClick={() => handleDelete(c)} className="flex items-center gap-2 text-sm text-red-600 focus:text-red-600"><Trash2 className="w-4 h-4" /> Delete</DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader><DialogTitle>{editing ? 'Edit Category' : 'Add Category'}</DialogTitle></DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4 mt-2">
                        <div className="space-y-1.5">
                            <Label>Name <span className="text-red-500">*</span></Label>
                            <Input value={data.name} onChange={e => setData('name', e.target.value)} placeholder="e.g. Tuition Fee" />
                            {errors.name && <p className="text-xs text-red-500">{errors.name}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label>Type <span className="text-red-500">*</span></Label>
                            <Select value={data.type} onValueChange={v => setData('type', v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {FEE_TYPES.map(t => <SelectItem key={t} value={t}><span className="capitalize">{t}</span></SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Description</Label>
                            <Input value={data.description} onChange={e => setData('description', e.target.value)} placeholder="Optional description" />
                        </div>
                        <div className="flex items-center gap-2">
                            <input type="checkbox" id="is_active" checked={data.is_active} onChange={e => setData('is_active', e.target.checked)} className="w-4 h-4" />
                            <Label htmlFor="is_active" className="cursor-pointer">Active</Label>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={processing} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {processing ? 'Saving...' : editing ? 'Update' : 'Add'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
