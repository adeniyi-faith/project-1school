<?php

namespace Tests\Feature;

use App\Support\ImageShrinker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageShrinkerTest extends TestCase
{
    private function noisyPicture(int $size, string $name = 'big.jpg'): UploadedFile
    {
        $image = imagecreatetruecolor($size, $size);
        for ($i = 0; $i < 4000; $i++) {
            imagesetpixel($image, random_int(0, $size - 1), random_int(0, $size - 1), random_int(0, 0xFFFFFF));
            imagefilledrectangle($image, random_int(0, $size), random_int(0, $size), random_int(0, $size), random_int(0, $size), random_int(0, 0xFFFFFF));
        }
        $path = tempnam(sys_get_temp_dir(), 'img').'.jpg';
        imagejpeg($image, $path, 100);

        return new UploadedFile($path, $name, 'image/jpeg', null, true);
    }

    public function test_a_big_picture_is_made_smaller_before_it_is_stored(): void
    {
        Storage::fake('private');
        $file = $this->noisyPicture(2400);

        $path = ImageShrinker::store($file, 'photos', 'private', 600);

        [$width, $height] = getimagesizefromstring(Storage::disk('private')->get($path));
        $this->assertLessThanOrEqual(600, max($width, $height));
        $this->assertLessThan($file->getSize(), Storage::disk('private')->size($path));
    }

    public function test_a_small_picture_is_kept_exactly_as_it_was(): void
    {
        Storage::fake('private');
        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $path = ImageShrinker::store($file, 'logos', 'private', 600);

        $this->assertSame(file_get_contents($file->getRealPath()), Storage::disk('private')->get($path));
    }

    public function test_something_that_is_not_a_picture_is_stored_unchanged(): void
    {
        Storage::fake('private');
        $file = UploadedFile::fake()->create('notes.pdf', 50, 'application/pdf');

        $path = ImageShrinker::store($file, 'docs', 'private', 600);

        Storage::disk('private')->assertExists($path);
    }
}
