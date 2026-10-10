import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Panel } from '@/components/app/kit';
import { CheckCircle2 } from 'lucide-react';

interface Props {
    test: { id: number; title: string; show_score: boolean };
    result: {
        status: 'submitted' | 'timed_out'; ended_reason: string | null; answered: number; questions: number;
        score: number | null; max_score: number | null; sections: { title: string; score: number; max: number }[];
    };
}

export default function StudentCbtDone({ test, result }: Props) {
    const reason = result.ended_reason === 'left_page'
        ? 'Your test was handed in because you left the test page too many times.'
        : result.status === 'timed_out' ? 'Your time ran out, so your test was handed in by itself.' : 'You handed in your test.';
    const percent = result.score != null && result.max_score ? Math.round((result.score / result.max_score) * 100) : null;

    return (
        <AppLayout title={test.title}>
            <div className="mx-auto max-w-xl space-y-6 pb-24 md:pb-0">
                <Panel>
                    <div className="text-center">
                        <CheckCircle2 className="mx-auto size-12 text-emerald-500" />
                        <h1 className="mt-3 text-xl font-semibold text-slate-900 dark:text-white">{test.title}</h1>
                        <p className="mt-1 text-sm text-slate-500">{reason} You answered {result.answered} of {result.questions} questions.</p>
                        {result.score != null ? (
                            <p className="mt-5 text-4xl font-semibold tabular-nums text-slate-900 dark:text-white">
                                {result.score} <span className="text-lg font-normal text-slate-500">/ {result.max_score}</span>
                                {percent != null && <span className="ml-2 text-lg font-medium text-slate-500">({percent}%)</span>}
                            </p>
                        ) : (
                            <p className="mt-5 text-sm text-slate-600 dark:text-slate-300">Your teacher will share the results.</p>
                        )}
                    </div>
                    {result.sections.length > 1 && (
                        <ul className="mt-5 divide-y divide-slate-100 border-t border-slate-100 text-sm dark:divide-white/[0.06] dark:border-white/[0.06]">
                            {result.sections.map((s, i) => <li key={i} className="flex justify-between py-2"><span>{s.title}</span><span className="tabular-nums">{s.score} / {s.max}</span></li>)}
                        </ul>
                    )}
                </Panel>
                <p className="text-center"><Link href="/school/student/cbt" className="text-sm text-indigo-600 hover:underline dark:text-indigo-400">Back to my tests</Link></p>
            </div>
        </AppLayout>
    );
}
