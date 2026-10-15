import { Head, Link } from '@inertiajs/react';
import { BookOpen, CalendarDays, ClipboardCheck, ClipboardList, GraduationCap, Users } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface MyClass { id: number; name: string; students: number; class_teacher: boolean; subjects: string[] }
interface Lesson { id: number; start: string; end: string; class: string; subject: string | null; room: string | null }
interface Props { staffName: string; hasStaffCard: boolean; classes: MyClass[]; today: Lesson[] }

export default function MyClasses({ staffName, hasStaffCard, classes, today }: Props) {
    return (
        <AppLayout breadcrumbs={[{ label: 'My Classes' }]}>
            <Head title="My Classes" />

            <div className="mb-6">
                <h1 className="text-xl font-bold text-slate-900 dark:text-white">Hello, {staffName}</h1>
                <p className="text-sm text-slate-500 mt-0.5">Your classes and today's lessons, with the things you do most one tap away.</p>
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                <Card className="lg:col-span-2 dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><GraduationCap className="w-4 h-4" /> My classes</CardTitle></CardHeader>
                    <CardContent className="space-y-3">
                        {classes.length === 0 ? (
                            <p className="text-sm text-slate-500">
                                {hasStaffCard
                                    ? 'No class is assigned to you yet. Ask the school admin to set you as a class teacher or subject teacher on the Classes page.'
                                    : 'Your login is not linked to a staff record yet. Ask the school admin to add you under Staff.'}
                            </p>
                        ) : classes.map((c) => (
                            <div key={c.id} className="rounded-lg border border-slate-200 dark:border-slate-800 p-3">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="font-semibold text-slate-900 dark:text-white">{c.name}</span>
                                        {c.class_teacher && <Badge className="bg-indigo-100 text-indigo-700">Class teacher</Badge>}
                                        <span className="text-xs text-slate-500 inline-flex items-center gap-1"><Users className="w-3 h-3" /> {c.students} students</span>
                                    </div>
                                    <div className="flex flex-wrap gap-2 text-xs">
                                        <Link href={`/school/attendance?class_id=${c.id}`} className="inline-flex items-center gap-1 rounded-md bg-slate-100 dark:bg-slate-800 px-2.5 py-1.5 font-medium hover:bg-slate-200"><ClipboardList className="w-3.5 h-3.5" /> Attendance</Link>
                                        <Link href="/school/results" className="inline-flex items-center gap-1 rounded-md bg-slate-100 dark:bg-slate-800 px-2.5 py-1.5 font-medium hover:bg-slate-200"><ClipboardCheck className="w-3.5 h-3.5" /> Scores</Link>
                                        <Link href="/school/homework" className="inline-flex items-center gap-1 rounded-md bg-slate-100 dark:bg-slate-800 px-2.5 py-1.5 font-medium hover:bg-slate-200"><BookOpen className="w-3.5 h-3.5" /> Homework</Link>
                                    </div>
                                </div>
                                {c.subjects.length > 0 && <p className="mt-2 text-xs text-slate-500">You teach: {c.subjects.join(', ')}</p>}
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><CalendarDays className="w-4 h-4" /> Today</CardTitle></CardHeader>
                    <CardContent>
                        {today.length === 0 ? <p className="text-sm text-slate-500">No lessons on your timetable today.</p> : (
                            <ul className="space-y-2">
                                {today.map((l) => (
                                    <li key={l.id} className="flex items-start gap-3 text-sm">
                                        <span className="w-24 shrink-0 font-medium text-slate-700 dark:text-slate-200">{l.start} to {l.end}</span>
                                        <span className="text-slate-600 dark:text-slate-300">{l.subject} · {l.class}{l.room ? ` · ${l.room}` : ''}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
