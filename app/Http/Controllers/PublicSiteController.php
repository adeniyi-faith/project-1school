<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolSite;
use App\Models\SchoolSiteAlbum;
use App\Models\SchoolSitePage;
use App\Website\LiveData;
use App\Website\Themes;
use App\Models\SchoolSitePost;
use App\Website\Prose;
use App\Website\SiteRenderer;
use App\Website\SiteSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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

    /** One sign-in page for parents, students and staff, drawn in the school's look. It posts to the normal login. */
    public function signIn(string $slug)
    {
        [$school, $site] = $this->live($slug);

        return SiteRenderer::renderPlain($school, $site, $this->pages($school), 'website.sign-in', 'sign-in', 'Sign in',
            'Parents, students and staff: sign in to see results, fees, notices and your school tools.');
    }

    /** The website's message form: it lands in Admissions > Inquiries so nothing is lost, and staff get a bell notice */
    public function contact(Request $request, string $slug)
    {
        [$school] = $this->live($slug);

        $data = $request->validate([
            'name' => 'required|string|max:150', 'phone' => 'required|string|max:25',
            'email' => 'nullable|email|max:150', 'message' => 'required|string|max:1000',
        ]);

        // A hidden box real people never see: a robot that fills it in is thanked and ignored
        if (! $request->filled('company_site')) {
            $inquiry = \App\Models\AdmissionInquiry::create([
                'school_id' => $school->id, 'student_name' => 'General question', 'class_interested' => 'Not stated',
                'guardian_name' => $data['name'], 'guardian_phone' => $data['phone'], 'guardian_email' => $data['email'] ?? null,
                'notes' => $data['message'], 'source' => 'website',
            ]);
            app(\App\Services\WebhookService::class)->dispatch($school->id, 'inquiry.created', [
                'id' => $inquiry->id, 'reference' => $inquiry->fresh()->reference, 'student_name' => $inquiry->student_name, 'guardian_name' => $inquiry->guardian_name,
                'guardian_phone' => $inquiry->guardian_phone, 'guardian_email' => $inquiry->guardian_email, 'message' => $data['message'], 'source' => 'website',
            ]);
            \App\Models\User::permission('students.view')->where('school_id', $school->id)->get()
                ->each(fn ($u) => \App\Models\SchoolNotification::create([
                    'school_id' => $school->id, 'user_id' => $u->id, 'type' => 'admission_inquiry',
                    'title' => 'Website message from '.$data['name'], 'body' => \Illuminate\Support\Str::limit($data['message'], 120).' Phone: '.$data['phone'],
                    'data' => ['url' => '/school/admissions/inquiries/'.$inquiry->id], 'channel' => 'in-app',
                ]));
        }

        return redirect(url()->previous().'#message')->with('contacted', 'Thank you. Your message has been sent and the office will get back to you.');
    }

    /** Search the school's own pages, stories and albums */
    public function search(Request $request, string $slug)
    {
        [$school, $site] = $this->live($slug);
        $query = trim((string) $request->query('q', ''));
        $results = SiteSearch::run($school, $query);

        return SiteRenderer::renderSearch($school, $site, $this->pages($school), Str::limit($query, SiteSearch::MAX, ''), $results);
    }

    public function gallery(string $slug)
    {
        [$school, $site] = $this->live($slug);

        return SiteRenderer::renderGallery($school, $site, $this->pages($school), SiteRenderer::albums($school));
    }

    public function album(string $slug, string $album)
    {
        [$school, $site] = $this->live($slug);
        $row = SiteRenderer::albums($school)->firstWhere('slug', $album);
        abort_if(! $row, 404);
        $photos = $row->photos->filter(fn ($p) => $p->media)->values()->map(fn ($p) => ['path' => $p->media->path, 'alt' => $p->caption ?: ($p->media->alt ?: $p->media->name), 'caption' => $p->caption]);

        return SiteRenderer::renderAlbum($school, $site, $this->pages($school), $row, $photos);
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
            'school' => $school, 'pages' => $this->pages($school), 'albums' => SiteRenderer::albums($school),
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

    /** The whole public site as data, for developers building their own pages or apps on top of the school's content */
    public function siteJson(string $slug)
    {
        [$school, $site] = $this->live($slug);
        $theme = Themes::all()[$site->theme] ?? Themes::ALL['heritage'];
        $date = fn ($d) => $d?->toDateString();

        return $this->json([
            'school' => [
                'name' => $school->name, 'slug' => $school->slug, 'tagline' => $site->tagline, 'logo' => $school->logo ? $school->logo_url : null,
                'address' => collect([$school->address, $school->city, $school->state])->filter()->implode(', '), 'phone' => $school->phone, 'email' => $school->email,
                'social' => $site->social ?? [], 'url' => SiteRenderer::homeUrl($school),
            ],
            'theme' => ['key' => $site->theme, 'label' => $theme['label'], 'brand' => $site->color ?: $theme['brand'], 'brand2' => $site->color2 ?: $theme['brand2']],
            'pages' => $this->pages($school)->map(fn ($p) => [
                'title' => $p->title, 'slug' => $p->slug, 'url' => SiteRenderer::pageUrl($school, $p), 'is_home' => $p->is_home, 'in_menu' => $p->show_in_menu,
                'description' => $p->seo_description, 'blocks' => $p->blocks,
            ])->values(),
            'live' => [
                'fees' => LiveData::fees($school)->map(fn ($c) => ['class' => $c['class'], 'lines' => $c['lines'], 'term_total' => $c['term_total']])->values(),
                'terms' => collect(LiveData::dates($school)['terms'])->map(fn ($t) => ['name' => $t['name'], 'year' => $t['year'], 'start' => $date($t['start']), 'end' => $date($t['end'])])->values(),
                'holidays' => collect(LiveData::dates($school)['holidays'])->map(fn ($h) => ['name' => $h['name'], 'date' => $date($h['date'])])->values(),
                'team' => LiveData::team($school)->values(),
                'events' => LiveData::events($school, 12)->map(fn ($e) => ['title' => $e['title'], 'date' => $date($e['date']), 'place' => $e['place'], 'url' => $e['url']])->values(),
            ],
        ]);
    }

    /** News and events as data */
    public function newsJson(Request $request, string $slug)
    {
        [$school] = $this->live($slug);
        $posts = $this->posts($school)->when($request->query('category'), fn ($q, $c) => $q->where('category', (string) $c))
            ->orderByDesc('published_at')->limit(50)->get();

        return $this->json(['posts' => $posts->map(fn ($p) => [
            'title' => $p->title, 'slug' => $p->slug, 'url' => SiteRenderer::postUrl($school, $p), 'category' => $p->category,
            'summary' => $p->excerpt ?: Prose::plain($p->body, 300), 'cover' => SiteRenderer::image($p->cover_image),
            'published_at' => $p->published_at?->toIso8601String(), 'event_date' => $p->event_date?->toDateString(), 'event_place' => $p->event_place,
        ])->values()]);
    }

    /** Public data: anyone may read it from any website, and it may be kept for five minutes */
    private function json(array $data)
    {
        return response()->json($data, 200, ['Access-Control-Allow-Origin' => '*', 'Cache-Control' => 'public, max-age=300'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
