<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Services\SchoolLifecycleService;
use Tests\Feature\Security\SecurityTestCase;

/** Deleting a school waits a grace period */
class SchoolLifecycleTest extends SecurityTestCase
{
    private function owner(): User
    {
        $owner = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $owner->assignRole('super-admin');

        return $owner;
    }

    public function test_deletion_needs_a_reason_and_the_name_then_pauses_the_school_for_thirty_days(): void
    {
        $a = $this->makeSchool('School A');
        $owner = $this->owner();
        $this->actingAs($owner);

        $this->post("/super-admin/schools/{$a->id}/schedule-delete", ['reason' => 'short', 'confirm_name' => 'School A'])->assertSessionHasErrors('reason');
        $this->post("/super-admin/schools/{$a->id}/schedule-delete", ['reason' => 'Closed down for good', 'confirm_name' => 'Wrong name'])->assertSessionHasErrors('confirm_name');
        $this->assertNull($a->fresh()->delete_scheduled_at);

        $this->post("/super-admin/schools/{$a->id}/schedule-delete", ['reason' => 'Closed down for good', 'confirm_name' => 'school a'])->assertSessionHas('success');
        $a->refresh();
        $this->assertSame('suspended', $a->status);
        $this->assertSame('active', $a->status_before_delete);
        $this->assertEquals(now()->addDays(30)->toDateString(), $a->delete_scheduled_at->toDateString());
        $this->assertNull(School::onlyTrashed()->first(), 'nothing is deleted yet');

        // It cannot be switched back on while the deletion is pending
        $this->patch("/super-admin/schools/{$a->id}/activate")->assertSessionHas('error');
        $this->assertSame('suspended', $a->fresh()->status);
    }

    public function test_cancelling_puts_the_school_back_exactly_as_it_was(): void
    {
        $a = $this->makeSchool('School A');
        $this->actingAs($this->owner());
        $this->post("/super-admin/schools/{$a->id}/schedule-delete", ['reason' => 'Closed down for good', 'confirm_name' => 'School A']);
        $this->post("/super-admin/schools/{$a->id}/cancel-delete")->assertSessionHas('success');

        $a->refresh();
        $this->assertSame('active', $a->status);
        $this->assertNull($a->delete_scheduled_at);
        $this->assertNull($a->delete_reason);
    }

    public function test_after_the_waiting_period_the_school_is_hidden_not_wiped_and_can_be_restored(): void
    {
        $a = $this->makeSchool('School A');
        $admin = $this->makeUser($a, 'school-admin');
        $owner = $this->owner();
        $this->actingAs($owner);
        $this->post("/super-admin/schools/{$a->id}/schedule-delete", ['reason' => 'Closed down for good', 'confirm_name' => 'School A']);

        $this->assertSame(0, app(SchoolLifecycleService::class)->purgeDue(), 'not due yet');
        $a->forceFill(['delete_scheduled_at' => now()->subMinute()])->save();

        $this->get('/super-admin/schools')->assertOk()->assertInertia(fn ($p) => $p->where('deleted.0.name', 'School A')->where('stats.total', 0));
        $this->assertSame(1, School::onlyTrashed()->count());
        $this->assertSame(1, User::where('school_id', $a->id)->count(), 'its people are kept');

        $this->post("/super-admin/deleted-schools/{$a->id}/restore")->assertSessionHas('success');
        $a->refresh();
        $this->assertNull($a->deleted_at);
        $this->assertSame('suspended', $a->status);
        $this->assertNull($a->delete_scheduled_at);
    }

    public function test_the_old_instant_delete_no_longer_deletes(): void
    {
        $a = $this->makeSchool('School A');
        $this->actingAs($this->owner())->delete("/super-admin/schools/{$a->id}")->assertRedirect();
        $this->assertNull($a->fresh()->deleted_at);
    }

    public function test_only_the_platform_owner_can_do_these(): void
    {
        $a = $this->makeSchool('School A');
        $this->actingAs($this->makeUser($a, 'school-admin'));
        $this->post("/super-admin/schools/{$a->id}/schedule-delete", ['reason' => 'Closed down for good', 'confirm_name' => 'School A'])->assertForbidden();
    }

    public function test_people_of_a_school_that_was_deleted_cannot_sign_in(): void
    {
        $a = $this->makeSchool('School A');
        $admin = $this->makeUser($a, 'school-admin');
        $a->delete();

        $this->actingAs($admin)->get('/school/home')->assertRedirect('/login');
        $this->assertGuest();
    }
}
