import { Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageHeader, Panel } from '@/components/app/kit';
import { naira } from '@/lib/format';
import { ArrowLeft, Award } from 'lucide-react';
import type { CertificateRow } from '@/Types';

interface Details {
    date_admitted: string | null;
    date_left: string;
    class_admitted: string | null;
    last_class: string | null;
    conduct: string;
    academic_ability: string | null;
    offices_held: string | null;
    activities: string | null;
    exams: string | null;
    reason_for_leaving: string | null;
    destination_school: string | null;
    fees_owed: number;
    fees_cleared: boolean;
    remark: string | null;
    show_photo: boolean;
}

interface Props {
    student: { id: number; name: string; admission_no: string | null; class: string | null; status: string; has_photo: boolean };
    type: 'testimonial' | 'transfer';
    title: string;
    defaults: Details;
    signers: { id: number; label: string; has_signature: boolean }[];
    conduct: string[];
    previous: CertificateRow[];
}

interface IssueForm {
    type: string;
    signer_id: string;
    mark_left: boolean;
    details: Details;
}

export default function IssueCertificate({ student, type, title, defaults, signers, conduct, previous }: Props) {
    const transfer = type === 'transfer';
    const form = useForm<IssueForm>({
        type,
        signer_id: signers[0] ? String(signers[0].id) : '',
        mark_left: student.status === 'active',
        details: { ...defaults, date_left: defaults.date_left ?? '' },
    });
    const d = form.data.details;
    const set = <K extends keyof Details>(key: K, value: Details[K]) => form.setData('details', { ...form.data.details, [key]: value });
    const err = (key: string) => (form.errors as Record<string, string | undefined>)[`details.${key}`];
    const conductItems = conduct.map(c => ({ value: c, label: c }));
    const signerItems = signers.map(s => ({ value: String(s.id), label: s.has_signature ? s.label : `${s.label} (no signature image)` }));
    const back = `/school/students/${student.id}?tab=certificates`;
    const valid = previous.filter(p => p.type === type && !p.revoked);

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.transform(data => ({ ...data, signer_id: data.signer_id || null }));
        form.post(`/school/students/${student.id}/certificates`);
    }

    const text = (key: 'offices_held' | 'activities' | 'exams' | 'reason_for_leaving' | 'destination_school', label: string, placeholder: string) => (
        <div className="space-y-1.5">
            <Label htmlFor={key}>{label}</Label>
            <Input id={key} value={d[key] ?? ''} onChange={e => set(key, e.target.value)} placeholder={placeholder} maxLength={300} />
            {err(key) && <p className="text-xs text-red-500">{err(key)}</p>}
        </div>
    );

    return (
        <AppLayout breadcrumbs={[{ label: 'Students', href: '/school/students' }, { label: student.name, href: back }, { label: `Issue ${title.toLowerCase()}` }]}>
            <div className="mx-auto max-w-3xl space-y-6 pb-24 md:pb-0">
                <PageHeader
                    title={`Issue ${title.toLowerCase()}`}
                    description={`For ${student.name}${student.admission_no ? ` (${student.admission_no})` : ''}. The details below come from the student's record; change anything that is not right before issuing.`}
                    actions={<Link href={back} className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200"><ArrowLeft className="size-4" /> Back</Link>}
                />

                {valid.length > 0 && (
                    <p className="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                        This student already has a valid {title.toLowerCase()} ({valid.map(v => v.serial).join(', ')}). To correct it, revoke the old one on the student's page after issuing this one.
                    </p>
                )}

                <form onSubmit={submit} className="space-y-6">
                    <Panel>
                        <p className="mb-4 font-medium text-slate-900 dark:text-white">Time at the school</p>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="date_admitted">Date admitted</Label>
                                <Input id="date_admitted" type="date" value={d.date_admitted ?? ''} onChange={e => set('date_admitted', e.target.value || null)} />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="date_left">Date left</Label>
                                <Input id="date_left" type="date" value={d.date_left} onChange={e => set('date_left', e.target.value)} />
                                {err('date_left') && <p className="text-xs text-red-500">{err('date_left')}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="class_admitted">Class admitted into</Label>
                                <Input id="class_admitted" value={d.class_admitted ?? ''} onChange={e => set('class_admitted', e.target.value)} maxLength={60} />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="last_class">Last class</Label>
                                <Input id="last_class" value={d.last_class ?? ''} onChange={e => set('last_class', e.target.value)} maxLength={60} />
                                {err('last_class') && <p className="text-xs text-red-500">{err('last_class')}</p>}
                            </div>
                        </div>
                    </Panel>

                    <Panel>
                        <p className="mb-4 font-medium text-slate-900 dark:text-white">{transfer ? 'Conduct and leaving' : 'Conduct and achievements'}</p>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label>Conduct and character</Label>
                                <Select items={conductItems} value={d.conduct} onValueChange={v => v && set('conduct', v)}>
                                    <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                    <SelectContent>{conductItems.map(c => <SelectItem key={c.value} value={c.value}>{c.label}</SelectItem>)}</SelectContent>
                                </Select>
                            </div>
                            {!transfer && (
                                <div className="space-y-1.5">
                                    <Label>Academic ability</Label>
                                    <Select items={conductItems} value={d.academic_ability ?? ''} onValueChange={v => set('academic_ability', v || null)}>
                                        <SelectTrigger className="w-full"><SelectValue placeholder="Choose" /></SelectTrigger>
                                        <SelectContent>{conductItems.map(c => <SelectItem key={c.value} value={c.value}>{c.label}</SelectItem>)}</SelectContent>
                                    </Select>
                                </div>
                            )}
                            {transfer && text('reason_for_leaving', 'Reason for leaving', 'e.g. Family moving to Abuja')}
                            {transfer && text('destination_school', 'School transferring to (if known)', 'e.g. Unity College, Abuja')}
                            {text('offices_held', 'Offices held', 'e.g. Class captain, Head girl')}
                            {text('activities', 'Clubs, sports and activities', 'e.g. Debate club, football team')}
                            {!transfer && text('exams', 'Examinations taken', 'e.g. WAEC SSCE May/June 2026')}
                        </div>
                        <div className="mt-4 space-y-1.5">
                            <Label htmlFor="remark">Remark (optional)</Label>
                            <Textarea id="remark" rows={2} maxLength={600} value={d.remark ?? ''} onChange={e => set('remark', e.target.value)} placeholder="e.g. A responsible and hardworking student." />
                        </div>
                        {transfer && (
                            <label className="mt-4 flex items-start gap-2 text-sm">
                                <Checkbox checked={d.fees_cleared} onCheckedChange={v => set('fees_cleared', !!v)} className="mt-0.5" />
                                <span>
                                    All school fees have been paid
                                    <span className="block text-xs text-slate-500">
                                        {defaults.fees_owed > 0 ? `The fee records show ${naira(defaults.fees_owed)} still owed.` : 'The fee records show nothing owed.'}
                                    </span>
                                </span>
                            </label>
                        )}
                    </Panel>

                    <Panel>
                        <p className="mb-4 font-medium text-slate-900 dark:text-white">Signing and printing</p>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label>Signed by</Label>
                                {signers.length === 0 ? (
                                    <p className="text-sm text-slate-500">Add signers on the Report Card Designs page. The certificate will have a blank signature line.</p>
                                ) : (
                                    <Select items={signerItems} value={form.data.signer_id} onValueChange={v => form.setData('signer_id', v ?? '')}>
                                        <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                        <SelectContent>{signerItems.map(s => <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>)}</SelectContent>
                                    </Select>
                                )}
                                <p className="text-xs text-slate-500">The signature image and school stamp come from the Report Card Designs page.</p>
                            </div>
                            <div className="space-y-3 pt-6">
                                <label className={`flex items-center gap-2 text-sm ${student.has_photo ? '' : 'opacity-50'}`}>
                                    <Checkbox checked={d.show_photo} disabled={!student.has_photo} onCheckedChange={v => set('show_photo', !!v)} />
                                    Print the student's photo{student.has_photo ? '' : ' (no photo uploaded)'}
                                </label>
                                {student.status === 'active' && (
                                    <label className="flex items-start gap-2 text-sm">
                                        <Checkbox checked={form.data.mark_left} onCheckedChange={v => form.setData('mark_left', !!v)} className="mt-0.5" />
                                        <span>
                                            Mark the student as {transfer ? 'transferred' : 'left (alumni)'}
                                            <span className="block text-xs text-slate-500">Their status changes from Active to {transfer ? 'Transferred' : 'Alumni'}.</span>
                                        </span>
                                    </label>
                                )}
                            </div>
                        </div>
                    </Panel>

                    <div className="flex justify-end gap-2">
                        <Link href={back} className="inline-flex h-9 items-center rounded-lg px-3.5 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/[0.06]">Cancel</Link>
                        <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700"><Award className="size-4" /> Issue {title.toLowerCase()}</Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
