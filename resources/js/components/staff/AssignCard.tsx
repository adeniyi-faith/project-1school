import { useState } from 'react';
import { router } from '@inertiajs/react';
import { ChevronRight, GraduationCap } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export interface AssignClass {
    id: number;
    name: string;
    is_class_teacher: boolean;
    class_teacher: string | null;
    subjects: { id: number; name: string; mine: boolean; teacher: string | null }[];
}

/** "Assign to classes and subjects": tick the classes this person is class teacher of and the subjects they teach. */
export default function AssignCard({ staffId, staffName, classes, label }: { staffId: number; staffName: string; classes: AssignClass[]; label: string }) {
    const [classIds, setClassIds] = useState<number[]>(classes.filter(c => c.is_class_teacher).map(c => c.id));
    const [lineIds, setLineIds] = useState<number[]>(classes.flatMap(c => c.subjects.filter(s => s.mine).map(s => s.id)));
    const [saving, setSaving] = useState(false);

    const toggle = (list: number[], set: (v: number[]) => void, id: number) =>
        set(list.includes(id) ? list.filter(x => x !== id) : [...list, id]);

    const save = () => {
        setSaving(true);
        router.put(`/school/staff/${staffId}/assignments`, { class_ids: classIds, line_ids: lineIds }, {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        });
    };

    return (
        <Card className="border-slate-200 dark:border-slate-800">
            <CardHeader className="pb-3">
                <CardTitle className="text-base flex items-center gap-2"><GraduationCap className="w-4 h-4" /> Assign to classes and subjects</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                <p className="text-sm text-slate-600 dark:text-slate-400">
                    Tick "{label}" for each class {staffName} is in charge of. Open a class to tick the subjects {staffName} teaches there.
                    A class has one {label.toLowerCase()}; ticking one that someone else has takes it over.
                </p>
                {classes.length === 0 ? (
                    <p className="text-sm text-slate-500">No classes yet. Add them on the Classes page first.</p>
                ) : (
                    <div className="space-y-2 max-h-[28rem] overflow-y-auto pr-1">
                        {classes.map(c => {
                            const mineHere = c.subjects.filter(s => lineIds.includes(s.id)).length;
                            return (
                                <details key={c.id} className="group rounded-lg border border-slate-200 dark:border-slate-800 px-3 py-2" open={classIds.includes(c.id) || mineHere > 0}>
                                    <summary className="cursor-pointer list-none text-sm font-medium text-slate-900 dark:text-white flex flex-wrap items-center gap-2">
                                        <ChevronRight className="w-4 h-4 text-slate-400 transition-transform group-open:rotate-90" />
                                        {c.name}
                                        {classIds.includes(c.id) && <span className="text-xs rounded bg-indigo-100 text-indigo-700 px-1.5 py-0.5">{label}</span>}
                                        {mineHere > 0 && <span className="text-xs rounded bg-emerald-100 text-emerald-700 px-1.5 py-0.5">{mineHere} subject{mineHere > 1 ? 's' : ''}</span>}
                                    </summary>
                                    <div className="mt-3 space-y-2">
                                        <label className="flex items-center gap-2 text-sm">
                                            <input type="checkbox" className="h-4 w-4" checked={classIds.includes(c.id)} onChange={() => toggle(classIds, setClassIds, c.id)} />
                                            <span>{label} of {c.name}{c.class_teacher && !classIds.includes(c.id) ? <span className="text-xs text-slate-500"> (now {c.class_teacher})</span> : null}</span>
                                        </label>
                                        {c.subjects.length === 0 ? (
                                            <p className="text-xs text-slate-500">This class has no subjects yet. Add them on the Subjects page.</p>
                                        ) : (
                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 border-t border-slate-100 dark:border-slate-800 pt-2">
                                                {c.subjects.map(s => (
                                                    <label key={s.id} className="flex items-center gap-2 text-sm">
                                                        <input type="checkbox" className="h-4 w-4" checked={lineIds.includes(s.id)} onChange={() => toggle(lineIds, setLineIds, s.id)} />
                                                        <span>{s.name}{s.teacher && !lineIds.includes(s.id) ? <span className="text-xs text-slate-500"> (now {s.teacher})</span> : null}</span>
                                                    </label>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </details>
                            );
                        })}
                    </div>
                )}
                <Button type="button" onClick={save} disabled={saving} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                    {saving ? 'Saving...' : 'Save assignments'}
                </Button>
            </CardContent>
        </Card>
    );
}
