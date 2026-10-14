<?php

namespace App\Website;

use App\Models\School;
use App\Models\SchoolSite;
use App\Models\SchoolSitePage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** Draws one page of a school's website, with everything search engines and phones need. Used for the real site and for previews */
class SiteRenderer
{
    public const SOCIAL = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'x' => 'X', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube'];

    public static function homeUrl(School $school): string
    {
        return url('/s/'.$school->slug);
    }

    public static function pageUrl(School $school, SchoolSitePage $page): string
    {
        return $page->is_home ? self::homeUrl($school) : self::homeUrl($school).'/'.$page->slug;
    }

    /** @param Collection<int, SchoolSitePage> $pages the published pages, for the menu */
    public static function render(School $school, SchoolSite $site, SchoolSitePage $page, Collection $pages, Collection $news, bool $preview = false): View
    {
        $blocks = collect($page->blocks ?? [])->reject(fn ($b) => ! empty($b['hidden']))->values()->all();

        // exactly one main heading (h1) per page: the first section that has a heading
        foreach ($blocks as $i => $b) {
            if (! empty($b['heading'])) {
                $blocks[$i]['_h1'] = true;
                break;
            }
        }
        $needsHiddenH1 = ! collect($blocks)->contains(fn ($b) => ! empty($b['_h1']));

        $contactPage = $pages->firstWhere('slug', 'contact');
        $logo = $school->logo ? $school->logo_url : null;
        $firstImage = collect($blocks)->map(fn ($b) => $b['image'] ?? null)->filter()->first();
        $social = collect($site->social ?? [])->filter(fn ($u) => is_string($u) && preg_match('#^https://#', $u))->all();

        $title = $page->seo_title ?: ($page->is_home
            ? $school->name.($site->tagline ? ' – '.$site->tagline : '')
            : $page->title.' | '.$school->name);

        $first = collect($blocks)->first(fn ($b) => in_array($b['type'], ['text', 'hero', 'apply'], true) && ! empty($b['body'] ?? $b['subheading'] ?? ''));
        $auto = $first ? Str::limit(trim(preg_replace('/\s+/', ' ', (string) ($first['body'] ?? $first['subheading'] ?? ''))), 155, '…') : null;
        $description = $page->seo_description ?: $site->meta_description ?: $auto;

        $url = self::pageUrl($school, $page);
        $image = $firstImage ? asset('storage/'.$firstImage) : $logo;

        return view('website.page', [
            'school' => $school, 'site' => $site, 'page' => $page, 'blocks' => $blocks, 'menu' => $pages->where('show_in_menu', true)->values(),
            'news' => $news, 'preview' => $preview, 'needsHiddenH1' => $needsHiddenH1,
            'homeUrl' => self::homeUrl($school), 'pageUrl' => $url, 'contactUrl' => $contactPage ? self::pageUrl($school, $contactPage) : ($school->email ? 'mailto:'.$school->email : null),
            'style' => Themes::style($site->theme, $site->color, $site->color2), 'theme' => $site->theme, 'btnStyle' => Themes::btnStyle($site->theme),
            'logo' => $logo, 'social' => $social, 'title' => $title, 'description' => $description, 'ogImage' => $image,
            'schema' => self::schema($school, $page, $blocks, $url, $title, $description, $image, $social),
            'place' => collect([$school->address, $school->city, $school->state])->filter()->implode(', '),
        ]);
    }

    /** Structured data (JSON-LD) so Google understands this is a school, where it is, and what questions it answers */
    private static function schema(School $school, SchoolSitePage $page, array $blocks, string $url, string $title, ?string $description, ?string $image, array $social): array
    {
        $org = array_filter([
            '@type' => 'EducationalOrganization', '@id' => self::homeUrl($school).'#school',
            'name' => $school->name, 'url' => self::homeUrl($school), 'description' => $description,
            'logo' => $school->logo ? $school->logo_url : null, 'image' => $image,
            'telephone' => $school->phone, 'email' => $school->email,
            'address' => array_filter(['@type' => 'PostalAddress', 'streetAddress' => $school->address, 'addressLocality' => $school->city, 'addressRegion' => $school->state, 'addressCountry' => $school->country]) ?: null,
            'sameAs' => array_values($social) ?: null,
        ]);
        if (isset($org['address']) && count($org['address']) < 2) {
            unset($org['address']);
        }

        $graph = [$org, array_filter([
            '@type' => 'WebPage', 'name' => $title, 'url' => $url, 'description' => $description, 'isPartOf' => ['@id' => self::homeUrl($school).'#school'],
        ])];

        if (! $page->is_home) {
            $graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => $school->name, 'item' => self::homeUrl($school)],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $page->title, 'item' => $url],
            ]];
        }

        $faq = collect($blocks)->where('type', 'faq')->flatMap(fn ($b) => $b['items'] ?? [])->filter(fn ($i) => ! empty($i['question']) && ! empty($i['answer']))->values();
        if ($faq->isNotEmpty()) {
            $graph[] = ['@type' => 'FAQPage', 'mainEntity' => $faq->map(fn ($i) => ['@type' => 'Question', 'name' => $i['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $i['answer']]])->all()];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }
}
