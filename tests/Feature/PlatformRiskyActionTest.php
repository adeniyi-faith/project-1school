<?php

namespace Tests\Feature;

use App\Models\PlatformApproval;
use App\Models\PlatformAuditLog;
use App\Models\School;
use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;

/** Risky platform actions need a written reason, a typed name, and (when switched on) a second person */
class PlatformRiskyActionTest extends SecurityTestCase
{
    private function owner(string $name = 'Faith Owner'): User
    {
        $u = User::factory()->create(['school_id' => null, 'status' => 'active', 'name' => $name]);
        $u->assignRole('super-admin');

        return $u;
    }

    private function staff(string $role, array $extras = [], string $name = 'Ada Staff'): User
    {
        $u = User::factory()->create(['school_id' => null, 'status' => 'active', 'name' => $name]);
        $u->assignRole($role);
        $u->platformStaff()->create(['school_scope' => 'all']);
        $u->givePermissionTo($extras);

        return $u;
    }

    public function test_deleting_a_school_needs_a_reason_and_the_typed_name(): void
    {
        $a = $this->makeSchool('Greenfield College');
        $this->actingAs($this->owner());

        $this->delete("/super-admin/schools/{$a->id}")->assertSessionHasErrors('reason');
        $this->assertNotNull(School::find($a->id));

        $this->delete("/super-admin/schools/{$a->id}", ['reason' => 'Closing the account as asked'])->assertSessionHasErrors('confirm_name');
        $this->delete("/super-admin/schools/{$a->id}", ['reason' => 'Closing the account as asked', 'confirm_name' => 'Wrong name'])->assertSessionHasErrors('confirm_name');
        $this->assertNotNull(School::find($a->id));

        $this->delete("/super-admin/schools/{$a->id}", ['reason' => 'too short', 'confirm_name' => 'Greenfield College'])->assertSessionHasErrors('reason');
        $this->assertNotNull(School::find($a->id));

        // Capitals and extra spaces do not matter
        $this->delete("/super-admin/schools/{$a->id}", ['reason' => 'Closing the account as asked', 'confirm_name' => '  greenfield   COLLEGE '])->assertSessionHasNoErrors();
        $this->assertNull(School::find($a->id));
    }

    public function test_the_reason_is_written_in_the_activity_log_and_refusals_are_marked_refused(): void
    {
        $a = $this->makeSchool('Greenfield College');
        $this->actingAs($this->owner());

        $this->delete("/super-admin/schools/{$a->id}", ['reason' => 'nope']);
        $this->assertSame('refused', PlatformAuditLog::latest('id')->first()->action);

        $this->delete("/super-admin/schools/{$a->id}", ['reason' => 'The school asked in writing on 12 October', 'confirm_name' => 'Greenfield College']);
        $line = PlatformAuditLog::where('action', 'changed')->where('route', 'super-admin.schools.destroy')->firstOrFail();
        $this->assertStringContainsString('Deleted a school: Greenfield College', $line->description);
        $this->assertStringContainsString('reason: The school asked in writing on 12 October', $line->description);
        $this->assertSame('The school asked in writing on 12 October', $line->details['reason']);
    }

    public function test_a_reason_is_asked_for_removing_a_login_a_package_and_a_plan(): void
    {
        $school = $this->makeSchool('School A');
        $teacher = $this->makeUser($school, 'teacher');
        $this->actingAs($this->owner());

        $this->delete("/super-admin/users/{$teacher->id}")->assertSessionHasErrors('reason');
        $this->assertNotNull(User::find($teacher->id));
        $this->delete("/super-admin/users/{$teacher->id}", ['reason' => 'Left the school last term', 'confirm_name' => $teacher->name])->assertSessionHasNoErrors();
        $this->assertNull(User::find($teacher->id));

        $package = \App\Models\Package::create(['name' => 'Old plan', 'slug' => 'old-plan', 'price_monthly' => 0, 'price_yearly' => 0, 'max_students' => 10, 'max_staff' => 5]);
        $this->delete("/super-admin/packages/{$package->id}")->assertSessionHasErrors('reason');
        $this->assertNotNull(\App\Models\Package::find($package->id));
        $this->delete("/super-admin/packages/{$package->id}", ['reason' => 'No longer sold at all'])->assertSessionHasNoErrors();
        $this->assertNull(\App\Models\Package::find($package->id));
    }

    public function test_the_checks_can_be_switched_off_for_an_environment(): void
    {
        config(['platform.risky_actions' => false]);
        $a = $this->makeSchool('School A');

        $this->actingAs($this->owner())->delete("/super-admin/schools/{$a->id}")->assertSessionHasNoErrors();
        $this->assertNull(School::find($a->id));
    }

    public function test_with_second_approval_staff_must_wait_for_someone_else(): void
    {
        config(['platform.second_approval' => true]);
        $a = $this->makeSchool('Greenfield College');
        $staff = $this->staff('platform-support', ['platform.schools.delete']);
        $body = ['reason' => 'The school asked in writing', 'confirm_name' => 'Greenfield College'];

        // First try: nothing is deleted, a request is made
        $this->actingAs($staff)->delete("/super-admin/schools/{$a->id}", $body)->assertSessionHas('success');
        $this->assertNotNull(School::find($a->id));
        $request = PlatformApproval::firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertSame($staff->id, $request->requested_by);
        $this->assertSame('Greenfield College', $request->target_name);
        $this->assertSame('approval_requested', PlatformAuditLog::where('route', 'super-admin.schools.destroy')->latest('id')->first()->action);

        // Asking again does not make a second request
        $this->delete("/super-admin/schools/{$a->id}", $body);
        $this->assertSame(1, PlatformApproval::count());
        $this->assertNotNull(School::find($a->id));
    }

    public function test_an_approved_request_lets_the_asker_do_it_once_and_names_the_approver(): void
    {
        config(['platform.second_approval' => true]);
        $a = $this->makeSchool('Greenfield College');
        $staff = $this->staff('platform-support', ['platform.schools.delete']);
        $owner = $this->owner();
        $body = ['reason' => 'The school asked in writing', 'confirm_name' => 'Greenfield College'];

        $this->actingAs($staff)->delete("/super-admin/schools/{$a->id}", $body);
        $request = PlatformApproval::firstOrFail();

        $this->actingAs($owner)->post("/super-admin/approvals/{$request->id}/approve", ['note' => 'Checked the letter'])->assertSessionHas('success');
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame($owner->id, $request->fresh()->decided_by);

        $this->actingAs($staff)->delete("/super-admin/schools/{$a->id}", $body)->assertSessionHasNoErrors();
        $this->assertNull(School::find($a->id));
        $this->assertSame('used', $request->fresh()->status);
        $line = PlatformAuditLog::where('action', 'changed')->where('route', 'super-admin.schools.destroy')->firstOrFail();
        $this->assertStringContainsString('approved by Faith Owner', $line->description);
    }

    public function test_an_approval_cannot_be_used_twice_by_someone_else_or_after_it_runs_out(): void
    {
        config(['platform.second_approval' => true]);
        $a = $this->makeSchool('Greenfield College');
        $b = $this->makeSchool('Other School');
        $staff = $this->staff('platform-support', ['platform.schools.delete']);
        $other = $this->staff('platform-support', ['platform.schools.delete'], 'Bola Other');
        $owner = $this->owner();
        $body = ['reason' => 'The school asked in writing', 'confirm_name' => 'Greenfield College'];

        $this->actingAs($staff)->delete("/super-admin/schools/{$a->id}", $body);
        $request = PlatformApproval::firstOrFail();
        $this->actingAs($owner)->post("/super-admin/approvals/{$request->id}/approve");

        // Someone else cannot use it
        $this->actingAs($other)->delete("/super-admin/schools/{$a->id}", $body);
        $this->assertNotNull(School::find($a->id));
        // It does not cover a different school
        $this->actingAs($staff)->delete("/super-admin/schools/{$b->id}", ['reason' => 'The school asked in writing', 'confirm_name' => 'Other School']);
        $this->assertNotNull(School::find($b->id));
        // It runs out
        $request->update(['expires_at' => now()->subMinute()]);
        $this->actingAs($staff)->delete("/super-admin/schools/{$a->id}", $body);
        $this->assertNotNull(School::find($a->id));
    }

    public function test_nobody_can_answer_their_own_request_and_a_turned_down_one_stays_blocked(): void
    {
        config(['platform.second_approval' => true]);
        $a = $this->makeSchool('Greenfield College');
        $asker = $this->staff('platform-support', ['platform.schools.delete', 'platform.approvals.manage']);
        $body = ['reason' => 'The school asked in writing', 'confirm_name' => 'Greenfield College'];

        $this->actingAs($asker)->delete("/super-admin/schools/{$a->id}", $body);
        $request = PlatformApproval::firstOrFail();

        $this->post("/super-admin/approvals/{$request->id}/approve")->assertSessionHas('error');
        $this->assertSame('pending', $request->fresh()->status);

        $this->actingAs($this->owner())->post("/super-admin/approvals/{$request->id}/deny", ['note' => 'Not yet'])->assertSessionHas('success');
        $this->assertSame('denied', $request->fresh()->status);
        $this->assertSame('Not yet', $request->fresh()->decision_note);

        $this->actingAs($asker)->delete("/super-admin/schools/{$a->id}", $body);
        $this->assertNotNull(School::find($a->id));
    }

    public function test_the_owner_never_waits_and_staff_without_the_permission_cannot_open_approvals(): void
    {
        config(['platform.second_approval' => true]);
        $a = $this->makeSchool('Greenfield College');

        $this->actingAs($this->owner())->delete("/super-admin/schools/{$a->id}", ['reason' => 'Closing the account as asked', 'confirm_name' => 'Greenfield College']);
        $this->assertNull(School::find($a->id));
        $this->assertSame(0, PlatformApproval::count());

        $this->actingAs($this->staff('platform-support'))->get('/super-admin/approvals')->assertForbidden();
        $this->actingAs($this->staff('platform-support', ['platform.approvals.manage'], 'Approver'))->get('/super-admin/approvals')->assertOk();
    }

    public function test_the_approvals_page_shows_what_is_waiting(): void
    {
        config(['platform.second_approval' => true]);
        $a = $this->makeSchool('Greenfield College');
        $this->actingAs($this->staff('platform-support', ['platform.schools.delete']))
            ->delete("/super-admin/schools/{$a->id}", ['reason' => 'The school asked in writing', 'confirm_name' => 'Greenfield College']);

        $this->actingAs($this->owner())->get('/super-admin/approvals')->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('switched_on', true)
                ->has('waiting', 1)
                ->where('waiting.0.target', 'Greenfield College')
                ->where('waiting.0.reason', 'The school asked in writing'));
    }
}
