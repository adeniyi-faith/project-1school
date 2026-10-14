<?php

namespace App\Website;

/**
 * The sections a website page is made of. Each section type says what its boxes are called, what it starts with
 * and which view draws it. Adding a new kind of section means one entry here and one view in
 * resources/views/website/blocks.
 */
class Blocks
{
    public const MAX_BLOCKS = 30;
    /** Page addresses the website uses for itself */
    public const RESERVED_SLUGS = ['news', 'sitemap', 'feed', 'gallery', 'apply', 'login', 'sign-in', 'admin'];

    /** The colour band a section sits on */
    public const BANDS = ['plain' => 'Plain', 'soft' => 'Soft tint', 'dark' => 'Deep colour'];

    public const BUTTONS = ['none' => 'No button', 'apply' => 'Apply online', 'login' => 'Sign in', 'contact' => 'Contact us'];

    /** Small pictures a card can start with (drawn by website/partials/icon.blade.php) */
    public const ICONS = [
        'none' => 'No icon', 'book' => 'Open book', 'heart' => 'Heart', 'shield' => 'Shield', 'star' => 'Star', 'users' => 'People',
        'laptop' => 'Computer', 'trophy' => 'Trophy', 'cap' => 'Graduation cap', 'sprout' => 'Growing plant', 'palette' => 'Art',
        'chat' => 'Chat', 'clipboard' => 'Checklist', 'user' => 'Person',
    ];

    /** field kinds: text, textarea, select (options), number, image (an uploaded picture), list (repeating rows of fields) */
    public static function types(): array
    {
        $heading = fn (string $label = 'Heading') => ['key' => 'heading', 'label' => $label, 'kind' => 'text', 'max' => 120];

        return [
            'hero' => [
                'label' => 'Top banner', 'help' => 'The big first impression: a large heading over a photo, with buttons.',
                'fields' => [
                    ['key' => 'eyebrow', 'label' => 'Small line above the heading', 'kind' => 'text', 'max' => 80],
                    $heading('Big heading'),
                    ['key' => 'lead', 'label' => 'Short line in italics (only the Statement theme shows it)', 'kind' => 'text', 'max' => 160],
                    ['key' => 'subheading', 'label' => 'One line, or a short paragraph, under it', 'kind' => 'textarea', 'max' => 400],
                    ['key' => 'image', 'label' => 'Photo', 'kind' => 'image', 'hint' => 'A wide, bright photo works best. Without one, your colours are used.'],
                    ['key' => 'foot', 'label' => 'Line at the bottom of the banner (only the Statement theme shows it)', 'kind' => 'text', 'max' => 80, 'hint' => 'For example a motto. Shown next to a row of small dots.'],
                    ['key' => 'facts', 'label' => 'Details card (only the Statement theme shows it)', 'kind' => 'list', 'limit' => 4, 'noun' => 'detail', 'fields' => [
                        ['key' => 'label', 'label' => 'Label (for example Date)', 'kind' => 'text', 'max' => 30],
                        ['key' => 'value', 'label' => 'Value (for example Wednesday,|5 August 2026)', 'kind' => 'text', 'max' => 100, 'hint' => 'Type | where the line should break.'],
                    ]],
                    ['key' => 'button', 'label' => 'First button', 'kind' => 'select', 'options' => self::BUTTONS],
                    ['key' => 'button2', 'label' => 'Second button', 'kind' => 'select', 'options' => self::BUTTONS],
                ],
                'default' => ['eyebrow' => '', 'heading' => 'Welcome', 'lead' => '', 'subheading' => '', 'image' => '', 'foot' => '', 'facts' => [], 'button' => 'apply', 'button2' => 'none'],
            ],
            'text' => [
                'label' => 'Text and picture', 'help' => 'A heading, some paragraphs, and an optional picture beside them.',
                'fields' => [
                    ['key' => 'eyebrow', 'label' => 'Small label above the heading', 'kind' => 'text', 'max' => 40],
                    $heading(),
                    ['key' => 'body', 'label' => 'Text', 'kind' => 'textarea', 'max' => 6000, 'format' => true, 'hint' => 'Leave a blank line between paragraphs. Use the buttons above the box for headings, lists, bold and links.'],
                    ['key' => 'image', 'label' => 'Picture (optional)', 'kind' => 'image'],
                    ['key' => 'side', 'label' => 'Picture sits on the', 'kind' => 'select', 'options' => ['right' => 'Right', 'left' => 'Left']],
                    ['key' => 'align', 'label' => 'When there is no picture, line up the text', 'kind' => 'select', 'options' => ['left' => 'To the left', 'center' => 'In the centre']],
                    ['key' => 'band', 'label' => 'Background', 'kind' => 'select', 'options' => self::BANDS],
                ],
                'default' => ['eyebrow' => '', 'heading' => '', 'body' => '', 'image' => '', 'side' => 'right', 'align' => 'left', 'band' => 'plain'],
            ],
            'features' => [
                'label' => 'Cards', 'help' => 'Up to 8 cards, each with a picture, a title and a few words. Good for programmes, facilities or reasons to choose you.',
                'fields' => [
                    ['key' => 'eyebrow', 'label' => 'Small label above the heading', 'kind' => 'text', 'max' => 40],
                    $heading(),
                    ['key' => 'intro', 'label' => 'A sentence under the heading', 'kind' => 'text', 'max' => 300],
                    ['key' => 'style', 'label' => 'Look', 'kind' => 'select', 'options' => ['cards' => 'Cards with pictures or icons', 'pillars' => 'Numbered cards (Pillar I, Pillar II ...)', 'panels' => 'Plain panels with a coloured bar on top (for example Vision and Mission)']],
                    ['key' => 'tag', 'label' => 'Word before each number (numbered cards only)', 'kind' => 'text', 'max' => 20],
                    ['key' => 'band', 'label' => 'Background', 'kind' => 'select', 'options' => self::BANDS],
                    ['key' => 'items', 'label' => 'Cards', 'kind' => 'list', 'limit' => 8, 'noun' => 'card', 'fields' => [
                        ['key' => 'icon', 'label' => 'Small picture', 'kind' => 'select', 'options' => self::ICONS],
                        ['key' => 'title', 'label' => 'Title', 'kind' => 'text', 'max' => 80],
                        ['key' => 'body', 'label' => 'A few words about it', 'kind' => 'textarea', 'max' => 300],
                        ['key' => 'image', 'label' => 'Picture (optional)', 'kind' => 'image'],
                    ]],
                ],
                'default' => ['eyebrow' => '', 'heading' => 'Why families choose us', 'intro' => '', 'style' => 'cards', 'tag' => 'Pillar', 'band' => 'soft', 'items' => []],
            ],
            'actions' => [
                'label' => 'Quick action cards', 'help' => 'Three big cards that sit over the bottom of the top banner, for example Apply online, Sign in and Talk to us.',
                'fields' => [
                    ['key' => 'items', 'label' => 'Cards', 'kind' => 'list', 'limit' => 4, 'noun' => 'card', 'fields' => [
                        ['key' => 'icon', 'label' => 'Small picture', 'kind' => 'select', 'options' => self::ICONS],
                        ['key' => 'title', 'label' => 'Title', 'kind' => 'text', 'max' => 60],
                        ['key' => 'body', 'label' => 'A few words', 'kind' => 'text', 'max' => 120],
                        ['key' => 'link', 'label' => 'Where it goes', 'kind' => 'select', 'options' => self::BUTTONS],
                    ]],
                ],
                'default' => ['items' => []],
            ],
            'steps' => [
                'label' => 'Steps', 'help' => 'Numbered steps, for example how to join the school.',
                'fields' => [
                    ['key' => 'eyebrow', 'label' => 'Small label above the heading', 'kind' => 'text', 'max' => 40],
                    $heading(),
                    ['key' => 'intro', 'label' => 'A sentence under the heading', 'kind' => 'text', 'max' => 300],
                    ['key' => 'band', 'label' => 'Background', 'kind' => 'select', 'options' => self::BANDS],
                    ['key' => 'items', 'label' => 'Steps', 'kind' => 'list', 'limit' => 8, 'noun' => 'step', 'fields' => [
                        ['key' => 'title', 'label' => 'Title', 'kind' => 'text', 'max' => 80],
                        ['key' => 'body', 'label' => 'A few words about it', 'kind' => 'textarea', 'max' => 300],
                    ]],
                ],
                'default' => ['eyebrow' => '', 'heading' => 'How it works', 'intro' => '', 'band' => 'plain', 'items' => []],
            ],
            'stats' => [
                'label' => 'Big numbers', 'help' => 'Up to 4 proud facts, for example 25 Years or 1,200 Students.',
                'fields' => [
                    $heading(),
                    ['key' => 'items', 'label' => 'Numbers', 'kind' => 'list', 'limit' => 4, 'noun' => 'number', 'fields' => [
                        ['key' => 'number', 'label' => 'The number', 'kind' => 'text', 'max' => 14],
                        ['key' => 'label', 'label' => 'What it counts', 'kind' => 'text', 'max' => 40],
                    ]],
                ],
                'default' => ['heading' => '', 'items' => []],
            ],
            'quote' => [
                'label' => 'Quote', 'help' => 'A pulled-out sentence in large italics with a coloured bar beside it, and who said it.',
                'fields' => [
                    ['key' => 'text', 'label' => 'The quote', 'kind' => 'textarea', 'max' => 400],
                    ['key' => 'who', 'label' => 'Who said it', 'kind' => 'text', 'max' => 100],
                ],
                'default' => ['text' => '', 'who' => ''],
            ],
            'profile' => [
                'label' => 'Person profile', 'help' => 'A photo with a coloured panel under it: a name, a role and a few paragraphs. Good for the head teacher, founder or convener.',
                'fields' => [
                    ['key' => 'eyebrow', 'label' => 'Small label (for example The Head Teacher)', 'kind' => 'text', 'max' => 40],
                    ['key' => 'name', 'label' => 'Name', 'kind' => 'text', 'max' => 80],
                    ['key' => 'role', 'label' => 'Role', 'kind' => 'text', 'max' => 60],
                    ['key' => 'body', 'label' => 'About them', 'kind' => 'textarea', 'max' => 3000, 'format' => true],
                    ['key' => 'image', 'label' => 'Photo', 'kind' => 'image', 'hint' => 'A portrait photo works best.'],
                ],
                'default' => ['eyebrow' => '', 'name' => '', 'role' => '', 'body' => '', 'image' => ''],
            ],
            'news' => [
                'label' => 'Latest news', 'help' => 'Shows your newest posts from News and events. It updates by itself. Before you write any posts, it shows announcements meant for everyone.',
                'fields' => [
                    $heading(),
                    ['key' => 'category', 'label' => 'Which posts', 'kind' => 'select', 'options' => ['all' => 'All kinds'] + \App\Models\SchoolSitePost::CATEGORIES],
                    ['key' => 'count', 'label' => 'How many to show (1 to 10)', 'kind' => 'number', 'min' => 1, 'max' => 10],
                ],
                'default' => ['heading' => 'Latest news', 'category' => 'all', 'count' => 3],
            ],
            'gallery' => [
                'label' => 'Photo gallery', 'help' => 'Shows your newest photos from the Gallery. It updates by itself and stays hidden until you have added photos.',
                'fields' => [
                    ['key' => 'eyebrow', 'label' => 'Small label above the heading', 'kind' => 'text', 'max' => 40],
                    $heading(),
                    ['key' => 'count', 'label' => 'How many photos (4 to 12)', 'kind' => 'number', 'min' => 4, 'max' => 12],
                ],
                'default' => ['eyebrow' => '', 'heading' => 'Life at our school', 'count' => 8],
            ],
            'apply' => [
                'label' => 'Call to action', 'help' => 'A bold coloured band that sends visitors to apply or get in touch. Applications land in Admission Inquiries.',
                'fields' => [
                    $heading(),
                    ['key' => 'body', 'label' => 'Text', 'kind' => 'textarea', 'max' => 600],
                    ['key' => 'button', 'label' => 'First button', 'kind' => 'select', 'options' => self::BUTTONS],
                    ['key' => 'button2', 'label' => 'Second button', 'kind' => 'select', 'options' => self::BUTTONS],
                ],
                'default' => ['heading' => 'Join our school', 'body' => 'Admission is open. Fill in a short form and we will contact you.', 'button' => 'apply', 'button2' => 'contact'],
            ],
            'faq' => [
                'label' => 'Questions and answers', 'help' => 'Answer what parents often ask. Google can show these answers in search results.',
                'fields' => [
                    $heading(),
                    ['key' => 'items', 'label' => 'Questions', 'kind' => 'list', 'limit' => 12, 'noun' => 'question', 'fields' => [
                        ['key' => 'question', 'label' => 'Question', 'kind' => 'text', 'max' => 160],
                        ['key' => 'answer', 'label' => 'Answer', 'kind' => 'textarea', 'max' => 800],
                    ]],
                ],
                'default' => ['heading' => 'Questions parents ask', 'items' => []],
            ],
            'contact' => [
                'label' => 'Contact details', 'help' => 'Your address, phone and email, taken from your school profile.',
                'fields' => [$heading()],
                'default' => ['heading' => 'Find us'],
            ],
        ];
    }

    public static function catalogue(): array
    {
        return collect(self::types())->map(fn ($t, $key) => ['type' => $key] + $t)->values()->all();
    }

    public static function fresh(string $type): ?array
    {
        $t = self::types()[$type] ?? null;

        return $t ? ['type' => $type, 'hidden' => false] + $t['default'] : null;
    }

    /**
     * Keep only known section types and known fields, trimmed to their limits, so nothing odd can be stored.
     * Pictures must be ones this school uploaded to its own website folder.
     */
    public static function clean(mixed $blocks, ?int $schoolId = null): array
    {
        $types = self::types();
        $out = [];

        foreach (array_slice(is_array($blocks) ? $blocks : [], 0, self::MAX_BLOCKS) as $block) {
            $type = is_array($block) ? ($block['type'] ?? null) : null;
            if (! is_string($type) || ! isset($types[$type])) {
                continue;
            }
            $out[] = ['type' => $type, 'hidden' => filter_var($block['hidden'] ?? false, FILTER_VALIDATE_BOOLEAN)]
                + self::cleanFields($types[$type]['fields'], $block, $schoolId);
        }

        return $out;
    }

    public static function imagePath(mixed $value, ?int $schoolId): string
    {
        $ok = is_string($value) && $schoolId && preg_match('#^schools/'.$schoolId.'/website/[A-Za-z0-9._-]+$#', $value);

        return $ok ? $value : '';
    }

    private static function cleanFields(array $fields, array $source, ?int $schoolId): array
    {
        $clean = [];

        foreach ($fields as $f) {
            $value = $source[$f['key']] ?? null;

            $clean[$f['key']] = match ($f['kind']) {
                'select' => array_key_exists((string) $value, $f['options']) ? (string) $value : array_key_first($f['options']),
                'number' => max($f['min'], min($f['max'], (int) $value ?: $f['min'])),
                'image' => self::imagePath($value, $schoolId),
                'list' => collect(is_array($value) ? array_slice($value, 0, $f['limit']) : [])
                    ->filter(fn ($row) => is_array($row))
                    ->map(fn ($row) => self::cleanFields($f['fields'], $row, $schoolId))
                    ->filter(fn ($row) => collect($row)->contains(fn ($v) => $v !== ''))
                    ->values()->all(),
                default => mb_substr(trim(is_scalar($value) ? (string) $value : ''), 0, $f['max']),
            };
        }

        return $clean;
    }
}
