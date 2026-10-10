<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentScheme;
use App\Models\CbtAttempt;
use App\Models\CbtBank;
use App\Models\CbtQuestion;
use App\Models\CbtTest as Test;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectScore;
use App\Models\User;
use App\Services\CbtService;
use App\Support\SimpleXlsx;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Security\SecurityTestCase;

/** Computer-based tests: question banks, papers, saving answers, timing, marking and results. */
class CbtTest extends SecurityTestCase
{
    private School $school;
    private User $teacher;
    private SchoolClass $jss1;
    private Subject $maths;
    private CbtBank $bank;
    private User $adaUser;
    private Student $ada;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = $this->makeSchool('CBT School');
        $this->teacher = $this->makeUser($this->school, 'teacher');
        $this->actingAs($this->teacher);
        $this->jss1 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        $this->maths = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'name' => 'Mathematics', 'code' => 'MTH1']);
        $this->bank = CbtBank::create(['school_id' => $this->school->id, 'name' => 'JSS 1 Mathematics', 'subject_id' => $this->maths->id]);
        foreach (range(1, 6) as $n) {
            CbtQuestion::create([
                'school_id' => $this->school->id, 'cbt_bank_id' => $this->bank->id, 'text' => "What is {$n} + {$n}?",
                'options' => [(string) ($n * 2), (string) ($n * 2 + 1), (string) ($n * 2 - 1)], 'correct_option' => 0,
                'difficulty' => $n <= 3 ? 'easy' : 'hard', 'topic' => 'Addition', 'marks' => 1,
            ]);
        }

        $this->adaUser = $this->makeUser($this->school, 'student');
        $this->ada = Student::create([
            'school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'user_id' => $this->adaUser->id, 'first_name' => 'Ada', 'last_name' => 'Obi',
            'admission_no' => 'A1', 'gender' => 'female', 'status' => 'active', 'date_of_birth' => '2014-01-01',
        ]);
    }

    private function makeTest(array $extra = [], array $sections = null): Test
    {
        $this->actingAs($this->teacher);
        $this->post('/school/cbt/tests', $extra + [
            'title' => 'First CA test', 'class_id' => $this->jss1->id, 'subject_id' => $this->maths->id, 'duration_minutes' => 20,
            'opens_at' => now()->subMinute()->format('Y-m-d H:i'), 'shuffle_questions' => true, 'shuffle_options' => true, 'show_score' => true,
            'sections' => $sections ?? [
                ['title' => 'Easy', 'cbt_bank_id' => $this->bank->id, 'difficulty' => 'easy', 'question_count' => 2],
                ['title' => 'Hard', 'cbt_bank_id' => $this->bank->id, 'difficulty' => 'hard', 'question_count' => 2],
            ],
        ])->assertSessionHasNoErrors();
        $test = Test::latest('id')->first();
        $this->post("/school/cbt/tests/{$test->id}/status", ['status' => 'published'])->assertSessionHasNoErrors();

        return $test->fresh();
    }

    /** Answer as the student: right answers for the first $right questions, wrong for the rest */
    private function sit(Test $test, int $right): CbtAttempt
    {
        $this->actingAs($this->adaUser);
        $this->post("/school/student/cbt/{$test->id}/start")->assertRedirect("/school/student/cbt/{$test->id}/exam");
        $attempt = CbtAttempt::sole();
        foreach (array_values($attempt->paper) as $i => $line) {
            $this->postJson("/school/student/cbt/{$test->id}/answer", ['question_id' => $line['q'], 'option' => $i < $right ? 0 : 1])
                ->assertOk()->assertJsonPath('finished', false);
        }

        return $attempt;
    }

    public function test_pasting_questions(): void
    {
        [$questions, $problems] = CbtService::parse("1. What is the capital of Nigeria?\nA. Lagos\nB. Abuja *\nC. Kano\n\nQ2) Water boils at\n(a) 50°C\n(b) 100°C\nAnswer: B\n\nWho wrote Things Fall Apart?\nA. Achebe\nB. Soyinka");

        $this->assertCount(2, $questions);
        $this->assertSame('What is the capital of Nigeria?', $questions[0]['text']);
        $this->assertSame(['Lagos', 'Abuja', 'Kano'], $questions[0]['options']);
        $this->assertSame(1, $questions[0]['correct_option']);
        $this->assertSame('Water boils at', $questions[1]['text']);
        $this->assertSame(1, $questions[1]['correct_option']);
        $this->assertCount(1, $problems);
        $this->assertStringContainsString('Question 3', $problems[0]);

        // Nothing is added while any question has a problem
        $this->post("/school/cbt/banks/{$this->bank->id}/import", ['pasted' => "Q?\nA. x\nB. y\n\nBad one\nA. only"])->assertSessionHasErrors('import.0');
        $this->assertSame(6, $this->bank->questions()->count());
        $this->post("/school/cbt/banks/{$this->bank->id}/import", ['pasted' => "Q?\nA. x\nB. y *", 'topic' => 'Misc', 'difficulty' => 'easy'])->assertSessionHasNoErrors();
        $this->assertSame('Misc', $this->bank->questions()->latest('id')->first()->topic);
    }

    public function test_uploading_the_excel_template(): void
    {
        $bytes = (new SimpleXlsx())->sheet('Questions', [
            CbtService::TEMPLATE_HEADINGS,
            ['Which is a noun?', 'run', 'table', 'quickly', '', '', 'B', 'Grammar', 'easy', '2', 'A thing'],
            ['Spell it', 'recieve', 'receive', '', '', '', 'b', '', '', '', ''],
        ])->bytes();
        $path = tempnam(sys_get_temp_dir(), 'x').'.xlsx';
        file_put_contents($path, $bytes);

        $this->post("/school/cbt/banks/{$this->bank->id}/import", ['file' => new UploadedFile($path, 'q.xlsx', null, null, true)])->assertSessionHasNoErrors();

        $q = $this->bank->questions()->where('text', 'Which is a noun?')->sole();
        $this->assertSame(['run', 'table', 'quickly'], $q->options);
        $this->assertSame(1, $q->correct_option);
        $this->assertSame(2.0, $q->marks);
        $this->assertSame('medium', $this->bank->questions()->where('text', 'Spell it')->value('difficulty'));
        $this->get('/school/cbt/banks/template')->assertOk();
    }

    public function test_a_test_cannot_open_without_enough_questions(): void
    {
        $this->post('/school/cbt/tests', [
            'title' => 'Too big', 'class_id' => $this->jss1->id, 'duration_minutes' => 10, 'opens_at' => now()->format('Y-m-d H:i'),
            'sections' => [['title' => 'All', 'cbt_bank_id' => $this->bank->id, 'difficulty' => 'easy', 'question_count' => 5]],
        ])->assertSessionHasNoErrors();
        $test = Test::sole();

        $this->post("/school/cbt/tests/{$test->id}/status", ['status' => 'published'])->assertSessionHasErrors('status.0');
        $this->assertSame('draft', $test->fresh()->status);
    }

    public function test_each_student_gets_a_paper_without_the_answers_and_is_marked_on_hand_in(): void
    {
        $test = $this->makeTest();
        $attempt = $this->sit($test, 3);

        $this->assertCount(4, $attempt->paper);
        $this->assertSame([0, 0, 1, 1], array_column($attempt->paper, 's'), 'sections stay in order');
        $hard = CbtQuestion::where('difficulty', 'hard')->pluck('id')->all();
        $this->assertEmpty(array_diff(array_column(array_slice($attempt->paper, 2), 'q'), $hard));

        $page = $this->get("/school/student/cbt/{$test->id}/exam", ['X-Inertia' => 'true'])->assertOk();
        $this->assertStringNotContainsString('correct_option', $page->getContent());
        $page->assertJsonCount(4, 'props.questions');

        $this->post("/school/student/cbt/{$test->id}/submit")->assertRedirect("/school/student/cbt/{$test->id}/done");
        $attempt->refresh();
        $this->assertSame('submitted', $attempt->status);
        $this->assertSame(3.0, $attempt->score);
        $this->assertSame(4.0, $attempt->max_score);
        $this->assertSame([['score' => 2, 'max' => 2], ['score' => 1, 'max' => 2]], array_map(fn ($s) => ['score' => (int) $s['score'], 'max' => (int) $s['max']], $attempt->section_scores));

        // Answers can't change after handing in, and starting again keeps the same sitting
        $this->postJson("/school/student/cbt/{$test->id}/answer", ['question_id' => $attempt->paper[0]['q'], 'option' => 1])->assertStatus(422);
        $this->post("/school/student/cbt/{$test->id}/start");
        $this->assertSame(1, CbtAttempt::count());
        $this->get("/school/student/cbt/{$test->id}/done", ['X-Inertia' => 'true'])->assertJsonPath('props.result.score', 3);
    }

    public function test_time_runs_out_on_the_server_and_extra_time_reopens(): void
    {
        $test = $this->makeTest();
        $attempt = $this->sit($test, 2);

        $this->travel(21)->minutes();
        $this->postJson("/school/student/cbt/{$test->id}/answer", ['question_id' => $attempt->paper[3]['q'], 'option' => 0])->assertStatus(422);
        $attempt->refresh();
        $this->assertSame('timed_out', $attempt->status);
        $this->assertSame(2.0, $attempt->score, 'answers saved before time ran out still count');

        // The teacher gives 5 more minutes after a power cut
        $this->actingAs($this->teacher);
        $this->post("/school/cbt/tests/{$test->id}/attempts/{$attempt->id}/time", ['minutes' => 5])->assertSessionHasNoErrors();
        $this->actingAs($this->adaUser);
        $this->postJson("/school/student/cbt/{$test->id}/answer", ['question_id' => $attempt->paper[3]['q'], 'option' => 0])->assertOk();
        $this->postJson("/school/student/cbt/{$test->id}/ping")->assertJsonPath('finished', false);
    }

    public function test_start_code_page_leaves_and_flags(): void
    {
        $test = $this->makeTest(['access_code' => 'HALL7', 'max_page_leaves' => 2]);
        $this->actingAs($this->adaUser);

        $this->post("/school/student/cbt/{$test->id}/start", ['code' => 'nope'])->assertSessionHasErrors('code');
        $this->post("/school/student/cbt/{$test->id}/start", ['code' => 'hall7'])->assertRedirect();
        $attempt = CbtAttempt::sole();

        $this->postJson("/school/student/cbt/{$test->id}/flag", ['question_id' => $attempt->paper[1]['q'], 'on' => true])->assertOk();
        $this->assertSame([$attempt->paper[1]['q']], $attempt->fresh()->flags);

        $this->postJson("/school/student/cbt/{$test->id}/left")->assertJsonPath('finished', false)->assertJsonPath('page_leaves', 1);
        $this->postJson("/school/student/cbt/{$test->id}/left")->assertJsonPath('finished', true);
        $this->assertSame('left_page', $attempt->fresh()->ended_reason);
    }

    public function test_results_report_and_copying_into_ca(): void
    {
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2025/2026', 'start_date' => '2025-09-08', 'end_date' => '2026-07-24', 'is_current' => true]);
        $term = $year->terms()->orderBy('sequence')->first();
        $test = $this->makeTest(['term_id' => $term->id, 'pass_mark' => 50]);
        $this->sit($test, 3);
        $this->post("/school/student/cbt/{$test->id}/submit");

        $this->actingAs($this->teacher);
        $this->get("/school/cbt/tests/{$test->id}", ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.results.0.percent', 75)
            ->assertJsonPath('props.summary.passed', 1)
            ->assertJsonCount(4, 'props.questionReport');
        $this->get("/school/cbt/tests/{$test->id}/export")->assertOk();

        $ca1 = AssessmentScheme::where('is_default', true)->where('school_id', $this->school->id)->first()
            ?? AssessmentScheme::where('is_default', true)->first();
        $part = $ca1->components->firstWhere('short_name', 'CA1');
        $this->post("/school/cbt/tests/{$test->id}/copy-to-results", ['component_id' => $part->id])->assertSessionHasNoErrors();
        $this->assertEquals(round(0.75 * $part->max_score, 1), SubjectScore::where('student_id', $this->ada->id)->where('assessment_component_id', $part->id)->value('score'));
    }

    public function test_who_can_see_what(): void
    {
        $test = $this->makeTest();

        // Another class's student cannot see it
        $jss2 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 2', 'numeric_name' => 8]);
        $bayoUser = $this->makeUser($this->school, 'student');
        Student::create(['school_id' => $this->school->id, 'class_id' => $jss2->id, 'user_id' => $bayoUser->id, 'first_name' => 'Bayo', 'last_name' => 'Ade',
            'admission_no' => 'B1', 'gender' => 'male', 'status' => 'active', 'date_of_birth' => '2013-01-01']);
        $this->actingAs($bayoUser);
        $this->post("/school/student/cbt/{$test->id}/start")->assertNotFound();
        $this->get('/school/student/cbt', ['X-Inertia' => 'true'])->assertJsonCount(0, 'props.tests');
        $this->get('/school/cbt/banks')->assertForbidden();

        // Ada sees it in her list
        $this->actingAs($this->adaUser);
        $this->get('/school/student/cbt', ['X-Inertia' => 'true'])->assertJsonPath('props.tests.0.state', 'open');

        // An accountant cannot manage tests; another school sees nothing
        $this->actingAs($this->makeUser($this->school, 'accountant'));
        $this->get('/school/cbt/tests')->assertForbidden();
        $this->actingAs($this->makeUser($this->makeSchool('Other'), 'school-admin'));
        $this->get("/school/cbt/tests/{$test->id}")->assertNotFound();
        $this->get("/school/cbt/banks/{$this->bank->id}")->assertNotFound();
        $this->post('/school/cbt/tests', [
            'title' => 'Steal', 'class_id' => $this->jss1->id, 'duration_minutes' => 10, 'opens_at' => now()->format('Y-m-d H:i'),
            'sections' => [['title' => 'x', 'cbt_bank_id' => $this->bank->id, 'question_count' => 1]],
        ])->assertSessionHasErrors(['class_id', 'sections.0.cbt_bank_id']);
    }

    public function test_question_pictures_are_only_shown_to_staff_and_people_with_the_question(): void
    {
        Storage::fake('private');
        $this->post("/school/cbt/banks/{$this->bank->id}/questions", [
            'text' => 'Which shape is this?', 'options' => ['Circle', 'Square'], 'correct_option' => 1, 'difficulty' => 'easy', 'marks' => 1,
            'image' => UploadedFile::fake()->image('shape.png', 100, 100),
        ])->assertSessionHasNoErrors();
        $q = CbtQuestion::where('text', 'Which shape is this?')->sole();
        $this->assertNotNull($q->image_path);
        $this->get("/cbt/images/{$q->id}")->assertOk();

        // A student whose paper does not have it cannot see it before the test
        $this->actingAs($this->adaUser);
        $this->get("/cbt/images/{$q->id}")->assertNotFound();
    }
}
