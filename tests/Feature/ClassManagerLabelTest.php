<?php

namespace Tests\Feature;

use App\Models\SchoolSetting;
use Tests\Feature\Security\SecurityTestCase;

/** A school can call the person in charge of a class "Manager", "Form Master" and so on */
class ClassManagerLabelTest extends SecurityTestCase
{
    public function test_admin_renames_the_label_and_the_classes_page_uses_it(): void
    {
        $school = $this->makeSchool('Label School');
        $admin = $this->makeUser($school, 'school-admin');

        $this->actingAs($admin)->get('/school/classes')->assertInertia(fn ($p) => $p->where('manager_label', 'Class Teacher'));

        $this->put('/school/class-manager-label', ['label' => ' Form Master '])->assertRedirect();

        $this->assertSame('Form Master', SchoolSetting::get($school->id, 'class_teacher_label'));
        $this->get('/school/classes')->assertInertia(fn ($p) => $p->where('manager_label', 'Form Master'));
    }

    public function test_a_blank_label_is_refused_and_a_teacher_cannot_change_it(): void
    {
        $school = $this->makeSchool('Label School 2');
        $admin = $this->makeUser($school, 'school-admin');
        $teacher = $this->makeUser($school, 'teacher');

        $this->actingAs($admin)->put('/school/class-manager-label', ['label' => ''])->assertSessionHasErrors('label');
        $this->actingAs($teacher)->put('/school/class-manager-label', ['label' => 'Boss'])->assertForbidden();
    }
}
