<?php

namespace Tests\Feature\Security;

class UserAccountPermissionTest extends SecurityTestCase
{
    private function newAdminPayload(): array
    {
        return [
            'name'     => 'Sneaky Teacher',
            'email'    => 'sneaky@example.test',
            'password' => 'password123',
            'role'     => 'school-admin',
            'status'   => 'active',
        ];
    }

    public function test_teacher_cannot_create_a_school_admin_account(): void
    {
        $school = $this->makeSchool('School A');

        $this->actingAs($this->makeUser($school, 'teacher'))
            ->post(route('school.settings.admins.store'), $this->newAdminPayload())
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.test']);
    }

    public function test_teacher_cannot_promote_themselves_to_admin(): void
    {
        $school = $this->makeSchool('School A');
        $teacher = $this->makeUser($school, 'teacher');

        $this->actingAs($teacher)
            ->put(route('school.settings.admins.update', $teacher->id), [
                'name' => $teacher->name, 'email' => $teacher->email,
                'role' => 'school-admin', 'status' => 'active',
            ])
            ->assertForbidden();

        $this->assertTrue($teacher->fresh()->hasRole('teacher'));
        $this->assertFalse($teacher->fresh()->hasRole('school-admin'));
    }

    public function test_teacher_cannot_open_the_user_list(): void
    {
        $school = $this->makeSchool('School A');

        $this->actingAs($this->makeUser($school, 'teacher'))
            ->get(route('school.settings.admins'))
            ->assertForbidden();
    }

    public function test_accountant_cannot_create_accounts(): void
    {
        $school = $this->makeSchool('School A');

        $this->actingAs($this->makeUser($school, 'accountant'))
            ->post(route('school.settings.admins.store'), $this->newAdminPayload())
            ->assertForbidden();
    }

    public function test_principal_cannot_create_accounts(): void
    {
        $school = $this->makeSchool('School A');

        $this->actingAs($this->makeUser($school, 'principal'))
            ->post(route('school.settings.admins.store'), $this->newAdminPayload())
            ->assertForbidden();
    }

    public function test_school_admin_can_create_accounts(): void
    {
        $school = $this->makeSchool('School A');

        $this->actingAs($this->makeUser($school, 'school-admin'))
            ->post(route('school.settings.admins.store'), $this->newAdminPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'sneaky@example.test', 'school_id' => $school->id]);
    }

    public function test_school_admin_can_still_delete_accounts(): void
    {
        $school = $this->makeSchool('School A');
        $victim = $this->makeUser($school, 'teacher');

        $this->actingAs($this->makeUser($school, 'school-admin'))
            ->delete(route('school.settings.admins.destroy', $victim->id))
            ->assertRedirect();
    }
}
