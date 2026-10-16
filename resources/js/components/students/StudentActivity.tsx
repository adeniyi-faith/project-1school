import { Link } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';

import { naira } from '@/lib/format';
export interface StudentActivity {
    hostel: { id: number; hostel: string | null; room: string | null; floor: string | null; bed: string | null; joined: string | null; left: string | null; status: string }[] | null;
    library: { id: number; title: string | null; author: string | null; issued: string | null; due: string | null; returned: string | null; fine: number; status: string }[] | null;
    transport: { id: number; route: string; from: string | null; to: string | null; vehicle: string | null; plate: string | null; stop: string | null }[] | null;
    fees: { owed: number; invoices: { id: number; no: string; fee: string | null; term: string | null; amount: number; balance: number; status: string; due: string | null }[] } | null;
    attendance: { days: number; present: number; late: number; absent: number; half_day: number; rate: number | null } | null;
    results: { id: number; sheet_id: number; term: string | null; average: number | null; position: number | null; class_size: number | null }[] | null;
    tests: { id: number; test_id: number; title: string | null; when: string | null; percent: number | null; needs_marking: boolean }[] | null;
}

export type ActivityKey = keyof StudentActivity;

export const ACTIVITY_LABELS: Record<ActivityKey, string> = {
    hostel: 'Hostel', library: 'Library', transport: 'Bus', fees: 'Fees', attendance: 'Attendance', results: 'Results', tests: 'Tests',
};

const day = (d: string | null) => (d ? new Date(d).toLocaleDateString(undefined, { dateStyle: 'medium' }) : '—');

function Empty({ text }: { text: string }) {
    return <p className="py-10 text-center text-sm text-slate-500">{text}</p>;
}

function Row({ children }: { children: React.ReactNode }) {
    return <li className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 py-3 text-sm">{children}</li>;
}

/** One school-activity tab on a student's profile: shows only what the viewer was sent (the server leaves out what they may not see) */
export function StudentActivityPanel({ kind, data }: { kind: ActivityKey; data: StudentActivity }) {
    return (
        <Card className="border-slate-200 dark:border-slate-800 dark:bg-slate-900">
            <CardContent className="p-4 sm:p-5">
                {kind === 'hostel' && data.hostel && (data.hostel.length === 0 ? <Empty text="Not in a hostel." /> : (
                    <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                        {data.hostel.map(a => (
                            <Row key={a.id}>
                                <span><span className="font-medium">{a.hostel}</span> · Room {a.room}{a.floor ? ` (${a.floor})` : ''}{a.bed ? ` · ${/^bed/i.test(a.bed) ? a.bed : `Bed ${a.bed}`}` : ''}</span>
                                <span className="text-slate-500">{day(a.joined)}{a.left ? ` to ${day(a.left)}` : ' to now'} · <span className="capitalize">{a.status === 'left' ? 'left' : 'living here'}</span></span>
                            </Row>
                        ))}
                    </ul>
                ))}

                {kind === 'library' && data.library && (data.library.length === 0 ? <Empty text="No books borrowed yet." /> : (
                    <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                        {data.library.map(b => (
                            <Row key={b.id}>
                                <span className="min-w-0"><span className="font-medium">{b.title}</span>{b.author ? <span className="text-slate-500"> · {b.author}</span> : null}</span>
                                <span className="text-slate-500">
                                    Taken {day(b.issued)} · {b.returned ? `returned ${day(b.returned)}` : `due ${day(b.due)}`}
                                    {b.status === 'overdue' || (!b.returned && b.due && new Date(b.due) < new Date()) ? <span className="ml-1 font-medium text-red-600">overdue</span> : null}
                                    {b.fine > 0 ? ` · fine ${naira(b.fine)}` : ''}
                                </span>
                            </Row>
                        ))}
                    </ul>
                ))}

                {kind === 'transport' && data.transport && (data.transport.length === 0 ? <Empty text="Not on a bus route." /> : (
                    <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                        {data.transport.map(r => (
                            <Row key={r.id}>
                                <span><span className="font-medium">{r.route}</span>{r.from && r.to ? ` · ${r.from} to ${r.to}` : ''}</span>
                                <span className="text-slate-500">{r.vehicle ? `${r.vehicle}${r.plate ? ` (${r.plate})` : ''}` : 'No bus set'}{r.stop ? ` · stop: ${r.stop}` : ''}</span>
                            </Row>
                        ))}
                    </ul>
                ))}

                {kind === 'fees' && data.fees && (
                    <>
                        <p className="mb-2 text-sm">Still owed: <span className={`font-semibold ${data.fees.owed > 0 ? 'text-red-600' : 'text-emerald-600'}`}>{naira(data.fees.owed)}</span></p>
                        {data.fees.invoices.length === 0 ? <Empty text="No invoices yet." /> : (
                            <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                                {data.fees.invoices.map(i => (
                                    <Row key={i.id}>
                                        <Link href={`/school/fees/invoices/${i.id}`} className="min-w-0 hover:underline"><span className="font-medium">{i.fee ?? 'Fee'}</span> · {i.term ?? i.no}</Link>
                                        <span className="text-slate-500">{naira(i.amount)} · owes {naira(i.balance)} · <span className="capitalize">{i.status}</span></span>
                                    </Row>
                                ))}
                            </ul>
                        )}
                    </>
                )}

                {kind === 'attendance' && data.attendance && (data.attendance.days === 0 ? <Empty text="No attendance marked in the last 90 days." /> : (
                    <div>
                        <p className="text-sm text-slate-500">Last 90 days · {data.attendance.days} days marked</p>
                        <p className="mt-1 text-2xl font-semibold">{data.attendance.rate}% <span className="text-sm font-normal text-slate-500">present or late</span></p>
                        <p className="mt-2 text-sm">{data.attendance.present} present, {data.attendance.late} late, {data.attendance.absent} absent{data.attendance.half_day ? `, ${data.attendance.half_day} half days` : ''}</p>
                    </div>
                ))}

                {kind === 'results' && data.results && (data.results.length === 0 ? <Empty text="No published results yet." /> : (
                    <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                        {data.results.map(r => (
                            <Row key={r.id}>
                                <span className="font-medium">{r.term}</span>
                                <span className="text-slate-500">Average {r.average != null ? r.average.toFixed(1) : '—'} · position {r.position ?? '—'}{r.class_size ? ` of ${r.class_size}` : ''}</span>
                            </Row>
                        ))}
                    </ul>
                ))}

                {kind === 'tests' && data.tests && (data.tests.length === 0 ? <Empty text="No computer tests taken yet." /> : (
                    <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                        {data.tests.map(t => (
                            <Row key={t.id}>
                                <Link href={`/school/cbt/tests/${t.test_id}/attempts/${t.id}`} className="min-w-0 font-medium hover:underline">{t.title}</Link>
                                <span className="text-slate-500">{day(t.when)} · {t.percent != null ? `${t.percent}%` : t.needs_marking ? 'waiting to be marked' : '—'}</span>
                            </Row>
                        ))}
                    </ul>
                ))}
            </CardContent>
        </Card>
    );
}
