import { useState } from 'react';
import { useForm } from '@inertiajs/react';

import AuthShell, { Field, headingClass, inputClass, primaryButtonClass } from '@/components/auth/AuthShell';

export default function TwoFactorChallenge() {
    const [useRecovery, setUseRecovery] = useState(false);
    const form = useForm({ code: '', recovery_code: '' });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.transform(d => (useRecovery ? { recovery_code: d.recovery_code } : { code: d.code }));
        form.post('/two-factor-challenge', { onFinish: () => form.reset('code', 'recovery_code') });
    }

    return (
        <AuthShell
            title="Two-step sign-in"
            topLink={{ href: '/login', label: 'Back to log in' }}
            heading="One more step"
            text="Your account asks for a code from your authenticator app, as well as your password."
            points={['Open the app on your phone', 'Type the 6-digit code it shows', 'Lost your phone? Use a recovery code']}
        >
            <h1 className={headingClass}>Enter your code</h1>
            <p className="mt-2 text-base text-[#5A6482]">
                {useRecovery ? 'Type one of the recovery codes you saved when you turned on two-step sign-in. Each works once.' : 'Open your authenticator app and type the 6-digit code for this account.'}
            </p>

            <form onSubmit={submit} className="mt-6 grid gap-[18px]" noValidate>
                {useRecovery ? (
                    <Field label="Recovery code" htmlFor="recovery_code" error={form.errors.recovery_code ?? form.errors.code}>
                        <input id="recovery_code" autoFocus autoComplete="off" placeholder="AB12-CD34" className={inputClass}
                            value={form.data.recovery_code} onChange={e => form.setData('recovery_code', e.target.value)} />
                    </Field>
                ) : (
                    <Field label="6-digit code" htmlFor="code" error={form.errors.code}>
                        <input id="code" inputMode="numeric" autoComplete="one-time-code" autoFocus maxLength={7} placeholder="123456" className={inputClass}
                            value={form.data.code} onChange={e => form.setData('code', e.target.value)} />
                    </Field>
                )}
                <button type="submit" className={primaryButtonClass} disabled={form.processing || !(useRecovery ? form.data.recovery_code : form.data.code)}>
                    {form.processing ? 'Checking…' : 'Log in'}
                </button>
            </form>

            <p className="mt-7 text-center text-[15px] text-[#5A6482]">
                <button type="button" className="font-medium text-[#3346E0] hover:underline" onClick={() => { form.clearErrors(); setUseRecovery(v => !v); }}>
                    {useRecovery ? 'Use the app instead' : 'I lost my phone: use a recovery code'}
                </button>
            </p>
        </AuthShell>
    );
}
