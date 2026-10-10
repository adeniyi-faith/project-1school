import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { router, usePage, Head } from '@inertiajs/react';
import { toast } from 'sonner';
import { useEffect } from 'react';

import AuthLayout from '@/Layouts/AuthLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
}

const colorMap: Record<string, string> = {
    indigo:  'bg-indigo-50 border-indigo-200 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:border-indigo-800 dark:text-indigo-300 dark:hover:bg-indigo-900/50',
    violet:  'bg-violet-50 border-violet-200 text-violet-700 hover:bg-violet-100 dark:bg-violet-950/40 dark:border-violet-800 dark:text-violet-300 dark:hover:bg-violet-900/50',
    blue:    'bg-blue-50 border-blue-200 text-blue-700 hover:bg-blue-100 dark:bg-blue-950/40 dark:border-blue-800 dark:text-blue-300 dark:hover:bg-blue-900/50',
    sky:     'bg-sky-50 border-sky-200 text-sky-700 hover:bg-sky-100 dark:bg-sky-950/40 dark:border-sky-800 dark:text-sky-300 dark:hover:bg-sky-900/50',
    emerald: 'bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300 dark:hover:bg-emerald-900/50',
    amber:   'bg-amber-50 border-amber-200 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/40 dark:border-amber-800 dark:text-amber-300 dark:hover:bg-amber-900/50',
    orange:  'bg-orange-50 border-orange-200 text-orange-700 hover:bg-orange-100 dark:bg-orange-950/40 dark:border-orange-800 dark:text-orange-300 dark:hover:bg-orange-900/50',
};

export default function Login() {
    const { flash, errors: serverErrors, showDemo, demoAccounts } = usePage<LoginProps>().props;

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

    return (
        <AuthLayout>
            <Head title="Login" />

            <div className="w-full max-w-md">
                {/* Logo / Branding */}
                <div className="text-center mb-8">
                    <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 mb-4 shadow-lg">
                        <svg className="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                        Genius SMS
                    </h1>
                    <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        School Management System
                    </p>
                </div>

                {/* Demo accounts panel — shown only on .test / xgenious.com */}
                {showDemo && demoAccounts.length > 0 && (
                    <div className="mb-4 rounded-xl border border-amber-200 bg-amber-50/80 dark:border-amber-800/60 dark:bg-amber-950/20 p-4">
                        <div className="flex items-center gap-2 mb-3">
                            <span className="flex items-center justify-center w-5 h-5 rounded-full bg-amber-400 text-white text-xs font-bold shrink-0">!</span>
                            <p className="text-xs font-semibold text-amber-800 dark:text-amber-300 uppercase tracking-wide">
                                Demo Mode — click a role to fill credentials
                            </p>
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            {demoAccounts.map((account) => (
                                <button
                                    key={account.role}
                                    type="button"
                                    onClick={() => fillDemo(account)}
                                    className={`flex items-center gap-2 px-3 py-2 rounded-lg border text-xs font-medium transition-colors cursor-pointer ${colorMap[account.color] ?? colorMap.indigo}`}
                                >
                                    <span className="w-1.5 h-1.5 rounded-full bg-current opacity-60 shrink-0" />
                                    {account.role}
                                </button>
                            ))}
                        </div>
                        <p className="text-[11px] text-amber-600 dark:text-amber-500 mt-2.5">
                            Password for all demo accounts: <span className="font-mono font-semibold">password</span>
                        </p>
                    </div>
                )}

                <Card className="shadow-xl border-0 dark:bg-slate-800/60 dark:backdrop-blur">
                    <CardHeader className="space-y-1 pb-4">
                        <CardTitle className="text-xl font-semibold text-slate-900 dark:text-white">
                            Welcome back
                        </CardTitle>
                        <CardDescription className="text-slate-500 dark:text-slate-400">
                            Sign in to your account to continue
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4" noValidate>
                            {/* Email */}
                            <div className="space-y-1.5">
                                <Label htmlFor="email" className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Email address
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    autoComplete="email"
                                    autoFocus
                                    placeholder="admin@school.edu"
                                    className="h-10"
                                    {...register('email')}
                                />
                                {errors.email && (
                                    <p className="text-xs text-red-500 mt-1">{errors.email.message}</p>
                                )}
                            </div>

                            {/* Password */}
                            <div className="space-y-1.5">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="password" className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Password
                                    </Label>
                                    <a
                                        href="/forgot-password"
                                        className="text-xs text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium transition-colors"
                                    >
                                        Forgot password?
                                    </a>
                                </div>
                                <Input
                                    id="password"
                                    type="password"
                                    autoComplete="current-password"
                                    placeholder="••••••••"
                                    className="h-10"
                                    {...register('password')}
                                />
                                {errors.password && (
                                    <p className="text-xs text-red-500 mt-1">{errors.password.message}</p>
                                )}
                            </div>

                            {/* Remember me */}
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="remember"
                                    checked={remember ?? false}
                                    onCheckedChange={(checked) =>
                                        setValue('remember', checked === true)
                                    }
                                />
                                <Label
                                    htmlFor="remember"
                                    className="text-sm text-slate-600 dark:text-slate-400 cursor-pointer select-none"
                                >
                                    Remember me for 30 days
                                </Label>
                            </div>

                            {/* Submit */}
                            <Button
                                type="submit"
                                className="w-full h-10 bg-indigo-600 hover:bg-indigo-700 text-white font-medium transition-colors mt-2"
                                disabled={isSubmitting}
                            >
                                {isSubmitting ? (
                                    <span className="flex items-center gap-2">
                                        <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                        </svg>
                                        Signing in…
                                    </span>
                                ) : (
                                    'Sign in'
                                )}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <p className="text-center text-xs text-slate-400 dark:text-slate-600 mt-6">
                    &copy; {new Date().getFullYear()} Genius SMS by xgenious. All rights reserved.
                </p>
            </div>
        </AuthLayout>
    );
}
