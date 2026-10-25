import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { EmptyState, PageHeader, Panel, Pill, Segmented } from '@/components/app/kit';
import { NotebookPen } from 'lucide-react';

interface Homework { id: number; title: string; description: string | null; subject: string | null; due_label: string; overdue: boolean; }
interface Slot { subject: string | null; start_time: string; end_time: string; room: string | null; }
interface Child {
    id: number; full_name: string; class: string | null; section: string | null;
    homework: Homework[]; timetable: Record<string, Slot[]>;
}
interface Props { linked: boolean; children: Child[]; today: string | null; }

const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

function HomeworkList({ items }: { items: Homework[] }) {
    if (items.length === 0) return <p className="py-8 text-center text-sm text-slate-500">No homework set at the moment.</p>;

    return (
        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
            {items.map(h => (
                <li key={h.id} className="py-3.5">
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="text-sm font-medium text-slate-900 dark:text-white">{h.title}</p>
                        {h.subject && <Pill>{h.subject}</Pill>}
                        {h.overdue && <Pill tone="bad">Past due</Pill>}
                    </div>
                    {h.description && <p className="mt-1 whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{h.description}</p>}
                    <p className="mt-1 text-xs text-slate-500">Due {h.due_label}</p>
                </li>
            ))}
        </ul>
    );
}

function TimetableView({ timetable, today }: { timetable: Record<string, Slot[]>; today: string | null }) {
    const days = DAYS.filter(d => timetable[d]?.length > 0);
    if (days.length === 0) return <p className="py-8 text-center text-sm text-slate-500">No timetable has been set for this class yet.</p>;

    return (
        <div className="space-y-4">
            {days.map(day => (
                <div key={day} className={cn('rounded-lg border p-3', day === today ? 'border-indigo-300 dark:border-indigo-500/40' : 'border-slate-100 dark:border-white/[0.06]')}>
                    <p className="mb-2 flex items-center gap-2 text-sm font-semibold capitalize text-slate-900 dark:text-white">
                        {day}{day === today && <Pill tone="info">Today</Pill>}
                    </p>
                    <ul className="space-y-1.5">
                        {timetable[day].map((s, i) => (
                            <li key={i} className="flex items-center justify-between gap-3 text-sm">
                                <span className="text-slate-800 dark:text-slate-200">{s.subject ?? '—'}{s.room ? <span className="text-slate-400"> · Room {s.room}</span> : null}</span>
                                <span className="shrink-0 tabular-nums text-slate-500">{s.start_time} – {s.end_time}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            ))}
        </div>
    );
}

export default function ParentClasswork({ linked, children, today }: Props) {
    const [childId, setChildId] = useState<number | null>(children[0]?.id ?? null);
    const [tab, setTab] = useState<'homework' | 'timetable'>('homework');

    if (!linked || children.length === 0) {
        return (
            <AppLayout title="Homework & Timetable">
                <Panel><EmptyState icon={NotebookPen} title={linked ? 'No children yet' : 'Account not linked'} text="Please contact the school office if this looks wrong." /></Panel>
            </AppLayout>
        );
    }

    const child = children.find(c => c.id === childId) ?? children[0];

    return (
        <AppLayout title="Homework & Timetable">
            <div className="space-y-6">
                <PageHeader title="Homework & timetable" description={`${child.full_name} · ${[child.class, child.section].filter(Boolean).join(' · ')}`} />

                {children.length > 1 && (
                    <Segmented label="Child" value={String(child.id)} onChange={v => setChildId(Number(v))} options={children.map(c => ({ value: String(c.id), label: c.full_name.split(' ')[0] }))} />
                )}
                <Segmented label="Show" value={tab} onChange={setTab} options={[{ value: 'homework', label: 'Homework', count: child.homework.length }, { value: 'timetable', label: 'Timetable' }]} />

                <Panel>
                    {tab === 'homework' ? <HomeworkList items={child.homework} /> : <TimetableView timetable={child.timetable} today={today} />}
                </Panel>
            </div>
        </AppLayout>
    );
}
