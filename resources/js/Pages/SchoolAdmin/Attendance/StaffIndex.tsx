import { useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import { ArrowLeft, ClipboardList } from 'lucide-react';
import { Link } from '@inertiajs/react';
import type { Staff, Department, PageProps } from '@/Types';
import { useAttendanceStore } from '@/Stores/useAttendanceStore';

type AttendanceStatus = 'present' | 'absent' | 'late' | 'half_day';

interface ExistingRecord { status: AttendanceStatus; remarks: string | null; }

interface Props {
    staffList: Staff[];
    existing: Record<number, ExistingRecord>;
    departments: Department[];
    filters: { date: string; department_id?: string };
}

const STATUS_OPTIONS: { value: AttendanceStatus; label: string; color: string }[] = [
    { value: 'present',  label: 'Present',  color: 'bg-green-100 text-green-700 border-green-300' },
    { value: 'absent',   label: 'Absent',   color: 'bg-red-100 text-red-700 border-red-300'       },
    { value: 'late',     label: 'Late',     color: 'bg-amber-100 text-amber-700 border-amber-300' },
    { value: 'half_day', label: 'Half Day', color: 'bg-blue-100 text-blue-700 border-blue-300'    },
];

export default function StaffAttendanceIndex({ staffList, existing, departments, filters }: Props) {
    const { flash } = usePage<PageProps>().props;
    const { records, markStudent, markAll, initRecords } = useAttendanceStore();

    useEffect(() => {
        if (staffList.length > 0) {
            initRecords(existing as Record<number, { status: AttendanceStatus; remarks: string | null }>, staffList.map(s => s.id));
        }
    }, [staffList.length]);

    function applyFilter(key: string, value: string) {
        router.get('/school/attendance/staff', { ...filters, [key]: value || undefined }, { preserveScroll: true });
    }

    function handleSubmit() {
        if (staffList.length === 0) return;
        const recordsPayload = staffList.map(s => ({
            staff_id: s.id,
            status:   records[s.id]?.status  ?? 'present',
            remarks:  records[s.id]?.remarks ?? '',
        }));
        router.post('/school/attendance/staff', {
            date:    filters.date,
            records: recordsPayload,
        }, { preserveScroll: true });
    }

    const presentCount = Object.values(records).filter(r => r.status === 'present').length;
    const absentCount  = Object.values(records).filter(r => r.status === 'absent').length;

    return (
        <AppLayout title="Staff Attendance">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link href="/school/attendance" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                            <ArrowLeft className="w-4 h-4" /> Student Attendance
                        </Link>
                        <span className="text-slate-300 dark:text-slate-700">|</span>
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Staff Attendance</h1>
                        </div>
                    </div>
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
                                <label className="text-xs font-medium text-slate-500 uppercase tracking-wide">Department</label>
                                <Select value={filters.department_id ?? ''} onValueChange={v => applyFilter('department_id', v)}>
                                    <SelectTrigger className="w-48"><SelectValue placeholder="All Departments" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">All Departments</SelectItem>
                                        {departments.map(d => <SelectItem key={d.id} value={String(d.id)}>{d.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {staffList.length > 0 && (
                    <div className="flex items-center gap-6">
                        <div className="flex gap-4">
                            <div className="text-center">
                                <p className="text-xl font-bold text-green-600">{presentCount}</p>
                                <p className="text-xs text-slate-400">Present</p>
                            </div>
                            <div className="text-center">
                                <p className="text-xl font-bold text-red-600">{absentCount}</p>
                                <p className="text-xs text-slate-400">Absent</p>
                            </div>
                        </div>
                        <div className="ml-auto flex gap-2 flex-wrap">
                            <span className="text-xs text-slate-400 self-center">Mark all:</span>
                            {STATUS_OPTIONS.map(({ value, label }) => (
                                <Button key={value} variant="outline" size="sm" onClick={() => markAll(staffList.map(s => s.id), value)}>
                                    {label}
                                </Button>
                            ))}
                        </div>
                    </div>
                )}

                {staffList.length === 0 ? (
                    <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 flex items-center justify-center py-20">
                        <div className="text-center">
                            <ClipboardList className="w-12 h-12 mx-auto text-slate-300 mb-3" />
                            <p className="text-slate-500">No active staff found.</p>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-hidden">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-slate-50 dark:bg-slate-900">
                                        <TableHead>#</TableHead>
                                        <TableHead>Staff Member</TableHead>
                                        <TableHead>Emp ID</TableHead>
                                        <TableHead>Department</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Remarks</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {staffList.map((s, idx) => {
                                        const rec = records[s.id] ?? { status: 'present', remarks: '' };
                                        return (
                                            <TableRow key={s.id} className={rec.status === 'absent' ? 'bg-red-50/50 dark:bg-red-950/10' : ''}>
                                                <TableCell className="text-slate-400 text-sm">{idx + 1}</TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <div className="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-950 flex items-center justify-center text-xs font-bold text-indigo-600 dark:text-indigo-400 shrink-0">
                                                            {s.first_name[0]}
                                                        </div>
                                                        <span className="text-sm font-medium text-slate-900 dark:text-white">{s.full_name}</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="font-mono text-xs text-slate-400">{s.emp_id}</TableCell>
                                                <TableCell className="text-slate-500 text-sm">{s.department?.name ?? '—'}</TableCell>
                                                <TableCell>
                                                    <div className="flex gap-1.5 flex-wrap">
                                                        {STATUS_OPTIONS.map(opt => (
                                                            <button
                                                                key={opt.value}
                                                                type="button"
                                                                onClick={() => markStudent(s.id, opt.value as AttendanceStatus)}
                                                                className={`px-2.5 py-1 rounded-full text-xs font-medium border transition-all ${
                                                                    rec.status === opt.value
                                                                        ? opt.color + ' ring-2 ring-offset-1 ring-current'
                                                                        : 'bg-slate-50 dark:bg-slate-900 text-slate-400 border-slate-200 dark:border-slate-700'
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
                                                        onChange={e => markStudent(s.id, rec.status as AttendanceStatus, e.target.value)}
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
                                Save Attendance ({staffList.length} staff)
                            </Button>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
