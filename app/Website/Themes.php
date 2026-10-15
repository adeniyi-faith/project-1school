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
    private const CRIMSON = "'Crimson Pro','Iowan Old Style','Palatino Linotype',Palatino,Georgia,serif";
    private const MONTSERRAT = "'Montserrat','Source Sans 3',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif";
    private const CASLON = "'Libre Caslon Text','Iowan Old Style','Palatino Linotype',Palatino,Georgia,serif";
    private const FIGTREE = "'Figtree','Source Sans 3',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif";
    private const OUTFIT = "'Outfit','Source Sans 3',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif";
    private const NUNITO = "'Nunito Sans','Source Sans 3',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif";
    private const GARAMOND = "'EB Garamond','Iowan Old Style','Palatino Linotype',Palatino,Georgia,serif";
    private const LORA = "'Lora','Iowan Old Style','Palatino Linotype',Palatino,Georgia,serif";
    private const BARLOW = "'Barlow','Source Sans 3',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif";
    private const SANS = "'Source Sans 3',system-ui,-apple-system,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif";

    public const ALL = [
        'heritage' => [
            'label' => 'Heritage', 'note' => 'Classic university look: gold header, red full-screen menu, big serif headlines, photo banners and a gold footer.',
            'brand' => '#990000', 'brand2' => '#ffcc00', 'bg' => '#ffffff', 'soft' => '#faf6ee', 'text' => '#1c1917',
            'head' => self::CRIMSON, 'body' => self::SANS, 'fonts' => 'Crimson+Pro:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500|Source+Sans+3:wght@400;600;700',
            'radius' => '0', 'radius_lg' => '1.75rem', 'btn_radius' => '.75rem', 'btn_case' => 'uppercase', 'btn_track' => '.16em', 'btn_style' => 'outline',
            'hdr' => 'brand2', 'ftr' => 'brand2',
        ],
        'modern' => [
            'label' => 'Modern', 'note' => 'Crisp preparatory-school look: solid navy bar, full-screen photo banner, square red buttons, slanted panel edges and a blue footer.',
            'brand' => '#002855', 'brand2' => '#c10230', 'bg' => '#ffffff', 'soft' => '#f3f5f9', 'text' => '#3b3b3b',
            'head' => self::MONTSERRAT, 'body' => self::MONTSERRAT, 'fonts' => 'Montserrat:wght@400;500;600;700;800|EB+Garamond:ital,wght@0,400;0,500;1,400',
            'radius' => '0', 'radius_lg' => '0', 'btn_radius' => '0', 'btn_case' => 'uppercase', 'btn_track' => '.26em', 'btn_style' => 'solid',
            'hdr' => 'brand', 'ftr' => 'slate',
        ],
        'warm' => [
            'label' => 'Warm', 'note' => 'Friendly independent-school look: white bar with Menu and Apply, full-screen white menu, photo banner with a serif headline, maroon bands with orange headings and a maroon footer.',
            'brand' => '#7a1f33', 'brand2' => '#e8852b', 'bg' => '#ffffff', 'soft' => '#f6f1ee', 'text' => '#3d4452',
            'head' => self::LORA, 'body' => self::BARLOW, 'fonts' => 'Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500|Barlow:wght@400;500;600;700|Barlow+Condensed:wght@500;600;700',
            'radius' => '1.25rem', 'radius_lg' => '1.5rem', 'btn_radius' => '.35rem', 'btn_case' => 'uppercase', 'btn_track' => '.12em', 'btn_style' => 'outline',
            'hdr' => 'bg', 'ftr' => 'brand',
        ],
        'evergreen' => [
            'label' => 'Evergreen', 'note' => 'Calm and classic: three-part bar with menu and search, deep green menu and banner panel, cream pages, serif headlines with an underlined italic phrase, square capital buttons.',
            'brand' => '#14472e', 'brand2' => '#00a062', 'bg' => '#f0ede2', 'soft' => '#ffffff', 'text' => '#2b2f2b',
            'head' => self::CASLON, 'body' => self::FIGTREE, 'fonts' => 'Libre+Caslon+Text:ital,wght@0,400;0,700;1,400|Figtree:wght@400;500;600;700',
            'radius' => '0', 'radius_lg' => '0', 'btn_radius' => '0', 'btn_case' => 'uppercase', 'btn_track' => '.14em', 'btn_style' => 'solid',
            'hdr' => 'brand', 'ftr' => 'brand',
        ],
        'crest' => [
            'label' => 'Crest', 'note' => 'Bold and friendly: white bar with a round menu button, coral full-screen menu, crimson banner with a big ring and arrow shape, huge capital headlines and chevron-cornered buttons.',
            'brand' => '#ad0040', 'brand2' => '#ff4b52', 'bg' => '#ffffff', 'soft' => '#f8f1f3', 'text' => '#595a60',
            'head' => self::OUTFIT, 'body' => self::NUNITO, 'fonts' => 'Outfit:wght@400;500;600;700;800|Nunito+Sans:ital,wght@0,400;0,600;0,700;1,400',
            'radius' => '0', 'radius_lg' => '0', 'btn_radius' => '0', 'btn_case' => 'uppercase', 'btn_track' => '.26em', 'btn_style' => 'solid',
            'hdr' => 'bg', 'ftr' => 'brand',
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

        $hdr = match ($t['hdr']) { 'brand2' => $brand2, 'brand' => $brand, default => $t['bg'] };
        $deep = self::shade($brand, .55);
        // white writing sits on the deep shade, so a pale main colour still gets a properly dark one
        for ($i = 0; $i < 20 && self::contrast($deep, '#ffffff') < 7; $i++) {
            $deep = self::shade($deep, .9);
        }
        $ftr = match ($t['ftr']) { 'brand2' => $brand2, 'brand' => $brand, 'deep' => $deep, 'slate' => self::tint($brand, .14), default => '#171717' };
        $hdrInk = self::ink($hdr);

        // Text colours that stay readable whatever two colours a school picks. A pale or white main colour
        // would otherwise vanish when used for writing on a white page, or gold on a gold band.
        $brandT = self::readable($brand, $t['bg'], 4.5);
        $brand2T = self::readable($brand2, $t['bg'], 3.0);
        $band = self::readable($brand, '#ffffff', 4.5);
        $brand2D = self::onDark($brand2, [$band, $deep]);
        // the full-screen menu on a phone has its own background in each theme
        $menuBg = match ($theme) { 'heritage' => $band, 'modern' => $brand, 'warm' => '#ffffff', default => $deep };

        // the second colour as a flat fill that carries writing: for buttons (small writing, 4.5) and big menus (large writing, 3)
        [$fill, $fillInk] = self::fillFor($brand2, 4.5);
        [$fillLg, $fillLgInk] = self::fillFor($brand2, 3.0);

        return [
            'fill' => $fill, 'fill-ink' => $fillInk, 'fill-lg' => $fillLg, 'fill-lg-ink' => $fillLgInk,
            'band' => $band, 'brand-t' => $brandT, 'brand2-t' => $brand2T, 'brand2-d' => $brand2D,
            'brand' => $brand, 'brand-ink' => self::ink($brand),
            'brand2' => $brand2, 'brand2-ink' => self::ink($brand2),
            'deep' => $deep, 'deep-ink' => self::ink($deep), 'deep-mid' => self::shade($brand, .8), 'gold-lt' => self::onDark(self::tint($brand2, .42), [$band, $deep]),
            'bg' => $t['bg'], 'soft' => $t['soft'], 'text' => $t['text'],
            'muted' => self::ink($t['bg']) === '#ffffff' ? '#d4d4d8' : '#57534e',
            'line' => 'rgba(0,0,0,.1)',
            'head-font' => $t['head'], 'body-font' => $t['body'],
            'radius' => $t['radius'], 'radius-lg' => $t['radius_lg'],
            'btn-radius' => $t['btn_radius'], 'btn-case' => $t['btn_case'], 'btn-track' => $t['btn_track'],
            'hdr-bg' => $hdr, 'hdr-ink' => $hdrInk, 'menu-bg' => $menuBg,
            // the school name takes the main colour when that reads well on the header, else plain text
            'hdr-name' => self::contrast($brand, $hdr) >= 3.5 ? $brand : $hdrInk,
            'ftr-bg' => $ftr, 'ftr-ink' => self::ink($ftr),
            'serif-font' => self::GARAMOND,
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

    /** The colour (made a little darker or lighter if it has to) and the writing colour that reads on it with at least $need contrast */
    public static function fillFor(string $hex, float $need): array
    {
        $ink = self::ink($hex);
        $fill = $hex;
        for ($i = 0; $i < 60 && self::contrast($fill, $ink) < $need; $i++) {
            $fill = $ink === '#ffffff' ? self::shade($fill, .97) : self::tint($fill, .04);
        }

        return [$fill, $ink];
    }

    /** The colour itself if it reads on this background, else the nearest darker shade that does */
    public static function readable(string $hex, string $on, float $need): string
    {
        $c = $hex;
        for ($i = 0; $i < 24 && self::contrast($c, $on) < $need; $i++) {
            $c = self::shade($c, .88);
        }

        return $c;
    }

    /** A colour for writing or drawing on the dark bands (the main colour or its deep shade): the colour itself if it shows, else the best of a lighter tint, white or near-black */
    public static function onDark(string $hex, array $backs): string
    {
        $score = fn (string $c) => min(array_map(fn ($b) => self::contrast($c, $b), $backs));
        $best = $hex;
        foreach ([$hex, self::tint($hex, .5), '#ffffff', '#1c1917'] as $c) {
            if ($score($c) >= 3.0) {
                return $c;
            }
            if ($score($c) > $score($best)) {
                $best = $c;
            }
        }

        return $best;
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
        // whichever of dark or white reads better on this colour (the old fixed cut-off gave white on orange and gold)
        return self::contrast('#1c1917', $hex) >= self::contrast('#ffffff', $hex) ? '#1c1917' : '#ffffff';
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
