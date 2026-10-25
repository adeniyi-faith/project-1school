import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { EmptyState, PageHeader, Panel, Pill, StatStrip } from '@/components/app/kit';
import { IdCard, Palette, Printer, ScanLine, Sparkles } from 'lucide-react';

type CardState = 'valid' | 'expired' | 'replaced' | 'revoked';
interface Row {
    id: number; name: string; admission_no: string; class: string;
    house: { name: string; colour: string } | null; has_photo: boolean; photo_url: string | null;
    card: { id: number; card_no: string; state: CardState; expires_on: string | null; prints: number } | null;
}
interface Props {
    students: { data: Row[]; meta: { total: number; from: number | null; to: number | null; last_page: number }; links: { prev: string | null; next: string | null } };
    stats: { students: number; with_card: number; without_card: number; without_photo: number };
    classes: { id: number; name: string }[];
    designs: { id: number; name: string; orientation: string; is_default: boolean }[];
    filters: { class_id?: string; show?: string; search?: string };
    max_per_print: number;
    can: { staff_cards: boolean; issue: boolean; scan: boolean; design: boolean };
}

const selectClass = 'h-9 rounded-lg border border-slate-200 bg-transparent px-2 text-sm dark:border-white/10 dark:bg-slate-900';
const SHOW = [
    { value: '', label: 'All students' }, { value: 'without', label: 'No working card' }, { value: 'with', label: 'Have a card' },
    { value: 'not_printed', label: 'Card not printed yet' }, { value: 'no_photo', label: 'No photo' },
];
const STATE_TONE: Record<CardState, 'good' | 'warn' | 'bad' | 'neutral'> = { valid: 'good', expired: 'warn', replaced: 'neutral', revoked: 'bad' };

export default function IdCardsIndex({ students, stats, classes, designs, filters, max_per_print, can }: Props) {
    const [picked, setPicked] = useState<number[]>([]);
    const [search, setSearch] = useState(filters.search ?? '');
    const [making, setMaking] = useState(false);
    const [makeClasses, setMakeClasses] = useState<number[]>([]);
    const [printing, setPrinting] = useState(false);
    const [print, setPrint] = useState({ who: 'picked' as 'picked' | 'classes', class_ids: [] as number[], design_id: String(designs.find(d => d.is_default)?.id ?? ''), layout: 'sheet', not_printed: false });

    const go = (patch: Record<string, string | undefined>) =>
        router.get('/school/id-cards', { ...filters, ...patch }, { preserveState: true, preserveScroll: true, replace: true, onSuccess: () => setPicked([]) });

    const toggle = (list: number[], id: number) => (list.includes(id) ? list.filter(x => x !== id) : [...list, id]);
    const pageIds = students.data.map(s => s.id);
    const allPicked = pageIds.length > 0 && pageIds.every(id => picked.includes(id));

    function openPrint(classIds?: number[]) {
        setPrint(p => ({ ...p, who: picked.length && !classIds ? 'picked' : 'classes', class_ids: classIds ?? (filters.class_id ? [Number(filters.class_id)] : p.class_ids) }));
        setPrinting(true);
    }

    function printUrl() {
        const q = new URLSearchParams();
        (print.who === 'picked' ? picked : []).forEach(id => q.append('student_ids[]', String(id)));
        (print.who === 'classes' ? print.class_ids : []).forEach(id => q.append('class_ids[]', String(id)));
        if (print.design_id) q.set('design_id', print.design_id);
        q.set('layout', print.layout);
        if (print.not_printed) q.set('not_printed', '1');
        return `/school/id-cards/print?${q.toString()}`;
    }

    const actions = (
        <div className="flex flex-wrap gap-2">
            {can.issue && <Button onClick={() => { setMakeClasses([]); setMaking(true); }} className="bg-indigo-600 text-white hover:bg-indigo-700"><Sparkles className="size-4" /> Make missing cards</Button>}
            {can.issue && <Button variant="outline" onClick={() => openPrint()}><Printer className="size-4" /> Print</Button>}
            {can.scan && <Link href="/school/id-cards/scan" className="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 text-sm max-md:h-10 font-medium hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5"><ScanLine className="size-4" /> Scan</Link>}
            {can.staff_cards && <Link href="/school/id-cards/staff" className="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 text-sm max-md:h-10 font-medium hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5"><IdCard className="size-4" /> Staff cards</Link>}
            <Link href="/school/id-cards/designs" className="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 text-sm max-md:h-10 font-medium hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5"><Palette className="size-4" /> Designs</Link>
        </div>
    );

    return (
        <AppLayout title="ID cards">
            <div className="space-y-6">
                <PageHeader title="Student ID cards" description="Make cards for the whole school at once, then again for new students. Each card has the student's photo and a QR code that staff scan to check the card, mark attendance or see fees owed." actions={actions} />
                <div className="flex flex-wrap gap-2 md:hidden">{actions}</div>

                <StatStrip items={[
                    { label: 'Active students', value: stats.students },
                    { label: 'With a working card', value: stats.with_card, tone: 'good' },
                    { label: 'Need a card', value: stats.without_card, tone: stats.without_card ? 'warn' : 'good', hint: stats.without_card && can.issue ? 'Use “Make missing cards”' : undefined },
                    { label: 'No photo yet', value: stats.without_photo, tone: stats.without_photo ? 'warn' : 'good', hint: stats.without_photo ? 'Upload on the student page, or many at once on the Students list' : undefined },
                ]} />

                <div className="flex flex-wrap items-center gap-2">
                    <select aria-label="Class" className={selectClass} value={filters.class_id ?? ''} onChange={e => go({ class_id: e.target.value || undefined, page: undefined })}>
                        <option value="">All classes</option>
                        {classes.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                    </select>
                    <select aria-label="Show" className={selectClass} value={filters.show ?? ''} onChange={e => go({ show: e.target.value || undefined, page: undefined })}>
                        {SHOW.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
                    </select>
                    <form onSubmit={e => { e.preventDefault(); go({ search: search || undefined, page: undefined }); }} className="flex gap-2">
                        <Input value={search} onChange={e => setSearch(e.target.value)} placeholder="Name or admission no." className="h-9 w-56" aria-label="Search" />
                    </form>
                    {picked.length > 0 && can.issue && (
                        <Button size="sm" onClick={() => openPrint()} className="ml-auto bg-indigo-600 text-white hover:bg-indigo-700"><Printer className="size-4" /> Print {picked.length} picked</Button>
                    )}
                </div>

                <Panel flush>
                    {students.data.length === 0 ? (
                        <EmptyState icon={IdCard} title="No students match" />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b border-slate-100 text-left text-xs text-slate-500 dark:border-white/[0.06]">
                                    <tr>
                                        {can.issue && <th className="w-10 px-4 py-2"><input type="checkbox" aria-label="Pick all on this page" checked={allPicked} onChange={() => setPicked(allPicked ? picked.filter(id => !pageIds.includes(id)) : [...new Set([...picked, ...pageIds])])} /></th>}
                                        <th className="px-4 py-2">Student</th>
                                        <th className="px-4 py-2">Class</th>
                                        <th className="px-4 py-2">Photo</th>
                                        <th className="px-4 py-2">Card</th>
                                        <th className="px-4 py-2" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                                    {students.data.map(s => (
                                        <tr key={s.id}>
                                            {can.issue && <td className="px-4 py-2"><input type="checkbox" aria-label={`Pick ${s.name}`} checked={picked.includes(s.id)} onChange={() => setPicked(toggle(picked, s.id))} /></td>}
                                            <td className="px-4 py-2">
                                                <div className="flex items-center gap-2">
                                                    <div className="size-9 shrink-0 overflow-hidden rounded-md bg-slate-100 dark:bg-slate-800">
                                                        {s.photo_url ? <img src={s.photo_url} alt="" className="size-full object-cover" loading="lazy" /> : <span className="flex size-full items-center justify-center text-xs font-semibold text-slate-400">{s.name[0]}</span>}
                                                    </div>
                                                    <div>
                                                        <Link href={`/school/students/${s.id}?tab=id-card`} className="font-medium hover:underline">{s.name}</Link>
                                                        <p className="text-xs text-slate-500">{s.admission_no}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-2">
                                                {s.class}
                                                {s.house && <span className="ml-1.5 inline-flex items-center gap-1 text-xs text-slate-500"><span className="size-2 rounded-full" style={{ background: s.house.colour }} />{s.house.name}</span>}
                                            </td>
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
                                                {can.issue && (s.card
                                                    ? <a href={`/school/students/${s.id}/id-card`} target="_blank" rel="noreferrer" className="text-xs font-medium text-indigo-600 hover:underline">Card PDF</a>
                                                    : <button type="button" className="text-xs font-medium text-indigo-600 hover:underline" onClick={() => router.post(`/school/students/${s.id}/id-card`, {}, { preserveScroll: true })}>Make card</button>)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    {students.meta.total > 0 && (
                        <div className="flex items-center justify-between border-t border-slate-100 px-4 py-2 text-xs text-slate-500 dark:border-white/[0.06]">
                            <span>{students.meta.from}–{students.meta.to} of {students.meta.total}{picked.length ? ` · ${picked.length} picked` : ''}</span>
                            <div className="flex gap-2">
                                {students.links.prev && <Link href={students.links.prev} preserveScroll className="rounded border border-slate-200 px-2 py-1 dark:border-white/10">Previous</Link>}
                                {students.links.next && <Link href={students.links.next} preserveScroll className="rounded border border-slate-200 px-2 py-1 dark:border-white/10">Next</Link>}
                            </div>
                        </div>
                    )}
                </Panel>
            </div>

            {/* Make missing cards */}
            <Dialog open={making} onOpenChange={setMaking}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Make missing cards</DialogTitle></DialogHeader>
                    <div className="space-y-4 pt-2">
                        <p className="text-sm text-slate-500">Every active student without a working card gets one: new students, and anyone whose card expired. Students who already have a card keep it, so you can run this again whenever new students join.</p>
                        <ClassPicker classes={classes} value={makeClasses} onChange={setMakeClasses} emptyLabel="The whole school" />
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setMaking(false)}>Cancel</Button>
                            <Button className="bg-indigo-600 text-white hover:bg-indigo-700" onClick={() => router.post('/school/id-cards/issue-missing', { class_ids: makeClasses }, { preserveScroll: true, onSuccess: () => setMaking(false) })}>Make cards</Button>
                        </DialogFooter>
                    </div>
                </DialogContent>
            </Dialog>

            {/* Print */}
            <Dialog open={printing} onOpenChange={setPrinting}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader><DialogTitle>Print ID cards</DialogTitle></DialogHeader>
                    <div className="space-y-4 pt-2">
                        <div className="space-y-2 text-sm">
                            <Label>Whose cards?</Label>
                            {picked.length > 0 && (
                                <label className="flex items-center gap-2"><input type="radio" checked={print.who === 'picked'} onChange={() => setPrint({ ...print, who: 'picked' })} /> The {picked.length} student{picked.length === 1 ? '' : 's'} I picked</label>
                            )}
                            <label className="flex items-center gap-2"><input type="radio" checked={print.who === 'classes'} onChange={() => setPrint({ ...print, who: 'classes' })} /> Whole classes</label>
                            {print.who === 'classes' && <ClassPicker classes={classes} value={print.class_ids} onChange={ids => setPrint({ ...print, class_ids: ids })} />}
                            <p className="text-xs text-slate-500">Students without a card get one first. At most {max_per_print} cards at a time.</p>
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="pr-design">Design</Label>
                            <select id="pr-design" className={`${selectClass} w-full`} value={print.design_id} onChange={e => setPrint({ ...print, design_id: e.target.value })}>
                                {designs.map(d => <option key={d.id} value={d.id}>{d.name} ({d.orientation})</option>)}
                            </select>
                        </div>
                        <div className="space-y-2 text-sm">
                            <Label>Paper</Label>
                            <label className="flex items-start gap-2"><input type="radio" className="mt-0.5" checked={print.layout === 'sheet'} onChange={() => setPrint({ ...print, layout: 'sheet' })} /><span>A4 sheets to cut out <span className="block text-xs text-slate-500">Fronts on one page, backs on the next, lined up for printing on both sides. Laminate after cutting.</span></span></label>
                            <label className="flex items-start gap-2"><input type="radio" className="mt-0.5" checked={print.layout === 'single'} onChange={() => setPrint({ ...print, layout: 'single' })} /><span>One card per page <span className="block text-xs text-slate-500">For a PVC ID card printer.</span></span></label>
                        </div>
                        <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={print.not_printed} onChange={e => setPrint({ ...print, not_printed: e.target.checked })} /> Only cards not printed before</label>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setPrinting(false)}>Cancel</Button>
                            <a href={printUrl()} target="_blank" rel="noreferrer" onClick={() => setTimeout(() => { setPrinting(false); router.reload(); }, 1500)}
                                aria-disabled={print.who === 'classes' ? !print.class_ids.length : !picked.length}
                                className={`inline-flex h-9 items-center gap-1.5 rounded-lg bg-indigo-600 px-3 text-sm font-medium text-white hover:bg-indigo-700 ${(print.who === 'classes' ? !print.class_ids.length : !picked.length) ? 'pointer-events-none opacity-50' : ''}`}>
                                <Printer className="size-4" /> Open PDF
                            </a>
                        </DialogFooter>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

function ClassPicker({ classes, value, onChange, emptyLabel }: { classes: { id: number; name: string }[]; value: number[]; onChange: (ids: number[]) => void; emptyLabel?: string }) {
    return (
        <div className="space-y-1.5">
            <div className="flex flex-wrap gap-1.5">
                {classes.map(c => (
                    <button key={c.id} type="button" onClick={() => onChange(value.includes(c.id) ? value.filter(x => x !== c.id) : [...value, c.id])}
                        className={`rounded-full border px-2.5 py-1 text-xs ${value.includes(c.id) ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300' : 'border-slate-200 dark:border-white/10'}`}>
                        {c.name}
                    </button>
                ))}
            </div>
            {emptyLabel && <p className="text-xs text-slate-500">{value.length ? `${value.length} class${value.length === 1 ? '' : 'es'} chosen` : `Nothing chosen = ${emptyLabel.toLowerCase()}`}</p>}
        </div>
    );
}
