<?php

namespace Tests\Feature;

use App\Models\AdmissionAssessment;
use App\Models\AdmissionInquiry;
use App\Models\AssessmentScheme;
use App\Models\CbtAttempt;
use App\Models\CbtBank;
use App\Models\CbtCandidate;
use App\Models\CbtQuestion;
use App\Models\CbtTest as Test;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectScore;
use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;

/** Theory questions in CBT: typed answers, teacher marking with a guide, and the calculator switch */
class CbtTheoryTest extends SecurityTestCase
{
    private School $school;
    private User $teacher;
    private SchoolClass $jss1;
    private Subject $maths;
    private CbtBank $bank;
    private User $adaUser;
    private Student $ada;
    private CbtQuestion $essay;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('Theory School');
        $this->teacher = $this->makeUser($this->school, 'teacher');
        $this->actingAs($this->teacher);
        $this->jss1 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        $this->maths = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'name' => 'Mathematics', 'code' => 'MTH1']);
        $this->bank = CbtBank::create(['school_id' => $this->school->id, 'name' => 'JSS 1 Mathematics', 'subject_id' => $this->maths->id]);
        foreach (range(1, 2) as $n) {
            CbtQuestion::create([
                'school_id' => $this->school->id, 'cbt_bank_id' => $this->bank->id, 'text' => "What is {$n} + {$n}?",
                'options' => [(string) ($n * 2), '0'], 'correct_option' => 0, 'difficulty' => 'easy', 'marks' => 2,
            ]);
        }
        $this->post("/school/cbt/banks/{$this->bank->id}/questions", [
            'type' => 'theory', 'text' => 'Explain how to find the area of a rectangle.', 'marking_guide' => 'Length times breadth (2), example (2), units (1)',
            'difficulty' => 'medium', 'marks' => 5, 'options' => ['ignored', 'too'], 'correct_option' => 1,
        ])->assertSessionHasNoErrors();
        $this->essay = CbtQuestion::where('type', 'theory')->sole();

        $this->adaUser = $this->makeUser($this->school, 'student');
        $this->ada = $this->student('Ada', 'A1', $this->adaUser);
    }

    private function student(string $name, string $no, ?User $user = null): Student
    {
        return Student::create([
            'school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'user_id' => $user?->id, 'first_name' => $name, 'last_name' => 'Obi',
            'admission_no' => $no, 'gender' => 'female', 'status' => 'active',
        ]);
    }

    private function makeTest(array $extra = []): Test
    {
        $this->actingAs($this->teacher);
        $this->post('/school/cbt/tests', $extra + [
            'title' => 'Maths test', 'class_id' => $this->jss1->id, 'subject_id' => $this->maths->id, 'duration_minutes' => 20,
            'opens_at' => now()->subMinute()->format('Y-m-d H:i'), 'show_score' => true,
            'sections' => [
                ['title' => 'Objective', 'cbt_bank_id' => $this->bank->id, 'question_type' => 'objective', 'question_count' => 2],
                ['title' => 'Theory', 'cbt_bank_id' => $this->bank->id, 'question_type' => 'theory', 'question_count' => 1],
            ],
        ])->assertSessionHasNoErrors();
        $test = Test::latest('id')->first();
        $this->post("/school/cbt/tests/{$test->id}/status", ['status' => 'published'])->assertSessionHasNoErrors();

        return $test->fresh();
    }

    /** Ada answers both objectives right and types $essay for the theory question, then hands in */
    private function sit(Test $test, string $essay): CbtAttempt
    {
        $this->actingAs($this->adaUser);
        $this->post("/school/student/cbt/{$test->id}/start");
        $attempt = CbtAttempt::where('student_id', $this->ada->id)->sole();
        foreach ($attempt->paper as $line) {
            $body = ($line['t'] ?? null) === 'theory' ? ['question_id' => $line['q'], 'text' => $essay] : ['question_id' => $line['q'], 'option' => 0];
            $this->postJson("/school/student/cbt/{$test->id}/answer", $body)->assertOk();
        }
        $this->post("/school/student/cbt/{$test->id}/submit");

        return $attempt->fresh();
    }

    public function test_a_theory_question_is_saved_without_options_and_cannot_change_kind(): void
    {
        $this->assertNull($this->essay->options);
        $this->assertNull($this->essay->correct_option);
        $this->assertSame('Length times breadth (2), example (2), units (1)', $this->essay->marking_guide);

        $this->post("/school/cbt/banks/{$this->bank->id}/questions/{$this->essay->id}", ['type' => 'objective', 'text' => 'x', 'difficulty' => 'easy', 'marks' => 1, 'options' => ['a', 'b'], 'correct_option' => 0])
            ->assertSessionHasErrors('type');
        $this->post("/school/cbt/banks/{$this->bank->id}/questions/{$this->essay->id}", ['text' => 'Explain area.', 'difficulty' => 'easy', 'marks' => 6])
            ->assertSessionHasNoErrors();
        $this->assertSame(6.0, $this->essay->fresh()->marks);

        $this->get("/school/cbt/banks/{$this->bank->id}?type=theory")->assertInertia(fn ($p) => $p->has('questions.data', 1)->where('questions.data.0.type', 'theory')->where('bank.theory', 1));
    }

    public function test_typed_answers_wait_for_the_teacher_and_count_once_marked(): void
    {
        $test = $this->makeTest();
        $attempt = $this->sit($test, "Multiply the length by the breadth.\nA 3 m by 2 m room is 6 m².");

        // the objectives are marked, the theory answer waits; no score shows yet
        $this->assertTrue($attempt->needs_marking);
        $this->assertSame(4.0, $attempt->score);
        $this->assertSame(9.0, $attempt->max_score);
        $this->assertNull($attempt->marking[$this->essay->id]['marks']);
        $this->get("/school/student/cbt/{$test->id}/done")->assertInertia(fn ($p) => $p->where('result.score', null)->where('result.needs_marking', true)->where('result.answered', 3));

        $this->actingAs($this->teacher);
        $this->get("/school/cbt/tests/{$test->id}")->assertInertia(fn ($p) => $p->where('summary.to_mark', 1)->where('results.0.percent', null)->where('results.0.needs_marking', true));
        $this->get("/school/cbt/tests/{$test->id}/marking")->assertOk()->assertInertia(fn ($p) => $p->component('SchoolAdmin/Cbt/Marking', false)
            ->where('question.id', $this->essay->id)->where('question.marking_guide', 'Length times breadth (2), example (2), units (1)')
            ->where('answers.0.name', 'Ada Obi')->where('answers.0.marks', null)->where('questions.0.waiting', 1));

        $this->post("/school/cbt/tests/{$test->id}/marking", ['question_id' => $this->essay->id, 'marks' => [$attempt->id => ['marks' => 9]]])
            ->assertSessionHasErrors();
        $this->post("/school/cbt/tests/{$test->id}/marking", ['question_id' => $this->essay->id, 'marks' => [$attempt->id => ['marks' => 4.5, 'comment' => 'No units']]])
            ->assertSessionHasNoErrors();

        $attempt->refresh();
        $this->assertFalse($attempt->needs_marking);
        $this->assertNotNull($attempt->marked_at);
        $this->assertSame(8.5, $attempt->score);
        $this->assertEquals(['score' => 4.5, 'max' => 5], $attempt->section_scores[1]);
        $this->assertSame('No units', $attempt->marking[$this->essay->id]['comment']);

        $this->actingAs($this->adaUser);
        $this->get("/school/student/cbt/{$test->id}/done")->assertInertia(fn ($p) => $p->where('result.score', 8.5)->where('result.max_score', 9));
    }

    public function test_a_blank_theory_answer_gets_zero_without_waiting(): void
    {
        $test = $this->makeTest();
        $attempt = $this->sit($test, '   ');

        $this->assertFalse($attempt->needs_marking);
        $this->assertEquals(0, $attempt->marking[$this->essay->id]['marks']);
        $this->assertSame(4.0, $attempt->score);
    }

    public function test_only_marked_sittings_go_to_the_term_results(): void
    {
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $test = $this->makeTest(['term_id' => $year->terms()->first()->id]);
        $attempt = $this->sit($test, 'Length times breadth');
        $ca1 = AssessmentScheme::forSubject($this->maths)->components->firstWhere('short_name', 'CA1');

        $this->actingAs($this->teacher);
        $this->post("/school/cbt/tests/{$test->id}/copy-to-results", ['component_id' => $ca1->id])->assertSessionHas('success');
        $this->assertSame(0, SubjectScore::count());

        $this->post("/school/cbt/tests/{$test->id}/marking", ['question_id' => $this->essay->id, 'marks' => [$attempt->id => ['marks' => 5]]]);
        $this->post("/school/cbt/tests/{$test->id}/copy-to-results", ['component_id' => $ca1->id]);
        $this->assertSame((float) $ca1->max_score, (float) SubjectScore::sole()->score);
    }

    public function test_options_cannot_be_sent_for_a_theory_question_or_text_for_an_objective(): void
    {
        $test = $this->makeTest();
        $this->actingAs($this->adaUser);
        $this->post("/school/student/cbt/{$test->id}/start");
        $paper = collect(CbtAttempt::sole()->paper);
        $theory = $paper->firstWhere('t', 'theory')['q'];
        $objective = $paper->first(fn ($l) => ! isset($l['t']))['q'];

        $this->postJson("/school/student/cbt/{$test->id}/answer", ['question_id' => $theory, 'option' => 0])->assertStatus(422);
        $this->postJson("/school/student/cbt/{$test->id}/answer", ['question_id' => $objective, 'text' => 'B'])->assertStatus(422);
        $this->postJson("/school/student/cbt/{$test->id}/answer", ['question_id' => $theory, 'text' => 'First draft'])->assertOk();
        $this->postJson("/school/student/cbt/{$test->id}/answer", ['question_id' => $theory, 'text' => ''])->assertOk();
        $this->assertSame([], CbtAttempt::sole()->written);

        $this->get("/school/student/cbt/{$test->id}/exam")->assertInertia(fn ($p) => $p->where('test.allow_calculator', false)
            ->where('questions', fn ($qs) => collect($qs)->firstWhere('id', $theory)['type'] === 'theory' && collect($qs)->firstWhere('id', $theory)['options'] === []));
    }

    public function test_the_calculator_is_switched_on_per_test(): void
    {
        $test = $this->makeTest(['allow_calculator' => true]);
        $this->assertTrue($test->allow_calculator);
        $this->actingAs($this->adaUser);
        $this->post("/school/student/cbt/{$test->id}/start");
        $this->get("/school/student/cbt/{$test->id}/exam")->assertInertia(fn ($p) => $p->where('test.allow_calculator', true));
    }

    public function test_an_entrance_exam_stays_pending_until_its_theory_is_marked(): void
    {
        $inquiry = AdmissionInquiry::create(['school_id' => $this->school->id, 'student_name' => 'Tola Ade', 'class_interested' => 'JSS 1', 'guardian_name' => 'Mrs Ade', 'guardian_phone' => '08030000000', 'status' => 'new']);
        $this->actingAs($this->teacher);
        $this->post('/school/cbt/tests', [
            'title' => 'Entrance', 'audience' => 'applicants', 'duration_minutes' => 20, 'opens_at' => now()->subMinute()->format('Y-m-d H:i'), 'pass_mark' => 50,
            'sections' => [['title' => 'All', 'cbt_bank_id' => $this->bank->id, 'question_count' => 3]],
        ])->assertSessionHasNoErrors();
        $test = Test::latest('id')->first();
        $this->post("/school/cbt/tests/{$test->id}/candidates", ['inquiry_ids' => [$inquiry->id]]);
        $this->post("/school/cbt/tests/{$test->id}/status", ['status' => 'published'])->assertSessionHasNoErrors();
        $candidate = CbtCandidate::sole();

        auth()->logout();
        $this->post('/exam/login', ['exam_number' => $candidate->exam_number, 'pin' => $candidate->pin])->assertSessionHasNoErrors();
        $this->post('/exam/start');
        $attempt = CbtAttempt::sole();
        $theory = collect($attempt->paper)->firstWhere('t', 'theory')['q'];
        $this->postJson('/exam/answer', ['question_id' => $theory, 'text' => 'Length times breadth'])->assertOk();
        $this->post('/exam/submit');

        $this->assertSame('pending', AdmissionAssessment::sole()->outcome);
        $this->assertNull(AdmissionAssessment::sole()->score);

        $this->actingAs($this->teacher);
        $this->get("/school/cbt/tests/{$test->id}/marking")->assertInertia(fn ($p) => $p->where('answers.0.name', 'Tola Ade')->where('answers.0.number', $candidate->exam_number));
        $this->post("/school/cbt/tests/{$test->id}/marking", ['question_id' => $theory, 'marks' => [$attempt->id => ['marks' => 4]]])->assertSessionHasNoErrors();

        $this->assertSame('failed', AdmissionAssessment::sole()->outcome);       // 4 of 9, below 50%
        $this->assertSame(4.0, (float) AdmissionAssessment::sole()->score);
    }

    public function test_marking_is_for_cbt_managers_of_the_same_school(): void
    {
        $test = $this->makeTest();
        $attempt = $this->sit($test, 'An answer');

        $this->actingAs($this->makeUser($this->school, 'accountant'));
        $this->get("/school/cbt/tests/{$test->id}/marking")->assertForbidden();

        $this->actingAs($this->makeUser($this->makeSchool('Other School'), 'teacher'));
        $this->get("/school/cbt/tests/{$test->id}/marking")->assertNotFound();
        $this->post("/school/cbt/tests/{$test->id}/marking", ['question_id' => $this->essay->id, 'marks' => [$attempt->id => ['marks' => 5]]])->assertNotFound();
        $this->assertTrue($attempt->fresh()->needs_marking);
    }
}
