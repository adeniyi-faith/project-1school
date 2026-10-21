<?php

namespace Tests\Feature;

use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Subject;
use Tests\Feature\Security\SecurityTestCase;

/** Admins on the Staff list, with a role column, and assigning a staff member to classes and subjects */
class StaffAssignmentsTest extends SecurityTestCase
{
    private function staff($school, string $first, array $extra = []): Staff
    {
        return Staff::create(['school_id' => $school->id, 'first_name' => $first, 'gender' => 'male'] + $extra);
    }

    public function test_school_admin_logins_get_a_staff_line_and_a_role_column(): void
    {
        $school = $this->makeSchool('Admins School');
        $admin = $this->makeUser($school, 'school-admin');
        $this->actingAs($admin)->post('/school/settings/admins', [
            'name' => 'Second Admin', 'email' => 'second@example.test', 'password' => 'Secret1234', 'role' => 'school-admin', 'status' => 'active',
        ])->assertRedirect();

        $this->get('/school/staff')->assertInertia(fn ($p) => $p
            ->has('staff.data', 1)
            ->where('staff.data.0.role', 'school-admin'));

        $this->get('/school/staff?role=teacher')->assertInertia(fn ($p) => $p->has('staff.data', 0));
        $this->get('/school/staff?role=school-admin')->assertInertia(fn ($p) => $p->has('staff.data', 1));
    }

    public function test_the_migration_gives_existing_admins_a_staff_line_once(): void
    {
        $school = $this->makeSchool('Old School');
        $admin = $this->makeUser($school, 'school-admin');
        $this->assertSame(0, Staff::withoutGlobalScopes()->where('user_id', $admin->id)->count());

        $migration = require base_path('database/migrations/2026_11_23_000001_put_school_admins_on_staff_list.php');
        $migration->up();
        $migration->up();

        $this->assertSame(1, Staff::withoutGlobalScopes()->where('user_id', $admin->id)->count());
    }

    public function test_assigning_class_teacher_and_subjects_from_the_staff_page(): void
    {
        $school = $this->makeSchool('Assign School');
        $admin = $this->makeUser($school, 'school-admin');
        $ada = $this->staff($school, 'Ada');
        $bola = $this->staff($school, 'Bola');
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'JSS 1', 'numeric_name' => 7, 'class_teacher_staff_id' => $bola->id]);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Maths', 'code' => 'MTH', 'class_id' => $class->id]);
        $maths = ClassSubject::firstOrCreate(['school_id' => $school->id, 'class_id' => $class->id, 'subject_id' => $subject->id]);

        $this->actingAs($admin)->get("/school/staff/{$ada->id}")->assertInertia(fn ($p) => $p
            ->where('assign.0.name', 'JSS 1')->where('assign.0.class_teacher', 'Bola')->has('assign.0.subjects', 1));

        $this->put("/school/staff/{$ada->id}/assignments", ['class_ids' => [$class->id], 'line_ids' => [$maths->id]])->assertRedirect();
        $this->assertSame($ada->id, $class->fresh()->class_teacher_staff_id);
        $this->assertSame($ada->id, $maths->fresh()->teacher_id);

        // Saving with nothing ticked frees them again
        $this->put("/school/staff/{$ada->id}/assignments", [])->assertRedirect();
        $this->assertNull($class->fresh()->class_teacher_staff_id);
        $this->assertNull($maths->fresh()->teacher_id);
    }

    public function test_other_schools_classes_are_refused_and_teachers_cannot_assign(): void
    {
        $school = $this->makeSchool('Mine');
        $other = $this->makeSchool('Theirs');
        $admin = $this->makeUser($school, 'school-admin');
        $teacher = $this->makeUser($school, 'teacher');
        $ada = $this->staff($school, 'Ada');
        $foreign = SchoolClass::create(['school_id' => $other->id, 'name' => 'Foreign', 'numeric_name' => 1]);

        $this->actingAs($admin)->put("/school/staff/{$ada->id}/assignments", ['class_ids' => [$foreign->id]])->assertSessionHasErrors('class_ids.0');
        $this->actingAs($teacher)->put("/school/staff/{$ada->id}/assignments", [])->assertForbidden();
    }
}
