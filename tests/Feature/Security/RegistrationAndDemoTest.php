<?php

namespace Tests\Feature\Security;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class RegistrationAndDemoTest extends SecurityTestCase
{
    private array $form = [
        'school_name' => 'Bright Future School',
        'name' => 'Ada Obi',
        'email' => 'ada@brightfuture.test',
        'password' => 'a-strong-pass',
        'password_confirmation' => 'a-strong-pass',
    ];

    public function test_a_school_can_register_and_lands_signed_in_as_its_admin(): void
    {
        Http::fake(['*/auth/v1/admin/users' => Http::response(['id' => 'sb-123'], 200)]);

        $this->post('/register', $this->form)->assertRedirect(route('dashboard'));

        $school = School::where('name', 'Bright Future School')->firstOrFail();
        $user = User::where('email', 'ada@brightfuture.test')->firstOrFail();

        $this->assertSame($school->id, $user->school_id);
        $this->assertSame('sb-123', $user->supabase_id);
        $this->assertTrue($user->hasRole('school-admin'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_registering_with_an_email_already_in_use_is_refused(): void
    {
        Http::fake(['*' => Http::response(['id' => 'sb-1'], 200)]);
        $school = $this->makeSchool('Existing');
        User::factory()->create(['school_id' => $school->id, 'email' => 'ada@brightfuture.test']);

        $this->post('/register', $this->form)->assertSessionHasErrors('email');
        $this->assertSame(0, School::where('name', 'Bright Future School')->count());
    }

    public function test_a_supabase_failure_creates_no_school(): void
    {
        Http::fake(['*' => Http::response(['msg' => 'nope'], 422)]);

        $this->post('/register', $this->form)->assertSessionHasErrors('email');
        $this->assertSame(0, School::where('name', 'Bright Future School')->count());
        $this->assertGuest();
    }

    public function test_the_demo_is_off_by_default(): void
    {
        config(['app.demo_enabled' => false]);

        $this->get('/demo')->assertNotFound();
    }

    public function test_the_demo_signs_a_visitor_in_but_cannot_change_anything(): void
    {
        config(['app.demo_enabled' => true, 'app.demo_email' => 'demo@schoolruns.demo']);

        $this->get('/demo')->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertSame('demo@schoolruns.demo', auth()->user()->email);

        $this->from('/profile')
            ->put('/profile', ['name' => 'Hacked'])
            ->assertRedirect('/profile')
            ->assertSessionHas('error');

        $this->assertSame('Demo Admin', auth()->user()->fresh()->name);
    }

    public function test_a_normal_admin_is_not_blocked_by_the_demo_guard(): void
    {
        config(['app.demo_enabled' => true, 'app.demo_email' => 'demo@schoolruns.demo']);
        $school = $this->makeSchool('Real School');
        $admin = $this->makeUser($school, 'school-admin');

        $this->actingAs($admin)
            ->put('/profile', ['name' => 'New Name', 'email' => $admin->email])
            ->assertSessionMissing('error');
    }
}
