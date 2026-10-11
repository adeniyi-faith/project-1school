<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AiUsage;
use App\Models\AssessmentScheme;
use App\Models\PlatformSetting;
use App\Models\ReportCardDesign;
use App\Models\ReportCardRemark;
use App\Models\ResultSheet;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Security\SecurityTestCase;

/** The AI (Gemini): the platform owner's key and allowances, each school's switch, usage costs, and AI report card comments */
class AiTest extends SecurityTestCase
{
    private const KEY = 'AIzaTestKey-1234567890-abcdefghij';

    private School $school;
    private User $owner;
    private User $admin;
    private ResultSheet $sheet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('AI School');
        $this->owner = $this->makeUser($this->school, 'super-admin');
        $this->admin = $this->makeUser($this->school, 'school-admin');

        // A class with results: Ada 85, Bayo 45
        $this->actingAs($this->admin);
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2025/2026', 'start_date' => '2025-09-08', 'end_date' => '2026-07-24', 'is_current' => true]);
        $part = AssessmentScheme::where('is_default', true)->sole()->components->pluck('id', 'short_name')->all();
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        $subject = Subject::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'name' => 'Mathematics', 'code' => 'MTH']);
        $this->sheet = ResultSheet::create(['school_id' => $this->school->id, 'term_id' => $year->terms()->orderBy('sequence')->first()->id, 'class_id' => $class->id]);
        $scores = [];
        foreach ([['Ada', 'female', 18, 17, 50], ['Bayo', 'male', 10, 5, 30]] as [$first, $gender, $ca1, $ca2, $exam]) {
            $student = Student::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'first_name' => $first, 'last_name' => 'Okafor', 'gender' => $gender, 'status' => 'active']);
            foreach (['CA1' => $ca1, 'CA2' => $ca2, 'Exam' => $exam] as $short => $score) {
                $scores[] = ['student_id' => $student->id, 'component_id' => $part[$short], 'score' => $score];
            }
        }
        $this->post("/school/results/{$this->sheet->id}/scores", ['subject_id' => $subject->id, 'scores' => $scores])->assertSessionHasNoErrors();
    }

    /** Gemini answers every request with this text (or JSON), counting 1,000,000 tokens in and 100,000 out */
    private function fakeGemini(string|array $answer, int $status = 200): void
    {
        $this->answer = [$answer, $status];
        if ($this->faked) {
            return;
        }
        $this->faked = true;
        // One fake that reads the answer set last, since later Http::fake calls would not replace the first
        Http::fake(['generativelanguage.googleapis.com/*' => function () {
            [$answer, $status] = $this->answer;

            return Http::response($status === 200 ? [
                'candidates' => [['content' => ['parts' => [['text' => is_array($answer) ? json_encode($answer) : $answer]]], 'finishReason' => 'STOP']],
                'usageMetadata' => ['promptTokenCount' => 1_000_000, 'candidatesTokenCount' => 60_000, 'thoughtsTokenCount' => 40_000],
            ] : ['error' => ['message' => $answer]], $status);
        }]);
    }

    private array $answer = ['', 200];

    private bool $faked = false;

    private function switchOn(array $extra = []): void
    {
        $this->fakeGemini('Key works.');
        $this->actingAs($this->owner);
        $this->post('/super-admin/ai', $extra + [
            'enabled' => true, 'api_key' => self::KEY, 'model_writing' => 'gemini-3.8-flash', 'model_quick' => 'gemini-3.5-flash-lite',
            'default_limit' => 5000, 'naira_per_dollar' => 1600,
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->admin);
    }

    private function teacherSigner()
    {
        return ReportCardDesign::forClass($this->school->id, $this->sheet->class_id)->signers()->where('label', 'Class Teacher')->sole();
    }

    private function studentIds(): array
    {
        return Student::where('class_id', $this->sheet->class_id)->orderBy('first_name')->pluck('id')->all();
    }

    public function test_only_the_platform_owner_sets_the_key_and_it_is_stored_encrypted(): void
    {
        $this->post('/super-admin/ai', ['enabled' => true, 'api_key' => self::KEY])->assertForbidden();
        $this->get('/super-admin/ai')->assertForbidden();

        $this->switchOn();
        $stored = PlatformSetting::where('key', 'ai_api_key')->value('value');
        $this->assertNotSame(self::KEY, $stored);
        $this->assertStringNotContainsString('AIza', $stored);
        $this->assertSame(self::KEY, app(AiService::class)->key());

        // The key never goes to the browser, on the AI page or the general settings page
        $this->actingAs($this->owner);
        $this->get('/super-admin/ai')->assertOk()->assertDontSee(self::KEY)->assertDontSee($stored, false)
            ->assertInertia(fn ($p) => $p->where('settings.has_key', true)->where('settings.key_hint', '…ghij')->where('settings.prices.writing.in', 0.75));
        $this->get('/super-admin/settings')->assertOk()->assertDontSee($stored, false);
    }

    public function test_a_key_google_refuses_is_not_saved(): void
    {
        $this->fakeGemini('API key not valid. Please pass a valid API key.', 400);
        $this->actingAs($this->owner);
        $this->post('/super-admin/ai', ['enabled' => true, 'api_key' => self::KEY, 'model_writing' => 'gemini-3.8-flash', 'model_quick' => 'gemini-3.5-flash-lite', 'default_limit' => 5000, 'naira_per_dollar' => 1600])
            ->assertSessionHasErrors('api_key');
        $this->assertNull(PlatformSetting::get('ai_api_key'));
    }

    public function test_ai_comments_are_drafted_without_names_and_not_saved(): void
    {
        $this->switchOn();
        $this->fakeGemini(['comments' => [
            ['n' => 1, 'comment' => '{name} has done very well this term, especially in Mathematics.'],
            ['n' => 2, 'comment' => '{name} should give more time to Mathematics next term.'],
        ]]);
        [$ada, $bayo] = $this->studentIds();

        $this->get("/school/results/{$this->sheet->id}/report-cards")->assertInertia(fn ($p) => $p->where('ai.shown', true)->where('ai.reason', null));
        $this->postJson("/school/results/{$this->sheet->id}/comments/ai", ['signer_id' => $this->teacherSigner()->id, 'student_ids' => [$ada, $bayo]])
            ->assertOk()
            ->assertJsonPath("comments.{$ada}", 'Ada has done very well this term, especially in Mathematics.')
            ->assertJsonPath("comments.{$bayo}", 'Bayo should give more time to Mathematics next term.');

        // What went to Google: scores and boy/girl, never names
        Http::assertSent(function (HttpRequest $r) {
            $body = json_encode($r->data());

            return str_contains($r->url(), 'gemini-3.5-flash-lite:generateContent') && $r->hasHeader('x-goog-api-key', self::KEY)
                && str_contains($body, 'Student 1, a girl: average 85') && str_contains($body, 'Mathematics')
                && ! str_contains($body, 'Ada') && ! str_contains($body, 'Okafor');
        });
        $this->assertSame(0, ReportCardRemark::count());

        // Cost: 1m tokens in at $0.30 + 100k out at $2.50 = $0.55, at ₦1,600 = ₦880
        $usage = AiUsage::sole();
        $this->assertSame('result_comments', $usage->feature);
        $this->assertSame(100_000, $usage->output_tokens);
        $this->assertEqualsWithDelta(880, $usage->cost_naira, 0.001);
        $this->assertSame($this->admin->id, $usage->user_id);
    }

    public function test_a_school_can_switch_ai_or_one_tool_off(): void
    {
        $this->switchOn();
        $this->fakeGemini(['comments' => []]);
        $signer = $this->teacherSigner()->id;

        $this->post('/school/settings/ai', ['on' => true, 'tools_off' => ['result_comments']])->assertSessionHasNoErrors();
        $this->postJson("/school/results/{$this->sheet->id}/comments/ai", ['signer_id' => $signer, 'student_ids' => $this->studentIds()])
            ->assertStatus(422)->assertJsonPath('errors.ai.0', 'Your school has switched off AI for report card comments.');

        $this->post('/school/settings/ai', ['on' => false])->assertSessionHasNoErrors();
        $this->get('/school/settings/ai')->assertInertia(fn ($p) => $p->component('SchoolAdmin/Settings/Ai', false)->where('state.school_off', true));
        $this->postJson("/school/results/{$this->sheet->id}/comments/ai", ['signer_id' => $signer, 'student_ids' => $this->studentIds()])->assertStatus(422);
        Http::assertSentCount(1);   // only the key check when it was saved

        // a teacher can see the page but not change the switches
        $this->actingAs($this->makeUser($this->school, 'teacher'));
        $this->post('/school/settings/ai', ['on' => true])->assertForbidden();
    }

    public function test_the_monthly_allowance_and_the_owner_switch_stop_a_school(): void
    {
        $this->switchOn();
        $signer = $this->teacherSigner()->id;
        AiUsage::create(['school_id' => $this->school->id, 'feature' => 'result_comments', 'model' => 'x', 'cost_naira' => 5000]);
        $this->fakeGemini(['comments' => []]);

        $this->postJson("/school/results/{$this->sheet->id}/comments/ai", ['signer_id' => $signer, 'student_ids' => $this->studentIds()])
            ->assertStatus(422)->assertJsonPath('errors.ai.0', "Your school has used this month's AI allowance. It starts again on the 1st.");

        // the owner gives this school more, then switches it off
        $this->actingAs($this->owner);
        $this->post("/super-admin/ai/schools/{$this->school->id}", ['blocked' => false, 'limit' => 20000])->assertSessionHasNoErrors();
        $this->assertNull(app(AiService::class)->unavailable($this->school->id, 'result_comments'));
        $this->get('/super-admin/ai')->assertInertia(fn ($p) => $p->where('schools.0.used', 5000)->where('schools.0.limit', 20000)->where('schools.0.custom_limit', true));

        $this->post("/super-admin/ai/schools/{$this->school->id}", ['blocked' => true])->assertSessionHasNoErrors();
        $this->assertSame('AI has been switched off for this school by the platform owner.', app(AiService::class)->unavailable($this->school->id, 'result_comments'));

        // last month's spending does not count
        $this->post("/super-admin/ai/schools/{$this->school->id}", ['blocked' => false])->assertSessionHasNoErrors();
        AiUsage::query()->update(['created_at' => now()->subMonth()]);
        $this->assertSame(0.0, app(AiService::class)->usedThisMonth($this->school->id));
    }

    public function test_a_failed_request_is_logged_and_explained(): void
    {
        $this->switchOn();
        $this->fakeGemini('Resource has been exhausted', 429);

        $this->postJson("/school/results/{$this->sheet->id}/comments/ai", ['signer_id' => $this->teacherSigner()->id, 'student_ids' => $this->studentIds()])
            ->assertStatus(422)->assertJsonPath('errors.ai.0', 'The AI is busy or the platform has used up its Google allowance. Try again in a few minutes.');
        $this->assertFalse(AiUsage::sole()->ok);
    }

    public function test_nothing_is_offered_while_the_platform_has_no_ai(): void
    {
        Http::fake();
        $this->get("/school/results/{$this->sheet->id}/report-cards")->assertInertia(fn ($p) => $p->where('ai.shown', false)->where('ai.reason', 'AI is not available on this platform yet.'));
        $this->postJson("/school/results/{$this->sheet->id}/comments/ai", ['signer_id' => $this->teacherSigner()->id, 'student_ids' => $this->studentIds()])->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_another_schools_students_are_never_sent(): void
    {
        $this->switchOn();
        $other = $this->makeSchool('Other School');
        $stranger = Student::create(['school_id' => $other->id, 'class_id' => SchoolClass::withoutGlobalScopes()->create(['school_id' => $other->id, 'name' => 'X'])->id, 'first_name' => 'Zed', 'last_name' => 'Q', 'gender' => 'male', 'status' => 'active']);
        $this->fakeGemini(['comments' => [['n' => 1, 'comment' => 'x']]]);

        $this->postJson("/school/results/{$this->sheet->id}/comments/ai", ['signer_id' => $this->teacherSigner()->id, 'student_ids' => [$stranger->id]])
            ->assertOk()->assertExactJson(['comments' => []]);
        Http::assertSentCount(1);   // only the key check when it was saved

        $signer = $this->teacherSigner()->id;
        $ids = $this->studentIds();
        $this->actingAs($this->makeUser($other, 'school-admin'));
        $this->postJson("/school/results/{$this->sheet->id}/comments/ai", ['signer_id' => $signer, 'student_ids' => $ids])->assertNotFound();
    }
}
