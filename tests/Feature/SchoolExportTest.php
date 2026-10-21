<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;
use ZipArchive;

/** A school's data copy holds only that school's records, never passwords or keys, and needs a reason */
class SchoolExportTest extends SecurityTestCase
{
    private function owner(): User
    {
        $owner = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $owner->assignRole('super-admin');

        return $owner;
    }

    private function open(string $path): ZipArchive
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));

        return $zip;
    }

    public function test_it_needs_a_reason_and_the_owner(): void
    {
        $a = $this->makeSchool('School A');
        $this->actingAs($this->makeUser($a, 'school-admin'))->post("/super-admin/schools/{$a->id}/export", ['reason' => 'Asked by the school for a copy'])->assertForbidden();

        $this->actingAs($this->owner())->post("/super-admin/schools/{$a->id}/export", ['reason' => 'short'])->assertSessionHasErrors('reason');
    }

    public function test_the_zip_has_only_this_schools_records_and_no_secrets(): void
    {
        $a = $this->makeSchool('School A');
        $b = $this->makeSchool('School B');
        $adminA = $this->makeUser($a, 'school-admin');
        $this->makeUser($b, 'school-admin');
        \App\Models\SchoolSetting::withoutGlobalScopes()->create(['school_id' => $a->id, 'key' => 'payment_keys_paystack', 'value' => 'SUPERSECRETVALUE']);

        $response = $this->actingAs($this->owner())->post("/super-admin/schools/{$a->id}/export", ['reason' => 'Asked by the school for a copy']);
        $response->assertOk();

        $zip = $this->open($response->baseResponse->getFile()->getPathname());
        $users = $zip->getFromName('users.csv');
        $this->assertStringContainsString($adminA->email, $users);
        $this->assertStringNotContainsString('School B', $zip->getFromName('schools.csv') ?: '');
        $this->assertSame(1, substr_count(trim($users), "\n"), 'header plus exactly one user');

        $all = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $all .= $zip->getFromIndex($i);
        }
        $this->assertStringNotContainsString('SUPERSECRETVALUE', $all);
        $this->assertStringNotContainsString($adminA->password, $all);
        $this->assertStringContainsString('NOT included', $zip->getFromName('README.txt'));
        $this->assertDatabaseHas('activity_log', ['description' => 'School data exported']);
    }

    public function test_the_schools_list_points_out_possible_duplicates_without_changing_anything(): void
    {
        $a = $this->makeSchool('Bright Star Academy');
        $b = $this->makeSchool('Second School');
        $b->update(['name' => ' BRIGHT STAR ACADEMY']);
        $this->makeSchool('Other School');

        $this->actingAs($this->owner())->get('/super-admin/schools')->assertOk()
            ->assertInertia(fn ($p) => $p->has('duplicates', 1)->where('duplicates.0.why', 'Same name')->has('duplicates.0.schools', 2));
        $this->assertSame(3, \App\Models\School::count());
    }
}
