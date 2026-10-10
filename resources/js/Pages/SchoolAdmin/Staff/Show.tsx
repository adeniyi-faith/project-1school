import { useState } from 'react';
import { Link, useForm, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog';
import { ArrowLeft, Pencil, User, Briefcase, FileText, Upload, Trash2 } from 'lucide-react';
import type { Staff, StaffDocument } from '@/Types';

interface Props {
    staff: Staff;
}

const statusColors: Record<string, string> = {
    active:     'bg-green-100 text-green-700',
    on_leave:   'bg-amber-100 text-amber-700',
    resigned:   'bg-slate-100 text-slate-600',
    terminated: 'bg-red-100 text-red-700',
};

function InfoRow({ label, value }: { label: string; value?: string | null }) {
    return (
        <div className="flex flex-col sm:flex-row sm:items-center gap-0.5 sm:gap-4 py-2.5 border-b border-slate-100 dark:border-slate-800 last:border-0">
            <span className="text-xs font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wide w-36 shrink-0">{label}</span>
            <span className="text-sm text-slate-900 dark:text-white">{value ?? '—'}</span>
        </div>
    );
}

export default function StaffShow({ staff }: Props) {
    const [docOpen, setDocOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm<{ title: string; file: File | null }>({
        title: '',
        file: null,
    });

    function handleDocUpload(e: React.FormEvent) {
        e.preventDefault();
        post(`/school/staff/${staff.id}/documents`, {
            forceFormData: true,
            onSuccess: () => { reset(); setDocOpen(false); },
        });
    }

    function handleDocDelete(doc: StaffDocument) {
        if (!confirm(`Delete document "${doc.title}"?`)) return;
        router.delete(`/school/staff/documents/${doc.id}`);
    }

    return (
        <AppLayout title={staff.full_name}>
            <div className="max-w-4xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <Link href="/school/staff" className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                        <ArrowLeft className="w-4 h-4" /> Back to Staff
                    </Link>
                    <Link href={`/school/staff/${staff.id}/edit`}>
                        <Button variant="outline" className="inline-flex items-center gap-2">
                            <Pencil className="w-4 h-4" /> Edit
                        </Button>
                    </Link>
                </div>

                {/* Profile card */}
                <Card className="border-slate-200 dark:border-slate-800">
                    <CardContent className="p-6">
                        <div className="flex items-start gap-5">
                            {staff.photo_url ? (
                                <img src={staff.photo_url} alt={staff.full_name} className="w-20 h-20 rounded-xl object-cover shrink-0" />
                            ) : (
                                <div className="w-20 h-20 rounded-xl bg-indigo-100 dark:bg-indigo-950 flex items-center justify-center text-2xl font-bold text-indigo-600 dark:text-indigo-400 shrink-0">
                                    {staff.first_name[0]}{staff.last_name?.[0] ?? ''}
                                </div>
                            )}
                            <div className="flex-1 min-w-0">
                                <h1 className="text-xl font-bold text-slate-900 dark:text-white">{staff.full_name}</h1>
                                <p className="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                                    {staff.designation?.name ?? 'No Designation'}
                                    {staff.department && ` · ${staff.department.name}`}
                                </p>
                                <div className="flex flex-wrap gap-2 mt-3">
                                    <Badge className={`border-0 ${statusColors[staff.status] ?? ''}`}>
                                        {staff.status.replace('_', ' ')}
                                    </Badge>
                                    <Badge variant="outline" className="font-mono text-xs">{staff.emp_id}</Badge>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Tabs */}
                <Tabs defaultValue="personal">
                    <TabsList className="w-full sm:w-auto">
                        <TabsTrigger value="personal" className="flex items-center gap-1.5"><User className="w-3.5 h-3.5" /> Personal</TabsTrigger>
                        <TabsTrigger value="employment" className="flex items-center gap-1.5"><Briefcase className="w-3.5 h-3.5" /> Employment</TabsTrigger>
                        <TabsTrigger value="documents" className="flex items-center gap-1.5"><FileText className="w-3.5 h-3.5" /> Documents ({staff.documents?.length ?? 0})</TabsTrigger>
                    </TabsList>

                    <TabsContent value="personal" className="mt-4">
                        <Card className="border-slate-200 dark:border-slate-800">
                            <CardContent className="p-6">
                                <InfoRow label="Full Name"     value={staff.full_name} />
                                <InfoRow label="Gender"        value={staff.gender ? (staff.gender.charAt(0).toUpperCase() + staff.gender.slice(1)) : undefined} />
                                <InfoRow label="Date of Birth" value={staff.date_of_birth ? new Date(staff.date_of_birth).toLocaleDateString() : undefined} />
                                <InfoRow label="Blood Group"   value={staff.blood_group} />
                                <InfoRow label="Religion"      value={staff.religion} />
                                <InfoRow label="Nationality"   value={staff.nationality} />
                                <InfoRow label="Phone"         value={staff.phone} />
                                <InfoRow label="Email"         value={staff.email} />
                                <InfoRow label="Address"       value={staff.address} />
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="employment" className="mt-4">
                        <Card className="border-slate-200 dark:border-slate-800">
                            <CardContent className="p-6">
                                <InfoRow label="Employee ID"   value={staff.emp_id} />
                                <InfoRow label="Department"    value={staff.department?.name} />
                                <InfoRow label="Designation"   value={staff.designation?.name} />
                                <InfoRow label="Joining Date"  value={staff.joining_date ? new Date(staff.joining_date).toLocaleDateString() : undefined} />
                                <InfoRow label="Salary Type"   value={staff.salary_type === 'fixed' ? 'Fixed Monthly' : 'Hourly'} />
                                <InfoRow label="Salary"        value={staff.salary ? `BDT ${Number(staff.salary).toLocaleString()}` : undefined} />
                                <InfoRow label="Status"        value={staff.status.replace('_', ' ')} />
                                {staff.notes && <InfoRow label="Notes" value={staff.notes} />}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="documents" className="mt-4">
                        <Card className="border-slate-200 dark:border-slate-800">
                            <CardHeader className="pb-3 flex flex-row items-center justify-between">
                                <CardTitle className="text-base">Documents</CardTitle>
                                <Button size="sm" onClick={() => setDocOpen(true)} className="bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-2">
                                    <Upload className="w-3.5 h-3.5" /> Upload
                                </Button>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {!staff.documents || staff.documents.length === 0 ? (
                                    <p className="text-sm text-slate-400 py-4 text-center">No documents uploaded yet.</p>
                                ) : staff.documents.map((doc) => (
                                    <div key={doc.id} className="flex items-center justify-between p-3 rounded-lg border border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors">
                                        <div>
                                            <p className="text-sm font-medium text-slate-900 dark:text-white">{doc.title}</p>
                                            <p className="text-xs text-slate-400 mt-0.5">
                                                {doc.file_type} · {doc.file_size ? (doc.file_size / 1024).toFixed(1) + ' KB' : '—'} · {new Date(doc.created_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                        <Button variant="ghost" size="icon" className="w-8 h-8 text-red-500 hover:text-red-700" onClick={() => handleDocDelete(doc)}>
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>

            {/* Upload Document Dialog */}
            <Dialog open={docOpen} onOpenChange={setDocOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Upload Document</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleDocUpload} className="space-y-4 mt-2">
                        <div className="space-y-1.5">
                            <Label>Document Title <span className="text-red-500">*</span></Label>
                            <Input value={data.title} onChange={e => setData('title', e.target.value)} placeholder="e.g. NID Copy, Certificate" />
                            {errors.title && <p className="text-xs text-red-500">{errors.title}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label>File <span className="text-red-500">*</span></Label>
                            <Input type="file" accept=".pdf,.jpg,.jpeg,.png" onChange={e => setData('file', e.target.files?.[0] ?? null)} />
                            <p className="text-xs text-slate-400">PDF, JPG, or PNG · max 5 MB</p>
                            {errors.file && <p className="text-xs text-red-500">{errors.file}</p>}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setDocOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={processing} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {processing ? 'Uploading...' : 'Upload'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
