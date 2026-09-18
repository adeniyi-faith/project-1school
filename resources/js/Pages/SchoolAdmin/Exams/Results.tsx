import { router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Trophy, TrendingUp } from 'lucide-react';
import { Link } from '@inertiajs/react';
import type { Subject, Section, PageProps } from '@/Types';

interface ResultRow {
    student: { id: number; first_name: string; last_name: string | null; roll_no: string | null; section?: Section };
    marks: { subject_id: number; marks_obtained: string | null; grade: string | null; gpa: number | null; is_absent: boolean }[];
    total: number; obtained: number; percentage: number; avg_gpa: number | null; failed: boolean; rank: number;
}
interface GradeScale { grade: string; gpa: string; min_marks: string; max_marks: string; remarks: string | null; }
interface Exam { id: number; name: string; type: string; class_id: number; status: string; school_class?: { name: string }; }

interface Props {
    exam: Exam; subjects: Subject[]; results: ResultRow[];
    sections: Section[]; gradeScale: GradeScale[]; filters: { section_id?: string };
}

const GRADE_COLOR: Record<string, string> = {
    'A+': 'bg-emerald-100 text-emerald-700', 'A': 'bg-green-100 text-green-700',
    'A-': 'bg-teal-100 text-teal-700', 'B': 'bg-blue-100 text-blue-700',
    'C': 'bg-amber-100 text-amber-700', 'D': 'bg-orange-100 text-orange-700',
    'F': 'bg-red-100 text-red-700',
};

export default function ExamResults({ exam, subjects, results, sections, gradeScale, filters }: Props) {
    function applyFilter(key: string, value: string) {
        router.get(`/school/exams/${exam.id}/results`, { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    const passCount = results.filter(r => !r.failed).length;
    const failCount = results.filter(r => r.failed).length;
    const avgPct = results.length > 0 ? (results.reduce((s, r) => s + r.percentage, 0) / results.length).toFixed(1) : '—';
    const topStudent = results[0];

    return (
        <AppLayout title={`Results — ${exam.name}`}>
            <div className="space-y-6">
                <div className="flex items-center justify-between flex-wrap gap-3">
                    <div className="flex items-center gap-3">
                        <Link href="/school/exams" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Exams
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <div>
                            <h1 className="text-xl font-bold text-slate-900 dark:text-white">{exam.name} — Results</h1>
                            <p className="text-sm text-slate-500">{exam.school_class?.name} · Merit List</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        {sections.length > 0 && (
                            <Select value={filters.section_id ?? ''} onValueChange={v => applyFilter('section_id', v)}>
                                <SelectTrigger className="w-36"><SelectValue placeholder="All Sections" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All Sections</SelectItem>
                                    {sections.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        )}
                        <Link href={`/school/exams/${exam.id}/marks`}>
                            <Button variant="outline">Edit Marks</Button>
                        </Link>
                    </div>
                </div>

                {/* Summary */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    {[
                        { label: 'Total Students', value: results.length, color: 'text-indigo-600' },
                        { label: 'Passed', value: passCount, color: 'text-green-600' },
                        { label: 'Failed', value: failCount, color: 'text-red-600' },
                        { label: 'Avg %', value: avgPct + '%', color: 'text-amber-600' },
                    ].map(({ label, value, color }) => (
                        <Card key={label} className="border-slate-200 dark:border-slate-800">
                            <CardContent className="p-4 text-center">
                                <p className={`text-2xl font-bold ${color}`}>{value}</p>
                                <p className="text-xs text-slate-500 mt-0.5">{label}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Top 3 */}
                {results.length > 0 && (
                    <div className="flex gap-3 flex-wrap">
                        {results.slice(0, 3).map((r, i) => (
                            <div key={r.student.id} className={`flex items-center gap-3 px-4 py-3 rounded-xl border ${i === 0 ? 'bg-amber-50 border-amber-200 dark:bg-amber-950/20 dark:border-amber-800' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950'}`}>
                                <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold ${i === 0 ? 'bg-amber-400 text-white' : i === 1 ? 'bg-slate-300 text-slate-700' : 'bg-orange-300 text-white'}`}>
                                    {i + 1}
                                </div>
                                <div>
                                    <p className="font-semibold text-sm text-slate-900 dark:text-white">{r.student.first_name} {r.student.last_name}</p>
                                    <p className="text-xs text-slate-500">{r.obtained}/{r.total} · {r.percentage}%</p>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Merit Table */}
                {results.length === 0 ? (
                    <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 flex items-center justify-center py-20">
                        <div className="text-center">
                            <Trophy className="w-12 h-12 mx-auto text-slate-300 mb-3" />
                            <p className="text-slate-500">No marks entered yet. <Link href={`/school/exams/${exam.id}/marks`} className="text-indigo-600 underline">Enter marks</Link> first.</p>
                        </div>
                    </div>
                ) : (
                    <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-slate-50 dark:bg-slate-900">
                                    <TableHead className="w-12">Rank</TableHead>
                                    <TableHead>Student</TableHead>
                                    {subjects.map(s => (
                                        <TableHead key={s.id} className="text-center text-xs">{s.code ?? s.name.slice(0,4)}</TableHead>
                                    ))}
                                    <TableHead className="text-center">Total</TableHead>
                                    <TableHead className="text-center">%</TableHead>
                                    <TableHead className="text-center">GPA</TableHead>
                                    <TableHead className="text-center">Result</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {results.map((row) => (
                                    <TableRow key={row.student.id} className={row.failed ? 'bg-red-50/40 dark:bg-red-950/10' : ''}>
                                        <TableCell className="text-center font-bold text-slate-700 dark:text-slate-300">
                                            {row.rank <= 3 ? ['🥇','🥈','🥉'][row.rank - 1] : row.rank}
                                        </TableCell>
                                        <TableCell>
                                            <p className="font-medium text-sm text-slate-900 dark:text-white">{row.student.first_name} {row.student.last_name}</p>
                                            <p className="text-xs text-slate-400">{row.student.roll_no ? `Roll: ${row.student.roll_no}` : ''}</p>
                                        </TableCell>
                                        {subjects.map(sub => {
                                            const m = row.marks.find(x => x.subject_id === sub.id);
                                            return (
                                                <TableCell key={sub.id} className="text-center text-sm">
                                                    {m?.is_absent ? (
                                                        <Badge className="bg-red-100 text-red-600 border-0 text-xs">Abs</Badge>
                                                    ) : m?.marks_obtained != null ? (
                                                        <div>
                                                            <span className="font-medium">{m.marks_obtained}</span>
                                                            {m.grade && (
                                                                <Badge className={`ml-1 border-0 text-[10px] ${GRADE_COLOR[m.grade] ?? ''}`}>{m.grade}</Badge>
                                                            )}
                                                        </div>
                                                    ) : <span className="text-slate-300">—</span>}
                                                </TableCell>
                                            );
                                        })}
                                        <TableCell className="text-center font-semibold text-slate-900 dark:text-white">{row.obtained}/{row.total}</TableCell>
                                        <TableCell className="text-center font-medium text-slate-700 dark:text-slate-300">{row.percentage}%</TableCell>
                                        <TableCell className="text-center">
                                            {row.avg_gpa != null ? <span className="font-semibold text-indigo-600 dark:text-indigo-400">{row.avg_gpa}</span> : '—'}
                                        </TableCell>
                                        <TableCell className="text-center">
                                            <Badge className={`border-0 ${row.failed ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'}`}>
                                                {row.failed ? 'Fail' : 'Pass'}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                {/* Grade scale reference */}
                {gradeScale.length > 0 && (
                    <Card className="border-slate-200 dark:border-slate-800">
                        <CardHeader className="pb-2"><CardTitle className="text-sm">Grade Scale Reference</CardTitle></CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap gap-2">
                                {gradeScale.map(scale => (
                                    <div key={scale.grade} className={`px-3 py-1.5 rounded-lg text-xs font-medium border ${GRADE_COLOR[scale.grade] ?? 'bg-slate-100 text-slate-600'}`}>
                                        <span className="font-bold">{scale.grade}</span> (GPA {scale.gpa}) · {scale.min_marks}–{scale.max_marks}%
                                        {scale.remarks && <span className="opacity-70"> · {scale.remarks}</span>}
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
