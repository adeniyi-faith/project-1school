<?php

namespace Tests\Feature;

use App\Support\SafeLocalDisk;
use Tests\TestCase;

class SafeLocalDiskTest extends TestCase
{
    public function test_a_local_disk_built_without_fileinfo_saves_reads_and_names_the_type_from_the_ending(): void
    {
        $root = sys_get_temp_dir().'/safe-disk-'.bin2hex(random_bytes(4));
        $disk = SafeLocalDisk::make(['driver' => 'local', 'root' => $root, 'throw' => false], 'public');

        $this->assertTrue($disk->put('logos/a.png', 'not really a picture'));
        $this->assertSame('not really a picture', $disk->get('logos/a.png'));
        $this->assertSame('image/png', $disk->mimeType('logos/a.png'));
        $this->assertTrue($disk->delete('logos/a.png'));

        \Illuminate\Support\Facades\File::deleteDirectory($root);
    }
}
