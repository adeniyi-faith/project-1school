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
import { Plus, MoreHorizontal, Pencil, Trash2, ArrowLeft, Settings2 } from 'lucide-react';
import { Link } from '@inertiajs/react';
import type { SchoolClass, PageProps, PaginatedResponse } from '@/Types';

interface FeeCategory { id: number; name: string; type: string; }
interface FeeStructure {
    id: number; class_id: number; fee_category_id: number; academic_year: string;
    amount: string; due_date: string | null; frequency: string; description: string | null; is_active: boolean;
    school_class?: { name: string }; fee_category?: FeeCategory;
}

interface Props {
    structures: PaginatedResponse<FeeStructure>;
    classes: SchoolClass[];
    categories: FeeCategory[];
    filters: { class_id?: string; category_id?: string; academic_year?: string };
    currentYear: string;
}

const FREQ_LABELS: Record<string, string> = {
    monthly: 'Monthly', quarterly: 'Quarterly', annual: 'Annual', one_time: 'One-Time',
};
const FREQ_COLORS: Record<string, string> = {
    monthly: 'bg-blue-100 text-blue-700', quarterly: 'bg-purple-100 text-purple-700',
    annual: 'bg-teal-100 text-teal-700', one_time: 'bg-amber-100 text-amber-700',
};

const emptyForm = { class_id: '', fee_category_id: '', academic_year: '2025-2026', amount: '', due_date: '', frequency: 'monthly', description: '', is_active: true };

export default function FeeStructures({ structures, classes, categories, filters, currentYear }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<FeeStructure | null>(null);
    const { data, setData, post, put, processing, errors, reset } = useForm(emptyForm as any);

    function applyFilter(key: string, value: string) {
        router.get('/school/fees/structures', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    function openCreate() { reset(); setEditing(null); setOpen(true); }
    function openEdit(s: FeeStructure) {
        setData({
            class_id: String(s.class_id), fee_category_id: String(s.fee_category_id),
            academic_year: s.academic_year, amount: s.amount,
            due_date: s.due_date ?? '', frequency: s.frequency,
            description: s.description ?? '', is_active: s.is_active,
        });
        setEditing(s); setOpen(true);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (editing) {
            put(`/school/fees/structures/${editing.id}`, { onSuccess: () => { setOpen(false); setEditing(null); reset(); } });
        } else {
            post('/school/fees/structures', { onSuccess: () => { setOpen(false); reset(); } });
        }
    }

    function handleDelete(s: FeeStructure) {
        if (!confirm('Delete this fee structure?')) return;
        router.delete(`/school/fees/structures/${s.id}`);
    }

    return (
        <AppLayout title="Fee Structures">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link href="/school/fees/payments" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Fees
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Fee Structures</h1>
                            <p className="text-sm text-slate-500">{structures.meta?.total ?? 0} structures configured</p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/school/fees/categories">
                            <Button variant="outline" className="inline-flex items-center gap-2"><Settings2 className="w-4 h-4" /> Categories</Button>
                        </Link>
                        <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                            <Plus className="w-4 h-4" /> Add Structure
                        </Button>
                    </div>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                {/* Filters */}
                <div className="flex gap-3 flex-wrap">
                    <Select value={filters.class_id ?? ''} onValueChange={v => applyFilter('class_id', v)}>
                        <SelectTrigger className="w-40"><SelectValue placeholder="All Classes" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Classes</SelectItem>
                            {classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Select value={filters.category_id ?? ''} onValueChange={v => applyFilter('category_id', v)}>
                        <SelectTrigger className="w-44"><SelectValue placeholder="All Categories" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Categories</SelectItem>
                            {categories.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Input
                        className="w-36"
                        placeholder="Year e.g. 2025-2026"
                        defaultValue={filters.academic_year ?? ''}
                        onBlur={e => applyFilter('academic_year', e.target.value)}
                    />
                </div>

                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Category</TableHead>
                                <TableHead>Class</TableHead>
                                <TableHead>Academic Year</TableHead>
                                <TableHead className="text-right">Amount</TableHead>
                                <TableHead>Frequency</TableHead>
                                <TableHead>Due Date</TableHead>
                                <TableHead className="text-center">Status</TableHead>
                                <TableHead className="w-12"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {structures.data.length === 0 ? (
                                <TableRow><TableCell colSpan={8} className="text-center py-16 text-slate-400">No fee structures configured yet.</TableCell></TableRow>
                            ) : structures.data.map(s => (
                                <TableRow key={s.id}>
                                    <TableCell>
                                        <p className="font-medium text-slate-900 dark:text-white">{s.fee_category?.name}</p>
                                        <p className="text-xs text-slate-400 capitalize">{s.fee_category?.type}</p>
                                    </TableCell>
                                    <TableCell className="text-slate-600 dark:text-slate-400">{s.school_class?.name}</TableCell>
                                    <TableCell className="text-slate-600 dark:text-slate-400">{s.academic_year}</TableCell>
                                    <TableCell className="text-right font-semibold text-slate-900 dark:text-white">
                                        ৳{Number(s.amount).toLocaleString()}
                                    </TableCell>
                                    <TableCell>
                                        <Badge className={`border-0 text-xs ${FREQ_COLORS[s.frequency] ?? ''}`}>{FREQ_LABELS[s.frequency] ?? s.frequency}</Badge>
                                    </TableCell>
                                    <TableCell className="text-slate-500 text-sm">
                                        {s.due_date ? new Date(s.due_date).toLocaleDateString() : '—'}
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <Badge className={`border-0 text-xs ${s.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'}`}>
                                            {s.is_active ? 'Active' : 'Inactive'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="icon" className="w-8 h-8"><MoreHorizontal className="w-4 h-4" /></Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem onClick={() => openEdit(s)} className="flex items-center gap-2 text-sm"><Pencil className="w-4 h-4" /> Edit</DropdownMenuItem>
                                                <DropdownMenuItem onClick={() => handleDelete(s)} className="flex items-center gap-2 text-sm text-red-600 focus:text-red-600"><Trash2 className="w-4 h-4" /> Delete</DropdownMenuItem>
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
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader><DialogTitle>{editing ? 'Edit Structure' : 'Add Fee Structure'}</DialogTitle></DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4 mt-2">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Class <span className="text-red-500">*</span></Label>
                                <Select value={data.class_id} onValueChange={v => setData('class_id', v)}>
                                    <SelectTrigger><SelectValue placeholder="Select class" /></SelectTrigger>
                                    <SelectContent>
                                        {classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                                {errors.class_id && <p className="text-xs text-red-500">{errors.class_id}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label>Category <span className="text-red-500">*</span></Label>
                                <Select value={data.fee_category_id} onValueChange={v => setData('fee_category_id', v)}>
                                    <SelectTrigger><SelectValue placeholder="Select category" /></SelectTrigger>
                                    <SelectContent>
                                        {categories.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                                {errors.fee_category_id && <p className="text-xs text-red-500">{errors.fee_category_id}</p>}
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Academic Year <span className="text-red-500">*</span></Label>
                                <Input value={data.academic_year} onChange={e => setData('academic_year', e.target.value)} placeholder="2025-2026" />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Amount (৳) <span className="text-red-500">*</span></Label>
                                <Input type="number" min="0" step="0.01" value={data.amount} onChange={e => setData('amount', e.target.value)} placeholder="0.00" />
                                {errors.amount && <p className="text-xs text-red-500">{errors.amount}</p>}
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Frequency <span className="text-red-500">*</span></Label>
                                <Select value={data.frequency} onValueChange={v => setData('frequency', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="monthly">Monthly</SelectItem>
                                        <SelectItem value="quarterly">Quarterly</SelectItem>
                                        <SelectItem value="annual">Annual</SelectItem>
                                        <SelectItem value="one_time">One-Time</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label>Due Date</Label>
                                <Input type="date" value={data.due_date} onChange={e => setData('due_date', e.target.value)} />
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Description</Label>
                            <Input value={data.description} onChange={e => setData('description', e.target.value)} placeholder="Optional" />
                        </div>
                        <div className="flex items-center gap-2">
                            <input type="checkbox" id="is_active_struct" checked={data.is_active} onChange={e => setData('is_active', e.target.checked)} className="w-4 h-4" />
                            <Label htmlFor="is_active_struct" className="cursor-pointer">Active</Label>
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
