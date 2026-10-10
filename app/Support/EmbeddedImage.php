<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

/** Turns a stored image into a data: address, so a PDF can show it without fetching anything. */
class EmbeddedImage
{
    public static function from(?string $path, string $disk = 'private'): ?string
    {
        if (! $path) {
            return null;
        }
        try {
            $storage = Storage::disk($disk);
            if (! $storage->exists($path)) {
                return null;
            }
            $mime = $storage->mimeType($path) ?: 'image/png';
            if (! in_array($mime, ['image/png', 'image/jpeg', 'image/gif'], true)) {
                return null;
            }

            return 'data:'.$mime.';base64,'.base64_encode($storage->get($path));
        } catch (Throwable) {
            return null;
        }
    }
}
