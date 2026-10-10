import { useEffect } from 'react';
import { router, usePage, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { EmptyState, PageHeader, Panel, PersonAvatar, StatStrip } from '@/components/app/kit';
import { cn } from '@/lib/utils';
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

const STATUS_OPTIONS: { value: AttendanceStatus; label: string; short: string; on: string; icon: React.ElementType }[] = [
    { value: 'present',  label: 'Present',  short: 'P', on: 'border-emerald-600 bg-emerald-600 text-white', icon: CheckCircle2 },
    { value: 'absent',   label: 'Absent',   short: 'A', on: 'border-red-600 bg-red-600 text-white',         icon: XCircle      },
    { value: 'late',     label: 'Late',     short: 'L', on: 'border-amber-500 bg-amber-500 text-white',     icon: Clock        },
    { value: 'half_day', label: 'Half day', short: 'H', on: 'border-indigo-600 bg-indigo-600 text-white',   icon: MinusCircle  },
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

    const labelCls = 'text-xs font-medium text-slate-500';
    const markedCount = presentCount + absentCount + lateCount + halfDayCount;

    function StatusButtons({ studentId, status }: { studentId: number; status: AttendanceStatus }) {
        return (
            <div role="radiogroup" aria-label="Attendance status" className="grid grid-cols-4 gap-1.5 md:inline-grid md:w-[17rem]">
                {STATUS_OPTIONS.map(opt => (
                    <button
                        key={opt.value}
                        type="button"
                        role="radio"
                        aria-checked={status === opt.value}
                        onClick={() => markStudent(studentId, opt.value)}
                        className={cn(
                            'h-10 rounded-lg border text-[13px] font-medium outline-none transition-colors focus-visible:ring-2 focus-visible:ring-indigo-400 md:h-8',
                            status === opt.value
                                ? opt.on
                                : 'border-slate-200 bg-white text-slate-500 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-400 dark:hover:bg-white/[0.06]',
                        )}
                    >
                        <span className="md:hidden">{opt.short}</span>
                        <span className="hidden md:inline">{opt.label}</span>
                    </button>
                ))}
            </div>
        );
    }

    return (
        <AppLayout breadcrumbs={[{ label: 'Attendance' }, { label: 'Students' }]}>
            <div className="space-y-6 pb-24 md:pb-0">
                <PageHeader
                    title="Student attendance"
                    description="Mark the daily register, class by class."
                    actions={
                        <a href="/school/attendance/staff" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 shadow-xs hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                            <ClipboardList className="size-4" /> Staff attendance
                        </a>
                    }
                />

                {flash?.success && (
                    <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                        {flash.success}
                    </div>
                )}

                <div className="flex flex-wrap items-end gap-3">
                    <div className="flex flex-col space-y-1.5">
                        <label className={labelCls}>Date</label>
                        <Input type="date" className="h-10 w-44" value={filters.date} onChange={e => applyFilter('date', e.target.value)} />
                    </div>
                    <div className="space-y-1.5">
                        <label className={labelCls}>Class</label>
                        <Select value={filters.class_id ?? ''} onValueChange={v => applyFilter('class_id', v)}>
                            <SelectTrigger className="h-10 w-44"><SelectValue placeholder="Select class" /></SelectTrigger>
                            <SelectContent>
                                {classes.map(c => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                            </SelectContent>
                        </Select>
                    </div>
                    {filteredSections.length > 0 && (
                        <div className="space-y-1.5">
                            <label className={labelCls}>Section</label>
                            <Select value={filters.section_id ?? ''} onValueChange={v => applyFilter('section_id', v)}>
                                <SelectTrigger className="h-10 w-40"><SelectValue placeholder="All sections" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All sections</SelectItem>
                                    {filteredSections.map(s => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                    )}
                </div>

                {!filters.class_id ? (
                    <Panel><EmptyState icon={ClipboardList} title="Pick a class" text="Choose a class and date above to mark the register." /></Panel>
                ) : students.length === 0 ? (
                    <Panel><EmptyState icon={ClipboardList} title="No students found" text="There are no active students in this class or section." /></Panel>
                ) : (
                    <>
                        <StatStrip items={[
                            { label: 'Present', value: presentCount, tone: 'good' },
                            { label: 'Absent', value: absentCount, tone: absentCount ? 'bad' : 'default' },
                            { label: 'Late', value: lateCount, tone: lateCount ? 'warn' : 'default' },
                            { label: 'Half day', value: halfDayCount },
                        ]} />

                        <Panel
                            flush
                            title="Register"
                            description={`${markedCount} of ${students.length} students`}
                            action={
                                <div className="flex items-center gap-1.5">
                                    <span className="hidden text-xs text-slate-400 sm:inline">Mark all</span>
                                    {STATUS_OPTIONS.slice(0, 2).map(({ value, label }) => (
                                        <Button key={value} variant="outline" size="sm" onClick={() => markAll(students.map(s => s.id), value)}>
                                            {label}
                                        </Button>
                                    ))}
                                </div>
                            }
                        >
                            <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                                {students.map((student, idx) => {
                                    const rec = records[student.id] ?? { status: 'present', remarks: '' };
                                    const name = `${student.first_name} ${student.last_name ?? ''}`.trim();
                                    return (
                                        <li key={student.id} className={cn('flex flex-col gap-3 px-4 py-3 md:flex-row md:items-center md:gap-4 md:px-5', rec.status === 'absent' && 'bg-red-50/40 dark:bg-red-500/[0.04]')}>
                                            <div className="flex min-w-0 flex-1 items-center gap-3">
                                                <span className="hidden w-6 text-xs tabular-nums text-slate-400 md:block">{idx + 1}</span>
                                                <PersonAvatar name={name} />
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-medium text-slate-900 dark:text-white">{name}</p>
                                                    <p className="text-xs text-slate-500">
                                                        {[student.roll_no && `Roll ${student.roll_no}`, student.section?.name].filter(Boolean).join(' · ') || '—'}
                                                    </p>
                                                </div>
                                            </div>
                                            <StatusButtons studentId={student.id} status={rec.status as AttendanceStatus} />
                                            <Input
                                                className="h-9 text-sm md:w-44"
                                                placeholder="Remark (optional)"
                                                aria-label={`Remark for ${name}`}
                                                value={rec.remarks}
                                                onChange={e => markStudent(student.id, rec.status, e.target.value)}
                                            />
                                        </li>
                                    );
                                })}
                            </ul>
                        </Panel>

                        <div className="fixed inset-x-0 bottom-[calc(4rem+env(safe-area-inset-bottom))] z-20 border-t border-slate-200 bg-white/95 p-3 backdrop-blur md:static md:border-0 md:bg-transparent md:p-0 dark:border-white/10 dark:bg-slate-950/95">
                            <Button onClick={handleSubmit} className="h-11 w-full md:ml-auto md:flex md:h-9 md:w-auto">
                                Save attendance ({students.length} students)
                            </Button>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
