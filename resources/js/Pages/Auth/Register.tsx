import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { router, usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import { useEffect, useState } from 'react';

import AuthShell, { Field, ghostButtonClass, headingClass, inputClass, primaryButtonClass } from '@/components/auth/AuthShell';
import { NIGERIAN_STATES } from '@/lib/nigeria';
import type { PageProps } from '@/Types';

const registerSchema = z
    .object({
        school_name: z.string().min(2, 'Enter your school\'s name'),
        state: z.string().min(1, 'Choose your state'),
        city: z.string().min(2, 'Enter your city or town'),
        address: z.string().optional(),
        name: z.string().min(2, 'Enter your full name'),
        email: z.string().email('Enter a valid email address'),
        phone: z
            .string()
            .optional()
            .refine((v) => !v || [10, 11].includes(v.replace(/\D/g, '').length), 'Enter a Nigerian phone number with 10 or 11 digits'),
        password: z.string().min(8, 'Use at least 8 characters'),
        password_confirmation: z.string().min(1, 'Repeat the password'),
        confirm_authorised: z.boolean().refine((v) => v, 'Please confirm to continue'),
    })
    .refine((d) => d.password === d.password_confirmation, {
        message: 'The two passwords do not match',
        path: ['password_confirmation'],
    });

type RegisterFormData = z.infer<typeof registerSchema>;
type FieldName = keyof RegisterFormData;

const STEP_ONE_FIELDS: FieldName[] = ['school_name', 'state', 'city', 'address'];
const ALL_FIELDS: FieldName[] = [...STEP_ONE_FIELDS, 'name', 'email', 'phone', 'password', 'password_confirmation'];
const STEPS = [
    { title: 'Tell us about your school', text: 'This sets up your school\'s account. You can change any of it later.' },
    { title: 'Your administrator account', text: 'This is the account you\'ll use to manage the school.' },
];

export default function Register() {
    const { flash, errors: serverErrors } = usePage<PageProps>().props;
    const [step, setStep] = useState(0);
    const [showPassword, setShowPassword] = useState(false);

    const {
        register,
        handleSubmit,
        setError,
        trigger,
        setFocus,
        formState: { errors, isSubmitting },
    } = useForm<RegisterFormData>({
        resolver: zodResolver(registerSchema),
        defaultValues: {
            school_name: '', state: '', city: '', address: '',
            name: '', email: '', phone: '', password: '', password_confirmation: '', confirm_authorised: false,
        },
    });

    useEffect(() => {
        if (flash?.error) toast.error(flash.error);
    }, [flash]);

    // Show messages from the server, and go back to step 1 if one of its fields is the problem.
    useEffect(() => {
        let backToStepOne = false;
        ALL_FIELDS.forEach((name) => {
            const message = serverErrors?.[name];
            if (message) {
                setError(name, { message });
                if (STEP_ONE_FIELDS.includes(name)) backToStepOne = true;
            }
        });
        if (backToStepOne) setStep(0);
    }, [serverErrors, setError]);

    const goToStep = (next: number) => {
        setStep(next);
        window.scrollTo({ top: 0 });
    };

    const continueToStepTwo = async () => {
        if (await trigger(STEP_ONE_FIELDS)) {
            goToStep(1);
        } else {
            const first = STEP_ONE_FIELDS.find((f) => errors[f]) ?? 'school_name';
            setFocus(first);
        }
    };

    const onSubmit = (data: RegisterFormData) => {
        const { confirm_authorised: _confirm, phone, ...rest } = data;
        // Phone is typed after the fixed +234, so drop a leading 0 and send the full international number.
        const digits = (phone ?? '').replace(/\D/g, '').replace(/^0/, '');

        router.post('/register', { ...rest, phone: digits ? `+234${digits}` : '' });
    };

    return (
        <AuthShell
            title="Register your school"
            topLink={{ href: '/login', label: 'Log in' }}
            heading="Bring your whole school online"
            text="Register once and your school gets its own private SchoolRuns account, ready for fees in naira."
            points={['Online fee payments and receipts', 'Results, report cards and attendance', 'AI lesson notes and exam questions', 'Logins for staff, parents and students']}
        >
            <div className="text-sm font-medium text-[#5A6482]">Step {step + 1} of 2</div>
            <h1 className={headingClass}>{STEPS[step].title}</h1>
            <p className="mt-2 text-base text-[#5A6482]">{STEPS[step].text}</p>

            <div className="mb-1 mt-[22px] grid grid-cols-2 gap-1.5" aria-hidden="true">
                {[0, 1].map((i) => (
                    <i key={i} className={`h-1 rounded ${i <= step ? 'bg-[#3346E0]' : 'bg-[#E9EDF5]'}`} />
                ))}
            </div>
            <div className="grid grid-cols-2 gap-1.5 text-[12.5px] text-[#5A6482]" aria-hidden="true">
                <span className={step === 0 ? 'font-semibold text-[#0E1A3A]' : ''}>School</span>
                <span className={step === 1 ? 'font-semibold text-[#0E1A3A]' : ''}>Your details</span>
            </div>

            <form onSubmit={handleSubmit(onSubmit)} className="mt-[26px] grid gap-[18px]" noValidate>
                {/* Step 1: the school. Hidden (not removed) on step 2 so its values are still sent. */}
                <div className={step === 0 ? 'grid gap-[18px]' : 'hidden'}>
                    <Field label="School name" htmlFor="school_name" error={errors.school_name?.message}>
                        <input id="school_name" type="text" autoComplete="organization" placeholder="e.g. Greenfield College" className={inputClass} aria-invalid={!!errors.school_name} {...register('school_name')} />
                    </Field>

                    <div className="grid gap-[18px] lg:grid-cols-2">
                        <Field label="State" htmlFor="state" error={errors.state?.message}>
                            <select id="state" className={`${inputClass} appearance-none bg-[url("data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20width='12'%20height='8'%20viewBox='0%200%2012%208'%3E%3Cpath%20d='M1%201l5%205%205-5'%20fill='none'%20stroke='%235A6482'%20stroke-width='1.8'/%3E%3C/svg%3E")] bg-[length:12px_8px] bg-[position:right_14px_center] bg-no-repeat pr-9`} aria-invalid={!!errors.state} {...register('state')}>
                                <option value="">Choose a state</option>
                                {NIGERIAN_STATES.map((s) => (
                                    <option key={s} value={s}>{s}</option>
                                ))}
                            </select>
                        </Field>
                        <Field label="City or town" htmlFor="city" error={errors.city?.message}>
                            <input id="city" type="text" autoComplete="address-level2" placeholder="e.g. Ikeja" className={inputClass} aria-invalid={!!errors.city} {...register('city')} />
                        </Field>
                    </div>

                    <Field label="School address (optional)" htmlFor="address" error={errors.address?.message}>
                        <input id="address" type="text" autoComplete="street-address" placeholder="Street and number" className={inputClass} {...register('address')} />
                    </Field>

                    <button type="button" className={primaryButtonClass} onClick={continueToStepTwo}>Continue</button>
                </div>

                {/* Step 2: the first administrator */}
                <div className={step === 1 ? 'grid gap-[18px]' : 'hidden'}>
                    <div className="rounded-xl bg-[#F5F7FC] px-3.5 py-3 text-sm text-[#2A3555]">
                        You'll be the school's first <b>administrator</b>. You can invite other admins, staff, parents and students once your school is set up.
                    </div>

                    <Field label="Your full name" htmlFor="name" error={errors.name?.message}>
                        <input id="name" type="text" autoComplete="name" className={inputClass} aria-invalid={!!errors.name} {...register('name')} />
                    </Field>

                    <Field label="Work email" htmlFor="email" error={errors.email?.message} hint="You'll use this to log in.">
                        <input id="email" type="email" inputMode="email" autoComplete="email" className={inputClass} aria-invalid={!!errors.email} {...register('email')} />
                    </Field>

                    <Field label="Phone number (optional)" htmlFor="phone" error={errors.phone?.message}>
                        <div className="flex overflow-hidden rounded-xl border-[1.5px] border-[#DCE1EE] bg-white focus-within:border-[#3346E0] focus-within:ring-[3px] focus-within:ring-[#3346E0]/15">
                            <span className="flex items-center border-r-[1.5px] border-[#DCE1EE] bg-[#F5F7FC] px-3.5 text-[15px] text-[#5A6482]">+234</span>
                            <input id="phone" type="tel" inputMode="numeric" autoComplete="tel-national" placeholder="803 000 0000" className="h-[49px] min-w-0 flex-1 bg-transparent px-3.5 text-base outline-none" {...register('phone')} />
                        </div>
                    </Field>

                    <Field label="Create a password" htmlFor="password" error={errors.password?.message} hint="At least 8 characters.">
                        <div className="relative">
                            <input id="password" type={showPassword ? 'text' : 'password'} autoComplete="new-password" className={`${inputClass} pr-[72px]`} aria-invalid={!!errors.password} {...register('password')} />
                            <button type="button" onClick={() => setShowPassword((v) => !v)} className="absolute right-1 top-1/2 h-11 -translate-y-1/2 px-3 text-sm font-semibold text-[#3346E0]">
                                {showPassword ? 'Hide' : 'Show'}
                            </button>
                        </div>
                    </Field>

                    <Field label="Repeat the password" htmlFor="password_confirmation" error={errors.password_confirmation?.message}>
                        <input id="password_confirmation" type={showPassword ? 'text' : 'password'} autoComplete="new-password" className={inputClass} aria-invalid={!!errors.password_confirmation} {...register('password_confirmation')} />
                    </Field>

                    <div className="grid gap-1.5">
                        <label className="grid cursor-pointer grid-cols-[22px_1fr] items-start gap-2.5 text-[14.5px] text-[#2A3555]">
                            <input type="checkbox" className="mt-px size-5 accent-[#3346E0]" {...register('confirm_authorised')} />
                            <span>I am authorised to register this school on SchoolRuns.</span>
                        </label>
                        {errors.confirm_authorised && <span role="alert" className="text-[13.5px] text-[#D0472F]">{errors.confirm_authorised.message}</span>}
                    </div>

                    <div className="grid grid-cols-[auto_1fr] gap-2.5">
                        <button type="button" className={ghostButtonClass} onClick={() => goToStep(0)}>Back</button>
                        <button type="submit" className={primaryButtonClass} disabled={isSubmitting}>
                            {isSubmitting ? 'Creating your school…' : 'Create school account'}
                        </button>
                    </div>
                </div>
            </form>

            <p className="mt-[26px] text-center text-[15px] text-[#5A6482]">
                Already registered?{' '}
                <a href="/login" className="font-medium text-[#3346E0] hover:underline">Log in</a>
            </p>
        </AuthShell>
    );
}
