<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\School;
use App\Models\SchoolSitePage;
use App\Support\ImageShrinker;
use App\Website\Blocks;
use App\Website\SiteRenderer;
use App\Website\SiteStarter;
use App\Website\Themes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** The school admin's editor for the public website: switch it on, pick a look, edit pages made of sections */
class WebsiteController extends Controller
{
    public function index(Request $request): Response
    {
        $school = School::findOrFail($this->getSchoolId());
        $site = SiteStarter::ensure($school);

        return Inertia::render('SchoolAdmin/Website/Index', [
            'site' => [
                'enabled' => $site->enabled, 'theme' => $site->theme, 'color' => $site->color, 'color2' => $site->color2,
                'tagline' => $site->tagline, 'meta_description' => $site->meta_description, 'footer_text' => $site->footer_text,
                'social' => array_merge(array_fill_keys(array_keys(SiteRenderer::SOCIAL), ''), $site->social ?? []),
            ],
            'pages' => SchoolSitePage::orderBy('position')->orderBy('id')->get(['id', 'slug', 'title', 'seo_title', 'seo_description', 'blocks', 'is_published', 'is_home', 'show_in_menu']),
            'themes' => Themes::options(),
            'socialNames' => SiteRenderer::SOCIAL,
            'blockTypes' => Blocks::catalogue(),
            'publicUrl' => SiteRenderer::homeUrl($school),
            'school' => ['name' => $school->name, 'phone' => $school->phone, 'email' => $school->email, 'address' => $school->address, 'logo' => (bool) $school->logo],
            'canEdit' => (bool) $request->user()->can('settings.edit'),
        ]);
    }

    public function updateSite(Request $request): RedirectResponse
    {
        $data = $request->validate($this->siteRules());
        $data['social'] = collect($data['social'] ?? [])->map(fn ($u) => trim((string) $u))->filter()->all();

        SiteStarter::ensure(School::findOrFail($this->getSchoolId()))->update($data);

        return back()->with('success', $data['enabled'] ? 'Saved. Your website is on and anyone with the link can see it.' : 'Saved. Your website is off, so visitors see "not found".');
    }

    public function storePage(Request $request): RedirectResponse
    {
        $data = $request->validate(['title' => 'required|string|max:120']);

        SchoolSitePage::create([
            'slug' => $this->freeSlug(Str::slug($data['title']) ?: 'page'), 'title' => $data['title'], 'blocks' => [], 'is_published' => false,
            'position' => (int) SchoolSitePage::max('position') + 1,
        ]);

        return back()->with('success', "Page \"{$data['title']}\" added. It stays hidden until you make it visible.");
    }

    public function updatePage(Request $request, int $page): RedirectResponse
    {
        $row = SchoolSitePage::findOrFail($page);
        $data = $request->validate($this->pageRules($row));

        if ($row->is_home && ! $data['is_home']) {
            return back()->withErrors(['is_home' => 'A website always needs a home page. Make another page the home page instead.']);
        }
        if ($data['is_home']) {
            SchoolSitePage::where('school_id', $row->school_id)->where('id', '!=', $row->id)->update(['is_home' => false]);
            $data['is_published'] = true;
        }

        $row->update([
            'title' => $data['title'], 'slug' => Str::lower($data['slug']),
            'seo_title' => $data['seo_title'] ?? null, 'seo_description' => $data['seo_description'] ?? null,
            'is_published' => $data['is_published'], 'is_home' => $data['is_home'], 'show_in_menu' => $data['show_in_menu'],
            'blocks' => Blocks::clean($data['blocks'], $row->school_id),
        ]);

        return back()->with('success', 'Saved.');
    }

    public function destroyPage(int $page): RedirectResponse
    {
        $row = SchoolSitePage::findOrFail($page);
        if ($row->is_home) {
            return back()->withErrors(['page' => 'The home page cannot be deleted. Make another page the home page first.']);
        }
        $row->delete();

        return back()->with('success', 'Page deleted.');
    }

    /** A picture for a section. Kept in the school's own website folder; the editor then saves its path with the section */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240']);
        $sid = $this->getSchoolId();
        $path = ImageShrinker::store($request->file('image'), "schools/{$sid}/website", 'public', 1800);

        return response()->json(['path' => $path, 'url' => asset('storage/'.$path)]);
    }

    /** Draws the page exactly as typed on screen, saving nothing, so people can look before they publish */
    public function preview(Request $request)
    {
        $school = School::findOrFail($this->getSchoolId());
        $site = SiteStarter::ensure($school);

        $data = $request->validate([
            'site' => 'array', 'site.theme' => ['nullable', Rule::in(array_keys(Themes::ALL))],
            'site.color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'], 'site.color2' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'site.tagline' => 'nullable|string|max:160', 'site.footer_text' => 'nullable|string|max:300', 'site.meta_description' => 'nullable|string|max:300',
            'site.social' => 'nullable|array',
            'page_id' => 'required|integer',
            'page' => 'array', 'page.title' => 'nullable|string|max:120', 'page.seo_title' => 'nullable|string|max:80', 'page.seo_description' => 'nullable|string|max:200',
            'page.has_blocks' => 'nullable|boolean', 'page.blocks' => 'nullable|array|max:'.Blocks::MAX_BLOCKS,
        ]);

        $site->fill(collect($data['site'] ?? [])->only(['theme', 'color', 'color2', 'tagline', 'footer_text', 'meta_description'])->all());
        $site->social = collect($data['site']['social'] ?? $site->social ?? [])->filter()->all();
        $site->theme = $site->theme ?: 'heritage';

        $pages = SchoolSitePage::orderBy('position')->orderBy('id')->get();
        $current = $pages->firstWhere('id', $data['page_id']);
        abort_if(! $current, 404);
        $current->fill(collect($data['page'] ?? [])->only(['title', 'seo_title', 'seo_description'])->all());
        if ($request->boolean('page.has_blocks')) {
            $current->blocks = Blocks::clean($data['page']['blocks'] ?? [], $school->id);
        }

        $news = Announcement::where('audience', 'all')->whereNotNull('published_at')->orderByDesc('published_at')->limit(10)->get(['id', 'title', 'body', 'published_at']);

        return SiteRenderer::render($school, $site, $current, $pages->where('is_published', true)->values(), $news, true);
    }

    private function siteRules(): array
    {
        return [
            'enabled' => 'required|boolean',
            'theme' => ['required', Rule::in(array_keys(Themes::ALL))],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color2' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tagline' => 'nullable|string|max:160',
            'meta_description' => 'nullable|string|max:300',
            'footer_text' => 'nullable|string|max:300',
            'social' => 'nullable|array',
            'social.*' => ['nullable', 'url:https', 'max:300'],
        ];
    }

    private function pageRules(SchoolSitePage $row): array
    {
        return [
            'title' => 'required|string|max:120',
            'slug' => ['required', 'alpha_dash:ascii', 'max:60', Rule::unique('school_site_pages', 'slug')->where('school_id', $row->school_id)->ignore($row->id)],
            'seo_title' => 'nullable|string|max:80',
            'seo_description' => 'nullable|string|max:200',
            'is_published' => 'required|boolean',
            'is_home' => 'required|boolean',
            'show_in_menu' => 'required|boolean',
            'blocks' => 'present|array|max:'.Blocks::MAX_BLOCKS,
        ];
    }

    private function freeSlug(string $slug): string
    {
        $try = $slug;
        for ($n = 2; SchoolSitePage::withTrashed()->where('slug', $try)->exists(); $n++) {
            $try = "{$slug}-{$n}";
        }

        return $try;
    }
}
