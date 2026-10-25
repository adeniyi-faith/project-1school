<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Support\Totp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class TwoFactorTest extends SecurityTestCase
{
    private const SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'; // the RFC 6238 test key "12345678901234567890"

    public function test_codes_match_the_published_test_values(): void
    {
        $this->assertSame('287082', Totp::code(self::SECRET, 59));
        $this->assertSame('081804', Totp::code(self::SECRET, 1111111109));
        $this->assertSame('005924', Totp::code(self::SECRET, 1234567890));
    }

    public function test_a_code_is_accepted_one_step_either_side_but_not_further(): void
    {
        $now = 1234567890;
        $this->assertNotNull(Totp::verify(self::SECRET, Totp::code(self::SECRET, $now), $now));
        $this->assertNotNull(Totp::verify(self::SECRET, Totp::code(self::SECRET, $now - 30), $now));
        $this->assertNotNull(Totp::verify(self::SECRET, Totp::code(self::SECRET, $now + 30), $now));
        $this->assertNull(Totp::verify(self::SECRET, Totp::code(self::SECRET, $now - 90), $now));
        $this->assertNull(Totp::verify(self::SECRET, 'abcdef', $now));
        $this->assertNull(Totp::verify(self::SECRET, '12345', $now));
    }

    private function userWithTwoFactor(): User
    {
        $user = User::factory()->create(['email' => 'two@example.test', 'status' => 'active', 'password' => 'secret-pass-1']);
        $user->forceFill(['two_factor_secret' => self::SECRET, 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => [Hash::make('AAAA-BBBB')]])->save();
        config(['app.login_driver' => 'local']);
        RateLimiter::clear('x');

        return $user;
    }

    public function test_the_password_alone_does_not_log_in_when_two_step_is_on(): void
    {
        $this->userWithTwoFactor();

        $this->post('/login', ['email' => 'two@example.test', 'password' => 'secret-pass-1'])->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
    }

    public function test_the_right_code_finishes_the_login_and_cannot_be_used_twice(): void
    {
        $user = $this->userWithTwoFactor();
        $this->post('/login', ['email' => 'two@example.test', 'password' => 'secret-pass-1']);

        $this->post('/two-factor-challenge', ['code' => Totp::code(self::SECRET)])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        auth()->logout();
        $this->post('/login', ['email' => 'two@example.test', 'password' => 'secret-pass-1']);
        $this->post('/two-factor-challenge', ['code' => Totp::code(self::SECRET)])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_a_wrong_code_is_refused_and_too_many_tries_are_blocked(): void
    {
        $this->userWithTwoFactor();
        $this->post('/login', ['email' => 'two@example.test', 'password' => 'secret-pass-1']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        }
        $this->post('/two-factor-challenge', ['code' => Totp::code(self::SECRET)])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_a_recovery_code_works_once(): void
    {
        $user = $this->userWithTwoFactor();
        $this->post('/login', ['email' => 'two@example.test', 'password' => 'secret-pass-1']);

        $this->post('/two-factor-challenge', ['recovery_code' => 'aaaa-bbbb'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame([], $user->fresh()->two_factor_recovery_codes);
    }

    public function test_the_challenge_page_needs_a_fresh_password_step(): void
    {
        $this->get('/two-factor-challenge')->assertRedirect(route('login'));
        $this->post('/two-factor-challenge', ['code' => '123456'])->assertRedirect(route('login'));
    }

    public function test_turning_it_on_needs_a_correct_code_and_gives_recovery_codes(): void
    {
        $user = User::factory()->create(['status' => 'active', 'password' => 'secret-pass-1']);
        $this->actingAs($user);

        $this->post('/security/start');
        $secret = $user->fresh()->two_factor_secret;
        $this->assertNotNull($secret);
        $this->assertFalse($user->fresh()->hasTwoFactor());

        $this->post('/security/confirm', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->hasTwoFactor());

        $this->post('/security/confirm', ['code' => Totp::code($secret)])->assertSessionHas('new_recovery_codes');
        $this->assertTrue($user->fresh()->hasTwoFactor());
        $this->assertCount(8, $user->fresh()->two_factor_recovery_codes);
    }

    public function test_turning_it_off_needs_the_password(): void
    {
        $user = $this->userWithTwoFactor();
        $this->actingAs($user);

        $this->delete('/security', ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->assertTrue($user->fresh()->hasTwoFactor());

        $this->delete('/security', ['password' => 'secret-pass-1'])->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->hasTwoFactor());
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_the_secret_is_never_sent_to_the_browser_in_the_user_data(): void
    {
        $user = $this->userWithTwoFactor();

        $this->assertArrayNotHasKey('two_factor_secret', $user->toArray());
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $user->toArray());
    }
}
