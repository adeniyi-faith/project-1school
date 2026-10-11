import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { EmptyState, PageHeader, Panel, Pill } from '@/components/app/kit';
import { ClubDialog } from '@/components/clubs/ClubForm';
import { ArrowLeft, Pencil, Trash2, UserPlus, Users } from 'lucide-react';

interface Member { id: number; role: string; joined_on: string; student: { id: number; name: string; admission_no: string; class: string | null; active: boolean } | null }
interface Props {
    club: { id: number; name: string; description: string | null; meets: string | null; is_active: boolean; patron_id: number | null; patron: string | null };
    members: Member[]; staff: { id: number; name: string }[];
    classes: { id: number; name: string }[]; class_id: number | null;
    candidates: { id: number; name: string; admission_no: string }[];
    can: { manage: boolean };
}

const ROLES = ['Member', 'President', 'Vice President', 'Secretary', 'Treasurer', 'Public Relations Officer'];

export default function ClubShow({ club, members, staff, classes, class_id, candidates, can }: Props) {
    const [editing, setEditing] = useState(false);
    const add = useForm({ student_ids: [] as number[], admission_nos: '' });
    const ticked = add.data.student_ids;

    function addMembers(e: React.FormEvent) {
        e.preventDefault();
        add.post(`/school/clubs/${club.id}/members`, { preserveScroll: true, onSuccess: () => add.setData({ student_ids: [], admission_nos: '' }) });
    }

    function setRole(m: Member, role: string) {
        router.put(`/school/clubs/${club.id}/members/${m.id}`, { role }, { preserveScroll: true });
    }

    return (
        <AppLayout title={club.name}>
            <div className="space-y-6">
                <Link href="/school/clubs" className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900 dark:hover:text-white">
                    <ArrowLeft className="size-4" /> Clubs
                </Link>
                <PageHeader
                    title={club.name}
                    description={[club.description, club.patron ? `Teacher in charge: ${club.patron}` : null, club.meets ? `Meets: ${club.meets}` : null].filter(Boolean).join(' · ') || undefined}
                    actions={can.manage ? (
                        <div className="flex gap-2">
                            <Button variant="outline" onClick={() => setEditing(true)}><Pencil className="size-4" /> Edit</Button>
                            <Button variant="ghost" className="text-red-600" onClick={() => confirm(`Remove ${club.name} and its member list?`) && router.delete(`/school/clubs/${club.id}`)}><Trash2 className="size-4" /></Button>
                        </div>
                    ) : undefined}
                />
                {can.manage && <div className="md:hidden"><Button variant="outline" onClick={() => setEditing(true)}><Pencil className="size-4" /> Edit club</Button></div>}

                <div className="grid gap-6 lg:grid-cols-[1fr_360px]">
                    <Panel title={`Members (${members.length})`} flush>
                        {members.length === 0 ? (
                            <EmptyState icon={Users} title="No members yet" text={can.manage ? 'Add students from a class or by admission number.' : undefined} />
                        ) : (
                            <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                                {members.map(m => (
                                    <li key={m.id} className="flex flex-wrap items-center gap-3 px-4 py-2 text-sm">
                                        <div className="min-w-0 flex-1">
                                            {m.student ? <Link href={`/school/students/${m.student.id}`} className="font-medium hover:underline">{m.student.name}</Link> : <span className="text-slate-400">Removed student</span>}
                                            <p className="text-xs text-slate-500">{m.student?.admission_no}{m.student?.class ? ` · ${m.student.class}` : ''} · joined {m.joined_on}</p>
                                        </div>
                                        {m.student && !m.student.active && <Pill tone="warn">Left school</Pill>}
                                        {can.manage ? (
                                            <>
                                                <select aria-label="Role" className="h-8 rounded-lg border border-slate-200 bg-transparent px-2 text-sm dark:border-white/10 dark:bg-slate-900" value={m.role} onChange={e => setRole(m, e.target.value)}>
                                                    {(ROLES.includes(m.role) ? ROLES : [m.role, ...ROLES]).map(r => <option key={r}>{r}</option>)}
                                                </select>
                                                <Button size="sm" variant="ghost" title="Remove from club" onClick={() => confirm(`Remove ${m.student?.name ?? 'this member'} from the club?`) && router.delete(`/school/clubs/${club.id}/members/${m.id}`, { preserveScroll: true })}>
                                                    <Trash2 className="size-3.5" />
                                                </Button>
                                            </>
                                        ) : m.role !== 'Member' && <Pill tone="info">{m.role}</Pill>}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Panel>

                    {can.manage && (
                        <Panel title="Add members">
                            <form onSubmit={addMembers} className="space-y-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="add-class">From a class</Label>
                                    <select id="add-class" className="h-9 w-full rounded-lg border border-slate-200 bg-transparent px-2 text-sm dark:border-white/10 dark:bg-slate-900"
                                        value={class_id ?? ''} onChange={e => router.get(`/school/clubs/${club.id}`, e.target.value ? { class_id: e.target.value } : {}, { preserveScroll: true, preserveState: false })}>
                                        <option value="">Choose a class</option>
                                        {classes.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                                    </select>
                                    {class_id && (candidates.length === 0 ? <p className="text-xs text-slate-500">Everyone in this class is already a member.</p> : (
                                        <div className="max-h-64 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-2 dark:border-white/10">
                                            <label className="flex items-center gap-2 border-b border-slate-100 pb-1 text-xs font-medium dark:border-white/[0.06]">
                                                <input type="checkbox" checked={ticked.length === candidates.length} onChange={e => add.setData('student_ids', e.target.checked ? candidates.map(c => c.id) : [])} /> Tick all
                                            </label>
                                            {candidates.map(c => (
                                                <label key={c.id} className="flex items-center gap-2 text-sm">
                                                    <input type="checkbox" checked={ticked.includes(c.id)} onChange={() => add.setData('student_ids', ticked.includes(c.id) ? ticked.filter(x => x !== c.id) : [...ticked, c.id])} />
                                                    {c.name} <span className="text-xs text-slate-500">{c.admission_no}</span>
                                                </label>
                                            ))}
                                        </div>
                                    ))}
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="add-nos">Or type admission numbers</Label>
                                    <textarea id="add-nos" rows={3} className="w-full rounded-lg border border-slate-200 bg-transparent px-3 py-2 text-sm dark:border-white/10"
                                        value={add.data.admission_nos} onChange={e => add.setData('admission_nos', e.target.value)} placeholder="One per line, or separated by commas" />
                                    {add.errors.admission_nos && <p className="text-xs text-red-500">{add.errors.admission_nos}</p>}
                                </div>
                                <Button type="submit" disabled={add.processing || (!ticked.length && !add.data.admission_nos.trim())} className="w-full bg-indigo-600 text-white hover:bg-indigo-700">
                                    <UserPlus className="size-4" /> Add{ticked.length ? ` ${ticked.length} ticked` : ''}
                                </Button>
                            </form>
                        </Panel>
                    )}
                </div>
            </div>
            <ClubDialog club={club} open={editing} onClose={() => setEditing(false)} staff={staff} />
        </AppLayout>
    );
}
