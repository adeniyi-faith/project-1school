<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
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

    /** A picture someone just chose in a form, shown straight away without being stored anywhere */
    public static function fromUpload(?UploadedFile $file): ?string
    {
        if (! $file || ! $file->isValid()) {
            return null;
        }
        $mime = $file->getMimeType();
        if (! in_array($mime, ['image/png', 'image/jpeg', 'image/gif'], true)) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($file->getRealPath()));
    }
}
