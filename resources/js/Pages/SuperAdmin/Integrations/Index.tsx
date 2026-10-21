import { useState } from 'react';
import { router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { PageHeader, Panel, Pill } from '@/components/app/kit';
import { PlugZap } from 'lucide-react';

interface Integration {
    key: string; label: string; about: string; state: 'ready' | 'missing' | 'warn'; detail: string; testable: boolean;
    last_ok: string | null; last_tested: string | null; last_result: boolean | null; last_message: string | null;
}

const stateText = { ready: 'Set up', missing: 'Not set up', warn: 'Needs a look' } as const;
const stateTone = { ready: 'good', missing: 'neutral', warn: 'warn' } as const;
const when = (iso: string | null) => (iso ? new Date(iso).toLocaleString() : null);

/** Each outside service: is it set up, did the last test work, and a button to test it now */
export default function IntegrationsIndex({ integrations }: { integrations: Integration[] }) {
    const [busy, setBusy] = useState<string | null>(null);

    function test(key: string) {
        router.post(`/super-admin/integrations/${key}/test`, {}, { preserveScroll: true, onStart: () => setBusy(key), onFinish: () => setBusy(null) });
    }

    return (
        <AppLayout title="Integrations status">
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader title="Integrations status" description="The outside services the app relies on. A test only asks the service a harmless question; it never changes anything there and never sends an email or text." />

                <div className="grid gap-4 md:grid-cols-2">
                    {integrations.map((i) => (
                        <Panel key={i.key} title={i.label} description={i.about} action={<Pill tone={stateTone[i.state]}>{stateText[i.state]}</Pill>}>
                            <p className="text-sm text-slate-600 dark:text-slate-300">{i.detail}</p>

                            {i.last_tested ? (
                                <div className="mt-3 rounded-lg bg-slate-50 p-3 text-[13px] dark:bg-white/[0.04]">
                                    <p className="flex flex-wrap items-center gap-2 text-slate-700 dark:text-slate-200">
                                        <Pill tone={i.last_result ? 'good' : 'bad'}>{i.last_result ? 'Last test passed' : 'Last test failed'}</Pill>
                                        <span className="text-xs text-slate-500">{when(i.last_tested)}</span>
                                    </p>
                                    {i.last_message && <p className="mt-1.5 break-words text-slate-600 dark:text-slate-300">{i.last_message}</p>}
                                    {!i.last_result && i.last_ok && <p className="mt-1 text-xs text-slate-500">Last worked: {when(i.last_ok)}</p>}
                                </div>
                            ) : (
                                i.testable && <p className="mt-3 text-[13px] text-slate-500">Not tested yet.</p>
                            )}

                            {i.testable && (
                                <div className="mt-4">
                                    <Button size="sm" variant="outline" disabled={busy !== null} onClick={() => test(i.key)}>
                                        <PlugZap /> {busy === i.key ? 'Testing…' : 'Test connection'}
                                    </Button>
                                </div>
                            )}
                        </Panel>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
