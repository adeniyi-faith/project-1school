import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { ArrowLeft, ChevronRight } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import type { PageProps, SchoolClass, Section } from '@/Types';

interface Props extends PageProps {
    classes:  Pick<SchoolClass, 'id' | 'name'>[];
    sections: (Pick<Section, 'id' | 'name'> & { class_id: number })[];
}

const schema = z.object({
    // Personal
    first_name:      z.string().min(1, 'First name is required'),
    last_name:       z.string().optional(),
    gender:          z.enum(['male', 'female', 'other']),
    date_of_birth:   z.string().optional(),
    blood_group:     z.string().optional(),
    religion:        z.string().optional(),
    nationality:     z.string().optional(),
    phone:           z.string().optional(),
    email:           z.string().email().optional().or(z.literal('')),
    address:         z.string().optional(),
    category:        z.enum(['general', 'disabled', 'quota']),
    status:          z.enum(['active', 'alumni', 'transferred', 'inactive']),
    admission_date:  z.string().optional(),
    previous_school: z.string().optional(),
    roll_no:         z.string().optional(),
    // Class
    class_id:   z.coerce.number().int().positive('Select a class'),
    section_id: z.coerce.number().int().positive().nullable().optional(),
    // Guardian
    guardian: z.object({
        name:       z.string().min(1, 'Guardian name is required'),
        relation:   z.string().min(1, 'Relation is required'),
        phone:      z.string().optional(),
        email:      z.string().email().optional().or(z.literal('')),
        occupation: z.string().optional(),
        address:    z.string().optional(),
    }),
});

type FormData = z.infer<typeof schema>;

const STEPS = ['Personal Info', 'Class & Roll', 'Guardian Info'];

export default function CreateStudent() {
    const { classes, sections } = usePage<Props>().props;
    const [step, setStep] = useState(0);

    const { register, handleSubmit, setValue, watch, setError, formState: { errors, isSubmitting } } =
        useForm<FormData>({
            resolver: zodResolver(schema),
            defaultValues: { gender: 'male', category: 'general', status: 'active', nationality: 'Bangladeshi', guardian: { relation: 'Father' } },
        });

    const selectedClassId = watch('class_id');
    const visibleSections = selectedClassId ? sections.filter((s) => s.class_id === Number(selectedClassId)) : [];

    const onSubmit = (data: FormData) => {
        router.post('/school/students', data, {
            onError: (errs) => Object.entries(errs).forEach(([f, m]) => setError(f as keyof FormData, { message: m })),
        });
    };

    const Field = ({ name, label, placeholder, type = 'text', required = false }: {
        name: string; label: string; placeholder?: string; type?: string; required?: boolean;
    }) => {
        const keys = name.split('.');
        const err  = keys.length === 2
            ? (errors as Record<string, Record<string, { message?: string }>>)[keys[0]]?.[keys[1]]
            : (errors as Record<string, { message?: string }>)[name];
        return (
            <div className="space-y-1.5">
                <Label className="text-sm font-medium">{label}{required && <span className="text-red-500 ml-1">*</span>}</Label>
                <Input type={type} placeholder={placeholder} className="h-9" {...register(name as keyof FormData)} />
                {err && <p className="text-xs text-red-500">{err.message as string}</p>}
            </div>
        );
    };

    return (
        <AppLayout breadcrumbs={[
            { label: 'Students', href: '/school/students' },
            { label: 'Admit Student' },
        ]}>
            <Head title="Admit Student" />

            <div className="max-w-2xl">
                <div className="flex items-center gap-3 mb-6">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/school/students"><ArrowLeft className="w-4 h-4" /></Link>
                    </Button>
                    <div>
                        <h1 className="text-xl font-bold text-slate-900 dark:text-white">Admit Student</h1>
                        <p className="text-sm text-slate-500">Step {step + 1} of {STEPS.length} — {STEPS[step]}</p>
                    </div>
                </div>

                {/* Step indicators */}
                <div className="flex items-center gap-2 mb-6">
                    {STEPS.map((s, i) => (
                        <div key={s} className="flex items-center gap-2">
                            <button
                                type="button"
                                onClick={() => i < step && setStep(i)}
                                className={`flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold transition-colors ${i === step ? 'bg-indigo-600 text-white' : i < step ? 'bg-emerald-500 text-white cursor-pointer' : 'bg-slate-200 text-slate-400 dark:bg-slate-800'}`}
                            >{i + 1}</button>
                            <span className={`text-xs hidden sm:block ${i === step ? 'text-slate-900 dark:text-white font-medium' : 'text-slate-400'}`}>{s}</span>
                            {i < STEPS.length - 1 && <ChevronRight className="w-3.5 h-3.5 text-slate-300 dark:text-slate-700" />}
                        </div>
                    ))}
                </div>

                <form onSubmit={handleSubmit(onSubmit)}>
                    {/* Step 0 — Personal */}
                    {step === 0 && (
                        <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                            <CardHeader className="pb-3"><CardTitle className="text-sm">Personal Information</CardTitle></CardHeader>
                            <CardContent className="grid grid-cols-2 gap-4">
                                <Field name="first_name" label="First Name" placeholder="John" required />
                                <Field name="last_name"  label="Last Name"  placeholder="Doe" />
                                <div className="space-y-1.5">
                                    <Label className="text-sm font-medium">Gender <span className="text-red-500">*</span></Label>
                                    <Select defaultValue="male" onValueChange={(v) => setValue('gender', v as 'male' | 'female' | 'other')}>
                                        <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="male">Male</SelectItem>
                                            <SelectItem value="female">Female</SelectItem>
                                            <SelectItem value="other">Other</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <Field name="date_of_birth" label="Date of Birth" type="date" />
                                <Field name="blood_group" label="Blood Group" placeholder="A+" />
                                <Field name="religion"    label="Religion"    placeholder="Islam" />
                                <Field name="nationality" label="Nationality"  placeholder="Bangladeshi" />
                                <Field name="phone"       label="Phone"        placeholder="+8801700000000" />
                                <Field name="email"       label="Email"        placeholder="student@email.com" type="email" />
                                <div className="space-y-1.5">
                                    <Label className="text-sm font-medium">Category</Label>
                                    <Select defaultValue="general" onValueChange={(v) => setValue('category', v as 'general' | 'disabled' | 'quota')}>
                                        <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="general">General</SelectItem>
                                            <SelectItem value="disabled">Disabled</SelectItem>
                                            <SelectItem value="quota">Quota</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-1.5">
                                    <Label className="text-sm font-medium">Status</Label>
                                    <Select defaultValue="active" onValueChange={(v) => setValue('status', v as 'active' | 'alumni' | 'transferred' | 'inactive')}>
                                        <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">Active</SelectItem>
                                            <SelectItem value="inactive">Inactive</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="col-span-2 space-y-1.5">
                                    <Label className="text-sm font-medium">Address</Label>
                                    <Textarea rows={2} className="resize-none" placeholder="House, Road, Area…" {...register('address')} />
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Step 1 — Class */}
                    {step === 1 && (
                        <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                            <CardHeader className="pb-3"><CardTitle className="text-sm">Class Assignment</CardTitle></CardHeader>
                            <CardContent className="grid grid-cols-2 gap-4">
                                <div className="space-y-1.5">
                                    <Label className="text-sm font-medium">Class <span className="text-red-500">*</span></Label>
                                    <Select onValueChange={(v) => { setValue('class_id', Number(v)); setValue('section_id', null); }}>
                                        <SelectTrigger className="h-9"><SelectValue placeholder="Select class" /></SelectTrigger>
                                        <SelectContent>
                                            {classes.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                    {errors.class_id && <p className="text-xs text-red-500">{errors.class_id.message}</p>}
                                </div>
                                <div className="space-y-1.5">
                                    <Label className="text-sm font-medium">Section</Label>
                                    <Select onValueChange={(v) => setValue('section_id', Number(v))} disabled={visibleSections.length === 0}>
                                        <SelectTrigger className="h-9"><SelectValue placeholder={visibleSections.length === 0 ? 'Select class first' : 'Select section'} /></SelectTrigger>
                                        <SelectContent>
                                            {visibleSections.map((s) => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <Field name="roll_no"         label="Roll No"       placeholder="01" />
                                <Field name="admission_date"  label="Admission Date" type="date" />
                                <div className="col-span-2">
                                    <Field name="previous_school" label="Previous School" placeholder="XYZ School" />
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Step 2 — Guardian */}
                    {step === 2 && (
                        <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                            <CardHeader className="pb-3"><CardTitle className="text-sm">Guardian Information</CardTitle></CardHeader>
                            <CardContent className="grid grid-cols-2 gap-4">
                                <Field name="guardian.name"  label="Guardian Name" placeholder="Mr. John Doe" required />
                                <div className="space-y-1.5">
                                    <Label className="text-sm font-medium">Relation <span className="text-red-500">*</span></Label>
                                    <Select defaultValue="Father" onValueChange={(v) => setValue('guardian.relation', v)}>
                                        <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            {['Father','Mother','Guardian','Uncle','Aunt','Sibling'].map((r) => (
                                                <SelectItem key={r} value={r}>{r}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <Field name="guardian.phone"      label="Phone"      placeholder="+8801700000000" />
                                <Field name="guardian.email"      label="Email"      type="email" />
                                <Field name="guardian.occupation" label="Occupation" placeholder="Business" />
                                <div className="col-span-2 space-y-1.5">
                                    <Label className="text-sm font-medium">Address</Label>
                                    <Textarea rows={2} className="resize-none" {...register('guardian.address')} />
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Nav buttons */}
                    <div className="flex gap-3 justify-end mt-4">
                        {step > 0 && (
                            <Button type="button" variant="outline" onClick={() => setStep(step - 1)}>Back</Button>
                        )}
                        {step < STEPS.length - 1 ? (
                            <Button type="button" className="bg-indigo-600 hover:bg-indigo-700 text-white" onClick={() => setStep(step + 1)}>
                                Next — {STEPS[step + 1]}
                            </Button>
                        ) : (
                            <Button type="submit" disabled={isSubmitting} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {isSubmitting ? 'Admitting…' : 'Admit Student'}
                            </Button>
                        )}
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
