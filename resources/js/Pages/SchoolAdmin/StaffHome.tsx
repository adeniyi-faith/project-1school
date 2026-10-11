import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { PageHeader, Panel, Pill, StatStrip } from '@/components/app/kit';
import { Bus, ClipboardList, Home, Megaphone, Package, Users } from 'lucide-react';
import ExpandableText from '@/components/ExpandableText';

interface FrontDesk {
    visitors_today: number; still_inside: number; open_inquiries: number;
    inside: { id: number; name: string; meeting: string; since: string | null }[];
}
interface Transport {
    vehicles: number;
    routes: { id: number; name: string; from: string | null; to: string | null; stops: number; students: number; vehicle: string | null; driver: string | null }[];
}
interface Hostel { beds: number; occupied: number; rooms_free: number; maintenance: number }
interface Store { items: number; low_count: number; low: { id: number; name: string; stock: number; minimum: number; unit: string }[] }

interface Props {
    name: string;
    panels: { front_desk?: FrontDesk; transport?: Transport; hostel?: Hostel; store?: Store };
    announcements: { id: number; title: string; body: string; date: string | null }[];
}

function greeting() {
    const h = new Date().getHours();
    return h < 12 ? 'Good morning' : h < 16 ? 'Good afternoon' : 'Good evening';
}

/** Home for front desk, drivers, wardens and store keepers: only the parts of the school their work covers */
export default function StaffHome({ name, panels, announcements }: Props) {
    const { front_desk: desk, transport, hostel, store } = panels;
    const nothing = !desk && !transport && !hostel && !store;

    return (
        <AppLayout title="Home">
            <div className="space-y-6">
                <PageHeader title={`${greeting()}, ${name.split(' ')[0]}`} description="Here is what needs your attention today." />

                {desk && (
                    <Panel title="Front desk" action={<Link href="/school/admissions/visitors"><Button variant="outline" size="sm"><Users className="size-4" /> Visitor log</Button></Link>}>
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
                                        <span className="text-xs text-slate-500">since {v.since}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                        <div className="mt-4">
                            <Link href="/school/admissions/inquiries"><Button variant="ghost" size="sm"><ClipboardList className="size-4" /> Admission inquiries</Button></Link>
                        </div>
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
        </AppLayout>
    );
}
