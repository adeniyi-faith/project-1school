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
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode(['intro' => 'Hello'])]]], 'finishReason' => 'STOP']],
            'usageMetadata' => ['promptTokenCount' => 1000, 'candidatesTokenCount' => 500],
        ])]);
    }

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
}
