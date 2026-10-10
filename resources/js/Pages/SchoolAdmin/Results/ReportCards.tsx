import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { EmptyState, PageHeader, Panel } from '@/components/app/kit';
import { ordinal } from '@/lib/format';
import { ArrowLeft, Download, FileText, MessageSquareQuote, Palette, Wand2 } from 'lucide-react';
import { fittingComments, renderComment } from '@/lib/commentBank';
import type { CommentBankEntry, ReportCardStudent } from '@/Types';

interface Signer { id: number; label: string; writer_permission: string; can_write: boolean }

interface Props {
    sheet: { id: number; status: string; term: string; class_name: string | null };
    design: { id: number; name: string };
    signers: Signer[];
    students: ReportCardStudent[];
    canDesign: boolean;
    bank: CommentBankEntry[];
}

const linkClass = 'inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 shadow-xs hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200';

export default function ReportCards({ sheet, design, signers, students, canDesign, bank }: Props) {
    const base = `/school/results/${sheet.id}`;
    const released = sheet.status === 'published' || sheet.status === 'locked';
    // signer id → student id → comment
    const [comments, setComments] = useState<Record<number, Record<number, string>>>(() => Object.fromEntries(
        signers.map(sg => [sg.id, Object.fromEntries(students.map(s => [s.id, s.remarks[sg.id] ?? '']))]),
    ));
    const [saving, setSaving] = useState<number | null>(null);
    const writable = signers.filter(s => s.can_write);

    // Typed but not saved yet: the server can't see these, so filling from the bank waits for a save
    const dirty = (signerId: number) => students.some(s => (comments[signerId]?.[s.id] ?? '') !== (s.remarks[signerId] ?? ''));

    function fill(signer: Signer, overwrite: boolean) {
        if (overwrite && !window.confirm(`Replace every ${signer.label}'s comment in this class with one from the comment bank?`)) return;
        setSaving(signer.id);
        router.post(`${base}/comments/fill`, { signer_id: signer.id, overwrite }, {
            preserveScroll: true,
            onSuccess: page => {
                const fresh = (page.props as unknown as Props).students;
                setComments(c => ({ ...c, [signer.id]: Object.fromEntries(fresh.map(s => [s.id, s.remarks[signer.id] ?? ''])) }));
            },
            onFinish: () => setSaving(null),
        });
    }

    function edit(signerId: number, studentId: number, text: string) {
        setComments(c => ({ ...c, [signerId]: { ...c[signerId], [studentId]: text } }));
    }

    function save(signer: Signer) {
        setSaving(signer.id);
        router.post(`${base}/comments`, {
            signer_id: signer.id,
            comments: students.map(s => ({ student_id: s.id, comment: comments[signer.id][s.id] ?? '' })),
        }, { preserveScroll: true, onFinish: () => setSaving(null) });
    }

    const headerActions = (
        <div className="flex flex-wrap gap-2">
            <Link href={base} className={linkClass}><ArrowLeft className="size-4" /> Back to results</Link>
            {students.length > 0 && (
                <>
                    <a href={`${base}/report-cards/term`} target="_blank" rel="noopener" className={linkClass}><Download className="size-4" /> Whole class: term</a>
                    <a href={`${base}/report-cards/session`} target="_blank" rel="noopener" className={linkClass}><Download className="size-4" /> Whole class: full year</a>
                </>
            )}
        </div>
    );

    return (
        <AppLayout breadcrumbs={[{ label: 'Academic' }, { label: 'Term results', href: '/school/results' }, { label: sheet.class_name ?? 'Class', href: base }, { label: 'Report cards' }]}>
            <div className="mx-auto max-w-6xl space-y-6">
                <PageHeader
                    title={`${sheet.class_name ?? 'Class'} report cards`}
                    description={`${sheet.term}. Write the comments, then download one student's card or the whole class in one file to print.`}
                    actions={headerActions}
                />
                {/* The header hides its buttons on phones, so they are repeated here */}
                <div className="md:hidden">{headerActions}</div>

                {writable.length > 0 && students.length > 0 && sheet.status !== 'locked' && (
                    <Panel>
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div className="max-w-xl">
                                <p className="font-medium text-slate-900 dark:text-white">Fill comments from the comment bank</p>
                                <p className="mt-1 text-sm text-slate-500">
                                    Each student gets a saved comment that fits their average. Only empty boxes are filled, and you can change any comment afterwards.{' '}
                                    <Link href="/school/results/comment-bank" className="inline-flex items-center gap-1 text-indigo-600 hover:underline dark:text-indigo-400"><MessageSquareQuote className="size-3.5" /> Open the comment bank</Link>
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {writable.map(sg => (
                                    <div key={sg.id} className="flex flex-col items-end gap-1">
                                        <Button variant="outline" disabled={saving !== null || dirty(sg.id) || !bank.some(c => c.writer_permission === sg.writer_permission)} onClick={() => fill(sg, false)}>
                                            <Wand2 className="size-4" /> Fill {sg.label}'s comments
                                        </Button>
                                        {dirty(sg.id) ? (
                                            <span className="text-xs text-amber-600">Save your typed comments first</span>
                                        ) : !bank.some(c => c.writer_permission === sg.writer_permission) ? (
                                            <span className="text-xs text-slate-500">No saved comments yet</span>
                                        ) : (
                                            <button type="button" className="text-xs text-slate-500 hover:underline" disabled={saving !== null} onClick={() => fill(sg, true)}>Replace all instead</button>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    </Panel>
                )}

                <p className="text-sm text-slate-500">
                    Printed with the <strong>{design.name}</strong> design.{' '}
                    {canDesign && <Link href={`/school/academics/report-card-designs?design=${design.id}`} className="inline-flex items-center gap-1 text-indigo-600 hover:underline dark:text-indigo-400"><Palette className="size-3.5" /> Change the design</Link>}
                </p>

                {!released && students.length > 0 && (
                    <p className="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                        These results are not published yet, so cards are marked "Preview". Parents and students can download their cards once the results are published.
                    </p>
                )}
                {sheet.status === 'locked' && (
                    <p className="rounded-lg bg-slate-100 px-4 py-3 text-sm text-slate-600 dark:bg-white/[0.06] dark:text-slate-300">These results are locked, so comments can no longer be changed.</p>
                )}

                {students.length === 0 ? (
                    <Panel><EmptyState icon={FileText} title="No results yet" text="Report cards appear here once scores have been entered for this class and term." /></Panel>
                ) : (
                    <Panel flush>
                        <ul className="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            {students.map(s => (
                                <li key={s.id} className="px-5 py-4">
                                    <div className="grid gap-3 lg:grid-cols-[14rem_1fr]">
                                        <div>
                                            <p className="font-medium text-slate-900 dark:text-white">{s.name}</p>
                                            <p className="text-xs text-slate-500">{s.admission_no} · {s.average}% · {ordinal(s.position)} of {s.class_size}</p>
                                            <div className="mt-2 flex gap-3 text-sm">
                                                <a href={`${base}/report-cards/term?student=${s.id}`} target="_blank" rel="noopener" className="text-indigo-600 hover:underline dark:text-indigo-400">Term card</a>
                                                <a href={`${base}/report-cards/session?student=${s.id}`} target="_blank" rel="noopener" className="text-indigo-600 hover:underline dark:text-indigo-400">Full year</a>
                                            </div>
                                        </div>
                                        <div className={`grid gap-3 ${signers.length > 1 ? 'md:grid-cols-2' : ''}`}>
                                            {signers.map(sg => (
                                                <label key={sg.id} className="block">
                                                    <span className="mb-1 block text-xs font-medium text-slate-500">{sg.label}'s comment</span>
                                                    {sg.can_write ? (
                                                        <>
                                                            <Textarea rows={2} maxLength={600} value={comments[sg.id]?.[s.id] ?? ''} onChange={e => edit(sg.id, s.id, e.target.value)} />
                                                            <SavedCommentPicker
                                                                options={fittingComments(bank, sg.writer_permission, s.average)}
                                                                onPick={text => edit(sg.id, s.id, renderComment(text, s))}
                                                            />
                                                        </>
                                                    ) : (
                                                        <p className="min-h-10 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700 dark:bg-white/[0.04] dark:text-slate-300">{comments[sg.id]?.[s.id] || '—'}</p>
                                                    )}
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                        {writable.length > 0 && (
                            <div className="flex flex-wrap justify-end gap-2 border-t border-slate-100 px-5 py-4 dark:border-white/[0.06]">
                                {writable.map(sg => (
                                    <Button key={sg.id} onClick={() => save(sg)} disabled={saving !== null} variant="outline">Save {sg.label}'s comments</Button>
                                ))}
                            </div>
                        )}
                    </Panel>
                )}
            </div>
        </AppLayout>
    );
}

/** A short list of saved comments that fit this student's average; picking one puts it in the box */
function SavedCommentPicker({ options, onPick }: { options: CommentBankEntry[]; onPick: (text: string) => void }) {
    if (options.length === 0) return null;
    return (
        <select
            aria-label="Use a saved comment"
            className="mt-1 h-8 w-full truncate rounded-md border border-slate-200 bg-white px-2 text-xs text-slate-600 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-300"
            value=""
            onChange={e => {
                const picked = options.find(o => String(o.id) === e.target.value);
                if (picked) onPick(picked.comment);
            }}
        >
            <option value="">Use a saved comment ({options.length})</option>
            {options.map(o => <option key={o.id} value={o.id}>{o.comment.length > 90 ? `${o.comment.slice(0, 90)}…` : o.comment}</option>)}
        </select>
    );
}
