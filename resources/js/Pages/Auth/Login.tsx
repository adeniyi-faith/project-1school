import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { router, usePage, Head } from '@inertiajs/react';
import { toast } from 'sonner';
import { useEffect, useState } from 'react';

import AuthLayout from '@/Layouts/AuthLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Eye, EyeOff, ShieldCheck, ClipboardCheck, Banknote, Users } from 'lucide-react';
import { LogoMark } from '@/components/layout/Logo';
import { Checkbox } from '@/components/ui/checkbox';
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

    const {
        register,
        handleSubmit,
        setValue,
        watch,
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

    const remember = watch('remember');
    const [showPassword, setShowPassword] = useState(false);

    return (
        <AuthLayout>
            <Head title="Sign in" />

            <div className="grid min-h-dvh lg:grid-cols-[1.05fr_1fr]">
                {/* Brand panel */}
                <aside className="relative flex flex-col gap-8 overflow-hidden bg-gradient-to-br from-[oklch(0.2_0.06_263)] via-[oklch(0.24_0.1_265)] to-[oklch(0.3_0.16_268)] px-6 pb-10 pt-8 text-white max-lg:rounded-b-[28px] max-lg:from-indigo-700 max-lg:via-indigo-700 max-lg:to-[oklch(0.36_0.16_292)] lg:p-12">
                    <div className="pointer-events-none absolute -right-32 -top-32 size-[28rem] rounded-full bg-indigo-500/25 blur-3xl" aria-hidden="true" />
                    <div className="pointer-events-none absolute -bottom-40 -left-24 size-[24rem] rounded-full bg-amber-400/10 blur-3xl" aria-hidden="true" />

                    <div className="relative flex items-center gap-2.5">
                        <LogoMark className="size-9 bg-white/15 from-white/20 to-white/10 shadow-none ring-1 ring-white/25" />
                        <span className="text-lg font-semibold tracking-tight">SchoolRuns</span>
                    </div>

                    <div className="relative mt-2 max-w-md lg:mt-auto">
                        <h1 className="text-[1.75rem] font-semibold leading-[1.12] tracking-[-0.03em] sm:text-4xl lg:text-[2.6rem]">
                            Everything about your school, in one place.
                        </h1>
                        <p className="mt-3 max-w-sm text-[15px] leading-relaxed text-white/70 lg:mt-4">
                            Attendance, results, fees and messages for staff, parents and students.
                        </p>
                    </div>

                    <ul className="relative hidden gap-3 lg:grid lg:grid-cols-3">
                        {[
                            { icon: ClipboardCheck, title: 'Daily register', text: 'Mark a class in seconds' },
                            { icon: Banknote,       title: 'Fees in ₦',      text: 'Receipts sent by SMS' },
                            { icon: Users,          title: 'Parents included', text: 'Results on their phone' },
                        ].map(({ icon: Icon, title, text }) => (
                            <li key={title} className="rounded-xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur-sm">
                                <Icon className="mb-3 size-[18px] text-indigo-200" strokeWidth={1.75} />
                                <p className="text-sm font-medium">{title}</p>
                                <p className="mt-0.5 text-xs text-white/55">{text}</p>
                            </li>
                        ))}
                    </ul>
                </aside>

                {/* Form */}
                <main className="flex items-start justify-center px-5 py-9 sm:px-8 lg:items-center lg:py-12">
                    <div className="w-full max-w-[26rem]">
                        <div className="mb-7">
                            <h2 className="text-2xl font-semibold tracking-[-0.025em] text-slate-900 dark:text-white">Welcome back</h2>
                            <p className="mt-1.5 text-sm text-slate-500 dark:text-slate-400">Sign in to continue to your school.</p>
                        </div>

                        {showDemo && demoAccounts.length > 0 && (
                            <div className="mb-6 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/60">
                                <p className="mb-3 flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-300">
                                    <ShieldCheck className="size-3.5 text-indigo-500" /> Demo mode: pick a role to fill in the sign-in details
                                </p>
                                <div className="flex flex-wrap gap-1.5">
                                    {demoAccounts.map((account) => (
                                        <button
                                            key={account.role}
                                            type="button"
                                            onClick={() => fillDemo(account)}
                                            className="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700 outline-none transition-colors hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 focus-visible:ring-2 focus-visible:ring-indigo-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                        >
                                            {account.role}
                                        </button>
                                    ))}
                                </div>
                                <p className="mt-2.5 text-[11px] text-slate-500">
                                    Password for every demo account: <span className="font-mono font-semibold text-slate-700 dark:text-slate-300">password</span>
                                </p>
                            </div>
                        )}

                        <form onSubmit={handleSubmit(onSubmit)} className="space-y-5" noValidate>
                            <div className="space-y-1.5">
                                <Label htmlFor="email" className="text-[13px] font-medium text-slate-700 dark:text-slate-300">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    autoComplete="email"
                                    autoFocus
                                    placeholder="you@school.edu.ng"
                                    className="h-11 rounded-[10px] bg-white px-3.5 text-[15px] shadow-xs dark:bg-slate-900"
                                    aria-invalid={!!errors.email}
                                    {...register('email')}
                                />
                                {errors.email && <p className="text-xs text-red-600 dark:text-red-400">{errors.email.message}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="password" className="text-[13px] font-medium text-slate-700 dark:text-slate-300">Password</Label>
                                    <a href="/forgot-password" className="text-xs font-medium text-indigo-600 transition-colors hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        Forgot password?
                                    </a>
                                </div>
                                <div className="relative">
                                    <Input
                                        id="password"
                                        type={showPassword ? 'text' : 'password'}
                                        autoComplete="current-password"
                                        placeholder="Enter your password"
                                        className="h-11 rounded-[10px] bg-white px-3.5 pr-11 text-[15px] shadow-xs dark:bg-slate-900"
                                        aria-invalid={!!errors.password}
                                        {...register('password')}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword((v) => !v)}
                                        className="absolute right-1.5 top-1/2 flex size-8 -translate-y-1/2 items-center justify-center rounded-md text-slate-400 outline-none transition-colors hover:text-slate-700 focus-visible:ring-2 focus-visible:ring-indigo-400 dark:hover:text-slate-200"
                                        aria-label={showPassword ? 'Hide password' : 'Show password'}
                                    >
                                        {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                                    </button>
                                </div>
                                {errors.password && <p className="text-xs text-red-600 dark:text-red-400">{errors.password.message}</p>}
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox id="remember" checked={remember ?? false} onCheckedChange={(checked) => setValue('remember', checked === true)} />
                                <Label htmlFor="remember" className="cursor-pointer select-none text-sm font-normal text-slate-600 dark:text-slate-400">
                                    Keep me signed in for 30 days
                                </Label>
                            </div>

                            <Button
                                type="submit"
                                className="h-11 w-full rounded-[10px] bg-indigo-600 text-[15px] font-medium text-white shadow-[inset_0_1px_0_oklch(1_0_0/0.2),0_1px_2px_oklch(0.3_0.15_264/0.4)] transition-colors hover:bg-indigo-700"
                                disabled={isSubmitting}
                            >
                                {isSubmitting ? (
                                    <span className="flex items-center gap-2">
                                        <svg className="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                        </svg>
                                        Signing in…
                                    </span>
                                ) : 'Sign in'}
                            </Button>
                        </form>

                        <div className="mt-6 space-y-1 text-sm text-slate-600 dark:text-slate-400">
                            <p>
                                New school?{' '}
                                <a href="/register" className="font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Register your school</a>
                            </p>
                            {demoEnabled && (
                                <p>
                                    Just looking?{' '}
                                    <a href="/demo" className="font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Try the demo</a>
                                </p>
                            )}
                        </div>

                        <p className="mt-6 text-xs leading-relaxed text-slate-400 dark:text-slate-500">
                            Your school decides who can sign in. If you can't get in, ask the school office to check your account.
                        </p>
                    </div>
                </main>
            </div>
        </AuthLayout>
    );
}
