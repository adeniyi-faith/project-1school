import { useState } from 'react';
import { useForm, router, usePage, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Card, CardContent } from '@/components/ui/card';
import { Plus, MoreHorizontal, Pencil, Trash2, BookOpen, Search, ClipboardList, AlertCircle } from 'lucide-react';
import type { PageProps, PaginatedResponse } from '@/Types';

interface Book {
    id: number; isbn: string | null; title: string; author: string; category: string | null;
    publisher: string | null; publication_year: number | null; location: string | null;
    total_copies: number; available_copies: number; description: string | null; is_active: boolean;
    issues_count: number;
}
interface Stats { total_books: number; available: number; issued_count: number; overdue_count: number; }

interface Props {
    books: PaginatedResponse<Book>;
    categories: string[];
    filters: { search?: string; category?: string; available?: string };
    stats: Stats;
}

const emptyForm = { isbn: '', title: '', author: '', category: '', publisher: '', publication_year: '', location: '', total_copies: '1', description: '' };

export default function LibraryBooks({ books, categories, filters, stats }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Book | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');
    const { data, setData, post, put, processing, errors, reset } = useForm(emptyForm as any);

    function doSearch() {
        router.get('/school/library/books', { ...filters, search: search || undefined }, { preserveScroll: true });
    }
    function applyFilter(key: string, value: string) {
        router.get('/school/library/books', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    function openCreate() { reset(); setEditing(null); setOpen(true); }
    function openEdit(b: Book) {
        setData({ isbn: b.isbn ?? '', title: b.title, author: b.author, category: b.category ?? '',
            publisher: b.publisher ?? '', publication_year: String(b.publication_year ?? ''),
            location: b.location ?? '', total_copies: String(b.total_copies), description: b.description ?? '' });
        setEditing(b); setOpen(true);
    }
    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (editing) {
            put(`/school/library/books/${editing.id}`, { onSuccess: () => { setOpen(false); reset(); } });
        } else {
            post('/school/library/books', { onSuccess: () => { setOpen(false); reset(); } });
        }
    }
    function handleDelete(b: Book) {
        if (!confirm(`Delete "${b.title}"?`)) return;
        router.delete(`/school/library/books/${b.id}`);
    }

    const statCards = [
        { label: 'Total Copies',    value: stats.total_books,   color: 'text-indigo-600',  icon: BookOpen },
        { label: 'Available',       value: stats.available,     color: 'text-green-600',   icon: BookOpen },
        { label: 'Currently Issued',value: stats.issued_count,  color: 'text-amber-600',   icon: ClipboardList },
        { label: 'Overdue',         value: stats.overdue_count, color: 'text-red-600',     icon: AlertCircle },
    ];

    return (
        <AppLayout title="Library — Books">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Library</h1>
                        <p className="text-sm text-slate-500 mt-0.5">{books.meta?.total ?? 0} titles in catalog</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/school/library/overdue">
                            <Button variant="outline" className="inline-flex items-center gap-2"><AlertCircle className="w-4 h-4" /> Overdue</Button>
                        </Link>
                        <Link href="/school/library/issues">
                            <Button variant="outline" className="inline-flex items-center gap-2"><ClipboardList className="w-4 h-4" /> Issue / Return</Button>
                        </Link>
                        <Button onClick={openCreate} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                            <Plus className="w-4 h-4" /> Add Book
                        </Button>
                    </div>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                {/* Stats */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    {statCards.map(({ label, value, color, icon: Icon }) => (
                        <Card key={label} className="border-slate-200 dark:border-slate-800">
                            <CardContent className="p-4 flex items-center gap-3">
                                <div className={`p-2 rounded-lg bg-slate-100 dark:bg-slate-800 ${color}`}><Icon className="w-5 h-5" /></div>
                                <div>
                                    <p className={`text-2xl font-bold ${color}`}>{value}</p>
                                    <p className="text-xs text-slate-500">{label}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Filters */}
                <div className="flex gap-3 flex-wrap">
                    <div className="flex gap-2">
                        <Input
                            placeholder="Search title, author, ISBN…"
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                            onKeyDown={e => e.key === 'Enter' && doSearch()}
                            className="w-60"
                        />
                        <Button type="button" variant="outline" onClick={doSearch} className="px-3"><Search className="w-4 h-4" /></Button>
                    </div>
                    <Select value={filters.category ?? ''} onValueChange={v => applyFilter('category', v)}>
                        <SelectTrigger className="w-40"><SelectValue placeholder="All Categories" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Categories</SelectItem>
                            {categories.map(c => <SelectItem key={c} value={c}>{c}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Select value={filters.available ?? ''} onValueChange={v => applyFilter('available', v)}>
                        <SelectTrigger className="w-36"><SelectValue placeholder="All Books" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Books</SelectItem>
                            <SelectItem value="yes">Available Only</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Table */}
                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50 dark:bg-slate-900">
                                <TableHead>Title</TableHead>
                                <TableHead>Author</TableHead>
                                <TableHead>Category</TableHead>
                                <TableHead>ISBN</TableHead>
                                <TableHead>Location</TableHead>
                                <TableHead className="text-center">Copies</TableHead>
                                <TableHead className="text-center">Available</TableHead>
                                <TableHead className="w-12"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {books.data.length === 0 ? (
                                <TableRow><TableCell colSpan={8} className="text-center py-16 text-slate-400">No books found.</TableCell></TableRow>
                            ) : books.data.map(b => (
                                <TableRow key={b.id}>
                                    <TableCell>
                                        <p className="font-medium text-slate-900 dark:text-white">{b.title}</p>
                                        {b.publisher && <p className="text-xs text-slate-400">{b.publisher} {b.publication_year ? `· ${b.publication_year}` : ''}</p>}
                                    </TableCell>
                                    <TableCell className="text-slate-600 dark:text-slate-400 text-sm">{b.author}</TableCell>
                                    <TableCell>
                                        {b.category && <Badge className="bg-indigo-100 text-indigo-700 border-0 text-xs">{b.category}</Badge>}
                                    </TableCell>
                                    <TableCell className="font-mono text-xs text-slate-500">{b.isbn ?? '—'}</TableCell>
                                    <TableCell className="text-slate-500 text-sm">{b.location ?? '—'}</TableCell>
                                    <TableCell className="text-center font-medium text-slate-700 dark:text-slate-300">{b.total_copies}</TableCell>
                                    <TableCell className="text-center">
                                        <Badge className={`border-0 text-xs font-bold ${b.available_copies > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                            {b.available_copies}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="icon" className="w-8 h-8"><MoreHorizontal className="w-4 h-4" /></Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem onClick={() => openEdit(b)} className="flex items-center gap-2 text-sm"><Pencil className="w-4 h-4" /> Edit</DropdownMenuItem>
                                                <DropdownMenuItem onClick={() => handleDelete(b)} className="flex items-center gap-2 text-sm text-red-600 focus:text-red-600"><Trash2 className="w-4 h-4" /> Delete</DropdownMenuItem>
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
                <DialogContent className="sm:max-w-lg max-h-[90vh] overflow-y-auto">
                    <DialogHeader><DialogTitle>{editing ? 'Edit Book' : 'Add Book'}</DialogTitle></DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4 mt-2">
                        <div className="space-y-1.5">
                            <Label>Title <span className="text-red-500">*</span></Label>
                            <Input value={data.title} onChange={e => setData('title', e.target.value)} placeholder="Book title" />
                            {errors.title && <p className="text-xs text-red-500">{errors.title}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Author <span className="text-red-500">*</span></Label>
                                <Input value={data.author} onChange={e => setData('author', e.target.value)} placeholder="Author name" />
                            </div>
                            <div className="space-y-1.5">
                                <Label>ISBN</Label>
                                <Input value={data.isbn} onChange={e => setData('isbn', e.target.value)} placeholder="978-..." />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Category</Label>
                                <Input value={data.category} onChange={e => setData('category', e.target.value)} placeholder="e.g. Science" />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Publisher</Label>
                                <Input value={data.publisher} onChange={e => setData('publisher', e.target.value)} />
                            </div>
                        </div>
                        <div className="grid grid-cols-3 gap-3">
                            <div className="space-y-1.5">
                                <Label>Year</Label>
                                <Input type="number" min="1900" max="2099" value={data.publication_year} onChange={e => setData('publication_year', e.target.value)} placeholder="2024" />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Location / Shelf</Label>
                                <Input value={data.location} onChange={e => setData('location', e.target.value)} placeholder="A-1-2" />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Copies <span className="text-red-500">*</span></Label>
                                <Input type="number" min="1" value={data.total_copies} onChange={e => setData('total_copies', e.target.value)} />
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Description</Label>
                            <Textarea rows={2} value={data.description} onChange={e => setData('description', e.target.value)} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={processing} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {processing ? 'Saving...' : editing ? 'Update' : 'Add Book'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
