import { useState } from 'react';
import { useForm, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { ChevronRight, Check, ArrowLeft } from 'lucide-react';
import type { Department, Designation } from '@/Types';

interface Props {
    departments: Department[];
    designations: Designation[];
}

const STEPS = ['Personal Info', 'Employment Details', 'Notes & Status'];

export default function StaffCreate({ departments, designations }: Props) {
    const [step, setStep] = useState(0);

    const { data, setData, post, processing, errors } = useForm({
        first_name:    '',
        last_name:     '',
        gender:        '',
        date_of_birth: '',
        blood_group:   '',
        religion:      '',
        nationality:   'Bangladeshi',
        phone:         '',
        email:         '',
        address:       '',
        department_id:  '',
        designation_id: '',
        joining_date:   '',
        salary_type:    'fixed',
        salary:         '',
        status:         'active',
        notes: '',
    });

    function next() { setStep(s => Math.min(s + 1, STEPS.length - 1)); }
    function prev() { setStep(s => Math.max(s - 1, 0)); }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/school/staff');
    }

    const filteredDesignations = data.department_id
        ? designations.filter(d => d.department_id === Number(data.department_id))
        : designations;

    return (
        <AppLayout title="Add Staff">
            <div className="max-w-3xl mx-auto space-y-6">
                <Link href="/school/staff" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors">
                    <ArrowLeft className="w-4 h-4" /> Back to Staff
                </Link>

                {/* Stepper */}
                <div className="flex items-center gap-0">
                    {STEPS.map((label, i) => (
                        <div key={label} className="flex items-center flex-1 last:flex-none">
                            <button
                                type="button"
                                onClick={() => i < step && setStep(i)}
                                className="flex items-center gap-2 group"
                            >
                                <div className={cn(
                                    'w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-colors',
                                    i < step   ? 'bg-indigo-600 text-white' :
                                    i === step ? 'bg-indigo-600 text-white ring-4 ring-indigo-100 dark:ring-indigo-950' :
                                                 'bg-slate-100 dark:bg-slate-800 text-slate-400',
                                )}>
                                    {i < step ? <Check className="w-4 h-4" /> : i + 1}
                                </div>
                                <span className={cn(
                                    'text-sm font-medium hidden sm:block',
                                    i === step ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400',
                                )}>{label}</span>
                            </button>
                            {i < STEPS.length - 1 && (
                                <div className={cn('flex-1 h-px mx-2', i < step ? 'bg-indigo-600' : 'bg-slate-200 dark:bg-slate-700')} />
                            )}
                        </div>
                    ))}
                </div>

                <form onSubmit={handleSubmit}>
                    {/* Step 1: Personal Info */}
                    {step === 0 && (
                        <Card className="border-slate-200 dark:border-slate-800">
                            <CardHeader>
                                <CardTitle className="text-lg">Personal Information</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-5">
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div className="space-y-1.5">
                                        <Label>First Name <span className="text-red-500">*</span></Label>
                                        <Input value={data.first_name} onChange={e => setData('first_name', e.target.value)} placeholder="First name" />
                                        {errors.first_name && <p className="text-xs text-red-500">{errors.first_name}</p>}
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>Last Name</Label>
                                        <Input value={data.last_name} onChange={e => setData('last_name', e.target.value)} placeholder="Last name" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div className="space-y-1.5">
                                        <Label>Gender <span className="text-red-500">*</span></Label>
                                        <Select value={data.gender} onValueChange={v => setData('gender', v)}>
                                            <SelectTrigger><SelectValue placeholder="Select gender" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="male">Male</SelectItem>
                                                <SelectItem value="female">Female</SelectItem>
                                                <SelectItem value="other">Other</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.gender && <p className="text-xs text-red-500">{errors.gender}</p>}
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>Date of Birth</Label>
                                        <Input type="date" value={data.date_of_birth} onChange={e => setData('date_of_birth', e.target.value)} />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div className="space-y-1.5">
                                        <Label>Blood Group</Label>
                                        <Select value={data.blood_group} onValueChange={v => setData('blood_group', v)}>
                                            <SelectTrigger><SelectValue placeholder="Blood group" /></SelectTrigger>
                                            <SelectContent>
                                                {['A+','A-','B+','B-','O+','O-','AB+','AB-'].map(b => <SelectItem key={b} value={b}>{b}</SelectItem>)}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>Religion</Label>
                                        <Input value={data.religion} onChange={e => setData('religion', e.target.value)} placeholder="Religion" />
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>Nationality</Label>
                                        <Input value={data.nationality} onChange={e => setData('nationality', e.target.value)} />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div className="space-y-1.5">
                                        <Label>Phone</Label>
                                        <Input value={data.phone} onChange={e => setData('phone', e.target.value)} placeholder="Phone number" />
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>Email</Label>
                                        <Input type="email" value={data.email} onChange={e => setData('email', e.target.value)} placeholder="Email address" />
                                        {errors.email && <p className="text-xs text-red-500">{errors.email}</p>}
                                    </div>
                                </div>

                                <div className="space-y-1.5">
                                    <Label>Address</Label>
                                    <Textarea value={data.address} onChange={e => setData('address', e.target.value)} rows={2} placeholder="Full address" />
                                </div>

                                <div className="flex justify-end">
                                    <Button type="button" onClick={next} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                                        Next <ChevronRight className="w-4 h-4" />
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Step 2: Employment */}
                    {step === 1 && (
                        <Card className="border-slate-200 dark:border-slate-800">
                            <CardHeader>
                                <CardTitle className="text-lg">Employment Details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-5">
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div className="space-y-1.5">
                                        <Label>Department</Label>
                                        <Select value={data.department_id} onValueChange={v => { setData('department_id', v); setData('designation_id', ''); }}>
                                            <SelectTrigger><SelectValue placeholder="Select department" /></SelectTrigger>
                                            <SelectContent>
                                                {departments.map(d => <SelectItem key={d.id} value={String(d.id)}>{d.name}</SelectItem>)}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>Designation</Label>
                                        <Select value={data.designation_id} onValueChange={v => setData('designation_id', v)}>
                                            <SelectTrigger><SelectValue placeholder="Select designation" /></SelectTrigger>
                                            <SelectContent>
                                                {filteredDesignations.map(d => <SelectItem key={d.id} value={String(d.id)}>{d.name}</SelectItem>)}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div className="space-y-1.5">
                                        <Label>Joining Date</Label>
                                        <Input type="date" value={data.joining_date} onChange={e => setData('joining_date', e.target.value)} />
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>Status <span className="text-red-500">*</span></Label>
                                        <Select value={data.status} onValueChange={v => setData('status', v)}>
                                            <SelectTrigger><SelectValue /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="active">Active</SelectItem>
                                                <SelectItem value="on_leave">On Leave</SelectItem>
                                                <SelectItem value="resigned">Resigned</SelectItem>
                                                <SelectItem value="terminated">Terminated</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.status && <p className="text-xs text-red-500">{errors.status}</p>}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div className="space-y-1.5">
                                        <Label>Salary Type <span className="text-red-500">*</span></Label>
                                        <Select value={data.salary_type} onValueChange={v => setData('salary_type', v)}>
                                            <SelectTrigger><SelectValue /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="fixed">Fixed Monthly</SelectItem>
                                                <SelectItem value="hourly">Hourly</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>Salary (BDT)</Label>
                                        <Input type="number" min="0" value={data.salary} onChange={e => setData('salary', e.target.value)} placeholder="e.g. 25000" />
                                        {errors.salary && <p className="text-xs text-red-500">{errors.salary}</p>}
                                    </div>
                                </div>

                                <div className="flex justify-between">
                                    <Button type="button" variant="outline" onClick={prev}>Back</Button>
                                    <Button type="button" onClick={next} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                                        Next <ChevronRight className="w-4 h-4" />
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Step 3: Notes & Submit */}
                    {step === 2 && (
                        <Card className="border-slate-200 dark:border-slate-800">
                            <CardHeader>
                                <CardTitle className="text-lg">Notes & Confirmation</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-5">
                                <div className="space-y-1.5">
                                    <Label>Internal Notes</Label>
                                    <Textarea
                                        value={data.notes}
                                        onChange={e => setData('notes', e.target.value)}
                                        rows={4}
                                        placeholder="Any internal notes about this staff member..."
                                    />
                                </div>

                                <div className="rounded-lg border border-slate-200 dark:border-slate-800 p-4 text-sm space-y-2">
                                    <p className="font-semibold text-slate-700 dark:text-slate-300 mb-3">Summary</p>
                                    <div className="grid grid-cols-2 gap-2 text-slate-600 dark:text-slate-400">
                                        <span>Name:</span>
                                        <span className="font-medium text-slate-900 dark:text-white">{data.first_name} {data.last_name}</span>
                                        <span>Gender:</span>
                                        <span className="capitalize">{data.gender}</span>
                                        <span>Department:</span>
                                        <span>{departments.find(d => String(d.id) === data.department_id)?.name ?? '—'}</span>
                                        <span>Designation:</span>
                                        <span>{designations.find(d => String(d.id) === data.designation_id)?.name ?? '—'}</span>
                                        <span>Status:</span>
                                        <span className="capitalize">{data.status.replace('_', ' ')}</span>
                                    </div>
                                </div>

                                <div className="flex justify-between">
                                    <Button type="button" variant="outline" onClick={prev}>Back</Button>
                                    <Button type="submit" disabled={processing} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                        {processing ? 'Registering...' : 'Register Staff'}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </form>
            </div>
        </AppLayout>
    );
}
