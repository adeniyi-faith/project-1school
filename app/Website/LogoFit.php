<?php

namespace App\Website;

use App\Models\School;
use App\Models\SchoolSite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Makes a school's logo readable on any header, menu or footer colour.
 *
 * The logo is looked at once (how light or dark it is, its shape, whether it comes with a solid box round it)
 * and the answer is remembered. Then, for each place the logo sits (header, full-screen menu, footer) the
 * background colour is compared with the logo. If the logo would be hard to see there, it is shown on a soft
 * badge (a light or dark plate behind it). The logo's own colours are never changed.
 * Schools can set the badge to Automatic, Always or Off, and choose its shape and colour.
 */
class LogoFit
{
    public const MODES = ['auto' => 'Automatic (only when the logo would be hard to see)', 'always' => 'Always show the logo on a badge', 'never' => 'Off (never add a badge)'];
    public const SHAPES = ['rounded' => 'Rounded corners', 'circle' => 'Circle (square logos only)', 'square' => 'Square corners'];
    public const COLOURS = ['white' => 'White', 'cream' => 'Soft cream'];

    private const BINS = 16;
    /** Logos at least this much wider than tall get a wider box in the header */
    private const WIDE = 1.7;
    /** A logo counts as hard to see where fewer than this share of it stands out from the background */
    private const NEED_SHARE = 0.6;
    private const NEED_CONTRAST = 2.5;

    private function __construct(
        private readonly ?array $facts,
        private readonly string $mode,
        private readonly string $shape,
        private readonly string $colour,
        private readonly array $backs,
        private readonly string $deep,
    ) {}

    public static function for(School $school, SchoolSite $site, array $vars): self
    {
        $facts = $school->logo ? self::factsFor($school->logo) : null;
        $mode = $site->option('logo_badge');
        $shape = $site->option('logo_badge_shape');
        $colour = $site->option('logo_badge_colour');

        return new self(
            $facts,
            isset(self::MODES[$mode]) ? $mode : 'auto',
            isset(self::SHAPES[$shape]) ? $shape : 'rounded',
            isset(self::COLOURS[$colour]) ? $colour : 'white',
            ['hdr' => $vars['hdr-bg'], 'menu' => $vars['menu-bg'], 'ftr' => $vars['ftr-bg']],
            $vars['deep'],
        );
    }

    /** What we know about a stored logo (cached until the file changes), or null when it cannot be read */
    public static function factsFor(string $path): ?array
    {
        try {
            $disk = Storage::disk('public');
            if (! $disk->exists($path)) {
                return null;
            }
            $key = 'logo-fit:v1:'.md5($path.'|'.$disk->lastModified($path).'|'.$disk->size($path));

            return Cache::rememberForever($key, fn () => self::analyse((string) $disk->get($path)) ?? false) ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{bins: list<int>, ratio: float, boxed: bool}|null  bins = how many visible pixels fall in each of
     *         16 steps from darkest to lightest; ratio = width / height; boxed = a solid colour fills the whole picture
     */
    public static function analyse(string $bytes): ?array
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }
        $src = @imagecreatefromstring($bytes);
        if (! $src) {
            return null;
        }
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w < 1 || $h < 1) {
            return null;
        }
        $scale = min(1, 64 / max($w, $h));
        $tw = max(1, (int) round($w * $scale));
        $th = max(1, (int) round($h * $scale));
        $img = imagecreatetruecolor($tw, $th);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
        imagecopyresampled($img, $src, 0, 0, 0, 0, $tw, $th, $w, $h);

        $bins = array_fill(0, self::BINS, 0);
        $edgeSolid = 0;
        $edgeTotal = 0;
        $edgeColours = [];
        for ($x = 0; $x < $tw; $x++) {
            for ($y = 0; $y < $th; $y++) {
                $c = imagecolorat($img, $x, $y);
                $a = ($c >> 24) & 0x7F;
                $rgb = [($c >> 16) & 255, ($c >> 8) & 255, $c & 255];
                if ($x === 0 || $y === 0 || $x === $tw - 1 || $y === $th - 1) {
                    $edgeTotal++;
                    if ($a <= 20) {
                        $edgeSolid++;
                        $edgeColours[] = $rgb;
                    }
                }
                if ($a > 60) {
                    continue; // see-through
                }
                $l = Themes::luminance(sprintf('#%02x%02x%02x', ...$rgb));
                $bins[min(self::BINS - 1, (int) floor($l * self::BINS))]++;
            }
        }

        // a solid box: nearly every edge pixel is opaque and they are all about the same colour
        $boxed = false;
        if ($edgeTotal > 0 && $edgeSolid / $edgeTotal > 0.95) {
            [$r0, $g0, $b0] = $edgeColours[0];
            $same = 0;
            foreach ($edgeColours as [$r, $g, $b]) {
                if (abs($r - $r0) + abs($g - $g0) + abs($b - $b0) < 60) {
                    $same++;
                }
            }
            $boxed = $same / count($edgeColours) > 0.9;
        }

        return ['bins' => $bins, 'ratio' => round($w / $h, 2), 'boxed' => $boxed];
    }

    /** Share (0 to 1) of the logo that stands out from this background colour */
    public static function visibleShare(array $facts, string $bg): float
    {
        $total = array_sum($facts['bins']);
        if ($total < 1) {
            return 1.0;
        }
        $ok = 0;
        foreach ($facts['bins'] as $i => $n) {
            $l = ($i + 0.5) / self::BINS;
            $c = (max($l, Themes::luminance($bg)) + 0.05) / (min($l, Themes::luminance($bg)) + 0.05);
            if ($c >= self::NEED_CONTRAST) {
                $ok += $n;
            }
        }

        return $ok / $total;
    }

    /** True when most of the logo is light (so a light badge would hide it and a dark one is needed) */
    private function mostlyLight(): bool
    {
        $total = array_sum($this->facts['bins']);
        if ($total < 1) {
            return false;
        }
        $sum = 0;
        foreach ($this->facts['bins'] as $i => $n) {
            $sum += $n * (($i + 0.5) / self::BINS);
        }

        return ($sum / $total) > 0.6;
    }

    public function isWide(): bool
    {
        return $this->facts !== null && $this->facts['ratio'] >= self::WIDE;
    }

    /** Does this place need a badge? */
    public function needsBadge(string $spot): bool
    {
        if ($this->facts === null || $this->mode === 'never') {
            return false;
        }
        if ($this->mode === 'always') {
            return true;
        }
        if ($this->facts['boxed']) {
            return false; // it already comes on its own box; that only gets rounded corners
        }

        return self::visibleShare($this->facts, $this->backs[$spot] ?? $this->backs['hdr']) < self::NEED_SHARE;
    }

    /** CSS classes for the logo picture in a place: $spot is hdr (top bar), menu (full-screen menu) or ftr (footer) */
    public function classes(string $spot): string
    {
        if ($this->facts === null) {
            return 'logo';
        }
        $c = ['logo'];
        if ($this->isWide()) {
            $c[] = 'logo--wide';
        }
        if ($this->facts['boxed'] && $this->mode !== 'always') {
            $c[] = 'logo--boxed';
        }
        if ($this->needsBadge($spot)) {
            $c[] = 'logo--badge';
            $c[] = 'logo--'.($this->mostlyLight() && $this->mode !== 'always' ? 'badge-dark' : 'badge-'.$this->colour);
            $shape = $this->shape === 'circle' && ($this->isWide() || $this->facts['ratio'] < 0.8 || $this->facts['ratio'] > 1.25) ? 'rounded' : $this->shape;
            $c[] = 'logo--'.$shape;
        }

        return implode(' ', $c);
    }
}
