<?php

namespace App\Http\Controllers\SchoolAdmin\Website;

use App\Models\SchoolSitePage;
use App\Website\Blocks;
use App\Website\PageTemplates;
use App\Website\SiteRenderer;
use App\Website\Themes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** The website's pages: a list to add from ready-made templates, and one editor per page */
class WebsitePageController extends WebsiteBase
{
    public function index(): Response
    {
        $school = $this->school();
        $site = $this->site($school);

        return Inertia::render('SchoolAdmin/Website/Pages', $this->shell($school, $site) + [
            'pages' => SchoolSitePage::orderBy('position')->orderBy('id')->get(['id', 'slug', 'title', 'blocks', 'is_published', 'is_home', 'show_in_menu', 'updated_at'])
                ->map(fn ($p) => [
                    'id' => $p->id, 'slug' => $p->slug, 'title' => $p->title, 'is_published' => $p->is_published, 'is_home' => $p->is_home,
                    'show_in_menu' => $p->show_in_menu, 'sections' => count($p->blocks ?? []), 'updated_at' => $p->updated_at,
                    'url' => SiteRenderer::pageUrl($school, $p),
                ]),
            'templates' => PageTemplates::options(),
        ]);
    }

    public function edit(int $page): Response
    {
        $school = $this->school();
        $site = $this->site($school);
        $row = SchoolSitePage::findOrFail($page);

        return Inertia::render('SchoolAdmin/Website/PageEdit', $this->shell($school, $site) + [
            'page' => $row->only(['id', 'slug', 'title', 'seo_title', 'seo_description', 'blocks', 'is_published', 'is_home', 'show_in_menu']),
            'blockTypes' => Blocks::catalogue(),
            'media' => $this->media(),
            'site' => ['tagline' => $site->tagline, 'theme' => Themes::ALL[$site->theme]['label'] ?? 'Heritage'],
            'school' => ['name' => $school->name, 'phone' => $school->phone, 'address' => $school->address],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'template' => ['required', Rule::in(array_keys(PageTemplates::all()))],
        ]);
        $school = $this->school();

        $row = SchoolSitePage::create([
            'slug' => $this->freeSlug(Str::slug($data['title']) ?: 'page'), 'title' => $data['title'],
            'blocks' => Blocks::clean(PageTemplates::blocks($data['template'], $school), $school->id),
            'is_published' => false, 'show_in_menu' => true,
            'position' => (int) SchoolSitePage::max('position') + 1,
        ]);

        return redirect("/school/website/pages/{$row->id}")->with('success', "Page \"{$row->title}\" made. Change the words and pictures, then switch on \"Visible to visitors\".");
    }

    public function update(Request $request, int $page): RedirectResponse
    {
        $row = SchoolSitePage::findOrFail($page);
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'slug' => ['required', 'alpha_dash:ascii', 'max:60', Rule::notIn(Blocks::RESERVED_SLUGS), Rule::unique('school_site_pages', 'slug')->where('school_id', $row->school_id)->ignore($row->id)],
            'seo_title' => 'nullable|string|max:80',
            'seo_description' => 'nullable|string|max:200',
            'is_published' => 'required|boolean',
            'is_home' => 'required|boolean',
            'show_in_menu' => 'required|boolean',
            'blocks' => 'present|array|max:'.Blocks::MAX_BLOCKS,
        ], ['slug.not_in' => 'That web address is used by the website itself. Pick another one.']);

        if ($row->is_home && ! $data['is_home']) {
            return back()->withErrors(['is_home' => 'A website always needs a home page. Make another page the home page instead.']);
        }
        if ($data['is_home']) {
            SchoolSitePage::where('id', '!=', $row->id)->update(['is_home' => false]);
            $data['is_published'] = true;
        }

        $row->update([
            'title' => $data['title'], 'slug' => Str::lower($data['slug']),
            'seo_title' => $data['seo_title'] ?? null, 'seo_description' => $data['seo_description'] ?? null,
            'is_published' => $data['is_published'], 'is_home' => $data['is_home'], 'show_in_menu' => $data['show_in_menu'],
            'blocks' => Blocks::clean($data['blocks'], $row->school_id),
        ]);

        return back()->with('success', $row->is_published ? 'Saved. Visitors see the new version now.' : 'Saved. This page is still hidden from visitors.');
    }

    /** Quick switches from the pages list (visible, in menu) without opening the editor */
    public function toggle(Request $request, int $page): RedirectResponse
    {
        $row = SchoolSitePage::findOrFail($page);
        $data = $request->validate(['is_published' => 'sometimes|boolean', 'show_in_menu' => 'sometimes|boolean']);
        if ($row->is_home) {
            unset($data['is_published']);
        }
        $row->update($data);

        return back()->with('success', 'Saved.');
    }

    public function duplicate(int $page): RedirectResponse
    {
        $row = SchoolSitePage::findOrFail($page);
        $copy = SchoolSitePage::create([
            'slug' => $this->freeSlug($row->slug.'-copy'), 'title' => $row->title.' (copy)', 'blocks' => $row->blocks ?? [],
            'seo_title' => null, 'seo_description' => $row->seo_description, 'is_published' => false, 'show_in_menu' => false,
            'position' => (int) SchoolSitePage::max('position') + 1,
        ]);

        return redirect("/school/website/pages/{$copy->id}")->with('success', 'A hidden copy was made. Change it, then make it visible.');
    }

    /** The menu order: the list of page ids from top to bottom */
    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate(['ids' => 'required|array|max:200', 'ids.*' => 'integer']);
        $pages = SchoolSitePage::whereIn('id', $data['ids'])->get()->keyBy('id');
        foreach (array_values($data['ids']) as $i => $id) {
            $pages->get($id)?->update(['position' => $i]);
        }

        return back()->with('success', 'Menu order saved.');
    }

    public function destroy(int $page): RedirectResponse
    {
        $row = SchoolSitePage::findOrFail($page);
        if ($row->is_home) {
            return back()->withErrors(['page' => 'The home page cannot be deleted. Make another page the home page first.']);
        }
        $row->delete();

        return redirect('/school/website/pages')->with('success', "Page \"{$row->title}\" deleted.");
    }

    /** Draws the page exactly as typed on screen, saving nothing, so people can look before they publish */
    public function preview(Request $request)
    {
        $school = $this->school();
        $site = $this->site($school);

        $data = $request->validate([
            'page_id' => 'required|integer',
            'page' => 'array', 'page.title' => 'nullable|string|max:120', 'page.seo_title' => 'nullable|string|max:80', 'page.seo_description' => 'nullable|string|max:200',
            'page.has_blocks' => 'nullable|boolean', 'page.blocks' => 'nullable|array|max:'.Blocks::MAX_BLOCKS,
            'site' => 'array', 'site.theme' => ['nullable', Rule::in(array_keys(Themes::ALL))],
            'site.color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'], 'site.color2' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $site->fill(collect($data['site'] ?? [])->only(['theme', 'color', 'color2'])->filter()->all());

        $pages = SchoolSitePage::orderBy('position')->orderBy('id')->get();
        $current = $pages->firstWhere('id', $data['page_id']);
        abort_if(! $current, 404);
        $current->fill(collect($data['page'] ?? [])->only(['title', 'seo_title', 'seo_description'])->all());
        if ($request->boolean('page.has_blocks')) {
            $current->blocks = Blocks::clean($data['page']['blocks'] ?? [], $school->id);
        }

        return SiteRenderer::render($school, $site, $current, $pages->where('is_published', true)->values(), true);
    }

    private function freeSlug(string $slug): string
    {
        $slug = in_array($slug, Blocks::RESERVED_SLUGS, true) ? $slug.'-page' : $slug;
        $try = $slug;
        for ($n = 2; SchoolSitePage::withTrashed()->where('slug', $try)->exists(); $n++) {
            $try = "{$slug}-{$n}";
        }

        return $try;
    }
}
