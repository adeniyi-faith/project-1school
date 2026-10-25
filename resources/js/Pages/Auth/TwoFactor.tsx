import { useEffect, useState } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';
import { toast } from 'sonner';

import AuthShell, { Field, headingClass, inputClass, primaryButtonClass } from '@/components/auth/AuthShell';
import type { PageProps } from '@/Types';

export default function TwoFactor() {
    const { flash } = usePage<PageProps>().props;
    const form = useForm({ code: '' });
    const [recovery, setRecovery] = useState(false);

    useEffect(() => {
        if (flash?.error) toast.error(flash.error);
    }, [flash]);

    return (
        <AuthShell
            title="Second step"
            topLink={{ href: '/login', label: 'Log in' }}
            heading="One more step"
            text="Your password was right. Now confirm it is really you."
            points={['Open your authenticator app', 'Type the 6 digits it shows', 'Lost your phone? Use a recovery code']}
        >
            <h1 className={headingClass}>{recovery ? 'Use a recovery code' : 'Enter your code'}</h1>
            <p className="mt-2 text-base text-[#5A6482]">
                {recovery
                    ? 'Type one of the recovery codes you saved when you set up. Each one works only once.'
                    : 'Open the authenticator app on your phone and type the 6-digit code for Adino School Systems.'}
            </p>

            <form onSubmit={(e) => { e.preventDefault(); form.post('/two-factor'); }} className="mt-6 grid gap-[18px]" noValidate>
                <Field label={recovery ? 'Recovery code' : '6-digit code'} htmlFor="code" error={form.errors.code}>
                    <input
                        id="code"
                        key={recovery ? 'r' : 'c'}
                        type="text"
                        inputMode={recovery ? 'text' : 'numeric'}
                        autoComplete="one-time-code"
                        autoFocus
                        placeholder={recovery ? 'abcde-12345' : '123456'}
                        maxLength={recovery ? 11 : 7}
                        className={`${inputClass} tracking-[0.2em]`}
                        aria-invalid={!!form.errors.code}
                        value={form.data.code}
                        onChange={(e) => form.setData('code', e.target.value)}
                    />
                </Field>
                <button type="submit" className={primaryButtonClass} disabled={form.processing || !form.data.code}>
                    {form.processing ? 'Checking…' : 'Confirm and sign in'}
                </button>
            </form>

            <div className="mt-7 flex flex-col items-center gap-3 text-[15px]">
                <button type="button" className="font-medium text-[#3346E0] hover:underline" onClick={() => { setRecovery(!recovery); form.reset(); form.clearErrors(); }}>
                    {recovery ? 'Use my authenticator app instead' : 'I lost my phone: use a recovery code'}
                </button>
                <button type="button" className="text-[#5A6482] hover:underline" onClick={() => router.post('/two-factor/cancel')}>Back to log in</button>
            </div>
        </AuthShell>
    );
}
