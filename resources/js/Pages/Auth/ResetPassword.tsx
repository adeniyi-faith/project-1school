import { useForm } from '@inertiajs/react';

import AuthShell, { Field, headingClass, inputClass, primaryButtonClass } from '@/components/auth/AuthShell';

interface Props { token: string; email: string }

export default function ResetPassword({ token, email }: Props) {
    const form = useForm({ token, email, password: '', password_confirmation: '' });

    return (
        <AuthShell
            title="Choose a new password"
            topLink={{ href: '/login', label: 'Log in' }}
            heading="Choose a new password"
            text="Pick something you can remember but others cannot guess."
            points={['At least 8 characters', 'You will be logged out on other devices']}
        >
            <h1 className={headingClass}>Choose a new password</h1>
            <p className="mt-2 text-base text-[#5A6482]">Use at least 8 characters.</p>

            <form onSubmit={e => { e.preventDefault(); form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') }); }} className="mt-6 grid gap-[18px]" noValidate>
                <Field label="Email address" htmlFor="email" error={form.errors.email}>
                    <input id="email" type="email" autoComplete="username" className={inputClass} value={form.data.email} onChange={e => form.setData('email', e.target.value)} />
                </Field>
                <Field label="New password" htmlFor="password" error={form.errors.password}>
                    <input id="password" type="password" autoComplete="new-password" autoFocus className={inputClass} aria-invalid={!!form.errors.password}
                        value={form.data.password} onChange={e => form.setData('password', e.target.value)} />
                </Field>
                <Field label="Type it again" htmlFor="password_confirmation">
                    <input id="password_confirmation" type="password" autoComplete="new-password" className={inputClass}
                        value={form.data.password_confirmation} onChange={e => form.setData('password_confirmation', e.target.value)} />
                </Field>
                <button type="submit" className={primaryButtonClass} disabled={form.processing || form.data.password.length < 8}>
                    {form.processing ? 'Saving…' : 'Save new password'}
                </button>
            </form>
        </AuthShell>
    );
}
