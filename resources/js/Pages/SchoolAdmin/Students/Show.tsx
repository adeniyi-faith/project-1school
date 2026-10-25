import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Award, Banknote, Mail, ScrollText, Pencil, FileUp, Trash2, FileText, User, GraduationCap, Users, Camera, IdCard, Shield, Printer, RefreshCw } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { zodResolver } from '@hookform/resolvers/zod';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { CertificateList } from '@/components/certificates/CertificateList';
import { ACTIVITY_LABELS, StudentActivityPanel, type ActivityKey, type StudentActivity } from '@/components/students/StudentActivity';
import type { CertificateRow, ClassHistoryLine, PageProps, Student } from '@/Types';

interface IdCardRow { id: number; card_no: string; state: 'valid' | 'expired' | 'replaced' | 'revoked'; expires_on: string | null; issued_on: string | null; issued_by: string | null; note: string | null; prints: number }
type Props = PageProps & {
    student: Student; classHistory: ClassHistoryLine[]; canEdit: boolean; certificates: CertificateRow[]; canIssueCertificates: boolean;
    clubs: { id: number; name: string | null; role: string }[];
    houses: { id: number; name: string; colour: string }[] | null;
    idCards: IdCardRow[] | null;
    activity: StudentActivity;
    quick: { collect: string | null; message: string | null; reportCard: string | null };
};

const CARD_STATE: Record<IdCardRow['state'], string> = {
    valid: 'bg-emerald-100 text-emerald-700', expired: 'bg-amber-100 text-amber-700', replaced: 'bg-slate-100 text-slate-600', revoked: 'bg-red-100 text-red-700',
};

const MOVE_LABELS: Record<ClassHistoryLine['outcome'], string> = { promoted: 'Moved up', repeated: 'Repeated', graduated: 'Graduated' };

const statusColors: Record<string, string> = {
    active:      'bg-emerald-100 text-emerald-700',
    alumni:      'bg-blue-100 text-blue-700',
    transferred: 'bg-amber-100 text-amber-700',
    inactive:    'bg-slate-100 text-slate-600',
};

const docSchema = z.object({
    title: z.string().min(1, 'Title required'),
    file:  z.instanceof(FileList).refine((f) => f.length > 0, 'File required'),
});
type DocForm = z.infer<typeof docSchema>;

export default function ShowStudent() {
    const { student, classHistory, canEdit, certificates, canIssueCertificates, clubs, houses, idCards, activity, quick } = usePage<Props>().props;
    type Tab = 'personal' | 'guardian' | 'documents' | 'history' | 'certificates' | 'id card' | ActivityKey;
    // Hostel, library, bus, fees ... tabs appear only for the parts this person is allowed to see
    const activityTabs = (Object.keys(ACTIVITY_LABELS) as ActivityKey[]).filter(k => activity?.[k] != null);
    const tabs: Tab[] = ['personal', 'guardian', 'documents', ...activityTabs, 'history', 'certificates', ...(idCards ? ['id card' as const] : [])];
    const boarding = activity?.hostel?.find(a => a.status === 'active');
    const [tab, setTab]       = useState<Tab>(() => {
        const asked = typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('tab') : null;
        if (asked && (Object.keys(ACTIVITY_LABELS) as string[]).includes(asked) && activity?.[asked as ActivityKey] != null) return asked as ActivityKey;
        return asked === 'certificates' ? 'certificates' : asked === 'id-card' && idCards ? 'id card' : 'personal';
    });
    const [replacing, setReplacing] = useState(false);
    const [reason, setReason] = useState('');
    const currentCard = idCards?.find(c => c.state === 'valid' || c.state === 'expired') ?? null;
    const uploadPhoto = (f: File | undefined) => { if (f) router.post(`/school/students/${student.id}/photo`, { photo: f }, { forceFormData: true, preserveScroll: true }); };
    const [docOpen, setDocOpen] = useState(false);

    const { register, handleSubmit, reset, formState: { errors, isSubmitting } } =
        useForm<DocForm>({ resolver: zodResolver(docSchema) });

    const uploadDoc = (data: DocForm) => {
        const form = new FormData();
        form.append('title', data.title);
        form.append('file',  data.file[0]);
        router.post(`/school/students/${student.id}/documents`, form, {
            forceFormData: true,
            onSuccess: () => { setDocOpen(false); reset(); },
        });
    };

    const InfoRow = ({ label, value }: { label: string; value?: string | null }) => (
        <div>
            <p className="text-xs text-slate-400 uppercase tracking-wide">{label}</p>
            <p className="min-w-0 break-words text-sm font-medium text-slate-800 dark:text-slate-200 mt-0.5">{value || '—'}</p>
        </div>
    );

    return (
        <AppLayout breadcrumbs={[
            { label: 'Students', href: '/school/students' },
            { label: student.full_name },
        ]}>
            <Head title={student.full_name} />

            {/* Header */}
            <div className="mb-6 flex items-start gap-3">
                <Button variant="ghost" size="icon" asChild className="-ml-2 shrink-0">
                    <Link href="/school/students" aria-label="Back to students"><ArrowLeft className="w-4 h-4" /></Link>
                </Button>
                <label className={`group relative flex h-24 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-indigo-50 text-2xl font-bold text-indigo-600 dark:bg-indigo-950/40 ${canEdit ? 'cursor-pointer' : ''}`}
                    title={canEdit ? 'Change photo (JPG or PNG, up to 2 MB)' : undefined}>
                    {student.photo_url
                        ? <img src={student.photo_url} className="h-24 w-20 object-cover" alt={`Photo of ${student.full_name}`} />
                        : student.first_name[0].toUpperCase()
                    }
                    {canEdit && (
                        <>
                            <span className="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity group-hover:opacity-100"><Camera className="size-5 text-white" /></span>
                            <input type="file" accept="image/png,image/jpeg" className="sr-only" onChange={e => uploadPhoto(e.target.files?.[0])} />
                        </>
                    )}
                </label>
                <div className="min-w-0 flex-1">
                    <h1 className="text-xl font-bold leading-tight text-slate-900 dark:text-white">{student.full_name}</h1>
                    <div className="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                        <span className="font-mono text-sm text-slate-500">{student.admission_no}</span>
                        <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${statusColors[student.status] ?? statusColors.inactive}`}>
                            {student.status}
                        </span>
                    </div>
                    {boarding && (
                        <button type="button" onClick={() => setTab('hostel')} className="mt-1 block text-left text-sm text-slate-600 hover:underline dark:text-slate-300">
                            Boarder · {boarding.hostel} · Room {boarding.room}{boarding.bed ? ` · ${/^bed/i.test(boarding.bed) ? boarding.bed : `Bed ${boarding.bed}`}` : ''}
                        </button>
                    )}
                    {canEdit && (
                        <label className="mt-2 inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/5">
                            <Camera className="size-3.5" /> {student.photo_url ? 'Change photo' : 'Upload photo'}
                            <input type="file" accept="image/png,image/jpeg" className="sr-only" onChange={e => uploadPhoto(e.target.files?.[0])} />
                        </label>
                    )}
                    {canEdit && !student.photo_url && <p className="mt-1 text-xs text-slate-500">A passport photo on a plain background. It is printed on the ID card and report card.</p>}
                </div>
                {canEdit && (
                    <Button size="sm" asChild className="shrink-0 gap-2 bg-indigo-600 text-white hover:bg-indigo-700">
                        <Link href={`/school/students/${student.id}/edit`}>
                            <Pencil className="w-4 h-4" /> <span className="max-sm:sr-only">Edit</span>
                        </Link>
                    </Button>
                )}
            </div>

            {/* Shortcuts for the jobs staff most often come here to do */}
            {(quick.collect || quick.message || quick.reportCard) && (
                <div className="mb-6 flex flex-wrap gap-2">
                    {quick.collect && (
                        <Button size="sm" variant="outline" asChild className="gap-2 max-md:h-9">
                            <Link href={quick.collect}><Banknote className="size-4" /> Collect payment</Link>
                        </Button>
                    )}
                    {quick.message && (
                        <Button size="sm" variant="outline" asChild className="gap-2 max-md:h-9">
                            <Link href={quick.message}><Mail className="size-4" /> Message parent</Link>
                        </Button>
                    )}
                    {quick.reportCard && (
                        <Button size="sm" variant="outline" asChild className="gap-2 max-md:h-9">
                            <a href={quick.reportCard} target="_blank" rel="noreferrer"><ScrollText className="size-4" /> Print report card</a>
                        </Button>
                    )}
                </div>
            )}

            {/* Quick stats */}
            <div className="mb-6 grid grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-4">
                {[
                    { icon: GraduationCap, label: 'Class', value: student.school_class?.name ?? '—' },
                    { icon: Users,         label: 'Arm', value: student.section?.name ?? '—' },
                    { icon: Shield,        label: 'House', value: student.house?.name ?? '—' },
                    { icon: FileText,      label: 'Documents', value: String(student.documents?.length ?? 0) },
                ].map((s) => (
                    <Card key={s.label} className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                        <CardContent className="flex items-center gap-3 p-3 sm:p-4">
                            <div className="max-sm:hidden w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 flex items-center justify-center">
                                <s.icon className="w-4 h-4 text-indigo-500" />
                            </div>
                            <div>
                                <p className="text-xs text-slate-500">{s.label}</p>
                                <p className="text-sm font-semibold text-slate-900 dark:text-white">{s.value}</p>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>

            {/* Tabs */}
            <div className="flex gap-1 mb-4 overflow-x-auto border-b border-slate-200 dark:border-slate-800">
                {tabs.map((t) => (
                    <button
                        key={t}
                        onClick={() => setTab(t)}
                        className={`shrink-0 px-4 py-2 text-sm font-medium capitalize border-b-2 transition-colors ${tab === t ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'}`}
                    >{t === 'history' ? 'Class history' : t === 'id card' ? 'ID card' : t in ACTIVITY_LABELS ? ACTIVITY_LABELS[t as ActivityKey] : t}</button>
                ))}
            </div>

            {activityTabs.includes(tab as ActivityKey) && <StudentActivityPanel kind={tab as ActivityKey} data={activity} />}

            {/* Personal tab */}
            {tab === 'personal' && (
                <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <CardHeader className="pb-3"><CardTitle className="text-sm">Personal Details</CardTitle></CardHeader>
                    <CardContent className="grid grid-cols-2 gap-x-4 gap-y-4 sm:grid-cols-3 sm:gap-x-6">
                        <InfoRow label="Full Name"      value={student.full_name} />
                        <InfoRow label="Gender"         value={student.gender} />
                        <InfoRow label="Date of Birth"  value={student.date_of_birth ? new Date(student.date_of_birth).toLocaleDateString() : null} />
                        <InfoRow label="Blood Group"    value={student.blood_group} />
                        <InfoRow label="Religion"       value={student.religion} />
                        <InfoRow label="Nationality"    value={student.nationality} />
                        <InfoRow label="Phone"          value={student.phone} />
                        <InfoRow label="Email"          value={student.email} />
                        <InfoRow label="Category"       value={student.category} />
                        <InfoRow label="Roll No"        value={student.roll_no} />
                        <InfoRow label="Admission Date" value={student.admission_date ? new Date(student.admission_date).toLocaleDateString() : null} />
                        <InfoRow label="Previous School" value={student.previous_school} />
                        {houses ? (
                            <div>
                                <p className="text-xs text-slate-400 uppercase tracking-wide">House</p>
                                <select aria-label="House" className="mt-0.5 w-full rounded-md border border-slate-200 bg-transparent px-2 py-1 text-sm dark:border-white/10"
                                    value={student.house_id ?? ''}
                                    onChange={e => router.post('/school/houses/assign', { houses: { [student.id]: e.target.value || null } }, { preserveScroll: true })}>
                                    <option value="">No house</option>
                                    {houses.map(h => <option key={h.id} value={h.id}>{h.name}</option>)}
                                </select>
                            </div>
                        ) : <InfoRow label="House" value={student.house?.name} />}
                        <InfoRow label="Clubs" value={clubs.map(c => (c.role !== 'Member' ? `${c.name} (${c.role})` : c.name)).join(', ')} />
                        {student.address && (
                            <div className="col-span-2 sm:col-span-3">
                                <p className="text-xs text-slate-400 uppercase tracking-wide">Address</p>
                                <p className="text-sm text-slate-800 dark:text-slate-200 mt-0.5">{student.address}</p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            )}

            {/* Guardian tab */}
            {tab === 'guardian' && (
                <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <CardHeader className="pb-3"><CardTitle className="text-sm">Guardian Details</CardTitle></CardHeader>
                    <CardContent className="grid grid-cols-2 gap-x-4 gap-y-4 sm:grid-cols-3 sm:gap-x-6">
                        {student.guardian ? (
                            <>
                                <InfoRow label="Name"       value={student.guardian.name} />
                                <InfoRow label="Relation"   value={student.guardian.relation} />
                                <InfoRow label="Phone"      value={student.guardian.phone} />
                                <InfoRow label="Email"      value={student.guardian.email} />
                                <InfoRow label="Occupation" value={student.guardian.occupation} />
                                {student.guardian.address && (
                                    <div className="col-span-2 sm:col-span-3">
                                        <p className="text-xs text-slate-400 uppercase tracking-wide">Address</p>
                                        <p className="text-sm text-slate-800 dark:text-slate-200 mt-0.5">{student.guardian.address}</p>
                                    </div>
                                )}
                            </>
                        ) : (
                            <p className="text-sm text-slate-400 col-span-2 sm:col-span-3">No guardian information.</p>
                        )}
                    </CardContent>
                </Card>
            )}

            {/* Documents tab */}
            {tab === 'documents' && (
                <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <CardHeader className="pb-3 flex-row items-center justify-between">
                        <CardTitle className="text-sm">Documents</CardTitle>
                        {canEdit && (
                            <Button size="sm" variant="outline" onClick={() => setDocOpen(true)} className="inline-flex items-center gap-2">
                                <FileUp className="w-3.5 h-3.5" /> Upload
                            </Button>
                        )}
                    </CardHeader>
                    <CardContent>
                        {!student.documents?.length ? (
                            <p className="text-sm text-slate-400">No documents uploaded yet.</p>
                        ) : (
                            <div className="space-y-2">
                                {student.documents.map((doc) => (
                                    <div key={doc.id} className="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-slate-800">
                                        <div className="flex items-center gap-2">
                                            <FileText className="w-4 h-4 text-slate-400 shrink-0" />
                                            <div>
                                                <p className="text-sm font-medium text-slate-700 dark:text-slate-300">{doc.title}</p>
                                                {doc.file_size && <p className="text-xs text-slate-400">{(doc.file_size / 1024).toFixed(1)} KB</p>}
                                            </div>
                                        </div>
                                        {canEdit && (
                                            <Button variant="ghost" size="icon" className="h-8 w-8 text-red-500 hover:text-red-600"
                                                onClick={() => { if (confirm('Delete this document?')) router.delete(`/school/students/documents/${doc.id}`); }}>
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </Button>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            )}

            {/* Class history tab: end-of-year moves */}
            {tab === 'history' && (
                <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <CardHeader className="pb-3"><CardTitle className="text-sm">Class history</CardTitle></CardHeader>
                    <CardContent>
                        {!classHistory.length ? (
                            <p className="text-sm text-slate-400">No end-of-year moves yet.</p>
                        ) : (
                            <ol className="space-y-3">
                                {classHistory.map((h) => (
                                    <li key={h.id} className="rounded-lg bg-slate-50 p-3 dark:bg-slate-800">
                                        <p className="text-sm font-medium text-slate-800 dark:text-slate-200">
                                            {h.year}: {MOVE_LABELS[h.outcome]}{' '}
                                            {h.outcome === 'promoted' ? `from ${h.from} to ${h.to}${h.to_section ? ` (${h.to_section})` : ''}` : h.outcome === 'repeated' ? h.from : `from ${h.from}`}
                                        </p>
                                        <p className="text-xs text-slate-500">
                                            {h.year_average !== null ? `Year average ${h.year_average.toFixed(1)}% · ` : ''}{h.by ? `by ${h.by} · ` : ''}{h.at}
                                        </p>
                                        {h.reason && <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">{h.reason}</p>}
                                    </li>
                                ))}
                            </ol>
                        )}
                    </CardContent>
                </Card>
            )}

            {/* Certificates tab: testimonials and transfer certificates */}
            {tab === 'certificates' && (
                <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-2 pb-3">
                        <CardTitle className="text-sm">Certificates</CardTitle>
                        {canIssueCertificates && (
                            <div className="flex flex-wrap gap-2">
                                <Link href={`/school/students/${student.id}/certificates/new?type=testimonial`} className="inline-flex h-7 items-center gap-1 rounded-lg bg-indigo-600 px-2.5 text-[0.8rem] font-medium max-md:h-9 text-white hover:bg-indigo-700">
                                    <Award className="size-3.5" /> Issue testimonial
                                </Link>
                                <Link href={`/school/students/${student.id}/certificates/new?type=transfer`} className="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 px-2.5 text-[0.8rem] font-medium max-md:h-9 text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:text-slate-200">
                                    <Award className="size-3.5" /> Issue transfer certificate
                                </Link>
                            </div>
                        )}
                    </CardHeader>
                    <CardContent>
                        {!certificates.length ? (
                            <p className="text-sm text-slate-400">No testimonial or transfer certificate has been issued for this student.</p>
                        ) : (
                            <CertificateList items={certificates} canRevoke={canIssueCertificates} />
                        )}
                    </CardContent>
                </Card>
            )}

            {/* ID card tab */}
            {tab === 'id card' && idCards && (
                <Card className="dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-2 pb-3">
                        <CardTitle className="text-sm">ID card</CardTitle>
                        <div className="flex flex-wrap gap-2">
                            {currentCard ? (
                                <>
                                    <a href={`/school/students/${student.id}/id-card`} target="_blank" rel="noreferrer" className="inline-flex h-7 items-center gap-1 rounded-lg bg-indigo-600 px-2.5 text-[0.8rem] font-medium max-md:h-9 text-white hover:bg-indigo-700"><Printer className="size-3.5" /> Card (PDF)</a>
                                    <a href={`/school/students/${student.id}/id-card?layout=sheet`} target="_blank" rel="noreferrer" className="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 px-2.5 text-[0.8rem] font-medium max-md:h-9 text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:text-slate-200">On A4 paper</a>
                                    <Button size="sm" variant="outline" onClick={() => { setReason(''); setReplacing(true); }}><RefreshCw className="size-3.5" /> Replace card</Button>
                                </>
                            ) : (
                                <Button size="sm" onClick={() => router.post(`/school/students/${student.id}/id-card`, {}, { preserveScroll: true })} className="bg-indigo-600 text-white hover:bg-indigo-700"><IdCard className="size-3.5" /> Make card</Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {!student.photo_url && <p className="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">This student has no photo yet, so the card will show their initials. Use "Upload photo" at the top first.</p>}
                        {currentCard?.state === 'expired' && <p className="text-sm text-amber-700">The card has expired. Use "Replace card" to make a new one.</p>}
                        {!idCards.length ? (
                            <p className="text-sm text-slate-400">No card has been made for this student yet.</p>
                        ) : (
                            <ul className="divide-y divide-slate-100 text-sm dark:divide-white/[0.06]">
                                {idCards.map(c => (
                                    <li key={c.id} className="flex flex-wrap items-center justify-between gap-2 py-2">
                                        <div>
                                            <span className="font-mono">{c.card_no}</span>
                                            <span className="block text-xs text-slate-500">Made {c.issued_on}{c.issued_by ? ` by ${c.issued_by}` : ''}{c.expires_on ? ` · valid until ${c.expires_on}` : ''} · printed {c.prints} time{c.prints === 1 ? '' : 's'}{c.note && c.state !== 'valid' ? ` · ${c.note}` : ''}</span>
                                        </div>
                                        <span className={`rounded-full px-2 py-0.5 text-xs font-medium capitalize ${CARD_STATE[c.state]}`}>{c.state}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            )}

            <Dialog open={replacing} onOpenChange={setReplacing}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Replace ID card</DialogTitle></DialogHeader>
                    <form onSubmit={e => { e.preventDefault(); router.post(`/school/students/${student.id}/id-card`, { reason }, { preserveScroll: true, onSuccess: () => setReplacing(false) }); }} className="space-y-4 pt-2">
                        <p className="text-sm text-slate-500">A new card number and QR code are made. The old card stops working when scanned, so a lost card cannot be used by someone else.</p>
                        <div className="space-y-1.5">
                            <Label htmlFor="replace-reason">Why?</Label>
                            <Input id="replace-reason" value={reason} onChange={e => setReason(e.target.value)} placeholder="e.g. Lost, damaged, new class" />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setReplacing(false)}>Cancel</Button>
                            <Button type="submit" disabled={!reason.trim()} className="bg-indigo-600 hover:bg-indigo-700 text-white">Make new card</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Upload dialog */}
            <Dialog open={docOpen} onOpenChange={setDocOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Upload Document</DialogTitle></DialogHeader>
                    <form onSubmit={handleSubmit(uploadDoc)} className="space-y-4 pt-2">
                        <div className="space-y-1.5">
                            <Label>Document Title <span className="text-red-500">*</span></Label>
                            <Input placeholder="e.g. Birth Certificate" {...register('title')} />
                            {errors.title && <p className="text-xs text-red-500">{errors.title.message}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label>File <span className="text-red-500">*</span></Label>
                            <Input type="file" accept=".pdf,.jpg,.jpeg,.png" {...register('file')} />
                            {errors.file && <p className="text-xs text-red-500">{errors.file.message as string}</p>}
                            <p className="text-xs text-slate-400">PDF, JPG, PNG · max 5 MB</p>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setDocOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={isSubmitting} className="bg-indigo-600 hover:bg-indigo-700 text-white">Upload</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
