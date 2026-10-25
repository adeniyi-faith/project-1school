import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { EmptyState, PageHeader, Panel, Pill, StatStrip } from '@/components/app/kit';
import { IdCard, Printer, ScanLine, Sparkles } from 'lucide-react';

type CardState = 'valid' | 'expired' | 'replaced' | 'revoked';
interface Row {
    id: number; name: string; emp_id: string | null; role: string; has_photo: boolean; photo_url: string | null;
    card: { id: number; card_no: string; state: CardState; expires_on: string | null; prints: number } | null;
}
interface Props {
    staff: { data: Row[]; meta: { total: number; from: number | null; to: number | null; last_page: number }; links: { prev: string | null; next: string | null } };
    stats: { staff: number; with_card: number; without_card: number; without_photo: number };
    filters: { show?: string; search?: string };
    max_per_print: number;
}

const selectClass = 'h-9 rounded-lg border border-slate-200 bg-transparent px-2 text-sm dark:border-white/10 dark:bg-slate-900';
const SHOW = [
    { value: '', label: 'All staff' }, { value: 'without', label: 'No working card' }, { value: 'with', label: 'Have a card' }, { value: 'no_photo', label: 'No photo' },
];
const STATE_TONE: Record<CardState, 'good' | 'warn' | 'bad' | 'neutral'> = { valid: 'good', expired: 'warn', replaced: 'neutral', revoked: 'bad' };

export default function StaffIdCards({ staff, stats, filters, max_per_print }: Props) {
    const [picked, setPicked] = useState<number[]>([]);
    const [search, setSearch] = useState(filters.search ?? '');
    const [printing, setPrinting] = useState(false);
    const [layout, setLayout] = useState<'sheet' | 'single'>('sheet');
    const [replacing, setReplacing] = useState<Row | null>(null);
    const [reason, setReason] = useState('');

    const go = (patch: Record<string, string | undefined>) =>
        router.get('/school/id-cards/staff', { ...filters, ...patch }, { preserveState: true, preserveScroll: true, replace: true, onSuccess: () => setPicked([]) });

    const pageIds = staff.data.map(s => s.id);
    const allPicked = pageIds.length > 0 && pageIds.every(id => picked.includes(id));

    function printUrl() {
        const q = new URLSearchParams();
        picked.forEach(id => q.append('staff_ids[]', String(id)));
        q.set('layout', layout);
        return `/school/id-cards/staff/print?${q.toString()}`;
    }

    const actions = (
        <div className="flex flex-wrap gap-2">
            <Button onClick={() => router.post('/school/id-cards/staff/issue-missing', {}, { preserveScroll: true })} className="bg-indigo-600 text-white hover:bg-indigo-700"><Sparkles className="size-4" /> Make missing cards</Button>
            <Button variant="outline" onClick={() => setPrinting(true)}><Printer className="size-4" /> Print</Button>
            <Link href="/school/id-cards/scan" className="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 text-sm font-medium hover:bg-slate-50 max-md:h-10 dark:border-white/10 dark:hover:bg-white/5"><ScanLine className="size-4" /> Scan</Link>
            <Link href="/school/id-cards" className="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 text-sm font-medium hover:bg-slate-50 max-md:h-10 dark:border-white/10 dark:hover:bg-white/5"><IdCard className="size-4" /> Student cards</Link>
        </div>
    );

    return (
        <AppLayout title="Staff ID cards">
            <div className="space-y-6">
                <PageHeader title="Staff ID cards" description="Cards for teachers and other staff, in the same design as the student cards (change it under Designs on the student cards page). The QR code can be scanned at the gate to mark a staff member present." actions={actions} />
                <div className="flex flex-wrap gap-2 md:hidden">{actions}</div>

                <StatStrip items={[
                    { label: 'Active staff', value: stats.staff },
                    { label: 'With a working card', value: stats.with_card, tone: 'good' },
                    { label: 'Need a card', value: stats.without_card, tone: stats.without_card ? 'warn' : 'good', hint: stats.without_card ? 'Use “Make missing cards”' : undefined },
                    { label: 'No photo yet', value: stats.without_photo, tone: stats.without_photo ? 'warn' : 'good', hint: stats.without_photo ? 'Add it on the staff member’s page' : undefined },
                ]} />

                <div className="flex flex-wrap items-center gap-2">
                    <select aria-label="Show" className={selectClass} value={filters.show ?? ''} onChange={e => go({ show: e.target.value || undefined, page: undefined })}>
                        {SHOW.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
                    </select>
                    <form onSubmit={e => { e.preventDefault(); go({ search: search || undefined, page: undefined }); }} className="flex gap-2">
                        <Input value={search} onChange={e => setSearch(e.target.value)} placeholder="Name or staff ID" className="h-9 w-56" aria-label="Search" />
                    </form>
                    {picked.length > 0 && (
                        <Button size="sm" onClick={() => setPrinting(true)} className="ml-auto bg-indigo-600 text-white hover:bg-indigo-700"><Printer className="size-4" /> Print {picked.length} picked</Button>
                    )}
                </div>

                <Panel flush>
                    {staff.data.length === 0 ? (
                        <EmptyState icon={IdCard} title="No staff match" />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b border-slate-100 text-left text-xs text-slate-500 dark:border-white/[0.06]">
                                    <tr>
                                        <th className="w-10 px-4 py-2"><input type="checkbox" aria-label="Pick all on this page" checked={allPicked} onChange={() => setPicked(allPicked ? picked.filter(id => !pageIds.includes(id)) : [...new Set([...picked, ...pageIds])])} /></th>
                                        <th className="px-4 py-2">Staff member</th>
                                        <th className="px-4 py-2">Role</th>
                                        <th className="px-4 py-2">Photo</th>
                                        <th className="px-4 py-2">Card</th>
                                        <th className="px-4 py-2" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                                    {staff.data.map(s => (
                                        <tr key={s.id}>
                                            <td className="px-4 py-2"><input type="checkbox" aria-label={`Pick ${s.name}`} checked={picked.includes(s.id)} onChange={() => setPicked(picked.includes(s.id) ? picked.filter(x => x !== s.id) : [...picked, s.id])} /></td>
                                            <td className="px-4 py-2">
                                                <div className="flex items-center gap-2">
                                                    <div className="size-9 shrink-0 overflow-hidden rounded-md bg-slate-100 dark:bg-slate-800">
                                                        {s.photo_url ? <img src={s.photo_url} alt="" className="size-full object-cover" loading="lazy" /> : <span className="flex size-full items-center justify-center text-xs font-semibold text-slate-400">{s.name[0]}</span>}
                                                    </div>
                                                    <div>
                                                        <Link href={`/school/staff/${s.id}`} className="font-medium hover:underline">{s.name}</Link>
                                                        <p className="text-xs text-slate-500">{s.emp_id}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-2">{s.role || <span className="text-xs text-slate-400">—</span>}</td>
                                            <td className="px-4 py-2">{s.has_photo ? <Pill tone="good">Yes</Pill> : <Pill tone="warn">None</Pill>}</td>
                                            <td className="px-4 py-2">
                                                {s.card ? (
                                                    <div>
                                                        <span className="font-mono text-xs">{s.card.card_no}</span>{' '}
                                                        <Pill tone={STATE_TONE[s.card.state]}>{s.card.state}</Pill>
                                                        <p className="text-xs text-slate-500">{s.card.prints ? `Printed ${s.card.prints}×` : 'Not printed yet'}{s.card.expires_on ? ` · until ${s.card.expires_on}` : ''}</p>
                                                    </div>
                                                ) : <span className="text-xs text-slate-400">No card</span>}
                                            </td>
                                            <td className="px-4 py-2 text-right">
                                                {s.card
                                                    ? <button type="button" className="text-xs font-medium text-indigo-600 hover:underline" onClick={() => { setReason(''); setReplacing(s); }}>Replace</button>
                                                    : <button type="button" className="text-xs font-medium text-indigo-600 hover:underline" onClick={() => router.post(`/school/id-cards/staff/${s.id}/issue`, {}, { preserveScroll: true })}>Make card</button>}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    {staff.meta.total > 0 && (
                        <div className="flex items-center justify-between border-t border-slate-100 px-4 py-2 text-xs text-slate-500 dark:border-white/[0.06]">
                            <span>{staff.meta.from}–{staff.meta.to} of {staff.meta.total}{picked.length ? ` · ${picked.length} picked` : ''}</span>
                            <div className="flex gap-2">
                                {staff.links.prev && <Link href={staff.links.prev} preserveScroll className="rounded border border-slate-200 px-2 py-1 dark:border-white/10">Previous</Link>}
                                {staff.links.next && <Link href={staff.links.next} preserveScroll className="rounded border border-slate-200 px-2 py-1 dark:border-white/10">Next</Link>}
                            </div>
                        </div>
                    )}
                </Panel>
            </div>

            <Dialog open={printing} onOpenChange={setPrinting}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Print staff ID cards</DialogTitle></DialogHeader>
                    <div className="space-y-4 pt-2">
                        <p className="text-sm text-slate-500">
                            {picked.length ? `The ${picked.length} staff member${picked.length === 1 ? '' : 's'} you picked.` : 'Every active staff member.'} Anyone without a card gets one first. At most {max_per_print} cards at a time.
                        </p>
                        <div className="space-y-2 text-sm">
                            <Label>Paper</Label>
                            <label className="flex items-start gap-2"><input type="radio" className="mt-0.5" checked={layout === 'sheet'} onChange={() => setLayout('sheet')} /><span>A4 sheets to cut out <span className="block text-xs text-slate-500">Fronts on one page, backs on the next, lined up for printing on both sides.</span></span></label>
                            <label className="flex items-start gap-2"><input type="radio" className="mt-0.5" checked={layout === 'single'} onChange={() => setLayout('single')} /><span>One card per page <span className="block text-xs text-slate-500">For a PVC ID card printer.</span></span></label>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setPrinting(false)}>Cancel</Button>
                            <a href={printUrl()} target="_blank" rel="noreferrer" onClick={() => setTimeout(() => { setPrinting(false); router.reload(); }, 1500)}
                                className="inline-flex h-9 items-center gap-1.5 rounded-lg bg-indigo-600 px-3 text-sm font-medium text-white hover:bg-indigo-700">
                                <Printer className="size-4" /> Open PDF
                            </a>
                        </DialogFooter>
                    </div>
                </DialogContent>
            </Dialog>

            <Dialog open={replacing !== null} onOpenChange={o => !o && setReplacing(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Replace {replacing?.name}'s card</DialogTitle></DialogHeader>
                    <div className="space-y-4 pt-2">
                        <p className="text-sm text-slate-500">A new card is made and the old one stops scanning.</p>
                        <div className="space-y-1.5">
                            <Label htmlFor="replace-reason">Why?</Label>
                            <Input id="replace-reason" value={reason} onChange={e => setReason(e.target.value)} placeholder="e.g. Lost, damaged" />
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setReplacing(null)}>Cancel</Button>
                            <Button disabled={!reason.trim()} className="bg-indigo-600 text-white hover:bg-indigo-700"
                                onClick={() => replacing && router.post(`/school/id-cards/staff/${replacing.id}/issue`, { reason }, { preserveScroll: true, onSuccess: () => setReplacing(null) })}>
                                Make new card
                            </Button>
                        </DialogFooter>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
