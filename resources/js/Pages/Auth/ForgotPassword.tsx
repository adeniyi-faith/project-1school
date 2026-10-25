import { useEffect } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { toast } from 'sonner';

import AuthShell, { Field, headingClass, inputClass, primaryButtonClass } from '@/components/auth/AuthShell';
import type { PageProps } from '@/Types';

export default function ForgotPassword() {
    const { flash } = usePage<PageProps>().props;
    const form = useForm({ email: '' });

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
    }, [flash]);

    return (
        <AuthShell
            title="Forgot password"
            topLink={{ href: '/login', label: 'Log in' }}
            heading="Back in, in a minute"
            text="We email you a link. Open it, choose a new password, and log in."
            points={['The link works for 60 minutes', 'Use the email your school has for you', 'No email? Ask the school office to reset it']}
        >
            <h1 className={headingClass}>Forgot your password?</h1>
            <p className="mt-2 text-base text-[#5A6482]">Type your email address and we will send you a link to choose a new one.</p>

            <form onSubmit={e => { e.preventDefault(); form.post('/forgot-password', { preserveScroll: true }); }} className="mt-6 grid gap-[18px]" noValidate>
                <Field label="Email address" htmlFor="email" error={form.errors.email}>
                    <input id="email" type="email" inputMode="email" autoComplete="username" autoFocus placeholder="you@school.edu.ng"
                        className={inputClass} aria-invalid={!!form.errors.email} value={form.data.email} onChange={e => form.setData('email', e.target.value)} />
                </Field>
                <button type="submit" className={primaryButtonClass} disabled={form.processing || !form.data.email}>
                    {form.processing ? 'Sending…' : 'Send me the link'}
                </button>
            </form>

            <p className="mt-7 text-center text-[15px] text-[#5A6482]">
                <a href="/login" className="font-medium text-[#3346E0] hover:underline">Back to log in</a>
            </p>
        </AuthShell>
    );
}
