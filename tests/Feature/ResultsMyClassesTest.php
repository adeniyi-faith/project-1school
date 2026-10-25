<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Subject;
use Tests\Feature\Security\SecurityTestCase;

/** The term results list marks which classes are the teacher's own */
class ResultsMyClassesTest extends SecurityTestCase
{
    public function test_a_teacher_sees_their_own_classes_marked_and_an_admin_sees_all_as_theirs(): void
    {
        $school = $this->makeSchool('Results List School');
        $teacher = $this->makeUser($school, 'teacher');
        $staff = Staff::create(['school_id' => $school->id, 'user_id' => $teacher->id, 'first_name' => 'Ada', 'gender' => 'female']);
        $mine = SchoolClass::create(['school_id' => $school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        SchoolClass::create(['school_id' => $school->id, 'name' => 'JSS 2', 'numeric_name' => 8]);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Maths', 'code' => 'MTH', 'class_id' => $mine->id]);
        ClassSubject::firstOrCreate(['school_id' => $school->id, 'class_id' => $mine->id, 'subject_id' => $subject->id])->update(['teacher_id' => $staff->id]);
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-30', 'is_current' => true]);

        $this->actingAs($teacher)->get('/school/results')->assertInertia(fn ($p) => $p
            ->where('sheets.0.class_name', 'JSS 1')->where('sheets.0.mine', true)
            ->where('sheets.1.class_name', 'JSS 2')->where('sheets.1.mine', false));

        $admin = $this->makeUser($school, 'school-admin');
        $this->actingAs($admin)->get('/school/results')->assertInertia(fn ($p) => $p
            ->where('sheets.0.mine', true)->where('sheets.1.mine', true));
    }
}
