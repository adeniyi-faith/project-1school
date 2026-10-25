<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\Homework;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Timetable;
use Tests\Feature\Security\SecurityTestCase;

/** Parents see their own children's homework and timetable, read-only. */
class ParentClassworkTest extends SecurityTestCase
{
    public function test_parent_sees_homework_and_timetable_of_their_children_only(): void
    {
        $school = $this->makeSchool('Classwork School');
        $parent = $this->makeUser($school, 'parent');
        $class  = SchoolClass::create(['school_id' => $school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        $other  = SchoolClass::create(['school_id' => $school->id, 'name' => 'JSS 2', 'numeric_name' => 8]);
        $maths  = Subject::create(['school_id' => $school->id, 'class_id' => $class->id, 'name' => 'Mathematics', 'code' => 'MTH']);
        $guardian = Guardian::create(['school_id' => $school->id, 'user_id' => $parent->id, 'name' => 'Mrs Test', 'phone' => '08030000000']);
        Student::create([
            'school_id' => $school->id, 'admission_no' => 'A1', 'first_name' => 'Ada', 'last_name' => 'Obi', 'gender' => 'female',
            'date_of_birth' => '2014-01-01', 'class_id' => $class->id, 'guardian_id' => $guardian->id, 'status' => 'active',
        ]);

        $mine   = Homework::create(['school_id' => $school->id, 'class_id' => $class->id, 'subject_id' => $maths->id, 'title' => 'Fractions', 'due_date' => now()->addDays(2), 'is_active' => true]);
        Homework::create(['school_id' => $school->id, 'class_id' => $class->id, 'subject_id' => $maths->id, 'title' => 'Ancient', 'due_date' => now()->subDays(60), 'is_active' => true]);
        Homework::create(['school_id' => $school->id, 'class_id' => $other->id, 'subject_id' => $maths->id, 'title' => 'Other class', 'due_date' => now()->addDays(2), 'is_active' => true]);
        Timetable::create(['school_id' => $school->id, 'class_id' => $class->id, 'subject_id' => $maths->id, 'day_of_week' => 'monday', 'start_time' => '08:00', 'end_time' => '08:40']);

        $this->actingAs($parent)->get('/school/parent/classwork')->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('children', 1)
                ->has('children.0.homework', 1)
                ->where('children.0.homework.0.id', $mine->id)
                ->where('children.0.timetable.monday.0.subject', 'Mathematics'));
    }

    public function test_staff_cannot_open_the_parent_page(): void
    {
        $school = $this->makeSchool('Classwork School');
        $this->actingAs($this->makeUser($school, 'teacher'))->get('/school/parent/classwork')->assertForbidden();
    }
}
