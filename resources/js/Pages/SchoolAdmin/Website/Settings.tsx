import { Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Panel } from '@/components/app/kit';
import { ErrorList, SaveBar, Switch, WebsiteLayout } from '@/components/website/kit';
import type { WebsiteShell } from '@/Types/website';

interface Props {
    shell: WebsiteShell; socialNames: Record<string, string>; sitemapUrl: string; feedUrl: string;
    site: { enabled: boolean; tagline: string | null; meta_description: string | null; footer_text: string | null; social: Record<string, string>; news_label: string; news_in_menu: boolean; apply_button: boolean };
    school: { name: string; phone: string | null; email: string | null; address: string | null; logo: string | null };
}

const row = 'flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3 dark:border-white/10';

export default function WebsiteSettings({ shell, site, school, socialNames, sitemapUrl, feedUrl }: Props) {
    const canEdit = shell.canEdit;
    const form = useForm({
        enabled: site.enabled, tagline: site.tagline ?? '', meta_description: site.meta_description ?? '', footer_text: site.footer_text ?? '',
        social: site.social, news_label: site.news_label, news_in_menu: site.news_in_menu, apply_button: site.apply_button,
    });
    const d = form.data;

    return (
        <WebsiteLayout shell={shell} title="Site settings" crumb={[{ label: 'Settings' }]} description="Things that show on every page: your motto, the footer, your social media and the menu.">
            <Panel title="Website on or off">
                <label className={row}><span><span className="block text-sm font-medium">{d.enabled ? 'Your website is on' : 'Your website is off'}</span><span className="block text-xs text-slate-500">{d.enabled ? 'Anyone with the link, and Google, can see it.' : 'Visitors see "not found" until you switch it on.'}</span></span>
                    <Switch label="Website is on" disabled={!canEdit} on={d.enabled} onChange={v => form.setData('enabled', v)} /></label>
            </Panel>

            <Panel title="About your school" description="Your name, logo, address, phone and email come from your school profile.">
                <div className="space-y-4 text-sm">
                    <div className="flex items-start gap-3 rounded-lg bg-slate-50 p-3 dark:bg-white/5">
                        {school.logo ? <img src={school.logo} alt="" className="size-12 shrink-0 rounded object-contain" /> : <span className="grid size-12 shrink-0 place-items-center text-center leading-tight rounded bg-slate-200 text-xs text-slate-500 dark:bg-white/10">No logo</span>}
                        <div className="min-w-0 break-words text-xs">
                            <p className="text-sm font-semibold">{school.name}</p>
                            <p>{school.address || <span className="text-amber-700">Address not added</span>} · {school.phone || <span className="text-amber-700">Phone not added</span>} · {school.email || <span className="text-amber-700">Email not added</span>}</p>
                            <Link href="/school/settings" className="text-indigo-600 hover:underline">Change these in Settings</Link>
                        </div>
                    </div>
                    <label className="block space-y-1"><span className="font-medium">Motto or tagline</span><Input disabled={!canEdit} maxLength={160} placeholder="Knowledge and character" value={d.tagline} onChange={e => form.setData('tagline', e.target.value)} /></label>
                    <label className="block space-y-1"><span className="font-medium">Short description for Google</span><Textarea disabled={!canEdit} maxLength={300} placeholder="A caring nursery, primary and secondary school in Lekki, Lagos, with strong WAEC results." value={d.meta_description} onChange={e => form.setData('meta_description', e.target.value)} /><span className="block text-xs text-slate-500">Used for any page that has no description of its own.</span></label>
                    <label className="block space-y-1"><span className="font-medium">Line at the bottom of every page</span><Input disabled={!canEdit} maxLength={300} placeholder="Approved by the Lagos State Ministry of Education" value={d.footer_text} onChange={e => form.setData('footer_text', e.target.value)} /></label>
                </div>
            </Panel>

            <Panel title="Menu and buttons">
                <div className="space-y-3 text-sm">
                    <label className="block space-y-1"><span className="font-medium">Name of the news page</span><Input disabled={!canEdit} maxLength={30} placeholder="News" value={d.news_label} onChange={e => form.setData('news_label', e.target.value)} /><span className="block text-xs text-slate-500">For example "News", "Blog" or "School life".</span></label>
                    <label className={row}><span>Show the news page in the menu <span className="block text-xs text-slate-500">Only once you have published a story</span></span><Switch label="News in menu" disabled={!canEdit} on={d.news_in_menu} onChange={v => form.setData('news_in_menu', v)} /></label>
                    <label className={row}><span>"Apply online" button in the header <span className="block text-xs text-slate-500">Opens your admission form</span></span><Switch label="Apply button" disabled={!canEdit} on={d.apply_button} onChange={v => form.setData('apply_button', v)} /></label>
                </div>
            </Panel>

            <Panel title="Social media" description="Paste the full link to each page you have. They show as icons in the footer.">
                <div className="grid gap-3 text-sm sm:grid-cols-2">
                    {Object.entries(socialNames).map(([key, label]) => (
                        <label key={key} className="block space-y-1"><span className="font-medium">{label}</span>
                            <Input disabled={!canEdit} placeholder={key === 'whatsapp' ? 'https://wa.me/2348012345678' : 'https://…'} value={d.social[key] ?? ''} onChange={e => form.setData('social', { ...d.social, [key]: e.target.value })} /></label>
                    ))}
                </div>
            </Panel>

            <Panel title="For search engines" description="Made for you and kept up to date. You only need these if you add your site to Google Search Console.">
                <ul className="space-y-1 text-sm">
                    <li>Sitemap: <a className="break-all text-indigo-600 hover:underline" href={sitemapUrl} target="_blank" rel="noreferrer">{sitemapUrl}</a></li>
                    <li>News feed (RSS): <a className="break-all text-indigo-600 hover:underline" href={feedUrl} target="_blank" rel="noreferrer">{feedUrl}</a></li>
                </ul>
            </Panel>

            <ErrorList errors={form.errors} />
            {canEdit && (
                <SaveBar dirty={form.isDirty}>
                    <Button type="button" disabled={form.processing || !form.isDirty} onClick={() => form.put('/school/website', { preserveScroll: true, onSuccess: () => form.setDefaults() })} className="bg-indigo-600 text-white hover:bg-indigo-700">{form.processing ? 'Saving…' : 'Save settings'}</Button>
                </SaveBar>
            )}
        </WebsiteLayout>
    );
}
