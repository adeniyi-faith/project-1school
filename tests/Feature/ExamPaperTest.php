<?php

namespace Tests\Feature;

use App\Models\AiUsage;
use App\Models\CbtBank;
use App\Models\CbtQuestion;
use App\Models\ExamPaper;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Services\QuestionWriterService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Security\SecurityTestCase;

/** The AI question writer in a question bank, and printed exam papers with their marking schemes */
class ExamPaperTest extends SecurityTestCase
{
    private School $school;
    private User $teacher;
    private SchoolClass $class;
    private Subject $subject;
    private CbtBank $bank;

    private array $answer = [];

    private bool $faked = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('Paper School');
        $this->teacher = $this->makeUser($this->school, 'teacher');
        $this->actingAs($this->teacher);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 2', 'numeric_name' => 8]);
        $this->subject = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'Mathematics', 'code' => 'MTH2']);
        $this->bank = CbtBank::create(['school_id' => $this->school->id, 'name' => 'JSS 2 Maths', 'subject_id' => $this->subject->id]);
    }

    /** Gemini answers with this JSON; the platform AI is switched on with a key */
    private function aiAnswers(array $answer): void
    {
        $this->answer = $answer;
        if (! $this->faked) {
            $this->faked = true;
            Http::fake(['generativelanguage.googleapis.com/*' => fn () => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode($this->answer)]]], 'finishReason' => 'STOP']],
                'usageMetadata' => ['promptTokenCount' => 800, 'candidatesTokenCount' => 2500],
            ])]);
            $this->answer = ['ok' => true];
            $this->actingAs($this->makeUser($this->school, 'super-admin'))->post('/super-admin/ai', [
                'enabled' => true, 'api_key' => 'AIzaTestKey-1234567890-abcdefghij', 'model_writing' => 'gemini-3.8-flash',
                'model_quick' => 'gemini-3.5-flash-lite', 'default_limit' => 5000, 'naira_per_dollar' => 1600,
            ])->assertSessionHasNoErrors();
            $this->actingAs($this->teacher);
            $this->answer = $answer;
        }
    }

    private function question(array $extra = []): CbtQuestion
    {
        return CbtQuestion::create($extra + [
            'school_id' => $this->school->id, 'cbt_bank_id' => $this->bank->id, 'text' => 'What is 2 + 3?',
            'options' => ['4', '5', '6', '7'], 'correct_option' => 1, 'difficulty' => 'easy', 'topic' => 'Addition', 'marks' => 1,
        ]);
    }

    public function test_the_ai_drafts_objective_questions_with_the_right_answer_kept_after_shuffling(): void
    {
        $this->aiAnswers(['questions' => [
            ['text' => 'What is 10% of ₦500?', 'options' => ['A. ₦5', 'B. ₦50', 'C. ₦500', 'D. ₦5,000'], 'answer' => 'B', 'explanation' => '10/100 x 500 = 50', 'difficulty' => 'easy'],
            ['text' => 'Broken one', 'options' => ['1', '2', '3', '4'], 'answer' => 'E'],
            ['text' => 'Which is a prime number?', 'options' => ['9', '15', '21', '23'], 'answer' => 'D', 'difficulty' => 'hard'],
        ]]);

        $drafts = $this->postJson("/school/cbt/banks/{$this->bank->id}/ai", ['type' => 'objective', 'count' => 3, 'topic' => 'Percentages', 'difficulty' => 'mixed'])
            ->assertOk()->json('questions');

        // The broken one (no option E) is dropped; letters typed by the AI are taken off the options
        $this->assertCount(2, $drafts);
        $this->assertSame('₦50', $drafts[0]['options'][$drafts[0]['correct_option']]);
        $this->assertContains('₦5', $drafts[0]['options']);
        $this->assertSame('23', $drafts[1]['options'][$drafts[1]['correct_option']]);
        $this->assertSame(['easy', 'hard'], array_column($drafts, 'difficulty'));
        $this->assertSame('Percentages', $drafts[0]['topic']);

        // Nothing is saved yet, and the request is logged against the school's allowance
        $this->assertSame(0, CbtQuestion::count());
        $this->assertSame(1, AiUsage::where('feature', 'questions')->where('ok', true)->count());
        Http::assertSent(fn (HttpRequest $r) => str_contains($r['contents'][0]['parts'][0]['text'] ?? '', 'Class: JSS 2')
            && str_contains($r['contents'][0]['parts'][0]['text'], 'Subject: Mathematics'));

        // Options that point at other letters keep their order
        $kept = app(QuestionWriterService::class)->objective(['text' => 'Q', 'options' => ['x', 'y', 'Both A and B', 'Neither'], 'answer' => 'C']);
        $this->assertSame(['x', 'y', 'Both A and B', 'Neither'], $kept['options']);
        $this->assertSame(2, $kept['correct_option']);
    }

    public function test_theory_parts_are_lettered_and_their_marks_add_up_to_the_total(): void
    {
        $this->aiAnswers(['questions' => [[
            'text' => 'Ada saved ₦2,000 at 5% simple interest a year.',
            'marks' => 10,
            'parts' => [
                ['text' => 'Define simple interest.', 'marks' => 3, 'guide' => 'Interest on the principal only (3)'],
                ['text' => 'Find the interest after 2 years.', 'marks' => 3, 'guide' => 'I = PRT/100 = ₦200 (3)'],
            ],
        ]]]);

        $q = $this->postJson("/school/cbt/banks/{$this->bank->id}/ai", ['type' => 'theory', 'count' => 1, 'topic' => 'Simple interest', 'difficulty' => 'medium', 'parts' => 2, 'marks' => 10])
            ->assertOk()->json('questions.0');

        $this->assertSame("Ada saved ₦2,000 at 5% simple interest a year.\n(a) Define simple interest. [5 marks]\n(b) Find the interest after 2 years. [5 marks]", $q['text']);
        $this->assertSame("(a) Interest on the principal only (3)\n(b) I = PRT/100 = ₦200 (3)", $q['marking_guide']);
        $this->assertEquals(10, $q['marks']);
        $this->assertSame('medium', $q['difficulty']);

        $writer = app(QuestionWriterService::class);
        $this->assertSame([2.5, 2.5, 5.0], $writer->splitMarks([1, 1, 2], 10));
        $this->assertSame([3.5, 3.5, 3.0], $writer->splitMarks([1, 1, 1], 10));
        $this->assertSame([5.0, 5.0], $writer->splitMarks([0, 0], 10));
    }

    public function test_kept_drafts_are_saved_together_and_checked_like_any_question(): void
    {
        $url = "/school/cbt/banks/{$this->bank->id}/questions/many";
        $good = [
            ['type' => 'objective', 'text' => 'What is 3 x 4?', 'options' => ['7', '12'], 'correct_option' => 1, 'difficulty' => 'easy', 'marks' => 1, 'topic' => 'Times', 'explanation' => null, 'marking_guide' => null],
            ['type' => 'theory', 'text' => "(a) Define a fraction. [2 marks]\n(b) Add 1/2 and 1/4. [3 marks]", 'options' => [], 'correct_option' => null, 'marking_guide' => '(a) ... (b) 3/4', 'difficulty' => 'medium', 'marks' => 5, 'topic' => 'Fractions'],
        ];

        $bad = $good;
        $bad[0]['correct_option'] = 2;
        $this->post($url, ['questions' => $bad])->assertSessionHasErrors('questions.0.correct_option');
        $this->assertSame(0, CbtQuestion::count());

        $this->post($url, ['questions' => $good])->assertSessionHasNoErrors();
        $this->assertSame(2, $this->bank->questions()->count());
        $theory = $this->bank->questions()->where('type', 'theory')->first();
        $this->assertNull($theory->options);
        $this->assertSame('(a) ... (b) 3/4', $theory->marking_guide);
        $this->assertSame($this->teacher->id, $theory->created_by);

        // Another school's teacher cannot reach this bank, and a principal (view only) cannot add
        $this->actingAs($this->makeUser($this->makeSchool('Other'), 'teacher'))->post($url, ['questions' => $good])->assertNotFound();
        $this->actingAs($this->makeUser($this->school, 'principal'))->post($url, ['questions' => $good])->assertForbidden();
    }

    public function test_a_paper_is_built_from_this_schools_questions_and_prints_with_its_marking_scheme(): void
    {
        $obj = [$this->question(), $this->question(['text' => 'What is 9 - 4?', 'correct_option' => 1])];
        $theory = $this->question(['type' => 'theory', 'text' => "(a) What is a prime number? [2 marks]\n(b) List three. [3 marks]", 'options' => null, 'correct_option' => null, 'marking_guide' => '(a) ... (b) 2, 3, 5', 'marks' => 5]);
        $otherSchool = $this->makeSchool('Other');
        $foreign = CbtQuestion::withoutGlobalScopes()->create(['school_id' => $otherSchool->id, 'cbt_bank_id' => CbtBank::withoutGlobalScopes()->create(['school_id' => $otherSchool->id, 'name' => 'X'])->id,
            'text' => 'Not yours', 'options' => ['a', 'b'], 'correct_option' => 0, 'difficulty' => 'easy', 'marks' => 1]);

        $paper = [
            'title' => 'First Term Examination', 'class_id' => $this->class->id, 'subject_id' => $this->subject->id, 'duration_minutes' => 90,
            'instructions' => 'Write your name.', 'show_marks' => true,
            'sections' => [
                ['title' => 'Section A: Objectives', 'instructions' => 'Answer all.', 'questions' => [$obj[0]->id, $obj[1]->id]],
                ['title' => 'Section B: Theory', 'instructions' => null, 'questions' => [$theory->id]],
            ],
        ];

        $this->post('/school/cbt/papers', array_replace_recursive($paper, ['sections' => [1 => ['questions' => [$theory->id, $foreign->id]]]]))->assertSessionHasErrors('sections');
        $this->post('/school/cbt/papers', array_replace_recursive($paper, ['sections' => [1 => ['questions' => [$obj[0]->id]]]]))->assertSessionHasErrors('sections');
        $empty = $paper;
        $empty['sections'][0]['questions'] = $empty['sections'][1]['questions'] = [];
        $this->post('/school/cbt/papers', $empty)->assertSessionHasErrors('sections');
        $this->assertSame(0, ExamPaper::count());

        $this->post('/school/cbt/papers', $paper)->assertSessionHasNoErrors();
        $saved = ExamPaper::firstOrFail();
        $this->assertSame([$obj[0]->id, $obj[1]->id, $theory->id], $saved->questionIds());
        $this->assertSame('1 hour 30 minutes', $saved->durationText());

        // A question deleted from its bank still prints on the paper
        $obj[1]->delete();
        $filled = $saved->fresh()->filled();
        $this->assertCount(2, $filled[0]['questions']);

        $this->get("/school/cbt/papers/{$saved->id}")->assertOk()->assertInertia(fn ($p) => $p
            ->where("questions.{$obj[1]->id}.deleted", true)->where('initial.sections.1.questions.0', $theory->id));
        $this->get('/school/cbt/papers')->assertOk()->assertInertia(fn ($p) => $p->where('papers.0.questions', 3)->where('papers.0.marks', 7));

        $pdf = $this->get("/school/cbt/papers/{$saved->id}/pdf")->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
        $this->get("/school/cbt/papers/{$saved->id}/marking-scheme")->assertOk();

        $html = view('exam-papers.paper', ['paper' => $saved->fresh(), 'sections' => $saved->fresh()->filled(), 'images' => [], 'total' => 7.0,
            'school' => ['name' => 'Paper School', 'address' => null, 'phone' => null, 'email' => null, 'motto' => null, 'logo' => null], 'color' => '#333', 'answers' => true])->render();
        $this->assertStringContainsString('MARKING SCHEME', $html);
        $this->assertStringContainsString('<b>1.</b> B', $html);
        $this->assertStringContainsString('2, 3, 5', $html);

        // Moving a question and saving keeps the new order
        $this->put("/school/cbt/papers/{$saved->id}", array_replace_recursive($paper, ['sections' => [0 => ['questions' => [$obj[1]->id, $obj[0]->id]]]]))->assertSessionHasNoErrors();
        $this->assertSame([$obj[1]->id, $obj[0]->id, $theory->id], $saved->fresh()->questionIds());

        // A principal can view and print, but not change; another school cannot see it at all
        $this->actingAs($this->makeUser($this->school, 'principal'));
        $this->get("/school/cbt/papers/{$saved->id}/pdf")->assertOk();
        $this->put("/school/cbt/papers/{$saved->id}", $paper)->assertForbidden();
        $this->actingAs($this->makeUser($otherSchool, 'teacher'))->get("/school/cbt/papers/{$saved->id}/pdf")->assertNotFound();
    }

    public function test_the_picker_lists_one_banks_questions_of_one_kind(): void
    {
        $this->question();
        $this->question(['type' => 'theory', 'text' => 'Explain.', 'options' => null, 'correct_option' => null, 'marks' => 4]);

        $this->getJson("/school/cbt/papers/questions?bank={$this->bank->id}")->assertOk()->assertJsonCount(2, 'questions');
        $this->getJson("/school/cbt/papers/questions?bank={$this->bank->id}&type=theory")->assertOk()->assertJsonCount(1, 'questions')->assertJsonPath('questions.0.text', 'Explain.');

        $this->actingAs($this->makeUser($this->makeSchool('Other'), 'teacher'))
            ->getJson("/school/cbt/papers/questions?bank={$this->bank->id}")->assertStatus(422);
    }
}
