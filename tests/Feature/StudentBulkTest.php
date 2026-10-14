<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Support\SimpleXlsx;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Security\SecurityTestCase;

class StudentBulkTest extends SecurityTestCase
{
    private function csv(array $rows): UploadedFile
    {
        $head = 'First name,Last name,Gender,Date of birth,Class,Arm,Admission number,Parent or guardian name,Relationship,Parent phone';
        $path = tempnam(sys_get_temp_dir(), 'csv').'.csv';
        file_put_contents($path, $head."\n".implode("\n", $rows));

        return new UploadedFile($path, 'students.csv', 'text/csv', null, true);
    }

    public function test_preview_flags_bad_rows_and_import_adds_only_good_ones_and_shares_a_parent(): void
    {
        $school = $this->makeSchool('Bulk School');
        $admin = $this->makeUser($school, 'school-admin');
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        Section::create(['school_id' => $school->id, 'class_id' => $class->id, 'name' => 'A']);

        $file = $this->csv([
            'Ada,Okafor,Female,21/03/2015,JSS 1,A,,Mrs Okafor,Mother,8031234567',
            'Bayo,Okafor,male,2013-05-02,jss 1,,,Mrs Okafor,Mother,08031234567',   // sibling: same parent phone
            'Chi,Eze,Female,2014-01-01,JSS 9,,,Mr Eze,Father,08099999999',          // no such class
            'Dayo,Ade,Boy,not-a-date,JSS 1,,,Mr Ade,Father,08088888888',            // bad date
        ]);

        $preview = $this->actingAs($admin)->post(route('school.students.import.preview'), ['file' => $file], ['Accept' => 'application/json'])->assertOk();
        $this->assertSame(2, $preview->json('ok'));
        $this->assertSame(2, $preview->json('bad'));

        $this->actingAs($admin)->post(route('school.students.import.store'), ['token' => $preview->json('token')])->assertRedirect(route('school.students.index'));

        $this->assertSame(2, Student::where('school_id', $school->id)->count());
        $this->assertSame(1, Guardian::where('school_id', $school->id)->count(), 'Brother and sister share one parent');
        $ada = Student::where('first_name', 'Ada')->first();
        $this->assertSame('2015-03-21', $ada->date_of_birth->toDateString());
        $this->assertNotEmpty($ada->admission_no);
        $this->assertSame('08031234567', Guardian::first()->phone);

        // Uploading the same file again: both good children are now duplicates
        $again = $this->actingAs($admin)->post(route('school.students.import.preview'), ['file' => $this->csv(['Ada,Okafor,Female,21/03/2015,JSS 1,A,,Mrs Okafor,Mother,8031234567'])], ['Accept' => 'application/json']);
        $this->assertSame(0, $again->json('ok'));
    }

    public function test_template_downloads_and_teachers_cannot_import(): void
    {
        $school = $this->makeSchool('Bulk Two');
        $this->actingAs($this->makeUser($school, 'school-admin'))->get(route('school.students.import.template'))->assertOk();
        $teacher = $this->makeUser($school, 'teacher');
        $h = ['X-Inertia' => 'true'];
        $this->actingAs($teacher)->get(route('school.students.import'), $h)->assertForbidden();
        $this->actingAs($teacher)->get(route('school.students.logins'), $h)->assertForbidden();
    }

    public function test_bulk_logins_make_accounts_for_students_and_parents_once(): void
    {
        $school = $this->makeSchool('Login School');
        $admin = $this->makeUser($school, 'school-admin');
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        $parent = Guardian::create(['school_id' => $school->id, 'name' => 'Mrs Okafor', 'relation' => 'Mother', 'phone' => '08031234567']);
        foreach (['Ada', 'Bayo'] as $n) {
            Student::create(['school_id' => $school->id, 'class_id' => $class->id, 'guardian_id' => $parent->id, 'first_name' => $n, 'last_name' => 'Okafor', 'gender' => 'female', 'status' => 'active', 'category' => 'general']);
        }

        $start = $this->actingAs($admin)->postJson(route('school.students.logins.start'), ['class_id' => $class->id, 'parents' => true])->assertOk();
        $token = $start->json('token');
        $step = $this->actingAs($admin)->postJson(route('school.students.logins.step', $token))->assertOk();
        $this->assertTrue($step->json('finished'));
        $this->assertSame(3, $step->json('made'), 'two students and one shared parent');

        $ada = Student::where('first_name', 'Ada')->first();
        $this->assertNotNull($ada->user_id);
        $this->assertTrue(User::find($ada->user_id)->hasRole('student'));
        $this->assertTrue(User::find($parent->fresh()->user_id)->hasRole('parent'));

        $this->actingAs($admin)->get(route('school.students.logins.pdf', $token))->assertOk();
        $this->actingAs($admin)->get(route('school.students.logins.excel', $token))->assertOk();

        // Running again makes nothing new unless asked to give new passwords
        $second = $this->actingAs($admin)->postJson(route('school.students.logins.start'), ['class_id' => $class->id, 'parents' => true])->json('token');
        $this->assertSame(0, $this->actingAs($admin)->postJson(route('school.students.logins.step', $second))->json('made'));
    }
}
