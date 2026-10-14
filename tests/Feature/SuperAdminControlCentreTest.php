<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;

/** The platform owner's school page: numbers, plan, admins, and suspension that really locks a school out */
class SuperAdminControlCentreTest extends SecurityTestCase
{
    private function owner(): User
    {
        $owner = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $owner->assignRole('super-admin');

        return $owner;
    }

    public function test_school_page_shows_numbers_plan_modules_and_admins(): void
    {
        $school = $this->makeSchool('School A');
        $admin = $this->makeUser($school, 'school-admin');

        $this->actingAs($this->owner())->get("/super-admin/schools/{$school->id}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('SuperAdmin/Schools/Show', false)
                ->where('usage.students', 0)
                ->where('admins.0.id', $admin->id)
                ->has('modules', 14)
                ->where('subscription', null));
    }

    public function test_owner_can_add_an_administrator_to_a_school(): void
    {
        $school = $this->makeSchool('School A');

        $this->actingAs($this->owner())->post("/super-admin/schools/{$school->id}/admins", [
            'name' => 'New Head', 'email' => 'head@example.com', 'password' => 'secret123',
        ])->assertSessionHasNoErrors();

        $created = User::where('email', 'head@example.com')->first();
        $this->assertSame($school->id, $created->school_id);
        $this->assertTrue($created->hasRole('school-admin'));
    }

    public function test_a_school_admin_cannot_use_the_owner_controls(): void
    {
        $school = $this->makeSchool('School A');
        $admin = $this->makeUser($school, 'school-admin');

        $this->actingAs($admin)->post("/super-admin/schools/{$school->id}/admins", [
            'name' => 'X', 'email' => 'x@example.com', 'password' => 'secret123',
        ])->assertForbidden();
    }

    public function test_suspending_a_school_signs_its_people_out(): void
    {
        $school = $this->makeSchool('School A');
        $admin = $this->makeUser($school, 'school-admin');
        $school->update(['status' => 'suspended']);

        $this->actingAs($admin)->get('/school/students')->assertRedirect(route('login'));
        $this->assertGuest();

        // The owner is never locked out
        $this->actingAs($this->owner())->get('/super-admin/schools')->assertOk();
    }


    public function test_owner_can_give_a_school_a_plan(): void
    {
        $school = $this->makeSchool('School A');
        $package = Package::create(['name' => 'Standard', 'slug' => 'standard', 'price_monthly' => 100, 'price_yearly' => 1000, 'is_active' => true]);

        $this->actingAs($this->owner())->post('/super-admin/subscriptions', [
            'school_id' => $school->id, 'package_id' => $package->id, 'start_date' => '2026-10-01', 'end_date' => '2027-10-01',
            'status' => 'active', 'is_trial' => false, 'amount_paid' => 0,
        ])->assertSessionHasNoErrors();

        $this->get("/super-admin/schools/{$school->id}")->assertInertia(fn ($p) => $p->component('SuperAdmin/Schools/Show', false)->where('subscription.package', 'Standard'));
    }
}
