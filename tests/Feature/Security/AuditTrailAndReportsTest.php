<?php

namespace Tests\Feature\Security;

use App\Models\Exam;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\FeePayment;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class AuditTrailAndReportsTest extends SecurityTestCase
{
    private function makeClassWithStudent($school, string $name = 'Class 1'): array
    {
        $class = SchoolClass::withoutGlobalScopes()->create(['school_id' => $school->id, 'name' => $name]);
        $student = Student::withoutGlobalScopes()->create([
            'school_id' => $school->id, 'class_id' => $class->id, 'first_name' => 'Ada', 'last_name' => 'Obi',
            'gender' => 'female', 'admission_no' => 'A' . random_int(1000, 9999), 'status' => 'active',
            'date_of_birth' => '2015-01-01', 'admission_date' => '2024-09-01',
        ]);

        return [$class, $student];
    }

    public function test_changing_a_mark_leaves_an_audit_entry_for_that_school(): void
    {
        $school = $this->makeSchool('School A');
        $admin = $this->makeUser($school, 'school-admin');
        [$class, $student] = $this->makeClassWithStudent($school);
        $subject = Subject::withoutGlobalScopes()->create(['school_id' => $school->id, 'class_id' => $class->id, 'name' => 'Maths', 'full_marks' => 100, 'pass_marks' => 40]);
        $exam = Exam::withoutGlobalScopes()->create(['school_id' => $school->id, 'class_id' => $class->id, 'name' => 'Term 1', 'type' => 'mid_term', 'status' => 'published']);

        $this->actingAs($admin);
        $mark = Mark::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'subject_id' => $subject->id, 'marks_obtained' => 50]);
        $mark->update(['marks_obtained' => 80]);

        $entries = Activity::where('log_name', 'results')->get();
        $this->assertCount(2, $entries);
        $this->assertSame($school->id, $entries->last()->properties['school_id']);
        $this->assertSame($admin->id, $entries->last()->causer_id);
        $this->assertEquals(80, $entries->last()->attribute_changes['attributes']['marks_obtained']);
        $this->assertEquals(50, $entries->last()->attribute_changes['old']['marks_obtained']);
    }

    public function test_fee_payments_and_user_changes_are_logged_and_passwords_are_not(): void
    {
        $school = $this->makeSchool('School A');
        $admin = $this->makeUser($school, 'school-admin');
        [$class, $student] = $this->makeClassWithStudent($school);
        $category = FeeCategory::withoutGlobalScopes()->create(['school_id' => $school->id, 'name' => 'Tuition', 'type' => 'tuition']);
        $structure = FeeStructure::withoutGlobalScopes()->create(['school_id' => $school->id, 'class_id' => $class->id, 'fee_category_id' => $category->id, 'amount' => 1000]);

        $this->actingAs($admin);
        FeePayment::create(['student_id' => $student->id, 'fee_structure_id' => $structure->id, 'amount_due' => 1000, 'amount_paid' => 1000, 'payment_date' => now(), 'status' => 'paid']);
        $user = User::factory()->create(['school_id' => $school->id]);
        $user->update(['name' => 'New Name']);

        $this->assertTrue(Activity::where('log_name', 'fees')->exists());
        $this->assertTrue(Activity::where('log_name', 'users')->exists());
        $this->assertStringNotContainsString('password', json_encode(Activity::where('log_name', 'users')->get()->toArray()));
    }

    public function test_role_change_is_logged_through_the_users_screen(): void
    {
        $school = $this->makeSchool('School A');
        $admin = $this->makeUser($school, 'school-admin');
        $teacher = $this->makeUser($school, 'teacher');

        $this->actingAs($admin)->put(route('school.settings.admins.update', $teacher->id), [
            'name' => $teacher->name, 'email' => $teacher->email, 'role' => 'accountant', 'status' => 'active',
        ])->assertRedirect();

        $entry = Activity::where('description', 'Role changed')->firstOrFail();
        $this->assertSame('teacher', $entry->properties['old_role']);
        $this->assertSame('accountant', $entry->properties['role']);
    }

    public function test_audit_log_screen_only_shows_the_own_schools_history(): void
    {
        $a = $this->makeSchool('School A');
        $b = $this->makeSchool('School B');
        $adminA = $this->makeUser($a, 'school-admin');
        $adminB = $this->makeUser($b, 'school-admin');

        activity()->causedBy($adminA)->withProperties(['school_id' => $a->id])->log('A did something');
        activity()->causedBy($adminB)->withProperties(['school_id' => $b->id])->log('B did something');

        $response = $this->actingAs($adminA)->getJson(route('school.reports.audit-log'), ['X-Inertia' => 'true']);

        $descriptions = collect($response->json('props.logs.data'))->pluck('description');
        $this->assertTrue($descriptions->contains('A did something'));
        $this->assertFalse($descriptions->contains('B did something'));
    }

    public function test_academic_report_works_and_works_out_pass_and_average(): void
    {
        $school = $this->makeSchool('School A');
        $admin = $this->makeUser($school, 'school-admin');
        [$class, $s1] = $this->makeClassWithStudent($school);
        $s2 = Student::withoutGlobalScopes()->create([
            'school_id' => $school->id, 'class_id' => $class->id, 'first_name' => 'Bayo', 'last_name' => 'Eze',
            'gender' => 'male', 'admission_no' => 'B' . random_int(1000, 9999), 'status' => 'active',
            'date_of_birth' => '2015-01-01', 'admission_date' => '2024-09-01',
        ]);
        $subject = Subject::withoutGlobalScopes()->create(['school_id' => $school->id, 'class_id' => $class->id, 'name' => 'Maths', 'full_marks' => 100, 'pass_marks' => 40]);
        $exam = Exam::withoutGlobalScopes()->create(['school_id' => $school->id, 'class_id' => $class->id, 'name' => 'Term 1', 'type' => 'mid_term', 'status' => 'published']);

        $this->actingAs($admin);
        Mark::create(['exam_id' => $exam->id, 'student_id' => $s1->id, 'subject_id' => $subject->id, 'marks_obtained' => 80]); // pass
        Mark::create(['exam_id' => $exam->id, 'student_id' => $s2->id, 'subject_id' => $subject->id, 'marks_obtained' => 20]); // fail

        $response = $this->getJson(route('school.reports.academic', ['exam_id' => $exam->id]), ['X-Inertia' => 'true'])->assertOk();

        $row = collect($response->json('props.classPerformance'))->firstWhere('class_name', 'Class 1');
        $this->assertSame(2, $row['total']);
        $this->assertSame(1, $row['passed']);
        $this->assertSame(1, $row['failed']);
        $this->assertEquals(50.0, $row['pass_rate']);
        $this->assertEquals(50.0, $row['avg_percent']);

        $subjectRow = collect($response->json('props.subjectPerformance'))->firstWhere('subject', 'Maths');
        $this->assertEquals(50.0, $subjectRow['avg_percent']);
    }
}
