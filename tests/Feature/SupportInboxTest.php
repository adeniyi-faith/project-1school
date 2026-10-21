<?php

namespace Tests\Feature;

use App\Models\SchoolNotification;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;

/** Schools ask for help, the platform team answers; schools only ever see their own requests and never private notes */
class SupportInboxTest extends SecurityTestCase
{
    private function owner(): User
    {
        $owner = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $owner->assignRole('super-admin');

        return $owner;
    }

    public function test_a_school_admin_raises_a_request_and_the_team_answers(): void
    {
        $a = $this->makeSchool('School A');
        $admin = $this->makeUser($a, 'school-admin');

        $this->actingAs($admin)->post('/school/support', ['subject' => 'Cannot print report cards', 'category' => 'problem', 'priority' => 'urgent', 'body' => 'It shows a blank page'])->assertSessionHasNoErrors();
        $ticket = SupportTicket::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('open', $ticket->status);
        $this->assertSame($a->id, $ticket->school_id);

        $owner = $this->owner();
        $this->actingAs($owner)->get('/super-admin/support')->assertOk()->assertInertia(fn ($p) => $p->where('counts.open', 1)->where('tickets.data.0.school', 'School A'));

        $this->post("/super-admin/support/{$ticket->id}/reply", ['body' => 'Fixed, please try again'])->assertSessionHasNoErrors();
        $this->assertSame('answered', $ticket->fresh()->status);
        $this->assertSame($owner->id, $ticket->fresh()->assigned_to);
        $this->assertSame(1, SchoolNotification::withoutGlobalScopes()->where('user_id', $admin->id)->where('type', 'support')->count());

        // The school replying puts it back in the team's queue
        $this->actingAs($admin)->post("/school/support/{$ticket->id}/reply", ['body' => 'Works now, thanks'])->assertSessionHasNoErrors();
        $this->assertSame('open', $ticket->fresh()->status);
        $this->patch("/school/support/{$ticket->id}/close")->assertRedirect();
        $this->assertSame('closed', $ticket->fresh()->status);
    }

    public function test_private_notes_never_reach_the_school_and_do_not_notify_them(): void
    {
        $a = $this->makeSchool('School A');
        $admin = $this->makeUser($a, 'school-admin');
        $this->actingAs($admin)->post('/school/support', ['subject' => 'Billing', 'category' => 'billing', 'priority' => 'normal', 'body' => 'Invoice?']);
        $ticket = SupportTicket::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($this->owner())->post("/super-admin/support/{$ticket->id}/reply", ['body' => 'They are always late', 'internal' => true]);
        $this->assertSame('open', $ticket->fresh()->status);
        $this->assertSame(0, SchoolNotification::withoutGlobalScopes()->count());

        $this->actingAs($admin)->get("/school/support/{$ticket->id}")->assertInertia(fn ($p) => $p->has('messages', 1)->where('messages.0.body', 'Invoice?'));
        $this->assertSame(2, SupportMessage::withoutGlobalScopes()->count());
    }

    public function test_one_school_cannot_see_or_answer_anothers_request(): void
    {
        $a = $this->makeSchool('School A');
        $b = $this->makeSchool('School B');
        $this->actingAs($this->makeUser($a, 'school-admin'))->post('/school/support', ['subject' => 'Secret', 'category' => 'question', 'priority' => 'normal', 'body' => 'Hi']);
        $ticket = SupportTicket::withoutGlobalScopes()->firstOrFail();

        $adminB = $this->makeUser($b, 'school-admin');
        $this->actingAs($adminB)->get("/school/support/{$ticket->id}")->assertNotFound();
        $this->post("/school/support/{$ticket->id}/reply", ['body' => 'x'])->assertNotFound();
        $this->get('/school/support')->assertInertia(fn ($p) => $p->where('tickets.data', []));
    }

    public function test_only_the_platform_team_can_open_the_inbox_and_teachers_cannot_raise_requests(): void
    {
        $a = $this->makeSchool('School A');
        $this->actingAs($this->makeUser($a, 'school-admin'))->get('/super-admin/support')->assertForbidden();
        $this->actingAs($this->makeUser($a, 'teacher'))->get('/school/support')->assertForbidden();
        $this->post('/school/support', ['subject' => 's', 'category' => 'question', 'priority' => 'normal', 'body' => 'b'])->assertForbidden();
    }

    public function test_assigning_needs_a_platform_team_member(): void
    {
        $a = $this->makeSchool('School A');
        $teacher = $this->makeUser($a, 'teacher');
        $this->actingAs($this->makeUser($a, 'school-admin'))->post('/school/support', ['subject' => 's', 'category' => 'question', 'priority' => 'normal', 'body' => 'b']);
        $ticket = SupportTicket::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($this->owner())->patch("/super-admin/support/{$ticket->id}/assign", ['assigned_to' => $teacher->id])->assertSessionHasErrors('assigned_to');
        $this->assertNull($ticket->fresh()->assigned_to);
    }
}
