<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentScheme;
use App\Models\Exam;
use App\Models\GradeScale;
use App\Models\GradingScheme;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\Term;
use App\Services\GradingService;
use App\Support\SchoolDefaults;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Security\SecurityTestCase;

/** Terms, score setups (CA1, CA2, Exam ...) and grade scales. */
class AcademicSetupTest extends SecurityTestCase
{
    public function test_a_new_school_starts_with_a_score_setup_and_the_waec_grade_scale(): void
    {
        $school = $this->makeSchool('Fresh School');

        $scheme = AssessmentScheme::withoutGlobalScopes()->where('school_id', $school->id)->sole();
        $this->assertTrue($scheme->is_default);
        $this->assertSame(['CA1', 'CA2', 'Exam'], $scheme->components()->pluck('short_name')->all());
        $this->assertEquals(100, $scheme->components()->sum('max_score'));

        $scale = GradingScheme::withoutGlobalScopes()->where('school_id', $school->id)->sole();
        $this->assertTrue($scale->is_default);
        $this->assertSame(
            ['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8', 'F9'],
            GradeScale::withoutGlobalScopes()->where('grading_scheme_id', $scale->id)->orderByDesc('min_marks')->pluck('grade')->all(),
        );
    }

    public function test_waec_bands_give_the_right_grade_at_each_boundary(): void
    {
        $school = $this->makeSchool('Boundary School');
        $grading = new GradingService($school->id);

        $expect = [100 => 'A1', 75 => 'A1', 74.5 => 'B2', 70 => 'B2', 65 => 'B3', 60 => 'C4', 55 => 'C5', 50 => 'C6', 49 => 'D7', 45 => 'D7', 40 => 'E8', 39.5 => 'F9', 0 => 'F9'];
        foreach ($expect as $score => $grade) {
            $this->assertSame($grade, $grading->calculate((float) $score, 100)['grade'], "score {$score}");
        }
    }

    public function test_a_new_school_year_gets_three_terms_or_the_number_the_school_chose(): void
    {
        $school = $this->makeSchool('Term School');
        $this->actingAs($this->makeUser($school, 'school-admin'));

        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23']);
        $this->assertSame(['First Term', 'Second Term', 'Third Term'], $year->terms()->pluck('name')->all());

        SchoolSetting::set($school->id, 'terms_per_year', 2, 'academic');
        $year2 = AcademicYear::create(['school_id' => $school->id, 'name' => '2027/2028', 'start_date' => '2027-09-06', 'end_date' => '2028-07-21']);
        $this->assertSame(['First Term', 'Second Term'], $year2->terms()->pluck('name')->all());
    }

    public function test_an_admin_adds_a_year_from_the_page_and_its_first_term_becomes_current(): void
    {
        $school = $this->makeSchool('Year School');
        $this->actingAs($this->makeUser($school, 'school-admin'));

        $this->post('/school/academics/years', ['name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'make_current' => true])
            ->assertSessionHasNoErrors();

        $current = Term::where('is_current', true)->sole();
        $this->assertSame('First Term', $current->name);
        $this->assertTrue($current->academicYear->is_current);

        // Moving on to the next term moves the "current" flag
        $second = Term::where('sequence', 2)->sole();
        $this->post("/school/academics/terms/{$second->id}/current")->assertSessionHasNoErrors();
        $this->assertSame([$second->id], Term::where('is_current', true)->pluck('id')->all());
    }

    public function test_score_parts_must_add_up_to_100(): void
    {
        $school = $this->makeSchool('Parts School');
        $this->actingAs($this->makeUser($school, 'school-admin'));

        $this->post('/school/academics/assessment-schemes', [
            'name' => 'Too many marks',
            'components' => [['name' => 'First CA', 'short_name' => 'CA1', 'max_score' => 30], ['name' => 'Examination', 'short_name' => 'Exam', 'max_score' => 80]],
        ])->assertSessionHasErrors('components');

        $this->post('/school/academics/assessment-schemes', [
            'name' => 'Three tests, an assignment and an exam',
            'components' => [
                ['name' => 'First CA', 'short_name' => 'CA1', 'max_score' => 10],
                ['name' => 'Second CA', 'short_name' => 'CA2', 'max_score' => 10],
                ['name' => 'Third CA', 'short_name' => 'CA3', 'max_score' => 10],
                ['name' => 'Assignment', 'short_name' => 'Assign', 'max_score' => 10],
                ['name' => 'Examination', 'short_name' => 'Exam', 'max_score' => 60],
            ],
        ])->assertSessionHasNoErrors();

        $scheme = AssessmentScheme::where('name', 'Three tests, an assignment and an exam')->sole();
        $this->assertSame(['CA1', 'CA2', 'CA3', 'Assign', 'Exam'], $scheme->components->pluck('short_name')->all());
        $this->assertFalse($scheme->is_default, 'a new setup does not replace the default unless asked');
    }

    public function test_editing_a_setup_keeps_the_parts_that_stay_and_removes_the_rest(): void
    {
        $school = $this->makeSchool('Edit School');
        $this->actingAs($this->makeUser($school, 'school-admin'));
        $scheme = AssessmentScheme::where('is_default', true)->sole();
        [$ca1, $ca2, $exam] = $scheme->components->all();

        // Merge CA1 and CA2 into one 40-mark CA, keeping CA1's record
        $this->put("/school/academics/assessment-schemes/{$scheme->id}", [
            'name' => 'CA + Exam',
            'components' => [
                ['id' => $ca1->id, 'name' => 'Continuous Assessment', 'short_name' => 'CA', 'max_score' => 40],
                ['id' => $exam->id, 'name' => 'Examination', 'short_name' => 'Exam', 'max_score' => 60],
            ],
        ])->assertSessionHasNoErrors();

        $parts = $scheme->fresh()->components;
        $this->assertSame([$ca1->id, $exam->id], $parts->pluck('id')->all());
        $this->assertSame('CA', $parts[0]->short_name);
        $this->assertNull($ca2->fresh());
    }

    public function test_a_grade_scale_must_reach_down_to_zero(): void
    {
        $school = $this->makeSchool('Scale School');
        $this->actingAs($this->makeUser($school, 'school-admin'));

        $this->post('/school/academics/grading-schemes', [
            'name' => 'Gappy',
            'bands' => [['grade' => 'A', 'min_marks' => 70, 'max_marks' => 100], ['grade' => 'B', 'min_marks' => 50, 'max_marks' => 69]],
        ])->assertSessionHasErrors('bands');

        $this->post('/school/academics/grading-schemes', [
            'name' => 'Pass or fail',
            'bands' => [['grade' => 'P', 'min_marks' => 50, 'max_marks' => 100, 'remarks' => 'Pass'], ['grade' => 'F', 'min_marks' => 0, 'max_marks' => 49, 'remarks' => 'Fail']],
        ])->assertSessionHasNoErrors();

        $this->assertSame(['P', 'F'], GradingScheme::where('name', 'Pass or fail')->sole()->bands->pluck('grade')->all());
    }

    public function test_a_class_can_use_its_own_grade_scale_and_marks_follow_it(): void
    {
        $school = $this->makeSchool('Class Scale School');
        $this->actingAs($this->makeUser($school, 'school-admin'));

        $primary = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary 4']);
        $ss1 = SchoolClass::create(['school_id' => $school->id, 'name' => 'SS 1']);
        $simple = SchoolDefaults::createGradingScheme($school->id, 'simple');

        $this->put("/school/academics/classes/{$primary->id}/scheme", ['grading_scheme_id' => $simple, 'assessment_scheme_id' => null])
            ->assertSessionHasNoErrors();

        $this->assertSame('A', GradingService::forClass($school->id, $primary->id)->calculate(72, 100)['grade']);
        $this->assertSame('B2', GradingService::forClass($school->id, $ss1->id)->calculate(72, 100)['grade']);

        // Saving marks for a Primary 4 exam uses the simple scale
        $subject = Subject::create(['school_id' => $school->id, 'class_id' => $primary->id, 'name' => 'Mathematics', 'code' => 'MTH']);
        $student = \App\Models\Student::create([
            'school_id' => $school->id, 'class_id' => $primary->id, 'first_name' => 'Ada', 'last_name' => 'Obi',
            'admission_no' => 'P4-001', 'gender' => 'female', 'status' => 'active',
        ]);
        $exam = Exam::create(['school_id' => $school->id, 'class_id' => $primary->id, 'name' => 'First Term Exam', 'type' => 'final', 'status' => 'published']);
        $this->post("/school/exams/{$exam->id}/marks", ['marks' => [[
            'student_id' => $student->id, 'subject_id' => $subject->id, 'marks_obtained' => 72, 'is_absent' => false,
        ]]])->assertSessionHasNoErrors();

        $this->assertSame('A', DB::table('marks')->where('exam_id', $exam->id)->value('grade'));
    }

    public function test_a_subject_can_override_its_class_score_setup(): void
    {
        $school = $this->makeSchool('Subject School');
        $this->actingAs($this->makeUser($school, 'school-admin'));

        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'SS 2']);
        $physics = Subject::create(['school_id' => $school->id, 'class_id' => $class->id, 'name' => 'Physics', 'code' => 'PHY']);
        $english = Subject::create(['school_id' => $school->id, 'class_id' => $class->id, 'name' => 'English', 'code' => 'ENG']);
        $threeCa = SchoolDefaults::createAssessmentScheme($school->id, 'three_ca');

        $this->assertTrue(AssessmentScheme::forSubject($physics)->is_default, 'with nothing chosen, the school default is used');

        $this->put("/school/academics/subjects/{$physics->id}/scheme", ['assessment_scheme_id' => $threeCa])->assertSessionHasNoErrors();

        $this->assertSame($threeCa, AssessmentScheme::forSubject($physics->fresh())->id);
        $this->assertTrue(AssessmentScheme::forSubject($english)->is_default);
    }

    public function test_the_default_setup_cannot_be_deleted_and_deleting_another_sends_classes_back_to_the_default(): void
    {
        $school = $this->makeSchool('Delete School');
        $this->actingAs($this->makeUser($school, 'school-admin'));
        $default = AssessmentScheme::where('is_default', true)->sole();
        $other = SchoolDefaults::createAssessmentScheme($school->id, 'single_ca');
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'JSS 1', 'assessment_scheme_id' => $other]);

        $this->delete("/school/academics/assessment-schemes/{$default->id}");
        $this->assertNotNull($default->fresh());

        $this->delete("/school/academics/assessment-schemes/{$other}");
        $this->assertNull(AssessmentScheme::find($other));
        $this->assertNull($class->fresh()->assessment_scheme_id);
    }

    public function test_one_school_cannot_see_or_use_another_schools_setup(): void
    {
        $mine = $this->makeSchool('Mine');
        $theirs = $this->makeSchool('Theirs');
        $this->actingAs($this->makeUser($mine, 'school-admin'));

        $theirScheme = AssessmentScheme::withoutGlobalScopes()->where('school_id', $theirs->id)->sole();
        $theirScale = GradingScheme::withoutGlobalScopes()->where('school_id', $theirs->id)->sole();
        $myClass = SchoolClass::create(['school_id' => $mine->id, 'name' => 'JSS 2']);

        $this->put("/school/academics/assessment-schemes/{$theirScheme->id}", [
            'name' => 'Hijacked', 'components' => [['name' => 'Exam', 'short_name' => 'Exam', 'max_score' => 100]],
        ])->assertNotFound();
        $this->delete("/school/academics/grading-schemes/{$theirScale->id}")->assertNotFound();

        $this->put("/school/academics/classes/{$myClass->id}/scheme", ['grading_scheme_id' => $theirScale->id])
            ->assertSessionHasErrors('grading_scheme_id');

        $this->get('/school/academics/assessment', ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonCount(1, 'props.assessmentSchemes')
            ->assertJsonPath('props.assessmentSchemes.0.name', 'CA1 + CA2 + Exam (40/60)');
    }

    public function test_principals_and_teachers_can_look_but_only_admins_can_change(): void
    {
        $school = $this->makeSchool('Role School');
        $scheme = AssessmentScheme::withoutGlobalScopes()->where('school_id', $school->id)->sole();
        $payload = ['name' => 'Changed', 'components' => [['name' => 'Exam', 'short_name' => 'Exam', 'max_score' => 100]]];

        $principal = $this->makeUser($school, 'principal');
        $this->actingAs($principal)->get('/school/academics/assessment', ['X-Inertia' => 'true'])
            ->assertOk()->assertJsonPath('props.canEdit', false);
        $this->actingAs($principal)->put("/school/academics/assessment-schemes/{$scheme->id}", $payload)->assertForbidden();

        $teacher = $this->makeUser($school, 'teacher');
        $this->actingAs($teacher)->get('/school/academics/terms', ['X-Inertia' => 'true'])->assertOk();
        $this->actingAs($teacher)->post('/school/academics/years', ['name' => 'X', 'start_date' => '2026-09-01', 'end_date' => '2027-07-01'])->assertForbidden();

        $this->actingAs($this->makeUser($school, 'accountant'))->get('/school/academics/assessment')->assertForbidden();
        $this->assertSame('CA1 + CA2 + Exam (40/60)', $scheme->fresh()->name);
    }

    public function test_an_exam_can_be_put_in_a_term_of_the_same_school_only(): void
    {
        $mine = $this->makeSchool('Exam Term School');
        $theirs = $this->makeSchool('Other Exam School');
        $this->actingAs($this->makeUser($mine, 'school-admin'));

        $myYear = AcademicYear::create(['school_id' => $mine->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23']);
        $theirYear = AcademicYear::withoutGlobalScopes()->create(['school_id' => $theirs->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23']);
        $class = SchoolClass::create(['school_id' => $mine->id, 'name' => 'SS 3']);
        $theirTerm = Term::withoutGlobalScopes()->where('academic_year_id', $theirYear->id)->first();
        $myTerm = $myYear->terms()->first();

        $exam = ['name' => 'First Term Exam', 'type' => 'final', 'class_id' => $class->id, 'status' => 'draft'];
        $this->post('/school/exams', $exam + ['term_id' => $theirTerm->id])->assertSessionHasErrors('term_id');
        $this->post('/school/exams', $exam + ['term_id' => $myTerm->id])->assertSessionHasNoErrors();

        $this->assertSame($myTerm->id, Exam::sole()->term_id);

        // The exam list offers this school's terms, labelled with their year
        $this->get('/school/exams', ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonCount(3, 'props.terms')
            ->assertJsonPath('props.terms.0.label', '2026/2027 · First Term')
            ->assertJsonPath('props.exams.data.0.term.name', 'First Term');
    }

    public function test_grades_a_school_typed_in_before_this_change_are_kept(): void
    {
        $school = $this->makeSchool('Old Grades School');
        // Simulate a school from before: its own grades, no named scale yet
        DB::table('grade_scales')->where('school_id', $school->id)->delete();
        DB::table('grading_schemes')->where('school_id', $school->id)->delete();
        DB::table('grade_scales')->insert([
            ['school_id' => $school->id, 'grade' => 'A', 'gpa' => 4, 'min_marks' => 70, 'max_marks' => 100, 'remarks' => 'Excellent', 'sort_order' => 1],
            ['school_id' => $school->id, 'grade' => 'F', 'gpa' => 0, 'min_marks' => 0, 'max_marks' => 69, 'remarks' => 'Fail', 'sort_order' => 2],
        ]);

        SchoolDefaults::apply($school->id);

        $scale = GradingScheme::withoutGlobalScopes()->where('school_id', $school->id)->sole();
        $this->assertTrue($scale->is_default);
        $this->assertSame(['A', 'F'], GradeScale::withoutGlobalScopes()->where('grading_scheme_id', $scale->id)->orderByDesc('min_marks')->pluck('grade')->all());
        $this->assertSame('A', (new GradingService($school->id))->calculate(80, 100)['grade']);
    }
}
