<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolSite;
use App\Models\SchoolSitePage;
use App\Models\SchoolSitePost;
use App\Website\Prose;
use App\Website\SiteRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/** Draws a school's public website. No login needed; only schools that switched their site on are shown */
class PublicSiteController extends Controller
{
    public function show(string $slug, ?string $page = null)
    {
        [$school, $site] = $this->live($slug);
        $pages = $this->pages($school);

        $current = $page === null
            ? ($pages->firstWhere('is_home', true) ?? $pages->first())
            : $pages->firstWhere('slug', $page);
        abort_if(! $current, 404);

        return SiteRenderer::render($school, $site, $current, $pages);
    }

    public function news(Request $request, string $slug)
    {
        [$school, $site] = $this->live($slug);
        $category = array_key_exists((string) $request->query('category'), SchoolSitePost::CATEGORIES) ? $request->query('category') : null;

        $posts = $this->posts($school)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderByDesc('published_at')->paginate(12)->withQueryString();

        return SiteRenderer::renderNews($school, $site, $this->pages($school), $posts, $category);
    }

    public function post(string $slug, string $post)
    {
        [$school, $site] = $this->live($slug);
        $row = $this->posts($school)->where('slug', $post)->firstOrFail();
        // three more stories, the same kind first
        $related = $this->posts($school)->where('id', '!=', $row->id)->orderByDesc('published_at')->limit(12)->get()
            ->sortBy(fn ($p) => $p->category === $row->category ? 0 : 1)->take(3)->values();

        return SiteRenderer::renderPost($school, $site, $this->pages($school), $row, $related);
    }

    /** The list of pages and posts for Google, so everything published is found */
    public function sitemap(string $slug)
    {
        [$school] = $this->live($slug);

        return response()->view('website.sitemap', [
            'school' => $school, 'pages' => $this->pages($school),
            'posts' => $this->posts($school)->orderByDesc('published_at')->limit(1000)->get(['id', 'slug', 'updated_at', 'published_at']),
        ])->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /** News feed (RSS) so news readers and other sites can follow the school's stories */
    public function feed(string $slug)
    {
        [$school, $site] = $this->live($slug);
        $posts = $this->posts($school)->orderByDesc('published_at')->limit(20)->get();

        return response()->view('website.feed', [
            'school' => $school, 'site' => $site, 'posts' => $posts,
            'summary' => fn ($p) => $p->excerpt ?: Prose::plain($p->body, 300),
        ])->header('Content-Type', 'application/rss+xml; charset=UTF-8');
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
            ->where('school_id', $school->id)->where('is_published', true)->whereNull('deleted_at')
            ->orderBy('position')->orderBy('id')->get();
    }

    private function posts(School $school)
    {
        return SchoolSitePost::withoutGlobalScopes()->where('school_id', $school->id)->whereNull('deleted_at')->live();
    }
}
