<?php

namespace Tests\Feature;

use App\Ai\AiStyles;
use App\Models\PlatformSetting;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Security\SecurityTestCase;

/** The style a teacher picks for AI lesson notes and questions: what they see, and what it changes behind the scenes */
class AiStylesTest extends SecurityTestCase
{
    private School $school;
    private User $teacher;
    private SchoolClass $class;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('Styles School');
        $this->teacher = $this->makeUser($this->school, 'teacher');
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'SS 1', 'numeric_name' => 10]);
        $this->subject = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'Mathematics', 'code' => 'MTH']);

        PlatformSetting::set('ai_enabled', '1', 'ai');
        app(AiService::class)->saveKey('AIzaTestKey-1234567890-abcdefghij');
        $this->reply = [
            'candidates' => [['content' => ['parts' => [['text' => json_encode(['intro' => 'Hello'])]]], 'finishReason' => 'STOP']],
            'usageMetadata' => ['promptTokenCount' => 1000, 'candidatesTokenCount' => 500],
        ];
        // One fake that reads the reply set last, since a later Http::fake would not replace the first
        Http::fake(['generativelanguage.googleapis.com/*' => fn () => Http::response($this->reply)]);
    }

    private array $reply = [];

    private function draft(array $extra = []): void
    {
        $this->actingAs($this->teacher)->postJson('/school/lesson-notes/ai', $extra + [
            'class_id' => $this->class->id, 'subject_id' => $this->subject->id, 'topic' => 'Fractions',
        ])->assertOk();
    }

    public function test_an_unknown_or_missing_style_falls_back_to_the_default(): void
    {
        $this->assertSame('standard', AiStyles::resolve('lesson_note', null)['key']);
        $this->assertSame('standard', AiStyles::resolve('lesson_note', 'nonsense')['key']);
        $this->assertSame('quick', AiStyles::resolve('questions', 'quiz')['tier']);
    }

    public function test_teachers_only_get_names_and_hints_never_models_or_rules(): void
    {
        $choices = AiStyles::choices('lesson_note');
        $this->assertCount(4, $choices);
        foreach ($choices as $c) {
            $this->assertSame(['key', 'label', 'hint'], array_keys($c));
        }

        $this->actingAs($this->teacher)->get('/school/lesson-notes/new')->assertOk()->assertInertia(fn ($p) => $p
            ->has('ai.styles', 4)
            ->where('ai.default_style', 'standard')
            ->missing('ai.styles.0.tier')
            ->missing('ai.styles.0.rule'));
    }

    public function test_a_quick_style_uses_the_cheap_model_and_a_full_style_uses_the_stronger_one(): void
    {
        $this->draft(['style' => 'short']);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'gemini-3.5-flash-lite')
            && str_contains($r['systemInstruction']['parts'][0]['text'], 'Keep every heading brief'));

        $this->draft(['style' => 'detailed']);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'gemini-3.8-flash')
            && str_contains($r['systemInstruction']['parts'][0]['text'], 'worked examples'));
    }

    public function test_the_platform_owner_can_move_a_style_to_another_model_without_teachers_noticing(): void
    {
        PlatformSetting::set('ai_style_tiers', json_encode(['lesson_note.detailed' => 'quick']), 'ai');
        $this->assertSame('quick', AiStyles::resolve('lesson_note', 'detailed')['tier']);

        $this->draft(['style' => 'detailed']);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'gemini-3.5-flash-lite'));
    }

    public function test_a_teacher_makes_a_picture_in_a_chosen_style_and_the_school_pays_a_flat_price(): void
    {
        $bank = \App\Models\CbtBank::create(['school_id' => $this->school->id, 'name' => 'Maths bank', 'subject_id' => $this->subject->id]);
        $this->reply = ([
            'candidates' => [['content' => ['parts' => [['inlineData' => ['mimeType' => 'image/png', 'data' => base64_encode('PNGDATA')]]]], 'finishReason' => 'STOP']],
            'usageMetadata' => ['promptTokenCount' => 50, 'candidatesTokenCount' => 1290],
        ]);

        $r = $this->actingAs($this->teacher)->postJson("/school/cbt/banks/{$bank->id}/ai-image", ['description' => 'A farmer harvesting yams', 'style' => 'explainer'])->assertOk();
        $this->assertSame('data:image/png;base64,'.base64_encode('PNGDATA'), $r->json('image'));
        Http::assertSent(fn (HttpRequest $q) => str_contains($q->url(), 'gemini-3.1-flash-lite-image')
            && str_contains($q['contents'][0]['parts'][0]['text'], 'A farmer harvesting yams')
            && str_contains($q['contents'][0]['parts'][0]['text'], 'educational diagram'));

        $use = \App\Models\AiUsage::withoutGlobalScopes()->where('feature', 'images')->sole();
        $this->assertTrue($use->ok);
        $this->assertEqualsWithDelta(0.0336 * 1600, $use->cost_naira, 0.01);
    }

    public function test_a_refused_picture_costs_nothing_and_a_school_stops_at_its_picture_limit(): void
    {
        $bank = \App\Models\CbtBank::create(['school_id' => $this->school->id, 'name' => 'Maths bank', 'subject_id' => $this->subject->id]);
        $this->reply = ['candidates' => [['finishReason' => 'SAFETY']]];
        $this->actingAs($this->teacher)->postJson("/school/cbt/banks/{$bank->id}/ai-image", ['description' => 'Something unsuitable'])->assertStatus(422)->assertJsonValidationErrors('ai');
        $this->assertSame(0.0, (float) \App\Models\AiUsage::withoutGlobalScopes()->where('feature', 'images')->sole()->cost_naira);

        // Refused tries do not count; two real pictures reach a limit of two
        PlatformSetting::set('ai_image_cap', '2', 'ai');
        foreach (range(1, 2) as $i) {
            \App\Models\AiUsage::create(['school_id' => $this->school->id, 'user_id' => $this->teacher->id, 'feature' => 'images', 'model' => 'x', 'input_tokens' => 0, 'output_tokens' => 0, 'cost_naira' => 54, 'ok' => true]);
        }
        $this->actingAs($this->teacher)->postJson("/school/cbt/banks/{$bank->id}/ai-image", ['description' => 'A market scene'])
            ->assertStatus(422)->assertJsonPath('errors.ai.0', fn ($m) => str_contains($m, '2 pictures'));
    }

    public function test_the_question_page_shows_picture_styles_and_cost_but_no_model(): void
    {
        $bank = \App\Models\CbtBank::create(['school_id' => $this->school->id, 'name' => 'Maths bank', 'subject_id' => $this->subject->id]);
        $this->actingAs($this->teacher)->get("/school/cbt/banks/{$bank->id}")->assertOk()->assertInertia(fn ($p) => $p
            ->has('ai.images.styles', 3)
            ->where('ai.images.reason', null)
            ->where('ai.images.cost', 53.76)
            ->missing('ai.images.model')
            ->missing('ai.images.styles.0.rule'));
    }
}
