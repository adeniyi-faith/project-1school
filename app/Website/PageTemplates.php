<?php

namespace App\Website;

use App\Models\School;

/** Ready-made pages a school can start from, so a new page is never a blank screen */
class PageTemplates
{
    public static function all(): array
    {
        return [
            'blank' => ['label' => 'Blank page', 'help' => 'Start with nothing and add your own sections.'],
            'about' => ['label' => 'About us', 'help' => 'Your story, what you believe in, and key numbers.'],
            'academics' => ['label' => 'Academics', 'help' => 'Your sections (nursery, primary, secondary) as cards with pictures.'],
            'admissions' => ['label' => 'Admissions', 'help' => 'How to join, common questions and an Apply button.'],
            'fees' => ['label' => 'Fees', 'help' => 'How fees work and how to pay, with questions and answers.'],
            'facilities' => ['label' => 'Facilities', 'help' => 'Labs, library, sports and boarding as picture cards.'],
            'contact' => ['label' => 'Contact', 'help' => 'Your address, phone, email and a call to visit.'],
        ];
    }

    public static function options(): array
    {
        return collect(self::all())->map(fn ($t, $key) => ['key' => $key, 'label' => $t['label'], 'help' => $t['help']])->values()->all();
    }

    /** The sections a new page starts with */
    public static function blocks(string $template, School $school): array
    {
        $n = $school->name;

        $blocks = match ($template) {
            'about' => [
                ['type' => 'hero', 'heading' => 'About '.$n, 'subheading' => 'Who we are and what we stand for.', 'button' => 'none', 'button2' => 'none'],
                ['type' => 'text', 'heading' => 'Our story', 'body' => "Tell the story of how the school began, who founded it and why.\n\n## Our mission\nOne or two sentences about what you want every child to become."],
                ['type' => 'stats', 'heading' => '', 'items' => [['number' => '20+', 'label' => 'Years'], ['number' => '500', 'label' => 'Students'], ['number' => '40', 'label' => 'Teachers']]],
                ['type' => 'features', 'heading' => 'Our values', 'items' => [['title' => 'Excellence', 'body' => 'We expect the best and help every child reach it.'], ['title' => 'Integrity', 'body' => 'We teach children to be honest and fair.'], ['title' => 'Care', 'body' => 'Every child is known, safe and supported.']]],
            ],
            'academics' => [
                ['type' => 'hero', 'heading' => 'Academics', 'subheading' => 'A clear path from the early years to graduation.', 'button' => 'apply', 'button2' => 'none'],
                ['type' => 'features', 'heading' => 'Our schools', 'intro' => 'Each stage builds on the one before.', 'items' => [['title' => 'Nursery', 'body' => 'Learning through play, songs and stories.'], ['title' => 'Primary', 'body' => 'Strong reading, writing and number skills.'], ['title' => 'Junior Secondary', 'body' => 'A broad curriculum and a love of learning.'], ['title' => 'Senior Secondary', 'body' => 'Focused preparation for WAEC, NECO and university.']]],
                ['type' => 'text', 'heading' => 'How we teach', 'body' => "Explain your approach: class sizes, continuous assessment, report cards, extra lessons."],
            ],
            'admissions' => [
                ['type' => 'text', 'heading' => 'How to join '.$n, 'body' => "1. Fill in the short online form.\n2. Your child sits our entrance test or interview.\n3. We send you an offer.\n4. Accept and pay to secure the place."],
                ['type' => 'faq', 'heading' => 'Questions parents ask', 'items' => [['question' => 'When can my child start?', 'answer' => 'We take new pupils at the start of every term, when places are free.'], ['question' => 'Is there an entrance exam?', 'answer' => 'Yes. It checks English and Mathematics so we can place your child well.']]],
                ['type' => 'apply', 'heading' => 'Ready to apply?', 'body' => 'It takes about two minutes.', 'button' => 'apply', 'button2' => 'contact'],
            ],
            'fees' => [
                ['type' => 'text', 'heading' => 'School fees', 'body' => "Fees are paid each term. They cover tuition, books and most activities.\n\nParents can pay online from the parent portal, in full or in parts."],
                ['type' => 'faq', 'heading' => 'Questions about fees', 'items' => [['question' => 'Can I pay in instalments?', 'answer' => 'Yes. Ask the bursary about a payment plan that suits your family.'], ['question' => 'How do I pay online?', 'answer' => 'Sign in to the parent portal and press Pay next to the invoice.']]],
                ['type' => 'apply', 'heading' => 'Questions about fees?', 'body' => 'Our bursary is happy to help.', 'button' => 'contact', 'button2' => 'none'],
            ],
            'facilities' => [
                ['type' => 'hero', 'heading' => 'Our facilities', 'subheading' => 'Spaces where children learn, play and grow.', 'button' => 'none', 'button2' => 'none'],
                ['type' => 'features', 'heading' => 'Around the school', 'items' => [['title' => 'Science laboratories', 'body' => 'Fully equipped for practical work.'], ['title' => 'Library', 'body' => 'Thousands of books and a quiet reading room.'], ['title' => 'Sports', 'body' => 'Football pitch, basketball court and athletics track.'], ['title' => 'ICT centre', 'body' => 'Computers for every class.']]],
            ],
            'contact' => [
                ['type' => 'contact', 'heading' => 'Get in touch'],
                ['type' => 'text', 'heading' => 'Visit us', 'body' => "Our office is open Monday to Friday, 8am to 4pm. You are welcome to visit and see the school.", 'align' => 'center'],
                ['type' => 'apply', 'heading' => 'Thinking about '.$n.'?', 'body' => 'Start with a short online form.', 'button' => 'apply', 'button2' => 'none'],
            ],
            default => [],
        };

        return Blocks::clean($blocks, $school->id);
    }
}
