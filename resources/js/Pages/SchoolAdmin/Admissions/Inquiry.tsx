import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { PageHeader, Panel, Pill } from '@/components/app/kit';
import { InquiryStatusPill } from '@/components/admissions/InquiryStatus';
import { cn } from '@/lib/utils';
import { ArrowLeft, Check, ClipboardCheck, GraduationCap, MessagesSquare, Pencil, Plus, Trash2, X } from 'lucide-react';
import type { AdmissionAssessment, AssessmentOutcome, InquiryStatus } from '@/Types';

interface Props {
    inquiry: {
        id: number; student_name: string; class_interested: string; guardian_name: string; guardian_phone: string;
        guardian_email: string | null; status: InquiryStatus; source: string; notes: string | null; created_at: string | null;
        decision: { by: string | null; at: string; note: string | null } | null;
        enrolled: { id: number; name: string; admission_no: string; class: string | null; by: string | null; at: string | null } | null;
        followups: { id: number; note: string; next_date: string | null; by: string | null; at: string | null }[];
    };
    assessments: AdmissionAssessment[];
    matchingGuardians: { id: number; name: string; phone: string; relation: string | null; children: string[] }[];
    classes: { id: number; name: string }[];
    sections: { id: number; class_id: number; name: string }[];
    can: { manage: boolean; enrol: boolean };
}

const OUTCOME_ITEMS: { value: AssessmentOutcome; label: string }[] = [
    { value: 'pending', label: 'Not done yet' },
    { value: 'passed', label: 'Passed' },
    { value: 'failed', label: 'Did not pass' },
    { value: 'absent', label: 'Did not attend' },
];
const OUTCOME_TONE = { pending: 'neutral', passed: 'good', failed: 'bad', absent: 'warn' } as const;
const NEW_GUARDIAN = 'new';

type AssessmentForm = {
    type: 'exam' | 'interview'; scheduled_at: string; venue: string; score: string; max_score: string; outcome: AssessmentOutcome; remarks: string;
};
type EnrolForm = {
    first_name: string; last_name: string; gender: string; date_of_birth: string; admission_date: string; previous_school: string;
    class_id: string; section_id: string; guardian_id: string; guardian_relation: string;
};

function ErrorText({ children }: { children?: string }) {
    return children ? <p className="text-xs text-red-500">{children}</p> : null;
}

function today() {
    return new Date().toISOString().slice(0, 10);
}

export default function Inquiry({ inquiry, assessments, matchingGuardians, classes, sections, can }: Props) {
    const open = inquiry.status !== 'admitted' && inquiry.status !== 'dropped';

    // ───── exams and interviews ─────
    const [assessOpen, setAssessOpen] = useState(false);
    const [editing, setEditing] = useState<AdmissionAssessment | null>(null);
    const assess = useForm<AssessmentForm>({ type: 'exam', scheduled_at: '', venue: '', score: '', max_score: '100', outcome: 'pending', remarks: '' });

    function openAssessment(type: 'exam' | 'interview', a: AdmissionAssessment | null = null) {
        assess.clearErrors();
        assess.setData({
            type: a?.type ?? type, scheduled_at: a?.scheduled_at ?? '', venue: a?.venue ?? '',
            score: a?.score != null ? String(a.score) : '', max_score: a?.max_score != null ? String(a.max_score) : '100',
            outcome: a?.outcome ?? 'pending', remarks: a?.remarks ?? '',
        });
        setEditing(a);
        setAssessOpen(true);
    }

    function saveAssessment(e: React.FormEvent) {
        e.preventDefault();
        // An interview has no score; an exam with no score yet sends neither
        assess.transform(d => ({ ...d, score: d.type === 'exam' && d.score !== '' ? d.score : null, max_score: d.type === 'exam' && d.score !== '' ? d.max_score : null }));
        const opts = { preserveScroll: true, onSuccess: () => setAssessOpen(false) };
        if (editing) assess.put(`/school/admissions/inquiries/${inquiry.id}/assessments/${editing.id}`, opts);
        else assess.post(`/school/admissions/inquiries/${inquiry.id}/assessments`, opts);
    }

    function removeAssessment(a: AdmissionAssessment) {
        if (confirm(`Remove this ${a.type === 'exam' ? 'entrance exam' : 'interview'}?`)) {
            router.delete(`/school/admissions/inquiries/${inquiry.id}/assessments/${a.id}`, { preserveScroll: true });
        }
    }

    // ───── decision ─────
    const [decideOpen, setDecideOpen] = useState<'accept' | 'decline' | null>(null);
    const decide = useForm({ decision: 'accept', note: '' });

    function openDecision(decision: 'accept' | 'decline') {
        decide.clearErrors();
        decide.setData({ decision, note: '' });
        setDecideOpen(decision);
    }

    // ───── enrolment ─────
    const [enrolOpen, setEnrolOpen] = useState(false);
    const [first, ...rest] = inquiry.student_name.trim().split(/\s+/);
    const guessedClass = classes.find(c => c.name.toLowerCase().replace(/\s/g, '') === inquiry.class_interested.toLowerCase().replace(/\s/g, ''));
    const enrol = useForm<EnrolForm>({
        first_name: first ?? '', last_name: rest.join(' '), gender: '', date_of_birth: '', admission_date: today(), previous_school: '',
        class_id: guessedClass ? String(guessedClass.id) : '', section_id: '',
        guardian_id: matchingGuardians[0] ? String(matchingGuardians[0].id) : NEW_GUARDIAN, guardian_relation: '',
    });
    const classSections = sections.filter(s => String(s.class_id) === enrol.data.class_id);
    const failed = assessments.filter(a => a.outcome === 'failed' || a.outcome === 'absent');

    function saveEnrol(e: React.FormEvent) {
        e.preventDefault();
        enrol.transform(d => ({
            ...d,
            guardian_id: d.guardian_id === NEW_GUARDIAN ? null : Number(d.guardian_id),
            section_id: d.section_id || null,
        }));
        enrol.post(`/school/admissions/inquiries/${inquiry.id}/enrol`);
    }

    const classItems = classes.map(c => ({ value: String(c.id), label: c.name }));
    const sectionItems = [{ value: '', label: 'No section' }, ...classSections.map(s => ({ value: String(s.id), label: s.name }))];
    const guardianItems = [
        ...matchingGuardians.map(g => ({ value: String(g.id), label: `${g.name} (already a parent here)` })),
        { value: NEW_GUARDIAN, label: `Add ${inquiry.guardian_name} as a new parent` },
    ];
    const genderItems = [{ value: 'female', label: 'Female' }, { value: 'male', label: 'Male' }];

    const headerActions = open && (
        <div className="flex flex-wrap gap-2">
            {can.manage && inquiry.status !== 'accepted' && (
                <>
                    <Button variant="outline" onClick={() => openDecision('decline')} className="text-red-600"><X className="size-4" /> Decline</Button>
                    <Button onClick={() => openDecision('accept')} className="bg-emerald-600 text-white hover:bg-emerald-700"><Check className="size-4" /> Offer a place</Button>
                </>
            )}
            {inquiry.status === 'accepted' && can.enrol && (
                <Button onClick={() => { enrol.clearErrors(); setEnrolOpen(true); }} className="bg-indigo-600 text-white hover:bg-indigo-700">
                    <GraduationCap className="size-4" /> Enrol as a student
                </Button>
            )}
        </div>
    );

    const steps = [
        { label: 'Enquiry', done: true },
        { label: 'Exam & interview', done: assessments.some(a => a.outcome !== 'pending') },
        { label: 'Place offered', done: inquiry.status === 'accepted' || inquiry.status === 'admitted' },
        { label: 'Enrolled', done: inquiry.status === 'admitted' },
    ];

    return (
        <AppLayout title={inquiry.student_name}>
            <div className="mx-auto max-w-4xl space-y-6 pb-24 md:pb-0">
                <Link href="/school/admissions/inquiries" className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                    <ArrowLeft className="size-4" /> All inquiries
                </Link>

                <PageHeader
                    title={inquiry.student_name}
                    description={<span className="inline-flex flex-wrap items-center gap-2">Applying for {inquiry.class_interested} <InquiryStatusPill status={inquiry.status} /></span>}
                    actions={headerActions}
                />
                {/* The header hides its buttons on phones, so they are repeated here */}
                <div className="md:hidden">{headerActions}</div>

                {/* Where this application is */}
                {inquiry.status !== 'dropped' && (
                    <ol className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        {steps.map((s, i) => (
                            <li key={s.label} className={cn('rounded-lg border px-3 py-2 text-sm', s.done
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300'
                                : 'border-slate-200 text-slate-500 dark:border-white/10')}>
                                <span className="mr-1 tabular-nums">{i + 1}.</span>{s.label}
                            </li>
                        ))}
                    </ol>
                )}

                {inquiry.enrolled && (
                    <Panel>
                        <p className="text-sm">
                            Enrolled as <Link href={`/school/students/${inquiry.enrolled.id}`} className="font-medium text-indigo-600 hover:underline dark:text-indigo-400">{inquiry.enrolled.name}</Link>
                            {' '}({inquiry.enrolled.admission_no}{inquiry.enrolled.class ? `, ${inquiry.enrolled.class}` : ''})
                            {inquiry.enrolled.by && <> by {inquiry.enrolled.by}</>}{inquiry.enrolled.at && <> on {inquiry.enrolled.at}</>}.
                        </p>
                    </Panel>
                )}

                {inquiry.decision && (
                    <Panel title={inquiry.status === 'dropped' ? 'Declined' : 'Place offered'}>
                        <p className="text-sm text-slate-600 dark:text-slate-300">
                            {inquiry.decision.by ?? 'Staff'} on {inquiry.decision.at}{inquiry.decision.note ? `: ${inquiry.decision.note}` : '.'}
                        </p>
                    </Panel>
                )}

                <Panel
                    title="Entrance exam and interview"
                    description="Optional. Book them here, then record how the child did."
                    action={open && can.manage && (
                        <div className="flex gap-2">
                            <Button variant="outline" size="sm" onClick={() => openAssessment('exam')}><Plus className="size-3.5" /> Exam</Button>
                            <Button variant="outline" size="sm" onClick={() => openAssessment('interview')}><Plus className="size-3.5" /> Interview</Button>
                        </div>
                    )}
                >
                    {assessments.length === 0 ? (
                        <p className="text-sm text-slate-500">None booked.</p>
                    ) : (
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {assessments.map(a => (
                                <li key={a.id} className="flex flex-wrap items-start justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                    <div className="min-w-0">
                                        <p className="flex items-center gap-2 font-medium text-slate-900 dark:text-white">
                                            {a.type === 'exam' ? <ClipboardCheck className="size-4 text-slate-400" /> : <MessagesSquare className="size-4 text-slate-400" />}
                                            {a.type === 'exam' ? 'Entrance exam' : 'Interview'}
                                            <Pill tone={OUTCOME_TONE[a.outcome]}>{OUTCOME_ITEMS.find(o => o.value === a.outcome)?.label}</Pill>
                                            {a.score != null && <span className="text-sm tabular-nums text-slate-600 dark:text-slate-300">{a.score} / {a.max_score}</span>}
                                        </p>
                                        <p className="mt-0.5 text-xs text-slate-500">
                                            {[a.scheduled_at && new Date(a.scheduled_at).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' }), a.venue, a.recorded_by && `by ${a.recorded_by}`].filter(Boolean).join(' · ')}
                                        </p>
                                        {a.remarks && <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">{a.remarks}</p>}
                                    </div>
                                    {open && can.manage && (
                                        <div className="flex shrink-0 gap-1">
                                            <Button variant="ghost" size="sm" onClick={() => openAssessment(a.type, a)}><Pencil className="size-3.5" /> {a.outcome === 'pending' ? 'Record result' : 'Edit'}</Button>
                                            <Button variant="ghost" size="sm" onClick={() => removeAssessment(a)} aria-label="Remove"><Trash2 className="size-3.5" /></Button>
                                        </div>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </Panel>

                <div className="grid gap-6 md:grid-cols-2">
                    <Panel title="Parent or guardian">
                        <dl className="space-y-1 text-sm">
                            <div><dt className="inline text-slate-500">Name: </dt><dd className="inline">{inquiry.guardian_name}</dd></div>
                            <div><dt className="inline text-slate-500">Phone: </dt><dd className="inline">{inquiry.guardian_phone}</dd></div>
                            {inquiry.guardian_email && <div><dt className="inline text-slate-500">Email: </dt><dd className="inline">{inquiry.guardian_email}</dd></div>}
                            <div><dt className="inline text-slate-500">Came via: </dt><dd className="inline capitalize">{inquiry.source}</dd></div>
                        </dl>
                        {matchingGuardians.length > 0 && (
                            <p className="mt-3 rounded-lg bg-indigo-50 px-3 py-2 text-xs text-indigo-800 dark:bg-indigo-500/10 dark:text-indigo-200">
                                This phone number already belongs to a parent here ({matchingGuardians.map(g => `${g.name}: ${g.children.join(', ') || 'no children yet'}`).join('; ')}). Enrolling links the child to them.
                            </p>
                        )}
                    </Panel>
                    <Panel title="Notes">
                        {inquiry.notes && <p className="mb-3 text-sm text-slate-600 dark:text-slate-300">{inquiry.notes}</p>}
                        {inquiry.followups.length === 0 && !inquiry.notes && <p className="text-sm text-slate-500">No notes yet.</p>}
                        <ul className="space-y-2">
                            {inquiry.followups.map(f => (
                                <li key={f.id} className="rounded-lg bg-slate-50 p-2 text-sm dark:bg-white/[0.04]">
                                    <p className="text-xs text-slate-500">{f.by ?? 'Staff'} · {f.at}</p>
                                    <p>{f.note}</p>
                                </li>
                            ))}
                        </ul>
                    </Panel>
                </div>
            </div>

            {/* Exam / interview */}
            <Dialog open={assessOpen} onOpenChange={setAssessOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>{assess.data.type === 'exam' ? 'Entrance exam' : 'Interview'}</DialogTitle></DialogHeader>
                    <form onSubmit={saveAssessment} className="mt-2 space-y-4">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="as-when">Date and time</Label>
                                <Input id="as-when" type="datetime-local" value={assess.data.scheduled_at} onChange={e => assess.setData('scheduled_at', e.target.value)} />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="as-venue">Where</Label>
                                <Input id="as-venue" value={assess.data.venue} onChange={e => assess.setData('venue', e.target.value)} placeholder="e.g. Main hall" />
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Result</Label>
                            <Select items={OUTCOME_ITEMS} value={assess.data.outcome} onValueChange={v => v && assess.setData('outcome', v as AssessmentOutcome)}>
                                <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>{OUTCOME_ITEMS.map(o => <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>)}</SelectContent>
                            </Select>
                        </div>
                        {assess.data.type === 'exam' && (
                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="as-score">Score (optional)</Label>
                                    <Input id="as-score" type="number" min="0" step="0.5" value={assess.data.score} onChange={e => assess.setData('score', e.target.value)} />
                                    <ErrorText>{assess.errors.score}</ErrorText>
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="as-max">Out of</Label>
                                    <Input id="as-max" type="number" min="1" value={assess.data.max_score} onChange={e => assess.setData('max_score', e.target.value)} />
                                    <ErrorText>{assess.errors.max_score}</ErrorText>
                                </div>
                            </div>
                        )}
                        <div className="space-y-1.5">
                            <Label htmlFor="as-remarks">Remarks</Label>
                            <Textarea id="as-remarks" value={assess.data.remarks} onChange={e => assess.setData('remarks', e.target.value)} placeholder={assess.data.type === 'exam' ? 'e.g. Strong in Maths, weak in English' : 'e.g. Confident, answered clearly'} />
                        </div>
                        <ErrorText>{(assess.errors as Record<string, string>).inquiry}</ErrorText>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setAssessOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={assess.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Save</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Offer a place / decline */}
            <Dialog open={decideOpen !== null} onOpenChange={o => !o && setDecideOpen(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>{decideOpen === 'accept' ? `Offer ${inquiry.student_name} a place` : 'Decline this application'}</DialogTitle></DialogHeader>
                    <form onSubmit={e => { e.preventDefault(); decide.post(`/school/admissions/inquiries/${inquiry.id}/decision`, { preserveScroll: true, onSuccess: () => setDecideOpen(null) }); }} className="mt-2 space-y-4">
                        {decideOpen === 'accept' && failed.length > 0 && (
                            <p className="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">
                                Note: this child did not pass or did not attend {failed.length === 1 ? 'one step' : `${failed.length} steps`} above.
                            </p>
                        )}
                        <div className="space-y-1.5">
                            <Label htmlFor="dec-note">{decideOpen === 'accept' ? 'Note (optional)' : 'Reason'}</Label>
                            <Textarea id="dec-note" value={decide.data.note} onChange={e => decide.setData('note', e.target.value)} placeholder={decideOpen === 'accept' ? 'e.g. Passed exam and interview' : 'e.g. No space left in JSS 1'} />
                            <ErrorText>{decide.errors.note ?? (decide.errors as Record<string, string>).inquiry}</ErrorText>
                        </div>
                        <p className="text-xs text-slate-500">You will be recorded as the person who made this decision.</p>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setDecideOpen(null)}>Cancel</Button>
                            <Button type="submit" disabled={decide.processing} className={decideOpen === 'accept' ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-red-600 text-white hover:bg-red-700'}>
                                {decideOpen === 'accept' ? 'Offer a place' : 'Decline'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Enrol */}
            <Dialog open={enrolOpen} onOpenChange={setEnrolOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader><DialogTitle>Enrol {inquiry.student_name}</DialogTitle></DialogHeader>
                    <form onSubmit={saveEnrol} className="mt-2 max-h-[70vh] space-y-4 overflow-y-auto pr-1">
                        <p className="text-sm text-slate-500">This creates the student record and gives the child an admission number. Details from the application are filled in for you.</p>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="en-first">First name</Label>
                                <Input id="en-first" value={enrol.data.first_name} onChange={e => enrol.setData('first_name', e.target.value)} />
                                <ErrorText>{enrol.errors.first_name}</ErrorText>
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="en-last">Surname</Label>
                                <Input id="en-last" value={enrol.data.last_name} onChange={e => enrol.setData('last_name', e.target.value)} />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Gender</Label>
                                <Select items={genderItems} value={enrol.data.gender} onValueChange={v => enrol.setData('gender', v ?? '')}>
                                    <SelectTrigger className="w-full"><SelectValue placeholder="Choose" /></SelectTrigger>
                                    <SelectContent>{genderItems.map(g => <SelectItem key={g.value} value={g.value}>{g.label}</SelectItem>)}</SelectContent>
                                </Select>
                                <ErrorText>{enrol.errors.gender}</ErrorText>
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="en-dob">Date of birth</Label>
                                <Input id="en-dob" type="date" max={today()} value={enrol.data.date_of_birth} onChange={e => enrol.setData('date_of_birth', e.target.value)} />
                                <ErrorText>{enrol.errors.date_of_birth}</ErrorText>
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>Class</Label>
                                <Select items={classItems} value={enrol.data.class_id} onValueChange={v => { enrol.setData(d => ({ ...d, class_id: v ?? '', section_id: '' })); }}>
                                    <SelectTrigger className="w-full"><SelectValue placeholder="Choose a class" /></SelectTrigger>
                                    <SelectContent>{classItems.map(c => <SelectItem key={c.value} value={c.value}>{c.label}</SelectItem>)}</SelectContent>
                                </Select>
                                <ErrorText>{enrol.errors.class_id}</ErrorText>
                            </div>
                            <div className="space-y-1.5">
                                <Label>Section / arm</Label>
                                <Select items={sectionItems} value={enrol.data.section_id} onValueChange={v => enrol.setData('section_id', v ?? '')} disabled={classSections.length === 0}>
                                    <SelectTrigger className="w-full"><SelectValue placeholder="No section" /></SelectTrigger>
                                    <SelectContent>{sectionItems.map(s => <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>)}</SelectContent>
                                </Select>
                                <ErrorText>{enrol.errors.section_id}</ErrorText>
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="en-start">Start date</Label>
                                <Input id="en-start" type="date" value={enrol.data.admission_date} onChange={e => enrol.setData('admission_date', e.target.value)} />
                                <ErrorText>{enrol.errors.admission_date}</ErrorText>
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="en-prev">Previous school</Label>
                                <Input id="en-prev" value={enrol.data.previous_school} onChange={e => enrol.setData('previous_school', e.target.value)} />
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label>Parent or guardian</Label>
                            <Select items={guardianItems} value={enrol.data.guardian_id} onValueChange={v => enrol.setData('guardian_id', v ?? NEW_GUARDIAN)}>
                                <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>{guardianItems.map(g => <SelectItem key={g.value} value={g.value}>{g.label}</SelectItem>)}</SelectContent>
                            </Select>
                            <ErrorText>{enrol.errors.guardian_id}</ErrorText>
                        </div>
                        {enrol.data.guardian_id === NEW_GUARDIAN && (
                            <div className="space-y-1.5">
                                <Label htmlFor="en-rel">{inquiry.guardian_name} is the child's</Label>
                                <Input id="en-rel" value={enrol.data.guardian_relation} onChange={e => enrol.setData('guardian_relation', e.target.value)} placeholder="e.g. Mother, Father, Uncle" />
                                <ErrorText>{enrol.errors.guardian_relation}</ErrorText>
                            </div>
                        )}
                        <ErrorText>{(enrol.errors as Record<string, string>).inquiry}</ErrorText>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setEnrolOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={enrol.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Enrol</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
