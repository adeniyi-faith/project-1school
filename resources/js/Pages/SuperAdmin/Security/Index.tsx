import { Head, router, useForm } from '@inertiajs/react';
import { Check, KeyRound, LogOut, ShieldAlert, ShieldCheck } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';

interface Person {
    id: number; name: string; email: string; status: string; last_login_at: string | null;
    two_factor: boolean; must_change_password: boolean; sessions: number;
}
interface Props { required: boolean; allowList: string; yourIp: string; sessionsListable: boolean; people: Person[] }

const when = (iso: string | null) => (iso ? new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' }) : 'Never');

export default function SecurityOwner({ required, allowList, yourIp, sessionsListable, people }: Props) {
    const form = useForm({ allow_list: allowList });
    const act = (p: Person, what: 'reset-two-factor' | 'sign-out' | 'force-password') =>
        router.post(`/super-admin/security/${p.id}/${what}`, {}, { preserveScroll: true });

    return (
        <AppLayout breadcrumbs={[{ label: 'Super Admin' }, { label: 'Staff Security' }]}>
            <Head title="Staff Security" />

            <div className="mb-6">
                <h1 className="text-xl font-bold text-slate-900 dark:text-white">Staff security</h1>
                <p className="mt-0.5 max-w-2xl text-sm text-slate-500 dark:text-slate-400">
                    See who has set up the second sign-in step, help someone who lost their phone, sign someone out at once, and choose which networks staff may use.
                </p>
            </div>

            {!required && (
                <div className="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200">
                    The authenticator app is <strong>not required</strong> on this site (<code>PLATFORM_REQUIRE_2FA=false</code>). People can still set it up, but are not forced to.
                </div>
            )}

            <section className="mb-6 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <h2 className="text-base font-semibold text-slate-900 dark:text-white">Networks staff may sign in from</h2>
                <p className="mt-1 max-w-2xl text-sm text-slate-500">
                    One address or range per line, such as <code>41.58.10.5</code> or <code>41.58.0.0/16</code>. Leave it empty to allow every network. This applies to platform staff only; you (the owner) are never blocked, so a typo cannot lock you out. Your address now is <strong>{yourIp}</strong>.
                </p>
                <form className="mt-3" onSubmit={(e) => { e.preventDefault(); form.put('/super-admin/security/networks', { preserveScroll: true }); }}>
                    <textarea
                        rows={4}
                        className="w-full max-w-xl rounded-lg border border-slate-300 bg-white p-3 font-mono text-sm dark:border-slate-700 dark:bg-slate-950"
                        placeholder="Empty = anywhere"
                        value={form.data.allow_list}
                        onChange={(e) => form.setData('allow_list', e.target.value)}
                    />
                    {form.errors.allow_list && <p role="alert" className="mt-1 text-sm text-red-600">{form.errors.allow_list}</p>}
                    <div className="mt-2"><Button type="submit" disabled={form.processing || !form.isDirty}>Save list</Button></div>
                </form>
            </section>

            <h2 className="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-200">Platform team ({people.length})</h2>
            {people.length === 0 ? (
                <p className="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-slate-700">No staff yet. Add them on the Platform Staff page.</p>
            ) : (
                <div className="space-y-3">
                    {people.map((p) => (
                        <div key={p.id} className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <p className="font-semibold text-slate-900 dark:text-white">{p.name} {p.status !== 'active' && <span className="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600 dark:bg-slate-800 dark:text-slate-300">Switched off</span>}</p>
                                    <p className="truncate text-xs text-slate-500">{p.email} · last signed in {when(p.last_login_at)}{sessionsListable ? ` · ${p.sessions} open ${p.sessions === 1 ? 'session' : 'sessions'}` : ''}</p>
                                    <div className="mt-2 flex flex-wrap gap-2 text-xs">
                                        {p.two_factor
                                            ? <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400"><ShieldCheck className="size-3" /> Authenticator on</span>
                                            : <span className="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-800 dark:bg-amber-950/50 dark:text-amber-300"><ShieldAlert className="size-3" /> No authenticator yet</span>}
                                        {p.must_change_password && <span className="rounded-full bg-sky-100 px-2 py-0.5 font-medium text-sky-700 dark:bg-sky-950/50 dark:text-sky-300">Must choose a new password</span>}
                                    </div>
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <Button size="sm" variant="outline" onClick={() => act(p, 'sign-out')}><LogOut className="mr-1 size-3.5" /> Sign out everywhere</Button>
                                    {!p.must_change_password && <Button size="sm" variant="outline" onClick={() => act(p, 'force-password')}><KeyRound className="mr-1 size-3.5" /> Make them pick a new password</Button>}
                                    {p.two_factor && <Button size="sm" variant="outline" className="text-red-600" onClick={() => { if (window.confirm(`Remove ${p.name}'s authenticator app? They will be signed out and must set it up again.`)) act(p, 'reset-two-factor'); }}><Check className="mr-1 size-3.5" /> Lost phone: reset</Button>}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
