<?php

namespace Tests\Feature;

use App\Models\SchoolNote;
use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;

/** The School 360 page: one page of facts about a school, for the platform team only */
class School360Test extends SecurityTestCase
{
    private function owner(): User
    {
        $owner = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $owner->assignRole('super-admin');

        return $owner;
    }

    public function test_the_page_shows_figures_for_that_school_only(): void
    {
        $a = $this->makeSchool('School A');
        $b = $this->makeSchool('School B');
        $adminA = $this->makeUser($a, 'school-admin');
        $adminA->update(['last_login_at' => now()->subHour()]);
        $this->makeUser($b, 'school-admin');

        $this->actingAs($this->owner())->get("/super-admin/schools/{$a->id}")->assertOk()->assertInertia(fn ($p) => $p
            ->where('school.name', 'School A')
            ->where('overview.logins.active_7days', 1)
            ->where('overview.checklist.total', 10));
    }

    public function test_school_staff_cannot_open_it(): void
    {
        $a = $this->makeSchool('School A');
        $this->actingAs($this->makeUser($a, 'school-admin'))->get("/super-admin/schools/{$a->id}")->assertForbidden();
    }

    public function test_notes_tags_and_manager_are_saved_and_stay_with_their_school(): void
    {
        $a = $this->makeSchool('School A');
        $b = $this->makeSchool('School B');
        $owner = $this->owner();
        $this->actingAs($owner);

        $this->post("/super-admin/schools/{$a->id}/notes", ['body' => 'Called the bursar', 'pinned' => true])->assertSessionHasNoErrors();
        $this->patch("/super-admin/schools/{$a->id}/profile", ['tags' => [' VIP ', 'vip', 'Lagos'], 'account_manager_id' => $owner->id])->assertSessionHasNoErrors();

        $this->assertSame(['vip', 'lagos'], $a->fresh()->tags);
        $this->assertSame($owner->id, $a->fresh()->account_manager_id);

        $this->get("/super-admin/schools/{$a->id}")->assertInertia(fn ($p) => $p->where('overview.notes.0.body', 'Called the bursar')->where('overview.notes.0.pinned', true));
        $this->get("/super-admin/schools/{$b->id}")->assertInertia(fn ($p) => $p->where('overview.notes', []));

        // A note of school A cannot be deleted through school B's address
        $note = SchoolNote::withoutGlobalScopes()->first();
        $this->delete("/super-admin/schools/{$b->id}/notes/{$note->id}")->assertNotFound();
        $this->delete("/super-admin/schools/{$a->id}/notes/{$note->id}")->assertRedirect();
        $this->assertSame(0, SchoolNote::withoutGlobalScopes()->count());
    }

    public function test_the_account_manager_must_be_platform_staff(): void
    {
        $a = $this->makeSchool('School A');
        $teacher = $this->makeUser($a, 'teacher');

        $this->actingAs($this->owner())->patch("/super-admin/schools/{$a->id}/profile", ['account_manager_id' => $teacher->id])->assertSessionHasErrors('account_manager_id');
        $this->assertNull($a->fresh()->account_manager_id);
    }
}
