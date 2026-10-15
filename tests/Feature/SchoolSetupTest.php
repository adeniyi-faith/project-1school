<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use App\Support\SetupChecklist;
use Tests\Feature\Security\SecurityTestCase;

/** Starting a school: the platform owner makes it with its first administrator, and the dashboard shows what is left to set up. */
class SchoolSetupTest extends SecurityTestCase
{
    private function owner(): User
    {
        $owner = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $owner->assignRole('super-admin');

        return $owner;
    }

    public function test_a_new_school_can_be_made_with_its_type_and_first_administrator_in_one_step(): void
    {
        $this->actingAs($this->owner())->post('/super-admin/schools', [
            'name' => 'Bright Stars', 'admin_name' => 'Mrs Bello', 'admin_email' => 'bello@brightstars.test', 'school_type' => 'nigerian',
        ])->assertSessionHasNoErrors()->assertSessionHas('login_details', fn ($d) => strlen($d[0]['password']) === 8 && $d[0]['email'] === 'bello@brightstars.test');

        $school = School::where('name', 'Bright Stars')->sole();
        $admin = User::where('email', 'bello@brightstars.test')->sole();
        $this->assertSame($school->id, $admin->school_id);
        $this->assertTrue($admin->hasRole('school-admin'));
        $this->assertTrue(SchoolClass::where('school_id', $school->id)->exists(), 'the Nigerian classes were added');
    }

    public function test_a_school_can_still_be_made_with_just_a_name(): void
    {
        $this->actingAs($this->owner())->post('/super-admin/schools', ['name' => 'Plain School'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('schools', ['name' => 'Plain School']);
        $this->assertSame(0, User::where('email', 'like', '%plain%')->count());
    }

    public function test_a_taken_administrator_email_leaves_no_half_made_school(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);

        $this->actingAs($this->owner())->post('/super-admin/schools', [
            'name' => 'Ghost School', 'admin_name' => 'X', 'admin_email' => 'taken@example.test',
        ])->assertSessionHasErrors('admin_email');

        $this->assertDatabaseMissing('schools', ['name' => 'Ghost School']);
    }

    public function test_the_checklist_ticks_itself_as_the_school_fills_in(): void
    {
        $school = $this->makeSchool('Checklist School');
        $before = SetupChecklist::for($school->id);
        $this->assertSame(10, $before['total']);
        $this->assertFalse(collect($before['items'])->firstWhere('key', 'classes')['done']);

        SchoolClass::create(['school_id' => $school->id, 'name' => 'JSS 1']);
        $after = SetupChecklist::for($school->id);
        $this->assertTrue(collect($after['items'])->firstWhere('key', 'classes')['done']);
        $this->assertGreaterThan($before['done'], $after['done']);
    }

    public function test_the_dashboard_shows_the_checklist_to_the_school_admin_only(): void
    {
        $school = $this->makeSchool('Dash School');
        $admin = $this->makeUser($school, 'school-admin');
        $principal = $this->makeUser($school, 'principal');

        $this->actingAs($admin)->get('/school/reports/dashboard', ['X-Inertia' => 'true'])->assertOk()
            ->assertJsonPath('props.setup.total', 10);
        $this->actingAs($principal)->get('/school/reports/dashboard', ['X-Inertia' => 'true'])->assertOk()
            ->assertJsonPath('props.setup', null);
    }
}
