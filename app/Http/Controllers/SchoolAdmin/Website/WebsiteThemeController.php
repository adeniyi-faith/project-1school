<?php

namespace App\Http\Controllers\SchoolAdmin\Website;

use App\Models\SchoolSitePage;
use App\Website\SiteRenderer;
use App\Website\Themes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Choosing the website's look: a live picture of every theme with the school's own content, and colour sets */
class WebsiteThemeController extends WebsiteBase
{
    public function index(): Response
    {
        $school = $this->school();
        $site = $this->site($school);

        return Inertia::render('SchoolAdmin/Website/Themes', $this->shell($school, $site) + [
            'themes' => Themes::options(),
            'palettes' => Themes::PALETTES,
            'logoColors' => \App\Support\LogoColors::for($school),
            'current' => ['theme' => $site->theme, 'color' => $site->color, 'color2' => $site->color2],
        ]);
    }

    /** The school's home page drawn in one theme, for the theme pictures (nothing is saved) */
    public function sample(Request $request, string $theme)
    {
        abort_unless($theme === 'current' || isset(Themes::all()[$theme]), 404);
        $school = $this->school();
        $site = $this->site($school);
        if ($theme !== 'current') {
            // "current" is the saved look; any other name is tried on with the colours being picked on screen
            $site->theme = $theme;
            $site->color = Themes::isHex($request->query('color')) ? $request->query('color') : null;
            $site->color2 = Themes::isHex($request->query('color2')) ? $request->query('color2') : null;
        }

        $pages = SchoolSitePage::where('is_published', true)->orderBy('position')->orderBy('id')->get();
        $home = $pages->firstWhere('is_home', true) ?? SchoolSitePage::orderBy('position')->first();
        abort_if(! $home, 404);

        return SiteRenderer::render($school, $site, $home, $pages, true);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'theme' => ['required', Rule::in(array_keys(Themes::all()))],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color2' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
        $this->site()->update($data);

        return back()->with('success', 'Your new look is saved. '.(Themes::all()[$data['theme']]['label']).' is now your theme.');
    }
}
