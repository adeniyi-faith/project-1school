import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Check } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { PageHeader, Panel, Pill } from '@/components/app/kit';
import { useCan } from '@/lib/can';
import type { PageProps } from '@/Types';

interface SchoolTypeInfo {
    key: string; label: string; tagline: string; classes: string[]; subject_count: number;
    terms: string[] | null; assessment: string | null; grading: string | null;
}

interface Props extends PageProps {
    types: SchoolTypeInfo[];
    current: string | null;
    parts: { key: string; label: string }[];
    have: { classes: number; subjects: number };
    canEdit: boolean;
}

/** Choose the kind of school and add its starting classes, subjects, term names and grades (only adds, never removes) */
export default function SchoolType({ types, current, parts, have }: Props) {
    const can = useCan();
    const canEdit = can('settings.edit');
    const [picked, setPicked] = useState<string>(current ?? types[0]?.key ?? '');
    const form = useForm<{ type: string; parts: string[] }>({ type: picked, parts: parts.map(p => p.key) });
    const type = types.find(t => t.key === picked);

    function choose(key: string) {
        setPicked(key);
        form.setData('type', key);
    }

    function toggle(key: string, on: boolean) {
        form.setData('parts', on ? [...form.data.parts, key] : form.data.parts.filter(k => k !== key));
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (confirm(`Add the ${type?.label} setup to your school? Nothing you already have will be changed or removed.`)) {
            form.post('/school/settings/school-type', { preserveScroll: true });
        }
    }

    return (
        <AppLayout breadcrumbs={[{ label: 'Settings', href: '/school/settings' }, { label: 'School Type' }]}>
            <Head title="School Type" />
            <div className="mx-auto max-w-5xl space-y-5 pb-24 md:pb-0">
                <PageHeader title="School type"
                    description="Tell the system what kind of school you run. It adds the classes, subjects, term names and grading that school normally uses. It only adds: classes, subjects, scores and results you already have are never changed or removed." />

                <div className="grid gap-3 sm:grid-cols-2">
                    {types.map(t => (
                        <button key={t.key} type="button" onClick={() => choose(t.key)} aria-pressed={picked === t.key}
                            className={`rounded-xl border p-4 text-left transition ${picked === t.key ? 'border-indigo-500 bg-indigo-50/60 ring-1 ring-indigo-500 dark:bg-indigo-500/10' : 'border-slate-200 bg-white hover:border-slate-300 dark:border-white/[0.08] dark:bg-slate-900'}`}>
                            <div className="flex items-center justify-between gap-2">
                                <span className="font-semibold text-slate-900 dark:text-white">{t.label}</span>
                                {current === t.key && <Pill tone="good">Your school type</Pill>}
                            </div>
                            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{t.tagline}</p>
                        </button>
                    ))}
                </div>

                {type && (
                    <Panel title={`What the ${type.label} setup adds`}>
                        {type.classes.length === 0 ? (
                            <p className="text-sm text-slate-600 dark:text-slate-300">Nothing is added for this choice. Build your own classes, subjects and grades under School Setup and Academics.</p>
                        ) : (
                            <dl className="space-y-3 text-sm">
                                <div><dt className="font-medium text-slate-900 dark:text-white">Classes ({type.classes.length})</dt><dd className="mt-0.5 text-slate-600 dark:text-slate-300">{type.classes.join(', ')}</dd></div>
                                <div><dt className="font-medium text-slate-900 dark:text-white">Subjects</dt><dd className="mt-0.5 text-slate-600 dark:text-slate-300">About {type.subject_count} different subjects, each offered to the classes that take it.</dd></div>
                                {type.terms && <div><dt className="font-medium text-slate-900 dark:text-white">Terms</dt><dd className="mt-0.5 text-slate-600 dark:text-slate-300">{type.terms.join(', ')}. School years that already have results, invoices or tests keep their term names.</dd></div>}
                                {type.assessment && <div><dt className="font-medium text-slate-900 dark:text-white">Score setup</dt><dd className="mt-0.5 text-slate-600 dark:text-slate-300">{type.assessment}</dd></div>}
                                {type.grading && <div><dt className="font-medium text-slate-900 dark:text-white">Grade scale</dt><dd className="mt-0.5 text-slate-600 dark:text-slate-300">{type.grading}. The boundaries are a sensible starting point; change them to match your exam board or school policy.</dd></div>}
                            </dl>
                        )}
                        <p className="mt-3 text-xs text-slate-500">You already have {have.classes} class{have.classes === 1 ? '' : 'es'} and {have.subjects} subject{have.subjects === 1 ? '' : 's'}. Ones with the same name are reused, not repeated.</p>

                        {canEdit && type.classes.length > 0 && (
                            <form onSubmit={submit} className="mt-4 space-y-3 border-t border-slate-100 pt-4 dark:border-white/[0.06]">
                                <p className="text-sm font-medium text-slate-900 dark:text-white">Add these parts</p>
                                <div className="grid gap-2 sm:grid-cols-2">
                                    {parts.map(p => (
                                        <label key={p.key} className="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                                            <Checkbox checked={form.data.parts.includes(p.key)} onCheckedChange={v => toggle(p.key, v === true)} />
                                            {p.label}
                                        </label>
                                    ))}
                                </div>
                                {form.errors.parts && <p className="text-xs text-red-600">{form.errors.parts}</p>}
                                <Button type="submit" disabled={form.processing || form.data.parts.length === 0} className="bg-indigo-600 text-white hover:bg-indigo-700">
                                    <Check className="size-4" /> Add the {type.label} setup
                                </Button>
                            </form>
                        )}
                        {canEdit && type.classes.length === 0 && (
                            <form onSubmit={e => { e.preventDefault(); form.transform(data => ({ ...data, parts: [] })); form.post('/school/settings/school-type', { preserveScroll: true }); }} className="mt-4">
                                <Button type="submit" disabled={form.processing} variant="outline">Save as my school type</Button>
                            </form>
                        )}
                    </Panel>
                )}
            </div>
        </AppLayout>
    );
}
