<?php

namespace App\Website;

/**
 * The building blocks a website page is made of. Each block type says what its boxes are called,
 * what it starts with, and which view draws it. Adding a new kind of block means adding one entry
 * here and one view in resources/views/website/blocks.
 */
class Blocks
{
    public const MAX_BLOCKS = 30;
    public const BUTTONS = ['none' => 'No button', 'apply' => 'Apply online', 'login' => 'Sign in'];

    /** field kinds: text (one line), textarea, select (options), number, list (repeating rows of text/textarea fields) */
    public static function types(): array
    {
        return [
            'hero' => [
                'label' => 'Big banner', 'help' => 'A large heading at the top of a page, with an optional button.',
                'fields' => [
                    ['key' => 'heading', 'label' => 'Heading', 'kind' => 'text', 'max' => 120],
                    ['key' => 'subheading', 'label' => 'Short line under it', 'kind' => 'text', 'max' => 220],
                    ['key' => 'button', 'label' => 'Button', 'kind' => 'select', 'options' => self::BUTTONS],
                ],
                'default' => ['heading' => 'Welcome', 'subheading' => '', 'button' => 'apply'],
            ],
            'text' => [
                'label' => 'Text', 'help' => 'A heading and some paragraphs.',
                'fields' => [
                    ['key' => 'heading', 'label' => 'Heading', 'kind' => 'text', 'max' => 120],
                    ['key' => 'body', 'label' => 'Text', 'kind' => 'textarea', 'max' => 4000],
                ],
                'default' => ['heading' => '', 'body' => ''],
            ],
            'features' => [
                'label' => 'Highlights', 'help' => 'Up to 8 small boxes, for example Boarding, Science labs, Sports.',
                'fields' => [
                    ['key' => 'heading', 'label' => 'Heading', 'kind' => 'text', 'max' => 120],
                    ['key' => 'items', 'label' => 'Boxes', 'kind' => 'list', 'limit' => 8, 'fields' => [
                        ['key' => 'title', 'label' => 'Title', 'kind' => 'text', 'max' => 80],
                        ['key' => 'body', 'label' => 'A sentence about it', 'kind' => 'textarea', 'max' => 300],
                    ]],
                ],
                'default' => ['heading' => 'Why families choose us', 'items' => []],
            ],
            'news' => [
                'label' => 'Latest news', 'help' => 'Shows your newest announcements that are meant for everyone. It updates by itself.',
                'fields' => [
                    ['key' => 'heading', 'label' => 'Heading', 'kind' => 'text', 'max' => 120],
                    ['key' => 'count', 'label' => 'How many to show (1 to 10)', 'kind' => 'number', 'min' => 1, 'max' => 10],
                ],
                'default' => ['heading' => 'Latest news', 'count' => 3],
            ],
            'apply' => [
                'label' => 'Apply online', 'help' => 'A box that sends visitors to your online admission form. What they send lands in Admission Inquiries.',
                'fields' => [
                    ['key' => 'heading', 'label' => 'Heading', 'kind' => 'text', 'max' => 120],
                    ['key' => 'body', 'label' => 'Text', 'kind' => 'textarea', 'max' => 600],
                ],
                'default' => ['heading' => 'Join our school', 'body' => 'Admission is open. Fill in a short form and we will contact you.'],
            ],
            'contact' => [
                'label' => 'Contact details', 'help' => 'Your address, phone and email, taken from your school profile.',
                'fields' => [
                    ['key' => 'heading', 'label' => 'Heading', 'kind' => 'text', 'max' => 120],
                ],
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

        return $t ? ['type' => $type] + $t['default'] : null;
    }

    /** Keep only known block types and known fields, trimmed to their limits, so nothing odd can be stored */
    public static function clean(mixed $blocks): array
    {
        $types = self::types();
        $out = [];

        foreach (array_slice(is_array($blocks) ? $blocks : [], 0, self::MAX_BLOCKS) as $block) {
            $type = is_array($block) ? ($block['type'] ?? null) : null;
            if (! is_string($type) || ! isset($types[$type])) {
                continue;
            }
            $out[] = ['type' => $type] + self::cleanFields($types[$type]['fields'], $block);
        }

        return $out;
    }

    private static function cleanFields(array $fields, array $source): array
    {
        $clean = [];

        foreach ($fields as $f) {
            $value = $source[$f['key']] ?? null;

            $clean[$f['key']] = match ($f['kind']) {
                'select' => array_key_exists((string) $value, $f['options']) ? (string) $value : array_key_first($f['options']),
                'number' => max($f['min'], min($f['max'], (int) $value ?: $f['min'])),
                'list' => collect(is_array($value) ? array_slice($value, 0, $f['limit']) : [])
                    ->filter(fn ($row) => is_array($row))
                    ->map(fn ($row) => self::cleanFields($f['fields'], $row))
                    ->filter(fn ($row) => collect($row)->contains(fn ($v) => $v !== ''))
                    ->values()->all(),
                default => mb_substr(trim(is_scalar($value) ? (string) $value : ''), 0, $f['max']),
            };
        }

        return $clean;
    }
}
