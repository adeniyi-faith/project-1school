import { useState, useEffect } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { EmptyState, PageHeader, Panel, PersonAvatar } from '@/components/app/kit';
import { ArrowLeft, Save, Users } from 'lucide-react';
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

    const filled = students.reduce((n, st) => n + subjects.filter(sub => {
        const r = buffer[st.id]?.[sub.id];
        return r && (r.is_absent || r.marks_obtained !== '');
    }).length, 0);
    const total = students.length * subjects.length;

    return (
        <AppLayout breadcrumbs={[{ label: 'Exams', href: '/school/exams' }, { label: exam.name }, { label: 'Marks' }]}>
            <div className="space-y-6 pb-24 md:pb-0">
                <PageHeader
                    title={exam.name}
                    description={`${exam.school_class?.name ?? ''} · Enter marks for each student`}
                    actions={
                        <>
                            <Link href="/school/exams" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 shadow-xs hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                                <ArrowLeft className="size-4" /> Exams
                            </Link>
                            <Link href={`/school/exams/${exam.id}/results`} className="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 shadow-xs hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                                View results
                            </Link>
                            <Button onClick={handleSave} disabled={saving || students.length === 0} className="gap-2">
                                <Save className="size-4" /> {saving ? 'Saving…' : 'Save marks'}
                            </Button>
                        </>
                    }
                />

                {flash?.success && (
                    <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">{flash.success}</div>
                )}

                {sections.length > 0 && (
                    <div className="space-y-1.5">
                        <label className="text-xs font-medium text-slate-500">Section</label>
                        <Select value={filters.section_id ?? ''} onValueChange={v => applyFilter('section_id', v)}>
                            <SelectTrigger className="h-10 w-48"><SelectValue placeholder="All sections" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All sections</SelectItem>
                                {sections.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}
                            </SelectContent>
                        </Select>
                    </div>
                )}

                {students.length === 0 ? (
                    <Panel><EmptyState icon={Users} title="No students yet" text="No active students were found in this class." /></Panel>
                ) : (
                    <Panel flush title="Mark sheet" description={`${filled} of ${total} entries filled`}>
                        <div className="overflow-x-auto scroll-quiet">
                            <table className="w-full min-w-max border-collapse text-sm">
                                <thead>
                                    <tr className="bg-slate-50/70 dark:bg-white/[0.03]">
                                        <th className="sticky left-0 z-10 min-w-[180px] border-y border-slate-200 bg-slate-50 px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500 dark:border-white/[0.08] dark:bg-slate-900">Student</th>
                                        {subjects.map(sub => (
                                            <th key={sub.id} className="min-w-[110px] border-y border-slate-200 px-3 py-3 text-center text-xs font-medium uppercase tracking-wide text-slate-500 dark:border-white/[0.08]">
                                                {sub.name}
                                                <span className="block text-[11px] font-normal normal-case tracking-normal text-slate-400">out of {sub.full_marks}</span>
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {students.map(student => (
                                        <tr key={student.id} className="border-b border-slate-100 last:border-0 dark:border-white/[0.05]">
                                            <td className="sticky left-0 z-10 bg-white px-4 py-2.5 dark:bg-slate-950">
                                                <div className="flex items-center gap-2.5">
                                                    <PersonAvatar name={`${student.first_name} ${student.last_name ?? ''}`} className="size-8" />
                                                    <div className="min-w-0">
                                                        <p className="truncate font-medium text-slate-900 dark:text-white">{student.first_name} {student.last_name}</p>
                                                        <p className="text-xs text-slate-400">{[student.roll_no && `Roll ${student.roll_no}`, student.section?.name].filter(Boolean).join(' · ')}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            {subjects.map(sub => {
                                                const rec = buffer[student.id]?.[sub.id];
                                                const isAbsent = rec?.is_absent ?? false;
                                                return (
                                                    <td key={sub.id} className="px-3 py-2 text-center">
                                                        <Input
                                                            type="number"
                                                            inputMode="decimal"
                                                            min="0"
                                                            max={sub.full_marks}
                                                            aria-label={`${sub.name} marks for ${student.first_name}`}
                                                            className={`mx-auto h-9 w-20 text-center tabular-nums ${isAbsent ? 'bg-slate-100 text-slate-400 dark:bg-white/[0.04]' : ''}`}
                                                            value={isAbsent ? '' : (rec?.marks_obtained ?? '')}
                                                            disabled={isAbsent}
                                                            onChange={e => setMark(student.id, sub.id, 'marks_obtained', e.target.value)}
                                                            placeholder="—"
                                                        />
                                                        <label className="mt-1 flex cursor-pointer items-center justify-center gap-1 text-[11px] text-slate-400">
                                                            <input type="checkbox" checked={isAbsent} onChange={e => setMark(student.id, sub.id, 'is_absent', e.target.checked)} className="size-3 accent-indigo-600" />
                                                            Absent
                                                        </label>
                                                    </td>
                                                );
                                            })}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Panel>
                )}

                {students.length > 0 && (
                    <div className="fixed inset-x-0 bottom-[calc(4rem+env(safe-area-inset-bottom))] z-20 border-t border-slate-200 bg-white/95 p-3 backdrop-blur md:static md:border-0 md:bg-transparent md:p-0 dark:border-white/10 dark:bg-slate-950/95">
                        <Button onClick={handleSave} disabled={saving} className="h-11 w-full gap-2 md:ml-auto md:flex md:h-9 md:w-auto">
                            <Save className="size-4" /> {saving ? 'Saving…' : `Save marks (${students.length} students × ${subjects.length} subjects)`}
                        </Button>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
