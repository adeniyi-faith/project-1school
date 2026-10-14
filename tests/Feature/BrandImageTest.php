<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Security\SecurityTestCase;

/** The logo goes up on its own small step and answers with the new picture and its colours */
class BrandImageTest extends SecurityTestCase
{
    public function test_the_logo_is_saved_on_its_own_and_its_colours_come_back(): void
    {
        Storage::fake('public');
        $school = $this->makeSchool('Logo School');
        $this->actingAs($this->makeUser($school, 'school-admin'));

        $img = imagecreatetruecolor(60, 60);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        imagefilledrectangle($img, 0, 0, 59, 40, imagecolorallocate($img, 0, 90, 160));
        $file = sys_get_temp_dir().'/brand-test.png';
        imagepng($img, $file);

        $res = $this->postJson('/school/settings/brand-image', ['kind' => 'logo', 'image' => new UploadedFile($file, 'logo.png', 'image/png', null, true)]);
        $res->assertOk()->assertJsonPath('kind', 'logo')->assertJsonPath('colors.0', '#005aa0');
        $this->assertNotNull($school->fresh()->logo);
        Storage::disk('public')->assertExists($school->fresh()->logo);
    }

    public function test_a_file_that_is_not_a_picture_is_refused_and_staff_without_settings_access_cannot_use_it(): void
    {
        Storage::fake('public');
        $school = $this->makeSchool('Logo School');
        $this->actingAs($this->makeUser($school, 'school-admin'));
        $this->postJson('/school/settings/brand-image', ['kind' => 'logo', 'image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertStatus(422);

        auth()->logout();
        $this->actingAs($this->makeUser($school, 'parent'));
        $this->postJson('/school/settings/brand-image', ['kind' => 'logo', 'image' => UploadedFile::fake()->image('l.png')])->assertForbidden();
    }
}
