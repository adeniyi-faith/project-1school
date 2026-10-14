<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolSite;
use App\Models\SchoolSitePage;
use App\Website\Blocks;
use App\Website\SiteStarter;
use App\Website\Themes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** The school admin's editor for the public website: switch it on, pick a look, edit pages made of blocks */
class WebsiteController extends Controller
{
    public function index(Request $request): Response
    {
        $school = School::findOrFail($this->getSchoolId());
        $site = SiteStarter::ensure($school);

        return Inertia::render('SchoolAdmin/Website/Index', [
            'site' => $site->only('enabled', 'theme', 'color', 'tagline', 'meta_description', 'footer_text'),
            'pages' => SchoolSitePage::orderBy('position')->orderBy('id')->get(['id', 'slug', 'title', 'blocks', 'is_published', 'is_home', 'show_in_menu']),
            'themes' => Themes::options(),
            'blockTypes' => Blocks::catalogue(),
            'publicUrl' => url('/s/'.$school->slug),
            'canEdit' => (bool) $request->user()->can('settings.edit'),
        ]);
    }

    public function updateSite(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'theme' => ['required', Rule::in(array_keys(Themes::ALL))],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tagline' => 'nullable|string|max:160',
            'meta_description' => 'nullable|string|max:300',
            'footer_text' => 'nullable|string|max:300',
        ]);

        SiteStarter::ensure(School::findOrFail($this->getSchoolId()))->update($data);

        return back()->with('success', $data['enabled'] ? 'Your website is on. Anyone with the link can see it.' : 'Your website is off. Visitors will see "not found".');
    }

    public function storePage(Request $request): RedirectResponse
    {
        $data = $request->validate(['title' => 'required|string|max:120']);
        $slug = $this->freeSlug(Str::slug($data['title']) ?: 'page');

        SchoolSitePage::create([
            'slug' => $slug, 'title' => $data['title'], 'blocks' => [], 'is_published' => false,
            'position' => (int) SchoolSitePage::max('position') + 1,
        ]);

        return back()->with('success', "Page \"{$data['title']}\" added. It stays hidden until you publish it.");
    }

    public function updatePage(Request $request, int $page): RedirectResponse
    {
        $row = SchoolSitePage::findOrFail($page);

        $data = $request->validate([
            'title' => 'required|string|max:120',
            'slug' => ['required', 'alpha_dash:ascii', 'max:60', Rule::unique('school_site_pages', 'slug')->where('school_id', $row->school_id)->ignore($row->id)],
            'is_published' => 'required|boolean',
            'is_home' => 'required|boolean',
            'show_in_menu' => 'required|boolean',
            'blocks' => 'present|array|max:'.Blocks::MAX_BLOCKS,
        ]);

        if ($row->is_home && ! $data['is_home']) {
            return back()->withErrors(['is_home' => 'A website always needs a home page. Make another page the home page instead.']);
        }
        if ($data['is_home']) {
            SchoolSitePage::where('school_id', $row->school_id)->where('id', '!=', $row->id)->update(['is_home' => false]);
            $data['is_published'] = true;
        }

        $row->update([
            'title' => $data['title'], 'slug' => Str::lower($data['slug']),
            'is_published' => $data['is_published'], 'is_home' => $data['is_home'], 'show_in_menu' => $data['show_in_menu'],
            'blocks' => Blocks::clean($data['blocks']),
        ]);

        return back()->with('success', 'Page saved.');
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

    private function freeSlug(string $slug): string
    {
        $try = $slug;
        for ($n = 2; SchoolSitePage::withTrashed()->where('slug', $try)->exists(); $n++) {
            $try = "{$slug}-{$n}";
        }

        return $try;
    }
}
