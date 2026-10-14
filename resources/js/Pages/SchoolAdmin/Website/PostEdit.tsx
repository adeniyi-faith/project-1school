import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Panel } from '@/components/app/kit';
import { PreviewOverlay } from '@/components/app/PreviewOverlay';
import { appendDeep, usePreview } from '@/lib/preview';
import { ErrorList, GooglePreview, ImagePicker, RichText, SaveBar, StatusPill, Switch, WebsiteLayout, selectClass } from '@/components/website/kit';
import { ArrowLeft, ExternalLink, Eye } from 'lucide-react';
import type { MediaItem, PostForm, WebsiteShell } from '@/Types/website';

interface Props { shell: WebsiteShell; post: PostForm; categories: Record<string, string>; media: MediaItem[]; newsBase: string; schoolName: string }

const slugify = (s: string) => s.toLowerCase().normalize('NFKD').replace(/[^\w\s-]/g, '').trim().replace(/[\s_-]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 110);
const plain = (s: string) => s.replace(/\[([^\]]*)\]\([^)]*\)/g, '$1').replace(/[#*>_]/g, '').replace(/^\s*(-|\d+\.)\s+/gm, '').replace(/\s+/g, ' ').trim();

export default function WebsitePostEdit({ shell, post, categories, media, newsBase, schoolName }: Props) {
    const canEdit = shell.canEdit;
    const isNew = !post.id;
    const form = useForm({
        title: post.title, slug: post.slug, category: post.category, excerpt: post.excerpt, body: post.body, cover_image: post.cover_image,
        status: post.status, published_at: post.published_at, event_date: post.event_date, event_place: post.event_place,
        is_featured: post.is_featured, seo_title: post.seo_title, seo_description: post.seo_description,
    });
    const [slugTouched, setSlugTouched] = useState(!!post.slug);
    const [later, setLater] = useState(!!post.published_at && new Date(post.published_at) > new Date());
    const { state, show, close } = usePreview();
    const d = form.data;
    const slug = slugTouched ? d.slug : slugify(d.title);
    const isEvent = d.category === 'events' || !!d.event_date;
    const live = post.status === 'published' && !later;

    function submit(status: 'draft' | 'published') {
        form.transform(data => ({ ...data, status, slug, published_at: status === 'published' && !later ? (post.status === 'published' ? data.published_at : '') : data.published_at }));
        const opts = { preserveScroll: true, onSuccess: () => form.setDefaults() };
        if (isNew) form.post('/school/website/posts', opts); else form.put(`/school/website/posts/${post.id}`, opts);
    }
    function preview() {
        const fd = new FormData();
        Object.entries({ ...d, slug, id: post.id ?? 0 }).forEach(([k, v]) => appendDeep(fd, k, v));
        show('/school/website/posts/preview', fd, `Preview: ${d.title || 'your story'}`);
    }

    const url = `${newsBase}/${slug || 'your-story'}`;
    const title = d.seo_title.trim() || `${d.title || 'Your story'} | ${schoolName}`;
    const description = d.seo_description.trim() || d.excerpt.trim() || plain(d.body).slice(0, 155);
    const words = plain(d.body).split(' ').filter(Boolean).length;
    const checks = [
        { ok: d.title.length >= 20 && d.title.length <= 70, text: `Headline is ${d.title.length} characters`, tip: 'Clear headlines of 20 to 70 characters work best, for example "JSS 2 wins the state science quiz".' },
        { ok: !!d.cover_image, text: 'Has a cover picture', tip: 'Stories with a picture get far more clicks, and show a big picture when shared on WhatsApp.' },
        { ok: description.length >= 70, text: 'Has a short summary', tip: 'Write one or two sentences under "Short summary". It shows on the news page and in Google.' },
        { ok: words >= 120, text: `${words} words in the story`, tip: 'A little more writing (120+ words) helps Google show your story.' },
    ];

    return (
        <WebsiteLayout shell={shell} wide title={isNew ? 'New story' : d.title || 'Story'} crumb={[{ label: 'News & events', href: '/school/website/posts' }, { label: isNew ? 'New story' : 'Edit' }]}
            description={<Link href="/school/website/posts" className="inline-flex items-center gap-1 hover:text-slate-800"><ArrowLeft className="size-3.5" /> All stories</Link>}>
            <div className="grid gap-5 lg:grid-cols-[1fr_320px]">
                <div className="min-w-0 space-y-4">
                    <Panel>
                        <div className="space-y-4 text-sm">
                            <label className="block space-y-1"><span className="font-medium">Headline</span>
                                <Input autoFocus={isNew} disabled={!canEdit} maxLength={160} placeholder="For example: Our pupils win the state spelling bee" className="h-12 text-lg font-semibold" value={d.title} onChange={e => form.setData('title', e.target.value)} /></label>
                            <div className="space-y-1"><span className="block font-medium">Cover picture</span>
                                <ImagePicker wide value={d.cover_image} onChange={v => form.setData('cover_image', v)} disabled={!canEdit} label="Cover picture" media={media} /></div>
                            <label className="block space-y-1"><span className="font-medium">Short summary <span className="font-normal text-slate-500">· shows on the news page and in Google</span></span>
                                <Textarea disabled={!canEdit} rows={2} maxLength={300} value={d.excerpt} onChange={e => form.setData('excerpt', e.target.value)} placeholder="One or two sentences that make parents want to read on." /></label>
                            <div className="space-y-1"><span className="block font-medium">The story</span>
                                <RichText label="The story" disabled={!canEdit} rows={14} max={30000} value={d.body} onChange={v => form.setData('body', v)} />
                                <p className="text-xs text-slate-500">Use the buttons for headings, bold, lists and links. Leave an empty line between paragraphs.</p></div>
                        </div>
                    </Panel>

                    {isEvent && (
                        <Panel title="Event details" description="Shown in a box at the top of the story, and Google can list it as an event.">
                            <div className="grid gap-3 text-sm sm:grid-cols-2">
                                <label className="block space-y-1"><span className="font-medium">Date</span><Input type="date" disabled={!canEdit} value={d.event_date} onChange={e => form.setData('event_date', e.target.value)} /></label>
                                <label className="block space-y-1"><span className="font-medium">Place</span><Input disabled={!canEdit} maxLength={160} placeholder="School hall" value={d.event_place} onChange={e => form.setData('event_place', e.target.value)} /></label>
                            </div>
                        </Panel>
                    )}
                </div>

                <aside className="min-w-0 space-y-4">
                    <Panel title="Publish">
                        <div className="space-y-3 text-sm">
                            <div className="flex items-center justify-between"><span className="text-slate-500">Now</span>{isNew ? <StatusPill status="draft" /> : <StatusPill status={post.status === 'published' && later ? 'scheduled' : post.status} />}</div>
                            <label className="block space-y-1"><span className="font-medium">Kind</span>
                                <select disabled={!canEdit} className={selectClass} value={d.category} onChange={e => form.setData('category', e.target.value)}>
                                    {Object.entries(categories).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                </select></label>
                            <label className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3 dark:border-white/10"><span>Feature it <span className="block text-xs text-slate-500">Shown first in Latest news</span></span><Switch label="Feature it" disabled={!canEdit} on={d.is_featured} onChange={v => form.setData('is_featured', v)} /></label>
                            <label className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3 dark:border-white/10"><span>Publish later <span className="block text-xs text-slate-500">Pick a day and time</span></span><Switch label="Publish later" disabled={!canEdit} on={later} onChange={v => { setLater(v); if (!v) form.setData('published_at', post.published_at); }} /></label>
                            {later && <Input type="datetime-local" aria-label="Publish on" disabled={!canEdit} value={d.published_at} onChange={e => form.setData('published_at', e.target.value)} />}
                            {canEdit && (
                                <div className="flex flex-col gap-2 pt-1">
                                    <Button type="button" disabled={form.processing || !d.title.trim() || (later && !d.published_at)} onClick={() => submit('published')} className="bg-indigo-600 text-white hover:bg-indigo-700">
                                        {form.processing ? 'Saving…' : later ? 'Schedule' : live ? 'Update' : 'Publish now'}
                                    </Button>
                                    <Button type="button" variant="outline" disabled={form.processing || !d.title.trim()} onClick={() => submit('draft')}>{live ? 'Take down (make draft)' : 'Save draft'}</Button>
                                </div>
                            )}
                            {post.url && post.status === 'published' && <a href={post.url} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 text-indigo-600 hover:underline">Open on the website <ExternalLink className="size-3.5" /></a>}
                        </div>
                    </Panel>

                    <Panel title="Show up on Google">
                        <GooglePreview url={url} title={title} description={description} checks={checks}>
                            <label className="block space-y-1 text-sm"><span className="font-medium">Web address</span>
                                <Input disabled={!canEdit} value={slug} onChange={e => { setSlugTouched(true); form.setData('slug', e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, '-')); }} /></label>
                            <label className="block space-y-1 text-sm"><span className="font-medium">Title in Google <span className="font-normal text-slate-500">(optional)</span></span>
                                <Input disabled={!canEdit} maxLength={80} placeholder={`${d.title || 'Your story'} | ${schoolName}`} value={d.seo_title} onChange={e => form.setData('seo_title', e.target.value)} /></label>
                            <label className="block space-y-1 text-sm"><span className="font-medium">Description in Google <span className="font-normal text-slate-500">(optional)</span></span>
                                <Textarea disabled={!canEdit} maxLength={200} rows={2} placeholder="Uses the short summary" value={d.seo_description} onChange={e => form.setData('seo_description', e.target.value)} /></label>
                        </GooglePreview>
                    </Panel>
                </aside>
            </div>

            <ErrorList errors={form.errors} />
            <SaveBar dirty={form.isDirty}>
                <Button type="button" variant="outline" onClick={preview}><Eye className="size-4" /> Preview</Button>
                {canEdit && <Button type="button" disabled={form.processing || !d.title.trim() || (later && !d.published_at)} onClick={() => submit(isNew || post.status === 'draft' ? 'draft' : 'published')} className="bg-indigo-600 text-white hover:bg-indigo-700 lg:hidden">{isNew || post.status === 'draft' ? 'Save draft' : 'Save'}</Button>}
            </SaveBar>
            {canEdit && !isNew && <div className="flex justify-end"><button type="button" className="text-sm text-red-600 hover:underline" onClick={() => { if (confirm(`Delete "${post.title}"? It will be taken off your website.`)) router.delete(`/school/website/posts/${post.id}`); }}>Delete this story</button></div>}
            <PreviewOverlay state={state} onClose={close} />
        </WebsiteLayout>
    );
}
