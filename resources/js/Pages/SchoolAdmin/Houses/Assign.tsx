import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { EmptyState, PageHeader, Panel } from '@/components/app/kit';
import { ArrowLeft, Users } from 'lucide-react';

interface Row { id: number; name: string; admission_no: string; gender: string | null; section: string | null; house_id: number | null }
interface Props {
    classes: { id: number; name: string }[]; class_id: number | null;
    houses: { id: number; name: string; colour: string }[]; students: Row[];
}

const selectClass = 'h-9 rounded-lg border border-slate-200 bg-transparent px-2 text-sm dark:border-white/10 dark:bg-slate-900';

export default function HousesAssign({ classes, class_id, houses, students }: Props) {
    const [choice, setChoice] = useState<Record<number, string>>(() => Object.fromEntries(students.map(s => [s.id, s.house_id ? String(s.house_id) : ''])));
    const [saving, setSaving] = useState(false);
    const changed = students.filter(s => (s.house_id ? String(s.house_id) : '') !== choice[s.id]);
    const colour = (id: string) => houses.find(h => String(h.id) === id)?.colour ?? 'transparent';

    function save() {
        setSaving(true);
        router.post('/school/houses/assign', { houses: Object.fromEntries(changed.map(s => [s.id, choice[s.id] || null])) }, { preserveScroll: true, onFinish: () => setSaving(false) });
    }

    return (
        <AppLayout title="Change houses">
            <div className="space-y-6">
                <Link href="/school/houses" className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                    <ArrowLeft className="size-4" /> Houses
                </Link>
                <PageHeader title="Change houses by class" description="Pick a class, change any student's house, then save." />

                <div className="flex flex-wrap items-center gap-3">
                    <select aria-label="Class" className={selectClass} value={class_id ?? ''} onChange={e => router.get('/school/houses/assign', { class_id: e.target.value }, { preserveState: false })}>
                        {classes.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                    </select>
                    <Button onClick={save} disabled={!changed.length || saving} className="bg-indigo-600 text-white hover:bg-indigo-700">
                        Save{changed.length ? ` (${changed.length} changed)` : ''}
                    </Button>
                </div>

                {houses.length === 0 ? (
                    <Panel><EmptyState icon={Users} title="No houses in use" text="Add houses first on the Houses page." /></Panel>
                ) : students.length === 0 ? (
                    <Panel><EmptyState icon={Users} title="No active students in this class" /></Panel>
                ) : (
                    <Panel flush>
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {students.map(s => (
                                <li key={s.id} className="flex flex-wrap items-center gap-3 px-4 py-2 text-sm">
                                    <span className="size-2.5 shrink-0 rounded-full" style={{ background: colour(choice[s.id]) }} />
                                    <div className="min-w-0 flex-1">
                                        <p className="font-medium">{s.name}</p>
                                        <p className="text-xs text-slate-500">{s.admission_no}{s.section ? ` · ${s.section}` : ''}{s.gender ? ` · ${s.gender}` : ''}</p>
                                    </div>
                                    <select aria-label={`House for ${s.name}`} className={selectClass} value={choice[s.id]} onChange={e => setChoice({ ...choice, [s.id]: e.target.value })}>
                                        <option value="">No house</option>
                                        {houses.map(h => <option key={h.id} value={h.id}>{h.name}</option>)}
                                    </select>
                                </li>
                            ))}
                        </ul>
                    </Panel>
                )}
            </div>
        </AppLayout>
    );
}
