import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import { ArrowLeft, AlertTriangle, DollarSign } from 'lucide-react';

interface OverdueIssue {
    id: number; overdue_days: number; estimated_fine: number;
    member_name: string; member_id_no: string; member_label: string;
    issued_date: string; due_date: string;
    book?: { title: string; author: string; isbn: string | null };
}
interface Summary { count: number; total_fine_est: number; }

export default function OverdueBooks({ overdue, summary }: { overdue: OverdueIssue[]; summary: Summary }) {
    return (
        <AppLayout title="Library — Overdue Books">
            <div className="space-y-6">
                <div className="flex items-center gap-3">
                    <Link href="/school/library/books" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                        <ArrowLeft className="w-4 h-4" /> Library
                    </Link>
                    <span className="text-slate-300 dark:text-slate-700">|</span>
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Overdue Books</h1>
                        <p className="text-sm text-slate-500">{summary.count} books not returned on time</p>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4 max-w-sm">
                    <Card className="border-red-200 dark:border-red-900">
                        <CardContent className="p-4 flex items-center gap-3">
                            <AlertTriangle className="w-5 h-5 text-red-500" />
                            <div>
                                <p className="text-2xl font-bold text-red-600">{summary.count}</p>
                                <p className="text-xs text-slate-500">Overdue Books</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-amber-200 dark:border-amber-900">
                        <CardContent className="p-4 flex items-center gap-3">
                            <DollarSign className="w-5 h-5 text-amber-500" />
                            <div>
                                <p className="text-xl font-bold text-amber-600">৳{summary.total_fine_est?.toLocaleString()}</p>
                                <p className="text-xs text-slate-500">Est. Fines</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {overdue.length === 0 ? (
                    <div className="rounded-xl border border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-950/20 py-20 text-center">
                        <AlertTriangle className="w-12 h-12 mx-auto text-green-300 mb-3" />
                        <p className="text-green-600 font-medium">No overdue books!</p>
                        <p className="text-sm text-green-500 mt-1">All issued books are within their due dates.</p>
                    </div>
                ) : (
                    <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-slate-50 dark:bg-slate-900">
                                    <TableHead>#</TableHead>
                                    <TableHead>Book</TableHead>
                                    <TableHead>Member</TableHead>
                                    <TableHead>Issued</TableHead>
                                    <TableHead>Due Date</TableHead>
                                    <TableHead className="text-center">Overdue Days</TableHead>
                                    <TableHead className="text-right">Est. Fine</TableHead>
                                    <TableHead className="w-24"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {overdue.map((iss, idx) => (
                                    <TableRow key={iss.id} className="bg-red-50/30 dark:bg-red-950/10">
                                        <TableCell className="text-slate-400 text-xs">{idx + 1}</TableCell>
                                        <TableCell>
                                            <p className="font-medium text-sm text-slate-900 dark:text-white">{iss.book?.title}</p>
                                            <p className="text-xs text-slate-400">{iss.book?.author}</p>
                                        </TableCell>
                                        <TableCell>
                                            <p className="text-sm text-slate-700 dark:text-slate-300">{iss.member_name}</p>
                                            <p className="text-xs text-slate-400">{iss.member_label} · {iss.member_id_no}</p>
                                        </TableCell>
                                        <TableCell className="text-sm text-slate-500">{new Date(iss.issued_date).toLocaleDateString()}</TableCell>
                                        <TableCell className="text-sm text-red-600 font-medium">{new Date(iss.due_date).toLocaleDateString()}</TableCell>
                                        <TableCell className="text-center">
                                            <Badge className="bg-red-100 text-red-700 border-0 font-bold">{iss.overdue_days} days</Badge>
                                        </TableCell>
                                        <TableCell className="text-right font-semibold text-amber-600">৳{iss.estimated_fine?.toLocaleString()}</TableCell>
                                        <TableCell>
                                            <Link href="/school/library/issues">
                                                <Button size="sm" variant="outline" className="text-xs h-7">Return</Button>
                                            </Link>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
