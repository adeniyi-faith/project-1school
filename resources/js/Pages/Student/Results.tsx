import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { BarChart3 } from 'lucide-react';

interface MarkRow { subject: string | null; marks: number | null; total: number | null; grade: string | null; absent: boolean; }
interface ExamResult { id: number; name: string; type: string; date: string | null; marks: MarkRow[]; }
interface Props {
    linked: boolean;
    student: { full_name: string; class: string | null } | null;
    exams: ExamResult[];
}

function gradeColor(g: string | null) {
    if (!g) return 'text-slate-400';
    const first = g[0].toUpperCase();
    if (first === 'A') return 'text-green-600';
    if (first === 'B') return 'text-blue-600';
    if (first === 'C') return 'text-amber-600';
    return 'text-red-600';
}

export default function Results({ linked, student, exams }: Props) {
    if (!linked) {
        return (
            <AppLayout title="My Results">
                <div className="flex flex-col items-center justify-center py-24 text-center">
                    <BarChart3 className="w-14 h-14 text-slate-300 mb-4" />
                    <h2 className="text-lg font-semibold text-slate-600 dark:text-slate-400">Account not linked</h2>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout title="My Results">
            <div className="space-y-6">
                <div>
                    <h1 className="text-xl font-bold text-slate-900 dark:text-white">My Results</h1>
                    <p className="text-sm text-slate-500">{student?.class}</p>
                </div>

                {exams.length === 0 ? (
                    <Card><CardContent className="py-16 text-center text-slate-400">No results available yet.</CardContent></Card>
                ) : (
                    <div className="space-y-4">
                        {exams.map(exam => (
                            <Card key={exam.id}>
                                <CardHeader className="pb-2">
                                    <CardTitle className="flex items-center justify-between text-sm font-semibold">
                                        <span>{exam.name}</span>
                                        <div className="flex items-center gap-2">
                                            <Badge variant="secondary" className="capitalize text-[10px]">{exam.type}</Badge>
                                            {exam.date && <span className="text-xs text-slate-400 font-normal">{exam.date}</span>}
                                        </div>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="text-left text-xs text-slate-500 border-b dark:border-slate-700">
                                                <th className="pb-2 font-medium">Subject</th>
                                                <th className="pb-2 font-medium text-right">Marks</th>
                                                <th className="pb-2 font-medium text-right">Grade</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                            {exam.marks.map((m, i) => (
                                                <tr key={i}>
                                                    <td className="py-2 text-slate-700 dark:text-slate-300">{m.subject ?? '—'}</td>
                                                    <td className="py-2 text-right text-slate-600 dark:text-slate-400">
                                                        {m.absent ? <span className="text-red-500 text-xs">Absent</span>
                                                            : <>{m.marks ?? '—'}{m.total ? <span className="text-slate-400">/{m.total}</span> : ''}</>}
                                                    </td>
                                                    <td className={cn('py-2 text-right font-bold', gradeColor(m.grade))}>{m.absent ? '—' : (m.grade ?? '—')}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
