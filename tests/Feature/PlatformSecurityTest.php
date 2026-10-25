<?php

namespace Tests\Feature;

use App\Models\PlatformSecurity;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Support\PlatformSecurityRules;
use App\Support\Totp;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Security\SecurityTestCase;

/** Second sign-in step, allowed networks, forced password change and sessions for the platform team */
class PlatformSecurityTest extends SecurityTestCase
{
    private function person(string $role = 'super-admin', string $password = 'password123'): User
    {
        $u = User::factory()->create(['school_id' => null, 'status' => 'active', 'password' => $password]);
        $u->assignRole($role);
        if ($role !== 'super-admin') {
            $u->platformStaff()->create(['school_scope' => 'all']);
        }

        return $u;
    }

    private function enrol(User $u): string
    {
        $secret = Totp::secret();
        PlatformSecurity::for($u)->update(['totp_secret' => $secret, 'totp_confirmed_at' => now()]);

        return $secret;
    }

    public function test_totp_matches_the_published_rfc_6238_examples(): void
    {
        $secret = Totp::base32Encode('12345678901234567890');
        // RFC 6238 appendix B, SHA-1, last 6 of the 8-digit codes
        $this->assertSame('287082', Totp::code($secret, intdiv(59, 30)));
        $this->assertSame('081804', Totp::code($secret, intdiv(1111111109, 30)));
        $this->assertSame('050471', Totp::code($secret, intdiv(1111111111, 30)));
        $this->assertSame('005924', Totp::code($secret, intdiv(1234567890, 30)));
        $this->assertSame('279037', Totp::code($secret, intdiv(2000000000, 30)));
    }

    public function test_a_code_is_refused_when_wrong_or_already_used(): void
    {
        $secret = Totp::secret();
        $now = 1700000000;
        $code = Totp::code($secret, Totp::step($now));

        $step = Totp::verify($secret, $code, null, $now);
        $this->assertSame(Totp::step($now), $step);
        $this->assertNull(Totp::verify($secret, $code, $step, $now), 'same code twice');
        $this->assertNull(Totp::verify($secret, '123abc', null, $now), 'letters are never a code');
        $this->assertNotNull(Totp::verify($secret, Totp::code($secret, Totp::step($now) - 1), null, $now), 'one step of clock drift is allowed');
        $this->assertNull(Totp::verify($secret, Totp::code($secret, Totp::step($now) - 3), null, $now), 'too old');
    }

    public function test_sign_in_waits_for_the_code_when_the_app_is_set_up(): void
    {
        $u = $this->person('platform-support');
        $secret = $this->enrol($u);

        $this->post('/login', ['email' => $u->email, 'password' => 'password123'])->assertRedirect('/two-factor');
        $this->assertGuest();

        $this->post('/two-factor', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post('/two-factor', ['code' => Totp::code($secret, Totp::step())])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);
    }

    public function test_a_code_cannot_be_used_twice(): void
    {
        $u = $this->person();
        $secret = $this->enrol($u);
        $code = Totp::code($secret, Totp::step());

        $this->post('/login', ['email' => $u->email, 'password' => 'password123']);
        $this->post('/two-factor', ['code' => $code])->assertRedirect('/dashboard');
        $this->post('/logout');

        $this->post('/login', ['email' => $u->email, 'password' => 'password123']);
        $this->post('/two-factor', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_a_recovery_code_works_once(): void
    {
        $u = $this->person();
        $this->enrol($u);
        $codes = PlatformSecurityRules::makeRecoveryCodes($u);

        $this->post('/login', ['email' => $u->email, 'password' => 'password123']);
        $this->post('/two-factor', ['code' => $codes[0]])->assertRedirect('/dashboard');
        $this->post('/logout');

        $this->post('/login', ['email' => $u->email, 'password' => 'password123']);
        $this->post('/two-factor', ['code' => $codes[0]])->assertSessionHasErrors('code');
        $this->post('/two-factor', ['code' => $codes[1]])->assertRedirect('/dashboard');
        $this->assertSame(8, PlatformSecurityRules::recoveryLeft($u));
    }

    public function test_five_wrong_codes_end_the_attempt(): void
    {
        $u = $this->person();
        $this->enrol($u);
        $this->post('/login', ['email' => $u->email, 'password' => 'password123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/two-factor', ['code' => '111111']);
        }
        $this->post('/two-factor', ['code' => '111111']);
        $this->get('/two-factor')->assertRedirect('/login');
    }

    public function test_school_people_are_not_asked_for_a_code(): void
    {
        $school = $this->makeSchool('Greenfield College');
        $u = User::factory()->create(['school_id' => $school->id, 'status' => 'active', 'password' => 'password123']);
        $u->assignRole('teacher');

        $this->post('/login', ['email' => $u->email, 'password' => 'password123'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);
    }

    public function test_platform_people_must_set_up_the_app_when_it_is_required(): void
    {
        config(['platform.require_2fa' => true]);
        $u = $this->person('platform-support');

        $this->actingAs($u)->get('/super-admin/dashboard')->assertRedirect('/security');
        $this->get('/security')->assertOk();

        // They can finish setting up with the code their app shows
        $secret = PlatformSecurity::for($u)->fresh()->totp_secret;
        $this->post('/security/two-factor', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post('/security/two-factor', ['code' => Totp::code($secret, Totp::step())])->assertRedirect('/security');
        $this->assertTrue(PlatformSecurityRules::isEnrolled($u));
        $this->assertSame(10, PlatformSecurityRules::recoveryLeft($u));
        $this->get('/super-admin/dashboard')->assertOk();
    }

    public function test_the_setup_secret_is_kept_scrambled_in_the_database(): void
    {
        $u = $this->person();
        $secret = $this->enrol($u);
        $raw = DB::table('platform_security')->where('user_id', $u->id)->value('totp_secret');
        $this->assertNotSame($secret, $raw);
        $this->assertStringNotContainsString($secret, (string) $raw);
    }

    public function test_the_network_list_holds_staff_but_never_the_owner(): void
    {
        $staff = $this->person('platform-support');
        $owner = $this->person('super-admin');
        PlatformSetting::set(PlatformSecurityRules::ALLOWLIST_KEY, "41.58.0.0/16\n10.0.0.5", 'security');

        $this->post('/login', ['email' => $staff->email, 'password' => 'password123'], ['REMOTE_ADDR' => '8.8.8.8'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->withServerVariables(['REMOTE_ADDR' => '41.58.9.9'])->post('/login', ['email' => $staff->email, 'password' => 'password123'])->assertRedirect('/dashboard');
        $this->post('/logout');
        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->post('/login', ['email' => $owner->email, 'password' => 'password123'])->assertRedirect('/dashboard');
    }

    public function test_a_signed_in_staff_member_is_signed_out_when_their_network_stops_being_allowed(): void
    {
        $staff = $this->person('platform-support');
        $this->actingAs($staff);
        $this->get('/super-admin/dashboard')->assertOk();

        PlatformSetting::set(PlatformSecurityRules::ALLOWLIST_KEY, '203.0.113.7', 'security');
        $this->get('/super-admin/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_ranges_match_correctly(): void
    {
        $this->assertTrue(PlatformSecurityRules::ipMatches('41.58.200.1', '41.58.0.0/16'));
        $this->assertFalse(PlatformSecurityRules::ipMatches('41.59.0.1', '41.58.0.0/16'));
        $this->assertTrue(PlatformSecurityRules::ipMatches('10.0.0.5', '10.0.0.5'));
        $this->assertTrue(PlatformSecurityRules::ipMatches('192.168.1.130', '192.168.1.128/25'));
        $this->assertFalse(PlatformSecurityRules::ipMatches('192.168.1.100', '192.168.1.128/25'));
        $this->assertTrue(PlatformSecurityRules::ipMatches('2001:db8::1', '2001:db8::/32'));
        $this->assertFalse(PlatformSecurityRules::ipMatches('2001:db9::1', '2001:db8::/32'));
        $this->assertTrue(PlatformSecurityRules::validEntry('41.58.0.0/16'));
        $this->assertFalse(PlatformSecurityRules::validEntry('41.58.0.0/40'));
        $this->assertFalse(PlatformSecurityRules::validEntry('hello'));
    }

    public function test_only_the_owner_can_edit_the_network_list_and_a_typo_is_refused(): void
    {
        $this->actingAs($this->person('platform-support'))->put('/super-admin/security/networks', ['allow_list' => '1.2.3.4'])->assertForbidden();

        $this->actingAs($this->person('super-admin'));
        $this->put('/super-admin/security/networks', ['allow_list' => 'not an address'])->assertSessionHasErrors('allow_list');
        $this->assertSame([], PlatformSecurityRules::allowList());

        $this->put('/super-admin/security/networks', ['allow_list' => "41.58.0.0/16\n10.0.0.5"])->assertSessionHasNoErrors();
        $this->assertSame(['41.58.0.0/16', '10.0.0.5'], PlatformSecurityRules::allowList());
    }

    public function test_a_new_staff_member_must_change_the_password_they_were_given(): void
    {
        $owner = $this->person('super-admin');
        $this->actingAs($owner)->post('/super-admin/staff', [
            'name' => 'New Person', 'email' => 'new@adino.test', 'role' => 'platform-support', 'school_scope' => 'all', 'password' => 'givenpass123',
        ])->assertSessionHasNoErrors();
        $new = User::where('email', 'new@adino.test')->first();
        $this->assertTrue(PlatformSecurityRules::mustChangePassword($new));

        $this->post('/logout');
        $this->actingAs($new)->get('/super-admin/dashboard')->assertRedirect('/security');

        $this->put('/security/password', ['current_password' => 'wrong-one', 'password' => 'a-brand-new-pass1', 'password_confirmation' => 'a-brand-new-pass1'])
            ->assertSessionHasErrors('current_password');
        $this->put('/security/password', ['current_password' => 'givenpass123', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');
        $this->put('/security/password', ['current_password' => 'givenpass123', 'password' => 'a-brand-new-pass1', 'password_confirmation' => 'a-brand-new-pass1'])
            ->assertSessionHasNoErrors();

        $this->assertFalse(PlatformSecurityRules::mustChangePassword($new->fresh()));
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('a-brand-new-pass1', $new->fresh()->password));
        $this->get('/super-admin/dashboard')->assertOk();
    }

    public function test_the_owner_can_reset_two_factor_and_force_a_password_change(): void
    {
        $staff = $this->person('platform-support');
        $this->enrol($staff);
        $this->actingAs($this->person('super-admin'));

        $this->post("/super-admin/security/{$staff->id}/force-password")->assertSessionHasNoErrors();
        $this->assertTrue(PlatformSecurityRules::mustChangePassword($staff));

        $this->post("/super-admin/security/{$staff->id}/reset-two-factor")->assertSessionHasNoErrors();
        $this->assertFalse(PlatformSecurityRules::isEnrolled($staff));
    }

    public function test_the_owner_cannot_use_these_buttons_on_a_school_login(): void
    {
        $school = $this->makeSchool('Greenfield College');
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');
        $this->actingAs($this->person('super-admin'))->post("/super-admin/security/{$teacher->id}/sign-out")->assertNotFound();
    }

    public function test_the_command_line_can_reset_anyone_including_the_owner(): void
    {
        $owner = $this->person('super-admin');
        $this->enrol($owner);

        $this->artisan('platform:reset-2fa', ['email' => $owner->email])->assertSuccessful();
        $this->assertFalse(PlatformSecurityRules::isEnrolled($owner));
        $this->artisan('platform:reset-2fa', ['email' => 'nobody@example.com'])->assertFailed();
    }

    public function test_sessions_can_be_listed_and_ended_when_kept_in_the_database(): void
    {
        config(['session.driver' => 'database']);
        DB::statement('create table if not exists sessions (id varchar primary key, user_id integer null, ip_address varchar null, user_agent text null, payload text, last_activity integer)');
        $u = $this->person();
        $other = $this->person();
        DB::table('sessions')->insert([
            ['id' => 'aaa', 'user_id' => $u->id, 'ip_address' => '1.1.1.1', 'user_agent' => 'Mozilla Chrome Windows', 'payload' => '', 'last_activity' => time()],
            ['id' => 'bbb', 'user_id' => $u->id, 'ip_address' => '2.2.2.2', 'user_agent' => 'Mozilla Firefox Linux', 'payload' => '', 'last_activity' => time()],
            ['id' => 'ccc', 'user_id' => $other->id, 'ip_address' => '3.3.3.3', 'user_agent' => 'x', 'payload' => '', 'last_activity' => time()],
        ]);

        $this->assertCount(2, PlatformSecurityRules::sessionsFor($u, 'aaa'));
        $this->assertFalse(PlatformSecurityRules::endSession($u, 'ccc'), "cannot end someone else's session");
        $this->assertTrue(PlatformSecurityRules::endSession($u, 'bbb'));
        $this->assertSame(1, PlatformSecurityRules::signOutEverywhere($u));
        $this->assertSame(1, DB::table('sessions')->where('user_id', $other->id)->count());
    }

    public function test_school_logins_cannot_use_the_platform_security_page(): void
    {
        $school = $this->makeSchool('Greenfield College');
        $teacher = User::factory()->create(['school_id' => $school->id, 'password' => 'password123']);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)->get('/security')->assertForbidden();
        $this->put('/security/password', ['current_password' => 'password123', 'password' => 'a-brand-new-pass1', 'password_confirmation' => 'a-brand-new-pass1'])->assertForbidden();
    }

    public function test_the_owner_page_lists_staff_with_and_without_the_app(): void
    {
        $set = $this->person('platform-support');
        $this->enrol($set);
        $this->person('platform-finance');

        $people = collect($this->actingAs($this->person('super-admin'))->get('/super-admin/security')->assertOk()->viewData('page')['props']['people'])->keyBy('id');
        $this->assertCount(2, $people);
        $this->assertTrue($people[$set->id]['two_factor']);
        $this->assertSame(1, $people->where('two_factor', false)->count());
        $this->get('/security')->assertOk();
    }
}
