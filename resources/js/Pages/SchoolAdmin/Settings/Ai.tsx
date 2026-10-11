import { useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { EmptyState, PageHeader, Panel, Pill } from '@/components/app/kit';
import { naira } from '@/lib/format';
import { Sparkles } from 'lucide-react';

interface Props {
    state: { platform_ready: boolean; blocked: boolean; school_off: boolean; tools_off: string[]; limit: number; used: number; reason: string | null };
    features: { key: string; label: string; on: boolean }[];
    byFeature: { label: string; requests: number; cost: number }[];
    recent: { id: number; when: string; who: string | null; feature: string; cost: number; ok: boolean; error: string | null }[];
    canEdit: boolean;
    month: string;
}

/** A school's AI page: switch the AI (or single tools) off, and see what has been used this month */
export default function AiSettings({ state, features, byFeature, recent, canEdit, month }: Props) {
    const form = useForm({ on: !state.school_off, tools_off: state.tools_off });
    const d = form.data;
    const percent = state.limit > 0 ? Math.min(100, Math.round((state.used / state.limit) * 100)) : 100;

    function toggleTool(key: string, on: boolean) {
        form.setData('tools_off', on ? d.tools_off.filter(k => k !== key) : [...d.tools_off, key]);
    }

    return (
        <AppLayout breadcrumbs={[{ label: 'Settings', href: '/school/settings' }, { label: 'AI Assistant' }]}>
            <div className="mx-auto max-w-3xl space-y-6 pb-24 md:pb-0">
                <PageHeader title="AI Assistant" description="The AI drafts work for your staff to check, such as report card comments. Students' names are not sent to it. Nothing it writes is used until a person saves it." />

                {!state.platform_ready ? (
                    <Panel><EmptyState icon={Sparkles} title="Not available yet" text="AI has not been set up on this platform yet." /></Panel>
                ) : (
                    <>
                        {state.blocked && <p className="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 dark:bg-red-500/10 dark:text-red-300">The platform owner has switched AI off for your school.</p>}
                        <Panel title={`This month's allowance (${month})`}>
                            <div className="space-y-2">
                                <div className="flex items-baseline justify-between text-sm">
                                    <span className="font-medium tabular-nums text-slate-900 dark:text-white">{naira(state.used)} used</span>
                                    <span className="tabular-nums text-slate-500">of {naira(state.limit)}</span>
                                </div>
                                <div className="h-2.5 overflow-hidden rounded-full bg-slate-100 dark:bg-white/[0.08]" role="progressbar" aria-valuenow={percent} aria-valuemin={0} aria-valuemax={100} aria-label="Allowance used">
                                    <div className={`h-full rounded-full ${percent >= 100 ? 'bg-red-500' : percent >= 80 ? 'bg-amber-500' : 'bg-violet-500'}`} style={{ width: `${percent}%` }} />
                                </div>
                                <p className="text-xs text-slate-500">When it is used up, AI stops until the 1st of next month. The platform owner sets the allowance.</p>
                            </div>
                            {byFeature.length > 0 && (
                                <ul className="mt-4 divide-y divide-slate-100 rounded-lg border border-slate-200 text-sm dark:divide-white/[0.06] dark:border-white/10">
                                    {byFeature.map(f => (
                                        <li key={f.label} className="flex items-center justify-between gap-3 px-3 py-2">
                                            <span>{f.label} <span className="text-slate-500">· {f.requests} request{f.requests === 1 ? '' : 's'}</span></span>
                                            <span className="font-medium tabular-nums">{naira(f.cost)}</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Panel>

                        <form onSubmit={e => { e.preventDefault(); form.post('/school/settings/ai', { preserveScroll: true }); }}>
                            <Panel title="Switches" description={canEdit ? undefined : 'Only an admin can change these.'}>
                                <div className="space-y-3 text-sm">
                                    <label className="flex items-center gap-2 font-medium"><Checkbox disabled={!canEdit} checked={d.on} onCheckedChange={v => form.setData('on', !!v)} /> Use AI in this school</label>
                                    <div className="space-y-2 border-l-2 border-slate-100 pl-4 dark:border-white/10">
                                        {features.map(f => (
                                            <label key={f.key} className={`flex items-center gap-2 ${d.on ? '' : 'opacity-50'}`}>
                                                <Checkbox disabled={!canEdit || !d.on} checked={!d.tools_off.includes(f.key)} onCheckedChange={v => toggleTool(f.key, !!v)} /> {f.label}
                                            </label>
                                        ))}
                                    </div>
                                </div>
                                {canEdit && <div className="mt-4 flex justify-end"><Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Save</Button></div>}
                            </Panel>
                        </form>

                        <Panel title="Recent requests" flush>
                            {recent.length === 0 ? (
                                <div className="px-5 pb-5 text-sm text-slate-500">Nothing yet this month.</div>
                            ) : (
                                <ul className="divide-y divide-slate-100 text-sm dark:divide-white/[0.06]">
                                    {recent.map(r => (
                                        <li key={r.id} className="flex flex-wrap items-center justify-between gap-2 px-5 py-2.5">
                                            <div className="min-w-0">
                                                <p className="font-medium text-slate-900 dark:text-white">{r.feature}</p>
                                                <p className="text-xs text-slate-500">{new Date(r.when).toLocaleString('en-GB', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' })}{r.who ? ` · ${r.who}` : ''}</p>
                                                {!r.ok && r.error && <p className="text-xs text-red-600">{r.error}</p>}
                                            </div>
                                            <div className="flex items-center gap-2">
                                                {!r.ok && <Pill tone="bad">Failed</Pill>}
                                                <span className="tabular-nums text-slate-600 dark:text-slate-300">{naira(r.cost, { kobo: true })}</span>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Panel>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
