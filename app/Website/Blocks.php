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
    public const BUTTONS = ['none' => 'No button', 'apply' => 'Apply online', 'login' => 'Sign in', 'contact' => 'Contact us'];

    /** field kinds: text, textarea, select (options), number, image (an uploaded picture), list (repeating rows of fields) */
    public static function types(): array
    {
        $heading = fn (string $label = 'Heading') => ['key' => 'heading', 'label' => $label, 'kind' => 'text', 'max' => 120];

        return [
            'hero' => [
                'label' => 'Top banner', 'help' => 'The big first impression: a large heading over a photo, with buttons.',
                'fields' => [
                    $heading('Big heading'),
                    ['key' => 'subheading', 'label' => 'One line under it', 'kind' => 'text', 'max' => 220],
                    ['key' => 'image', 'label' => 'Photo', 'kind' => 'image', 'hint' => 'A wide, bright photo works best. Without one, your colours are used.'],
                    ['key' => 'button', 'label' => 'First button', 'kind' => 'select', 'options' => self::BUTTONS],
                    ['key' => 'button2', 'label' => 'Second button', 'kind' => 'select', 'options' => self::BUTTONS],
                ],
                'default' => ['heading' => 'Welcome', 'subheading' => '', 'image' => '', 'button' => 'apply', 'button2' => 'none'],
            ],
            'text' => [
                'label' => 'Text and picture', 'help' => 'A heading, some paragraphs, and an optional picture beside them.',
                'fields' => [
                    $heading(),
                    ['key' => 'body', 'label' => 'Text', 'kind' => 'textarea', 'max' => 4000, 'hint' => 'Leave a blank line between paragraphs.'],
                    ['key' => 'image', 'label' => 'Picture (optional)', 'kind' => 'image'],
                    ['key' => 'side', 'label' => 'Picture sits on the', 'kind' => 'select', 'options' => ['right' => 'Right', 'left' => 'Left']],
                    ['key' => 'align', 'label' => 'When there is no picture, line up the text', 'kind' => 'select', 'options' => ['left' => 'To the left', 'center' => 'In the centre']],
                ],
                'default' => ['heading' => '', 'body' => '', 'image' => '', 'side' => 'right', 'align' => 'left'],
            ],
            'features' => [
                'label' => 'Cards', 'help' => 'Up to 8 cards, each with a picture, a title and a few words. Good for programmes, facilities or reasons to choose you.',
                'fields' => [
                    $heading(),
                    ['key' => 'intro', 'label' => 'A sentence under the heading', 'kind' => 'text', 'max' => 300],
                    ['key' => 'items', 'label' => 'Cards', 'kind' => 'list', 'limit' => 8, 'noun' => 'card', 'fields' => [
                        ['key' => 'title', 'label' => 'Title', 'kind' => 'text', 'max' => 80],
                        ['key' => 'body', 'label' => 'A few words about it', 'kind' => 'textarea', 'max' => 300],
                        ['key' => 'image', 'label' => 'Picture (optional)', 'kind' => 'image'],
                    ]],
                ],
                'default' => ['heading' => 'Why families choose us', 'intro' => '', 'items' => []],
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
            'news' => [
                'label' => 'Latest news', 'help' => 'Shows your newest announcements that are meant for everyone. It updates by itself.',
                'fields' => [
                    $heading(),
                    ['key' => 'count', 'label' => 'How many to show (1 to 10)', 'kind' => 'number', 'min' => 1, 'max' => 10],
                ],
                'default' => ['heading' => 'Latest news', 'count' => 3],
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
