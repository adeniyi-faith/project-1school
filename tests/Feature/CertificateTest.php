<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCertificate;
use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;

/** Testimonials and transfer certificates: issuing, numbering, the PDF, revoking and the public check. */
class CertificateTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private Student $ada;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = $this->makeSchool('Cert School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'SS 3', 'numeric_name' => 12]);
        $this->ada = Student::create([
            'school_id' => $this->school->id, 'class_id' => $class->id, 'first_name' => 'Ada', 'last_name' => 'Obi', 'admission_no' => 'ADM-7',
            'gender' => 'female', 'status' => 'active', 'date_of_birth' => '2008-03-01', 'admission_date' => '2020-09-14',
        ]);
    }

    private function issue(string $type, array $details = [], array $extra = [])
    {
        return $this->post("/school/students/{$this->ada->id}/certificates", $extra + [
            'type' => $type,
            'mark_left' => false,
            'details' => $details + [
                'date_admitted' => '2020-09-14', 'date_left' => '2026-07-24', 'class_admitted' => 'JSS 1', 'last_class' => 'SS 3',
                'conduct' => 'Very good', 'academic_ability' => 'Excellent', 'offices_held' => 'Head girl', 'exams' => 'WAEC SSCE 2026',
                'reason_for_leaving' => 'Finished secondary school', 'fees_cleared' => true,
            ],
        ]);
    }

    public function test_the_form_starts_from_the_students_record(): void
    {
        $this->get("/school/students/{$this->ada->id}/certificates/new?type=transfer", ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.type', 'transfer')
            ->assertJsonPath('props.defaults.date_admitted', '2020-09-14')
            ->assertJsonPath('props.defaults.last_class', 'SS 3')
            ->assertJsonPath('props.defaults.fees_cleared', true)
            ->assertJsonPath('props.signers.0.label', 'Principal');
    }

    public function test_issuing_numbers_each_type_separately_and_keeps_what_was_printed(): void
    {
        $this->issue('testimonial', [], ['mark_left' => true])->assertSessionHasNoErrors();
        $this->issue('testimonial')->assertSessionHasNoErrors();
        $this->issue('transfer')->assertSessionHasNoErrors();

        $year = now()->year;
        $this->assertSame(["TST/{$year}/0001", "TST/{$year}/0002", "TC/{$year}/0001"], StudentCertificate::orderBy('id')->pluck('serial')->all());
        $first = StudentCertificate::orderBy('id')->first();
        $this->assertSame('Ada Obi', $first->details['name']);
        $this->assertSame('Head girl', $first->details['offices_held']);
        $this->assertArrayNotHasKey('reason_for_leaving', $first->details, 'transfer-only details are not kept on a testimonial');
        $this->assertSame(10, strlen($first->verify_code));
        $this->assertSame('alumni', $this->ada->fresh()->status, 'a testimonial can mark the student as left');

        // Renaming the student later does not change the certificate
        $this->ada->update(['first_name' => 'Adaeze']);
        $this->assertSame('Ada Obi', $first->fresh()->details['name']);
    }

    public function test_the_pdf_and_revoking(): void
    {
        $this->issue('transfer', [], ['mark_left' => true]);
        $certificate = StudentCertificate::sole();
        $this->assertSame('transferred', $this->ada->fresh()->status);

        $this->get("/school/certificates/{$certificate->id}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->post("/school/certificates/{$certificate->id}/revoke", ['reason' => ''])->assertSessionHasErrors('reason');
        $this->post("/school/certificates/{$certificate->id}/revoke", ['reason' => 'Wrong leaving date'])->assertSessionHasNoErrors();
        $this->assertTrue($certificate->fresh()->isRevoked());
        $this->get("/school/certificates/{$certificate->id}/pdf")->assertOk();
    }

    public function test_anyone_can_check_a_certificate_without_signing_in(): void
    {
        $this->issue('testimonial');
        $certificate = StudentCertificate::sole();
        auth()->logout();

        $this->get('/verify/'.$certificate->verify_code)->assertOk()->assertSee('Genuine')->assertSee('Ada Obi')->assertSee('Cert School')
            ->assertDontSee('2008', false);
        $this->get('/verify/'.strtolower($certificate->verify_code))->assertOk()->assertSee('Genuine');
        $this->get('/verify/NOSUCHCODE')->assertOk()->assertSee('Not found');

        $certificate->update(['revoked_at' => now(), 'revoke_reason' => 'x']);
        $this->get('/verify/'.$certificate->verify_code)->assertOk()->assertSee('Revoked')->assertDontSee('Genuine');
    }

    public function test_who_may_issue_and_other_schools(): void
    {
        $this->issue('testimonial');
        $certificate = StudentCertificate::sole();

        $teacher = $this->makeUser($this->school, 'teacher');
        $this->actingAs($teacher);
        $this->issue('testimonial')->assertForbidden();
        $this->post("/school/certificates/{$certificate->id}/revoke", ['reason' => 'x'])->assertForbidden();

        $this->actingAs($this->makeUser($this->school, 'principal'));
        $this->issue('transfer')->assertSessionHasNoErrors();

        $this->actingAs($this->makeUser($this->makeSchool('Other'), 'school-admin'));
        $this->get("/school/certificates/{$certificate->id}/pdf")->assertNotFound();
        $this->get("/school/students/{$this->ada->id}/certificates/new")->assertNotFound();
        $this->get('/school/certificates', ['X-Inertia' => 'true'])->assertJsonCount(0, 'props.certificates.data');
    }

    public function test_the_leaving_date_cannot_be_before_admission(): void
    {
        $this->issue('testimonial', ['date_left' => '2019-01-01'])->assertSessionHasErrors('details.date_left');
        $this->assertSame(0, StudentCertificate::count());
    }
}
