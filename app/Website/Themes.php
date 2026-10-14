<?php

namespace App\Website;

/** The looks a school can pick for its website. Each is a set of colours and a font, applied as CSS variables */
class Themes
{
    public const ALL = [
        'classic' => ['label' => 'Classic', 'note' => 'White and calm, serif headings', 'accent' => '#1d4ed8', 'bg' => '#ffffff', 'soft' => '#f1f5f9', 'text' => '#0f172a', 'font' => "Georgia, 'Times New Roman', serif"],
        'modern'  => ['label' => 'Modern',  'note' => 'Clean and bold, sans-serif',   'accent' => '#7c3aed', 'bg' => '#ffffff', 'soft' => '#f5f3ff', 'text' => '#111827', 'font' => "system-ui, -apple-system, 'Segoe UI', sans-serif"],
        'warm'    => ['label' => 'Warm',    'note' => 'Friendly cream background',     'accent' => '#c2410c', 'bg' => '#fffbf5', 'soft' => '#ffedd5', 'text' => '#292524', 'font' => "system-ui, -apple-system, 'Segoe UI', sans-serif"],
    ];

    public static function options(): array
    {
        return collect(self::ALL)->map(fn ($t, $key) => ['key' => $key, 'label' => $t['label'], 'note' => $t['note'], 'accent' => $t['accent']])->values()->all();
    }

    /** The CSS values for a site: the theme, with the school's own colour on top when it set one */
    public static function css(string $theme, ?string $color): array
    {
        $t = self::ALL[$theme] ?? self::ALL['classic'];
        if ($color && preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $t['accent'] = $color;
        }

        return $t;
    }
}
