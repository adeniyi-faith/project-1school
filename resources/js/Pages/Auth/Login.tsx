import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { router, usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import { useEffect, useState } from 'react';

import AuthShell, { Field, headingClass, inputClass, primaryButtonClass } from '@/components/auth/AuthShell';
import type { PageProps } from '@/Types';

const loginSchema = z.object({
    email: z.string().email('Please enter a valid email address'),
    password: z.string().min(1, 'Password is required'),
    remember: z.boolean().optional(),
});

type LoginFormData = z.infer<typeof loginSchema>;

interface DemoAccount {
    role: string;
    email: string;
    password: string;
    color: string;
}

interface LoginProps extends PageProps {
    showDemo: boolean;
    demoAccounts: DemoAccount[];
    demoEnabled?: boolean;
}

export default function Login() {
    const { flash, errors: serverErrors, showDemo, demoAccounts, demoEnabled } = usePage<LoginProps>().props;
    const [showPassword, setShowPassword] = useState(false);

    const {
        register,
        handleSubmit,
        setValue,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<LoginFormData>({
        resolver: zodResolver(loginSchema),
        defaultValues: { email: '', password: '', remember: false },
    });

    useEffect(() => {
        if (flash?.error) toast.error(flash.error);
        if (flash?.success) toast.success(flash.success);
    }, [flash]);

    useEffect(() => {
        if (serverErrors?.email) setError('email', { message: serverErrors.email });
        if (serverErrors?.password) setError('password', { message: serverErrors.password });
    }, [serverErrors, setError]);

    const onSubmit = (data: LoginFormData) => {
        router.post('/login', data, {
            onError: (errs) => {
                if (errs.email) setError('email', { message: errs.email });
                if (errs.password) setError('password', { message: errs.password });
                if (errs.message) toast.error(errs.message);
            },
        });
    };

    const fillDemo = (account: DemoAccount) => {
        setValue('email', account.email, { shouldValidate: true });
        setValue('password', account.password, { shouldValidate: true });
        toast.info(`Demo: ${account.role} credentials filled`);
    };

    return (
        <AuthShell
            title="Log in"
            topLink={{ href: '/register', label: 'Register a school' }}
            heading="One login for your whole school"
            text="Administrators, staff, parents and students each sign in to their own view of the same school."
            points={['Admin & staff · run classes, fees and results', 'Parents · see fees, results and attendance', 'Students · see homework, timetables and results']}
        >
            <h1 className={headingClass}>Welcome back</h1>
            <p className="mt-2 text-base text-[#5A6482]">Log in with the email and password your school gave you.</p>

            {showDemo && demoAccounts.length > 0 && (
                <div className="mt-6 rounded-xl bg-[#F5F7FC] p-4 text-sm text-[#2A3555]">
                    <p className="font-medium">Demo mode: pick a role to fill in the details</p>
                    <div className="mt-3 flex flex-wrap gap-2">
                        {demoAccounts.map((account) => (
                            <button
                                key={account.role}
                                type="button"
                                onClick={() => fillDemo(account)}
                                className="h-10 rounded-full border-[1.5px] border-[#DCE1EE] bg-white px-4 text-sm font-medium transition hover:border-[#3346E0] hover:text-[#3346E0]"
                            >
                                {account.role}
                            </button>
                        ))}
                    </div>
                    <p className="mt-3 text-[13px] text-[#5A6482]">
                        Password for every demo account: <span className="font-mono font-semibold text-[#0E1A3A]">password</span>
                    </p>
                </div>
            )}

            <form onSubmit={handleSubmit(onSubmit)} className="mt-6 grid gap-[18px]" noValidate>
                <Field label="Email address" htmlFor="email" error={errors.email?.message}>
                    <input
                        id="email"
                        type="email"
                        inputMode="email"
                        autoComplete="username"
                        autoFocus
                        placeholder="you@school.edu.ng"
                        className={inputClass}
                        aria-invalid={!!errors.email}
                        {...register('email')}
                    />
                </Field>

                <div className="grid min-w-0 gap-[7px]">
                    <div className="flex items-center justify-between gap-3">
                        <label htmlFor="password" className="text-[15px] font-semibold">Password</label>
                        <a href="/forgot-password" className="text-[14.5px] text-[#3346E0] hover:underline">Forgot password?</a>
                    </div>
                    <div className="relative">
                        <input
                            id="password"
                            type={showPassword ? 'text' : 'password'}
                            autoComplete="current-password"
                            className={`${inputClass} pr-[72px]`}
                            aria-invalid={!!errors.password}
                            {...register('password')}
                        />
                        <button
                            type="button"
                            onClick={() => setShowPassword((v) => !v)}
                            className="absolute right-1 top-1/2 h-11 -translate-y-1/2 px-3 text-sm font-semibold text-[#3346E0]"
                            aria-label={showPassword ? 'Hide password' : 'Show password'}
                        >
                            {showPassword ? 'Hide' : 'Show'}
                        </button>
                    </div>
                    {errors.password && <span role="alert" className="text-[13.5px] text-[#D0472F]">{errors.password.message}</span>}
                </div>

                <label className="grid cursor-pointer grid-cols-[22px_1fr] items-start gap-2.5 text-[14.5px] text-[#2A3555]">
                    <input type="checkbox" className="mt-px size-5 accent-[#3346E0]" {...register('remember')} />
                    <span>Keep me signed in on this device</span>
                </label>

                <button type="submit" className={primaryButtonClass} disabled={isSubmitting}>
                    {isSubmitting ? 'Signing in…' : 'Log in'}
                </button>
            </form>

            <div className="mt-7 grid gap-2 text-center text-[15px] text-[#5A6482]">
                <p>
                    Not on SchoolRuns yet?{' '}
                    <a href="/register" className="font-medium text-[#3346E0] hover:underline">Register your school</a>
                </p>
                {demoEnabled && (
                    <p>
                        Just looking?{' '}
                        <a href="/demo" className="font-medium text-[#3346E0] hover:underline">Try the demo</a>
                    </p>
                )}
            </div>

            <p className="mt-6 text-center text-[13px] leading-relaxed text-[#5A6482]">
                Your school decides who can sign in. If you can't get in, ask the school office to check your account.
            </p>
        </AuthShell>
    );
}
