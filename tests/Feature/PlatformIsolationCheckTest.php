<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\Security\SecurityTestCase;

/** The live school-separation self-check: owner only, quiet on clean data, loud on a record sitting in the wrong school */
class PlatformIsolationCheckTest extends SecurityTestCase
{
    private function owner(): User
    {
        $owner = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $owner->assignRole('super-admin');

        return $owner;
    }

    private function checkStatus(array $report, string $key): string
    {
        return collect($report['checks'])->firstWhere('key', $key)['status'];
    }

    public function test_only_the_owner_can_see_or_run_it(): void
    {
        $this->actingAs($this->makeUser($this->makeSchool('A'), 'school-admin'));
        $this->get('/super-admin/isolation-check')->assertForbidden();
        $this->post('/super-admin/isolation-check/run')->assertForbidden();
    }

    public function test_clean_data_has_no_problems_and_the_run_is_remembered_and_audited(): void
    {
        $school = $this->makeSchool('A');
        $class = SchoolClass::withoutGlobalScopes()->create(['school_id' => $school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        Student::withoutGlobalScopes()->create(['school_id' => $school->id, 'class_id' => $class->id, 'first_name' => 'Ada', 'last_name' => 'X', 'admission_no' => 'A1', 'gender' => 'female', 'status' => 'active']);

        $this->actingAs($this->owner())->post('/super-admin/isolation-check/run')->assertSessionHas('success');
        $report = $this->getJson('/super-admin/isolation-check')->assertOk()->json('report');

        $this->assertSame(0, $report['summary']['bad']);
        $this->assertSame('ok', $this->checkStatus($report, 'models'));
        $this->assertSame('ok', $this->checkStatus($report, 'links'));
        $this->assertSame('ok', $this->checkStatus($report, 'routes'));
        $this->assertNotNull(Activity::where('log_name', 'platform')->where('description', 'ran the school-separation self-check')->first());
    }

    public function test_a_student_pointing_at_another_schools_class_is_reported_without_names(): void
    {
        $a = $this->makeSchool('A');
        $b = $this->makeSchool('B');
        $classB = SchoolClass::withoutGlobalScopes()->create(['school_id' => $b->id, 'name' => 'B Class', 'numeric_name' => 7]);
        Student::withoutGlobalScopes()->create(['school_id' => $a->id, 'class_id' => $classB->id, 'first_name' => 'Secretname', 'last_name' => 'X', 'admission_no' => 'A1', 'gender' => 'female', 'status' => 'active']);

        $this->actingAs($this->owner())->post('/super-admin/isolation-check/run')->assertSessionHas('error');
        $body = $this->getJson('/super-admin/isolation-check')->getContent();
        $report = json_decode($body, true)['report'];

        $this->assertSame('bad', $this->checkStatus($report, 'links'));
        $this->assertStringContainsString('students', $body);
        $this->assertStringNotContainsString('Secretname', $body);
    }

    public function test_a_record_of_a_missing_school_and_an_account_with_no_school_are_found(): void
    {
        $school = $this->makeSchool('A');
        SchoolClass::withoutGlobalScopes()->create(['school_id' => $school->id, 'name' => 'Real', 'numeric_name' => 1]);
        User::factory()->create(['school_id' => null, 'status' => 'active']); // not a platform owner, and no school
        DB::statement('PRAGMA defer_foreign_keys = ON');
        DB::table('classes')->insert(['school_id' => 9999, 'name' => 'Ghost', 'numeric_name' => 3, 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->owner())->post('/super-admin/isolation-check/run');
        $report = $this->getJson('/super-admin/isolation-check')->json('report');

        $this->assertSame('warn', $this->checkStatus($report, 'accounts'));
        $this->assertSame('ok', $this->checkStatus($report, 'no-school')); // the database itself refuses a school-owned row with no school
        $this->assertSame('bad', $this->checkStatus($report, 'orphans'));
    }
}
