import { useState } from 'react';
import { router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';

interface Plan { id: number; name: string }
interface Ext { key: string; name: string; ready: boolean; hidden_from: number[] }

export default function ExtensionsIndex({ plans, extensions }: { plans: Plan[]; extensions: Ext[] }) {
    const [hidden, setHidden] = useState<Record<string, number[]>>(Object.fromEntries(extensions.map(e => [e.key, e.hidden_from])));
    const flip = (key: string, id: number) => setHidden(h => ({ ...h, [key]: h[key].includes(id) ? h[key].filter(x => x !== id) : [...h[key], id] }));

    return (
        <AppLayout title="Site Extensions">
            <div className="mx-auto max-w-4xl space-y-5">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Site Extensions</h1>
                    <p className="mt-0.5 text-sm text-slate-500">Every extension is available on every plan unless you tick it here. A ticked box hides that extension from schools on that plan.</p>
                </div>
                {plans.length === 0 ? <p className="rounded-lg border border-dashed p-6 text-sm text-slate-500">There are no plans yet. Create one under Packages first.</p> : (
                    <div className="overflow-x-auto rounded-xl border border-slate-200 dark:border-white/10">
                        <table className="w-full min-w-[32rem] text-sm">
                            <thead><tr className="border-b border-slate-200 text-left dark:border-white/10"><th className="p-3 font-medium">Extension</th>{plans.map(p => <th key={p.id} className="p-3 font-medium">Hide from {p.name}</th>)}</tr></thead>
                            <tbody>{extensions.map(e => (
                                <tr key={e.key} className="border-b border-slate-100 last:border-0 dark:border-white/5">
                                    <td className="p-3">{e.name}{!e.ready && <span className="ml-2 text-xs text-slate-500">(coming soon)</span>}</td>
                                    {plans.map(p => <td key={p.id} className="p-3"><input type="checkbox" className="size-4" aria-label={`Hide ${e.name} from ${p.name}`} checked={hidden[e.key].includes(p.id)} onChange={() => flip(e.key, p.id)} /></td>)}
                                </tr>
                            ))}</tbody>
                        </table>
                    </div>
                )}
                {plans.length > 0 && <Button onClick={() => router.post('/super-admin/extensions', { hidden }, { preserveScroll: true })} className="bg-indigo-600 text-white hover:bg-indigo-700">Save</Button>}
            </div>
        </AppLayout>
    );
}
