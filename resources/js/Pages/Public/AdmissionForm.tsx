import { useForm, usePage } from '@inertiajs/react';
import { GraduationCap, CheckCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface School { id: number; name: string; }
interface Props { school: School; }

export default function AdmissionForm({ school }: Props) {
    const { flash } = usePage<{ flash?: { success?: string } }>().props as any;

    const form = useForm({
        student_name: '', class_interested: '', guardian_name: '',
        guardian_phone: '', guardian_email: '', notes: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(`/apply/${school.id}`);
    }

    return (
        <div className="min-h-screen bg-gradient-to-br from-indigo-50 to-slate-100 dark:from-slate-900 dark:to-slate-800 flex items-center justify-center p-4">
            <div className="w-full max-w-lg">
                {/* Header */}
                <div className="text-center mb-8">
                    <div className="flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 mx-auto mb-4">
                        <GraduationCap className="w-8 h-8 text-white" />
                    </div>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white">{school.name}</h1>
                    <p className="text-slate-500 mt-1">Admission Inquiry Form</p>
                </div>

                {/* Success state */}
                {flash?.success ? (
                    <div className="bg-white dark:bg-slate-900 rounded-2xl shadow-lg p-8 text-center">
                        <CheckCircle className="w-14 h-14 text-green-500 mx-auto mb-4" />
                        <h2 className="text-lg font-semibold text-slate-900 dark:text-white mb-2">Application Submitted!</h2>
                        <p className="text-slate-500">{flash.success}</p>
                    </div>
                ) : (
                    <div className="bg-white dark:bg-slate-900 rounded-2xl shadow-lg p-8">
                        <form onSubmit={submit} className="space-y-5">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label>Student Name *</Label>
                                    <Input value={form.data.student_name} onChange={e => form.setData('student_name', e.target.value)} placeholder="Full name" />
                                    {form.errors.student_name && <p className="text-xs text-red-500 mt-1">{form.errors.student_name}</p>}
                                </div>
                                <div>
                                    <Label>Class Applying For *</Label>
                                    <Input value={form.data.class_interested} onChange={e => form.setData('class_interested', e.target.value)} placeholder="e.g. Grade 5" />
                                    {form.errors.class_interested && <p className="text-xs text-red-500 mt-1">{form.errors.class_interested}</p>}
                                </div>
                            </div>
                            <div>
                                <Label>Guardian / Parent Name *</Label>
                                <Input value={form.data.guardian_name} onChange={e => form.setData('guardian_name', e.target.value)} />
                                {form.errors.guardian_name && <p className="text-xs text-red-500 mt-1">{form.errors.guardian_name}</p>}
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label>Contact Phone *</Label>
                                    <Input value={form.data.guardian_phone} onChange={e => form.setData('guardian_phone', e.target.value)} />
                                    {form.errors.guardian_phone && <p className="text-xs text-red-500 mt-1">{form.errors.guardian_phone}</p>}
                                </div>
                                <div>
                                    <Label>Email</Label>
                                    <Input type="email" value={form.data.guardian_email} onChange={e => form.setData('guardian_email', e.target.value)} />
                                </div>
                            </div>
                            <div>
                                <Label>Additional Notes</Label>
                                <Textarea rows={3} value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} placeholder="Any questions or additional information…" />
                            </div>
                            <Button type="submit" disabled={form.processing} className="w-full">
                                {form.processing ? 'Submitting…' : 'Submit Application'}
                            </Button>
                        </form>
                    </div>
                )}

                <p className="text-center text-xs text-slate-400 mt-6">
                    Powered by <span className="font-medium text-slate-500">Genius SMS</span>
                </p>
            </div>
        </div>
    );
}
