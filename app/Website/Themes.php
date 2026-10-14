<?php

namespace App\Website;

/**
 * The looks a school can pick for its website. Each theme is a set of colours, fonts and shapes handed to the
 * page as CSS variables; the school can override the two colours. Text colours are worked out so writing is
 * always readable on whatever colour the school chooses.
 */
class Themes
{
    private const SERIF = "'Source Serif 4','Iowan Old Style','Palatino Linotype',Palatino,Georgia,serif";
    private const SANS = "'Source Sans 3',system-ui,-apple-system,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif";

    public const ALL = [
        'heritage' => [
            'label' => 'Heritage', 'note' => 'Gold header, deep red accents, classic serif headings. Inspired by great universities.',
            'brand' => '#990000', 'brand2' => '#ffcc00', 'bg' => '#ffffff', 'soft' => '#faf6ee', 'text' => '#1c1917',
            'head' => self::SERIF, 'body' => self::SANS,
            'radius' => '.375rem', 'radius_lg' => '1.5rem', 'btn_radius' => '.75rem', 'btn_case' => 'uppercase', 'btn_track' => '.16em', 'btn_style' => 'outline',
            'hdr' => 'brand2', 'ftr' => 'brand2',
        ],
        'modern' => [
            'label' => 'Modern', 'note' => 'Crisp white, bold colour, rounded cards and pill buttons.',
            'brand' => '#4338ca', 'brand2' => '#f59e0b', 'bg' => '#ffffff', 'soft' => '#f5f6ff', 'text' => '#0f172a',
            'head' => self::SANS, 'body' => self::SANS,
            'radius' => '1rem', 'radius_lg' => '1.5rem', 'btn_radius' => '999px', 'btn_case' => 'none', 'btn_track' => '0', 'btn_style' => 'solid',
            'hdr' => 'bg', 'ftr' => 'dark',
        ],
        'warm' => [
            'label' => 'Warm', 'note' => 'Friendly cream background, soft curves, gentle serif headings.',
            'brand' => '#c2410c', 'brand2' => '#fbbf24', 'bg' => '#fffaf3', 'soft' => '#fdf0dc', 'text' => '#292524',
            'head' => self::SERIF, 'body' => self::SANS,
            'radius' => '1.25rem', 'radius_lg' => '2rem', 'btn_radius' => '999px', 'btn_case' => 'none', 'btn_track' => '.01em', 'btn_style' => 'solid',
            'hdr' => 'bg', 'ftr' => 'dark',
        ],
    ];

    /** Ready colour pairs a school can try with one tap (main colour, second colour) */
    public const PALETTES = [
        ['name' => 'Cardinal and gold', 'color' => '#990000', 'color2' => '#ffcc00'],
        ['name' => 'Royal blue', 'color' => '#1e3a8a', 'color2' => '#facc15'],
        ['name' => 'Forest green', 'color' => '#166534', 'color2' => '#fde047'],
        ['name' => 'Navy and sky', 'color' => '#0c2340', 'color2' => '#7dd3fc'],
        ['name' => 'Purple and gold', 'color' => '#5b21b6', 'color2' => '#fbbf24'],
        ['name' => 'Maroon and cream', 'color' => '#7f1d1d', 'color2' => '#fde68a'],
        ['name' => 'Teal and coral', 'color' => '#0f766e', 'color2' => '#fb923c'],
        ['name' => 'Indigo and amber', 'color' => '#4338ca', 'color2' => '#f59e0b'],
        ['name' => 'Burnt orange', 'color' => '#c2410c', 'color2' => '#fbbf24'],
        ['name' => 'Black and gold', 'color' => '#111827', 'color2' => '#eab308'],
    ];

    public static function options(): array
    {
        return collect(self::ALL)->map(fn ($t, $key) => [
            'key' => $key, 'label' => $t['label'], 'note' => $t['note'], 'brand' => $t['brand'], 'brand2' => $t['brand2'],
            'bg' => $t['bg'], 'soft' => $t['soft'], 'serif' => $t['head'] === self::SERIF,
        ])->values()->all();
    }

    public static function isHex(?string $c): bool
    {
        return is_string($c) && (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $c);
    }

    /** The CSS variables for a site: the theme, with the school's own colours on top when it chose some */
    public static function vars(string $theme, ?string $color, ?string $color2): array
    {
        $t = self::ALL[$theme] ?? self::ALL['heritage'];
        $brand = self::isHex($color) ? strtolower($color) : $t['brand'];
        $brand2 = self::isHex($color2) ? strtolower($color2) : $t['brand2'];

        $hdr = $t['hdr'] === 'brand2' ? $brand2 : $t['bg'];
        $ftr = $t['ftr'] === 'brand2' ? $brand2 : '#171717';
        $hdrInk = self::ink($hdr);

        return [
            'brand' => $brand, 'brand-ink' => self::ink($brand),
            'brand2' => $brand2, 'brand2-ink' => self::ink($brand2),
            'bg' => $t['bg'], 'soft' => $t['soft'], 'text' => $t['text'],
            'muted' => self::ink($t['bg']) === '#ffffff' ? '#d4d4d8' : '#57534e',
            'line' => 'rgba(0,0,0,.1)',
            'head-font' => $t['head'], 'body-font' => $t['body'],
            'radius' => $t['radius'], 'radius-lg' => $t['radius_lg'],
            'btn-radius' => $t['btn_radius'], 'btn-case' => $t['btn_case'], 'btn-track' => $t['btn_track'],
            'hdr-bg' => $hdr, 'hdr-ink' => $hdrInk,
            // the school name takes the main colour when that reads well on the header, else plain text
            'hdr-name' => self::contrast($brand, $hdr) >= 3.5 ? $brand : $hdrInk,
            'ftr-bg' => $ftr, 'ftr-ink' => self::ink($ftr),
            // links in running text: the main colour, unless it is too faint on the page background
            'link' => self::contrast($brand, $t['bg']) >= 4.5 ? $brand : $t['text'],
        ];
    }

    public static function style(string $theme, ?string $color, ?string $color2): string
    {
        return collect(self::vars($theme, $color, $color2))->map(fn ($v, $k) => "--{$k}:{$v}")->implode(';');
    }

    public static function btnStyle(string $theme): string
    {
        return (self::ALL[$theme] ?? self::ALL['heritage'])['btn_style'];
    }

    /** Readable text colour (dark or white) for writing on this background */
    public static function ink(string $hex): string
    {
        return self::luminance($hex) > 0.45 ? '#1c1917' : '#ffffff';
    }

    public static function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(function ($c) {
            $c = hexdec($c) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    public static function contrast(string $a, string $b): float
    {
        [$x, $y] = [self::luminance($a), self::luminance($b)];

        return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
    }
}
