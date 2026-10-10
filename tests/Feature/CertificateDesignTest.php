<?php

namespace Tests\Feature;

use App\Models\CertificateTemplate;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCertificate;
use App\Models\User;
use App\Services\CertificateService;
use Tests\Feature\Security\SecurityTestCase;

/** The certificate designer: saving a design, the preview, the QR code, and issued certificates keeping their look. */
class CertificateDesignTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private Student $ada;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = $this->makeSchool('Design School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'SS 3', 'numeric_name' => 12]);
        $this->ada = Student::create([
            'school_id' => $this->school->id, 'class_id' => $class->id, 'first_name' => 'Ada', 'last_name' => 'Obi', 'admission_no' => 'ADM-7',
            'gender' => 'female', 'status' => 'active', 'date_of_birth' => '2008-03-01', 'admission_date' => '2020-09-14',
        ]);
    }

    private function design(array $changes = []): array
    {
        return $changes + [
            'border' => 'modern', 'primary_color' => '#0f766e', 'accent_color' => null,
            'title' => 'Leaving Testimonial', 'body' => "{name} was a {conduct} student.\n\n{he_she} served well.", 'closing' => 'Goodbye, {name}.',
            'show_details' => false, 'show_qr' => true, 'show_watermark' => false,
        ];
    }

    private function issue(): StudentCertificate
    {
        $this->post("/school/students/{$this->ada->id}/certificates", [
            'type' => 'testimonial', 'mark_left' => false,
            'details' => ['date_admitted' => '2020-09-14', 'date_left' => '2026-07-24', 'class_admitted' => 'JSS 1', 'last_class' => 'SS 3', 'conduct' => 'Very good'],
        ])->assertSessionHasNoErrors();

        return StudentCertificate::latest('id')->first();
    }

    public function test_the_page_starts_with_the_standard_designs(): void
    {
        $this->get('/school/certificates/designs', ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.templates.0.type', 'testimonial')
            ->assertJsonPath('props.templates.0.border', 'classic')
            ->assertJsonPath('props.templates.1.title', 'Transfer Certificate')
            ->assertJsonPath('props.canEdit', true);
    }

    public function test_saving_a_design_and_unknown_blanks(): void
    {
        $this->post('/school/certificates/designs/testimonial', $this->design())->assertSessionHasNoErrors();
        $saved = CertificateTemplate::for($this->school->id, 'testimonial');
        $this->assertSame('modern', $saved->border);
        $this->assertSame('Leaving Testimonial', $saved->title);
        $this->assertFalse($saved->show_details);

        $this->post('/school/certificates/designs/testimonial', $this->design(['body' => 'Dear {nmae}']))->assertSessionHasErrors('body');
        $this->post('/school/certificates/designs/testimonial', $this->design(['border' => 'fancy']))->assertSessionHasErrors('border');
        $this->post('/school/certificates/designs/report', $this->design())->assertNotFound();
        $this->assertSame('Leaving Testimonial', $saved->fresh()->title);
    }

    public function test_the_wording_is_filled_in_and_escaped(): void
    {
        $this->post('/school/certificates/designs/testimonial', $this->design())->assertSessionHasNoErrors();
        $this->ada->update(['first_name' => '<b>Ada</b>']);
        $data = app(CertificateService::class)->pdfData($this->issue());

        $this->assertSame('Leaving Testimonial', $data['title']);
        $this->assertCount(2, $data['paragraphs']);
        $this->assertStringContainsString('&lt;B&gt;ADA&lt;/B&gt; OBI', $data['paragraphs'][0]);
        $this->assertStringContainsString('very good', $data['paragraphs'][0]);
        $this->assertStringStartsWith('She served well', strip_tags($data['paragraphs'][1]), 'a blank at the start of a sentence gets a capital');
        $this->assertStringStartsWith('data:image/png;base64,', $data['qr']);
    }

    public function test_issued_certificates_keep_their_look_after_the_design_changes(): void
    {
        $certificate = $this->issue();
        $this->assertSame('classic', $certificate->details['layout']['border']);

        $this->post('/school/certificates/designs/testimonial', $this->design())->assertSessionHasNoErrors();

        $data = app(CertificateService::class)->pdfData($certificate->fresh());
        $this->assertSame('Testimonial', $data['title']);
        $this->assertSame('classic', $data['layout']['border']);
        $this->assertSame('modern', app(CertificateService::class)->pdfData($this->issue())['layout']['border']);
    }

    public function test_the_preview_is_a_pdf(): void
    {
        foreach (CertificateTemplate::BORDERS as $border) {
            $this->post('/school/certificates/designs/transfer', $this->design(['border' => $border]))->assertSessionHasNoErrors();
            $this->get('/school/certificates/designs/transfer/preview')->assertOk()->assertHeader('content-type', 'application/pdf');
        }
    }

    public function test_who_may_change_designs(): void
    {
        $principal = $this->makeUser($this->school, 'principal');
        $this->actingAs($principal);
        $this->get('/school/certificates/designs', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.canEdit', false);
        $this->get('/school/certificates/designs/testimonial/preview')->assertOk();
        $this->post('/school/certificates/designs/testimonial', $this->design())->assertForbidden();

        $this->actingAs($this->makeUser($this->school, 'teacher'));
        $this->get('/school/certificates/designs')->assertForbidden();

        // Another school's design is its own
        $this->actingAs($this->makeUser($this->makeSchool('Other'), 'school-admin'));
        $this->post('/school/certificates/designs/testimonial', $this->design(['title' => 'Other title']))->assertSessionHasNoErrors();
        $this->assertSame('Testimonial', CertificateTemplate::for($this->school->id, 'testimonial')->title);
    }
}
