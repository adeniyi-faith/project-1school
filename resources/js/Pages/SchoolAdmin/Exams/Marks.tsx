import { useState, useEffect } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Save } from 'lucide-react';
import type { Subject, Section, PageProps } from '@/Types';

interface Student { id: number; first_name: string; last_name: string | null; roll_no: string | null; section?: Section; }
interface ExistingMark { marks_obtained: string | null; grade: string | null; is_absent: boolean; remarks: string | null; }
interface Exam { id: number; name: string; type: string; class_id: number; status: string; school_class?: { name: string }; }

interface Props {
    exam: Exam;
    subjects: Subject[];
    students: Student[];
    existingMarks: Record<number, Record<number, ExistingMark>>;
    sections: Section[];
    filters: { section_id?: string };
}

type MarksBuffer = Record<number, Record<number, { marks_obtained: string; is_absent: boolean; remarks: string }>>;

export default function MarksEntry({ exam, subjects, students, existingMarks, sections, filters }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [buffer, setBuffer] = useState<MarksBuffer>({});
    const [saving, setSaving] = useState(false);

    // Init buffer from existing marks
    useEffect(() => {
        const init: MarksBuffer = {};
        students.forEach(s => {
            init[s.id] = {};
            subjects.forEach(sub => {
                const existing = existingMarks[s.id]?.[sub.id];
                init[s.id][sub.id] = {
                    marks_obtained: existing?.marks_obtained ?? '',
                    is_absent:      existing?.is_absent ?? false,
                    remarks:        existing?.remarks ?? '',
                };
            });
        });
        setBuffer(init);
    }, [students.length, subjects.length]);

    function setMark(studentId: number, subjectId: number, field: string, value: string | boolean) {
        setBuffer(prev => ({
            ...prev,
            [studentId]: { ...prev[studentId], [subjectId]: { ...prev[studentId]?.[subjectId], [field]: value } },
        }));
    }

    function applyFilter(key: string, value: string) {
        router.get(`/school/exams/${exam.id}/marks`, { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    function handleSave() {
        setSaving(true);
        const records: object[] = [];
        students.forEach(s => {
            subjects.forEach(sub => {
                const rec = buffer[s.id]?.[sub.id];
                if (rec) {
                    records.push({ student_id: s.id, subject_id: sub.id, marks_obtained: rec.marks_obtained, is_absent: rec.is_absent, remarks: rec.remarks });
                }
            });
        });
        router.post(`/school/exams/${exam.id}/marks`, { records }, {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        });
    }

    return (
        <AppLayout title={`Marks — ${exam.name}`}>
            <div className="space-y-6">
                <div className="flex items-center justify-between flex-wrap gap-3">
                    <div className="flex items-center gap-3">
                        <Link href="/school/exams" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Exams
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <div>
                            <h1 className="text-xl font-bold text-slate-900 dark:text-white">{exam.name}</h1>
                            <p className="text-sm text-slate-500">{exam.school_class?.name} · Marks Entry</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        {sections.length > 0 && (
                            <Select value={filters.section_id ?? ''} onValueChange={v => applyFilter('section_id', v)}>
                                <SelectTrigger className="w-36"><SelectValue placeholder="All Sections" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All Sections</SelectItem>
                                    {sections.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        )}
                        <Link href={`/school/exams/${exam.id}/results`}>
                            <Button variant="outline">View Results</Button>
                        </Link>
                        <Button onClick={handleSave} disabled={saving || students.length === 0} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                            <Save className="w-4 h-4" /> {saving ? 'Saving...' : 'Save Marks'}
                        </Button>
                    </div>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                )}

                {students.length === 0 ? (
                    <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 flex items-center justify-center py-20">
                        <p className="text-slate-400">No active students found in this class.</p>
                    </div>
                ) : (
                    <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-slate-50 dark:bg-slate-900">
                                    <TableHead className="w-8">#</TableHead>
                                    <TableHead className="min-w-[140px]">Student</TableHead>
                                    {subjects.map(sub => (
                                        <TableHead key={sub.id} className="text-center min-w-[100px]">
                                            <div>{sub.name}</div>
                                            <div className="text-[10px] text-slate-400 font-normal">/ {sub.full_marks}</div>
                                        </TableHead>
                                    ))}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {students.map((student, idx) => (
                                    <TableRow key={student.id}>
                                        <TableCell className="text-slate-400 text-xs">{idx + 1}</TableCell>
                                        <TableCell>
                                            <p className="font-medium text-slate-900 dark:text-white text-sm">{student.first_name} {student.last_name}</p>
                                            <p className="text-xs text-slate-400">{student.roll_no ? `Roll: ${student.roll_no}` : ''} {student.section?.name ?? ''}</p>
                                        </TableCell>
                                        {subjects.map(sub => {
                                            const rec = buffer[student.id]?.[sub.id];
                                            const isAbsent = rec?.is_absent ?? false;
                                            return (
                                                <TableCell key={sub.id} className="text-center p-1.5">
                                                    <div className="flex flex-col items-center gap-1">
                                                        <Input
                                                            type="number"
                                                            min="0"
                                                            max={sub.full_marks}
                                                            className={`w-20 h-8 text-center text-sm ${isAbsent ? 'bg-slate-100 dark:bg-slate-800 text-slate-400' : ''}`}
                                                            value={isAbsent ? '' : (rec?.marks_obtained ?? '')}
                                                            disabled={isAbsent}
                                                            onChange={e => setMark(student.id, sub.id, 'marks_obtained', e.target.value)}
                                                            placeholder="—"
                                                        />
                                                        <label className="flex items-center gap-1 text-[10px] text-slate-400 cursor-pointer">
                                                            <input
                                                                type="checkbox"
                                                                checked={isAbsent}
                                                                onChange={e => setMark(student.id, sub.id, 'is_absent', e.target.checked)}
                                                                className="w-3 h-3"
                                                            />
                                                            Absent
                                                        </label>
                                                    </div>
                                                </TableCell>
                                            );
                                        })}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                {students.length > 0 && (
                    <div className="flex justify-end">
                        <Button onClick={handleSave} disabled={saving} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                            <Save className="w-4 h-4" /> {saving ? 'Saving...' : `Save Marks (${students.length} students × ${subjects.length} subjects)`}
                        </Button>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
