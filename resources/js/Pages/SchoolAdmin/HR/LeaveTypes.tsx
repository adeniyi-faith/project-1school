import { useState } from 'react';
import { useForm, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Plus, MoreHorizontal, Pencil, Trash2, ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';
import type { PageProps } from '@/Types';

interface LeaveType {
    id: number; name: string; max_days_per_year: number; is_paid: boolean;
    description: string | null; is_active: boolean; leave_requests_count: number;
}

const emptyForm = { name: '', max_days_per_year: '15', is_paid: true, description: '', is_active: true };

export default function LeaveTypes({ types }: { types: LeaveType[] }) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<LeaveType | null>(null);
    const { data, setData, post, put, processing, errors, reset } = useForm(emptyForm as any);

    function openCreate() { reset(); setEditing(null); setOpen(true); }
    function openEdit(t: LeaveType) {
        setData({ name: t.name, max_days_per_year: String(t.max_days_per_year), is_paid: t.is_paid, description: t.description ?? '', is_active: t.is_active });
        setEditing(t); setOpen(true);
    }
    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (editing) {
            put(`/school/hr/leave-types/${editing.id}`, { onSuccess: () => { setOpen(false); reset(); } });
        } else {
            post('/school/hr/leave-types', { onSuccess: () => { setOpen(false); reset(); } });
        }
    }
    function handleDelete(t: LeaveType) {
        if (!confirm(`Delete leave type "${t.name}"?`)) return;
        router.delete(`/school/hr/leave-types/${t.id}`);
    }

    return (
        <AppLayout title="Leave Types">
            <div className="max-w-3xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link href="/school/hr/leaves" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Leave Requests
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Leave Types</h1>
                    </div>
                    <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                        <Plus className="w-4 h-4" /> Add Type
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
                                <TableHead className="text-center">Max Days/Year</TableHead>
                                <TableHead className="text-center">Paid Leave</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead className="text-center">Used</TableHead>
                                <TableHead className="text-center">Status</TableHead>
                                <TableHead className="w-12"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {types.length === 0 ? (
                                <TableRow><TableCell colSpan={7} className="text-center py-16 text-slate-400">No leave types configured.</TableCell></TableRow>
                            ) : types.map(t => (
                                <TableRow key={t.id}>
                                    <TableCell className="font-medium text-slate-900 dark:text-white">{t.name}</TableCell>
                                    <TableCell className="text-center font-semibold text-indigo-600">{t.max_days_per_year}</TableCell>
                                    <TableCell className="text-center">
                                        <Badge className={`border-0 text-xs ${t.is_paid ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'}`}>
                                            {t.is_paid ? 'Paid' : 'Unpaid'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-slate-500 text-sm">{t.description ?? '—'}</TableCell>
                                    <TableCell className="text-center text-sm text-slate-600">{t.leave_requests_count}</TableCell>
                                    <TableCell className="text-center">
                                        <Badge className={`border-0 text-xs ${t.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'}`}>
                                            {t.is_active ? 'Active' : 'Inactive'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="icon" className="w-8 h-8"><MoreHorizontal className="w-4 h-4" /></Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem onClick={() => openEdit(t)} className="flex items-center gap-2 text-sm"><Pencil className="w-4 h-4" /> Edit</DropdownMenuItem>
                                                <DropdownMenuItem onClick={() => handleDelete(t)} className="flex items-center gap-2 text-sm text-red-600 focus:text-red-600"><Trash2 className="w-4 h-4" /> Delete</DropdownMenuItem>
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
                    <DialogHeader><DialogTitle>{editing ? 'Edit Leave Type' : 'Add Leave Type'}</DialogTitle></DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4 mt-2">
                        <div className="space-y-1.5">
                            <Label>Name <span className="text-red-500">*</span></Label>
                            <Input value={data.name} onChange={e => setData('name', e.target.value)} placeholder="e.g. Annual Leave" />
                            {errors.name && <p className="text-xs text-red-500">{errors.name}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label>Max Days Per Year <span className="text-red-500">*</span></Label>
                            <Input type="number" min="0" max="365" value={data.max_days_per_year} onChange={e => setData('max_days_per_year', e.target.value)} />
                        </div>
                        <div className="space-y-1.5">
                            <Label>Description</Label>
                            <Input value={data.description} onChange={e => setData('description', e.target.value)} placeholder="Optional" />
                        </div>
                        <div className="flex gap-4">
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" checked={data.is_paid} onChange={e => setData('is_paid', e.target.checked)} className="w-4 h-4" />
                                <span className="text-sm">Paid Leave</span>
                            </label>
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" checked={data.is_active} onChange={e => setData('is_active', e.target.checked)} className="w-4 h-4" />
                                <span className="text-sm">Active</span>
                            </label>
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
