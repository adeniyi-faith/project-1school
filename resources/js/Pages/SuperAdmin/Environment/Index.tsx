import AppLayout from '@/Layouts/AppLayout';
import { PageHeader, Panel, Pill } from '@/components/app/kit';

interface Row { label: string; env: string; value: string; state: 'set' | 'empty' | 'example' }
interface Group { title: string; rows: Row[] }

/** The app's important settings, read-only, with secrets hidden */
export default function EnvironmentIndex({ groups }: { groups: Group[] }) {
    return (
        <AppLayout title="Environment">
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader title="Environment" description="The settings the app is running with. Secrets show only whether they are set and their last four characters. To change one, edit the server's .env file; nothing here can be edited." />

                <div className="grid gap-4 lg:grid-cols-2">
                    {groups.map((g) => (
                        <Panel key={g.title} title={g.title}>
                            <dl className="divide-y divide-slate-100 text-sm dark:divide-white/[0.06]">
                                {g.rows.map((r) => (
                                    <div key={r.env} className="py-2">
                                        <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-0.5">
                                            <dt className="text-slate-500 dark:text-slate-400">{r.label}</dt>
                                            <dd className="min-w-0 break-all text-right font-medium text-slate-900 dark:text-white">
                                                {r.state === 'empty' ? <span className="font-normal text-slate-400">{r.value}</span> : r.value}
                                            </dd>
                                        </div>
                                        <div className="mt-0.5 flex items-center justify-between gap-2">
                                            <code className="text-[11px] text-slate-400">{r.env}</code>
                                            {r.state === 'example' && <Pill tone="warn">Still an example value</Pill>}
                                        </div>
                                    </div>
                                ))}
                            </dl>
                        </Panel>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
