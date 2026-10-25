import { Link, useForm } from '@inertiajs/react';
import CbtShell from '@/components/cbt/CbtShell';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Panel } from '@/components/app/kit';
import { examTime } from '@/components/cbt/CbtParts';
import { ArrowLeft, PlayCircle } from 'lucide-react';

interface Props {
    base: string;
    person?: { name: string; number: string | null };
    test: {
        id: number; title: string; subject: string | null; instructions: string | null; duration_minutes: number; questions: number;
        sections: { title: string; count: number }[]; needs_code: boolean; open: boolean; opens_at: string; closes_at: string | null; max_page_leaves: number | null;
    };
}

export default function StudentCbtStart({ base, person, test }: Props) {
    const form = useForm({ code: '' });
    const errors = form.errors as Record<string, string | undefined>;

    return (
        <CbtShell title={test.title} person={person}>
            <div className="mx-auto max-w-2xl space-y-6 pb-24 md:pb-0">
                {!person && <Link href="/school/student/cbt" className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800 dark:hover:text-slate-200"><ArrowLeft className="size-4" /> My tests</Link>}
                <Panel>
                    <h1 className="text-xl font-semibold text-slate-900 dark:text-white">{test.title}</h1>
                    <p className="mt-1 text-sm text-slate-500">{test.subject ? `${test.subject} · ` : ''}{test.questions} questions · {test.duration_minutes} minutes</p>
                    {test.sections.length > 1 && (
                        <ul className="mt-3 text-sm text-slate-600 dark:text-slate-300">{test.sections.map((s, i) => <li key={i}>{s.title}: {s.count} questions</li>)}</ul>
                    )}
                    {test.instructions && <p className="mt-4 whitespace-pre-line text-sm leading-relaxed">{test.instructions}</p>}
                    <ul className="mt-4 list-disc space-y-1 pl-5 text-sm text-slate-600 dark:text-slate-300">
                        <li>The timer starts when you press Start and does not stop, even if you close the page.</li>
                        <li>Every answer is saved the moment you click it. If the power or network goes, log in again on any computer and carry on.</li>
                        <li>Leaving the test page is recorded{test.max_page_leaves ? `, and after ${test.max_page_leaves} times your test is handed in by itself` : ''}.</li>
                        <li>When the time is up, your test is handed in by itself.</li>
                    </ul>

                    {test.open ? (
                        <form onSubmit={e => { e.preventDefault(); form.post(`${base}/start`); }} className="mt-6 space-y-4">
                            {test.needs_code && (
                                <div className="max-w-xs space-y-1.5">
                                    <Label htmlFor="code">Start code from your supervisor</Label>
                                    <Input id="code" value={form.data.code} onChange={e => form.setData('code', e.target.value.toUpperCase())} autoComplete="off" className="font-mono uppercase" />
                                    {errors.code && <p className="text-xs text-red-500">{errors.code}</p>}
                                </div>
                            )}
                            {errors.test && <p className="text-sm text-red-600">{errors.test}</p>}
                            <Button type="submit" disabled={form.processing} className="h-12 w-full bg-indigo-600 text-base text-white hover:bg-indigo-700 sm:w-auto sm:px-8"><PlayCircle className="size-5" /> Start the test</Button>
                        </form>
                    ) : (
                        <p className="mt-6 rounded-lg bg-slate-100 px-4 py-3 text-sm dark:bg-white/[0.06]">
                            {new Date(test.opens_at) > new Date() ? `This test opens ${examTime(test.opens_at)}.` : 'This test is closed.'}
                        </p>
                    )}
                </Panel>
            </div>
        </CbtShell>
    );
}
