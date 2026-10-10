import { Panel } from '@/components/app/kit';
import { ordinal } from '@/lib/format';
import type { TermReport } from '@/Types';

/** One published term result, as parents and students see it. */
export function TermReportCard({ report }: { report: TermReport }) {
    const behaviour = report.ratings.filter(r => r.domain === 'affective');
    const skills = report.ratings.filter(r => r.domain === 'psychomotor');

    return (
        <Panel
            title={report.term}
            description={report.class ?? undefined}
            action={<span className="font-medium text-slate-900 dark:text-white">{ordinal(report.position)} of {report.class_size}</span>}
            flush
        >
            <dl className="grid grid-cols-3 gap-px border-y border-slate-100 bg-slate-100 text-center dark:border-white/[0.06] dark:bg-white/[0.06]">
                {[
                    ['Average', `${report.average}%`],
                    ['Total', String(report.total_score)],
                    ['Class average', report.class_average !== null ? `${report.class_average}%` : '—'],
                ].map(([label, value]) => (
                    <div key={label} className="bg-white px-2 py-3 dark:bg-slate-900">
                        <dt className="text-xs text-slate-500 dark:text-slate-400">{label}</dt>
                        <dd className="mt-0.5 text-base font-semibold tabular-nums text-slate-900 dark:text-white">{value}</dd>
                    </div>
                ))}
            </dl>
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="text-left text-xs text-slate-500 dark:text-slate-400">
                            <th className="px-5 py-2 font-medium">Subject</th>
                            <th className="px-2 py-2 text-right font-medium">Score</th>
                            <th className="px-2 py-2 text-right font-medium">Grade</th>
                            <th className="px-2 py-2 font-medium">Remark</th>
                            <th className="px-5 py-2 text-right font-medium">Position</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                        {report.subjects.map((s, i) => (
                            <tr key={i}>
                                <td className="px-5 py-2 text-slate-800 dark:text-slate-200">{s.subject ?? '—'}</td>
                                <td className="px-2 py-2 text-right tabular-nums">{s.total}</td>
                                <td className="px-2 py-2 text-right font-semibold">{s.grade ?? '—'}</td>
                                <td className="px-2 py-2 text-slate-500 dark:text-slate-400">{s.remarks ?? ''}</td>
                                <td className="px-5 py-2 text-right tabular-nums text-slate-600 dark:text-slate-400">{ordinal(s.position)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            {(behaviour.length > 0 || skills.length > 0) && (
                <div className="grid gap-4 border-t border-slate-100 px-5 py-4 sm:grid-cols-2 dark:border-white/[0.06]">
                    {[['Behaviour', behaviour], ['Skills', skills]].map(([title, rows]) => (rows as TermReport['ratings']).length > 0 && (
                        <div key={title as string}>
                            <p className="mb-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">{title as string}</p>
                            <ul className="space-y-1 text-sm">
                                {(rows as TermReport['ratings']).map((r, i) => (
                                    <li key={i} className="flex justify-between gap-2">
                                        <span className="text-slate-700 dark:text-slate-300">{r.name}</span>
                                        <span className="text-slate-500 dark:text-slate-400">{r.label}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>
            )}
        </Panel>
    );
}
