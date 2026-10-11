<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;

/** The platform owner can step inside any school and work there; nobody else can */
class SuperAdminSchoolAccessTest extends SecurityTestCase
{
    private function student(int $schoolId, string $name): Student
    {
        $class = \App\Models\SchoolClass::withoutGlobalScopes()->firstOrCreate(['school_id' => $schoolId, 'name' => 'JSS 1'], ['numeric_name' => 7]);

        return Student::withoutGlobalScopes()->create(['school_id' => $schoolId, 'class_id' => $class->id, 'first_name' => $name, 'last_name' => 'X', 'admission_no' => $name, 'gender' => 'male', 'status' => 'active']);
    }

    private function owner(): User
    {
        $owner = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $owner->assignRole('super-admin');

        return $owner;
    }

    public function test_school_pages_send_the_owner_to_choose_a_school_first(): void
    {
        $this->actingAs($this->owner())->get('/school/students')->assertRedirect(route('super-admin.schools.index'));
        $this->get('/super-admin/schools')->assertOk();
    }

    public function test_the_owner_works_inside_the_chosen_school_only_and_can_leave(): void
    {
        $a = $this->makeSchool('School A');
        $b = $this->makeSchool('School B');
        $this->student($a->id, 'Ada');
        $this->student($b->id, 'Bola');

        $owner = $this->owner();
        $this->actingAs($owner)->post("/super-admin/schools/{$b->id}/enter")->assertRedirect('/school/reports/dashboard');

        $this->get('/school/students')->assertOk()->assertInertia(fn ($p) => $p
            ->where('students.meta.total', 1)->where('students.data.0.first_name', 'Bola')->where('acting_school.name', 'School B'));

        // Things made while inside belong to that school, and the account itself is never changed
        $this->post('/school/classes', ['name' => 'JSS 2', 'numeric_name' => 8])->assertSessionHasNoErrors();
        $this->assertSame($b->id, \App\Models\SchoolClass::withoutGlobalScopes()->where('name', 'JSS 2')->value('school_id'));
        $this->assertNull($owner->fresh()->school_id);

        $this->post('/super-admin/leave-school')->assertRedirect(route('super-admin.schools.index'));
        $this->get('/school/students')->assertRedirect(route('super-admin.schools.index'));
        $this->assertCount(2, Student::all());
    }

    public function test_only_the_owner_can_enter_a_school(): void
    {
        $a = $this->makeSchool('School A');
        $admin = $this->makeUser($a, 'school-admin');
        $this->actingAs($admin)->post("/super-admin/schools/{$a->id}/enter")->assertForbidden();
        // And a school admin can never be made to see another school by a leftover session value
        $b = $this->makeSchool('School B');
        $this->student($b->id, 'Bola');
        $this->withSession(['acting_school_id' => $b->id])->get('/school/students')->assertInertia(fn ($p) => $p->where('students.meta.total', 0));
    }

    public function test_the_page_title_is_never_the_framework_default(): void
    {
        $this->get('/login')->assertOk()->assertSee('<title inertia>SchoolRuns</title>', false);
    }
}
