<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\School;
use App\Models\SchoolSite;
use App\Models\SchoolSitePage;
use App\Website\SiteRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/** Draws a school's public website. No login needed; only schools that switched their site on are shown */
class PublicSiteController extends Controller
{
    public function show(Request $request, string $slug, ?string $page = null)
    {
        [$school, $site] = $this->live($slug);
        $pages = $this->pages($school);

        $current = $page === null
            ? ($pages->firstWhere('is_home', true) ?? $pages->first())
            : $pages->firstWhere('slug', $page);
        abort_if(! $current, 404);

        // Only announcements meant for everyone are ever shown on the open web
        $news = Announcement::withoutGlobalScopes()
            ->where('school_id', $school->id)->where('audience', 'all')
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->orderByDesc('is_pinned')->orderByDesc('published_at')->limit(10)->get(['id', 'title', 'body', 'published_at']);

        return SiteRenderer::render($school, $site, $current, $pages, $news);
    }

    /** The list of pages for Google, so every published page is found */
    public function sitemap(string $slug)
    {
        [$school] = $this->live($slug);
        $pages = $this->pages($school);

        return response()->view('website.sitemap', ['school' => $school, 'pages' => $pages])->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /** @return array{0: School, 1: SchoolSite} */
    private function live(string $slug): array
    {
        $school = School::where('slug', $slug)->where('status', 'active')->firstOrFail();
        $site = SchoolSite::withoutGlobalScopes()->where('school_id', $school->id)->where('enabled', true)->firstOrFail();

        return [$school, $site];
    }

    /** @return Collection<int, SchoolSitePage> */
    private function pages(School $school): Collection
    {
        return SchoolSitePage::withoutGlobalScopes()
            ->where('school_id', $school->id)->where('is_published', true)
            ->orderBy('position')->orderBy('id')->get();
    }
}
