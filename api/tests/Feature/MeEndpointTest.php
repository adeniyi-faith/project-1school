<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(string $supabaseUserId, string $email): string
    {
        config(['services.supabase.jwt_secret' => 'a-test-secret-that-is-long-enough-for-hs256']);

        return JWT::encode([
            'sub' => $supabaseUserId,
            'email' => $email,
            'exp' => now()->addHour()->timestamp,
        ], config('services.supabase.jwt_secret'), 'HS256');
    }

    public function test_it_reports_no_school_for_a_supabase_login_with_no_local_account(): void
    {
        $token = $this->tokenFor('11111111-1111-1111-1111-111111111111', 'new@example.com');

        $response = $this->getJson('/api/me', ['Authorization' => "Bearer {$token}"]);

        $response->assertOk()->assertJson([
            'supabase_user_email' => 'new@example.com',
            'school' => null,
        ]);
    }

    public function test_it_reports_the_school_for_a_registered_admin(): void
    {
        $school = School::factory()->create(['code' => '001', 'name' => 'Oscar Memorial School']);
        User::factory()->create([
            'supabase_user_id' => '22222222-2222-2222-2222-222222222222',
            'school_id' => $school->id,
            'role' => UserRole::SchoolAdmin,
        ]);
        $token = $this->tokenFor('22222222-2222-2222-2222-222222222222', 'admin@example.com');

        $response = $this->getJson('/api/me', ['Authorization' => "Bearer {$token}"]);

        $response->assertOk()->assertJson([
            'role' => 'school_admin',
            'school' => ['code' => '001', 'name' => 'Oscar Memorial School'],
        ]);
    }

    public function test_it_requires_a_valid_supabase_token(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }
}
