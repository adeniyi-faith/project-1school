import { useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface Props {
    settings: { provider: string; from_email: string; brevo_key: string | null; daily_limit: string; access: { mode: 'all' | 'selected'; plans: number[]; schools: number[] } };
    mailboxAddress: string; defaults: { brevo: number; mailbox: number };
    plans: { id: number; name: string }[]; schools: { id: number; name: string }[];
}

const card = 'rounded-xl border border-slate-200 p-4 dark:border-white/10';

export default function NewsletterIndex({ settings, mailboxAddress, defaults, plans, schools }: Props) {
    const errors = usePage().props.errors as Record<string, string>;
    const form = useForm({ provider: settings.provider, from_email: settings.from_email, brevo_key: '', daily_limit: settings.daily_limit, access: settings.access });
    const [find, setFind] = useState('');
    const toggle = (field: 'plans' | 'schools', id: number) => form.setData('access', { ...form.data.access, [field]: form.data.access[field].includes(id) ? form.data.access[field].filter(x => x !== id) : [...form.data.access[field], id] });
    const shown = schools.filter(s => s.name.toLowerCase().includes(find.toLowerCase()));
    const brevo = form.data.provider === 'brevo';

    return (
        <AppLayout title="Newsletter Email">
            <div className="mx-auto max-w-3xl space-y-5 pb-24">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Newsletter Email</h1>
                    <p className="mt-0.5 text-sm text-slate-500">The email service you offer schools for their newsletter. A school can also use its own account instead.</p>
                </div>

                <section className={card}>
                    <h2 className="font-semibold">Which service do you provide?</h2>
                    <div className="mt-3 space-y-2 text-sm">
                        <label className={`flex gap-3 rounded-lg border p-3 ${brevo ? 'border-indigo-500' : 'border-slate-200 dark:border-white/10'}`}>
                            <input type="radio" checked={brevo} onChange={() => form.setData('provider', 'brevo')} />
                            <span><strong>Brevo</strong> <span className="rounded bg-emerald-100 px-1.5 text-xs text-emerald-800">Recommended</span><span className="block text-xs text-slate-500">Built for newsletters, so emails reach inboxes more reliably. Default limit {defaults.brevo} emails a day per school.</span></span>
                        </label>
                        <label className={`flex gap-3 rounded-lg border p-3 ${!brevo ? 'border-indigo-500' : 'border-slate-200 dark:border-white/10'}`}>
                            <input type="radio" checked={!brevo} onChange={() => form.setData('provider', 'mailbox')} />
                            <span><strong>The system's cPanel mailbox</strong> ({mailboxAddress || 'none set'})<span className="block text-xs text-slate-500">No account needed, but shared hosting limits how much it can send and large sends may land in spam. Default limit {defaults.mailbox} emails a day per school.</span></span>
                        </label>
                    </div>
                    {brevo && (
                        <div className="mt-3 space-y-3 text-sm">
                            <label className="block space-y-1"><span className="font-medium">Brevo API key</span><Input type="password" autoComplete="off" placeholder={settings.brevo_key ? `Saved (${settings.brevo_key}). Type to replace.` : 'Paste the key'} value={form.data.brevo_key} onChange={e => form.setData('brevo_key', e.target.value)} />{errors.brevo_key && <span className="text-xs text-red-600">{errors.brevo_key}</span>}</label>
                        </div>
                    )}
                    <div className="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <label className="block space-y-1"><span className="font-medium">Send from this address</span><Input type="email" placeholder={mailboxAddress} value={form.data.from_email} onChange={e => form.setData('from_email', e.target.value)} /><span className="block text-xs text-slate-500">{brevo ? 'Must be a sender you verified in Brevo.' : 'Empty uses the system address. '} Each school's name is shown as the sender name.</span>{errors.from_email && <span className="text-xs text-red-600">{errors.from_email}</span>}</label>
                        <label className="block space-y-1"><span className="font-medium">Daily limit for each school</span><Input type="number" min={1} placeholder={String(brevo ? defaults.brevo : defaults.mailbox)} value={form.data.daily_limit} onChange={e => form.setData('daily_limit', e.target.value)} /></label>
                    </div>
                    {errors.provider && <p role="alert" className="mt-2 text-sm text-red-600">{errors.provider}</p>}
                </section>

                <section className={card}>
                    <h2 className="font-semibold">Who may use it?</h2>
                    <div className="mt-3 space-y-2 text-sm">
                        <label className="flex gap-2"><input type="radio" checked={form.data.access.mode === 'all'} onChange={() => form.setData('access', { ...form.data.access, mode: 'all' })} /> <span>All schools</span></label>
                        <label className="flex gap-2"><input type="radio" checked={form.data.access.mode === 'selected'} onChange={() => form.setData('access', { ...form.data.access, mode: 'selected' })} /> <span>Only the schools and plans I choose</span></label>
                    </div>
                    {form.data.access.mode === 'selected' && (
                        <div className="mt-3 space-y-4 text-sm">
                            <div>
                                <p className="font-medium">Plans</p>
                                <p className="text-xs text-slate-500">Every school on a ticked plan gets it. Add it to a paid plan here whenever you like.</p>
                                <div className="mt-1.5 flex flex-wrap gap-2">{plans.length === 0 && <span className="text-xs text-slate-500">No plans yet.</span>}{plans.map(p => (
                                    <label key={p.id} className="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-1.5 dark:border-white/10"><input type="checkbox" checked={form.data.access.plans.includes(p.id)} onChange={() => toggle('plans', p.id)} /> {p.name}</label>
                                ))}</div>
                            </div>
                            <div>
                                <p className="font-medium">Individual schools</p>
                                <Input className="mt-1.5 max-w-xs" placeholder="Find a school" aria-label="Find a school" value={find} onChange={e => setFind(e.target.value)} />
                                <ul className="mt-2 max-h-56 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200 dark:divide-white/5 dark:border-white/10">
                                    {shown.map(s => <li key={s.id}><label className="flex items-center gap-2 px-3 py-2"><input type="checkbox" checked={form.data.access.schools.includes(s.id)} onChange={() => toggle('schools', s.id)} /> {s.name}</label></li>)}
                                    {shown.length === 0 && <li className="px-3 py-2 text-xs text-slate-500">No school matches.</li>}
                                </ul>
                                <p className="mt-1 text-xs text-slate-500">{form.data.access.schools.length} chosen</p>
                            </div>
                        </div>
                    )}
                    <p className="mt-3 text-xs text-slate-500">Schools that are not allowed can still use their own Brevo or mailbox account.</p>
                </section>

                <Button disabled={form.processing} onClick={() => form.post('/super-admin/newsletter', { preserveScroll: true, onSuccess: () => form.setData('brevo_key', '') })} className="bg-indigo-600 text-white hover:bg-indigo-700">{form.processing ? 'Checking…' : brevo ? 'Save and check the key' : 'Save'}</Button>
            </div>
        </AppLayout>
    );
}
