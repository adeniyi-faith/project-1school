import { useRef, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { EmptyState, Panel, Pill } from '@/components/app/kit';
import { ErrorList, WebsiteLayout, imageUrl, uploadImage } from '@/components/website/kit';
import { Copy, Images, Trash2, Upload } from 'lucide-react';
import type { MediaItem, WebsiteShell } from '@/Types/website';

interface Props { shell: WebsiteShell; media: MediaItem[] }

const kb = (n: number | null) => (n ? (n > 1048576 ? `${(n / 1048576).toFixed(1)} MB` : `${Math.round(n / 1024)} KB`) : '');

export default function WebsiteMedia({ shell, media }: Props) {
    const input = useRef<HTMLInputElement>(null);
    const [busy, setBusy] = useState<string>('');
    const [errors, setErrors] = useState<string[]>([]);
    const [open, setOpen] = useState<MediaItem | null>(null);
    const form = useForm({ name: '', alt: '' });

    async function upload(files: FileList | null) {
        if (!files?.length) return;
        const list = Array.from(files).slice(0, 20);
        const failed: string[] = [];
        for (const [i, f] of list.entries()) {
            setBusy(`Uploading ${i + 1} of ${list.length}…`);
            try { await uploadImage(f); } catch (e) { failed.push(`${f.name}: ${(e as Error).message}`); }
        }
        setBusy(''); setErrors(failed);
        if (input.current) input.current.value = '';
        router.reload({ only: ['media'] });
    }
    function edit(m: MediaItem) { setOpen(m); form.setData({ name: m.name, alt: m.alt ?? '' }); form.clearErrors(); }

    return (
        <WebsiteLayout shell={shell} title="Pictures" crumb={[{ label: 'Pictures' }]}
            description="Every picture for your website in one place. Upload once, then use it on any page or story. Big phone photos are made smaller so your site stays fast."
            actions={shell.canEdit && <>
                <Button disabled={!!busy} onClick={() => input.current?.click()} className="bg-indigo-600 text-white hover:bg-indigo-700"><Upload className="size-4" /> {busy || 'Upload pictures'}</Button>
                <input ref={input} type="file" multiple accept="image/png,image/jpeg,image/webp" className="sr-only" aria-label="Upload pictures" onChange={e => upload(e.target.files)} />
            </>}>
            {errors.length > 0 && <div className="space-y-1 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300">{errors.map(e => <p key={e}>{e}</p>)}</div>}

            {media.length === 0 ? (
                <Panel><EmptyState icon={Images} title="No pictures yet" text="Upload photos of your classrooms, pupils at work, sports and events. You can pick up to 20 at once." /></Panel>
            ) : (
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    {media.map(m => (
                        <button key={m.id} type="button" onClick={() => edit(m)} className="group overflow-hidden rounded-xl border border-slate-200 bg-white text-left hover:border-indigo-400 dark:border-white/10 dark:bg-slate-900">
                            <img src={imageUrl(m.path)} alt={m.alt ?? ''} loading="lazy" className="aspect-[4/3] w-full object-cover transition-transform group-hover:scale-[1.02]" />
                            <span className="block p-2">
                                <span className="block truncate text-sm font-medium">{m.name}</span>
                                <span className="flex items-center justify-between gap-2 text-xs text-slate-500">{m.width ? `${m.width}×${m.height}` : ''} {kb(m.size)} {m.used && <Pill tone="good">In use</Pill>}</span>
                            </span>
                        </button>
                    ))}
                </div>
            )}

            <Dialog open={!!open} onOpenChange={v => !v && setOpen(null)}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader><DialogTitle>Picture details</DialogTitle></DialogHeader>
                    {open && (
                        <form id="media-form" className="space-y-3 text-sm" onSubmit={e => { e.preventDefault(); form.put(`/school/website/media/${open.id}`, { preserveScroll: true, onSuccess: () => setOpen(null) }); }}>
                            <img src={imageUrl(open.path)} alt={open.alt ?? ''} className="max-h-64 w-full rounded-lg bg-slate-100 object-contain" />
                            <label className="block space-y-1"><span className="font-medium">Name</span><Input disabled={!shell.canEdit} maxLength={200} value={form.data.name} onChange={e => form.setData('name', e.target.value)} /></label>
                            <label className="block space-y-1"><span className="font-medium">What the picture shows <span className="font-normal text-slate-500">· read out to blind visitors and used by Google</span></span>
                                <Input disabled={!shell.canEdit} maxLength={200} placeholder="Pupils doing an experiment in the science lab" value={form.data.alt} onChange={e => form.setData('alt', e.target.value)} /></label>
                            <button type="button" className="inline-flex items-center gap-1 text-xs text-indigo-600 hover:underline" onClick={() => navigator.clipboard?.writeText(location.origin + imageUrl(open.path))}><Copy className="size-3" /> Copy picture link</button>
                            <ErrorList errors={form.errors} />
                        </form>
                    )}
                    <DialogFooter className="sm:justify-between">
                        {shell.canEdit && open && (open.used
                            ? <span className="text-xs text-slate-500">In use on a page or story, so it cannot be deleted.</span>
                            : <Button variant="ghost" className="text-red-600" onClick={() => { if (confirm('Delete this picture for good?')) router.delete(`/school/website/media/${open.id}`, { preserveScroll: true, onSuccess: () => setOpen(null) }); }}><Trash2 className="size-4" /> Delete</Button>)}
                        <div className="flex gap-2">
                            <Button variant="ghost" onClick={() => setOpen(null)}>Close</Button>
                            {shell.canEdit && <Button type="submit" form="media-form" disabled={form.processing || !form.isDirty} className="bg-indigo-600 text-white hover:bg-indigo-700">Save</Button>}
                        </div>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </WebsiteLayout>
    );
}
