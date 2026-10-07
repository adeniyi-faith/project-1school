import { Head, Link, router, usePage } from '@inertiajs/react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { PageProps } from '@/Types';

const schema = z.object({
    name:     z.string().min(2, 'School name is required'),
    slug:     z.string().optional(),
    email:    z.string().email('Invalid email').optional().or(z.literal('')),
    phone:    z.string().optional(),
    address:  z.string().optional(),
    city:     z.string().optional(),
    state:    z.string().optional(),
    country:  z.string().default('BD'),
    timezone: z.string().default('Asia/Dhaka'),
    currency: z.string().default('BDT'),
    language: z.string().default('en'),
    status:   z.enum(['active', 'inactive', 'suspended']).default('active'),
});

type FormData = z.infer<typeof schema>;

export default function CreateSchool() {
    const { errors: serverErrors } = usePage<PageProps>().props;

    const { register, handleSubmit, setValue, watch, setError, formState: { errors, isSubmitting } } =
        useForm<FormData>({
            resolver: zodResolver(schema),
            defaultValues: { country: 'BD', timezone: 'Asia/Dhaka', currency: 'BDT', language: 'en', status: 'active' },
        });

    const onSubmit = (data: FormData) => {
        router.post('/super-admin/schools', data, {
            onError: (errs) => {
                Object.entries(errs).forEach(([field, msg]) =>
                    setError(field as keyof FormData, { message: msg })
                );
            },
        });
    };

    const Field = ({ name, label, placeholder, type = 'text', required = false }: {
        name: keyof FormData; label: string; placeholder?: string; type?: string; required?: boolean;
    }) => (
        <div className="space-y-1.5">
            <Label htmlFor={name} className="text-sm font-medium text-slate-700 dark:text-slate-300">
                {label}{required && <span className="text-red-500 ml-1">*</span>}
            </Label>
            <Input id={name} type={type} placeholder={placeholder} className="h-9" {...register(name)} />
            {errors[name] && <p className="text-xs text-red-500">{errors[name]?.message as string}</p>}
        </div>
    );

    return (
        <AppLayout breadcrumbs={[
            { label: 'Schools', href: '/super-admin/schools' },
            { label: 'Create School' },
        ]}>
            <Head title="Create School" />

            <div className="max-w-2xl">
                <div className="flex items-center gap-3 mb-6">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/super-admin/schools"><ArrowLeft className="w-4 h-4" /></Link>
                    </Button>
                    <div>
                        <h1 className="text-xl font-bold text-slate-900 dark:text-white">Create School</h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400">Add a new school to the system</p>
                    </div>
                </div>

                <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                    <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold text-slate-700 dark:text-slate-300">Basic Information</CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-2 gap-4">
                            <div className="col-span-2">
                                <Field name="name" label="School Name" placeholder="Greenfield Academy" required />
                            </div>
                            <Field name="slug" label="Slug" placeholder="auto-generated if empty" />
                            <Field name="email" label="Email" placeholder="info@school.edu" type="email" />
                            <Field name="phone" label="Phone" placeholder="+8801700000000" />
                            <div className="space-y-1.5">
                                <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">Status</Label>
                                <Select defaultValue="active" onValueChange={(v) => setValue('status', v as FormData['status'])}>
                                    <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="inactive">Inactive</SelectItem>
                                        <SelectItem value="suspended">Suspended</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold text-slate-700 dark:text-slate-300">Location</CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-2 gap-4">
                            <div className="col-span-2">
                                <div className="space-y-1.5">
                                    <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">Address</Label>
                                    <Textarea placeholder="123 School Road…" className="resize-none" rows={2} {...register('address')} />
                                </div>
                            </div>
                            <Field name="city" label="City" placeholder="Dhaka" />
                            <Field name="state" label="State / Division" placeholder="Dhaka Division" />
                        </CardContent>
                    </Card>

                    <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold text-slate-700 dark:text-slate-300">Locale Settings</CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-3 gap-4">
                            <div className="space-y-1.5">
                                <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">Timezone</Label>
                                <Select defaultValue="Asia/Dhaka" onValueChange={(v) => setValue('timezone', v)}>
                                    <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Asia/Dhaka">Asia/Dhaka (BST)</SelectItem>
                                        <SelectItem value="Asia/Kolkata">Asia/Kolkata (IST)</SelectItem>
                                        <SelectItem value="UTC">UTC</SelectItem>
                                        <SelectItem value="America/New_York">America/New_York</SelectItem>
                                        <SelectItem value="Europe/London">Europe/London</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">Currency</Label>
                                <Select defaultValue="BDT" onValueChange={(v) => setValue('currency', v)}>
                                    <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="BDT">BDT (৳)</SelectItem>
                                        <SelectItem value="USD">USD ($)</SelectItem>
                                        <SelectItem value="INR">INR (₹)</SelectItem>
                                        <SelectItem value="GBP">GBP (£)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label className="text-sm font-medium text-slate-700 dark:text-slate-300">Language</Label>
                                <Select defaultValue="en" onValueChange={(v) => setValue('language', v)}>
                                    <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="en">English</SelectItem>
                                        <SelectItem value="bn">Bengali</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex gap-3 justify-end">
                        <Button type="button" variant="outline" asChild>
                            <Link href="/super-admin/schools">Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={isSubmitting} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                            {isSubmitting ? 'Creating…' : 'Create School'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
