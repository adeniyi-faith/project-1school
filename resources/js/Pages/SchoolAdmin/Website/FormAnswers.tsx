import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Pill } from '@/components/app/kit';
import { WebsiteLayout } from '@/components/website/kit';
import type { WebsiteShell } from '@/Types/website';

interface Answer { key: string; label: string; type: string; value: string }
interface Consent { text: string; required: boolean; agreed: boolean }
interface Sub { id: number; status: string; at: string; answers: Answer[]; consents: Consent[]; newsletter: boolean; page: string | null }
interface Paged<T> { data: T[]; links: { url: string | null; label: string; active: boolean }[]; total: number }
interface Props { shell: WebsiteShell; form: { id: number; name: string }; submissions: Paged<Sub>; counts: { all: number; new: number }; filters: { status: string }; canManage: boolean }

const when = (iso: string) => new Date(iso).toLocaleString(undefined, { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' });

export default function FormAnswers({ shell, form, submissions, counts, filters, canManage }: Props) {
    const base = `/school/website/forms/${form.id}`;
    const filter = (status: string) => router.get(`${base}/answers`, status ? { status } : {}, { preserveState: true, replace: true });

    return (
        <WebsiteLayout shell={shell} title={form.name} crumb={[{ label: 'Extensions', href: '/school/website/extensions' }, { label: 'Forms', href: '/school/website/forms' }, { label: 'Answers' }]}
            description={`${counts.all} answer${counts.all === 1 ? '' : 's'}${counts.new ? `, ${counts.new} not read yet` : ''}.`}
            actions={<a href={`${base}/export`} className="inline-flex h-9 items-center rounded-lg border border-slate-200 px-3 text-sm font-medium hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5">Download as Excel</a>}>
            <div className="flex flex-wrap items-center gap-2">
                {([['', 'All'], ['new', 'Not read'], ['read', 'Read']] as const).map(([k, label]) => (
                    <button key={k} type="button" onClick={() => filter(k)} aria-pressed={filters.status === k} className={`rounded-full border px-3 py-1 text-sm ${filters.status === k ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300' : 'border-slate-200 text-slate-600 dark:border-white/10 dark:text-slate-300'}`}>{label}</button>
                ))}
                {canManage && counts.new > 0 && <Button type="button" variant="ghost" size="sm" onClick={() => router.post(`${base}/answers/read-all`, {}, { preserveScroll: true })}>Mark all as read</Button>}
            </div>

            {submissions.data.length === 0 ? (
                <p className="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-white/15">Nothing here yet. Answers appear as soon as someone sends the form.</p>
            ) : (
                <ul className="space-y-3">
                    {submissions.data.map(s => (
                        <li key={s.id} className="rounded-xl border border-slate-200 p-4 dark:border-white/10">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <p className="flex items-center gap-2 text-sm text-slate-500">{when(s.at)} {s.status === 'new' && <Pill tone="warn">New</Pill>}</p>
                                {canManage && (
                                    <div className="flex gap-1">
                                        <Button type="button" variant="ghost" size="sm" onClick={() => router.post(`${base}/answers/${s.id}/read`, { read: s.status === 'new' }, { preserveScroll: true })}>{s.status === 'new' ? 'Mark as read' : 'Mark as not read'}</Button>
                                        <Button type="button" variant="ghost" size="sm" className="text-red-600" onClick={() => { if (confirm('Delete this answer for good? This cannot be undone.')) router.post(`${base}/answers/${s.id}/delete`, {}, { preserveScroll: true }); }}>Delete</Button>
                                    </div>
                                )}
                            </div>
                            <dl className="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                                {s.answers.map(a => (
                                    <div key={a.key} className={a.type === 'textarea' ? 'sm:col-span-2' : ''}>
                                        <dt className="text-xs font-medium text-slate-500">{a.label}</dt>
                                        <dd className="whitespace-pre-wrap break-words text-sm text-slate-900 dark:text-white">{a.value || <span className="text-slate-400">Not answered</span>}</dd>
                                    </div>
                                ))}
                            </dl>
                            {(s.consents.length > 0 || s.newsletter) && (
                                <div className="mt-3 space-y-1 border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-white/[0.06]">
                                    {s.consents.map((c, i) => <p key={i}><span className={c.agreed ? 'text-emerald-600' : 'text-amber-600'}>{c.agreed ? 'Agreed' : 'Did not agree'}</span>: {c.text}</p>)}
                                    {s.newsletter && <p>Asked to get the newsletter (a confirmation email was sent).</p>}
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            )}
            {submissions.links.length > 3 && (
                <div className="flex flex-wrap gap-1">
                    {submissions.links.map((l, i) => (
                        <button key={i} type="button" disabled={!l.url} onClick={() => l.url && router.get(l.url, {}, { preserveScroll: true })}
                            className={`min-w-8 rounded-md px-2 py-1 text-sm ${l.active ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100 disabled:opacity-40 dark:hover:bg-white/5'}`}>
                            {l.label.replace('&laquo;', '‹').replace('&raquo;', '›').replace(' Previous', '').replace('Next ', '')}
                        </button>
                    ))}
                </div>
            )}
        </WebsiteLayout>
    );
}
