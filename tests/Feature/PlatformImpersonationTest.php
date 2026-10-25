<?php

namespace Tests\Feature;

use App\Models\PlatformAuditLog;
use App\Models\User;
use App\Support\Impersonation;
use Tests\Feature\Security\SecurityTestCase;

/** "Log in as this user": time-limited, reason needed, look-only by default, always written down */
class PlatformImpersonationTest extends SecurityTestCase
{
    private function owner(): User
    {
        $u = User::factory()->create(['school_id' => null, 'status' => 'active', 'name' => 'Faith Owner']);
        $u->assignRole('super-admin');

        return $u;
    }

    private function staff(array $extras = [], string $scope = 'all', array $schools = []): User
    {
        $u = User::factory()->create(['school_id' => null, 'status' => 'active', 'name' => 'Ada Staff']);
        $u->assignRole('platform-support');
        $u->platformStaff()->create(['school_scope' => $scope]);
        if ($schools) {
            \DB::table('platform_staff_schools')->insert(array_map(fn ($id) => ['user_id' => $u->id, 'school_id' => $id], $schools));
        }
        $u->givePermissionTo($extras);

        return $u;
    }

    private function teacher(int $schoolId, string $name = 'Tola Teacher'): User
    {
        $u = User::factory()->create(['school_id' => $schoolId, 'status' => 'active', 'name' => $name]);
        $u->assignRole('teacher');

        return $u;
    }

    private const WHY = ['reason' => 'The teacher cannot see her class list', 'minutes' => 15, 'mode' => 'look'];

    public function test_a_reason_is_needed_and_the_person_becomes_the_login_for_a_while(): void
    {
        $school = $this->makeSchool('Greenfield College');
        $teacher = $this->teacher($school->id);
        $this->actingAs($this->owner());

        $this->post("/super-admin/users/{$teacher->id}/impersonate", ['reason' => 'short', 'minutes' => 15, 'mode' => 'look'])->assertSessionHasErrors('reason');
        $this->assertFalse(Impersonation::active());

        $this->post("/super-admin/users/{$teacher->id}/impersonate", self::WHY)->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($teacher);
        $this->assertTrue(Impersonation::active());
        $this->assertSame(PlatformAuditLog::where('action', 'impersonate_start')->count(), 1);
        $this->assertStringContainsString('The teacher cannot see her class list', PlatformAuditLog::where('action', 'impersonate_start')->first()->description);
    }

    public function test_look_only_turns_back_every_change_and_logs_it(): void
    {
        $school = $this->makeSchool('Greenfield College');
        $teacher = $this->teacher($school->id);
        $owner = $this->owner();
        $this->actingAs($owner);
        $this->post("/super-admin/users/{$teacher->id}/impersonate", self::WHY);

        $this->post('/school/classes', ['name' => 'JSS 1'])->assertRedirect();
        $this->assertDatabaseMissing('classes', ['name' => 'JSS 1']);
        $this->assertSame(1, PlatformAuditLog::where('action', 'refused')->where('user_id', $owner->id)->count());
    }

    public function test_password_and_security_pages_are_never_open_while_signed_in_as_someone(): void
    {
        $school = $this->makeSchool('Greenfield College');
        $teacher = $this->teacher($school->id);
        $this->actingAs($this->owner());
        $this->post("/super-admin/users/{$teacher->id}/impersonate", ['reason' => 'Checking a report about a lost login', 'minutes' => 15, 'mode' => 'act']);

        $before = $teacher->fresh()->password;
        $this->put('/profile/password', ['current_password' => 'x', 'password' => 'newpassword123', 'password_confirmation' => 'newpassword123'])->assertRedirect();
        $this->assertSame($before, $teacher->fresh()->password);
    }

    public function test_stopping_brings_the_real_person_back_and_is_logged(): void
    {
        $school = $this->makeSchool('Greenfield College');
        $teacher = $this->teacher($school->id);
        $owner = $this->owner();
        $this->actingAs($owner);
        $this->post("/super-admin/users/{$teacher->id}/impersonate", self::WHY);

        $this->post('/impersonation/stop')->assertRedirect();
        $this->assertAuthenticatedAs($owner);
        $this->assertFalse(Impersonation::active());
        $this->assertSame(1, PlatformAuditLog::where('action', 'impersonate_stop')->count());
        $this->get('/super-admin/dashboard')->assertOk();
    }

    public function test_time_running_out_ends_it(): void
    {
        $school = $this->makeSchool('Greenfield College');
        $teacher = $this->teacher($school->id);
        $owner = $this->owner();
        $this->actingAs($owner);
        $this->post("/super-admin/users/{$teacher->id}/impersonate", self::WHY);

        $this->travel(16)->minutes();
        $this->get('/dashboard');
        $this->assertAuthenticatedAs($owner);
        $this->assertFalse(Impersonation::active());
    }

    public function test_signing_out_while_impersonating_returns_to_the_real_account(): void
    {
        $school = $this->makeSchool('Greenfield College');
        $teacher = $this->teacher($school->id);
        $owner = $this->owner();
        $this->actingAs($owner);
        $this->post("/super-admin/users/{$teacher->id}/impersonate", self::WHY);

        $this->post('/logout')->assertRedirect();
        $this->assertAuthenticatedAs($owner);
    }

    public function test_only_people_with_the_permission_and_only_for_their_schools(): void
    {
        $a = $this->makeSchool('Greenfield College');
        $b = $this->makeSchool('Blue Hills');
        $ta = $this->teacher($a->id, 'Teacher A');
        $tb = $this->teacher($b->id, 'Teacher B');

        // No permission
        $this->actingAs($this->staff())->post("/super-admin/users/{$ta->id}/impersonate", self::WHY)->assertForbidden();

        // Permission, but only for school A
        $scoped = $this->staff(['platform.users.impersonate'], 'assigned', [$a->id]);
        $this->actingAs($scoped);
        $this->post("/super-admin/users/{$tb->id}/impersonate", self::WHY)->assertForbidden();
        $this->post("/super-admin/users/{$ta->id}/impersonate", self::WHY)->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($ta);
    }

    public function test_staff_without_permission_to_act_cannot_choose_changes_allowed(): void
    {
        $school = $this->makeSchool('Greenfield College');
        $teacher = $this->teacher($school->id);
        $this->actingAs($this->staff(['platform.users.impersonate']));
        $this->post("/super-admin/users/{$teacher->id}/impersonate", ['reason' => 'Checking a report about a lost login', 'minutes' => 15, 'mode' => 'act']);

        $this->assertTrue(Impersonation::current()['read_only']);
    }

    public function test_the_platform_team_and_switched_off_logins_cannot_be_opened(): void
    {
        $school = $this->makeSchool('Greenfield College');
        $off = $this->teacher($school->id);
        $off->update(['status' => 'suspended']);
        $owner = $this->owner();
        $this->actingAs($owner);

        $this->post("/super-admin/users/{$off->id}/impersonate", self::WHY)->assertSessionHas('error');
        $this->assertAuthenticatedAs($owner);

        $other = $this->staff();
        $this->post("/super-admin/users/{$other->id}/impersonate", self::WHY)->assertSessionHas('error');
        $this->assertAuthenticatedAs($owner);
    }

    public function test_it_ends_at_once_if_the_real_person_is_switched_off(): void
    {
        $school = $this->makeSchool('Greenfield College');
        $teacher = $this->teacher($school->id);
        $staff = $this->staff(['platform.users.impersonate']);
        $this->actingAs($staff);
        $this->post("/super-admin/users/{$teacher->id}/impersonate", self::WHY);

        $staff->update(['status' => 'suspended']);
        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_school_logins_cannot_start_it(): void
    {
        $school = $this->makeSchool('Greenfield College');
        $a = $this->teacher($school->id);
        $b = $this->teacher($school->id, 'Other');
        $this->actingAs($a)->post("/super-admin/users/{$b->id}/impersonate", self::WHY)->assertForbidden();
    }
}
