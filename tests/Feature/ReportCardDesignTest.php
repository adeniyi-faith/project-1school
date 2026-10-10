<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Guardian;
use App\Models\ReportCardDesign;
use App\Models\ReportCardRemark;
use App\Models\ResultSheet;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\ReportCardService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Security\SecurityTestCase;

/** Report card designs, signers with the school's own titles, signatures, and student photos. */
class ReportCardDesignTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private SchoolClass $primary;
    private SchoolClass $jss1;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');

        $this->school = $this->makeSchool('Design School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);
        $this->primary = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Primary 5', 'numeric_name' => 5]);
        $this->jss1 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
    }

    private function default(): ReportCardDesign
    {
        return ReportCardDesign::where('school_id', $this->school->id)->where('is_default', true)->sole();
    }

    private function payload(ReportCardDesign $design, array $overrides = []): array
    {
        return array_replace_recursive([
            'name' => $design->name, 'is_default' => $design->is_default, 'template' => 'modern',
            'primary_color' => '#0f766e', 'accent_color' => '#f59e0b', 'font_size' => 'large', 'paper' => 'a4',
            'term_title' => 'Terminal Report', 'session_title' => 'Cumulative Report', 'footer_note' => 'Not valid without the school stamp.',
            'options' => ['show_photo' => true, 'show_class_stats' => false],
            'signers' => $design->signers()->get()->map(fn ($s) => [
                'id' => $s->id, 'label' => $s->label, 'name' => null, 'has_comment' => true, 'writer_permission' => $s->writer_permission,
            ])->all(),
        ], $overrides);
    }

    public function test_every_school_starts_with_a_default_design_with_class_teacher_and_principal(): void
    {
        $design = $this->default();

        $this->assertSame('classic', $design->template);
        $this->assertSame(['Class Teacher', 'Principal'], $design->signers()->pluck('label')->all());
        $this->assertTrue($design->settings()['show_logo'], 'the logo is on by default');
        $this->assertFalse($design->settings()['show_photo']);
        $this->assertSame($design->id, ReportCardDesign::forClass($this->school->id, $this->jss1->id)->id);
    }

    public function test_a_design_is_changed_with_new_titles_signers_signature_and_stamp(): void
    {
        $design = $this->default();
        $payload = $this->payload($design);
        $payload['signers'][1]['label'] = 'Head Teacher';
        $payload['signers'][1]['name'] = 'Mrs A. Bello';
        $payload['signers'][1]['signature'] = UploadedFile::fake()->image('sign.png', 200, 60);
        $payload['signers'][] = ['label' => 'Proprietress', 'has_comment' => false, 'writer_permission' => 'results.publish'];
        $payload['stamp'] = UploadedFile::fake()->image('stamp.png', 120, 120);

        $this->post("/school/academics/report-card-designs/{$design->id}", $payload)->assertSessionHasNoErrors();

        $design->refresh();
        $this->assertSame(['modern', '#0f766e', 'large', 'Terminal Report'], [$design->template, $design->primary_color, $design->font_size, $design->term_title]);
        $this->assertTrue($design->settings()['show_photo']);
        $this->assertFalse($design->settings()['show_class_stats']);
        $this->assertTrue($design->settings()['show_logo'], 'switches not sent keep their value');
        $signers = $design->signers()->get();
        $this->assertSame(['Class Teacher', 'Head Teacher', 'Proprietress'], $signers->pluck('label')->all());
        $this->assertSame('Mrs A. Bello', $signers[1]->name);
        Storage::disk('private')->assertExists($signers[1]->signature_path);
        Storage::disk('private')->assertExists($design->stamp_path);

        // What the PDF gets
        $printed = app(ReportCardService::class)->design($design);
        $this->assertStringStartsWith('data:image/png;base64,', $printed['signers'][1]['signature']);
        $this->assertStringStartsWith('data:image/png;base64,', $printed['stamp']);
        $this->assertSame(11, $printed['font_size']);

        // Removing a signer removes them and their comments
        $payload = $this->payload($design->fresh());
        array_pop($payload['signers']);
        $this->post("/school/academics/report-card-designs/{$design->id}", $payload)->assertSessionHasNoErrors();
        $this->assertSame(2, $design->signers()->count());
    }

    public function test_bad_colours_files_and_writers_are_refused(): void
    {
        $design = $this->default();

        $this->post("/school/academics/report-card-designs/{$design->id}", $this->payload($design, ['primary_color' => 'red']))->assertSessionHasErrors('primary_color');
        $this->post("/school/academics/report-card-designs/{$design->id}", $this->payload($design, ['template' => 'fancy']))->assertSessionHasErrors('template');
        $bad = $this->payload($design);
        $bad['signers'][0]['writer_permission'] = 'users.delete';
        $this->post("/school/academics/report-card-designs/{$design->id}", $bad)->assertSessionHasErrors('signers.0.writer_permission');
        $bad = $this->payload($design, ['stamp' => UploadedFile::fake()->create('stamp.pdf', 10, 'application/pdf')]);
        $this->post("/school/academics/report-card-designs/{$design->id}", $bad)->assertSessionHasErrors('stamp');
    }

    public function test_classes_get_their_own_design_and_fall_back_to_the_default(): void
    {
        $this->post('/school/academics/report-card-designs', ['name' => 'Primary section', 'copy_from' => $this->default()->id])->assertSessionHasNoErrors();
        $primaryDesign = ReportCardDesign::where('name', 'Primary section')->sole();
        $this->assertSame(['Class Teacher', 'Principal'], $primaryDesign->signers()->pluck('label')->all(), 'signers are copied');

        $this->post("/school/academics/report-card-designs/{$primaryDesign->id}/classes", ['class_ids' => [$this->primary->id]])->assertSessionHasNoErrors();
        $this->assertSame($primaryDesign->id, ReportCardDesign::forClass($this->school->id, $this->primary->id)->id);
        $this->assertSame($this->default()->id, ReportCardDesign::forClass($this->school->id, $this->jss1->id)->id);

        // The default cannot be deleted; deleting another sends its classes back to the default
        $this->delete("/school/academics/report-card-designs/{$this->default()->id}")->assertSessionHas('error');
        $this->delete("/school/academics/report-card-designs/{$primaryDesign->id}")->assertSessionHasNoErrors();
        $this->assertNull($this->primary->fresh()->report_card_design_id);

        // Making another design the default moves the flag
        $this->post('/school/academics/report-card-designs', ['name' => 'Secondary']);
        $secondary = ReportCardDesign::where('name', 'Secondary')->sole();
        $this->post("/school/academics/report-card-designs/{$secondary->id}", $this->payload($secondary, ['is_default' => true]))->assertSessionHasNoErrors();
        $this->assertSame($secondary->id, $this->default()->id);
    }

    public function test_the_preview_can_show_on_screen_as_a_web_page(): void
    {
        $design = $this->default();

        $this->get("/school/academics/report-card-designs/{$design->id}/preview?view=html")
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8')
            ->assertSee('Download PDF', false)
            ->assertSee('width=device-width', false);
    }

    public function test_the_preview_prints_a_sample_card_with_the_design(): void
    {
        $design = $this->default();

        $this->get("/school/academics/report-card-designs/{$design->id}/preview")->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get("/school/academics/report-card-designs/{$design->id}/preview?type=session")->assertOk()->assertHeader('content-type', 'application/pdf');

        foreach (ReportCardDesign::TEMPLATES as $template) {
            $design->update(['template' => $template, 'paper' => 'letter']);
            $this->get("/school/academics/report-card-designs/{$design->id}/preview")->assertOk();
        }
    }

    public function test_only_settings_editors_change_designs_and_only_in_their_school(): void
    {
        $design = $this->default();
        $teacher = $this->makeUser($this->school, 'teacher');
        $this->actingAs($teacher)->get('/school/academics/report-card-designs', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.canEdit', false);
        $this->actingAs($teacher)->post("/school/academics/report-card-designs/{$design->id}", $this->payload($design))->assertForbidden();

        $other = $this->makeUser($this->makeSchool('Other'), 'school-admin');
        $this->actingAs($other)->post("/school/academics/report-card-designs/{$design->id}", $this->payload($design))->assertNotFound();
        $this->actingAs($other)->get("/school/academics/report-card-designs/{$design->id}/preview")->assertNotFound();
    }

    public function test_comments_follow_the_signers_of_the_class_design(): void
    {
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2025/2026', 'start_date' => '2025-09-08', 'end_date' => '2026-07-24', 'is_current' => true]);
        $sheet = ResultSheet::create(['school_id' => $this->school->id, 'term_id' => $year->terms()->first()->id, 'class_id' => $this->jss1->id]);
        $design = $this->default();
        $payload = $this->payload($design);
        $payload['signers'][] = ['label' => 'Academic Director', 'has_comment' => true, 'writer_permission' => 'results.publish'];
        $this->post("/school/academics/report-card-designs/{$design->id}", $payload)->assertSessionHasNoErrors();

        $this->get("/school/results/{$sheet->id}/report-cards", ['X-Inertia' => 'true'])
            ->assertJsonCount(3, 'props.signers')
            ->assertJsonPath('props.signers.2.label', 'Academic Director');

        // A signer from another design cannot be used on this class
        $this->post('/school/academics/report-card-designs', ['name' => 'Other design']);
        $foreign = ReportCardDesign::where('name', 'Other design')->sole()->signers()->first();
        $this->post("/school/results/{$sheet->id}/comments", ['signer_id' => $foreign->id, 'comments' => []])->assertNotFound();
        $this->assertSame(0, ReportCardRemark::count());
    }

    public function test_student_photos_are_private_and_shown_only_to_the_right_people(): void
    {
        $ada = Student::create(['school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'first_name' => 'Ada', 'gender' => 'female', 'status' => 'active']);
        $bayo = Student::create(['school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'first_name' => 'Bayo', 'gender' => 'male', 'status' => 'active']);

        $this->post("/school/students/{$ada->id}/photo", ['photo' => UploadedFile::fake()->image('ada.jpg')])->assertSessionHasNoErrors();
        $ada->refresh();
        Storage::disk('private')->assertExists($ada->photo);
        $this->assertStringContainsString("/photos/students/{$ada->id}", $ada->photo_url);
        $this->get("/photos/students/{$ada->id}")->assertOk();

        // Bulk: files named after admission numbers
        $this->post('/school/students/photos', ['photos' => [
            UploadedFile::fake()->image("{$bayo->admission_no}.png"),
            UploadedFile::fake()->image('NOBODY-1.jpg'),
        ]])->assertSessionHas('info');
        $this->assertNotNull($bayo->fresh()->photo);

        // Wrong file type
        $this->post("/school/students/{$ada->id}/photo", ['photo' => UploadedFile::fake()->create('ada.pdf', 5, 'application/pdf')])->assertSessionHasErrors('photo');

        // Parent of Ada sees Ada's photo, not Bayo's; another school's admin sees neither
        $parentUser = User::factory()->create(['school_id' => $this->school->id, 'status' => 'active']);
        $parentUser->assignRole('parent');
        $guardian = Guardian::create(['school_id' => $this->school->id, 'user_id' => $parentUser->id, 'name' => 'Mrs Ada', 'phone' => '0803']);
        $ada->update(['guardian_id' => $guardian->id]);
        $this->actingAs($parentUser)->get("/photos/students/{$ada->id}")->assertOk();
        $this->actingAs($parentUser)->get("/photos/students/{$bayo->id}")->assertNotFound();
        $this->actingAs($this->makeUser($this->makeSchool('Other'), 'school-admin'))->get("/photos/students/{$ada->id}")->assertNotFound();

        // With "Student photo" on, the photo is printed on the card
        $this->actingAs($this->admin);
        $this->default()->update(['options' => ['show_photo' => true]]);
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2025/2026', 'start_date' => '2025-09-08', 'end_date' => '2026-07-24', 'is_current' => true]);
        $sheet = ResultSheet::create(['school_id' => $this->school->id, 'term_id' => $year->terms()->first()->id, 'class_id' => $this->jss1->id, 'class_average' => 50]);
        \App\Models\TermResultSummary::create(['school_id' => $this->school->id, 'result_sheet_id' => $sheet->id, 'student_id' => $ada->id, 'subjects_count' => 0, 'total_score' => 0, 'average' => 50, 'position' => 1, 'class_size' => 1]);
        $card = app(ReportCardService::class)->termCards($sheet)[0];
        $this->assertStringStartsWith('data:image/jpeg;base64,', $card['student']['photo']);
    }
}
