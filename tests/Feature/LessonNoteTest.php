<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\LessonNoteFormat;
use App\Models\LessonPlan;
use App\Models\SchemeOfWork;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Security\SecurityTestCase;

/** Schemes of work, lesson note formats, lesson notes (written, drafted by the AI, approved, printed) */
class LessonNoteTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private User $principal;
    private User $teacher;
    private SchoolClass $class;
    private Subject $subject;
    private Term $term;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->school = $this->makeSchool('Notes School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->principal = $this->makeUser($this->school, 'principal');
        $this->teacher = $this->makeUser($this->school, 'teacher');

        $this->actingAs($this->admin);
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2025/2026', 'start_date' => '2025-09-08', 'end_date' => '2026-07-24', 'is_current' => true]);
        $this->term = $year->terms()->orderBy('sequence')->first();
        $this->term->update(['start_date' => '2025-09-10']);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'SS 1', 'numeric_name' => 10]);
        $this->subject = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'Mathematics', 'code' => 'MTH']);
    }

    private array $answer = [];

    private bool $faked = false;

    /** Gemini answers with this JSON; the platform AI is switched on with a key */
    private function aiAnswers(array $answer): void
    {
        $this->answer = $answer;
        if (! $this->faked) {
            $this->faked = true;
            Http::fake(['generativelanguage.googleapis.com/*' => fn () => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode($this->answer)]]], 'finishReason' => 'STOP']],
                'usageMetadata' => ['promptTokenCount' => 2000, 'candidatesTokenCount' => 1500],
            ])]);
            $this->answer = ['ok' => true];
            $owner = $this->makeUser($this->school, 'super-admin');
            $this->actingAs($owner)->post('/super-admin/ai', [
                'enabled' => true, 'api_key' => 'AIzaTestKey-1234567890-abcdefghij', 'model_writing' => 'gemini-3.8-flash',
                'model_quick' => 'gemini-3.5-flash-lite', 'default_limit' => 5000, 'naira_per_dollar' => 1600,
            ])->assertSessionHasNoErrors();
            $this->answer = $answer;
        }
    }

    private function scheme(): SchemeOfWork
    {
        return SchemeOfWork::create([
            'school_id' => $this->school->id, 'class_id' => $this->class->id, 'subject_id' => $this->subject->id, 'term_id' => $this->term->id,
            'title' => 'Maths SS 1', 'weeks' => [
                ['week' => 1, 'topic' => 'Number bases', 'details' => 'Conversion from base 10'],
                ['week' => 2, 'topic' => 'Modular arithmetic', 'details' => ''],
            ],
        ]);
    }

    private function base(): array
    {
        return ['class_id' => $this->class->id, 'subject_id' => $this->subject->id, 'term_id' => $this->term->id];
    }

    public function test_typed_weeks_are_read_without_the_ai_and_the_scheme_keeps_its_file(): void
    {
        $this->actingAs($this->teacher);
        $typed = "Week 1: Number bases\nConversion from base 10\n- base 2\nWk 2 - Modular arithmetic\n3) Indices\n3. Indices again\nRevision";

        $weeks = $this->postJson('/school/lesson-notes/schemes/read', $this->base() + ['text' => $typed, 'use_ai' => false])->assertOk()->json('weeks');
        $this->assertSame([1, 2, 3], array_column($weeks, 'week'));
        $this->assertSame('Number bases', $weeks[0]['topic']);
        $this->assertSame("Conversion from base 10\nbase 2", $weeks[0]['details']);
        // The second "week 3" line replaces the first, and the unnumbered line after it is its content
        $this->assertSame('Indices again', $weeks[2]['topic']);

        $this->postJson('/school/lesson-notes/schemes/read', $this->base() + ['text' => 'Just some words', 'use_ai' => false])->assertStatus(422)->assertJsonValidationErrors('source');
        $this->postJson('/school/lesson-notes/schemes/read', $this->base() + ['file' => UploadedFile::fake()->create('s.pdf', 50, 'application/pdf'), 'use_ai' => false])
            ->assertStatus(422)->assertJsonValidationErrors('source');

        $this->post('/school/lesson-notes/schemes', $this->base() + ['weeks' => $weeks, 'file' => UploadedFile::fake()->create('scheme.pdf', 50, 'application/pdf')])
            ->assertSessionHasNoErrors();
        $scheme = SchemeOfWork::sole();
        $this->assertSame('Mathematics · SS 1', $scheme->title);
        $this->assertCount(3, $scheme->weeks);
        $this->assertSame('scheme.pdf', $scheme->source_name);
        Storage::disk('private')->assertExists($scheme->source_path);
        $this->get("/school/lesson-notes/schemes/{$scheme->id}/file")->assertOk();

        // The same week number twice is refused
        $this->post("/school/lesson-notes/schemes/{$scheme->id}", $this->base() + ['weeks' => [['week' => 1, 'topic' => 'A'], ['week' => 1, 'topic' => 'B']]])
            ->assertSessionHasErrors('weeks');
    }

    public function test_the_ai_reads_a_pdf_or_word_scheme_into_weeks(): void
    {
        $this->aiAnswers(['weeks' => [
            ['week' => 2, 'topic' => '  Modular   arithmetic ', 'details' => 'Clock arithmetic'],
            ['week' => 1, 'topic' => 'Number bases', 'details' => ''],
            ['week' => 1, 'topic' => 'Duplicate', 'details' => ''],
            ['week' => 40, 'topic' => 'Too far', 'details' => ''],
        ]]);
        $this->actingAs($this->teacher);

        $weeks = $this->post('/school/lesson-notes/schemes/read', $this->base() + ['file' => UploadedFile::fake()->createWithContent('scheme.pdf', '%PDF-1.4 fake'), 'use_ai' => true], ['Accept' => 'application/json'])
            ->assertOk()->json('weeks');
        $this->assertSame([['week' => 1, 'topic' => 'Number bases', 'details' => ''], ['week' => 2, 'topic' => 'Modular arithmetic', 'details' => 'Clock arithmetic']], $weeks);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'gemini-3.8-flash')
            && ($r['contents'][0]['parts'][0]['inlineData']['mimeType'] ?? null) === 'application/pdf'
            && base64_decode($r['contents'][0]['parts'][0]['inlineData']['data']) === '%PDF-1.4 fake');

        // A Word file is read on our side and only its text is sent
        $docx = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new \ZipArchive;
        $zip->open($docx, \ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml', '<w:document><w:body><w:p><w:r><w:t>WEEK 1 Number bases &amp; numerals</w:t></w:r></w:p><w:p><w:r><w:t>WEEK 2 Modular arithmetic</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();
        $this->post('/school/lesson-notes/schemes/read', $this->base() + ['file' => new UploadedFile($docx, 'scheme.docx', null, null, true), 'use_ai' => true], ['Accept' => 'application/json'])->assertOk();
        Http::assertSent(fn (HttpRequest $r) => str_contains($r['contents'][0]['parts'][0]['text'] ?? '', "WEEK 1 Number bases & numerals\nWEEK 2 Modular arithmetic"));

        $this->assertSame(2, \App\Models\AiUsage::where('feature', 'lesson_notes')->count());
    }

    public function test_a_teacher_writes_a_note_from_the_scheme_with_the_ai_and_a_head_approves_it(): void
    {
        $scheme = $this->scheme();
        $sections = collect(LessonNoteFormat::PRESETS['Standard lesson note'])->mapWithKeys(fn ($s) => [$s[0] => 'About '.$s[1]])->all();
        $sections['presentation'] = "**Step 1**: count in base 2\nStep 2: convert";
        $this->aiAnswers($sections);
        $this->actingAs($this->teacher);

        // Picking week 1 of the scheme fills the topic and the Monday of week 1
        $this->get("/school/lesson-notes/new?scheme={$scheme->id}&week=1")->assertOk()->assertInertia(fn ($p) => $p
            ->component('SchoolAdmin/LessonNotes/Note', false)
            ->where('values.title', 'Number bases')
            ->where('values.week', 1)
            ->where('values.week_start', '2025-09-08')
            ->has('formats', 2));
        $format = LessonNoteFormat::where('is_default', true)->sole();
        $this->assertSame('Standard lesson note', $format->name);

        $draft = $this->postJson('/school/lesson-notes/ai', $this->base() + [
            'scheme_of_work_id' => $scheme->id, 'week' => 2, 'topic' => 'Modular arithmetic', 'lesson_note_format_id' => $format->id,
            'duration_minutes' => 40, 'instructions' => 'Use a clock example',
        ])->assertOk()->json('sections');
        $this->assertSame("Step 1: count in base 2\nStep 2: convert", $draft['presentation']);
        Http::assertSent(fn (HttpRequest $r) => str_contains($prompt = $r['contents'][0]['parts'][0]['text'] ?? '', 'Topic: Modular arithmetic')
            && str_contains($prompt, "Last week's topic: Number bases") && str_contains($prompt, 'Use a clock example')
            && str_contains($prompt, '- presentation: Presentation') && str_contains($prompt, 'Class: SS 1'));

        $this->post('/school/lesson-notes', $this->base() + [
            'scheme_of_work_id' => $scheme->id, 'lesson_note_format_id' => $format->id, 'week' => 2, 'week_start' => '2025-09-15',
            'duration_minutes' => 40, 'title' => 'Modular arithmetic', 'sections' => $draft + ['made_up' => ''], 'ai_drafted' => true, 'submit' => true,
        ])->assertSessionHasNoErrors();
        $note = LessonPlan::sole();
        $this->assertSame('submitted', $note->status);
        $this->assertTrue($note->ai_drafted);
        $this->assertSame($this->teacher->id, $note->created_by);
        $this->assertArrayNotHasKey('made_up', $note->sections);

        // Once sent, the teacher cannot change it
        $this->put("/school/lesson-notes/{$note->id}", $this->base() + ['lesson_note_format_id' => $format->id, 'week_start' => '2025-09-15', 'title' => 'X', 'sections' => []])->assertForbidden();

        // Sending back needs a reason; then the teacher can change it again
        $this->actingAs($this->principal);
        $this->put("/school/lesson-notes/{$note->id}/review", ['action' => 'rejected'])->assertSessionHasErrors('reviewer_feedback');
        $this->put("/school/lesson-notes/{$note->id}/review", ['action' => 'rejected', 'reviewer_feedback' => 'Add an evaluation question'])->assertSessionHasNoErrors();
        $this->actingAs($this->teacher);
        $this->put("/school/lesson-notes/{$note->id}", $this->base() + [
            'lesson_note_format_id' => $format->id, 'week_start' => '2025-09-15', 'title' => 'Modular arithmetic', 'sections' => ['evaluation' => 'What is 7 mod 3?'], 'submit' => true,
        ])->assertSessionHasNoErrors();
        $this->assertSame('submitted', $note->fresh()->status);
        $this->assertNull($note->fresh()->reviewer_feedback);

        $this->actingAs($this->principal);
        $this->put("/school/lesson-notes/{$note->id}/review", ['action' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $note->fresh()->status);
        $this->put("/school/lesson-notes/{$note->id}/review", ['action' => 'approved'])->assertSessionHasErrors('action');

        // Teachers cannot approve
        $this->actingAs($this->teacher)->put("/school/lesson-notes/{$note->id}/review", ['action' => 'approved'])->assertForbidden();

        // It prints as an A4 PDF
        $pdf = $this->get("/school/lesson-notes/{$note->id}/pdf")->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
    }

    public function test_teachers_only_see_and_change_their_own_notes(): void
    {
        LessonNoteFormat::ensureFor($this->school->id);
        $format = LessonNoteFormat::where('is_default', true)->sole();
        $other = $this->makeUser($this->school, 'teacher');
        $this->actingAs($other);
        $this->post('/school/lesson-notes', $this->base() + ['lesson_note_format_id' => $format->id, 'week_start' => '2025-09-08', 'title' => 'Theirs', 'sections' => ['objectives' => 'x']])->assertSessionHasNoErrors();
        $note = LessonPlan::sole();

        $this->actingAs($this->teacher);
        $this->get('/school/lesson-notes')->assertInertia(fn ($p) => $p->where('notes.total', 0)->where('mine', true));
        $this->put("/school/lesson-notes/{$note->id}", $this->base() + ['lesson_note_format_id' => $format->id, 'week_start' => '2025-09-08', 'title' => 'Mine now', 'sections' => []])->assertForbidden();
        $this->delete("/school/lesson-notes/{$note->id}")->assertForbidden();

        // Heads see everyone's
        $this->actingAs($this->principal)->get('/school/lesson-notes')->assertInertia(fn ($p) => $p->where('notes.total', 1));

        // A note from another school cannot be opened
        $elsewhere = $this->makeSchool('Elsewhere');
        $this->actingAs($this->makeUser($elsewhere, 'school-admin'))->get("/school/lesson-notes/{$note->id}")->assertNotFound();
    }

    public function test_formats_keep_their_keys_and_old_lesson_plans_still_open(): void
    {
        $this->actingAs($this->principal);
        $this->get('/school/lesson-notes/formats')->assertOk()->assertInertia(fn ($p) => $p->has('formats', 2));

        $this->post('/school/lesson-notes/formats', ['name' => 'Ours', 'sections' => [['label' => 'Aim'], ['label' => 'Activity'], ['label' => 'Activity', 'hint' => 'Second']], 'is_default' => true])
            ->assertSessionHasNoErrors();
        $ours = LessonNoteFormat::where('name', 'Ours')->sole();
        $this->assertSame(['aim', 'activity', 'activity_2'], array_column($ours->sections, 'key'));
        $this->assertTrue($ours->is_default);
        $this->assertSame(1, LessonNoteFormat::where('is_default', true)->count());

        // Renaming one heading keeps the others' keys, so notes keep their text
        $this->put("/school/lesson-notes/formats/{$ours->id}", ['name' => 'Ours', 'sections' => [['label' => 'Aim'], ['label' => 'Class activity']]])->assertSessionHasNoErrors();
        $this->assertSame(['aim', 'class_activity'], array_column($ours->fresh()->sections, 'key'));
        $this->delete("/school/lesson-notes/formats/{$ours->id}")->assertSessionHasErrors('format');
        $this->post('/school/lesson-notes/formats', ['name' => 'Ours', 'sections' => [['label' => 'X']]])->assertSessionHasErrors('name');

        // Teachers cannot change formats
        $this->actingAs($this->teacher)->post('/school/lesson-notes/formats', ['name' => 'Mine', 'sections' => [['label' => 'X']]])->assertForbidden();

        // A plan written before formats existed opens with its old parts, and the old address leads here
        $old = LessonPlan::create($this->base() + ['school_id' => $this->school->id, 'title' => 'Old plan', 'objectives' => 'Old aims', 'content' => 'Old content', 'week_start' => '2025-09-08', 'status' => 'draft']);
        $this->actingAs($this->principal);
        $this->get('/school/homework/lesson-plans')->assertRedirect(route('school.lesson-notes.index'));
        $this->get("/school/lesson-notes/{$old->id}")->assertOk()->assertInertia(fn ($p) => $p->where('values.sections.objectives', 'Old aims')->where('values.sections.content', 'Old content'));

        // Saving it in the new format keeps the old text that has no heading there
        $this->actingAs($this->admin);
        $this->put("/school/lesson-notes/{$old->id}", $this->base() + ['lesson_note_format_id' => $ours->id, 'week_start' => '2025-09-08', 'title' => 'Old plan', 'sections' => ['aim' => 'New aim', 'content' => 'Old content']])
            ->assertSessionHasNoErrors();
        $body = collect($old->fresh()->body())->pluck('text', 'label')->all();
        $this->assertSame(['Aim' => 'New aim', 'Class activity' => '', 'Content' => 'Old content'], $body);
    }
}
