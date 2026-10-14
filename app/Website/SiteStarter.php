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
            ['enabled' => true, 'theme' => 'heritage', 'tagline' => null],
        );

        if (! SchoolSitePage::withoutGlobalScopes()->where('school_id', $school->id)->exists()) {
            foreach (self::pages($school) as $position => [$attrs, $blocks]) {
                SchoolSitePage::withoutGlobalScopes()->create($attrs + [
                    'school_id' => $school->id, 'is_published' => true, 'position' => $position,
                    'blocks' => Blocks::clean($blocks, $school->id),
                ]);
            }
        }

        return $site;
    }

    /**
     * Gives the newer starter text to pages nobody has edited yet (a page that was never saved after it was made).
     * Pages a school has changed are left alone. Returns how many pages were refreshed.
     */
    public static function refreshUntouched(School $school): int
    {
        $count = 0;
        $fresh = [];
        foreach (self::pages($school) as $position => [$attrs, $blocks]) {
            $fresh[$attrs['slug']] = [$attrs, $blocks];
        }

        $all = SchoolSitePage::withoutGlobalScopes()->where('school_id', $school->id)->get();
        $edited = fn (SchoolSitePage $p) => $p->updated_at && $p->created_at && $p->updated_at->gt($p->created_at->copy()->addSeconds(5));
        // a school that has changed, added or removed any page keeps its own set of pages
        $wholeSetUntouched = $all->isNotEmpty() && ! $all->contains($edited) && $all->pluck('slug')->diff(array_keys($fresh))->isEmpty();

        foreach ($all->whereIn('slug', array_keys($fresh)) as $page) {
            if ($edited($page)) {
                continue;
            }
            $page->timestamps = false;
            $page->title = $fresh[$page->slug][0]['title'];
            if ($wholeSetUntouched) {
                $page->position = array_search($page->slug, array_keys($fresh));
            }
            $page->blocks = Blocks::clean($fresh[$page->slug][1], $school->id);
            $page->saveQuietly();
            $count++;
        }

        if ($wholeSetUntouched) {
            foreach (array_diff(array_keys($fresh), $all->pluck('slug')->all()) as $slug) {
                [$attrs, $blocks] = $fresh[$slug];
                $position = array_search($slug, array_keys($fresh));
                SchoolSitePage::withoutGlobalScopes()->create($attrs + ['school_id' => $school->id, 'is_published' => true, 'position' => $position, 'blocks' => Blocks::clean($blocks, $school->id)]);
                $count++;
            }
        }

        return $count;
    }

    /** @return array<int, array{0: array, 1: array}> */
    private static function pages(School $school): array
    {
        $n = $school->name;

        return [
            [['slug' => 'home', 'title' => 'Home', 'is_home' => true], [
                ['type' => 'hero', 'eyebrow' => 'Admission is open', 'heading' => 'Where every child is known, challenged and cared for', 'subheading' => $n.' gives your child a strong start: good teaching, a safe campus and a community that cheers every pupil on.', 'button' => 'apply', 'button2' => 'contact'],
                ['type' => 'actions', 'items' => [
                    ['icon' => 'clipboard', 'title' => 'Apply online', 'body' => 'A short form, about two minutes.', 'link' => 'apply'],
                    ['icon' => 'users', 'title' => 'Parent, student and staff sign in', 'body' => 'Results, fees and notices in one place.', 'link' => 'login'],
                    ['icon' => 'chat', 'title' => 'Talk to us', 'body' => 'Visit the campus or ask a question.', 'link' => 'contact'],
                ]],
                ['type' => 'text', 'eyebrow' => 'Welcome', 'heading' => 'A school that knows your child', 'body' => "At {$n} we believe every child can do well when they feel safe, known and challenged. Our teachers take time to understand how each pupil learns, so that no child is left behind and no child is held back.\n\n- Small, friendly classes\n- Qualified, caring teachers\n- Clear termly report cards you can read on your phone\n- Close contact between school and home"],
                ['type' => 'features', 'eyebrow' => 'Why families choose us', 'heading' => 'Everything a good school should offer', 'intro' => 'Six reasons parents trust us with their children.', 'items' => [
                    ['icon' => 'book', 'title' => 'Strong academics', 'body' => 'A full curriculum taught well, with regular tests and honest feedback.'],
                    ['icon' => 'heart', 'title' => 'Caring teachers', 'body' => 'Staff who know every child by name and celebrate their progress.'],
                    ['icon' => 'shield', 'title' => 'Safe and secure', 'body' => 'A protected campus, trusted staff and careful pick-up routines.'],
                    ['icon' => 'laptop', 'title' => 'Modern learning', 'body' => 'Computer skills and digital tools used to make lessons clearer.'],
                    ['icon' => 'trophy', 'title' => 'Sports and clubs', 'body' => 'Houses, clubs and competitions that build confidence and teamwork.'],
                    ['icon' => 'users', 'title' => 'Parents as partners', 'body' => 'Results, fees and notices on your phone, and a school that listens.'],
                ]],
                ['type' => 'stats', 'heading' => '', 'items' => [
                    ['number' => '3', 'label' => 'Terms every year'],
                    ['number' => '1:20', 'label' => 'Teacher to pupil care'],
                    ['number' => '100%', 'label' => 'Report cards online'],
                    ['number' => '24/7', 'label' => 'Parent portal access'],
                ]],
                ['type' => 'steps', 'eyebrow' => 'Joining us', 'heading' => 'Joining is simple', 'intro' => 'Four easy steps from your first question to your child\'s first day.', 'items' => [
                    ['title' => 'Apply online', 'body' => 'Fill in the short form with your child\'s details.'],
                    ['title' => 'Meet us', 'body' => 'Visit the school, and your child may sit a short assessment.'],
                    ['title' => 'Receive an offer', 'body' => 'We send you a place offer with the fees and what to bring.'],
                    ['title' => 'Start learning', 'body' => 'Complete enrolment and welcome your child on the first day.'],
                ]],
                ['type' => 'gallery', 'eyebrow' => 'Gallery', 'heading' => 'Life at '.$n, 'count' => 8],
                ['type' => 'news', 'eyebrow' => 'From the school', 'heading' => 'News and events', 'count' => 3],
                ['type' => 'apply', 'heading' => 'Give your child the best start', 'body' => 'Admission is open at '.$n.'. Apply in two minutes and our office will contact you.', 'button' => 'apply', 'button2' => 'contact'],
            ]],
            [['slug' => 'about', 'title' => 'About us'], [
                ['type' => 'text', 'eyebrow' => 'Our story', 'heading' => 'About '.$n, 'body' => "{$n} is a school built on a simple idea: children learn best when they are happy, safe and expected to do well.\n\nWe work closely with parents, because a child does best when home and school pull in the same direction. Every term, parents receive a clear report card, and the school office is always ready to talk."],
                ['type' => 'features', 'eyebrow' => 'What we stand for', 'heading' => 'Our values', 'items' => [
                    ['icon' => 'heart', 'title' => 'Care', 'body' => 'We treat every child with kindness and respect.'],
                    ['icon' => 'star', 'title' => 'Excellence', 'body' => 'We aim high and help every pupil reach their best.'],
                    ['icon' => 'shield', 'title' => 'Integrity', 'body' => 'We teach honesty, discipline and responsibility.'],
                ]],
                ['type' => 'text', 'heading' => 'Our mission', 'body' => "To give every child a strong education, a good character and the confidence to succeed, in a school families can trust.\n\nOur vision is to be the school parents choose first, and graduates are proud of."],
            ]],
            [['slug' => 'academics', 'title' => 'Academics'], [
                ['type' => 'text', 'eyebrow' => 'Learning', 'heading' => 'Teaching that builds understanding', 'body' => "Our lessons follow a clear scheme of work for every subject and term. Pupils are tested regularly, and we use continuous assessment (marks from class work and tests through the term, not only the final exam) so that you can see progress early and not be surprised at the end.\n\n- Core subjects taught by qualified teachers\n- Homework that practises what was taught\n- Computer and digital skills\n- Creative arts, sports and clubs"],
                ['type' => 'features', 'eyebrow' => 'Stages', 'heading' => 'Learning for every age', 'items' => [
                    ['icon' => 'sprout', 'title' => 'Early years', 'body' => 'Play, phonics, number work and social skills in a warm setting.'],
                    ['icon' => 'book', 'title' => 'Primary', 'body' => 'Strong reading, writing and mathematics with science and the arts.'],
                    ['icon' => 'laptop', 'title' => 'Junior secondary', 'body' => 'Broader subjects, study habits and digital skills.'],
                    ['icon' => 'cap', 'title' => 'Senior secondary', 'body' => 'Focused preparation for national exams and the next step.'],
                ]],
                ['type' => 'apply', 'heading' => 'See our classrooms for yourself', 'body' => 'Book a visit or apply online.', 'button' => 'apply', 'button2' => 'contact'],
            ]],
            [['slug' => 'admissions', 'title' => 'Admissions'], [
                ['type' => 'steps', 'eyebrow' => 'How to join', 'heading' => 'How to join '.$n, 'intro' => 'Here is what happens from your first inquiry to your child\'s first day.', 'items' => [
                    ['title' => 'Apply online', 'body' => 'Send the short application form. It takes about two minutes.'],
                    ['title' => 'Entrance exam or interview', 'body' => 'We meet your child to understand where they are and how we can help.'],
                    ['title' => 'Offer of a place', 'body' => 'If there is a place, we send an offer with fees and what to bring.'],
                    ['title' => 'Enrolment', 'body' => 'Pay the fees, complete your child\'s records and join the class.'],
                ]],
                ['type' => 'faq', 'heading' => 'Questions parents ask', 'items' => [
                    ['question' => 'How do I apply?', 'answer' => 'Use the Apply online button. Fill in a short form and our office will contact you.'],
                    ['question' => 'Which classes can I apply for?', 'answer' => 'The form lists the classes we admit into. If you are not sure which fits your child, ask the office.'],
                    ['question' => 'When does the new term start?', 'answer' => 'Please contact the school office for the exact dates of the coming term.'],
                    ['question' => 'How are school fees paid?', 'answer' => 'Fees are charged each term. After enrolment you can see what is owed and pay online from the parent portal, in full or in parts where the school allows it.'],
                    ['question' => 'How will I follow my child\'s progress?', 'answer' => 'Sign in to the parent portal to see attendance, results and notices, and to download the report card each term.'],
                ]],
                ['type' => 'apply', 'heading' => 'Ready to apply?', 'body' => 'It takes about two minutes.', 'button' => 'apply', 'button2' => 'contact'],
            ]],
            [['slug' => 'contact', 'title' => 'Contact'], [
                ['type' => 'contact', 'heading' => 'Find us'],
                ['type' => 'apply', 'heading' => 'Want to visit?', 'body' => 'We would love to show you around. Call the office or send an inquiry.', 'button' => 'apply', 'button2' => 'none'],
            ]],
        ];
    }
}
