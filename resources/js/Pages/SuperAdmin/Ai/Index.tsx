import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { PageHeader, Panel, Pill, StatStrip } from '@/components/app/kit';
import { naira } from '@/lib/format';
import { KeyRound, Pencil, PlugZap, Sparkles } from 'lucide-react';

type Tier = 'writing' | 'quick';

interface Settings {
    enabled: boolean; has_key: boolean; key_hint: string | null;
    models: Record<Tier, string>; prices: Record<Tier, { in: number; out: number }>;
    image_model: string; image_price: number; image_cap: number;
    default_limit: number; naira_per_dollar: number;
}

interface SchoolRow { id: number; name: string; status: string; blocked: boolean; school_off: boolean; limit: number; custom_limit: boolean; used: number; requests: number }

interface Props {
    settings: Settings;
    knownModels: string[];
    prices: Record<string, [number, number]>;
    schools: SchoolRow[];
    month: string;
}

/** The platform owner's AI page: the Gemini key, the two models, prices, and each school's monthly allowance */
export default function AiIndex({ settings, knownModels, prices, schools, month }: Props) {
    const [editing, setEditing] = useState<SchoolRow | null>(null);
    const form = useForm({
        enabled: settings.enabled,
        api_key: '',
        remove_key: false,
        model_writing: settings.models.writing,
        model_quick: settings.models.quick,
        price_writing_in: String(settings.prices.writing.in),
        price_writing_out: String(settings.prices.writing.out),
        price_quick_in: String(settings.prices.quick.in),
        price_quick_out: String(settings.prices.quick.out),
        model_image: settings.image_model,
        price_image: String(settings.image_price),
        image_cap: String(settings.image_cap),
        default_limit: String(settings.default_limit),
        naira_per_dollar: String(settings.naira_per_dollar),
    });
    const d = form.data;
    const errors = form.errors as Record<string, string | undefined>;
    const used = schools.reduce((n, s) => n + s.used, 0);
    const requests = schools.reduce((n, s) => n + s.requests, 0);

    // Picking a known model fills in Google's price for it
    function setModel(tier: Tier, model: string) {
        const known = prices[model];
        form.setData({ ...d, [`model_${tier}`]: model, ...(known ? { [`price_${tier}_in`]: String(known[0]), [`price_${tier}_out`]: String(known[1]) } : {}) });
    }

    function save(e: React.FormEvent) {
        e.preventDefault();
        form.post('/super-admin/ai', { preserveScroll: true, onSuccess: () => form.setData('api_key', '') });
    }

    const err = (k: string) => errors[k] && <p className="text-xs text-red-500">{errors[k]}</p>;
    const modelBox = (tier: Tier, label: string, hint: string) => (
        <div className="space-y-3 rounded-lg border border-slate-200 p-3 dark:border-white/10">
            <div>
                <Label htmlFor={`model_${tier}`}>{label}</Label>
                <p className="text-xs text-slate-500">{hint}</p>
            </div>
            <Input id={`model_${tier}`} list="known-models" value={d[`model_${tier}`]} onChange={e => setModel(tier, e.target.value.trim())} />
            {err(`model_${tier}`)}
            <div className="grid grid-cols-2 gap-2">
                <div className="space-y-1">
                    <Label htmlFor={`price_${tier}_in`} className="text-xs">$ per million tokens sent</Label>
                    <Input id={`price_${tier}_in`} type="number" min={0} step="0.01" value={d[`price_${tier}_in`]} onChange={e => form.setData(`price_${tier}_in`, e.target.value)} />
                </div>
                <div className="space-y-1">
                    <Label htmlFor={`price_${tier}_out`} className="text-xs">$ per million tokens received</Label>
                    <Input id={`price_${tier}_out`} type="number" min={0} step="0.01" value={d[`price_${tier}_out`]} onChange={e => form.setData(`price_${tier}_out`, e.target.value)} />
                </div>
            </div>
        </div>
    );

    return (
        <AppLayout title="AI (Gemini)">
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader title="AI (Gemini)" description="One Google Gemini key for the whole platform. You choose the models and how much each school may use a month; each school can also switch AI off for itself." />

                <StatStrip items={[
                    { label: 'AI on the platform', value: settings.enabled && settings.has_key ? 'On' : 'Off', tone: settings.enabled && settings.has_key ? 'good' : 'warn' },
                    { label: `Spent in ${month}`, value: naira(used), hint: `${requests} request${requests === 1 ? '' : 's'}` },
                    { label: 'Schools using it', value: schools.filter(s => s.requests > 0).length },
                    { label: 'Default allowance', value: naira(settings.default_limit), hint: 'per school, per month' },
                ]} />

                <form onSubmit={save} className="space-y-6">
                    <Panel title="Key and switch" description="Make the key at aistudio.google.com on a project with billing turned on. Keys on Google's free plan let Google use what schools send to improve its products, so use a paid key.">
                        <div className="space-y-4">
                            <label className="flex items-center gap-2 text-sm font-medium"><Checkbox checked={d.enabled} onCheckedChange={v => form.setData('enabled', !!v)} /> AI is available to schools</label>
                            <div className="space-y-1.5">
                                <Label htmlFor="api_key">Gemini API key</Label>
                                <div className="flex flex-wrap items-center gap-2">
                                    <Input id="api_key" type="password" autoComplete="off" value={d.api_key} onChange={e => form.setData('api_key', e.target.value)} placeholder={settings.has_key ? `Saved (${settings.key_hint}). Type a new one to replace it.` : 'Paste the key'} className="max-w-md" />
                                    {settings.has_key && <Pill tone="good"><KeyRound className="size-3" /> Saved {settings.key_hint}</Pill>}
                                </div>
                                <p className="text-xs text-slate-500">The key is stored encrypted and is never shown again. A new key is tried with Google before it is saved.</p>
                                {err('api_key')}
                                {settings.has_key && !d.api_key && (
                                    <label className="flex items-center gap-2 text-sm text-red-700 dark:text-red-400"><Checkbox checked={d.remove_key} onCheckedChange={v => form.setData('remove_key', !!v)} /> Remove the saved key (AI stops everywhere)</label>
                                )}
                            </div>
                        </div>
                    </Panel>

                    <Panel title="Models and prices" description="Google's prices, used to work out what each school has spent. Check them at ai.google.dev/gemini-api/docs/pricing when Google changes them.">
                        <datalist id="known-models">{knownModels.map(m => <option key={m} value={m} />)}</datalist>
                        <div className="grid gap-4 md:grid-cols-2">
                            {modelBox('writing', 'Model for long writing', 'Lesson notes, exam questions and other long work.')}
                            {modelBox('quick', 'Model for short jobs', 'Report card comments and other short work.')}
                        </div>
                        <div className="mt-4 grid gap-4 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="default_limit">Monthly allowance per school (₦)</Label>
                                <Input id="default_limit" type="number" min={0} step="100" value={d.default_limit} onChange={e => form.setData('default_limit', e.target.value)} />
                                <p className="text-xs text-slate-500">When a school reaches it, AI stops for that school until the 1st. Give a school its own allowance in the list below.</p>
                                {err('default_limit')}
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="naira_per_dollar">Naira to one US dollar</Label>
                                <Input id="naira_per_dollar" type="number" min={1} step="1" value={d.naira_per_dollar} onChange={e => form.setData('naira_per_dollar', e.target.value)} />
                                <p className="text-xs text-slate-500">Google charges in dollars. This turns each request's cost into naira.</p>
                                {err('naira_per_dollar')}
                            </div>
                        </div>
                    </Panel>

                    <Panel title="Pictures" description="The model that makes teaching pictures, what Google charges for one, and how many pictures a school may make a month. Teachers pick a picture style; they never see any of this.">
                        <div className="grid gap-4 sm:grid-cols-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="model_image">Picture model</Label>
                                <Input id="model_image" value={d.model_image} onChange={e => form.setData('model_image', e.target.value.trim())} />
                                <p className="text-xs text-slate-500">Type it as Google writes it. Try one picture after changing it.</p>
                                {err('model_image')}
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="price_image">$ for one picture</Label>
                                <Input id="price_image" type="number" min={0} step="0.001" value={d.price_image} onChange={e => form.setData('price_image', e.target.value)} />
                                {err('price_image')}
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="image_cap">Pictures a school may make a month</Label>
                                <Input id="image_cap" type="number" min={0} step="1" value={d.image_cap} onChange={e => form.setData('image_cap', e.target.value)} />
                                {err('image_cap')}
                            </div>
                        </div>
                    </Panel>

                    <div className="flex flex-wrap justify-end gap-2">
                        {settings.has_key && (
                            <Button type="button" variant="outline" onClick={() => router.post('/super-admin/ai/check', {}, { preserveScroll: true })}><PlugZap className="size-4" /> Check the key</Button>
                        )}
                        <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700"><Sparkles className="size-4" /> Save AI settings</Button>
                    </div>
                </form>

                <Panel title="Schools" description={`What each school has spent in ${month}.`} flush>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="text-left text-xs text-slate-500">
                                <tr className="border-b border-slate-100 dark:border-white/[0.06]">
                                    <th className="px-5 py-2 font-medium">School</th>
                                    <th className="px-3 py-2 font-medium">AI</th>
                                    <th className="px-3 py-2 text-right font-medium">Spent</th>
                                    <th className="px-3 py-2 text-right font-medium">Allowance</th>
                                    <th className="px-3 py-2 text-right font-medium">Requests</th>
                                    <th className="px-5 py-2" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                                {schools.map(s => (
                                    <tr key={s.id}>
                                        <td className="px-5 py-2.5 font-medium text-slate-900 dark:text-white">{s.name}</td>
                                        <td className="px-3 py-2.5">
                                            {s.blocked ? <Pill tone="bad">Off (by you)</Pill> : s.school_off ? <Pill tone="neutral">Off (by school)</Pill> : s.used >= s.limit ? <Pill tone="warn">Allowance used</Pill> : <Pill tone="good">On</Pill>}
                                        </td>
                                        <td className="px-3 py-2.5 text-right tabular-nums">{naira(s.used)}</td>
                                        <td className="px-3 py-2.5 text-right tabular-nums">{naira(s.limit)}{s.custom_limit && <span className="ml-1 text-xs text-slate-500">(own)</span>}</td>
                                        <td className="px-3 py-2.5 text-right tabular-nums">{s.requests}</td>
                                        <td className="px-5 py-2.5 text-right"><Button variant="ghost" size="sm" onClick={() => setEditing(s)} aria-label={`Change AI for ${s.name}`}><Pencil className="size-4" /></Button></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Panel>
            </div>

            {editing && <SchoolDialog school={editing} defaultLimit={settings.default_limit} onClose={() => setEditing(null)} />}
        </AppLayout>
    );
}

function SchoolDialog({ school, defaultLimit, onClose }: { school: SchoolRow; defaultLimit: number; onClose: () => void }) {
    const form = useForm({ blocked: school.blocked, limit: school.custom_limit ? String(school.limit) : '' });
    return (
        <Dialog open onOpenChange={o => !o && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader><DialogTitle>AI for {school.name}</DialogTitle></DialogHeader>
                <form onSubmit={e => { e.preventDefault(); form.transform(x => ({ ...x, limit: x.limit === '' ? null : x.limit })); form.post(`/super-admin/ai/schools/${school.id}`, { preserveScroll: true, onSuccess: onClose }); }} className="space-y-4">
                    <label className="flex items-center gap-2 text-sm"><Checkbox checked={form.data.blocked} onCheckedChange={v => form.setData('blocked', !!v)} /> Switch AI off for this school</label>
                    <div className="space-y-1.5">
                        <Label htmlFor="school-limit">Monthly allowance (₦)</Label>
                        <Input id="school-limit" type="number" min={0} step="100" value={form.data.limit} onChange={e => form.setData('limit', e.target.value)} placeholder={`Default: ${naira(defaultLimit)}`} />
                        <p className="text-xs text-slate-500">Leave empty to use the default.</p>
                        {form.errors.limit && <p className="text-xs text-red-500">{form.errors.limit}</p>}
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onClose}>Cancel</Button>
                        <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Save</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
