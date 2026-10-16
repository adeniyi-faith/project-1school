import { router, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { Pill } from '@/components/app/kit';
import { Switch, WebsiteLayout } from '@/components/website/kit';
import type { WebsiteShell } from '@/Types/website';

interface Extension { key: string; name: string; description: string; needs: string | null; ready: boolean; hidden: boolean; on: boolean; setup: string | null }
interface Props { shell: WebsiteShell; extensions: Extension[] }

export default function Extensions({ shell, extensions }: Props) {
    const errors = usePage().props.errors as Record<string, string>;
    const toggle = (e: Extension, on: boolean) => router.post(`/school/website/extensions/${e.key}`, { on }, { preserveScroll: true });

    return (
        <WebsiteLayout shell={shell} title="Extensions" crumb={[{ label: 'Extensions' }]} description="Extra features for your website. Switch on only what you need. Switching one off hides it from visitors, and nothing you already wrote is lost.">
            {errors.extension && <p role="alert" className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{errors.extension}</p>}
            <div className="grid gap-3 sm:grid-cols-2">
                {extensions.map(e => (
                    <section key={e.key} className={`flex flex-col gap-3 rounded-xl border p-4 ${e.ready && !e.hidden ? 'border-slate-200 dark:border-white/10' : 'border-dashed border-slate-300 opacity-80 dark:border-white/15'}`}>
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <h2 className="font-semibold text-slate-900 dark:text-white">{e.name}</h2>
                                <div className="mt-1 flex flex-wrap gap-1.5">
                                    {!e.ready && <Pill tone="neutral">Coming soon</Pill>}
                                    {e.ready && e.hidden && <Pill tone="warn">Not in your plan</Pill>}
                                    {e.ready && !e.hidden && e.on && <Pill tone="good">On</Pill>}
                                </div>
                            </div>
                            {e.ready && !e.hidden && shell.canEdit && <Switch label={`${e.name} on or off`} on={e.on} onChange={v => toggle(e, v)} />}
                        </div>
                        <p className="text-sm text-slate-600 dark:text-slate-300">{e.description}</p>
                        {e.needs && <p className="text-xs text-slate-500">Needs: {e.needs}</p>}
                        {e.on && e.setup && <Link href={e.setup} className="mt-auto inline-flex h-9 w-fit items-center rounded-lg bg-indigo-600 px-3 text-sm font-medium text-white hover:bg-indigo-700">Open {e.name}</Link>}
                    </section>
                ))}
            </div>
        </WebsiteLayout>
    );
}
