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
import { ArrowLeft } from 'lucide-react';
import type { Staff, Department, Designation } from '@/Types';

interface Props {
    staff: Staff;
    departments: Department[];
    designations: Designation[];
}

export default function StaffEdit({ staff, departments, designations }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        first_name:     staff.first_name,
        last_name:      staff.last_name ?? '',
        gender:         staff.gender,
        date_of_birth:  staff.date_of_birth ?? '',
        blood_group:    staff.blood_group ?? '',
        religion:       staff.religion ?? '',
        nationality:    staff.nationality ?? 'Bangladeshi',
        phone:          staff.phone ?? '',
        email:          staff.email ?? '',
        address:        staff.address ?? '',
        department_id:  staff.department_id ? String(staff.department_id) : '',
        designation_id: staff.designation_id ? String(staff.designation_id) : '',
        joining_date:   staff.joining_date ?? '',
        salary_type:    staff.salary_type,
        salary:         staff.salary ?? '',
        status:         staff.status,
        notes:          staff.notes ?? '',
    });

    const filteredDesignations = data.department_id
        ? designations.filter(d => d.department_id === Number(data.department_id))
        : designations;

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(`/school/staff/${staff.id}`);
    }

    return (
        <AppLayout title={`Edit — ${staff.full_name}`}>
            <div className="max-w-3xl mx-auto space-y-6">
                <Link href={`/school/staff/${staff.id}`} className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                    <ArrowLeft className="w-4 h-4" /> Back to Profile
                </Link>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card className="border-slate-200 dark:border-slate-800">
                        <CardHeader><CardTitle className="text-lg">Personal Information</CardTitle></CardHeader>
                        <CardContent className="space-y-5">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-1.5">
                                    <Label>First Name <span className="text-red-500">*</span></Label>
                                    <Input value={data.first_name} onChange={e => setData('first_name', e.target.value)} />
                                    {errors.first_name && <p className="text-xs text-red-500">{errors.first_name}</p>}
                                </div>
                                <div className="space-y-1.5">
                                    <Label>Last Name</Label>
                                    <Input value={data.last_name} onChange={e => setData('last_name', e.target.value)} />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-1.5">
                                    <Label>Gender <span className="text-red-500">*</span></Label>
                                    <Select value={data.gender} onValueChange={v => setData('gender', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
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
                                    <Input value={data.religion} onChange={e => setData('religion', e.target.value)} />
                                </div>
                                <div className="space-y-1.5">
                                    <Label>Nationality</Label>
                                    <Input value={data.nationality} onChange={e => setData('nationality', e.target.value)} />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-1.5">
                                    <Label>Phone</Label>
                                    <Input value={data.phone} onChange={e => setData('phone', e.target.value)} />
                                </div>
                                <div className="space-y-1.5">
                                    <Label>Email</Label>
                                    <Input type="email" value={data.email} onChange={e => setData('email', e.target.value)} />
                                    {errors.email && <p className="text-xs text-red-500">{errors.email}</p>}
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label>Address</Label>
                                <Textarea value={data.address} onChange={e => setData('address', e.target.value)} rows={2} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-slate-200 dark:border-slate-800">
                        <CardHeader><CardTitle className="text-lg">Employment Details</CardTitle></CardHeader>
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
                                    <Input type="number" min="0" value={data.salary} onChange={e => setData('salary', e.target.value)} />
                                    {errors.salary && <p className="text-xs text-red-500">{errors.salary}</p>}
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label>Internal Notes</Label>
                                <Textarea value={data.notes} onChange={e => setData('notes', e.target.value)} rows={3} />
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-3">
                        <Link href={`/school/staff/${staff.id}`}>
                            <Button type="button" variant="outline">Cancel</Button>
                        </Link>
                        <Button type="submit" disabled={processing} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                            {processing ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
