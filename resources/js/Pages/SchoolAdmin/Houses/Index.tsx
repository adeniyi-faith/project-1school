import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { EmptyState, PageHeader, Panel, Pill, StatStrip } from '@/components/app/kit';
import { Medal, Pencil, Plus, Shuffle, Trash2, Undo2, Users } from 'lucide-react';

interface House {
    id: number; name: string; colour: string; motto: string | null; is_active: boolean;
    patron_id: number | null; patron: string | null; captain_id: number | null; captain: string | null; captain_no: string | null;
    members: number; points: number;
}
interface PointLine { id: number; house: string | null; colour: string | null; points: number; reason: string; event: string | null; date: string; by: string | null }
interface Props {
    houses: House[]; recent: PointLine[]; unassigned: number;
    staff: { id: number; name: string }[]; classes: { id: number; name: string }[];
    can: { manage: boolean; points: boolean };
}

const selectClass = 'h-9 w-full rounded-lg border border-slate-200 bg-transparent px-2 text-sm dark:border-white/10 dark:bg-slate-900';
// The usual four colours Nigerian schools use for inter-house sports
const STARTERS = [
    { name: 'Red House', colour: '#dc2626' }, { name: 'Blue House', colour: '#2563eb' },
    { name: 'Green House', colour: '#16a34a' }, { name: 'Yellow House', colour: '#eab308' },
];

type HouseForm = { name: string; colour: string; motto: string; patron_id: string; captain_no: string; is_active: boolean };
const today = () => new Date().toISOString().slice(0, 10);

export default function HousesIndex({ houses, recent, unassigned, staff, classes, can }: Props) {
    const [editing, setEditing] = useState<House | 'new' | null>(null);
    const form = useForm<HouseForm>({ name: '', colour: '#2563eb', motto: '', patron_id: '', captain_no: '', is_active: true });

    const [giving, setGiving] = useState(false);
    const points = useForm({ house_ids: [] as number[], points: '5', reason: '', event: '', awarded_on: today() });

    const [sharing, setSharing] = useState(false);
    const share = useForm({ class_id: '', everyone: false });

    const ranked = [...houses].filter(h => h.is_active).sort((a, b) => b.points - a.points);
    const top = ranked[0]?.points ?? 0;

    function open(house: House | null, starter?: { name: string; colour: string }) {
        form.clearErrors();
        form.setData(house
            ? { name: house.name, colour: house.colour, motto: house.motto ?? '', patron_id: house.patron_id ? String(house.patron_id) : '', captain_no: house.captain_no ?? '', is_active: house.is_active }
            : { name: starter?.name ?? '', colour: starter?.colour ?? '#2563eb', motto: '', patron_id: '', captain_no: '', is_active: true });
        setEditing(house ?? 'new');
    }

    function save(e: React.FormEvent) {
        e.preventDefault();
        form.transform(d => ({ ...d, patron_id: d.patron_id || null }));
        const opts = { preserveScroll: true, onSuccess: () => setEditing(null) };
        if (editing && editing !== 'new') form.put(`/school/houses/${editing.id}`, opts);
        else form.post('/school/houses', opts);
    }

    function remove(h: House) {
        if (confirm(`Remove ${h.name}? Its ${h.members} students will have no house until you share them again.`)) {
            router.delete(`/school/houses/${h.id}`, { preserveScroll: true, onSuccess: () => setEditing(null) });
        }
    }

    function give(e: React.FormEvent) {
        e.preventDefault();
        points.post('/school/houses/points', { preserveScroll: true, onSuccess: () => { setGiving(false); points.reset('reason', 'house_ids'); } });
    }

    const toggleHouse = (id: number) => points.setData('house_ids', points.data.house_ids.includes(id) ? points.data.house_ids.filter(x => x !== id) : [...points.data.house_ids, id]);

    const actions = (
        <div className="flex flex-wrap gap-2">
            {can.points && houses.length > 0 && <Button onClick={() => { points.clearErrors(); setGiving(true); }} className="bg-indigo-600 text-white hover:bg-indigo-700"><Medal className="size-4" /> Give points</Button>}
            {can.manage && houses.length > 1 && <Button variant="outline" onClick={() => setSharing(true)}><Shuffle className="size-4" /> Share students</Button>}
            {can.manage && <Link href="/school/houses/assign" className="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 text-sm max-md:h-10 font-medium hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5"><Users className="size-4" /> Change by class</Link>}
            {can.manage && <Button variant="outline" onClick={() => open(null)}><Plus className="size-4" /> New house</Button>}
        </div>
    );

    return (
        <AppLayout title="Houses">
            <div className="space-y-6">
                <PageHeader title="Houses" description="Sports houses, who is in each one, and the points table for inter-house sports and competitions." actions={actions} />
                <div className="md:hidden">{actions}</div>

                {houses.length === 0 ? (
                    <Panel>
                        <EmptyState icon={Medal} title="No houses yet" text={can.manage ? 'Most schools have four houses named after colours or notable people. Start with these and rename them, or add your own.' : 'The school has not set up houses yet.'} />
                        {can.manage && (
                            <div className="flex flex-wrap justify-center gap-2 pb-4">
                                {STARTERS.map(s => (
                                    <Button key={s.name} variant="outline" onClick={() => open(null, s)}>
                                        <span className="size-3 rounded-full" style={{ background: s.colour }} /> {s.name}
                                    </Button>
                                ))}
                            </div>
                        )}
                    </Panel>
                ) : (
                    <>
                        <StatStrip items={[
                            { label: 'Houses', value: houses.filter(h => h.is_active).length },
                            { label: 'Students in a house', value: houses.reduce((n, h) => n + h.members, 0) },
                            { label: 'Without a house', value: unassigned, tone: unassigned > 0 ? 'warn' : 'good', hint: unassigned > 0 && can.manage ? 'Use “Share students”' : undefined },
                            { label: 'Leading', value: ranked[0] && top !== 0 ? ranked[0].name : '—' },
                        ]} />

                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {ranked.concat(houses.filter(h => !h.is_active)).map((h, i) => (
                                <div key={h.id} className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-white/10 dark:bg-slate-900">
                                    <div className="h-2" style={{ background: h.colour }} />
                                    <div className="space-y-2 p-4">
                                        <div className="flex items-start justify-between gap-2">
                                            <div>
                                                <p className="font-semibold text-slate-900 dark:text-white">{h.name}</p>
                                                {h.motto && <p className="text-xs italic text-slate-500">{h.motto}</p>}
                                            </div>
                                            {h.is_active ? (i === 0 && top !== 0 ? <Pill tone="good">1st</Pill> : <Pill>{ordinal(i + 1)}</Pill>) : <Pill tone="neutral">Not in use</Pill>}
                                        </div>
                                        <p className="text-3xl font-bold tabular-nums" style={{ color: h.colour }}>{h.points}<span className="ml-1 text-sm font-normal text-slate-500">points</span></p>
                                        <div className="text-xs text-slate-500 space-y-0.5">
                                            <p>{h.members} student{h.members === 1 ? '' : 's'}</p>
                                            <p>House master/mistress: {h.patron ?? '—'}</p>
                                            <p>Captain: {h.captain ?? '—'}</p>
                                        </div>
                                        {can.manage && <Button size="sm" variant="ghost" onClick={() => open(h)} className="-ml-2"><Pencil className="size-3.5" /> Edit</Button>}
                                    </div>
                                </div>
                            ))}
                        </div>

                        <Panel title="Points given" description="Newest first. A line given by mistake is cancelled by an opposite line, so the record stays complete." flush>
                            {recent.length === 0 ? (
                                <p className="px-4 py-6 text-sm text-slate-400">No points given yet.</p>
                            ) : (
                                <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                                    {recent.map(p => (
                                        <li key={p.id} className="flex flex-wrap items-center gap-3 px-4 py-2.5 text-sm">
                                            <span className="size-2.5 shrink-0 rounded-full" style={{ background: p.colour ?? '#94a3b8' }} />
                                            <span className="w-28 shrink-0 font-medium">{p.house}</span>
                                            <span className={`w-14 shrink-0 font-semibold tabular-nums ${p.points > 0 ? 'text-emerald-600' : 'text-red-600'}`}>{p.points > 0 ? `+${p.points}` : p.points}</span>
                                            <span className="min-w-0 flex-1">{p.reason}{p.event ? <span className="text-slate-500"> · {p.event}</span> : null}</span>
                                            <span className="text-xs text-slate-500">{p.date}{p.by ? ` · ${p.by}` : ''}</span>
                                            {can.manage && !p.reason.startsWith('Cancelled:') && (
                                                <Button size="sm" variant="ghost" title="Cancel this line" onClick={() => confirm('Cancel these points? An opposite line is added.') && router.post(`/school/houses/points/${p.id}/undo`, {}, { preserveScroll: true })}>
                                                    <Undo2 className="size-3.5" />
                                                </Button>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Panel>
                    </>
                )}
            </div>

            {/* Add / edit a house */}
            <Dialog open={editing !== null} onOpenChange={o => !o && setEditing(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>{editing === 'new' ? 'New house' : 'Edit house'}</DialogTitle></DialogHeader>
                    <form onSubmit={save} className="space-y-4 pt-2">
                        <div className="grid grid-cols-[1fr_auto] gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="h-name">Name</Label>
                                <Input id="h-name" value={form.data.name} onChange={e => form.setData('name', e.target.value)} placeholder="e.g. Aggrey House" />
                                {form.errors.name && <p className="text-xs text-red-500">{form.errors.name}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="h-colour">Colour</Label>
                                <input id="h-colour" type="color" value={form.data.colour} onChange={e => form.setData('colour', e.target.value)} className="h-9 w-14 cursor-pointer rounded-lg border border-slate-200 dark:border-white/10" />
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="h-motto">Motto (optional)</Label>
                            <Input id="h-motto" value={form.data.motto} onChange={e => form.setData('motto', e.target.value)} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="h-patron">House master or mistress</Label>
                            <select id="h-patron" className={selectClass} value={form.data.patron_id} onChange={e => form.setData('patron_id', e.target.value)}>
                                <option value="">Nobody yet</option>
                                {staff.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="h-captain">Captain's admission number</Label>
                            <Input id="h-captain" value={form.data.captain_no} onChange={e => form.setData('captain_no', e.target.value)} placeholder="Leave empty for none" />
                            {form.errors.captain_no && <p className="text-xs text-red-500">{form.errors.captain_no}</p>}
                        </div>
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={form.data.is_active} onChange={e => form.setData('is_active', e.target.checked)} />
                            In use (new students can be shared into it)
                        </label>
                        <DialogFooter className="gap-2">
                            {editing && editing !== 'new' && <Button type="button" variant="ghost" className="mr-auto text-red-600" onClick={() => remove(editing)}><Trash2 className="size-4" /> Remove</Button>}
                            <Button type="button" variant="outline" onClick={() => setEditing(null)}>Cancel</Button>
                            <Button type="submit" disabled={form.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Save</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Give points */}
            <Dialog open={giving} onOpenChange={setGiving}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Give house points</DialogTitle></DialogHeader>
                    <form onSubmit={give} className="space-y-4 pt-2">
                        <div className="space-y-1.5">
                            <Label>Which house(s)?</Label>
                            <div className="flex flex-wrap gap-2">
                                {ranked.map(h => (
                                    <button type="button" key={h.id} onClick={() => toggleHouse(h.id)}
                                        className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm ${points.data.house_ids.includes(h.id) ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300' : 'border-slate-200 dark:border-white/10'}`}>
                                        <span className="size-2.5 rounded-full" style={{ background: h.colour }} /> {h.name}
                                    </button>
                                ))}
                            </div>
                            {points.errors.house_ids && <p className="text-xs text-red-500">Pick at least one house.</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="p-points">Points</Label>
                                <Input id="p-points" type="number" value={points.data.points} onChange={e => points.setData('points', e.target.value)} />
                                <p className="text-xs text-slate-500">Use a minus (e.g. -5) to take points off.</p>
                                {points.errors.points && <p className="text-xs text-red-500">{points.errors.points}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="p-date">Date</Label>
                                <Input id="p-date" type="date" max={today()} value={points.data.awarded_on} onChange={e => points.setData('awarded_on', e.target.value)} />
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="p-reason">Reason</Label>
                            <Input id="p-reason" value={points.data.reason} onChange={e => points.setData('reason', e.target.value)} placeholder="e.g. 1st place, 4×100m relay" />
                            {points.errors.reason && <p className="text-xs text-red-500">{points.errors.reason}</p>}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="p-event">Event (optional)</Label>
                            <Input id="p-event" value={points.data.event} onChange={e => points.setData('event', e.target.value)} placeholder="e.g. Inter-house sports 2026" />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setGiving(false)}>Cancel</Button>
                            <Button type="submit" disabled={points.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Save points</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Share students */}
            <Dialog open={sharing} onOpenChange={setSharing}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Share students into houses</DialogTitle></DialogHeader>
                    <form onSubmit={e => { e.preventDefault(); share.post('/school/houses/share', { preserveScroll: true, onSuccess: () => setSharing(false) }); }} className="space-y-4 pt-2">
                        <p className="text-sm text-slate-500">Students are dealt out in turn, class by class, so each house gets a fair mix of every class and of boys and girls.</p>
                        <div className="space-y-1.5">
                            <Label htmlFor="s-class">Which students?</Label>
                            <select id="s-class" className={selectClass} value={share.data.class_id} onChange={e => share.setData('class_id', e.target.value)}>
                                <option value="">The whole school</option>
                                {classes.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                            </select>
                        </div>
                        <label className="flex items-start gap-2 text-sm">
                            <input type="checkbox" className="mt-0.5" checked={share.data.everyone} onChange={e => share.setData('everyone', e.target.checked)} />
                            <span>Start again for everyone. <span className="text-slate-500">Leave this off to keep students who already have a house and only place new ones.</span></span>
                        </label>
                        {(share.errors as Record<string, string | undefined>).houses && <p className="text-xs text-red-500">{(share.errors as Record<string, string | undefined>).houses}</p>}
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setSharing(false)}>Cancel</Button>
                            <Button type="submit" disabled={share.processing} className="bg-indigo-600 text-white hover:bg-indigo-700">Share</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

function ordinal(n: number) {
    const s = ['th', 'st', 'nd', 'rd'], v = n % 100;
    return n + (s[(v - 20) % 10] || s[v] || s[0]);
}
