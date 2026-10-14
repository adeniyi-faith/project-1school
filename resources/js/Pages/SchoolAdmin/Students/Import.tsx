import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { PageHeader, Panel, Pill } from '@/components/app/kit';
import { ArrowLeft, Download, FileUp, Loader2, X } from 'lucide-react';

interface PreviewRow {
    line: number; name: string; gender: string; class: string; arm: string; date_of_birth: string; parent: string; phone: string; errors: string[];
}
interface Preview { token: string; ok: number; bad: number; rows: PreviewRow[] }
interface Props { classes: { name: string; arms: string[] }[]; maxRows: number }

/** Add many students from a filled-in template: upload, check the preview, then add the good rows */
export default function ImportStudents({ classes, maxRows }: Props) {
    const [file, setFile] = useState<File | null>(null);
    const [checking, setChecking] = useState(false);
    const [adding, setAdding] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [preview, setPreview] = useState<Preview | null>(null);
    const [onlyProblems, setOnlyProblems] = useState(false);

    async function check() {
        if (!file) return;
        setChecking(true);
        setError(null);
        setPreview(null);
        const body = new FormData();
        body.append('file', file);
        try {
            const { data } = await axios.post<Preview>('/school/students/import/preview', body);
            setPreview(data);
        } catch (e) {
            const errs = axios.isAxiosError(e) ? e.response?.data?.errors : null;
            setError(errs?.file?.[0] ?? 'The file could not be read just now. Check it is the filled-in template and try again.');
        } finally {
            setChecking(false);
        }
    }

    function add() {
        if (!preview) return;
        setAdding(true);
        router.post('/school/students/import', { token: preview.token }, { onFinish: () => setAdding(false) });
    }

    const shown = preview ? (onlyProblems ? preview.rows.filter(r => r.errors.length) : preview.rows) : [];

    const back = (
        <Link href="/school/students" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
            <ArrowLeft className="size-4" /> Students
        </Link>
    );

    return (
        <AppLayout breadcrumbs={[{ label: 'Academic' }, { label: 'Students', href: '/school/students' }, { label: 'Add many students' }]}>
            <Head title="Add many students" />
            <div className="mx-auto max-w-5xl space-y-5 pb-24 md:pb-10">
                <PageHeader title="Add many students at once" description="Fill in one row per child in a spreadsheet, upload it, and check it before anything is saved." actions={back} />
                <div className="flex flex-wrap gap-2 md:hidden">{back}</div>

                <Panel title="1. Download the template" description="It has the right columns, and a second sheet listing your school's class and arm names so you can copy them exactly.">
                    <div className="flex flex-wrap gap-2">
                        <a href="/school/students/import/template" className="inline-flex h-9 items-center gap-2 rounded-lg bg-indigo-600 px-3.5 text-sm font-medium text-white hover:bg-indigo-700"><Download className="size-4" /> Excel template</a>
                        <a href="/school/students/import/template?format=csv" className="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200"><Download className="size-4" /> CSV template</a>
                    </div>
                    <ul className="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-600 dark:text-slate-300">
                        <li>Columns marked * must be filled in. Leave the admission number empty and each child gets the next number.</li>
                        <li>Brothers and sisters: give the same parent phone number and they share one parent record.</li>
                        <li>Up to {maxRows} students in one file. Your classes: {classes.length ? classes.map(c => c.name + (c.arms.length ? ` (${c.arms.join(', ')})` : '')).join('; ') : 'none yet, add classes first'}.</li>
                    </ul>
                </Panel>

                <Panel title="2. Upload your filled-in file">
                    <div className="space-y-3">
                        <label className="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm hover:border-indigo-400 dark:border-white/15">
                            <FileUp className="size-5 text-slate-500" aria-hidden="true" />
                            <span className="min-w-0 flex-1 truncate">{file ? file.name : 'Choose an Excel (.xlsx) or CSV file'}</span>
                            {file && <button type="button" onClick={e => { e.preventDefault(); setFile(null); setPreview(null); }} aria-label="Remove the file" className="text-slate-500 hover:text-slate-800"><X className="size-4" /></button>}
                            <input type="file" accept=".xlsx,.csv,.txt" className="sr-only" onChange={e => { setFile(e.target.files?.[0] ?? null); setPreview(null); setError(null); }} />
                        </label>
                        <Button type="button" onClick={check} disabled={!file || checking}>
                            {checking ? <Loader2 className="size-4 animate-spin" /> : null} {checking ? 'Checking…' : 'Check the file'}
                        </Button>
                        {error && <p role="alert" className="text-sm text-red-600">{error}</p>}
                    </div>
                </Panel>

                {preview && (
                    <Panel title="3. Check, then add" description="Nothing has been saved yet."
                        action={preview.bad > 0 ? (
                            <label className="flex items-center gap-2 text-sm"><input type="checkbox" className="size-4 accent-indigo-600" checked={onlyProblems} onChange={e => setOnlyProblems(e.target.checked)} /> Show only rows with problems</label>
                        ) : undefined}>
                        <div className="mb-3 flex flex-wrap items-center gap-2 text-sm">
                            <Pill tone="good">{preview.ok} ready to add</Pill>
                            {preview.bad > 0 && <Pill tone="bad">{preview.bad} with problems (will be left out)</Pill>}
                        </div>
                        <div className="max-h-[28rem] overflow-auto rounded-lg border border-slate-200 dark:border-white/10">
                            <table className="w-full text-left text-sm">
                                <thead className="sticky top-0 bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800">
                                    <tr><th className="px-3 py-2">Row</th><th className="px-3 py-2">Student</th><th className="px-3 py-2">Class</th><th className="px-3 py-2">Parent</th><th className="px-3 py-2">Check</th></tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                                    {shown.map(r => (
                                        <tr key={r.line} className={r.errors.length ? 'bg-red-50/60 dark:bg-red-500/10' : ''}>
                                            <td className="px-3 py-2 text-slate-500">{r.line}</td>
                                            <td className="px-3 py-2"><div className="font-medium">{r.name || '—'}</div><div className="text-xs text-slate-500">{[r.gender, r.date_of_birth].filter(Boolean).join(' · ')}</div></td>
                                            <td className="px-3 py-2">{[r.class, r.arm].filter(Boolean).join(' ') || '—'}</td>
                                            <td className="px-3 py-2"><div>{r.parent || '—'}</div><div className="text-xs text-slate-500">{r.phone}</div></td>
                                            <td className="px-3 py-2">{r.errors.length ? <ul className="space-y-0.5 text-xs text-red-700 dark:text-red-300">{r.errors.map((m, i) => <li key={i}>{m}</li>)}</ul> : <span className="text-emerald-700 dark:text-emerald-300">OK</span>}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="mt-4 flex flex-wrap items-center justify-end gap-3">
                            {preview.bad > 0 && <p className="mr-auto text-xs text-slate-500">Rows with problems are skipped. Fix them in your file and upload it again; students already added are not added twice.</p>}
                            <Button type="button" onClick={add} disabled={adding || preview.ok === 0} className="bg-indigo-600 text-white hover:bg-indigo-700">
                                {adding ? <Loader2 className="size-4 animate-spin" /> : null} Add {preview.ok} student{preview.ok === 1 ? '' : 's'}
                            </Button>
                        </div>
                    </Panel>
                )}
            </div>
        </AppLayout>
    );
}
