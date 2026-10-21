import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { EmptyState, PageHeader, Panel, Pill } from '@/components/app/kit';
import { CheckCircle2, RotateCcw, Trash2 } from 'lucide-react';

interface FailedJob {
    uuid: string; job: string; queue: string; connection: string; failed_at: string; attempts: number | null; summary: string; exception: string;
}

interface Props {
    ready: boolean;
    driver: string;
    jobs: { data: FailedJob[]; total: number; current_page: number; last_page: number; next_page_url: string | null; prev_page_url: string | null } | null;
}

/** Background jobs that failed: why, try again, or delete (a reason is required and logged) */
export default function FailedJobsIndex({ ready, driver, jobs }: Props) {
    // null = closed; a job = delete that one; 'all' = delete every failed job
    const [deleting, setDeleting] = useState<FailedJob | 'all' | null>(null);
    const [open, setOpen] = useState<string | null>(null);
    const form = useForm({ reason: '' });
    const total = jobs?.total ?? 0;

    function confirmDelete(e: React.FormEvent) {
        e.preventDefault();
        if (!deleting) return;
        const url = deleting === 'all' ? '/super-admin/failed-jobs/flush' : `/super-admin/failed-jobs/${deleting.uuid}`;
        const options = { preserveScroll: true, onSuccess: () => { setDeleting(null); form.reset(); } };
        deleting === 'all' ? form.post(url, options) : form.delete(url, options);
    }

    return (
        <AppLayout title="Failed jobs">
            <div className="mx-auto max-w-5xl space-y-6 pb-24 md:pb-0">
                <PageHeader title="Failed jobs" description="Background work (such as sending a batch of messages) that stopped with an error. Send it back to try again, or delete it once you have dealt with it." />

                {driver === 'sync' && (
                    <p className="rounded-lg bg-slate-50 p-3 text-[13px] text-slate-600 dark:bg-white/[0.04] dark:text-slate-300">
                        The queue is set to "sync", so jobs run inside the page and are not normally stored here. This screen starts to matter when the queue is switched to "database".
                    </p>
                )}

                {!ready && <Panel><EmptyState icon={RotateCcw} title="The failed-jobs table does not exist yet" text="Run the database updates (a deploy does this) and come back." /></Panel>}

                {ready && jobs && jobs.total === 0 && (
                    <Panel><EmptyState icon={CheckCircle2} title="No failed jobs" text="Nothing has failed, or everything that failed has been cleared." /></Panel>
                )}

                {ready && jobs && jobs.total > 0 && (
                    <>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <p className="text-sm text-slate-500 dark:text-slate-400">{total} failed job{total === 1 ? '' : 's'}</p>
                            <div className="flex flex-wrap gap-2">
                                <Button variant="outline" size="sm" onClick={() => router.post('/super-admin/failed-jobs/retry-all', {}, { preserveScroll: true })}><RotateCcw /> Try all again</Button>
                                <Button variant="outline" size="sm" onClick={() => setDeleting('all')}><Trash2 /> Delete all</Button>
                            </div>
                        </div>

                        <div className="space-y-3">
                            {jobs.data.map((j) => (
                                <Panel key={j.uuid} title={<span className="break-all">{j.job}</span>} description={`Failed ${new Date(j.failed_at).toLocaleString()} · queue "${j.queue}"`}
                                    action={<Pill tone="bad">Failed</Pill>}>
                                    <p className="break-words text-sm text-slate-700 dark:text-slate-200">{j.summary || 'No error message was saved.'}</p>
                                    {open === j.uuid && <pre className="mt-3 max-h-72 overflow-auto rounded-lg bg-slate-50 p-3 text-xs text-slate-700 dark:bg-white/[0.04] dark:text-slate-300">{j.exception}</pre>}
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        <Button variant="outline" size="sm" onClick={() => setOpen(open === j.uuid ? null : j.uuid)}>{open === j.uuid ? 'Hide details' : 'Show details'}</Button>
                                        <Button variant="outline" size="sm" onClick={() => router.post(`/super-admin/failed-jobs/${j.uuid}/retry`, {}, { preserveScroll: true })}><RotateCcw /> Try again</Button>
                                        <Button variant="outline" size="sm" onClick={() => setDeleting(j)}><Trash2 /> Delete</Button>
                                    </div>
                                </Panel>
                            ))}
                        </div>

                        {jobs.last_page > 1 && (
                            <div className="flex items-center justify-between text-sm text-slate-500">
                                <span>Page {jobs.current_page} of {jobs.last_page}</span>
                                <div className="flex gap-2">
                                    <Button variant="outline" size="sm" disabled={!jobs.prev_page_url} onClick={() => jobs.prev_page_url && router.get(jobs.prev_page_url)}>Previous</Button>
                                    <Button variant="outline" size="sm" disabled={!jobs.next_page_url} onClick={() => jobs.next_page_url && router.get(jobs.next_page_url)}>Next</Button>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>

            <Dialog open={deleting !== null} onOpenChange={(o) => { if (!o) { setDeleting(null); form.reset(); } }}>
                <DialogContent className="sm:max-w-md">
                    <form onSubmit={confirmDelete} className="space-y-4">
                        <DialogHeader>
                            <DialogTitle>{deleting === 'all' ? `Delete all ${total} failed jobs?` : 'Delete this failed job?'}</DialogTitle>
                            <DialogDescription>This cannot be undone. The reason is saved in the audit trail with your name.</DialogDescription>
                        </DialogHeader>
                        <div className="space-y-1.5">
                            <Label htmlFor="reason">Why are you deleting {deleting === 'all' ? 'them' : 'it'}?</Label>
                            <Textarea id="reason" rows={3} value={form.data.reason} onChange={(e) => form.setData('reason', e.target.value)} placeholder="For example: old test messages, no longer needed" />
                            {form.errors.reason && <p className="text-xs text-red-500">{form.errors.reason}</p>}
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setDeleting(null)}>Cancel</Button>
                            <Button type="submit" variant="destructive" disabled={form.processing}>Delete</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
