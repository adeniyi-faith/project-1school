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
            // Already small in pixels (for instance shrunk in the browser): keep it as it is, no image work needed
            if (max($width, $height) <= $maxSide && $file->getSize() <= self::KEEP_BYTES * 8) {
                return null;
            }
            // Opening a picture takes about 4 bytes per pixel, and we hold up to three copies at once
            // (original, turned, smaller). A fatal "out of memory" cannot be caught, so check first and
            // keep the original when the server does not have the room.
            if (! self::hasRoomFor($width * $height * 4 * 3 + 8 * 1024 * 1024)) {
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
            unset($source);

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

    /** Is there enough spare memory for this much work? Tries to raise the limit a little first. */
    private static function hasRoomFor(int $bytes): bool
    {
        $limit = self::toBytes((string) ini_get('memory_limit'));
        if ($limit === -1) {
            return true;
        }
        if ($limit - memory_get_usage(true) < $bytes) {
            @ini_set('memory_limit', (string) max($limit, memory_get_usage(true) + $bytes + 16 * 1024 * 1024));
            $limit = self::toBytes((string) ini_get('memory_limit'));
        }

        return $limit === -1 || $limit - memory_get_usage(true) >= $bytes;
    }

    private static function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return -1;
        }
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
