<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AdmissionAssessment;
use App\Models\AdmissionInquiry;
use App\Models\CbtCandidate;
use App\Models\AssessmentScheme;
use App\Models\CbtAttempt;
use App\Models\CbtBank;
use App\Models\ClassSubject;
use App\Models\CbtQuestion;
use App\Models\CbtTest as Test;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Staff;
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
        // The teacher teaches Maths in JSS 1, so may send its scores into the term results
        $staff = Staff::withoutGlobalScopes()->create([
            'school_id' => $this->school->id, 'user_id' => $this->teacher->id, 'emp_id' => 'T1', 'first_name' => 'T', 'last_name' => 'One',
            'status' => 'active', 'salary_type' => 'fixed', 'gender' => 'male', 'email' => $this->teacher->email,
        ]);
        ClassSubject::where('class_id', $this->jss1->id)->where('subject_id', $this->maths->id)->update(['teacher_id' => $staff->id]);
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

    private function entranceExam(int $applicants = 2): array
    {
        $this->actingAs($this->teacher);
        $this->post('/school/cbt/tests', [
            'title' => 'JSS1 entrance exam', 'audience' => 'applicants', 'class_id' => $this->jss1->id, 'duration_minutes' => 30,
            'opens_at' => now()->subMinute()->toIso8601String(), 'shuffle_questions' => true, 'shuffle_options' => true, 'show_score' => false, 'pass_mark' => 50,
            'sections' => [['title' => 'Maths', 'cbt_bank_id' => $this->bank->id, 'question_count' => 4]],
        ])->assertSessionHasNoErrors();
        $test = Test::latest('id')->first();
        $this->assertSame('applicants', $test->audience);
        $this->assertNull($test->class_id);

        // Cannot open without applicants
        $this->post("/school/cbt/tests/{$test->id}/status", ['status' => 'published'])->assertSessionHasErrors('status.0');

        $inquiries = collect(range(1, $applicants))->map(fn ($n) => AdmissionInquiry::create([
            'school_id' => $this->school->id, 'student_name' => "Applicant {$n}", 'class_interested' => 'JSS 1', 'guardian_name' => 'Parent', 'guardian_phone' => "080000000{$n}", 'status' => 'new',
        ]));
        $this->post("/school/cbt/tests/{$test->id}/candidates", ['inquiry_ids' => $inquiries->pluck('id')->all()])->assertSessionHasNoErrors();
        // Adding the same people again adds nobody twice
        $this->post("/school/cbt/tests/{$test->id}/candidates", ['inquiry_ids' => $inquiries->pluck('id')->all()])->assertSessionHasNoErrors();
        $this->assertSame($applicants, CbtCandidate::count());
        $this->post("/school/cbt/tests/{$test->id}/status", ['status' => 'published'])->assertSessionHasNoErrors();

        return [$test->fresh(), CbtCandidate::orderBy('exam_number')->get(), $inquiries];
    }

    public function test_applicants_sit_an_entrance_exam_with_their_exam_number_and_pin(): void
    {
        [$test, $candidates] = $this->entranceExam();
        $first = $candidates[0];
        $this->assertSame("E{$test->id}-001", $first->exam_number);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $first->pin);
        $this->assertNotSame($first->pin, $first->getRawOriginal('pin'), 'the PIN is stored encrypted');
        $this->assertSame('pending', $first->assessment->outcome);
        $this->assertSame('exam', $first->assessment->type);

        // Slips print as a PDF
        $this->get("/school/cbt/tests/{$test->id}/slips")->assertOk()->assertHeader('Content-Type', 'application/pdf');

        // A wrong PIN is refused; the applicant has no account
        auth()->logout();
        $this->post('/exam/login', ['exam_number' => $first->exam_number, 'pin' => '000000x'])->assertSessionHasErrors('exam_number');
        $this->get('/exam/start')->assertRedirect('/exam');

        // Lower-case exam number works
        $this->post('/exam/login', ['exam_number' => strtolower($first->exam_number), 'pin' => $first->pin])->assertRedirect('/exam/start');
        $this->get('/exam/start')->assertOk()->assertInertia(fn ($p) => $p->component('Student/Cbt/Start', false)->where('person.number', $first->exam_number)->where('base', '/exam'));
        $this->post('/exam/start')->assertRedirect('/exam/paper');
        $attempt = CbtAttempt::sole();
        $this->assertSame($first->id, $attempt->cbt_candidate_id);
        $this->assertNull($attempt->student_id);
        $this->get('/exam/paper')->assertOk()->assertInertia(fn ($p) => $p->component('Student/Cbt/Exam', false)->has('questions', 4)->missing('questions.0.correct_option'));

        // 3 of 4 right = 75%, above the 50% pass mark
        $questions = CbtQuestion::whereIn('id', collect($attempt->paper)->pluck('q'))->get()->keyBy('id');
        foreach (array_values($attempt->paper) as $i => $line) {
            $q = $questions[$line['q']];
            $this->postJson('/exam/answer', ['question_id' => $q->id, 'option' => $i < 3 ? $q->correct_option : ($q->correct_option + 1) % 3])->assertOk();
        }
        $this->post('/exam/submit')->assertRedirect('/exam/done');
        $this->get('/exam/done')->assertOk()->assertInertia(fn ($p) => $p->where('result.score', null)); // show_score is off

        $assessment = $first->assessment->fresh();
        $this->assertSame('passed', $assessment->outcome);
        $this->assertEquals(3, $assessment->score);
        $this->assertEquals(4, $assessment->max_score);

        // Logging out ends the session; the PIN cannot open a second sitting
        $this->post('/exam/logout')->assertRedirect('/exam');
        $this->post('/exam/login', ['exam_number' => $first->exam_number, 'pin' => $first->pin]);
        $this->get('/exam/start')->assertRedirect('/exam/done');
        $this->assertSame(1, CbtAttempt::count());

        // Staff see both applicants; the one who never came is marked absent when the exam closes
        $this->actingAs($this->teacher);
        $this->get("/school/cbt/tests/{$test->id}")->assertOk()->assertInertia(fn ($p) => $p->has('results', 2)->where('results.0.outcome', 'passed')->where('canCopy', false));
        $this->post("/school/cbt/tests/{$test->id}/status", ['status' => 'closed'])->assertSessionHasNoErrors();
        $this->assertSame('absent', $candidates[1]->assessment->fresh()->outcome);
        $this->assertSame('passed', $assessment->fresh()->outcome);

        // Removing a sitting puts the admission line back to waiting
        $this->delete("/school/cbt/tests/{$test->id}/attempts/{$attempt->id}")->assertSessionHasNoErrors();
        $this->assertSame('pending', $assessment->fresh()->outcome);
        $this->assertNull($assessment->fresh()->score);
    }

    public function test_entrance_exam_failing_new_pin_and_removing_an_applicant(): void
    {
        [$test, $candidates, $inquiries] = $this->entranceExam();
        [$a, $b] = [$candidates[0], $candidates[1]];

        // A new PIN replaces the old one
        $old = $a->pin;
        $this->post("/school/cbt/tests/{$test->id}/candidates/{$a->id}/pin")->assertSessionHasNoErrors();
        $new = $a->fresh()->pin;
        $this->assertNotSame($old, $new);
        auth()->logout();
        $this->post('/exam/login', ['exam_number' => $a->exam_number, 'pin' => $old])->assertSessionHasErrors('exam_number');
        $this->post('/exam/login', ['exam_number' => $a->exam_number, 'pin' => $new])->assertRedirect('/exam/start');

        // Nothing answered = 0%, below the pass mark; time running out also records it
        $this->post('/exam/start');
        $this->travel(31)->minutes();
        $this->postJson('/exam/ping')->assertOk()->assertJsonPath('finished', true);
        $this->assertSame('failed', $a->assessment->fresh()->outcome);

        // An applicant who has not started can be taken off; their pending line goes too
        $this->actingAs($this->teacher);
        $bAssessment = $b->admission_assessment_id;
        $this->delete("/school/cbt/tests/{$test->id}/candidates/{$b->id}")->assertSessionHasNoErrors();
        $this->assertNull(CbtCandidate::find($b->id));
        $this->assertNull(AdmissionAssessment::find($bAssessment));
        // One who has started cannot
        $this->delete("/school/cbt/tests/{$test->id}/candidates/{$a->id}")->assertSessionHasErrors('candidate');

        // Who sits it cannot be changed once people are down for it
        $this->put("/school/cbt/tests/{$test->id}", [
            'title' => 'X', 'audience' => 'students', 'class_id' => $this->jss1->id, 'duration_minutes' => 30, 'opens_at' => now()->toIso8601String(),
            'sections' => [['title' => 'Maths', 'cbt_bank_id' => $this->bank->id, 'question_count' => 4]],
        ])->assertSessionHasErrors('audience');
    }

    public function test_applicants_only_reach_their_own_sitting_and_a_draft_exam_is_closed_to_them(): void
    {
        Storage::fake('private');
        [$test, $candidates] = $this->entranceExam();
        $q = CbtQuestion::first();
        $q->update(['image_path' => 'cbt/x.png']);
        Storage::disk('private')->put('cbt/x.png', 'png');

        auth()->logout();
        // Not logged in: nothing
        $this->get("/exam/images/{$q->id}")->assertRedirect('/exam');
        $this->postJson('/exam/answer', ['question_id' => $q->id, 'option' => 0])->assertRedirect('/exam');

        $this->post('/exam/login', ['exam_number' => $candidates[0]->exam_number, 'pin' => $candidates[0]->pin]);
        $this->post('/exam/start');
        $paper = collect(CbtAttempt::sole()->paper)->pluck('q');
        $other = CbtQuestion::whereNotIn('id', $paper)->first();
        $this->postJson('/exam/answer', ['question_id' => $other->id, 'option' => 0])->assertStatus(422);
        if ($paper->contains($q->id)) {
            $this->get("/exam/images/{$q->id}")->assertOk();
        } else {
            $this->get("/exam/images/{$q->id}")->assertNotFound();
        }
        // A student account cannot reach staff screens through the exam session
        $this->get('/school/cbt/tests')->assertRedirect();

        // An exam put back to draft lets nobody log in
        $this->actingAs($this->teacher);
        $draft = Test::create(['school_id' => $this->school->id, 'title' => 'Later', 'audience' => 'applicants', 'duration_minutes' => 10, 'opens_at' => now(), 'status' => 'draft']);
        $cand = CbtCandidate::create(['school_id' => $this->school->id, 'cbt_test_id' => $draft->id, 'inquiry_id' => $candidates[1]->inquiry_id, 'exam_number' => 'E999-001', 'pin' => '123456']);
        auth()->logout();
        $this->post('/exam/login', ['exam_number' => $cand->exam_number, 'pin' => '123456'])->assertSessionHasErrors('exam_number');

        // Staff of another school cannot see or change this exam's applicants
        $other = $this->makeUser($this->makeSchool('Other'), 'school-admin');
        $this->actingAs($other);
        $this->get("/school/cbt/tests/{$test->id}/slips")->assertNotFound();
        $this->delete("/school/cbt/tests/{$test->id}/candidates/{$candidates[1]->id}")->assertNotFound();
    }

    public function test_the_student_home_offers_an_open_test_until_it_is_handed_in(): void
    {
        $test = $this->makeTest();

        $this->actingAs($this->adaUser);
        $this->get('/school/student/dashboard')->assertInertia(fn ($p) => $p
            ->where('today.open_tests.0.id', $test->id)
            ->where('latestTerm', null));

        $this->sit($test, 4);
        $this->post("/school/student/cbt/{$test->id}/submit");
        $this->get('/school/student/dashboard')->assertInertia(fn ($p) => $p->where('today.open_tests', []));
    }
}
