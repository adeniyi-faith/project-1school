import { useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { EmptyState, Panel, Pill } from '@/components/app/kit';
import { ErrorList, WebsiteLayout, imageUrl } from '@/components/website/kit';
import { Images, Plus } from 'lucide-react';
import type { AlbumRow, WebsiteShell } from '@/Types/website';

interface Props { shell: WebsiteShell; albums: AlbumRow[] }

export default function WebsiteGallery({ shell, albums }: Props) {
    const [open, setOpen] = useState(false);
    const form = useForm({ title: '', description: '', event_date: '', is_published: true });

    return (
        <WebsiteLayout shell={shell} title="Gallery" crumb={[{ label: 'Gallery' }]}
            description="Photo albums for your website: sports day, graduation, trips. Visitors see them at the Gallery page, and the newest photos can also show on your home page."
            actions={shell.canEdit && <Button onClick={() => setOpen(true)} className="bg-indigo-600 text-white hover:bg-indigo-700"><Plus className="size-4" /> New album</Button>}>
            {albums.length === 0 ? (
                <Panel><EmptyState icon={Images} title="No albums yet" text="Create an album, then add photos from your picture library or upload new ones." /></Panel>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {albums.map(a => (
                        <Link key={a.id} href={`/school/website/gallery/${a.id}`} className="group overflow-hidden rounded-xl border border-slate-200 bg-white hover:border-indigo-400 dark:border-white/10 dark:bg-slate-900">
                            {a.cover
                                ? <img src={imageUrl(a.cover)} alt="" loading="lazy" className="aspect-[4/3] w-full object-cover transition-transform group-hover:scale-[1.02]" />
                                : <div className="grid aspect-[4/3] place-items-center bg-slate-100 text-slate-400 dark:bg-white/5"><Images className="size-8" /></div>}
                            <div className="space-y-1 p-3">
                                <div className="flex items-center justify-between gap-2"><h2 className="truncate font-medium">{a.title}</h2>{a.is_published ? <Pill tone="good">Visible</Pill> : <Pill tone="neutral">Hidden</Pill>}</div>
                                <p className="text-xs text-slate-500">{a.photos} {a.photos === 1 ? 'photo' : 'photos'}{a.event_date ? ` · ${a.event_date}` : ''}</p>
                            </div>
                        </Link>
                    ))}
                </div>
            )}

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader><DialogTitle>New album</DialogTitle></DialogHeader>
                    <form id="album-form" className="space-y-3 text-sm" onSubmit={e => { e.preventDefault(); form.post('/school/website/gallery'); }}>
                        <label className="block space-y-1"><span className="font-medium">Album name</span><Input autoFocus required maxLength={160} placeholder="Inter-house sports 2026" value={form.data.title} onChange={e => form.setData('title', e.target.value)} /></label>
                        <label className="block space-y-1"><span className="font-medium">Short description (optional)</span><Textarea maxLength={1000} rows={3} value={form.data.description} onChange={e => form.setData('description', e.target.value)} /></label>
                        <label className="block space-y-1"><span className="font-medium">Date (optional)</span><Input type="date" value={form.data.event_date} onChange={e => form.setData('event_date', e.target.value)} /></label>
                        <ErrorList errors={form.errors} />
                    </form>
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
                        <Button type="submit" form="album-form" disabled={form.processing || !form.data.title.trim()} className="bg-indigo-600 text-white hover:bg-indigo-700">Create album</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </WebsiteLayout>
    );
}
