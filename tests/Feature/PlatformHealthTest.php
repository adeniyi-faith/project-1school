<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;

/** The platform health page: only the platform owner sees it, and it reports instead of crashing */
class PlatformHealthTest extends SecurityTestCase
{
    private function owner(): User
    {
        $owner = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $owner->assignRole('super-admin');

        return $owner;
    }

    public function test_the_owner_sees_every_check(): void
    {
        $this->actingAs($this->owner())->get('/super-admin/health')->assertOk()->assertInertia(fn ($p) => $p
            ->component('SuperAdmin/Health/Index', false)
            ->has('report.checks', 9)
            ->where('report.checks.1.key', 'database')
            ->where('report.checks.1.status', 'ok'));
    }

    public function test_the_same_report_is_available_as_json(): void
    {
        $this->actingAs($this->owner())->getJson('/super-admin/health')->assertOk()
            ->assertJsonStructure(['checks' => [['key', 'label', 'status', 'summary', 'facts', 'notes']], 'summary', 'checked_at']);
    }

    public function test_nobody_else_can_open_it(): void
    {
        $school = $this->makeSchool('School A');
        $this->actingAs($this->makeUser($school, 'school-admin'))->get('/super-admin/health')->assertForbidden();
        auth()->logout();
        $this->get('/super-admin/health')->assertRedirect();
    }

    public function test_the_menu_item_is_offered_to_the_owner_only(): void
    {
        $owner = $this->owner();
        $this->assertContains('/super-admin/health', \App\Support\NavAccess::paths($owner));
        $admin = $this->makeUser($this->makeSchool('School A'), 'school-admin');
        $this->assertNotContains('/super-admin/health', \App\Support\NavAccess::paths($admin));
    }
}
