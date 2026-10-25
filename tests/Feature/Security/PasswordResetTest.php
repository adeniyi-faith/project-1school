<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

class PasswordResetTest extends SecurityTestCase
{
    public function test_a_link_is_emailed_to_a_real_account_only_but_the_answer_is_the_same(): void
    {
        Notification::fake();
        config(['app.login_driver' => 'local']);
        $user = User::factory()->create(['email' => 'real@example.test']);

        $this->post('/forgot-password', ['email' => 'real@example.test'])->assertSessionHas('success');
        $this->post('/forgot-password', ['email' => 'nobody@example.test'])->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class);
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_a_switched_off_account_gets_no_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'off@example.test', 'status' => 'suspended']);

        $this->post('/forgot-password', ['email' => 'off@example.test'])->assertSessionHas('success');

        Notification::assertNothingSent();
    }

    public function test_the_link_sets_a_new_password_once(): void
    {
        config(['app.login_driver' => 'local']);
        $user = User::factory()->create(['email' => 'real@example.test']);
        $token = Password::createToken($user);

        $this->get("/reset-password/{$token}?email=real@example.test")->assertOk();

        $this->post('/reset-password', [
            'token' => $token, 'email' => 'real@example.test',
            'password' => 'a-new-password', 'password_confirmation' => 'a-new-password',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('a-new-password', $user->fresh()->password));

        // The same link cannot be used a second time
        $this->post('/reset-password', [
            'token' => $token, 'email' => 'real@example.test',
            'password' => 'another-password', 'password_confirmation' => 'another-password',
        ])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('a-new-password', $user->fresh()->password));
    }

    public function test_a_short_or_unmatched_password_is_refused(): void
    {
        $user = User::factory()->create(['email' => 'real@example.test']);
        $token = Password::createToken($user);

        $this->post('/reset-password', ['token' => $token, 'email' => 'real@example.test', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');
        $this->post('/reset-password', ['token' => $token, 'email' => 'real@example.test', 'password' => 'long-enough-1', 'password_confirmation' => 'different-1'])
            ->assertSessionHasErrors('password');
    }
}
