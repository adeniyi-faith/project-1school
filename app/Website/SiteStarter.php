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
            ['enabled' => false, 'theme' => 'heritage', 'tagline' => null],
        );

        if (! SchoolSitePage::withoutGlobalScopes()->where('school_id', $school->id)->exists()) {
            $page = fn (array $attrs, array $blocks) => SchoolSitePage::withoutGlobalScopes()->create($attrs + ['school_id' => $school->id, 'blocks' => Blocks::clean($blocks, $school->id)]);

            $page(['slug' => 'home', 'title' => 'Home', 'is_home' => true, 'is_published' => true, 'position' => 0], [
                ['type' => 'hero', 'heading' => 'Welcome to '.$school->name, 'subheading' => 'A place where every child can learn and grow.', 'button' => 'apply', 'button2' => 'contact'],
                ['type' => 'text', 'heading' => 'A school that knows your child', 'body' => "Tell families who you are in a few warm sentences: what you believe in, and what a day at your school feels like.\n\nYou can change this text, and add a picture, from your dashboard under Website."],
                ['type' => 'features', 'heading' => 'Why families choose us', 'items' => [
                    ['title' => 'Caring teachers', 'body' => 'Small classes and staff who know every child by name.'],
                    ['title' => 'Strong results', 'body' => 'Steady progress shown in clear termly report cards.'],
                    ['title' => 'Safe and friendly', 'body' => 'A calm school where children feel they belong.'],
                ]],
                ['type' => 'news', 'heading' => 'Latest news', 'count' => 3],
                ['type' => 'apply', 'heading' => 'Thinking about '.$school->name.'?', 'body' => 'Admission is open. Fill in a short form and we will contact you.', 'button' => 'apply', 'button2' => 'contact'],
            ]);

            $page(['slug' => 'about', 'title' => 'About us', 'is_published' => true, 'position' => 1], [
                ['type' => 'text', 'heading' => 'About '.$school->name, 'body' => "Tell families who you are: when the school started, what you believe in and what makes it special.\n\nEdit this page from your dashboard under Website."],
            ]);

            $page(['slug' => 'admissions', 'title' => 'Admissions', 'is_published' => true, 'position' => 2], [
                ['type' => 'text', 'heading' => 'How to join '.$school->name, 'body' => "Explain the steps: inquiry, entrance exam or interview, offer and enrolment."],
                ['type' => 'faq', 'heading' => 'Questions parents ask', 'items' => [
                    ['question' => 'How do I apply?', 'answer' => 'Use the Apply online button. Fill in a short form and our office will contact you.'],
                    ['question' => 'When does the new term start?', 'answer' => 'Please contact the school office for the exact dates of the coming term.'],
                ]],
                ['type' => 'apply', 'heading' => 'Ready to apply?', 'body' => 'It takes about two minutes.', 'button' => 'apply', 'button2' => 'none'],
            ]);

            $page(['slug' => 'contact', 'title' => 'Contact', 'is_published' => true, 'position' => 3], [
                ['type' => 'contact', 'heading' => 'Find us'],
            ]);
        }

        return $site;
    }
}
