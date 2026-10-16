<?php

namespace Tests\Feature\Correctness;

use App\Models\AcademicYear;
use App\Models\AssessmentScheme;
use App\Models\CbtAttempt;
use App\Models\CbtBank;
use App\Models\CbtQuestion;
use App\Models\CbtTest as Test;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectScore;
use Tests\Feature\Security\SecurityTestCase;

/** Computer test marking worked out by hand: questions worth 2, 3 and 5 marks (10 in all). */
class CbtScoringCorrectnessTest extends SecurityTestCase
{
    private $school;
    private $teacher;
    private SchoolClass $class;
    private Subject $maths;
    private CbtBank $bank;
    private $term;
    /** @var array<int,int> question id by marks */
    private array $byMarks = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('CBT Right School');
        $this->teacher = $this->makeUser($this->school, 'teacher');
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $this->term = $year->terms()->first();
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        $this->maths = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'Mathematics', 'code' => 'MTH']);
        $this->bank = CbtBank::create(['school_id' => $this->school->id, 'name' => 'Maths bank', 'subject_id' => $this->maths->id]);
        foreach ([2, 3, 5] as $marks) {
            $this->byMarks[$marks] = CbtQuestion::create([
                'school_id' => $this->school->id, 'cbt_bank_id' => $this->bank->id, 'text' => "Worth {$marks} marks", 'options' => ['right', 'wrong a', 'wrong b'],
                'correct_option' => 0, 'difficulty' => 'easy', 'topic' => 'Mix', 'marks' => $marks,
            ])->id;
        }
    }

    private function openTest(array $extra = []): Test
    {
        $this->actingAs($this->teacher);
        $this->post('/school/cbt/tests', $extra + [
            'title' => 'Hand-marked test', 'class_id' => $this->class->id, 'subject_id' => $this->maths->id, 'term_id' => $this->term->id, 'duration_minutes' => 30,
            'opens_at' => now()->subMinute()->format('Y-m-d H:i'), 'shuffle_questions' => true, 'shuffle_options' => true, 'show_score' => true,
            'sections' => [['title' => 'All', 'cbt_bank_id' => $this->bank->id, 'question_count' => 3]],
        ])->assertSessionHasNoErrors();
        $t = Test::latest('id')->first();
        $this->post("/school/cbt/tests/{$t->id}/status", ['status' => 'published'])->assertSessionHasNoErrors();

        return $t->fresh();
    }

    private function pupil(string $first): array
    {
        $user = $this->makeUser($this->school, 'student');
        $s = Student::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'user_id' => $user->id, 'first_name' => $first, 'last_name' => 'Pupil', 'gender' => 'female', 'status' => 'active']);

        return [$user, $s];
    }

    /** Sit the test: $answers = marks-of-question => option picked (0 is right) ; questions not listed stay blank */
    private function sit($user, Test $test, array $answers): CbtAttempt
    {
        $this->actingAs($user);
        $this->post("/school/student/cbt/{$test->id}/start");
        $marksOf = array_flip($this->byMarks);
        foreach ($answers as $marks => $option) {
            $this->postJson("/school/student/cbt/{$test->id}/answer", ['question_id' => $this->byMarks[$marks], 'option' => $option])->assertOk();
        }
        $this->post("/school/student/cbt/{$test->id}/submit");

        return CbtAttempt::where('student_id', Student::where('user_id', $user->id)->value('id'))->sole();
    }

    public function test_marks_are_added_by_question_weight_and_blanks_score_zero(): void
    {
        $test = $this->openTest(['pass_mark' => 70]);
        [$u1] = $this->pupil('Ada');
        [$u2] = $this->pupil('Bayo');
        [$u3] = $this->pupil('Chidi');
        [$u4] = $this->pupil('Dupe');

        // Ada: the 2-mark and 5-mark right, the 3-mark wrong  -> 7 / 10 = 70%
        $ada = $this->sit($u1, $test, [2 => 0, 3 => 1, 5 => 0]);
        $this->assertEquals([7.0, 10.0], [(float) $ada->score, (float) $ada->max_score]);
        // Bayo: all right -> 10 / 10 ; Chidi: only the 3-mark answered and right -> 3 / 10 (blanks are 0) ; Dupe: all wrong -> 0
        $this->assertEquals(10.0, (float) $this->sit($u2, $test, [2 => 0, 3 => 0, 5 => 0])->score);
        $this->assertEquals(3.0, (float) $this->sit($u3, $test, [3 => 0])->score);
        $this->assertEquals(0.0, (float) $this->sit($u4, $test, [2 => 2, 3 => 1, 5 => 1])->score);

        // The staff results page: percentages 70 / 100 / 30 / 0 and passes against a 70% mark = Ada (exactly 70) and Bayo
        $this->actingAs($this->teacher);
        $page = $this->get("/school/cbt/tests/{$test->id}", ['X-Inertia' => 'true'])->assertOk();
        $percents = collect($page->json('props.results'))->pluck('percent')->map(fn ($p) => (float) $p)->sort()->values()->all();
        $this->assertEquals([0.0, 30.0, 70.0, 100.0], $percents);
        $this->assertSame(2, $page->json('props.summary.passed'), '70% against a 70 pass mark is a pass');
    }

    public function test_sending_percentages_to_term_results_scales_them_to_the_part(): void
    {
        $test = $this->openTest();
        [$u1, $s1] = $this->pupil('Ada');
        [$u2, $s2] = $this->pupil('Bayo');
        $this->sit($u1, $test, [2 => 0, 3 => 1, 5 => 0]);   // 70%
        $this->sit($u2, $test, [3 => 0]);                    // 30%

        $part = AssessmentScheme::where('is_default', true)->sole()->components->firstWhere('short_name', 'CA1'); // out of 20
        $this->actingAs($this->teacher);
        $this->post("/school/cbt/tests/{$test->id}/copy-to-results", ['component_id' => $part->id])->assertSessionHasNoErrors();

        // 70% of 20 = 14 ; 30% of 20 = 6
        $score = fn ($s) => (float) SubjectScore::where('student_id', $s->id)->where('assessment_component_id', $part->id)->value('score');
        $this->assertEquals(14.0, $score($s1));
        $this->assertEquals(6.0, $score($s2));
    }

    public function test_a_pupil_gets_one_sitting_and_cannot_change_answers_after_handing_in(): void
    {
        $test = $this->openTest();
        [$u] = $this->pupil('Ada');
        $attempt = $this->sit($u, $test, [2 => 0]);
        $this->assertSame('submitted', $attempt->status);
        $this->postJson("/school/student/cbt/{$test->id}/answer", ['question_id' => $this->byMarks[3], 'option' => 0])->assertStatus(422);
        $this->post("/school/student/cbt/{$test->id}/start");
        $this->assertSame(1, CbtAttempt::count());
        $this->assertEquals(2.0, (float) $attempt->fresh()->score);
    }

    public function test_an_option_that_does_not_exist_is_refused(): void
    {
        $test = $this->openTest();
        [$u] = $this->pupil('Ada');
        $this->actingAs($u);
        $this->post("/school/student/cbt/{$test->id}/start");
        $this->postJson("/school/student/cbt/{$test->id}/answer", ['question_id' => $this->byMarks[2], 'option' => 9])->assertStatus(422);
        $this->postJson("/school/student/cbt/{$test->id}/answer", ['question_id' => $this->byMarks[2], 'option' => -1])->assertStatus(422);
        $this->postJson("/school/student/cbt/{$test->id}/answer", ['question_id' => 999999, 'option' => 0])->assertStatus(422);
    }

    public function test_a_pupil_from_another_class_cannot_sit_the_test(): void
    {
        $test = $this->openTest();
        $other = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 3', 'numeric_name' => 9]);
        $user = $this->makeUser($this->school, 'student');
        Student::create(['school_id' => $this->school->id, 'class_id' => $other->id, 'user_id' => $user->id, 'first_name' => 'Zed', 'last_name' => 'Other', 'gender' => 'male', 'status' => 'active']);
        $this->actingAs($user);
        $this->post("/school/student/cbt/{$test->id}/start");
        $this->assertSame(0, CbtAttempt::count());
    }
}
