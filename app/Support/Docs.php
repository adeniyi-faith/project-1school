<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * The guides. Each page is a Markdown file in resources/docs/{slug}.md; this list decides the order and the group.
 * To add a guide, write the file and add one line here.
 */
class Docs
{
    public const PAGES = [
        'For schools' => [
            'getting-started' => ['Getting started', 'Set up a new school in a few steps, in the right order.'],
            'people-and-logins' => ['Staff, teachers and logins', 'One person, one record: how staff and logins fit together.'],
            'admissions' => ['Admissions, step by step', 'From the first inquiry to a pupil with a login and fees.'],
            'website' => ['Your school website', 'Themes, pages, live sections, the message form and WhatsApp.'],
        ],
        'For developers' => [
            'dev-overview' => ['Developer overview', 'Everything you can build on, in one page.'],
            'dev-site-data' => ['Site data (JSON)', 'Public links for pages, fees, term dates, team, events and news.'],
            'dev-webhooks' => ['Webhooks', 'Get told the moment an inquiry, enrolment or payment happens.'],
            'dev-api' => ['Read-only key', 'Read your inquiries from your own scripts.'],
            'dev-themes-blocks' => ['Themes and sections', 'Add a new look or a new section with a command and a folder.'],
            'dev-css-analytics' => ['Extra CSS and analytics', 'What the safe CSS box allows, and how to add analytics.'],
            'dev-export-import' => ['Export and import', 'Back up a site or copy it to another school.'],
        ],
    ];

    /** @return array<string, list<array{slug: string, title: string, summary: string}>> */
    public static function groups(): array
    {
        $out = [];
        foreach (self::PAGES as $group => $pages) {
            foreach ($pages as $slug => [$title, $summary]) {
                $out[$group][] = ['slug' => $slug, 'title' => $title, 'summary' => $summary];
            }
        }

        return $out;
    }

    public static function find(string $slug): ?array
    {
        foreach (self::groups() as $group => $pages) {
            foreach ($pages as $page) {
                if ($page['slug'] === $slug && is_file(resource_path("docs/{$slug}.md"))) {
                    $words = str_word_count(strip_tags((string) file_get_contents(resource_path("docs/{$slug}.md"))));

                    return $page + ['group' => $group, 'minutes' => max(1, (int) ceil($words / 200))];
                }
            }
        }

        return null;
    }

    /** The page as safe HTML: raw HTML in the file is dropped and links must be ordinary web or page links */
    public static function render(string $slug): string
    {
        return (string) Str::markdown((string) file_get_contents(resource_path("docs/{$slug}.md")), ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    /** @return array{prev: ?array, next: ?array} */
    public static function neighbours(string $slug): array
    {
        $flat = array_merge(...array_values(self::groups()));
        $i = array_search($slug, array_column($flat, 'slug'), true);

        return ['prev' => $flat[$i - 1] ?? null, 'next' => $flat[$i + 1] ?? null];
    }
}
