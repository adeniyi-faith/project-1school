<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

class LoginProtectionTest extends SecurityTestCase
{
    public function test_repeated_wrong_passwords_get_blocked(): void
    {
        config(['app.login_driver' => 'supabase']);
        RateLimiter::clear('x');
        Http::fake(['*' => Http::response(['error' => 'invalid'], 400)]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'a@example.test', 'password' => 'wrong'])
                ->assertSessionHasErrors('email');
        }

        // 6th try: even a request that would reach Supabase is stopped first
        Http::fake(['*' => Http::response(['user' => ['id' => 'u1', 'email' => 'a@example.test']], 200)]);

        $this->post('/login', ['email' => 'a@example.test', 'password' => 'whatever'])
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Too many login attempts',
            session('errors')->first('email')
        );
        $this->assertGuest();
    }

    public function test_demo_accounts_are_hidden_by_default_even_on_test_domains(): void
    {
        config(['app.show_demo_accounts' => false]);

        $this->get('http://school.test/login', ['X-Inertia' => 'true'])
            ->assertJsonPath('props.showDemo', false)
            ->assertJsonPath('props.demoAccounts', []);
    }

    public function test_demo_accounts_show_only_when_switched_on(): void
    {
        config(['app.show_demo_accounts' => true]);

        $this->get('/login', ['X-Inertia' => 'true'])
            ->assertJsonPath('props.showDemo', true)
            ->assertJsonCount(7, 'props.demoAccounts');
    }
}
