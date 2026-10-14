<?php

namespace App\Website;

use App\Models\School;
use App\Models\SchoolSite;
use App\Models\SchoolSitePage;

/** Gives a school a website to start from, written from what we already know about it. Safe to call again */
class SiteStarter
{
    public static function ensure(School $school): SchoolSite
    {
        $site = SchoolSite::withoutGlobalScopes()->firstOrCreate(
            ['school_id' => $school->id],
            ['enabled' => false, 'theme' => 'classic', 'tagline' => null],
        );

        if (! SchoolSitePage::withoutGlobalScopes()->where('school_id', $school->id)->exists()) {
            $page = fn (array $attrs, array $blocks) => SchoolSitePage::withoutGlobalScopes()->create($attrs + ['school_id' => $school->id, 'blocks' => Blocks::clean($blocks)]);

            $page(['slug' => 'home', 'title' => 'Home', 'is_home' => true, 'is_published' => true, 'position' => 0], [
                ['type' => 'hero', 'heading' => 'Welcome to '.$school->name, 'subheading' => 'A place where every child can learn and grow.', 'button' => 'apply'],
                ['type' => 'features', 'heading' => 'Why families choose us', 'items' => [
                    ['title' => 'Caring teachers', 'body' => 'Small classes and staff who know every child by name.'],
                    ['title' => 'Strong results', 'body' => 'Steady progress shown in clear termly report cards.'],
                    ['title' => 'Safe and friendly', 'body' => 'A calm school where children feel they belong.'],
                ]],
                ['type' => 'news', 'heading' => 'Latest news', 'count' => 3],
                ['type' => 'apply', 'heading' => 'Join our school', 'body' => 'Admission is open. Fill in a short form and we will contact you.'],
            ]);

            $page(['slug' => 'about', 'title' => 'About us', 'is_published' => true, 'position' => 1], [
                ['type' => 'text', 'heading' => 'About '.$school->name, 'body' => "Tell families who you are: when the school started, what you believe in and what makes it special.\n\nEdit this page from your dashboard under Website."],
            ]);

            $page(['slug' => 'contact', 'title' => 'Contact', 'is_published' => true, 'position' => 2], [
                ['type' => 'contact', 'heading' => 'Find us'],
            ]);
        }

        return $site;
    }
}
