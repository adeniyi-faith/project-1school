<?php

namespace Tests\Feature\Correctness;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Security\SecurityTestCase;

/** Who can sign in, and what school admins can and cannot do to staff accounts. */
class LoginAccountsCorrectnessTest extends SecurityTestCase
{
    private function account(string $role, string $status = 'active', string $email = 'someone@school.test'): User
    {
        $school = \App\Models\School::first() ?? $this->makeSchool('Login School');
        $u = User::factory()->create(['school_id' => $school->id, 'email' => $email, 'status' => $status, 'password' => Hash::make('Correct-Horse-9')]);
        $u->assignRole($role);

        return $u;
    }

    public function test_the_right_password_signs_in_and_a_wrong_one_does_not(): void
    {
        $this->account('teacher');
        $this->post('/login', ['email' => 'someone@school.test', 'password' => 'Wrong-Pass-1'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => 'someone@school.test', 'password' => 'Correct-Horse-9'])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_a_suspended_or_inactive_account_cannot_sign_in_even_with_the_right_password(): void
    {
        $this->account('teacher', 'suspended', 'sus@school.test');
        $this->account('teacher', 'inactive', 'off@school.test');
        foreach (['sus@school.test', 'off@school.test'] as $email) {
            $this->post('/login', ['email' => $email, 'password' => 'Correct-Horse-9']);
            $this->assertGuest();
        }
    }

    public function test_suspending_someone_who_is_already_signed_in_cuts_them_off(): void
    {
        $u = $this->account('teacher');
        $this->actingAs($u)->get('/school/students')->assertOk();
        $u->update(['status' => 'suspended']);
        $this->actingAs($u->fresh())->get('/school/students')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_a_school_admin_cannot_delete_suspend_or_demote_their_own_account(): void
    {
        $admin = $this->account('school-admin', 'active', 'boss@school.test');
        $this->actingAs($admin);
        $this->delete("/school/settings/admins/{$admin->id}")->assertSessionHasErrors('user');
        $this->patch("/school/settings/admins/{$admin->id}/suspend")->assertSessionHasErrors('user');
        $this->put("/school/settings/admins/{$admin->id}", ['name' => 'Boss', 'email' => 'boss@school.test', 'role' => 'teacher', 'status' => 'active'])->assertSessionHasErrors('user');
        $fresh = $admin->fresh();
        $this->assertSame('active', $fresh->status);
        $this->assertTrue($fresh->hasRole('school-admin'));
        $this->assertNull($fresh->deleted_at);
    }

    public function test_one_admin_may_switch_another_admin_off_while_a_working_one_remains(): void
    {
        $a = $this->account('school-admin', 'active', 'a@school.test');
        $b = $this->account('school-admin', 'active', 'b@school.test');
        $this->actingAs($a);
        // Two admins: one may switch the other off
        $this->patch("/school/settings/admins/{$b->id}/suspend")->assertSessionHasNoErrors();
        $this->assertSame('suspended', $b->fresh()->status);
    }

    public function test_creating_a_login_saves_the_role_and_a_duplicate_email_is_refused(): void
    {
        $admin = $this->account('school-admin', 'active', 'boss@school.test');
        $this->actingAs($admin);
        $this->post('/school/settings/admins', ['name' => 'Mrs Bello', 'email' => 'bello@school.test', 'password' => 'LongEnough1', 'role' => 'accountant', 'status' => 'active'])->assertSessionHasNoErrors();
        $made = User::where('email', 'bello@school.test')->first();
        $this->assertTrue($made->hasRole('accountant'));
        $this->assertSame($admin->school_id, $made->school_id);
        $this->assertTrue(Hash::check('LongEnough1', $made->password));

        $this->post('/school/settings/admins', ['name' => 'Twin', 'email' => 'bello@school.test', 'password' => 'LongEnough1', 'role' => 'teacher', 'status' => 'active'])->assertSessionHasErrors('email');
        $this->post('/school/settings/admins', ['name' => 'Short', 'email' => 'short@school.test', 'password' => '1234567', 'role' => 'teacher', 'status' => 'active'])->assertSessionHasErrors('password');
        $this->post('/school/settings/admins', ['name' => 'Sneaky', 'email' => 'sneak@school.test', 'password' => 'LongEnough1', 'role' => 'super-admin', 'status' => 'active'])->assertSessionHasErrors('role');
        $this->assertSame(0, User::where('email', 'sneak@school.test')->count());
    }
}
