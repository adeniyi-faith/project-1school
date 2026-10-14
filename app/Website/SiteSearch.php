<?php

namespace App\Website;

use App\Models\School;
use App\Models\SchoolSiteAlbum;
use App\Models\SchoolSitePage;
use App\Models\SchoolSitePost;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Search inside one school's public website: its pages, news stories and photo albums.
 * Every word typed must appear somewhere in the item (capitals do not matter). Only what a visitor may already see is searched.
 */
class SiteSearch
{
    public const MIN = 2;
    public const MAX = 80;

    /** The block fields that hold words a visitor reads */
    private const TEXT_KEYS = ['heading', 'eyebrow', 'intro', 'subheading', 'lead', 'body', 'text', 'title', 'name', 'role', 'question', 'answer', 'label', 'value', 'who', 'foot'];

    /** The words to look for: at most 6, each at least 2 letters */
    public static function terms(string $query): array
    {
        $words = preg_split('/\s+/u', mb_strtolower(trim($query)), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_slice(array_unique(array_filter($words, fn ($w) => mb_strlen($w) >= self::MIN)), 0, 6));
    }

    /** @return Collection<int, array{kind: string, title: string, url: string, snippet: HtmlString, image: ?string, score: int}> */
    public static function run(School $school, string $query): Collection
    {
        $query = Str::limit(trim($query), self::MAX, '');
        $terms = self::terms($query);
        if ($terms === []) {
            return collect();
        }

        return self::pages($school, $terms)->concat(self::posts($school, $terms))->concat(self::albums($school, $terms))
            ->sortByDesc('score')->values();
    }

    private static function pages(School $school, array $terms): Collection
    {
        return SchoolSitePage::withoutGlobalScopes()->where('school_id', $school->id)->where('is_published', true)->whereNull('deleted_at')
            ->orderBy('position')->get()
            ->map(function ($p) use ($school, $terms) {
                $text = self::words($p->blocks ?? []);
                $found = self::match($terms, $p->title, $text.' '.$p->seo_description);

                return $found === null ? null : [
                    'kind' => 'Page', 'title' => $p->title, 'url' => SiteRenderer::pageUrl($school, $p), 'image' => null,
                    'snippet' => self::snippet($text ?: (string) $p->seo_description, $terms), 'score' => $found + ($p->is_home ? 0 : 1),
                ];
            })->filter()->values();
    }

    private static function posts(School $school, array $terms): Collection
    {
        $query = SchoolSitePost::withoutGlobalScopes()->where('school_id', $school->id)->whereNull('deleted_at')->live();
        foreach ($terms as $t) {
            $like = '%'.addcslashes($t, '%_\\').'%';
            $query->where(fn ($q) => $q->whereRaw('LOWER(title) LIKE ?', [$like])->orWhereRaw('LOWER(COALESCE(excerpt, \'\')) LIKE ?', [$like])->orWhereRaw('LOWER(COALESCE(body, \'\')) LIKE ?', [$like]));
        }

        return $query->orderByDesc('published_at')->limit(40)->get()->map(function ($p) use ($school, $terms) {
            $text = trim(($p->excerpt ? Prose::plain($p->excerpt).' ' : '').Prose::plain($p->body));

            return [
                'kind' => $p->categoryLabel(), 'title' => $p->title, 'url' => SiteRenderer::postUrl($school, $p), 'image' => SiteRenderer::image($p->cover_image),
                'snippet' => self::snippet($text, $terms), 'score' => (self::match($terms, $p->title, '') !== null ? 6 : 2),
            ];
        });
    }

    private static function albums(School $school, array $terms): Collection
    {
        return SchoolSiteAlbum::withoutGlobalScopes()->where('school_id', $school->id)->where('is_published', true)->whereNull('deleted_at')->get()
            ->map(function ($a) use ($school, $terms) {
                $found = self::match($terms, $a->title, (string) $a->description);

                return $found === null ? null : [
                    'kind' => 'Photo album', 'title' => $a->title, 'url' => SiteRenderer::galleryUrl($school, $a), 'image' => SiteRenderer::image($a->cover_path),
                    'snippet' => self::snippet((string) $a->description, $terms), 'score' => $found,
                ];
            })->filter()->values();
    }

    /** All the words of a page's sections, in one line */
    public static function words(mixed $blocks): string
    {
        $out = [];
        $walk = function ($node) use (&$walk, &$out) {
            if (! is_array($node) || ! empty($node['hidden'])) {
                return;
            }
            foreach ($node as $key => $value) {
                if (is_array($value)) {
                    $walk($value);
                } elseif (is_string($value) && in_array($key, self::TEXT_KEYS, true) && trim($value) !== '') {
                    $out[] = Prose::plain($value);
                }
            }
        };
        $walk($blocks);

        return implode(' ', $out);
    }

    /** null when a word is missing; else a score (a word in the title counts more) */
    private static function match(array $terms, string $title, string $text): ?int
    {
        $title = mb_strtolower($title);
        $all = $title.' '.mb_strtolower($text);
        $score = 0;
        foreach ($terms as $t) {
            if (! str_contains($all, $t)) {
                return null;
            }
            $score += str_contains($title, $t) ? 8 : 2;
        }

        return $score;
    }

    /** A short piece of text around the first word found, with the words marked. Everything is escaped. */
    public static function snippet(string $text, array $terms): HtmlString
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        if ($text === '') {
            return new HtmlString('');
        }
        $lower = mb_strtolower($text);
        $at = null;
        foreach ($terms as $t) {
            $p = mb_strpos($lower, $t);
            if ($p !== false && ($at === null || $p < $at)) {
                $at = $p;
            }
        }
        $start = max(0, ($at ?? 0) - 70);
        $piece = mb_substr($text, $start, 190);
        $piece = ($start > 0 ? '…' : '').$piece.($start + 190 < mb_strlen($text) ? '…' : '');
        $html = e($piece);
        foreach ($terms as $t) {
            $html = preg_replace('/('.preg_quote(e($t), '/').')/iu', '<mark>$1</mark>', $html) ?? $html;
        }

        return new HtmlString($html);
    }
}
