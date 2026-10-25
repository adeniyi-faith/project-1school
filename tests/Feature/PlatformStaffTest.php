<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use App\Support\PlatformAccess;
use Database\Seeders\RolePermissionSeeder;
use Tests\Feature\Security\SecurityTestCase;

/** The platform's own team: support, finance, sales, developer and content people, each with limited powers */
class PlatformStaffTest extends SecurityTestCase
{
    private function owner(): User
    {
        $owner = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $owner->assignRole('super-admin');

        return $owner;
    }

    private function staff(string $role, string $scope = 'all', array $schools = [], array $extras = []): User
    {
        $user = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $user->assignRole($role);
        $user->platformStaff()->create(['school_scope' => $scope]);
        foreach ($schools as $school) {
            \DB::table('platform_staff_schools')->insert(['user_id' => $user->id, 'school_id' => $school->id, 'created_at' => now(), 'updated_at' => now()]);
        }
        if ($extras) {
            $user->givePermissionTo($extras);
        }

        return $user;
    }

    public function test_the_owner_adds_a_staff_member_with_a_role_and_chosen_schools(): void
    {
        $a = $this->makeSchool('School A');
        $this->makeSchool('School B');

        $this->actingAs($this->owner())->post('/super-admin/staff', [
            'name' => 'Ada Support', 'email' => 'ada@adino.test', 'role' => 'platform-support',
            'extras' => ['platform.billing.view'], 'school_scope' => 'assigned', 'school_ids' => [$a->id],
        ])->assertSessionHasNoErrors()->assertSessionHas('login_details');

        $ada = User::where('email', 'ada@adino.test')->firstOrFail();
        $this->assertTrue($ada->hasRole('platform-support'));
        $this->assertNull($ada->school_id);
        $this->assertSame('assigned', $ada->platformStaff->school_scope);
        $this->assertSame([$a->id], PlatformAccess::schoolIds($ada));
        $this->assertTrue($ada->hasDirectPermission('platform.billing.view'));
    }

    public function test_choosing_assigned_schools_needs_at_least_one_school(): void
    {
        $this->actingAs($this->owner())->post('/super-admin/staff', [
            'name' => 'X', 'email' => 'x@adino.test', 'role' => 'platform-sales', 'school_scope' => 'assigned', 'school_ids' => [],
        ])->assertSessionHasErrors('school_ids');
    }

    public function test_the_staff_page_and_its_powers_belong_to_the_owner_alone(): void
    {
        $other = $this->staff('platform-developer', extras: ['platform.settings.manage']);
        $this->actingAs($other)->get('/super-admin/staff')->assertForbidden();
        $this->post('/super-admin/staff', ['name' => 'Z', 'email' => 'z@adino.test', 'role' => 'platform-support', 'school_scope' => 'all'])->assertForbidden();

        // Nobody can be handed the power to manage the team
        $this->actingAs($this->owner())->post('/super-admin/staff', [
            'name' => 'Y', 'email' => 'y@adino.test', 'role' => 'platform-support', 'school_scope' => 'all', 'extras' => ['platform.staff.manage'],
        ])->assertSessionHasErrors('extras.0');
    }

    public function test_each_role_opens_only_its_own_pages(): void
    {
        $support = $this->staff('platform-support');
        $this->actingAs($support)->get('/super-admin/schools')->assertOk();
        $this->get('/super-admin/users')->assertOk();
        $this->get('/super-admin/packages')->assertForbidden();
        $this->get('/super-admin/settings')->assertForbidden();
        $this->get('/super-admin/module-manager')->assertForbidden();
        $this->get('/super-admin/schools/create')->assertForbidden();

        $finance = $this->staff('platform-finance');
        $this->actingAs($finance)->get('/super-admin/subscriptions')->assertOk();
        $this->get('/super-admin/packages')->assertOk();
        $this->get('/super-admin/users')->assertForbidden();
        $this->get('/super-admin/newsletter')->assertForbidden();

        $content = $this->staff('platform-content');
        $this->actingAs($content)->get('/super-admin/newsletter')->assertOk();
        $this->get('/super-admin/extensions')->assertOk();
        $this->get('/super-admin/schools')->assertForbidden();
    }

    public function test_an_extra_permission_opens_one_more_page_for_one_person(): void
    {
        $s = $this->staff('platform-support');
        $this->actingAs($s)->get('/super-admin/packages')->assertForbidden();
        $s->givePermissionTo('platform.billing.view');
        $this->get('/super-admin/packages')->assertOk();
        // Viewing is not changing
        $this->post('/super-admin/packages', ['name' => 'X'])->assertForbidden();
    }

    public function test_pages_not_listed_for_staff_are_owner_only(): void
    {
        // Every platform page must either be listed in PlatformAccess::PAGES or stay owner-only; a new page is never open to staff by accident
        $dev = $this->staff('platform-developer');
        $this->actingAs($dev);
        foreach (\Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'super-admin') || ! in_array('GET', $route->methods(), true) || str_contains($route->uri(), '{')) {
                continue;
            }
            $rule = PlatformAccess::requirement($route->getName());
            if ($rule === null) {
                $this->get('/'.$route->uri())->assertForbidden();
            }
        }
        $this->assertTrue(true);
    }

    public function test_school_people_cannot_open_platform_pages(): void
    {
        $a = $this->makeSchool('School A');
        $admin = $this->makeUser($a, 'school-admin');
        $this->actingAs($admin)->get('/super-admin/schools')->assertForbidden();
        $this->get('/super-admin/staff')->assertForbidden();
    }

    public function test_staff_limited_to_some_schools_see_and_reach_only_those(): void
    {
        $a = $this->makeSchool('School A');
        $b = $this->makeSchool('School B');
        $c = $this->makeSchool('School C');
        $me = $this->staff('platform-support', 'assigned', [$a, $c]);

        $this->actingAs($me)->get('/super-admin/schools')->assertOk()->assertInertia(fn ($p) => $p
            ->where('schools.meta.total', 2)->where('stats.total', 2));
        $this->get("/super-admin/schools/{$a->id}")->assertOk();
        $this->get("/super-admin/schools/{$b->id}")->assertForbidden();
        $this->post("/super-admin/schools/{$b->id}/enter")->assertForbidden();
        // Pages that are about the whole platform are closed to them
        $this->get('/super-admin/dashboard')->assertForbidden();

        $this->makeUser($a, 'teacher');
        $this->makeUser($b, 'teacher');
        $this->get('/super-admin/users')->assertOk()->assertInertia(fn ($p) => $p->where('users.meta.total', 1));
    }

    public function test_staff_cannot_touch_the_owner_or_other_staff_through_the_users_page(): void
    {
        $a = $this->makeSchool('School A');
        $owner = $this->owner();
        $other = $this->staff('platform-developer');
        $support = $this->staff('platform-support');
        $teacher = $this->makeUser($a, 'teacher');

        $this->actingAs($support);
        $this->patch("/super-admin/users/{$owner->id}/reset-password", ['password' => 'newpassword1'])->assertForbidden();
        $this->patch("/super-admin/users/{$other->id}/suspend")->assertForbidden();
        $this->put("/super-admin/users/{$teacher->id}", [
            'name' => $teacher->name, 'email' => $teacher->email, 'role' => 'super-admin', 'school_id' => $a->id, 'status' => 'active',
        ])->assertSessionHasErrors('role');
        $this->assertFalse($teacher->fresh()->hasRole('super-admin'));
        $this->patch("/super-admin/users/{$teacher->id}/suspend")->assertSessionHas('success');
        $this->assertSame('suspended', $teacher->fresh()->status);

        // The users list never shows the owner or the team to staff
        $this->get('/super-admin/users')->assertInertia(fn ($p) => $p->where('users.meta.total', 1));
    }

    public function test_staff_inside_a_school_can_look_but_not_change_unless_given_the_extra(): void
    {
        $a = $this->makeSchool('School A');
        SchoolClass::withoutGlobalScopes()->create(['school_id' => $a->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        $support = $this->staff('platform-support');

        $this->actingAs($support)->post("/super-admin/schools/{$a->id}/enter")->assertRedirect('/school/reports/dashboard');
        $this->get('/school/classes')->assertOk()->assertInertia(fn ($p) => $p
            ->where('acting_school.name', 'School A')->where('acting_school.read_only', true)->where('auth.user.role', 'super-admin'));
        $this->post('/school/classes', ['name' => 'JSS 2', 'numeric_name' => 8])->assertSessionHas('error');
        $this->assertSame(1, SchoolClass::withoutGlobalScopes()->count());

        // Inside a school they never gain platform powers
        $this->get('/super-admin/staff')->assertForbidden();
        $this->get('/super-admin/packages')->assertForbidden();

        $this->post('/super-admin/leave-school')->assertRedirect();
        $this->get('/school/classes')->assertRedirect();

        $support->givePermissionTo('platform.schools.act');
        $this->post("/super-admin/schools/{$a->id}/enter");
        $this->post('/school/classes', ['name' => 'JSS 2', 'numeric_name' => 8])->assertSessionHasNoErrors();
        $this->assertSame($a->id, SchoolClass::withoutGlobalScopes()->where('name', 'JSS 2')->value('school_id'));
        $this->assertNull($support->fresh()->school_id);
    }

    public function test_staff_who_cannot_enter_schools_are_turned_back(): void
    {
        $a = $this->makeSchool('School A');
        $this->actingAs($this->staff('platform-finance'))->post("/super-admin/schools/{$a->id}/enter")->assertForbidden();
        $this->get('/school/students')->assertRedirect();
        $this->post('/school/classes', ['name' => 'X', 'numeric_name' => 1])->assertForbidden();
    }

    public function test_losing_a_school_from_the_list_ends_the_visit(): void
    {
        $a = $this->makeSchool('School A');
        $me = $this->staff('platform-support', 'assigned', [$a]);
        $this->actingAs($me)->post("/super-admin/schools/{$a->id}/enter");
        $this->get('/school/students')->assertOk();

        \DB::table('platform_staff_schools')->where('user_id', $me->id)->delete();
        $this->get('/school/students')->assertRedirect();
        $this->assertNull(session('acting_school_id'));
    }

    public function test_the_menu_only_lists_what_staff_may_open(): void
    {
        $this->actingAs($this->staff('platform-finance'))->get('/super-admin/subscriptions')->assertInertia(function ($p) {
            $p->where('auth.user.role', 'platform-staff')->where('platform.role', 'Finance');
            $paths = $p->toArray()['props']['auth']['paths'];
            $this->assertContains('/super-admin/subscriptions', $paths);
            $this->assertNotContains('/super-admin/users', $paths);
            $this->assertNotContains('/super-admin/staff', $paths);
        });
    }

    public function test_signing_in_lands_staff_on_a_page_they_may_open(): void
    {
        $this->actingAs($this->staff('platform-finance'))->get('/dashboard')->assertRedirect(route('super-admin.dashboard'));
        $this->actingAs($this->staff('platform-content'))->get('/dashboard')->assertRedirect(route('super-admin.dashboard'));
        $a = $this->makeSchool('School A');
        $this->actingAs($this->staff('platform-support', 'assigned', [$a]))->get('/dashboard')->assertRedirect(route('super-admin.schools.index'));
    }

    public function test_extras_survive_the_role_seeder_and_removal_ends_all_access(): void
    {
        $s = $this->staff('platform-support', extras: ['platform.billing.view']);
        $this->seed(RolePermissionSeeder::class);
        $this->assertTrue($s->fresh()->hasPermissionTo('platform.billing.view'));

        $this->actingAs($this->owner())->delete("/super-admin/staff/{$s->id}", ['reason' => 'Left the team last week', 'confirm_name' => $s->name])->assertSessionHas('success');
        $s = $s->fresh();
        $this->assertFalse(PlatformAccess::isPlatform($s));
        $this->assertSame('inactive', $s->status);
        $this->actingAs($s)->get('/super-admin/schools')->assertRedirect(route('login'));
    }

    public function test_the_owner_cannot_be_changed_from_the_team_page(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->delete("/super-admin/staff/{$owner->id}", ['reason' => 'Trying to remove the owner', 'confirm_name' => $owner->name])->assertNotFound();
        $this->assertTrue($owner->fresh()->hasRole('super-admin'));
    }

    public function test_switching_off_a_staff_member_is_logged(): void
    {
        $s = $this->staff('platform-sales');
        $this->actingAs($this->owner())->patch("/super-admin/staff/{$s->id}/suspend");
        $this->assertSame('suspended', $s->fresh()->status);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'platform', 'description' => 'Platform staff member switched off', 'subject_id' => $s->id]);
    }
}
