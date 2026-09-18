import { Head, Link, router, usePage } from '@inertiajs/react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import type { PageProps, Student, SchoolClass, Section } from '@/Types';

interface Props extends PageProps {
    student:  Student;
    classes:  Pick<SchoolClass, 'id' | 'name'>[];
    sections: (Pick<Section, 'id' | 'name'> & { class_id: number })[];
}

const schema = z.object({
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
    class_id:        z.coerce.number().int().positive('Select a class'),
    section_id:      z.coerce.number().int().positive().nullable().optional(),
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

export default function EditStudent() {
    const { student, classes, sections } = usePage<Props>().props;

    const { register, handleSubmit, setValue, watch, setError, formState: { errors, isSubmitting } } =
        useForm<FormData>({
            resolver: zodResolver(schema),
            defaultValues: {
                first_name:      student.first_name,
                last_name:       student.last_name ?? '',
                gender:          student.gender,
                date_of_birth:   student.date_of_birth?.slice(0, 10) ?? '',
                blood_group:     student.blood_group ?? '',
                religion:        student.religion ?? '',
                nationality:     student.nationality,
                phone:           student.phone ?? '',
                email:           student.email ?? '',
                address:         student.address ?? '',
                category:        student.category,
                status:          student.status,
                admission_date:  student.admission_date?.slice(0, 10) ?? '',
                previous_school: student.previous_school ?? '',
                roll_no:         student.roll_no ?? '',
                class_id:        student.class_id,
                section_id:      student.section_id ?? undefined,
                guardian: {
                    name:       student.guardian?.name ?? '',
                    relation:   student.guardian?.relation ?? 'Father',
                    phone:      student.guardian?.phone ?? '',
                    email:      student.guardian?.email ?? '',
                    occupation: student.guardian?.occupation ?? '',
                    address:    student.guardian?.address ?? '',
                },
            },
        });

    const selectedClassId = watch('class_id');
    const visibleSections = sections.filter((s) => s.class_id === Number(selectedClassId));

    const onSubmit = (data: FormData) => {
        router.put(`/school/students/${student.id}`, data, {
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
            { label: student.full_name, href: `/school/students/${student.id}` },
            { label: 'Edit' },
        ]}>
            <Head title={`Edit ${student.full_name}`} />

            <div className="max-w-2xl">
                <div className="flex items-center gap-3 mb-6">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={`/school/students/${student.id}`}><ArrowLeft className="w-4 h-4" /></Link>
                    </Button>
                    <div>
                        <h1 className="text-xl font-bold text-slate-900 dark:text-white">Edit Student</h1>
                        <p className="text-sm text-slate-500">{student.full_name} · {student.admission_no}</p>
                    </div>
                </div>

                <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                    {/* Personal */}
                    <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                        <CardHeader className="pb-3"><CardTitle className="text-sm">Personal Information</CardTitle></CardHeader>
                        <CardContent className="grid grid-cols-2 gap-4">
                            <Field name="first_name" label="First Name" required />
                            <Field name="last_name"  label="Last Name" />
                            <div className="space-y-1.5">
                                <Label className="text-sm font-medium">Gender</Label>
                                <Select defaultValue={student.gender} onValueChange={(v) => setValue('gender', v as 'male' | 'female' | 'other')}>
                                    <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="male">Male</SelectItem>
                                        <SelectItem value="female">Female</SelectItem>
                                        <SelectItem value="other">Other</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <Field name="date_of_birth" label="Date of Birth" type="date" />
                            <Field name="blood_group" label="Blood Group" />
                            <Field name="religion"    label="Religion" />
                            <Field name="phone"       label="Phone" />
                            <Field name="email"       label="Email" type="email" />
                            <div className="space-y-1.5">
                                <Label className="text-sm font-medium">Status</Label>
                                <Select defaultValue={student.status} onValueChange={(v) => setValue('status', v as FormData['status'])}>
                                    <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="alumni">Alumni</SelectItem>
                                        <SelectItem value="transferred">Transferred</SelectItem>
                                        <SelectItem value="inactive">Inactive</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label className="text-sm font-medium">Category</Label>
                                <Select defaultValue={student.category} onValueChange={(v) => setValue('category', v as FormData['category'])}>
                                    <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="general">General</SelectItem>
                                        <SelectItem value="disabled">Disabled</SelectItem>
                                        <SelectItem value="quota">Quota</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="col-span-2 space-y-1.5">
                                <Label className="text-sm font-medium">Address</Label>
                                <Textarea rows={2} className="resize-none" {...register('address')} />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Class */}
                    <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                        <CardHeader className="pb-3"><CardTitle className="text-sm">Class Assignment</CardTitle></CardHeader>
                        <CardContent className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label className="text-sm font-medium">Class <span className="text-red-500">*</span></Label>
                                <Select defaultValue={String(student.class_id)} onValueChange={(v) => { setValue('class_id', Number(v)); setValue('section_id', null); }}>
                                    <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        {classes.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                                {errors.class_id && <p className="text-xs text-red-500">{errors.class_id.message}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label className="text-sm font-medium">Section</Label>
                                <Select defaultValue={student.section_id ? String(student.section_id) : undefined} onValueChange={(v) => setValue('section_id', Number(v))}>
                                    <SelectTrigger className="h-9"><SelectValue placeholder="Select section" /></SelectTrigger>
                                    <SelectContent>
                                        {visibleSections.map((s) => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                            </div>
                            <Field name="roll_no"         label="Roll No" />
                            <Field name="admission_date"  label="Admission Date" type="date" />
                        </CardContent>
                    </Card>

                    {/* Guardian */}
                    <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                        <CardHeader className="pb-3"><CardTitle className="text-sm">Guardian Information</CardTitle></CardHeader>
                        <CardContent className="grid grid-cols-2 gap-4">
                            <Field name="guardian.name"  label="Guardian Name" required />
                            <div className="space-y-1.5">
                                <Label className="text-sm font-medium">Relation</Label>
                                <Select defaultValue={student.guardian?.relation ?? 'Father'} onValueChange={(v) => setValue('guardian.relation', v)}>
                                    <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        {['Father','Mother','Guardian','Uncle','Aunt','Sibling'].map((r) => (
                                            <SelectItem key={r} value={r}>{r}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <Field name="guardian.phone"      label="Phone" />
                            <Field name="guardian.email"      label="Email" type="email" />
                            <Field name="guardian.occupation" label="Occupation" />
                            <div className="col-span-2 space-y-1.5">
                                <Label className="text-sm font-medium">Guardian Address</Label>
                                <Textarea rows={2} className="resize-none" {...register('guardian.address')} />
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex gap-3 justify-end">
                        <Button type="button" variant="outline" asChild>
                            <Link href={`/school/students/${student.id}`}>Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={isSubmitting} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                            {isSubmitting ? 'Saving…' : 'Save Changes'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
