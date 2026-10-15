<?php

namespace App\Http\Controllers\SchoolAdmin\Website;

use App\Models\SchoolSiteMedia;
use App\Models\SchoolSitePage;
use App\Models\SchoolSitePost;
use App\Website\LogoFit;
use App\Website\SiteRenderer;
use App\Website\Themes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** The website's overview (dashboard) and its settings */
class WebsiteController extends WebsiteBase
{
    public function overview(): Response
    {
        $school = $this->school();
        $site = $this->site($school);
        $home = SchoolSitePage::where('is_home', true)->first();

        return Inertia::render('SchoolAdmin/Website/Overview', $this->shell($school, $site) + [
            'theme' => Themes::all()[$site->theme]['label'] ?? 'Heritage',
            'counts' => [
                'pages' => SchoolSitePage::count(),
                'published' => SchoolSitePost::where('status', 'published')->count(),
                'drafts' => SchoolSitePost::where('status', 'draft')->count(),
                'media' => SchoolSiteMedia::count(),
            ],
            'recent' => SchoolSitePost::latest('updated_at')->limit(5)->get(['id', 'title', 'status', 'category', 'updated_at']),
            'homeId' => $home?->id,
            'checklist' => [
                ['key' => 'logo', 'done' => (bool) $school->logo, 'label' => 'Add your school logo', 'href' => '/school/settings'],
                ['key' => 'contact', 'done' => $school->phone && $school->address, 'label' => 'Add your address and phone number', 'href' => '/school/settings'],
                ['key' => 'theme', 'done' => $site->updated_at && $site->updated_at->gt($site->created_at), 'label' => 'Choose a theme and your colours', 'href' => '/school/website/themes'],
                ['key' => 'home', 'done' => $home && $home->updated_at->gt($home->created_at), 'label' => 'Make the home page your own', 'href' => $home ? "/school/website/pages/{$home->id}" : '/school/website/pages'],
                ['key' => 'post', 'done' => SchoolSitePost::where('status', 'published')->exists(), 'label' => 'Publish your first news story', 'href' => '/school/website/posts/create'],
                ['key' => 'live', 'done' => $site->enabled, 'label' => 'Switch your website on', 'href' => '/school/website/settings'],
            ],
            'sitemapUrl' => SiteRenderer::homeUrl($school).'/sitemap.xml',
        ]);
    }

    public function settings(): Response
    {
        $school = $this->school();
        $site = $this->site($school);

        return Inertia::render('SchoolAdmin/Website/Settings', $this->shell($school, $site) + [
            'site' => [
                'enabled' => $site->enabled, 'tagline' => $site->tagline, 'meta_description' => $site->meta_description, 'footer_text' => $site->footer_text,
                'social' => array_merge(array_fill_keys(array_keys(SiteRenderer::SOCIAL), ''), $site->social ?? []),
                'news_label' => $site->option('news_label'), 'news_in_menu' => (bool) $site->option('news_in_menu'), 'apply_button' => (bool) $site->option('apply_button'), 'announcement' => (string) $site->option('announcement'), 'cookie_notice' => (bool) $site->option('cookie_notice'),
                'whatsapp' => (string) $site->option('whatsapp'), 'whatsapp_text' => (string) $site->option('whatsapp_text'),
                'logo_badge' => $site->option('logo_badge'), 'logo_badge_shape' => $site->option('logo_badge_shape'), 'logo_badge_colour' => $site->option('logo_badge_colour'),
            ],
            'socialNames' => SiteRenderer::SOCIAL,
            'logoOptions' => ['modes' => LogoFit::MODES, 'shapes' => LogoFit::SHAPES, 'colours' => LogoFit::COLOURS],
            'school' => ['name' => $school->name, 'phone' => $school->phone, 'email' => $school->email, 'address' => collect([$school->address, $school->city, $school->state])->filter()->implode(', '), 'logo' => $school->logo ? $school->logo_url : null],
            'sitemapUrl' => SiteRenderer::homeUrl($school).'/sitemap.xml',
            'feedUrl' => SiteRenderer::homeUrl($school).'/feed.xml',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => 'sometimes|boolean',
            'tagline' => 'sometimes|nullable|string|max:160',
            'meta_description' => 'sometimes|nullable|string|max:300',
            'footer_text' => 'sometimes|nullable|string|max:300',
            'social' => 'sometimes|nullable|array',
            'social.*' => ['nullable', 'url:https', 'max:300'],
            'news_label' => 'sometimes|nullable|string|max:30',
            'news_in_menu' => 'sometimes|boolean',
            'apply_button' => 'sometimes|boolean',
            'announcement' => 'sometimes|nullable|string|max:200',
            'cookie_notice' => 'sometimes|boolean',
            'whatsapp' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^[0-9+\s()-]*$/'],
            'whatsapp_text' => 'sometimes|nullable|string|max:160',
            'logo_badge' => ['sometimes', Rule::in(array_keys(LogoFit::MODES))],
            'logo_badge_shape' => ['sometimes', Rule::in(array_keys(LogoFit::SHAPES))],
            'logo_badge_colour' => ['sometimes', Rule::in(array_keys(LogoFit::COLOURS))],
        ]);

        $site = $this->site();
        $options = $site->options ?? [];
        foreach (['news_label', 'news_in_menu', 'apply_button', 'announcement', 'cookie_notice', 'whatsapp', 'whatsapp_text', 'logo_badge', 'logo_badge_shape', 'logo_badge_colour'] as $key) {
            if (array_key_exists($key, $data)) {
                $options[$key] = match ($key) {
                    'news_label' => trim((string) $data[$key]) ?: 'News',
                    'announcement', 'whatsapp', 'whatsapp_text' => trim((string) $data[$key]),
                    'logo_badge', 'logo_badge_shape', 'logo_badge_colour' => (string) $data[$key],
                    default => (bool) $data[$key],
                };
                unset($data[$key]);
            }
        }
        if (array_key_exists('social', $data)) {
            $data['social'] = collect($data['social'] ?? [])->map(fn ($u) => trim((string) $u))->filter()->all();
        }

        $site->update($data + ['options' => $options]);

        $message = array_key_exists('enabled', $data) && count($data) === 1
            ? ($data['enabled'] ? 'Your website is live. Anyone with the link can see it.' : 'Your website is off. Visitors now see "not found".')
            : 'Saved.';

        return back()->with('success', $message);
    }
}
