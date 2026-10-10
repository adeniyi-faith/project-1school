<?php

namespace Tests\Feature\Security;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class RegistrationAndDemoTest extends SecurityTestCase
{
    private array $form = [
        'school_name' => 'Bright Future School',
        'state' => 'Lagos',
        'city' => 'Ikeja',
        'name' => 'Ada Obi',
        'email' => 'ada@brightfuture.test',
        'password' => 'a-strong-pass',
        'password_confirmation' => 'a-strong-pass',
    ];

    public function test_by_default_registration_and_sign_in_use_the_local_database_only(): void
    {
        Http::fake(); // any call to Supabase would be recorded

        $this->post('/register', $this->form)->assertRedirect(route('dashboard'));
        $user = User::where('email', 'ada@brightfuture.test')->firstOrFail();
        $this->assertNull($user->supabase_id);
        $this->assertNotSame('a-strong-pass', $user->password);

        // The new school is set up for Nigeria: naira and Lagos time
        $school = School::findOrFail($user->school_id);
        $this->assertSame('NGN', $school->currency);
        $this->assertSame('Africa/Lagos', $school->timezone);
        $this->assertSame('Lagos', $school->state);

        auth()->logout();

        $this->post('/login', ['email' => 'ada@brightfuture.test', 'password' => 'wrong-pass'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'ada@brightfuture.test', 'password' => 'a-strong-pass'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        Http::assertNothingSent();
    }

    public function test_an_unknown_email_or_placeholder_password_cannot_sign_in_locally(): void
    {
        $u = User::factory()->create(['email' => 'p@example.test']);
        // Store a raw, unhashed value, like the placeholder left by a demo or Supabase user
        \Illuminate\Support\Facades\DB::table('users')->where('id', $u->id)->update(['password' => 'not-hashed-placeholder']);

        $this->post('/login', ['email' => 'nobody@example.test', 'password' => 'whatever1'])
            ->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'p@example.test', 'password' => 'not-hashed-placeholder'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_make_admin_creates_a_super_admin_who_can_sign_in(): void
    {
        $this->artisan('make:admin', ['email' => 'boss@example.test'])
            ->expectsQuestion('Choose a password (at least 8 characters)', 'super-secret-1')
            ->assertSuccessful();

        $user = User::where('email', 'boss@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole('super-admin'));

        $this->post('/login', ['email' => 'boss@example.test', 'password' => 'super-secret-1'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_a_school_can_register_with_supabase_when_that_driver_is_on(): void
    {
        config(['app.login_driver' => 'supabase']);
        Http::fake(['*/auth/v1/admin/users' => Http::response(['id' => 'sb-123'], 200)]);

        $this->post('/register', $this->form)->assertRedirect(route('dashboard'));
        $this->assertSame('sb-123', User::where('email', 'ada@brightfuture.test')->firstOrFail()->supabase_id);
    }

    public function test_a_school_can_register_and_lands_signed_in_as_its_admin(): void
    {
        config(['app.login_driver' => 'supabase']);
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
        config(['app.login_driver' => 'supabase']);
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
