<?php

namespace App\Support;

use App\Models\School;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Looks at a school's saved logo and suggests colours to use on the website, report cards and so on.
 * It shrinks the picture, ignores see-through, white, black and grey pixels, groups similar colours
 * and returns the most common ones (strongest first) as hex codes. Never fails: no logo, no image
 * support or an unreadable picture just gives an empty list.
 */
class LogoColors
{
    /** @return list<string> up to $max hex colours such as "#990000" */
    public static function for(School $school, int $max = 4): array
    {
        if (! $school->logo || ! function_exists('imagecreatefromstring')) {
            return [];
        }

        try {
            $bytes = Storage::disk('public')->get($school->logo);
            if (! $bytes) {
                return [];
            }

            return self::fromBytes($bytes, $max);
        } catch (Throwable) {
            return [];
        }
    }

    /** @return list<string> */
    public static function fromBytes(string $bytes, int $max = 4): array
    {
        $src = @imagecreatefromstring($bytes);
        if (! $src) {
            return [];
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $side = 48;
        $scale = min(1, $side / max($w, $h));
        $tw = max(1, (int) round($w * $scale));
        $th = max(1, (int) round($h * $scale));
        $img = imagecreatetruecolor($tw, $th);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
        imagecopyresampled($img, $src, 0, 0, 0, 0, $tw, $th, $w, $h);

        // Count pixels in colour groups (each channel rounded to 32 steps)
        $groups = [];
        for ($x = 0; $x < $tw; $x++) {
            for ($y = 0; $y < $th; $y++) {
                $c = imagecolorat($img, $x, $y);
                if ((($c >> 24) & 0x7F) > 40) {
                    continue; // mostly see-through
                }
                [$r, $g, $b] = [($c >> 16) & 255, ($c >> 8) & 255, $c & 255];
                $hi = max($r, $g, $b);
                $lo = min($r, $g, $b);
                if ($hi > 235 && $lo > 225) {
                    continue; // white
                }
                if ($hi < 40) {
                    continue; // black
                }
                if ($hi - $lo < 28) {
                    continue; // grey
                }
                $key = ($r >> 5).'-'.($g >> 5).'-'.($b >> 5);
                $groups[$key] ??= ['n' => 0, 'r' => 0, 'g' => 0, 'b' => 0];
                $groups[$key]['n']++;
                $groups[$key]['r'] += $r;
                $groups[$key]['g'] += $g;
                $groups[$key]['b'] += $b;
            }
        }

        uasort($groups, fn ($a, $b) => $b['n'] <=> $a['n']);

        $picked = [];
        foreach ($groups as $gr) {
            $rgb = [(int) round($gr['r'] / $gr['n']), (int) round($gr['g'] / $gr['n']), (int) round($gr['b'] / $gr['n'])];
            foreach ($picked as $p) {
                // skip colours that look almost the same as one already chosen
                if (abs($p[0] - $rgb[0]) + abs($p[1] - $rgb[1]) + abs($p[2] - $rgb[2]) < 90) {
                    continue 2;
                }
            }
            $picked[] = $rgb;
            if (count($picked) >= $max) {
                break;
            }
        }

        return array_map(fn ($c) => sprintf('#%02x%02x%02x', ...$c), $picked);
    }
}
