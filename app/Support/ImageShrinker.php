<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Throwable;

/**
 * Stores an uploaded picture, making it smaller first when it is big. Phone photos are often 3-8 MB;
 * a logo, signature or passport photo never needs more than about 800 pixels. Small pictures are kept
 * exactly as they were. If the picture cannot be read or the server has no image support, it is stored
 * unchanged, so an upload never fails because of this.
 */
class ImageShrinker
{
    /** Pictures at or under this many bytes and pixels are left alone */
    private const KEEP_BYTES = 300 * 1024;

    public static function store(UploadedFile $file, string $directory, string $disk, int $maxSide = 800): string
    {
        $data = self::shrink($file, $maxSide);
        if ($data === null) {
            return $file->store($directory, $disk);
        }

        $path = trim($directory, '/').'/'.Str::random(40).'.'.$data['extension'];
        \Illuminate\Support\Facades\Storage::disk($disk)->put($path, $data['bytes']);

        return $path;
    }

    /** @return array{bytes: string, extension: string}|null null = keep the original */
    public static function shrink(UploadedFile $file, int $maxSide): ?array
    {
        if (! function_exists('imagecreatefromstring') || ! $file->isValid()) {
            return null;
        }

        try {
            $info = @getimagesize($file->getRealPath());
            if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
                return null; // only JPEG and PNG are re-encoded; anything else is kept
            }
            [$width, $height] = $info;
            if ($file->getSize() <= self::KEEP_BYTES && max($width, $height) <= $maxSide) {
                return null;
            }
            // a huge picture would not fit in memory when opened: keep it rather than crash
            if ($width * $height > 40_000_000) {
                return null;
            }

            $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
            if (! $source) {
                return null;
            }

            $scale = min(1, $maxSide / max($width, $height));
            $newWidth = max(1, (int) round($width * $scale));
            $newHeight = max(1, (int) round($height * $scale));
            $isPng = $info[2] === IMAGETYPE_PNG;

            // phone photos carry a "rotate me" note; apply it so the picture stays upright
            if (! $isPng && function_exists('exif_read_data')) {
                $orientation = @exif_read_data($file->getRealPath())['Orientation'] ?? 1;
                $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
                if ($angle) {
                    $rotated = imagerotate($source, $angle, 0);
                    if ($rotated) {
                        $source = $rotated;
                        if (abs($angle) === 90) {
                            [$newWidth, $newHeight] = [$newHeight, $newWidth];
                        }
                    }
                }
            }

            $target = imagecreatetruecolor($newWidth, $newHeight);
            if ($isPng) {
                imagealphablending($target, false);
                imagesavealpha($target, true);
                imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
            } else {
                imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
            }
            imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, imagesx($source), imagesy($source));

            ob_start();
            $isPng ? imagepng($target, null, 7) : imagejpeg($target, null, 82);
            $bytes = (string) ob_get_clean();

            // never make a file bigger than it was
            if ($bytes === '' || strlen($bytes) >= $file->getSize()) {
                return null;
            }

            return ['bytes' => $bytes, 'extension' => $isPng ? 'png' : 'jpg'];
        } catch (Throwable) {
            return null;
        }
    }
}
