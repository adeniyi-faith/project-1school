<?php

namespace Tests\Feature\Security;

use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class SecurityTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    protected function makeSchool(string $name): School
    {
        return School::create([
            'name'   => $name,
            'slug'   => str($name)->slug()->toString(),
            'email'  => str($name)->slug() . '@example.test',
            'status' => 'active',
        ]);
    }

    protected function makeUser(School $school, string $role): User
    {
        $user = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
