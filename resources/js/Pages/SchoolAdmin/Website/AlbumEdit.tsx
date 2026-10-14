import { useRef, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { EmptyState, Panel } from '@/components/app/kit';
import { ErrorList, Switch, WebsiteLayout, imageUrl, uploadImage } from '@/components/website/kit';
import { ArrowLeft, ArrowRight, Check, Images, ImagePlus, Star, Trash2, Upload, X } from 'lucide-react';
import type { AlbumForm, AlbumPhoto, MediaItem, WebsiteShell } from '@/Types/website';

interface Props { shell: WebsiteShell; album: AlbumForm; photos: AlbumPhoto[]; media: MediaItem[] }

export default function AlbumEdit({ shell, album, photos, media }: Props) {
    const canEdit = shell.canEdit;
    const form = useForm({ title: album.title, description: album.description, event_date: album.event_date, is_published: album.is_published });
    const input = useRef<HTMLInputElement>(null);
    const [busy, setBusy] = useState('');
    const [errors, setErrors] = useState<string[]>([]);
    const [picker, setPicker] = useState(false);
    const [chosen, setChosen] = useState<number[]>([]);
    const base = `/school/website/gallery/${album.id}`;
    const inAlbum = new Set(photos.map(p => p.media_id));

    async function upload(files: FileList | null) {
        if (!files?.length) return;
        const list = Array.from(files).slice(0, 20);
        const ids: number[] = []; const failed: string[] = [];
        for (const [i, f] of list.entries()) {
            setBusy(`Uploading ${i + 1} of ${list.length}…`);
            try { ids.push((await uploadImage(f)).id); } catch (e) { failed.push(`${f.name}: ${(e as Error).message}`); }
        }
        setBusy(''); setErrors(failed);
        if (input.current) input.current.value = '';
        if (ids.length) router.post(`${base}/photos`, { media_ids: ids }, { preserveScroll: true });
    }
    function addChosen() { router.post(`${base}/photos`, { media_ids: chosen }, { preserveScroll: true, onSuccess: () => { setPicker(false); setChosen([]); } }); }
    function move(i: number, dir: -1 | 1) {
        const order = photos.map(p => p.id); const j = i + dir;
        if (j < 0 || j >= order.length) return;
        [order[i], order[j]] = [order[j], order[i]];
        router.put(`${base}/order`, { order }, { preserveScroll: true });
    }

    return (
        <WebsiteLayout shell={shell} title={album.title} crumb={[{ label: 'Gallery', href: '/school/website/gallery' }, { label: album.title }]}
            description="Add photos, write captions, choose the cover and put them in the order you like."
            actions={canEdit && <>
                <Button variant="outline" onClick={() => { setChosen([]); setPicker(true); }}><ImagePlus className="size-4" /> From my pictures</Button>
                <Button disabled={!!busy} onClick={() => input.current?.click()} className="bg-indigo-600 text-white hover:bg-indigo-700"><Upload className="size-4" /> {busy || 'Upload photos'}</Button>
                <input ref={input} type="file" multiple accept="image/png,image/jpeg,image/webp" className="sr-only" aria-label="Upload photos" onChange={e => upload(e.target.files)} />
            </>}>
            {errors.length > 0 && <div className="space-y-1 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300">{errors.map(e => <p key={e}>{e}</p>)}</div>}

            <Panel title="Album details">
                <form className="space-y-3 text-sm" onSubmit={e => { e.preventDefault(); form.put(base, { preserveScroll: true }); }}>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <label className="block space-y-1"><span className="font-medium">Album name</span><Input disabled={!canEdit} required maxLength={160} value={form.data.title} onChange={e => form.setData('title', e.target.value)} /></label>
                        <label className="block space-y-1"><span className="font-medium">Date (optional)</span><Input disabled={!canEdit} type="date" value={form.data.event_date} onChange={e => form.setData('event_date', e.target.value)} /></label>
                    </div>
                    <label className="block space-y-1"><span className="font-medium">Short description (optional)</span><Textarea disabled={!canEdit} maxLength={1000} rows={2} value={form.data.description} onChange={e => form.setData('description', e.target.value)} /></label>
                    <div className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2 dark:border-white/10">
                        <span>Show this album on the website <span className="block text-xs text-slate-500">Hidden albums are only visible to you here.</span></span>
                        <Switch label="Show album" disabled={!canEdit} on={form.data.is_published} onChange={v => form.setData('is_published', v)} />
                    </div>
                    <ErrorList errors={form.errors} />
                    {canEdit && (
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <Button type="submit" disabled={form.processing || !form.isDirty} className="bg-indigo-600 text-white hover:bg-indigo-700">Save album</Button>
                            <Button type="button" variant="ghost" className="text-red-600" onClick={() => { if (confirm('Delete this album? The pictures stay in your picture library.')) router.delete(base); }}><Trash2 className="size-4" /> Delete album</Button>
                        </div>
                    )}
                </form>
            </Panel>

            {photos.length === 0 ? (
                <Panel><EmptyState icon={Images} title="No photos in this album" text="Upload photos, or pick some you already uploaded to your picture library." /></Panel>
            ) : (
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    {photos.map((p, i) => (
                        <div key={p.id} className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-white/10 dark:bg-slate-900">
                            <div className="relative">
                                <img src={imageUrl(p.path)} alt={p.alt ?? ''} loading="lazy" className="aspect-[4/3] w-full object-cover" />
                                {album.cover_media_id === p.media_id && <span className="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full bg-amber-400 px-2 py-0.5 text-xs font-semibold text-slate-900"><Star className="size-3" /> Cover</span>}
                            </div>
                            <div className="space-y-2 p-2">
                                <CaptionBox photo={p} base={base} disabled={!canEdit} />
                                {canEdit && (
                                    <div className="flex items-center justify-between">
                                        <div className="flex gap-1">
                                            <Button type="button" size="icon" variant="ghost" className="size-8" aria-label="Move earlier" disabled={i === 0} onClick={() => move(i, -1)}><ArrowLeft className="size-4" /></Button>
                                            <Button type="button" size="icon" variant="ghost" className="size-8" aria-label="Move later" disabled={i === photos.length - 1} onClick={() => move(i, 1)}><ArrowRight className="size-4" /></Button>
                                        </div>
                                        <div className="flex gap-1">
                                            <Button type="button" size="icon" variant="ghost" className="size-8" aria-label="Make cover" disabled={album.cover_media_id === p.media_id} onClick={() => router.put(`${base}/cover`, { media_id: p.media_id }, { preserveScroll: true })}><Star className="size-4" /></Button>
                                            <Button type="button" size="icon" variant="ghost" className="size-8 text-red-600" aria-label="Take out of album" onClick={() => router.delete(`${base}/photos/${p.id}`, { preserveScroll: true })}><X className="size-4" /></Button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            <Dialog open={picker} onOpenChange={setPicker}>
                <DialogContent className="sm:max-w-3xl">
                    <DialogHeader><DialogTitle>Choose photos from your pictures</DialogTitle></DialogHeader>
                    {media.length === 0 ? <p className="p-6 text-center text-sm text-slate-500">Your picture library is empty. Use “Upload photos” instead.</p> : (
                        <div className="grid max-h-[55vh] grid-cols-3 gap-2 overflow-y-auto sm:grid-cols-5">
                            {media.map(m => {
                                const already = inAlbum.has(m.id); const on = chosen.includes(m.id);
                                return (
                                    <button key={m.id} type="button" disabled={already} aria-pressed={on} onClick={() => setChosen(c => on ? c.filter(x => x !== m.id) : [...c, m.id])}
                                        className={`relative overflow-hidden rounded-lg border-2 ${on ? 'border-indigo-600' : 'border-transparent'} ${already ? 'opacity-40' : ''}`}>
                                        <img src={imageUrl(m.path)} alt={m.alt ?? m.name} loading="lazy" className="aspect-square w-full object-cover" />
                                        {on && <span className="absolute right-1 top-1 grid size-6 place-items-center rounded-full bg-indigo-600 text-white"><Check className="size-4" /></span>}
                                        {already && <span className="absolute inset-x-0 bottom-0 bg-black/60 text-[10px] text-white">In album</span>}
                                    </button>
                                );
                            })}
                        </div>
                    )}
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setPicker(false)}>Cancel</Button>
                        <Button disabled={!chosen.length} onClick={addChosen} className="bg-indigo-600 text-white hover:bg-indigo-700">Add {chosen.length || ''} {chosen.length === 1 ? 'photo' : 'photos'}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </WebsiteLayout>
    );
}

/** A caption box that saves when you click away */
function CaptionBox({ photo, base, disabled }: { photo: AlbumPhoto; base: string; disabled: boolean }) {
    const [text, setText] = useState(photo.caption);
    return (
        <Input value={text} disabled={disabled} maxLength={200} placeholder="Write a caption" aria-label="Caption" className="h-8 text-xs"
            onChange={e => setText(e.target.value)}
            onBlur={() => { if (text !== photo.caption) router.put(`${base}/photos/${photo.id}`, { caption: text }, { preserveScroll: true }); }} />
    );
}
