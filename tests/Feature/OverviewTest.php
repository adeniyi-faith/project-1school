<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\ResultSheet;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use Tests\Feature\Security\SecurityTestCase;

/** The principal's read-only class and teacher overview: who has not finished entering scores. */
class OverviewTest extends SecurityTestCase
{
    public function test_a_subject_with_no_scores_is_listed_as_unfinished_for_class_and_teacher(): void
    {
        $school = $this->makeSchool('Overview School');
        $principal = $this->makeUser($school, 'principal');
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2025/2026', 'start_date' => '2025-09-08', 'end_date' => '2026-07-24', 'is_current' => true]);
        $year->terms()->orderBy('sequence')->first()->makeCurrent();
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        foreach (['Ada', 'Bayo'] as $i => $name) {
            Student::create(['school_id' => $school->id, 'class_id' => $class->id, 'first_name' => $name, 'last_name' => 'Test', 'admission_no' => "O{$i}", 'gender' => 'female', 'status' => 'active', 'date_of_birth' => '2014-01-01']);
        }
        $subject = Subject::create(['school_id' => $school->id, 'class_id' => $class->id, 'name' => 'Mathematics', 'code' => 'M1']);
        $teacher = Staff::create(['school_id' => $school->id, 'emp_id' => 'EMP-1', 'first_name' => 'Tola', 'last_name' => 'Ade', 'status' => 'active', 'joining_date' => '2024-01-01', 'gender' => 'female']);
        ClassSubject::updateOrCreate(['class_id' => $class->id, 'subject_id' => $subject->id], ['school_id' => $school->id, 'teacher_id' => $teacher->id]);

        $this->actingAs($principal)->get('/school/overview')->assertOk()->assertInertia(fn ($p) => $p
            ->where('classes.0.name', 'JSS 1')
            ->where('classes.0.subjects', 1)
            ->where('classes.0.subjects_done', 0)
            ->where('classes.0.unfinished.0.subject', 'Mathematics')
            ->where('classes.0.unfinished.0.expected', 2)
            ->where('teachers.0.finished', 0)
            ->where('teachers.0.total', 1));
    }

    public function test_a_teacher_cannot_open_the_overview(): void
    {
        $teacher = $this->makeUser($this->makeSchool('Overview School'), 'teacher');

        $this->actingAs($teacher)->get('/school/overview')->assertForbidden();
    }
}
