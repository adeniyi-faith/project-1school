<?php

namespace App\Http\Controllers\SchoolAdmin\Website;

use App\Models\SchoolApiToken;
use App\Models\SchoolSitePage;
use App\Models\SchoolWebhook;
use App\Services\WebhookService;
use App\Website\Blocks;
use App\Website\SafeCss;
use App\Website\SiteRenderer;
use App\Website\Themes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Website > Developer: extra CSS, analytics, webhooks, a read-only key, and moving a whole site in or out */
class WebsiteDeveloperController extends WebsiteBase
{
    /** Site options that travel with an exported site (never keys or secrets) */
    private const EXPORT_OPTIONS = ['news_label', 'news_in_menu', 'apply_button', 'announcement', 'cookie_notice', 'logo_badge', 'logo_badge_shape', 'logo_badge_colour', 'whatsapp', 'whatsapp_text', 'custom_css'];

    public function index(): Response
    {
        $school = $this->school();
        $site = $this->site($school);
        $hook = SchoolWebhook::first();
        $home = SiteRenderer::homeUrl($school);

        return Inertia::render('SchoolAdmin/Website/Developer', $this->shell($school, $site) + [
            'dev' => [
                'custom_css' => (string) $site->option('custom_css'), 'ga_id' => (string) $site->option('ga_id'),
                'verify_google' => (string) $site->option('verify_google'), 'verify_bing' => (string) $site->option('verify_bing'),
            ],
            'webhook' => $hook ? ['url' => $hook->url, 'events' => $hook->events ?? [], 'is_active' => $hook->is_active, 'last_status' => $hook->last_status, 'last_error' => $hook->last_error, 'last_sent_at' => $hook->last_sent_at?->toIso8601String()] : null,
            'eventNames' => SchoolWebhook::EVENTS,
            'newSecret' => session('dev_secret'),
            'tokens' => SchoolApiToken::latest()->get(['id', 'name', 'last_four', 'last_used_at', 'created_at']),
            'newToken' => session('dev_token'),
            'urls' => ['site' => $home.'/site.json', 'news' => $home.'/news.json', 'api' => url('/api/school'), 'docs' => url('/docs')],
            'customThemes' => collect(Themes::custom())->map(fn ($t, $k) => ['key' => $k, 'label' => $t['label'], 'base' => $t['base']])->values(),
            'customBlocks' => collect(Blocks::custom())->map(fn ($t, $k) => ['type' => $k, 'label' => $t['label']])->values(),
        ]);
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'custom_css' => 'nullable|string|max:8000',
            'ga_id' => ['nullable', 'regex:/^G-[A-Z0-9]{6,14}$/'],
            'verify_google' => ['nullable', 'regex:/^[A-Za-z0-9_-]{20,100}$/'],
            'verify_bing' => ['nullable', 'regex:/^[A-Za-z0-9_-]{20,100}$/'],
        ], ['ga_id.regex' => 'A Google Analytics ID looks like G-ABC123XYZ.', 'verify_google.regex' => 'Paste only the code from the verification tag, not the whole tag.', 'verify_bing.regex' => 'Paste only the code from the verification tag, not the whole tag.']);

        $site = $this->site();
        $options = $site->options ?? [];
        $options['custom_css'] = SafeCss::clean((string) ($data['custom_css'] ?? ''));
        foreach (['ga_id', 'verify_google', 'verify_bing'] as $k) {
            $options[$k] = trim((string) ($data[$k] ?? ''));
        }
        $site->update(['options' => $options]);

        return back()->with('success', 'Saved. Anything in the CSS box that could run code or load other files was removed.');
    }

    public function saveWebhook(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'url' => 'required|string|max:500',
            'events' => 'required|array|min:1',
            'events.*' => [Rule::in(array_keys(SchoolWebhook::EVENTS))],
            'is_active' => 'sometimes|boolean',
        ]);
        if ($problem = WebhookService::urlProblem($data['url'])) {
            return back()->withErrors(['url' => $problem]);
        }

        $hook = SchoolWebhook::first();
        if ($hook) {
            $hook->update(['url' => $data['url'], 'events' => array_values($data['events']), 'is_active' => (bool) ($data['is_active'] ?? true)]);

            return back()->with('success', 'Webhook saved.');
        }

        $secret = Str::random(40);
        SchoolWebhook::create(['school_id' => $this->getSchoolId(), 'url' => $data['url'], 'secret' => $secret, 'events' => array_values($data['events']), 'is_active' => true]);

        // shown once, on the next screen; it signs every message so the receiver can check it came from here
        return back()->with('success', 'Webhook saved. Copy the signing secret now; it is shown only once.')->with('dev_secret', $secret);
    }

    public function testWebhook(WebhookService $webhooks): RedirectResponse
    {
        $hook = SchoolWebhook::firstOrFail();
        [$ok, $message] = $webhooks->send($hook, 'test', ['message' => 'This is a test from SchoolRuns.']);

        return back()->with($ok ? 'success' : 'error', $ok ? 'Test delivered. Your receiver answered OK.' : "Test failed: {$message}");
    }

    public function removeWebhook(): RedirectResponse
    {
        SchoolWebhook::query()->delete();

        return back()->with('success', 'Webhook removed.');
    }

    public function makeToken(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:80']);
        abort_if(SchoolApiToken::count() >= 5, 422, 'You can have up to 5 keys. Revoke one first.');

        $key = 'sr_'.Str::random(40);
        SchoolApiToken::create(['school_id' => $this->getSchoolId(), 'name' => $data['name'], 'token_hash' => hash('sha256', $key), 'last_four' => substr($key, -4), 'created_by' => $request->user()->id]);

        return back()->with('success', 'Key made. Copy it now; it is shown only once.')->with('dev_token', $key);
    }

    public function revokeToken(int $token): RedirectResponse
    {
        SchoolApiToken::findOrFail($token)->delete();

        return back()->with('success', 'Key revoked. Anything using it stops working now.');
    }

    /** The whole site (look, settings and pages) as one file, to back up or move to another school */
    public function export(): StreamedResponse
    {
        $school = $this->school();
        $site = $this->site($school);
        $pages = SchoolSitePage::orderBy('position')->orderBy('id')->get();
        $slugOf = $pages->pluck('slug', 'id');

        $payload = [
            'format' => 'schoolruns-site', 'version' => 1, 'exported_at' => now()->toIso8601String(), 'from' => $school->name,
            'site' => ['theme' => $site->theme, 'color' => $site->color, 'color2' => $site->color2, 'tagline' => $site->tagline,
                'meta_description' => $site->meta_description, 'footer_text' => $site->footer_text, 'social' => $site->social ?? [],
                'options' => collect($site->options ?? [])->only(self::EXPORT_OPTIONS)->all()],
            'pages' => $pages->map(fn ($p) => [
                'title' => $p->title, 'slug' => $p->slug, 'is_home' => $p->is_home, 'is_published' => $p->is_published, 'show_in_menu' => $p->show_in_menu,
                'parent' => $p->parent_id ? ($slugOf[$p->parent_id] ?? null) : null, 'seo_title' => $p->seo_title, 'seo_description' => $p->seo_description, 'blocks' => $p->blocks,
            ])->values()->all(),
        ];

        return response()->streamDownload(
            fn () => print(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            Str::slug($school->name).'-website-'.now()->format('Y-m-d').'.json',
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Bring pages (and, if ticked, the look and settings) in from an exported file. Pages with a new address are added;
     * pages with an address already in use are only replaced when "replace" is ticked. Everything is cleaned the same
     * way as in the page editor, so a file cannot bring in anything the editor would refuse. Pictures from another
     * school are left out, because each school can only use its own.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|max:2048', 'replace' => 'sometimes|boolean', 'apply_settings' => 'sometimes|boolean']);
        $json = json_decode((string) file_get_contents($request->file('file')->getRealPath()), true);
        if (! is_array($json) || ($json['format'] ?? '') !== 'schoolruns-site' || ! is_array($json['pages'] ?? null)) {
            return back()->withErrors(['file' => 'That is not a SchoolRuns website file. Use one made with "Download my website".']);
        }

        $school = $this->school();
        $replace = $request->boolean('replace');
        $added = 0;
        $replaced = 0;
        $skipped = 0;
        $slugs = [];

        foreach (array_slice($json['pages'], 0, 40) as $row) {
            $slug = Str::slug((string) ($row['slug'] ?? ''));
            $title = trim((string) ($row['title'] ?? ''));
            if ($slug === '' || $title === '' || in_array($slug, Blocks::RESERVED_SLUGS, true)) {
                $skipped++;
                continue;
            }
            $fields = [
                'title' => mb_substr($title, 0, 120), 'seo_title' => mb_substr((string) ($row['seo_title'] ?? ''), 0, 80) ?: null, 'seo_description' => mb_substr((string) ($row['seo_description'] ?? ''), 0, 200) ?: null,
                'blocks' => Blocks::clean($row['blocks'] ?? [], $school->id),
            ];
            $existing = SchoolSitePage::where('slug', $slug)->first();
            if ($existing) {
                if ($replace) {
                    $existing->update($fields);
                    $replaced++;
                } else {
                    $skipped++;
                }
                continue;
            }
            SchoolSitePage::create($fields + ['slug' => $slug, 'is_published' => (bool) ($row['is_published'] ?? false), 'is_home' => false, 'show_in_menu' => (bool) ($row['show_in_menu'] ?? true), 'position' => (int) SchoolSitePage::max('position') + 1]);
            $added++;
            $slugs[$slug] = $row['parent'] ?? null;
        }
        // sub-menus, only between pages this school now has
        foreach ($slugs as $slug => $parent) {
            $parentPage = $parent ? SchoolSitePage::where('slug', Str::slug((string) $parent))->first() : null;
            if ($parentPage && ! $parentPage->is_home && ! $parentPage->parent_id) {
                SchoolSitePage::where('slug', $slug)->update(['parent_id' => $parentPage->id]);
            }
        }

        $settings = '';
        if ($request->boolean('apply_settings') && is_array($json['site'] ?? null)) {
            $in = $json['site'];
            $site = $this->site($school);
            $options = $site->options ?? [];
            foreach (collect($in['options'] ?? [])->only(self::EXPORT_OPTIONS) as $k => $v) {
                $options[$k] = $k === 'custom_css' ? SafeCss::clean((string) $v) : (is_bool($v) || is_string($v) ? $v : null);
            }
            $site->update([
                'theme' => isset(Themes::all()[$in['theme'] ?? '']) ? $in['theme'] : $site->theme,
                'color' => Themes::isHex($in['color'] ?? null) ? $in['color'] : $site->color, 'color2' => Themes::isHex($in['color2'] ?? null) ? $in['color2'] : $site->color2,
                'tagline' => mb_substr((string) ($in['tagline'] ?? ''), 0, 160) ?: $site->tagline,
                'meta_description' => mb_substr((string) ($in['meta_description'] ?? ''), 0, 300) ?: $site->meta_description,
                'footer_text' => mb_substr((string) ($in['footer_text'] ?? ''), 0, 300) ?: $site->footer_text,
                'options' => array_filter($options, fn ($v) => $v !== null),
            ]);
            $settings = ' The look and settings were applied too.';
        }

        return back()->with('success', "Imported: {$added} page".($added === 1 ? '' : 's').' added'.($replaced ? ", {$replaced} replaced" : '').($skipped ? ", {$skipped} skipped (address already used, or not valid)" : '').'.'.$settings);
    }
}
