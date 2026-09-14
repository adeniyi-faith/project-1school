<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSupabaseAuth;
use Tests\TestCase;

class SchoolRegistrationTest extends TestCase
{
    use InteractsWithSupabaseAuth, RefreshDatabase;

    public function test_it_registers_a_school_and_makes_the_caller_its_admin(): void
    {
        $token = $this->tokenFor('11111111-1111-1111-1111-111111111111', 'admin@example.com');

        $response = $this->postJson('/api/schools', [
            'school_name' => 'Oscar Memorial School',
            'admin_name' => 'Ada Admin',
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertCreated()->assertJson([
            'school' => ['code' => '001', 'name' => 'Oscar Memorial School'],
            'user' => ['name' => 'Ada Admin', 'email' => 'admin@example.com', 'role' => 'school_admin'],
        ]);

        $this->assertDatabaseHas('schools', ['code' => '001', 'name' => 'Oscar Memorial School']);
        $this->assertDatabaseHas('users', [
            'supabase_user_id' => '11111111-1111-1111-1111-111111111111',
            'role' => UserRole::SchoolAdmin,
        ]);
    }

    public function test_school_codes_increment(): void
    {
        $this->postJson('/api/schools', [
            'school_name' => 'First School',
            'admin_name' => 'First Admin',
        ], ['Authorization' => 'Bearer '.$this->tokenFor('11111111-1111-1111-1111-111111111111', 'first@example.com')]);

        $response = $this->postJson('/api/schools', [
            'school_name' => 'Second School',
            'admin_name' => 'Second Admin',
        ], ['Authorization' => 'Bearer '.$this->tokenFor('22222222-2222-2222-2222-222222222222', 'second@example.com')]);

        $response->assertCreated()->assertJson(['school' => ['code' => '002']]);
    }

    public function test_a_supabase_login_cannot_register_a_second_school(): void
    {
        $token = $this->tokenFor('11111111-1111-1111-1111-111111111111', 'admin@example.com');

        $this->postJson('/api/schools', [
            'school_name' => 'Oscar Memorial School',
            'admin_name' => 'Ada Admin',
        ], ['Authorization' => "Bearer {$token}"]);

        $response = $this->postJson('/api/schools', [
            'school_name' => 'Another School',
            'admin_name' => 'Ada Admin',
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertStatus(409);
    }

    public function test_it_requires_a_valid_supabase_token(): void
    {
        $response = $this->postJson('/api/schools', [
            'school_name' => 'Oscar Memorial School',
            'admin_name' => 'Ada Admin',
        ]);

        $response->assertStatus(401);
    }

    public function test_it_validates_required_fields(): void
    {
        $token = $this->tokenFor('11111111-1111-1111-1111-111111111111', 'admin@example.com');

        $response = $this->postJson('/api/schools', [], ['Authorization' => "Bearer {$token}"]);

        $response->assertStatus(422)->assertJsonValidationErrors(['school_name', 'admin_name']);
    }
}
