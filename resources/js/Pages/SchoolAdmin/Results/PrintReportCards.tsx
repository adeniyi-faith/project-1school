import { useEffect, useRef, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { EmptyState, PageHeader, Panel, Pill } from '@/components/app/kit';
import { ResultStatusPill } from '@/components/results/ResultStatus';
import { Download, FileArchive, Printer, RotateCw } from 'lucide-react';
import type { ResultStatus, TermOption } from '@/Types';

interface SheetRow { id: number; class_name: string; status: ResultStatus; students: number }
interface ExportRow {
    id: number;
    label: string;
    layout: 'class' | 'student';
    status: 'running' | 'ready' | 'failed';
    done: number;
    total: number;
    cards: number;
    error: string | null;
    by: string | null;
    created_at: string | null;
}

interface Props {
    terms: TermOption[];
    termId: number | null;
    sheets: SheetRow[];
    chosen: number[];
    exports: ExportRow[];
    keepDays: number;
}

interface PrintForm { term_id: string; sheet_ids: number[]; type: 'term' | 'session'; layout: 'class' | 'student' }

export default function PrintReportCards({ terms, termId, sheets, chosen, exports, keepDays }: Props) {
    const usable = sheets.filter(s => s.students > 0);
    const form = useForm<PrintForm>({
        term_id: termId ? String(termId) : '',
        sheet_ids: chosen.filter(id => usable.some(s => s.id === id)),
        type: 'term',
        layout: 'class',
    });
    const termItems = terms.map(t => ({ value: String(t.id), label: t.is_current ? `${t.label} (current)` : t.label }));
    const allChosen = usable.length > 0 && usable.every(s => form.data.sheet_ids.includes(s.id));
    const running = exports.find(e => e.status === 'running');
    const [stepError, setStepError] = useState(false);
    const busy = useRef(false);
    const [tick, setTick] = useState(0);

    // Ask the server for the next step until the download is ready. Each step is one short request.
    useEffect(() => {
        if (!running || busy.current || stepError) return;
        busy.current = true;
        router.post(`/school/results/print/${running.id}/step`, {}, {
            preserveScroll: true,
            preserveState: true,
            onError: () => setStepError(true),
            onFinish: visit => {
                busy.current = false;
                if (visit.cancelled || visit.interrupted) setStepError(true);
                // Look again once this request is fully done, in case the page updated while it was busy
                setTick(t => t + 1);
            },
        });
    }, [running, stepError, tick]);

    function toggle(id: number, on: boolean) {
        form.setData('sheet_ids', on ? [...form.data.sheet_ids, id] : form.data.sheet_ids.filter(x => x !== id));
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        setStepError(false);
        form.post('/school/results/print', { preserveScroll: true });
    }

    return (
        <AppLayout breadcrumbs={[{ label: 'Academic' }, { label: 'Term results', href: '/school/results' }, { label: 'Print report cards' }]}>
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader
                    title="Print report cards"
                    description="Download report cards for one class, several classes or the whole school in one ZIP file."
                />

                {terms.length === 0 ? (
                    <Panel><EmptyState icon={Printer} title="No terms yet" text="Add a school year on the Terms page first." /></Panel>
                ) : (
                    <Panel>
                        <form onSubmit={submit} className="space-y-5">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label>Term</Label>
                                    <Select items={termItems} value={form.data.term_id} onValueChange={v => v && router.get('/school/results/print', { term_id: v }, { preserveScroll: true })}>
                                        <SelectTrigger className="w-full"><SelectValue placeholder="Choose a term" /></SelectTrigger>
                                        <SelectContent>{termItems.map(t => <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>)}</SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <fieldset className="space-y-2">
                                    <legend className="mb-1 text-sm font-medium">Which card</legend>
                                    <Choice name="type" checked={form.data.type === 'term'} onChange={() => form.setData('type', 'term')} title="Term report card" text="This term's scores, positions and comments." />
                                    <Choice name="type" checked={form.data.type === 'session'} onChange={() => form.setData('type', 'session')} title="Full-year report card" text="Every term of this school year so far." />
                                </fieldset>
                                <fieldset className="space-y-2">
                                    <legend className="mb-1 text-sm font-medium">Files in the ZIP</legend>
                                    <Choice name="layout" checked={form.data.layout === 'class'} onChange={() => form.setData('layout', 'class')} title="One PDF per class" text="Best for printing a whole class at once." />
                                    <Choice name="layout" checked={form.data.layout === 'student'} onChange={() => form.setData('layout', 'student')} title="One PDF per student" text="A folder per class. Best for sending cards to parents." />
                                </fieldset>
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label>Classes</Label>
                                    {usable.length > 0 && (
                                        <button type="button" className="text-xs text-indigo-600 hover:underline dark:text-indigo-400" onClick={() => form.setData('sheet_ids', allChosen ? [] : usable.map(s => s.id))}>
                                            {allChosen ? 'Clear all' : 'Whole school'}
                                        </button>
                                    )}
                                </div>
                                {sheets.length === 0 ? (
                                    <p className="text-sm text-slate-500">No classes have results for this term yet.</p>
                                ) : (
                                    <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                        {sheets.map(s => (
                                            <label key={s.id} className={`flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-white/10 ${s.students > 0 ? '' : 'opacity-50'}`}>
                                                <Checkbox checked={form.data.sheet_ids.includes(s.id)} disabled={s.students === 0} onCheckedChange={v => toggle(s.id, !!v)} />
                                                <span className="flex-1 truncate">{s.class_name}</span>
                                                {s.students > 0 ? <ResultStatusPill status={s.status} /> : <span className="text-xs text-slate-500">No results yet</span>}
                                            </label>
                                        ))}
                                    </div>
                                )}
                                {form.errors.sheet_ids && <p className="text-xs text-red-500">{form.errors.sheet_ids}</p>}
                                <p className="text-xs text-slate-500">Cards for results that are not published yet are marked "Preview".</p>
                            </div>

                            <div className="flex justify-end">
                                <Button type="submit" disabled={form.processing || !!running || form.data.sheet_ids.length === 0} className="bg-indigo-600 text-white hover:bg-indigo-700">
                                    <FileArchive className="size-4" /> Make ZIP for {form.data.sheet_ids.length} {form.data.sheet_ids.length === 1 ? 'class' : 'classes'}
                                </Button>
                            </div>
                        </form>
                    </Panel>
                )}

                {exports.length > 0 && (
                    <Panel flush>
                        <div className="border-b border-slate-100 px-5 py-4 dark:border-white/[0.06]">
                            <p className="font-medium text-slate-900 dark:text-white">Your downloads</p>
                            <p className="text-xs text-slate-500">Downloads are kept for {keepDays} days.</p>
                        </div>
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {exports.map(e => (
                                <li key={e.id} className="flex flex-wrap items-center gap-3 px-5 py-3">
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-medium text-slate-900 dark:text-white">{e.label}</p>
                                        <p className="text-xs text-slate-500">
                                            {e.layout === 'class' ? 'One PDF per class' : 'One PDF per student'}
                                            {e.cards > 0 && ` · ${e.cards} ${e.cards === 1 ? 'card' : 'cards'}`}
                                            {e.by && ` · by ${e.by}`}
                                            {e.created_at && ` · ${new Date(e.created_at).toLocaleString()}`}
                                        </p>
                                        {e.status === 'running' && (
                                            <div className="mt-2 max-w-sm">
                                                <div className="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                                                    <div className="h-full rounded-full bg-indigo-600 transition-all" style={{ width: `${e.total ? Math.round((e.done / e.total) * 100) : 0}%` }} />
                                                </div>
                                                <p className="mt-1 text-xs text-slate-500">Step {Math.min(e.done + 1, e.total)} of {e.total}. Keep this page open.</p>
                                            </div>
                                        )}
                                        {e.status === 'failed' && e.error && <p className="mt-1 text-xs text-red-600">{e.error}</p>}
                                    </div>
                                    {e.status === 'ready' && (
                                        <a href={`/school/results/print/${e.id}/download`} className="inline-flex h-9 items-center gap-2 rounded-lg bg-indigo-600 px-3.5 text-sm font-medium text-white hover:bg-indigo-700">
                                            <Download className="size-4" /> Download ZIP
                                        </a>
                                    )}
                                    {e.status === 'running' && stepError && (
                                        <Button variant="outline" onClick={() => setStepError(false)}><RotateCw className="size-4" /> Carry on</Button>
                                    )}
                                    {e.status === 'running' && !stepError && <Pill tone="info">Working</Pill>}
                                    {e.status === 'failed' && <Pill tone="warn">Not made</Pill>}
                                </li>
                            ))}
                        </ul>
                        {running && stepError && (
                            <p className="border-t border-slate-100 px-5 py-3 text-sm text-amber-700 dark:border-white/[0.06] dark:text-amber-300">
                                A step did not finish, perhaps because the connection dropped. Press "Carry on" to continue from where it stopped.
                            </p>
                        )}
                    </Panel>
                )}
            </div>
        </AppLayout>
    );
}

function Choice({ name, checked, onChange, title, text }: { name: string; checked: boolean; onChange: () => void; title: string; text: string }) {
    return (
        <label className={`flex cursor-pointer gap-3 rounded-lg border px-3 py-2.5 text-sm ${checked ? 'border-indigo-500 bg-indigo-50/60 dark:bg-indigo-500/10' : 'border-slate-200 dark:border-white/10'}`}>
            <input type="radio" name={name} checked={checked} onChange={onChange} className="mt-1" />
            <span>
                <span className="block font-medium text-slate-900 dark:text-white">{title}</span>
                <span className="block text-xs text-slate-500">{text}</span>
            </span>
        </label>
    );
}
