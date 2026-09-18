import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { BarChart3 } from 'lucide-react';

interface MarkRow { exam: string | null; type: string | null; subject: string | null; marks: number | null; total: number | null; grade: string | null; absent: boolean; }
interface Child { id: number; full_name: string; class: string | null; marks: MarkRow[]; }
interface Props { linked: boolean; guardian: { name: string } | null; children: Child[]; }

function gradeColor(g: string | null) {
    if (!g) return 'text-slate-400';
    const f = g[0].toUpperCase();
    return f === 'A' ? 'text-green-600' : f === 'B' ? 'text-blue-600' : f === 'C' ? 'text-amber-600' : 'text-red-600';
}

export default function ParentResults({ linked, children }: Props) {
    if (!linked) {
        return (
            <AppLayout title="Children Results">
                <div className="flex flex-col items-center justify-center py-24 text-center">
                    <BarChart3 className="w-14 h-14 text-slate-300 mb-4" />
                    <h2 className="text-lg font-semibold text-slate-600 dark:text-slate-400">Account not linked</h2>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout title="Children Results">
            <div className="space-y-8">
                <h1 className="text-xl font-bold text-slate-900 dark:text-white">Children Results</h1>
                {children.map(child => (
                    <div key={child.id}>
                        <p className="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">{child.full_name} — {child.class}</p>
                        <Card>
                            <CardContent className="p-0">
                                {child.marks.length === 0 ? (
                                    <p className="py-8 text-center text-slate-400 text-sm">No results yet.</p>
                                ) : (
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="text-xs text-slate-500 border-b dark:border-slate-700 text-left">
                                                <th className="px-4 py-2 font-medium">Exam</th>
                                                <th className="px-4 py-2 font-medium">Subject</th>
                                                <th className="px-4 py-2 font-medium text-right">Marks</th>
                                                <th className="px-4 py-2 font-medium text-right">Grade</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                            {child.marks.map((m, i) => (
                                                <tr key={i}>
                                                    <td className="px-4 py-2 text-slate-600 dark:text-slate-400 text-xs">{m.exam ?? '—'}</td>
                                                    <td className="px-4 py-2 text-slate-700 dark:text-slate-300">{m.subject ?? '—'}</td>
                                                    <td className="px-4 py-2 text-right text-slate-600">
                                                        {m.absent ? <span className="text-red-500 text-xs">Absent</span>
                                                            : <>{m.marks ?? '—'}{m.total ? <span className="text-slate-400">/{m.total}</span> : ''}</>}
                                                    </td>
                                                    <td className={cn('px-4 py-2 text-right font-bold', gradeColor(m.grade))}>{m.absent ? '—' : (m.grade ?? '—')}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                ))}
            </div>
        </AppLayout>
    );
}
