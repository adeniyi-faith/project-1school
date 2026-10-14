import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageHeader, Panel, StatStrip } from '@/components/app/kit';
import { ArrowLeft, FileSpreadsheet, KeyRound, Loader2, Printer } from 'lucide-react';

interface Props {
    classes: { id: number; name: string; students: number; with_login: number }[];
    sections: { id: number; class_id: number; name: string }[];
    totals: { students: number; with_login: number };
}
interface Progress { done: number; total: number; finished: boolean; made: number; skipped: number; problems: string[] }

/** Make sign-in details for a whole class or school in small steps, then print or download the sheet */
export default function Logins({ classes, sections, totals }: Props) {
    const [classId, setClassId] = useState('');
    const [sectionId, setSectionId] = useState('');
    const [parents, setParents] = useState(true);
    const [reset, setReset] = useState(false);
    const [running, setRunning] = useState(false);
    const [token, setToken] = useState<string | null>(null);
    const [progress, setProgress] = useState<Progress | null>(null);
    const [error, setError] = useState<string | null>(null);

    const classItems = [{ value: 'all', label: 'Every class' }, ...classes.map(c => ({ value: String(c.id), label: `${c.name} (${c.students})` }))];
    const armItems = [{ value: 'all', label: 'All arms' }, ...sections.filter(s => String(s.class_id) === classId).map(s => ({ value: String(s.id), label: s.name }))];

    async function run() {
        if (reset && !confirm('This gives everyone in the list a NEW password. Their old passwords stop working at once. Continue?')) return;
        setRunning(true);
        setError(null);
        setProgress(null);
        setToken(null);
        try {
            const start = await axios.post<{ token: string; total: number }>('/school/students/logins/start', {
                class_id: classId || null, section_id: sectionId || null, parents, reset,
            });
            setToken(start.data.token);
            let p: Progress;
            do {
                p = (await axios.post<Progress>(`/school/students/logins/${start.data.token}/step`)).data;
                setProgress(p);
            } while (!p.finished);
        } catch (e) {
            const errs = axios.isAxiosError(e) ? e.response?.data?.errors : null;
            setError(errs?.class_id?.[0] ?? 'Something went wrong. What was made so far is kept; run it again to carry on.');
        } finally {
            setRunning(false);
        }
    }

    const back = (
        <Link href="/school/students" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
            <ArrowLeft className="size-4" /> Students
        </Link>
    );

    return (
        <AppLayout breadcrumbs={[{ label: 'Academic' }, { label: 'Students', href: '/school/students' }, { label: 'Login details' }]}>
            <Head title="Student login details" />
            <div className="mx-auto max-w-4xl space-y-5 pb-24 md:pb-10">
                <PageHeader title="Student and parent login details" description="Make a username and password for every student (and their parents) in one go, then print the slips." actions={back} />
                <div className="flex flex-wrap gap-2 md:hidden">{back}</div>

                <StatStrip items={[
                    { label: 'Active students', value: totals.students },
                    { label: 'Already have a login', value: totals.with_login, tone: 'good' },
                    { label: 'Still without', value: totals.students - totals.with_login, tone: totals.students > totals.with_login ? 'warn' : 'default' },
                ]} />

                <Panel title="Who should get login details?" description="Students who already have a login are left alone, so you can run this again later for new students.">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="cls">Class</Label>
                            <Select items={classItems} value={classId || 'all'} onValueChange={v => { setClassId(!v || v === 'all' ? '' : v); setSectionId(''); }}>
                                <SelectTrigger id="cls" className="w-full"><SelectValue placeholder="Every class" /></SelectTrigger>
                                <SelectContent>{classItems.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                            </Select>
                        </div>
                        {classId && armItems.length > 1 && (
                            <div className="space-y-1.5">
                                <Label htmlFor="arm">Arm</Label>
                                <Select items={armItems} value={sectionId || 'all'} onValueChange={v => setSectionId(!v || v === 'all' ? '' : v)}>
                                    <SelectTrigger id="arm" className="w-full"><SelectValue placeholder="All arms" /></SelectTrigger>
                                    <SelectContent>{armItems.map(i => <SelectItem key={i.value} value={i.value}>{i.label}</SelectItem>)}</SelectContent>
                                </Select>
                            </div>
                        )}
                    </div>
                    <div className="mt-4 space-y-2 text-sm">
                        <label className="flex items-start gap-2"><input type="checkbox" className="mt-0.5 size-4 accent-indigo-600" checked={parents} onChange={e => setParents(e.target.checked)} /> <span>Also make a login for each parent or guardian (one login per parent, even with several children).</span></label>
                        <label className="flex items-start gap-2"><input type="checkbox" className="mt-0.5 size-4 accent-indigo-600" checked={reset} onChange={e => setReset(e.target.checked)} /> <span>Give new passwords to people who already have a login (use this when slips were lost).</span></label>
                    </div>
                    <div className="mt-5">
                        <Button type="button" onClick={run} disabled={running} className="bg-indigo-600 text-white hover:bg-indigo-700">
                            {running ? <Loader2 className="size-4 animate-spin" /> : <KeyRound className="size-4" />} {running ? 'Making login details…' : 'Make login details'}
                        </Button>
                    </div>
                    {error && <p role="alert" className="mt-3 text-sm text-red-600">{error}</p>}
                </Panel>

                {progress && (
                    <Panel title={progress.finished ? 'Done' : 'Working…'}>
                        <div className="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10"><div className="h-full bg-indigo-600 transition-all" style={{ width: `${Math.round((progress.done / progress.total) * 100)}%` }} /></div>
                        <p role="status" className="mt-2 text-sm text-slate-600 dark:text-slate-300">
                            {progress.done} of {progress.total} students looked at. {progress.made} login{progress.made === 1 ? '' : 's'} made{progress.skipped ? `, ${progress.skipped} skipped because they already have one` : ''}.
                        </p>
                        {progress.problems.length > 0 && <ul className="mt-2 list-disc pl-5 text-sm text-red-600">{progress.problems.map((m, i) => <li key={i}>{m}</li>)}</ul>}
                        {progress.finished && token && progress.made > 0 && (
                            <div className="mt-4 space-y-2">
                                <div className="flex flex-wrap gap-2">
                                    <a href={`/school/students/logins/${token}/pdf`} target="_blank" rel="noreferrer" className="inline-flex h-9 items-center gap-2 rounded-lg bg-indigo-600 px-3.5 text-sm font-medium text-white hover:bg-indigo-700"><Printer className="size-4" /> Printable slips (PDF)</a>
                                    <a href={`/school/students/logins/${token}/excel`} className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200"><FileSpreadsheet className="size-4" /> Excel list</a>
                                </div>
                                <p className="text-xs text-amber-700 dark:text-amber-300">Download them now. For safety the passwords are only kept for 2 hours and can never be looked up afterwards; a lost one can only be replaced with a new one.</p>
                            </div>
                        )}
                    </Panel>
                )}
            </div>
        </AppLayout>
    );
}
