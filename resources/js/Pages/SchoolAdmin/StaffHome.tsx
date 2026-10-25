import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { PageHeader, Panel, Pill, StatStrip } from '@/components/app/kit';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { BookOpen, Bus, ClipboardList, Home, LogIn, Megaphone, Package, Users } from 'lucide-react';
import ExpandableText from '@/components/ExpandableText';

interface FrontDesk {
    visitors_today: number; still_inside: number; open_inquiries: number;
    inside: { id: number; name: string; meeting: string; since: string | null }[];
}
interface Transport {
    vehicles: number;
    routes: {
        id: number; name: string; from: string | null; to: string | null; stops: number; students: number; vehicle: string | null; driver: string | null;
        pickups: { id: number; name: string; class: string | null; stop: string | null }[];
    }[];
}
interface Hostel {
    beds: number; occupied: number; rooms_free: number; maintenance: number;
    tonight: { room: string; students: { id: number; name: string; class: string | null; bed: string | null }[] }[];
}
interface LoanLine { id: number; book: string; who: string; due: string | null; days_late: number }
interface Library { out_now: number; due_today_count: number; overdue_count: number; due_today: LoanLine[]; overdue: LoanLine[] }
interface Store { items: number; low_count: number; low: { id: number; name: string; stock: number; minimum: number; unit: string }[] }

interface Props {
    name: string;
    panels: { front_desk?: FrontDesk; library?: Library; transport?: Transport; hostel?: Hostel; store?: Store };
    announcements: { id: number; title: string; body: string; date: string | null }[];
}

function greeting() {
    const h = new Date().getHours();
    return h < 12 ? 'Good morning' : h < 16 ? 'Good afternoon' : 'Good evening';
}

/** Home for front desk, drivers, wardens and store keepers: only the parts of the school their work covers */
export default function StaffHome({ name, panels, announcements }: Props) {
    const { front_desk: desk, library, transport, hostel, store } = panels;
    const nothing = !desk && !library && !transport && !hostel && !store;
    const [signingIn, setSigningIn] = useState(false);
    const visitor = useForm({ name: '', phone: '', purpose: '', person_to_meet: '', remarks: '' });

    function signIn(e: React.FormEvent) {
        e.preventDefault();
        visitor.post('/school/admissions/visitors', { preserveScroll: true, onSuccess: () => { setSigningIn(false); visitor.reset(); } });
    }

    return (
        <AppLayout title="Home">
            <div className="space-y-6">
                <PageHeader title={`${greeting()}, ${name.split(' ')[0]}`} description="Here is what needs your attention today." />

                {desk && (
                    <Panel title="Front desk" action={
                        <div className="flex gap-2">
                            <Button size="sm" onClick={() => { visitor.reset(); visitor.clearErrors(); setSigningIn(true); }}><LogIn className="size-4" /> Sign in a visitor</Button>
                            <Link href="/school/admissions/visitors"><Button variant="outline" size="sm"><Users className="size-4" /> Visitor log</Button></Link>
                        </div>
                    }>
                        <StatStrip items={[
                            { label: 'Visitors today', value: desk.visitors_today },
                            { label: 'Still inside', value: desk.still_inside, tone: desk.still_inside > 0 ? 'warn' : 'default' },
                            { label: 'Admission inquiries to follow up', value: desk.open_inquiries },
                        ]} />
                        {desk.inside.length > 0 && (
                            <ul className="mt-4 divide-y divide-slate-100 text-sm dark:divide-white/[0.06]">
                                {desk.inside.map(v => (
                                    <li key={v.id} className="flex justify-between gap-3 py-2">
                                        <span><span className="font-medium">{v.name}</span> <span className="text-slate-500">to see {v.meeting}</span></span>
                                        <span className="flex items-center gap-3">
                                            <span className="text-xs text-slate-500">since {v.since}</span>
                                            <Button size="sm" variant="outline" onClick={() => router.patch(`/school/admissions/visitors/${v.id}/checkout`, {}, { preserveScroll: true })}>Sign out</Button>
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                        <div className="mt-4">
                            <Link href="/school/admissions/inquiries"><Button variant="ghost" size="sm"><ClipboardList className="size-4" /> Admission inquiries</Button></Link>
                        </div>
                    </Panel>
                )}

                {library && (
                    <Panel title="Library today" action={<Link href="/school/library/issues"><Button variant="outline" size="sm"><BookOpen className="size-4" /> Issue or return a book</Button></Link>}>
                        <StatStrip items={[
                            { label: 'Books out now', value: library.out_now },
                            { label: 'Due back today', value: library.due_today_count },
                            { label: 'Overdue', value: library.overdue_count, tone: library.overdue_count > 0 ? 'warn' : 'default' },
                        ]} />
                        {library.due_today.length > 0 && <LoanList title="Due back today" lines={library.due_today} />}
                        {library.overdue.length > 0 && <LoanList title="Overdue" lines={library.overdue} late />}
                        {library.due_today.length === 0 && library.overdue.length === 0 && <p className="mt-3 text-sm text-emerald-700 dark:text-emerald-400">Nothing is due or late today.</p>}
                        <div className="mt-4"><Link href="/school/library/overdue"><Button variant="ghost" size="sm">All overdue books</Button></Link></div>
                    </Panel>
                )}

                {transport && (
                    <Panel title="School buses" description={`${transport.vehicles} vehicle${transport.vehicles === 1 ? '' : 's'} in use`} action={<Link href="/school/transport/routes"><Button variant="outline" size="sm"><Bus className="size-4" /> All routes</Button></Link>}>
                        {transport.routes.length === 0 ? (
                            <p className="text-sm text-slate-500">No routes have been set up yet.</p>
                        ) : (
                            <ul className="divide-y divide-slate-100 text-sm dark:divide-white/[0.06]">
                                {transport.routes.map(r => (
                                    <li key={r.id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                                        <div>
                                            <Link href={`/school/transport/routes/${r.id}/assignments`} className="font-medium text-indigo-600 hover:underline dark:text-indigo-400">{r.name}</Link>
                                            <p className="text-xs text-slate-500">{[r.from && r.to ? `${r.from} to ${r.to}` : null, r.stops ? `${r.stops} stops` : null, r.vehicle, r.driver].filter(Boolean).join(' · ')}</p>
                                        </div>
                                        <Pill tone="info">{r.students} student{r.students === 1 ? '' : 's'}</Pill>
                                        {r.pickups.length > 0 && (
                                            <details className="basis-full text-sm" open={transport.routes.length === 1}>
                                                <summary className="cursor-pointer py-1 text-xs font-medium text-indigo-600 dark:text-indigo-400">Pick-up list</summary>
                                                <ol className="mt-1 list-decimal space-y-0.5 pl-5">
                                                    {r.pickups.map(p => (
                                                        <li key={p.id}>{p.name}<span className="text-xs text-slate-500">{[p.class, p.stop].filter(Boolean).map(t => ` · ${t}`).join('')}</span></li>
                                                    ))}
                                                </ol>
                                            </details>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Panel>
                )}

                {hostel && (
                    <Panel title="Hostel" action={<Link href="/school/hostel/allocations"><Button variant="outline" size="sm"><Home className="size-4" /> Allocations</Button></Link>}>
                        <StatStrip items={[
                            { label: 'Beds', value: hostel.beds },
                            { label: 'Occupied', value: hostel.occupied },
                            { label: 'Free beds', value: Math.max(0, hostel.beds - hostel.occupied), tone: 'good' },
                            { label: 'Rooms under repair', value: hostel.maintenance, tone: hostel.maintenance > 0 ? 'warn' : 'default' },
                        ]} />
                        {hostel.tonight.length > 0 && (
                            <div className="mt-4 space-y-1">
                                <p className="text-sm font-medium">Who is in which room tonight</p>
                                {hostel.tonight.map(r => (
                                    <details key={r.room} className="rounded-md border border-slate-100 px-3 py-1.5 text-sm dark:border-white/[0.06]">
                                        <summary className="cursor-pointer font-medium">{r.room} <span className="text-xs font-normal text-slate-500">({r.students.length})</span></summary>
                                        <ul className="mt-1 space-y-0.5 pb-1">
                                            {r.students.map(st => <li key={st.id}>{st.name}<span className="text-xs text-slate-500">{[st.class, st.bed ? `Bed ${st.bed}` : null].filter(Boolean).map(t => ` · ${t}`).join('')}</span></li>)}
                                        </ul>
                                    </details>
                                ))}
                            </div>
                        )}
                    </Panel>
                )}

                {store && (
                    <Panel title="Store" description={`${store.items} item${store.items === 1 ? '' : 's'} in stock records`} action={<Link href="/school/inventory/items"><Button variant="outline" size="sm"><Package className="size-4" /> Items</Button></Link>}>
                        {store.low_count === 0 ? (
                            <p className="text-sm text-emerald-700 dark:text-emerald-400">Nothing is running low.</p>
                        ) : (
                            <>
                                <p className="mb-2 text-sm font-medium text-red-600">{store.low_count} item{store.low_count === 1 ? ' is' : 's are'} at or below the minimum</p>
                                <ul className="divide-y divide-slate-100 text-sm dark:divide-white/[0.06]">
                                    {store.low.map(i => (
                                        <li key={i.id} className="flex justify-between py-2">
                                            <span>{i.name}</span>
                                            <span className="tabular-nums text-slate-500">{i.stock} {i.unit} left (minimum {i.minimum})</span>
                                        </li>
                                    ))}
                                </ul>
                            </>
                        )}
                    </Panel>
                )}

                {nothing && (
                    <Panel><p className="text-sm text-slate-500">Your account has no work areas yet. Ask the school admin to check your role.</p></Panel>
                )}

                {announcements.length > 0 && (
                    <Panel title="Announcements" action={<Link href="/school/communication/announcements"><Button variant="ghost" size="sm"><Megaphone className="size-4" /> All</Button></Link>}>
                        <ul className="space-y-3">
                            {announcements.map(a => (
                                <li key={a.id}>
                                    <p className="text-sm font-medium text-slate-900 dark:text-white">{a.title} <span className="text-xs font-normal text-slate-400">{a.date}</span></p>
                                    <ExpandableText text={a.body} className="text-sm text-slate-600 dark:text-slate-300" />
                                </li>
                            ))}
                        </ul>
                    </Panel>
                )}
            </div>

            <Dialog open={signingIn} onOpenChange={setSigningIn}>
                <DialogContent className="max-w-md">
                    <DialogHeader><DialogTitle>Sign in a visitor</DialogTitle></DialogHeader>
                    <form onSubmit={signIn} className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <Label>Visitor name *</Label>
                                <Input value={visitor.data.name} onChange={e => visitor.setData('name', e.target.value)} />
                                {visitor.errors.name && <p className="mt-1 text-xs text-red-500">{visitor.errors.name}</p>}
                            </div>
                            <div>
                                <Label>Phone *</Label>
                                <Input value={visitor.data.phone} onChange={e => visitor.setData('phone', e.target.value)} />
                                {visitor.errors.phone && <p className="mt-1 text-xs text-red-500">{visitor.errors.phone}</p>}
                            </div>
                        </div>
                        <div>
                            <Label>Reason for the visit *</Label>
                            <Input value={visitor.data.purpose} onChange={e => visitor.setData('purpose', e.target.value)} placeholder="e.g. Meeting with teacher" />
                            {visitor.errors.purpose && <p className="mt-1 text-xs text-red-500">{visitor.errors.purpose}</p>}
                        </div>
                        <div>
                            <Label>Who they came to see *</Label>
                            <Input value={visitor.data.person_to_meet} onChange={e => visitor.setData('person_to_meet', e.target.value)} placeholder="e.g. Mr. Ahmed" />
                            {visitor.errors.person_to_meet && <p className="mt-1 text-xs text-red-500">{visitor.errors.person_to_meet}</p>}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setSigningIn(false)}>Cancel</Button>
                            <Button type="submit" disabled={visitor.processing}>Sign in</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

function LoanList({ title, lines, late }: { title: string; lines: LoanLine[]; late?: boolean }) {
    return (
        <div className="mt-4">
            <p className="text-sm font-medium">{title}</p>
            <ul className="divide-y divide-slate-100 text-sm dark:divide-white/[0.06]">
                {lines.map(l => (
                    <li key={l.id} className="flex justify-between gap-3 py-2">
                        <span><span className="font-medium">{l.book}</span> <span className="text-slate-500">with {l.who}</span></span>
                        <span className={`text-xs ${late ? 'text-red-600' : 'text-slate-500'}`}>{late ? `${l.days_late} day${l.days_late === 1 ? '' : 's'} late` : `due ${l.due}`}</span>
                    </li>
                ))}
            </ul>
        </div>
    );
}
