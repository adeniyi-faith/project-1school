import { Head, useForm, usePage } from '@inertiajs/react';
import AuthLayout from '@/Layouts/AuthLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { MonitorCheck } from 'lucide-react';

/** Where applicants log in to an entrance exam with the exam number and PIN on their slip */
export default function ExamLogin() {
    const form = useForm({ exam_number: '', pin: '' });
    const flash = usePage().props.flash as { error?: string | null } | undefined;

    return (
        <AuthLayout>
            <Head title="Entrance exam" />
            <div className="flex min-h-dvh items-center justify-center px-4 py-10">
                <div className="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-slate-900">
                    <div className="mb-5 text-center">
                        <MonitorCheck className="mx-auto size-10 text-indigo-600" />
                        <h1 className="mt-3 text-xl font-semibold text-slate-900 dark:text-white">Entrance exam</h1>
                        <p className="mt-1 text-sm text-slate-500">Type the exam number and PIN printed on your exam slip.</p>
                    </div>
                    <form onSubmit={e => { e.preventDefault(); form.post('/exam/login'); }} className="space-y-4">
                        {flash?.error && <p className="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-950/30 dark:text-amber-200">{flash.error}</p>}
                        <div className="space-y-1.5">
                            <Label htmlFor="exam_number">Exam number</Label>
                            <Input id="exam_number" value={form.data.exam_number} onChange={e => form.setData('exam_number', e.target.value.toUpperCase())}
                                autoComplete="off" autoFocus placeholder="e.g. E12-004" className="h-11 font-mono text-lg uppercase" />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="pin">PIN</Label>
                            <Input id="pin" type="password" inputMode="numeric" value={form.data.pin} onChange={e => form.setData('pin', e.target.value)}
                                autoComplete="off" className="h-11 font-mono text-lg tracking-widest" />
                        </div>
                        {(form.errors.exam_number || form.errors.pin) && <p className="text-sm text-red-600">{form.errors.exam_number ?? form.errors.pin}</p>}
                        <Button type="submit" disabled={form.processing} className="h-11 w-full bg-indigo-600 text-white hover:bg-indigo-700">Continue</Button>
                    </form>
                </div>
            </div>
        </AuthLayout>
    );
}
