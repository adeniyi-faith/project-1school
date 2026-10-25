import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Copy, KeyRound, Laptop, LogOut, ShieldCheck, Smartphone } from 'lucide-react';
import { toast } from 'sonner';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface SessionRow { id: string; device: string; ip: string | null; last_active: string; current: boolean }
interface Props {
    enrolled: boolean;
    required: boolean;
    setup: { secret: string; qr: string | null } | null;
    recoveryLeft: number;
    recoveryCodes: string[] | null;
    mustChangePassword: boolean;
    passwordChangedAt: string | null;
    sessionsListable: boolean;
    sessions: SessionRow[];
}

const when = (iso: string) => new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });

function Card({ title, children, tone }: { title: string; children: React.ReactNode; tone?: 'warn' }) {
    return (
        <section className={`rounded-xl border bg-white p-5 dark:bg-slate-900 ${tone === 'warn' ? 'border-amber-300 dark:border-amber-700' : 'border-slate-200 dark:border-slate-800'}`}>
            <h2 className="mb-3 text-base font-semibold text-slate-900 dark:text-white">{title}</h2>
            {children}
        </section>
    );
}

function Err({ text }: { text?: string }) {
    return text ? <p role="alert" className="mt-1 text-xs text-red-600">{text}</p> : null;
}

export default function SecurityIndex({ enrolled, required, setup, recoveryLeft, recoveryCodes, mustChangePassword, passwordChangedAt, sessionsListable, sessions }: Props) {
    const confirm = useForm({ code: '' });
    const again = useForm({ code: '' });
    const pw = useForm({ current_password: '', password: '', password_confirmation: '' });
    const [showPw, setShowPw] = useState(false);

    const copy = (text: string) => navigator.clipboard?.writeText(text).then(() => toast.success('Copied.'), () => toast.error('Could not copy.'));

    return (
        <AppLayout breadcrumbs={[{ label: 'My security' }]}>
            <Head title="My security" />

            <div className="mb-6">
                <h1 className="text-xl font-bold text-slate-900 dark:text-white">My security</h1>
                <p className="mt-0.5 max-w-2xl text-sm text-slate-500 dark:text-slate-400">
                    Because you work on the platform, your account can reach many schools. These steps keep it safe even if someone learns your password.
                </p>
            </div>

            <div className="grid max-w-3xl gap-4">
                {mustChangePassword && (
                    <Card title="Choose a new password first" tone="warn">
                        <p className="text-sm text-slate-600 dark:text-slate-300">The password you were given is only for your first sign-in. Pick your own below to carry on.</p>
                    </Card>
                )}

                {recoveryCodes && (
                    <Card title="Your recovery codes" tone="warn">
                        <p className="text-sm text-slate-600 dark:text-slate-300">
                            Save these now. They are shown only once. If you lose your phone, each code lets you sign in one time.
                        </p>
                        <div className="mt-3 grid grid-cols-2 gap-2 rounded-lg bg-slate-50 p-3 font-mono text-sm dark:bg-slate-800/60 sm:grid-cols-5">
                            {recoveryCodes.map((c) => <span key={c}>{c}</span>)}
                        </div>
                        <Button variant="outline" className="mt-3" onClick={() => copy(recoveryCodes.join('\n'))}><Copy className="mr-1 size-4" /> Copy all</Button>
                    </Card>
                )}

                <Card title="Authenticator app (second sign-in step)">
                    {enrolled ? (
                        <>
                            <p className="flex items-center gap-2 text-sm font-medium text-emerald-700 dark:text-emerald-400"><ShieldCheck className="size-4" /> On. You type a code from your app each time you sign in.</p>
                            <p className="mt-1 text-sm text-slate-500">You have {recoveryLeft} recovery {recoveryLeft === 1 ? 'code' : 'codes'} left.</p>
                            <form className="mt-4 flex flex-wrap items-start gap-2" onSubmit={(e) => { e.preventDefault(); again.post('/security/recovery-codes', { preserveScroll: true, onSuccess: () => again.reset() }); }}>
                                <div>
                                    <Input inputMode="numeric" autoComplete="one-time-code" placeholder="Code from your app" className="w-48" value={again.data.code} onChange={(e) => again.setData('code', e.target.value)} />
                                    <Err text={again.errors.code} />
                                </div>
                                <Button type="submit" variant="outline" disabled={again.processing || !again.data.code}><KeyRound className="mr-1 size-4" /> Make new recovery codes</Button>
                            </form>
                            {!required && (
                                <Button variant="ghost" className="mt-2 text-red-600" disabled={!again.data.code}
                                    onClick={() => again.delete('/security/two-factor', { preserveScroll: true })}>Switch off (uses the code above)</Button>
                            )}
                        </>
                    ) : (
                        setup && (
                            <>
                                <p className="text-sm text-slate-600 dark:text-slate-300">
                                    {required ? 'You must set this up before you can use the platform. ' : ''}
                                    Install an authenticator app (Google Authenticator, Microsoft Authenticator or Authy), then scan this picture.
                                </p>
                                <div className="mt-4 flex flex-col items-start gap-4 sm:flex-row">
                                    {setup.qr && <img src={setup.qr} alt="QR code to scan with your authenticator app" className="size-44 rounded-lg border border-slate-200 bg-white p-1" />}
                                    <div className="min-w-0 text-sm">
                                        <p className="text-slate-500">Cannot scan? Type this key into the app instead:</p>
                                        <p className="mt-1 break-all rounded bg-slate-50 px-2 py-1 font-mono dark:bg-slate-800/60">{setup.secret}</p>
                                    </div>
                                </div>
                                <form className="mt-4 flex flex-wrap items-start gap-2" onSubmit={(e) => { e.preventDefault(); confirm.post('/security/two-factor', { preserveScroll: true }); }}>
                                    <div>
                                        <Input inputMode="numeric" autoComplete="one-time-code" placeholder="6-digit code" className="w-40" value={confirm.data.code} onChange={(e) => confirm.setData('code', e.target.value)} />
                                        <Err text={confirm.errors.code} />
                                    </div>
                                    <Button type="submit" disabled={confirm.processing || !confirm.data.code}>Turn on</Button>
                                </form>
                            </>
                        )
                    )}
                </Card>

                <Card title="Password">
                    <p className="text-sm text-slate-500">{passwordChangedAt ? `Last changed ${when(passwordChangedAt)}.` : 'Choose a password only you know. Other devices are signed out when you change it.'}</p>
                    <form className="mt-3 grid max-w-sm gap-3" onSubmit={(e) => { e.preventDefault(); pw.put('/security/password', { preserveScroll: true, onSuccess: () => pw.reset() }); }}>
                        <div>
                            <Input type={showPw ? 'text' : 'password'} autoComplete="current-password" placeholder="Current password" value={pw.data.current_password} onChange={(e) => pw.setData('current_password', e.target.value)} />
                            <Err text={pw.errors.current_password} />
                        </div>
                        <div>
                            <Input type={showPw ? 'text' : 'password'} autoComplete="new-password" placeholder="New password (10 or more characters)" value={pw.data.password} onChange={(e) => pw.setData('password', e.target.value)} />
                            <Err text={pw.errors.password} />
                        </div>
                        <Input type={showPw ? 'text' : 'password'} autoComplete="new-password" placeholder="New password again" value={pw.data.password_confirmation} onChange={(e) => pw.setData('password_confirmation', e.target.value)} />
                        <label className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300"><input type="checkbox" checked={showPw} onChange={(e) => setShowPw(e.target.checked)} /> Show passwords</label>
                        <Button type="submit" disabled={pw.processing || !pw.data.current_password || !pw.data.password}>Change password</Button>
                    </form>
                </Card>

                <Card title="Where you are signed in">
                    {!sessionsListable ? (
                        <p className="text-sm text-slate-500">This site does not keep sessions in its database, so the list cannot be shown. You can still change your password to sign everything else out.</p>
                    ) : (
                        <>
                            <ul className="divide-y divide-slate-100 dark:divide-slate-800">
                                {sessions.map((s) => (
                                    <li key={s.id} className="flex items-center gap-3 py-3">
                                        {/Android|iPhone/.test(s.device) ? <Smartphone className="size-5 shrink-0 text-slate-400" /> : <Laptop className="size-5 shrink-0 text-slate-400" />}
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium text-slate-900 dark:text-white">{s.device}{s.current && <span className="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400">This device</span>}</p>
                                            <p className="truncate text-xs text-slate-500">{s.ip ?? 'Unknown address'} · last active {when(s.last_active)}</p>
                                        </div>
                                        {!s.current && <Button variant="outline" size="sm" onClick={() => router.delete(`/security/sessions/${s.id}`, { preserveScroll: true })}>Sign out</Button>}
                                    </li>
                                ))}
                            </ul>
                            {sessions.some((s) => !s.current) && (
                                <Button variant="outline" className="mt-3 text-red-600" onClick={() => router.delete('/security/sessions', { preserveScroll: true })}><LogOut className="mr-1 size-4" /> Sign out everywhere else</Button>
                            )}
                        </>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
