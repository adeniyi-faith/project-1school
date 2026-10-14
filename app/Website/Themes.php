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
    private const NEWSREADER = "'Newsreader','Source Serif 4','Iowan Old Style',Georgia,serif";
    private const INSTRUMENT = "'Instrument Sans','Source Sans 3',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif";
    private const SANS = "'Source Sans 3',system-ui,-apple-system,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif";

    public const ALL = [
        'heritage' => [
            'label' => 'Heritage', 'note' => 'Classic university look: top strip, serif headings, a banner with action cards over its edge.',
            'brand' => '#990000', 'brand2' => '#ffcc00', 'bg' => '#ffffff', 'soft' => '#faf6ee', 'text' => '#1c1917',
            'head' => self::SERIF, 'body' => self::SANS,
            'radius' => '.375rem', 'radius_lg' => '1.5rem', 'btn_radius' => '.75rem', 'btn_case' => 'uppercase', 'btn_track' => '.16em', 'btn_style' => 'outline',
            'hdr' => 'bg', 'ftr' => 'dark',
        ],
        'modern' => [
            'label' => 'Modern', 'note' => 'Floating menu bar, a split banner with a big picture, a bento grid of cards and a timeline.',
            'brand' => '#4338ca', 'brand2' => '#f59e0b', 'bg' => '#ffffff', 'soft' => '#f5f6ff', 'text' => '#0f172a',
            'head' => self::SANS, 'body' => self::SANS,
            'radius' => '1rem', 'radius_lg' => '1.5rem', 'btn_radius' => '999px', 'btn_case' => 'none', 'btn_track' => '0', 'btn_style' => 'solid',
            'hdr' => 'bg', 'ftr' => 'dark',
        ],
        'warm' => [
            'label' => 'Warm', 'note' => 'Friendly and playful: centred header, soft shapes, an arched picture, bright rounded cards.',
            'brand' => '#c2410c', 'brand2' => '#fbbf24', 'bg' => '#fffaf3', 'soft' => '#fdf0dc', 'text' => '#292524',
            'head' => self::SERIF, 'body' => self::SANS,
            'radius' => '1.25rem', 'radius_lg' => '2rem', 'btn_radius' => '999px', 'btn_case' => 'none', 'btn_track' => '.01em', 'btn_style' => 'solid',
            'hdr' => 'bg', 'ftr' => 'dark',
        ],
        'statement' => [
            'label' => 'Statement', 'note' => 'Bold and serious: deep colour bands, huge serif headlines, numbered commitments and pillars.',
            'brand' => '#6b1414', 'brand2' => '#c89b32', 'bg' => '#fbf9f4', 'soft' => '#f2ede2', 'text' => '#3b3631',
            'head' => self::NEWSREADER, 'body' => self::INSTRUMENT, 'fonts' => 'Newsreader:ital,opsz,wght@0,6..72,500;0,6..72,600;0,6..72,700;0,6..72,800;1,6..72,300;1,6..72,400;1,6..72,500;1,6..72,600;1,6..72,700|Instrument+Sans:wght@400;500;600;700',
            'radius' => '.5rem', 'radius_lg' => '.5rem', 'btn_radius' => '.4rem', 'btn_case' => 'none', 'btn_track' => '0', 'btn_style' => 'solid',
            'hdr' => 'bg', 'ftr' => 'deep',
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

    /** The theme to draw with: the saved one, or Heritage when the name is not known */
    public static function key(?string $theme): string
    {
        return isset(self::ALL[$theme]) ? $theme : 'heritage';
    }

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
        $deep = self::shade($brand, .55);
        $ftr = match ($t['ftr']) { 'brand2' => $brand2, 'deep' => $deep, default => '#171717' };
        $hdrInk = self::ink($hdr);

        return [
            'brand' => $brand, 'brand-ink' => self::ink($brand),
            'brand2' => $brand2, 'brand2-ink' => self::ink($brand2),
            'deep' => $deep, 'deep-ink' => self::ink($deep), 'deep-mid' => self::shade($brand, .8), 'gold-lt' => self::tint($brand2, .42),
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

    /** A lighter tint of a colour: $amount is how far it moves towards white (0.4 = 40%) */
    public static function tint(string $hex, float $amount): string
    {
        $parts = array_map(fn ($c) => str_pad(dechex((int) round(hexdec($c) + (255 - hexdec($c)) * $amount)), 2, '0', STR_PAD_LEFT), str_split(ltrim($hex, '#'), 2));

        return '#'.implode('', $parts);
    }

    /** The Google Fonts address a theme needs, if it uses fonts that are not on every device */
    public static function fontsUrl(?string $theme): ?string
    {
        $fonts = (self::ALL[self::key($theme)])['fonts'] ?? null;

        return $fonts ? 'https://fonts.googleapis.com/css2?'.collect(explode('|', $fonts))->map(fn ($f) => 'family='.$f)->implode('&').'&display=swap' : null;
    }

    /** A darker shade of a colour: $keep is how much of the original stays (0.4 keeps 40%, the rest is black) */
    public static function shade(string $hex, float $keep): string
    {
        $parts = array_map(fn ($c) => str_pad(dechex((int) round(hexdec($c) * $keep)), 2, '0', STR_PAD_LEFT), str_split(ltrim($hex, '#'), 2));

        return '#'.implode('', $parts);
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
