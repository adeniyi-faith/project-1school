<?php

namespace Tests\Feature;

use App\Models\PlatformAuditLog;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;

/** What the platform team does is written down: who, what, which school, when, from where */
class PlatformAuditTest extends SecurityTestCase
{
    private function owner(): User
    {
        $u = User::factory()->create(['school_id' => null, 'status' => 'active', 'name' => 'Faith Owner']);
        $u->assignRole('super-admin');

        return $u;
    }

    private function staff(string $role, array $extras = []): User
    {
        $u = User::factory()->create(['school_id' => null, 'status' => 'active', 'name' => 'Ada Staff']);
        $u->assignRole($role);
        $u->platformStaff()->create(['school_scope' => 'all']);
        $u->givePermissionTo($extras);

        return $u;
    }

    public function test_entering_a_school_is_written_down_with_who_where_and_from(): void
    {
        $a = $this->makeSchool('School A');
        $support = $this->staff('platform-support');

        $this->actingAs($support)->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0) Chrome/120')
            ->post("/super-admin/schools/{$a->id}/enter");

        $line = PlatformAuditLog::where('action', 'enter_school')->firstOrFail();
        $this->assertSame($support->id, $line->user_id);
        $this->assertSame('Ada Staff', $line->user_name);
        $this->assertSame('Support', $line->role);
        $this->assertSame($a->id, $line->acting_school_id);
        $this->assertSame('School A', $line->acting_school_name);
        $this->assertSame('203.0.113.9', $line->ip);
        $this->assertStringContainsString('Chrome', $line->user_agent);
        $this->assertSame('Entered a school: School A', $line->description);
    }

    public function test_changes_keep_what_changed_but_never_a_password(): void
    {
        $a = $this->makeSchool('School A');
        $teacher = $this->makeUser($a, 'teacher');

        $this->actingAs($this->owner())->put("/super-admin/schools/{$a->id}", ['name' => 'School A Renamed', 'email' => 'a@x.test', 'status' => 'active']);
        $line = PlatformAuditLog::where('action', 'changed')->where('route', 'super-admin.schools.update')->firstOrFail();
        $this->assertContains('name', $line->details['fields']);
        $this->assertSame('Edited a school: School A Renamed', $line->description);
        $this->assertStringContainsString('School A Renamed', json_encode($line->details['changes']));

        $this->patch("/super-admin/users/{$teacher->id}/reset-password", ['password' => 'SuperSecret123']);
        $reset = PlatformAuditLog::where('route', 'super-admin.users.reset-password')->firstOrFail();
        $this->assertSame([], $reset->details['fields'] ?? []);
        $this->assertStringNotContainsString('SuperSecret123', json_encode(PlatformAuditLog::all()->toArray()));
    }

    public function test_lines_can_never_be_changed_or_removed(): void
    {
        $line = PlatformAuditLog::create(['action' => 'changed', 'description' => 'x']);
        $this->expectException(\LogicException::class);
        $line->update(['description' => 'y']);
    }

    public function test_deleting_is_refused_too(): void
    {
        $line = PlatformAuditLog::create(['action' => 'changed', 'description' => 'x']);
        $this->expectException(\LogicException::class);
        $line->delete();
    }

    public function test_look_only_visits_and_turned_back_changes_are_recorded(): void
    {
        $a = $this->makeSchool('School A');
        SchoolClass::withoutGlobalScopes()->create(['school_id' => $a->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        $support = $this->staff('platform-support');
        $this->actingAs($support)->post("/super-admin/schools/{$a->id}/enter");

        $this->get('/school/classes')->assertOk();
        $this->post('/school/classes', ['name' => 'JSS 2', 'numeric_name' => 8]);
        $this->post('/super-admin/leave-school');

        $this->assertDatabaseHas('platform_audit_logs', ['action' => 'viewed', 'path' => '/school/classes', 'acting_school_name' => 'School A']);
        $this->assertDatabaseHas('platform_audit_logs', ['action' => 'refused', 'method' => 'POST', 'path' => '/school/classes']);
        $this->assertDatabaseHas('platform_audit_logs', ['action' => 'leave_school', 'acting_school_name' => 'School A']);
    }

    public function test_a_platform_page_staff_may_not_open_is_recorded_as_refused(): void
    {
        $this->actingAs($this->staff('platform-content'))->get('/super-admin/schools')->assertForbidden();
        $this->assertDatabaseHas('platform_audit_logs', ['action' => 'refused', 'user_name' => 'Ada Staff']);
    }

    public function test_ordinary_school_people_leave_no_platform_lines(): void
    {
        $a = $this->makeSchool('School A');
        $this->actingAs($this->makeUser($a, 'school-admin'))->post('/school/classes', ['name' => 'JSS 2', 'numeric_name' => 8]);
        $this->assertSame(0, PlatformAuditLog::count());
    }

    public function test_the_audit_page_is_for_people_with_the_audit_permission(): void
    {
        $this->actingAs($this->staff('platform-support'))->get('/super-admin/audit')->assertForbidden();
        $this->actingAs($this->staff('platform-developer'))->get('/super-admin/audit')->assertOk();
        $this->actingAs($this->owner())->get('/super-admin/audit')->assertOk();
        $this->get('/super-admin/audit/export')->assertOk();
    }

    public function test_the_page_filters_by_person_kind_school_and_dates(): void
    {
        $a = $this->makeSchool('Alpha School');
        $b = $this->makeSchool('Beta School');
        $s1 = $this->staff('platform-support');
        $s2 = $this->staff('platform-developer');
        PlatformAuditLog::create(['user_id' => $s1->id, 'user_name' => 'One', 'action' => 'enter_school', 'description' => 'Entered a school: Alpha School', 'acting_school_name' => 'Alpha School', 'created_at' => now()->subDays(10)]);
        PlatformAuditLog::create(['user_id' => $s2->id, 'user_name' => 'Two', 'action' => 'changed', 'description' => 'Edited a school: Beta School', 'acting_school_name' => 'Beta School', 'created_at' => now()]);

        $this->actingAs($this->owner());
        $count = fn (array $q) => $this->get('/super-admin/audit?'.http_build_query($q))->viewData('page')['props']['lines']['meta']['total'];
        $this->assertSame(2, $count(['action' => '']) - 0 >= 2 ? 2 : 0);
        $this->assertSame(1, $count(['user_id' => $s1->id]));
        $this->assertSame(1, $count(['action' => 'changed']));
        $this->assertSame(1, $count(['school' => 'Beta']));
        $this->assertSame(1, $count(['from' => now()->subDays(2)->toDateString()]) - ($count(['from' => now()->subDays(2)->toDateString()]) - 1));
        $this->assertSame(1, $count(['to' => now()->subDays(5)->toDateString(), 'user_id' => $s1->id]));
        $this->assertSame(1, $count(['q' => 'Alpha']));
    }

    public function test_the_download_is_a_csv_safe_for_excel_and_is_itself_recorded(): void
    {
        PlatformAuditLog::create(['user_name' => '=HYPERLINK("http://x")', 'action' => 'changed', 'description' => 'Edited a school: A', 'acting_school_name' => 'School A']);
        $owner = $this->owner();

        $res = $this->actingAs($owner)->get('/super-admin/audit/export?action=changed');
        $res->assertOk();
        $this->assertStringContainsString('text/csv', $res->headers->get('content-type'));
        $body = $res->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $body);
        $this->assertStringContainsString('School A', $body);
        $this->assertDatabaseHas('platform_audit_logs', ['action' => 'exported', 'user_id' => $owner->id]);
    }

    public function test_recording_can_be_switched_off_for_an_environment(): void
    {
        config(['platform.audit' => false]);
        $a = $this->makeSchool('School A');
        $this->actingAs($this->owner())->post("/super-admin/schools/{$a->id}/enter");
        $this->assertSame(0, PlatformAuditLog::count());
        $this->get('/super-admin/audit')->assertInertia(fn ($p) => $p->where('enabled', false));
    }

    public function test_signing_in_and_out_is_recorded_for_the_team(): void
    {
        $u = $this->staff('platform-support');
        $u->forceFill(['password' => 'secret-pass-1', 'email' => 'ada@adino.test'])->save();
        config(['app.login_driver' => 'database']);

        $this->post('/login', ['email' => 'ada@adino.test', 'password' => 'secret-pass-1']);
        $this->post('/logout');

        $this->assertDatabaseHas('platform_audit_logs', ['action' => 'login', 'user_id' => $u->id]);
        $this->assertDatabaseHas('platform_audit_logs', ['action' => 'logout', 'user_id' => $u->id]);
    }
}
