import { useEffect } from 'react';
import { router, usePage, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import { ClipboardList, CheckCircle2, XCircle, Clock, MinusCircle } from 'lucide-react';
import type { SchoolClass, Section, Student, PageProps } from '@/Types';
import { useAttendanceStore } from '@/Stores/useAttendanceStore';

type AttendanceStatus = 'present' | 'absent' | 'late' | 'half_day';

interface ExistingRecord { status: AttendanceStatus; remarks: string | null; }

interface Props {
    classes: SchoolClass[];
    sections: Section[];
    students: Student[];
    existing: Record<number, ExistingRecord>;
    filters: { date: string; class_id?: string; section_id?: string };
}

const STATUS_OPTIONS: { value: AttendanceStatus; label: string; color: string; icon: React.ElementType }[] = [
    { value: 'present',  label: 'Present',  color: 'bg-green-100 text-green-700 border-green-300', icon: CheckCircle2 },
    { value: 'absent',   label: 'Absent',   color: 'bg-red-100 text-red-700 border-red-300',       icon: XCircle      },
    { value: 'late',     label: 'Late',     color: 'bg-amber-100 text-amber-700 border-amber-300', icon: Clock        },
    { value: 'half_day', label: 'Half Day', color: 'bg-blue-100 text-blue-700 border-blue-300',    icon: MinusCircle  },
];

export default function AttendanceIndex({ classes, sections, students, existing, filters }: Props) {
    const { flash } = usePage<PageProps>().props;
    const { records, currentDate, currentClassId, currentSectionId,
            setDate, setClassId, setSectionId, markStudent, markAll, initRecords } = useAttendanceStore();

    // Sync store with page data
    useEffect(() => {
        setDate(filters.date);
        if (filters.class_id)   setClassId(filters.class_id);
        if (filters.section_id) setSectionId(filters.section_id ?? '');
    }, []);

    useEffect(() => {
        if (students.length > 0) {
            initRecords(existing as Record<number, { status: AttendanceStatus; remarks: string | null }>, students.map(s => s.id));
        }
    }, [students.length]);

    function applyFilter(key: string, value: string) {
        router.get('/school/attendance', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    const filteredSections = filters.class_id
        ? sections.filter(s => s.class_id === Number(filters.class_id))
        : [];

    const presentCount  = Object.values(records).filter(r => r.status === 'present').length;
    const absentCount   = Object.values(records).filter(r => r.status === 'absent').length;
    const lateCount     = Object.values(records).filter(r => r.status === 'late').length;
    const halfDayCount  = Object.values(records).filter(r => r.status === 'half_day').length;

    function handleSubmit() {
        if (students.length === 0) return;

        const recordsPayload = students.map(s => ({
            student_id: s.id,
            status:     records[s.id]?.status  ?? 'present',
            remarks:    records[s.id]?.remarks ?? '',
        }));

        router.post('/school/attendance', {
            date:     filters.date,
            class_id: filters.class_id,
            records:  recordsPayload,
        }, { preserveScroll: true });
    }

    return (
        <AppLayout title="Attendance">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Student Attendance</h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Mark daily class-wise attendance</p>
                    </div>
                    <a href="/school/attendance/staff">
                        <Button variant="outline" className="inline-flex items-center gap-2">
                            <ClipboardList className="w-4 h-4" /> Staff Attendance
                        </Button>
                    </a>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                        {flash.success}
                    </div>
                )}

                {/* Filters */}
                <Card className="border-slate-200 dark:border-slate-800">
                    <CardContent className="p-4">
                        <div className="flex flex-wrap gap-3 items-end">
                            <div className="space-y-1">
                                <label className="text-xs font-medium text-slate-500 uppercase tracking-wide">Date</label>
                                <Input
                                    type="date"
                                    className="w-44"
                                    value={filters.date}
                                    onChange={e => applyFilter('date', e.target.value)}
                                />
                            </div>
                            <div className="space-y-1">
                                <label className="text-xs font-medium text-slate-500 uppercase tracking-wide">Class</label>
                                <Select value={filters.class_id ?? ''} onValueChange={v => applyFilter('class_id', v)}>
                                    <SelectTrigger className="w-40"><SelectValue placeholder="Select class" /></SelectTrigger>
                                    <SelectContent>
                                        {classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                            </div>
                            {filteredSections.length > 0 && (
                                <div className="space-y-1">
                                    <label className="text-xs font-medium text-slate-500 uppercase tracking-wide">Section</label>
                                    <Select value={filters.section_id ?? ''} onValueChange={v => applyFilter('section_id', v)}>
                                        <SelectTrigger className="w-36"><SelectValue placeholder="All sections" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">All Sections</SelectItem>
                                            {filteredSections.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Stats + bulk actions (only shown when students loaded) */}
                {students.length > 0 && (
                    <div className="flex flex-wrap items-center gap-4">
                        <div className="flex gap-3 flex-wrap">
                            {[
                                { label: 'Present',  count: presentCount,  color: 'text-green-600' },
                                { label: 'Absent',   count: absentCount,   color: 'text-red-600'   },
                                { label: 'Late',     count: lateCount,     color: 'text-amber-600' },
                                { label: 'Half Day', count: halfDayCount,  color: 'text-blue-600'  },
                            ].map(({ label, count, color }) => (
                                <div key={label} className="text-center">
                                    <p className={`text-xl font-bold ${color}`}>{count}</p>
                                    <p className="text-xs text-slate-400">{label}</p>
                                </div>
                            ))}
                        </div>
                        <div className="ml-auto flex gap-2 flex-wrap">
                            <span className="text-xs text-slate-400 self-center">Mark all as:</span>
                            {STATUS_OPTIONS.map(({ value, label }) => (
                                <Button key={value} variant="outline" size="sm" onClick={() => markAll(students.map(s => s.id), value)}>
                                    {label}
                                </Button>
                            ))}
                        </div>
                    </div>
                )}

                {/* Table */}
                {!filters.class_id ? (
                    <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 flex items-center justify-center py-20">
                        <div className="text-center">
                            <ClipboardList className="w-12 h-12 mx-auto text-slate-300 mb-3" />
                            <p className="text-slate-500 dark:text-slate-400">Select a class and date to mark attendance</p>
                        </div>
                    </div>
                ) : students.length === 0 ? (
                    <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 flex items-center justify-center py-20">
                        <p className="text-slate-400">No active students found in this class/section.</p>
                    </div>
                ) : (
                    <>
                        <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-hidden">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-slate-50 dark:bg-slate-900">
                                        <TableHead className="w-12">#</TableHead>
                                        <TableHead>Student</TableHead>
                                        <TableHead>Roll No</TableHead>
                                        <TableHead>Section</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Remarks</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {students.map((student, idx) => {
                                        const rec = records[student.id] ?? { status: 'present', remarks: '' };
                                        const statusOpt = STATUS_OPTIONS.find(o => o.value === rec.status)!;

                                        return (
                                            <TableRow key={student.id} className={rec.status === 'absent' ? 'bg-red-50/50 dark:bg-red-950/10' : ''}>
                                                <TableCell className="text-slate-400 text-sm">{idx + 1}</TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <div className="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-950 flex items-center justify-center text-xs font-bold text-indigo-600 dark:text-indigo-400 shrink-0">
                                                            {student.first_name[0]}
                                                        </div>
                                                        <span className="text-sm font-medium text-slate-900 dark:text-white">
                                                            {student.first_name} {student.last_name}
                                                        </span>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-slate-500 text-sm">{student.roll_no ?? '—'}</TableCell>
                                                <TableCell className="text-slate-500 text-sm">{student.section?.name ?? '—'}</TableCell>
                                                <TableCell>
                                                    <div className="flex gap-1.5 flex-wrap">
                                                        {STATUS_OPTIONS.map(opt => (
                                                            <button
                                                                key={opt.value}
                                                                type="button"
                                                                onClick={() => markStudent(student.id, opt.value)}
                                                                className={`px-2.5 py-1 rounded-full text-xs font-medium border transition-all ${
                                                                    rec.status === opt.value
                                                                        ? opt.color + ' ring-2 ring-offset-1 ring-current'
                                                                        : 'bg-slate-50 dark:bg-slate-900 text-slate-400 border-slate-200 dark:border-slate-700 hover:' + opt.color
                                                                }`}
                                                            >
                                                                {opt.label}
                                                            </button>
                                                        ))}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        className="h-7 text-xs w-36"
                                                        placeholder="Remark..."
                                                        value={rec.remarks}
                                                        onChange={e => markStudent(student.id, rec.status, e.target.value)}
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="flex justify-end">
                            <Button onClick={handleSubmit} className="bg-indigo-600 hover:bg-indigo-700 text-white px-8">
                                Save Attendance ({students.length} students)
                            </Button>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
