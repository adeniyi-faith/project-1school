<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\School;
use App\Models\SchoolSite;
use App\Models\SchoolSitePage;
use App\Website\Themes;
use Illuminate\Http\Request;

/** Draws a school's public website. No login needed; only schools that switched their site on are shown */
class PublicSiteController extends Controller
{
    public function show(Request $request, string $slug, ?string $page = null)
    {
        $school = School::where('slug', $slug)->where('status', 'active')->firstOrFail();
        $site = SchoolSite::withoutGlobalScopes()->where('school_id', $school->id)->where('enabled', true)->firstOrFail();

        $pages = SchoolSitePage::withoutGlobalScopes()
            ->where('school_id', $school->id)->where('is_published', true)
            ->orderBy('position')->orderBy('id')->get();

        $current = $page === null
            ? ($pages->firstWhere('is_home', true) ?? $pages->first())
            : $pages->firstWhere('slug', $page);
        abort_if(! $current, 404);

        // Only announcements meant for everyone are ever shown on the open web
        $news = Announcement::withoutGlobalScopes()
            ->where('school_id', $school->id)->where('audience', 'all')
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->orderByDesc('is_pinned')->orderByDesc('published_at')->limit(10)->get(['id', 'title', 'body', 'published_at']);

        return response()->view('website.page', [
            'school'  => $school,
            'site'    => $site,
            'page'    => $current,
            'menu'    => $pages->where('show_in_menu', true)->values(),
            'news'    => $news,
            'css'     => Themes::css($site->theme, $site->color),
            'homeUrl' => url('/s/'.$school->slug),
        ]);
    }
}
