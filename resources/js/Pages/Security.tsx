import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PageHeader, Panel, Pill } from '@/components/app/kit';
import { ShieldCheck } from 'lucide-react';

interface Props {
    enabled: boolean;
    recovery_left: number;
    setup: { secret: string; qr: string | null } | null;
    new_codes: string[] | null;
}

export default function Security({ enabled, recovery_left, setup, new_codes }: Props) {
    const confirm = useForm({ code: '' });
    const off = useForm({ password: '' });
    const again = useForm({ password: '' });

    return (
        <AppLayout title="Two-step sign-in">
            <div className="max-w-2xl space-y-6">
                <Link href="/profile" className="text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">← My profile</Link>
                <PageHeader title="Two-step sign-in" description="Besides your password, you type a 6-digit code from an app on your phone. Even if someone learns your password, they cannot get in without your phone." />

                {new_codes && (
                    <Panel title="Save your recovery codes now">
                        <p className="text-sm text-slate-600 dark:text-slate-300">If you lose your phone, each of these codes lets you in once. They are shown only this time, so copy them somewhere safe (not on the same phone).</p>
                        <ul className="mt-3 grid grid-cols-2 gap-2 font-mono text-sm sm:grid-cols-4">
                            {new_codes.map(c => <li key={c} className="rounded-md bg-slate-100 px-2 py-1.5 text-center dark:bg-white/[0.06]">{c}</li>)}
                        </ul>
                    </Panel>
                )}

                {enabled ? (
                    <>
                        <Panel title="Status" action={<Pill tone="good">On</Pill>}>
                            <p className="text-sm text-slate-600 dark:text-slate-300">Two-step sign-in is on. You have {recovery_left} recovery code{recovery_left === 1 ? '' : 's'} left.</p>
                        </Panel>
                        <Panel title="Get new recovery codes" description="The old ones stop working.">
                            <form onSubmit={e => { e.preventDefault(); again.post('/security/recovery-codes', { preserveScroll: true, onSuccess: () => again.reset() }); }} className="flex flex-wrap items-end gap-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="again-password">Your password</Label>
                                    <Input id="again-password" type="password" autoComplete="current-password" value={again.data.password} onChange={e => again.setData('password', e.target.value)} />
                                    {again.errors.password && <p className="text-xs text-red-500">{again.errors.password}</p>}
                                </div>
                                <Button type="submit" variant="outline" disabled={again.processing || !again.data.password}>Make new codes</Button>
                            </form>
                        </Panel>
                        <Panel title="Turn off" description="Your account goes back to password only.">
                            <form onSubmit={e => { e.preventDefault(); off.delete('/security', { preserveScroll: true, onSuccess: () => off.reset() }); }} className="flex flex-wrap items-end gap-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="off-password">Your password</Label>
                                    <Input id="off-password" type="password" autoComplete="current-password" value={off.data.password} onChange={e => off.setData('password', e.target.value)} />
                                    {off.errors.password && <p className="text-xs text-red-500">{off.errors.password}</p>}
                                </div>
                                <Button type="submit" variant="outline" className="text-red-600" disabled={off.processing || !off.data.password}>Turn off</Button>
                            </form>
                        </Panel>
                    </>
                ) : setup ? (
                    <Panel title="Set it up" description="Takes a minute. You need an authenticator app, such as Google Authenticator or Microsoft Authenticator, on your phone.">
                        <ol className="list-decimal space-y-4 pl-5 text-sm text-slate-700 dark:text-slate-200">
                            <li>
                                In the app, choose “add account” and scan this picture.
                                {setup.qr && <img src={setup.qr} alt="QR code to scan with your authenticator app" className="mt-2 size-44 rounded-lg border border-slate-200 bg-white p-2 dark:border-white/10" />}
                                <p className="mt-2 text-xs text-slate-500">Cannot scan? Type this key into the app instead: <span className="select-all font-mono text-slate-800 dark:text-slate-100">{setup.secret}</span></p>
                            </li>
                            <li>
                                Type the 6-digit code the app now shows.
                                <form onSubmit={e => { e.preventDefault(); confirm.post('/security/confirm', { preserveScroll: true, onSuccess: () => confirm.reset() }); }} className="mt-2 flex flex-wrap items-start gap-2">
                                    <div className="space-y-1">
                                        <Input aria-label="6-digit code" inputMode="numeric" autoComplete="one-time-code" maxLength={7} placeholder="123456" className="w-40 font-mono" value={confirm.data.code} onChange={e => confirm.setData('code', e.target.value)} />
                                        {confirm.errors.code && <p className="text-xs text-red-500">{confirm.errors.code}</p>}
                                    </div>
                                    <Button type="submit" disabled={confirm.processing || !confirm.data.code} className="bg-indigo-600 text-white hover:bg-indigo-700">Turn on</Button>
                                </form>
                            </li>
                        </ol>
                    </Panel>
                ) : (
                    <Panel title="Status" action={<Pill>Off</Pill>}>
                        <p className="text-sm text-slate-600 dark:text-slate-300">Two-step sign-in is off. We recommend it for administrators, principals and anyone who handles fees.</p>
                        <Button className="mt-3 bg-indigo-600 text-white hover:bg-indigo-700" onClick={() => router.post('/security/start', {}, { preserveScroll: true })}>
                            <ShieldCheck className="size-4" /> Set it up
                        </Button>
                    </Panel>
                )}
            </div>
        </AppLayout>
    );
}
