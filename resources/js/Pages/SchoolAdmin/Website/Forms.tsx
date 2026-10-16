import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Panel, Pill } from '@/components/app/kit';
import { ExternalLink, FileText, Inbox } from 'lucide-react';
import { WebsiteLayout, selectClass } from '@/components/website/kit';
import type { WebsiteShell } from '@/Types/website';

interface FormRow { id: number; name: string; active: boolean; fields: number; answers: number; new: number; url: string }
interface Props { shell: WebsiteShell; forms: FormRow[]; templates: Record<string, string>; canManage: boolean }

export default function Forms({ shell, forms, templates, canManage }: Props) {
    const [template, setTemplate] = useState('prospectus');
    const make = () => router.post('/school/website/forms', { template });

    return (
        <WebsiteLayout shell={shell} title="Forms and surveys" crumb={[{ label: 'Extensions', href: '/school/website/extensions' }, { label: 'Forms' }]}
            description="Build a form with your own questions and privacy ticks, then show it on any page. The answers are kept here.">
            {canManage && (
                <Panel title="Make a new form" description="Start from a ready-made form and change anything, or start empty.">
                    <div className="flex flex-col gap-2 sm:flex-row">
                        <select aria-label="Start from" className={`${selectClass} sm:max-w-xs`} value={template} onChange={e => setTemplate(e.target.value)}>
                            {Object.entries(templates).map(([k, label]) => <option key={k} value={k}>{label}</option>)}
                        </select>
                        <Button type="button" onClick={make} className="bg-indigo-600 text-white hover:bg-indigo-700">Make the form</Button>
                    </div>
                </Panel>
            )}

            {forms.length === 0 ? (
                <p className="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-white/15">No forms yet. {canManage ? 'Pick a starting point above.' : ''}</p>
            ) : (
                <ul className="grid gap-3 sm:grid-cols-2">
                    {forms.map(f => (
                        <li key={f.id} className="flex flex-col gap-3 rounded-xl border border-slate-200 p-4 dark:border-white/10">
                            <div className="flex items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <h2 className="flex items-center gap-2 font-semibold"><FileText className="size-4 shrink-0 text-slate-400" /><span className="truncate">{f.name}</span></h2>
                                    <p className="mt-1 text-xs text-slate-500">{f.fields} question{f.fields === 1 ? '' : 's'} · {f.answers} answer{f.answers === 1 ? '' : 's'}</p>
                                </div>
                                <div className="flex flex-wrap justify-end gap-1.5">
                                    {f.active ? <Pill tone="good">Open</Pill> : <Pill tone="neutral">Closed</Pill>}
                                    {f.new > 0 && <Pill tone="warn">{f.new} new</Pill>}
                                </div>
                            </div>
                            <div className="mt-auto flex flex-wrap gap-2">
                                <Link href={`/school/website/forms/${f.id}/answers`} className="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 px-3 text-sm font-medium hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5"><Inbox className="size-4" /> Answers</Link>
                                <Link href={`/school/website/forms/${f.id}`} className="inline-flex h-9 items-center rounded-lg bg-indigo-600 px-3 text-sm font-medium text-white hover:bg-indigo-700">{canManage ? 'Edit' : 'Open'}</Link>
                                {f.active && <a href={f.url} target="_blank" rel="noreferrer" className="inline-flex h-9 items-center gap-1.5 rounded-lg px-3 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5">Open page <ExternalLink className="size-3.5" /></a>}
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </WebsiteLayout>
    );
}
