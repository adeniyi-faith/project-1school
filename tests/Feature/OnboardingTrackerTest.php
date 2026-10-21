<?php

namespace Tests\Feature;

use App\Models\OnboardingNudge;
use App\Models\School;
use App\Models\SchoolNotification;
use App\Models\User;
use App\Support\SetupChecklist;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Security\SecurityTestCase;

/** The onboarding tracker: which schools look stuck, and the nudge */
class OnboardingTrackerTest extends SecurityTestCase
{
    private function owner(): User
    {
        $owner = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $owner->assignRole('super-admin');

        return $owner;
    }

    public function test_a_school_with_nobody_signed_in_after_three_days_is_stuck_but_a_new_one_is_not(): void
    {
        $old = $this->makeSchool('Old School');
        $old->forceFill(['created_at' => now()->subDays(5)])->save();
        $this->makeSchool('Brand New School');

        $this->actingAs($this->owner())->get('/super-admin/onboarding')->assertOk()->assertInertia(fn ($p) => $p
            ->where('counts.all', 2)->where('counts.stuck', 1)->where('counts.never', 1)
            ->where('schools.data.0.name', 'Old School')->where('schools.data.0.reasons', ['never_signed_in']));
    }

    public function test_a_school_that_signed_in_recently_is_fine_but_a_quiet_one_is_stuck(): void
    {
        $busy = $this->makeSchool('Busy School');
        $quiet = $this->makeSchool('Quiet School');
        foreach ([$busy, $quiet] as $s) {
            $s->forceFill(['created_at' => now()->subDays(10)])->save();
        }
        $this->makeUser($busy, 'school-admin')->update(['last_login_at' => now()->subDay()]);
        $this->makeUser($quiet, 'school-admin')->update(['last_login_at' => now()->subDays(9)]);

        $this->actingAs($this->owner())->get('/super-admin/onboarding?show=all')->assertInertia(fn ($p) => $p->where('counts.stuck', 1)->where('schools.data.0.name', 'Quiet School')->where('schools.data.0.reasons', ['quiet']));
    }

    public function test_suspended_schools_and_the_demo_school_are_left_out(): void
    {
        $this->makeSchool('Live School');
        $this->makeSchool('Gone School')->update(['status' => 'suspended']);
        $demo = $this->makeSchool('Demo');
        $demo->update(['slug' => \App\Support\DemoSchool::SLUG]);

        $this->actingAs($this->owner())->get('/super-admin/onboarding?show=all')->assertInertia(fn ($p) => $p->where('counts.all', 1));
    }

    public function test_the_checklist_counts_only_that_schools_own_data(): void
    {
        $a = $this->makeSchool('School A');
        $b = $this->makeSchool('School B');
        \App\Models\SchoolClass::withoutGlobalScopes()->create(['school_id' => $a->id, 'name' => 'JSS 1', 'numeric_name' => 7]);

        $done = fn (School $s) => collect(SetupChecklist::for($s->id)['items'])->firstWhere('key', 'classes')['done'];
        $this->assertTrue($done($a));
        $this->assertFalse($done($b));
    }

    public function test_a_nudge_goes_to_admins_is_logged_and_cannot_be_repeated_at_once(): void
    {
        Mail::fake();
        $a = $this->makeSchool('School A');
        $a->forceFill(['created_at' => now()->subDays(5)])->save();
        $admin = $this->makeUser($a, 'school-admin');
        $this->makeUser($a, 'teacher');
        $owner = $this->owner();
        $this->actingAs($owner);

        $this->getJson("/super-admin/onboarding/{$a->id}/draft")->assertOk()->assertJsonPath('message', fn ($m) => str_contains($m, 'School A'));
        $this->post("/super-admin/onboarding/{$a->id}/nudge", ['message' => 'Hello, need a hand?'])->assertSessionHas('success');

        $this->assertSame([$admin->id], SchoolNotification::withoutGlobalScopes()->pluck('user_id')->all());
        $this->assertSame(1, OnboardingNudge::withoutGlobalScopes()->count());

        $this->post("/super-admin/onboarding/{$a->id}/nudge", ['message' => 'Hello again please'])->assertSessionHas('error');
        $this->assertSame(1, OnboardingNudge::withoutGlobalScopes()->count());
    }

    public function test_only_the_platform_team_can_use_it(): void
    {
        $a = $this->makeSchool('School A');
        $this->actingAs($this->makeUser($a, 'school-admin'))->get('/super-admin/onboarding')->assertForbidden();
        $this->post("/super-admin/onboarding/{$a->id}/nudge", ['message' => 'Hello, need a hand?'])->assertForbidden();
    }
}
