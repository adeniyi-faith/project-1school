<?php

namespace App\Website;

use App\Models\Announcement;
use App\Models\School;
use App\Models\SchoolSite;
use App\Models\SchoolSitePage;
use App\Models\SchoolSitePost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Draws a school's website: pages, the news list and single posts, with everything phones and search engines
 * need. Used for the real site and for previews in the editor (where links do nothing and nothing is saved).
 */
class SiteRenderer
{
    public const SOCIAL = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'x' => 'X', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'whatsapp' => 'WhatsApp'];

    public static function homeUrl(School $school): string
    {
        return url('/s/'.$school->slug);
    }

    public static function pageUrl(School $school, SchoolSitePage $page): string
    {
        return $page->is_home ? self::homeUrl($school) : self::homeUrl($school).'/'.$page->slug;
    }

    public static function newsUrl(School $school, ?string $category = null): string
    {
        return self::homeUrl($school).'/news'.($category ? '?category='.$category : '');
    }

    public static function postUrl(School $school, SchoolSitePost $post): string
    {
        return self::homeUrl($school).'/news/'.$post->slug;
    }

    public static function image(?string $path): ?string
    {
        return $path ? asset('storage/'.$path) : null;
    }

    /** @param Collection<int, SchoolSitePage> $pages the published pages */
    public static function render(School $school, SchoolSite $site, SchoolSitePage $page, Collection $pages, bool $preview = false): View
    {
        $blocks = collect($page->blocks ?? [])->reject(fn ($b) => ! empty($b['hidden']))->values()->all();

        // exactly one main heading (h1) per page: the first section that has a heading
        foreach ($blocks as $i => $b) {
            if (! empty($b['heading'])) {
                $blocks[$i]['_h1'] = true;
                break;
            }
        }

        $first = collect($blocks)->first(fn ($b) => ! empty($b['body'] ?? $b['subheading'] ?? $b['intro'] ?? ''));
        $auto = $first ? Prose::plain($first['body'] ?? $first['subheading'] ?? $first['intro'] ?? '', 155) : null;
        $url = self::pageUrl($school, $page);
        $firstImage = collect($blocks)->map(fn ($b) => $b['image'] ?? null)->filter()->first();

        $ctx = self::context($school, $site, $pages, $preview, $url, $page->is_home ? 'home' : $page->slug);
        $title = $page->seo_title ?: ($page->is_home ? $school->name.($site->tagline ? ' – '.$site->tagline : '') : $page->title.' | '.$school->name);
        $description = $page->seo_description ?: $site->meta_description ?: $auto;
        $image = self::image($firstImage) ?? $ctx['logo'];

        $graph = [self::organisation($school, $site, $description, $image, $ctx['social']), self::webPage($school, $title, $url, $description)];
        if (! $page->is_home) {
            $graph[] = self::breadcrumbs($school, [[$page->title, $url]]);
        }
        $faq = collect($blocks)->where('type', 'faq')->flatMap(fn ($b) => $b['items'] ?? [])->filter(fn ($i) => ! empty($i['question']) && ! empty($i['answer']))->values();
        if ($faq->isNotEmpty()) {
            $graph[] = ['@type' => 'FAQPage', 'mainEntity' => $faq->map(fn ($i) => ['@type' => 'Question', 'name' => $i['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => Prose::plain($i['answer'])]])->all()];
        }

        return view('website.page', $ctx + [
            'page' => $page, 'blocks' => $blocks, 'needsHiddenH1' => ! collect($blocks)->contains(fn ($b) => ! empty($b['_h1'])),
            'news' => self::newsItems($school), 'title' => $title, 'description' => $description, 'ogImage' => $image, 'ogType' => 'website',
            'schema' => ['@context' => 'https://schema.org', '@graph' => $graph],
        ]);
    }

    /** The news and events list */
    public static function renderNews(School $school, SchoolSite $site, Collection $pages, LengthAwarePaginator $posts, ?string $category): View
    {
        $label = $site->option('news_label');
        $url = self::newsUrl($school, $category);
        $ctx = self::context($school, $site, $pages, false, $url, 'news');
        $title = ($category ? SchoolSitePost::CATEGORIES[$category].' | ' : $label.' | ').$school->name;
        $description = 'The latest news, events and achievements from '.$school->name.'.';

        return view('website.news', $ctx + [
            'posts' => $posts, 'category' => $category, 'label' => $label, 'categories' => SchoolSitePost::CATEGORIES,
            'title' => $title, 'description' => $description, 'ogImage' => $ctx['logo'], 'ogType' => 'website',
            'schema' => ['@context' => 'https://schema.org', '@graph' => [
                self::organisation($school, $site, $site->meta_description, $ctx['logo'], $ctx['social']),
                ['@type' => 'CollectionPage', 'name' => $title, 'url' => $url, 'description' => $description],
                self::breadcrumbs($school, [[$label, self::newsUrl($school)]]),
            ]],
        ]);
    }

    /** One news story or event */
    public static function renderPost(School $school, SchoolSite $site, Collection $pages, SchoolSitePost $post, Collection $related, bool $preview = false): View
    {
        $url = self::postUrl($school, $post);
        $ctx = self::context($school, $site, $pages, $preview, $url, 'news');
        $title = $post->seo_title ?: $post->title.' | '.$school->name;
        $description = $post->seo_description ?: $post->excerpt ?: Prose::plain($post->body, 155);
        $image = self::image($post->cover_image) ?? $ctx['logo'];
        $published = $post->published_at ?? now();

        $article = array_filter([
            '@type' => $post->category === 'events' ? 'Event' : 'NewsArticle',
            'headline' => $post->title, 'name' => $post->title, 'description' => $description, 'image' => $image ? [$image] : null, 'url' => $url,
            'datePublished' => $post->category === 'events' ? null : $published->toAtomString(),
            'dateModified' => $post->category === 'events' ? null : ($post->updated_at ?? $published)->toAtomString(),
            'startDate' => $post->category === 'events' && $post->event_date ? $post->event_date->toDateString() : null,
            'location' => $post->category === 'events' && $post->event_place ? ['@type' => 'Place', 'name' => $post->event_place, 'address' => $ctx['place'] ?: $post->event_place] : null,
            'organizer' => $post->category === 'events' ? ['@id' => self::homeUrl($school).'#school'] : null,
            'publisher' => $post->category === 'events' ? null : ['@id' => self::homeUrl($school).'#school'],
            'author' => $post->category === 'events' ? null : ['@id' => self::homeUrl($school).'#school'],
        ]);
        if ($post->category === 'events' && empty($article['startDate'])) {
            $article['@type'] = 'NewsArticle'; // Google needs a date for an event; without one it is a news story
            $article['datePublished'] = $published->toAtomString();
        }

        return view('website.post', $ctx + [
            'post' => $post, 'related' => $related, 'label' => $site->option('news_label'),
            'title' => $title, 'description' => $description, 'ogImage' => $image, 'ogType' => 'article',
            'schema' => ['@context' => 'https://schema.org', '@graph' => [
                self::organisation($school, $site, $site->meta_description, $ctx['logo'], $ctx['social']),
                $article,
                self::breadcrumbs($school, [[$site->option('news_label'), self::newsUrl($school)], [$post->title, $url]]),
            ]],
        ]);
    }

    /**
     * The newest stories for the "Latest news" section: website posts when the school writes them,
     * otherwise its announcements meant for everyone.
     */
    public static function newsItems(School $school): Collection
    {
        $posts = SchoolSitePost::withoutGlobalScopes()->where('school_id', $school->id)->whereNull('deleted_at')->live()
            ->orderByDesc('is_featured')->orderByDesc('published_at')->limit(10)->get();

        if ($posts->isNotEmpty()) {
            return $posts->map(fn ($p) => [
                'title' => $p->title, 'url' => self::postUrl($school, $p), 'excerpt' => $p->excerpt ?: Prose::plain($p->body, 180),
                'date' => $p->category === 'events' && $p->event_date ? $p->event_date : $p->published_at, 'image' => self::image($p->cover_image),
                'tag' => $p->categoryLabel(), 'category' => $p->category,
            ]);
        }

        return Announcement::withoutGlobalScopes()->where('school_id', $school->id)->where('audience', 'all')
            ->whereNotNull('published_at')->where('published_at', '<=', now())->whereNull('deleted_at')
            ->orderByDesc('is_pinned')->orderByDesc('published_at')->limit(10)->get(['title', 'body', 'published_at'])
            ->map(fn ($a) => ['title' => $a->title, 'url' => null, 'excerpt' => Str::limit(trim(preg_replace('/\s+/', ' ', $a->body)), 180), 'date' => $a->published_at, 'image' => null, 'tag' => 'News', 'category' => 'news']);
    }

    /** What every page of the site shares: header, menu, footer, colours */
    private static function context(School $school, SchoolSite $site, Collection $pages, bool $preview, string $url, string $current): array
    {
        $home = self::homeUrl($school);
        $menu = $pages->where('show_in_menu', true)->values()->map(fn ($p) => [
            'label' => $p->title, 'url' => self::pageUrl($school, $p), 'current' => $current === ($p->is_home ? 'home' : $p->slug), 'slug' => $p->slug,
        ]);

        $hasPosts = SchoolSitePost::withoutGlobalScopes()->where('school_id', $school->id)->whereNull('deleted_at')->live()->exists();
        if ($hasPosts && $site->option('news_in_menu')) {
            $news = ['label' => $site->option('news_label'), 'url' => self::newsUrl($school), 'current' => $current === 'news', 'slug' => 'news'];
            $at = $menu->search(fn ($m) => $m['slug'] === 'contact');
            $menu = $at === false ? $menu->push($news) : $menu->splice(0, $at)->push($news)->merge($menu);
        }

        $contactPage = $pages->firstWhere('slug', 'contact');

        return [
            'school' => $school, 'site' => $site, 'preview' => $preview, 'menu' => $menu->values(), 'homeUrl' => $home, 'pageUrl' => $url,
            'contactUrl' => $contactPage ? self::pageUrl($school, $contactPage) : ($school->email ? 'mailto:'.$school->email : null),
            'newsUrl' => $hasPosts ? self::newsUrl($school) : null,
            'style' => Themes::style($site->theme, $site->color, $site->color2), 'theme' => $site->theme,
            'logo' => $school->logo ? $school->logo_url : null,
            'social' => collect($site->social ?? [])->filter(fn ($u) => is_string($u) && preg_match('#^https://#', $u))->all(),
            'place' => collect([$school->address, $school->city, $school->state])->filter()->implode(', '),
        ];
    }

    private static function organisation(School $school, SchoolSite $site, ?string $description, ?string $image, array $social): array
    {
        $address = array_filter(['@type' => 'PostalAddress', 'streetAddress' => $school->address, 'addressLocality' => $school->city, 'addressRegion' => $school->state, 'addressCountry' => $school->country]);

        return array_filter([
            '@type' => 'EducationalOrganization', '@id' => self::homeUrl($school).'#school',
            'name' => $school->name, 'url' => self::homeUrl($school), 'description' => $description ?: $site->meta_description,
            'logo' => $school->logo ? $school->logo_url : null, 'image' => $image,
            'telephone' => $school->phone, 'email' => $school->email,
            'address' => count($address) > 1 ? $address : null,
            'sameAs' => array_values($social) ?: null,
        ]);
    }

    private static function webPage(School $school, string $title, string $url, ?string $description): array
    {
        return array_filter(['@type' => 'WebPage', 'name' => $title, 'url' => $url, 'description' => $description, 'isPartOf' => ['@id' => self::homeUrl($school).'#school']]);
    }

    /** @param array<int, array{0: string, 1: string}> $trail */
    private static function breadcrumbs(School $school, array $trail): array
    {
        $items = [['@type' => 'ListItem', 'position' => 1, 'name' => $school->name, 'item' => self::homeUrl($school)]];
        foreach ($trail as $i => [$name, $url]) {
            $items[] = ['@type' => 'ListItem', 'position' => $i + 2, 'name' => $name, 'item' => $url];
        }

        return ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }
}
