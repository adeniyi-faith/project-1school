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
import type { PageProps } from '@/Types';

const registerSchema = z
    .object({
        school_name: z.string().min(2, 'Please enter your school name'),
        name: z.string().min(2, 'Please enter your full name'),
        email: z.string().email('Please enter a valid email address'),
        phone: z.string().optional(),
        password: z.string().min(8, 'Use at least 8 characters'),
        password_confirmation: z.string().min(1, 'Please repeat the password'),
    })
    .refine((d) => d.password === d.password_confirmation, {
        message: 'The two passwords do not match',
        path: ['password_confirmation'],
    });

type RegisterFormData = z.infer<typeof registerSchema>;

type FieldName = keyof RegisterFormData;

const fields: { name: FieldName; label: string; type: string; placeholder: string; autoComplete?: string }[] = [
    { name: 'school_name', label: 'School name', type: 'text', placeholder: 'Greenfield Academy' },
    { name: 'name', label: 'Your full name', type: 'text', placeholder: 'Ada Obi', autoComplete: 'name' },
    { name: 'email', label: 'Email address', type: 'email', placeholder: 'you@school.edu', autoComplete: 'email' },
    { name: 'phone', label: 'Phone (optional)', type: 'tel', placeholder: '+234 800 000 0000', autoComplete: 'tel' },
    { name: 'password', label: 'Password', type: 'password', placeholder: 'At least 8 characters', autoComplete: 'new-password' },
    { name: 'password_confirmation', label: 'Repeat password', type: 'password', placeholder: 'Type it again', autoComplete: 'new-password' },
];

export default function Register() {
    const { flash, errors: serverErrors } = usePage<PageProps>().props;

    const {
        register,
        handleSubmit,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<RegisterFormData>({
        resolver: zodResolver(registerSchema),
        defaultValues: { school_name: '', name: '', email: '', phone: '', password: '', password_confirmation: '' },
    });

    useEffect(() => {
        if (flash?.error) toast.error(flash.error);
    }, [flash]);

    useEffect(() => {
        fields.forEach(({ name }) => {
            const message = serverErrors?.[name];
            if (message) setError(name, { message });
        });
    }, [serverErrors, setError]);

    const onSubmit = (data: RegisterFormData) => {
        router.post('/register', data);
    };

    return (
        <AuthLayout>
            <Head title="Register your school" />

            <div className="w-full max-w-md">
                <div className="text-center mb-6">
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Register your school</h1>
                    <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Create your school and its first admin account.
                    </p>
                </div>

                <Card className="shadow-xl border-0 dark:bg-slate-800/60 dark:backdrop-blur">
                    <CardHeader className="space-y-1 pb-4">
                        <CardTitle className="text-xl font-semibold text-slate-900 dark:text-white">Get started</CardTitle>
                        <CardDescription className="text-slate-500 dark:text-slate-400">
                            It takes about a minute.
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4" noValidate>
                            {fields.map((f) => (
                                <div key={f.name} className="space-y-1.5">
                                    <Label htmlFor={f.name} className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {f.label}
                                    </Label>
                                    <Input
                                        id={f.name}
                                        type={f.type}
                                        autoComplete={f.autoComplete}
                                        placeholder={f.placeholder}
                                        className="h-10"
                                        {...register(f.name)}
                                    />
                                    {errors[f.name] && (
                                        <p className="text-xs text-red-500 mt-1">{errors[f.name]?.message}</p>
                                    )}
                                </div>
                            ))}

                            <Button
                                type="submit"
                                className="w-full h-10 bg-indigo-600 hover:bg-indigo-700 text-white font-medium transition-colors mt-2"
                                disabled={isSubmitting}
                            >
                                {isSubmitting ? 'Creating your school…' : 'Create my school'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <p className="text-center text-sm text-slate-600 dark:text-slate-400 mt-5">
                    Already registered?{' '}
                    <a href="/login" className="font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                        Sign in
                    </a>
                </p>
            </div>
        </AuthLayout>
    );
}
