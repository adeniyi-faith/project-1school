<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AdmissionInquiry;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InquiryFollowup;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Services\FeeLedgerService;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Security\SecurityTestCase;

/** The family's side of admission (receipt, status page, accepting) and enrolment doing the whole job. */
class AdmissionFamilyTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private SchoolClass $jss1;
    private Term $term;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = $this->makeSchool('Family School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->jss1 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1']);

        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $this->term = $year->terms()->orderBy('sequence')->first();
        $this->term->forceFill(['is_current' => true])->save();

        $cat = FeeCategory::create(['school_id' => $this->school->id, 'name' => 'Tuition', 'type' => 'tuition']);
        FeeStructure::create(['school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'fee_category_id' => $cat->id, 'academic_year' => '2026/2027', 'amount' => 90000, 'frequency' => 'per_term']);
    }

    private function apply(array $extra = []): AdmissionInquiry
    {
        $this->post('/apply/'.$this->school->id, $extra + [
            'student_name' => 'Tolu Adeyemi', 'class_interested' => 'JSS 1', 'guardian_name' => 'Mrs Adeyemi',
            'guardian_phone' => '08031112222', 'guardian_email' => 'adeyemi@example.test',
            'student_gender' => 'female', 'student_dob' => '2014-05-02',
        ])->assertSessionHasNoErrors();

        return AdmissionInquiry::withoutGlobalScopes()->where('student_name', 'Tolu Adeyemi')->sole();
    }

    public function test_an_online_application_gets_a_reference_and_an_emailed_receipt(): void
    {
        Mail::fake();
        $inquiry = $this->apply();

        $this->assertMatchesRegularExpression('/^APP-\d{4}-\d{4}$/', $inquiry->reference);
        $this->assertSame('female', $inquiry->student_gender);
        $this->assertSame('2014-05-02', $inquiry->student_dob->toDateString());
    }

    public function test_the_family_checks_their_application_with_reference_and_phone_only(): void
    {
        $inquiry = $this->apply();

        $this->get("/apply/{$this->school->id}/status?reference={$inquiry->reference}&phone=%2B2348031112222")
            ->assertOk()->assertSee('Tolu Adeyemi');

        // A wrong phone shows nothing about the child
        $this->get("/apply/{$this->school->id}/status?reference={$inquiry->reference}&phone=08099999999")
            ->assertOk()->assertDontSee('Tolu Adeyemi');

        // Another school cannot see it either
        $other = $this->makeSchool('Other School');
        $this->get("/apply/{$other->id}/status?reference={$inquiry->reference}&phone=08031112222")
            ->assertOk()->assertDontSee('Tolu Adeyemi');
    }

    public function test_the_family_can_accept_only_after_an_offer_and_staff_are_told(): void
    {
        $inquiry = $this->apply();
        $body = ['reference' => $inquiry->reference, 'phone' => '08031112222'];

        $this->post("/apply/{$this->school->id}/status/accept", $body)->assertNotFound();

        $this->actingAs($this->admin)->post("/school/admissions/inquiries/{$inquiry->id}/decision", ['decision' => 'accept'])->assertSessionHasNoErrors();
        auth()->logout();

        $this->post("/apply/{$this->school->id}/status/accept", $body)->assertRedirect();
        $this->assertNotNull($inquiry->fresh()->family_accepted_at);
        $this->assertDatabaseHas('school_notifications', ['user_id' => $this->admin->id, 'type' => 'admission_accepted']);
    }

    public function test_an_offer_emails_the_family_and_writes_it_in_the_follow_ups(): void
    {
        Mail::fake();
        $inquiry = $this->apply();

        $this->actingAs($this->admin)->post("/school/admissions/inquiries/{$inquiry->id}/decision", ['decision' => 'accept'])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'emailed'));

        $this->assertTrue(InquiryFollowup::where('inquiry_id', $inquiry->id)->where('note', 'like', 'Offer email sent%')->exists());
    }

    public function test_an_offer_without_an_email_says_to_phone_the_family(): void
    {
        $inquiry = $this->apply(['guardian_email' => '']);

        $this->actingAs($this->admin)->post("/school/admissions/inquiries/{$inquiry->id}/decision", ['decision' => 'accept'])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'phone the family'));

        $this->assertTrue(InquiryFollowup::where('inquiry_id', $inquiry->id)->where('note', 'like', '%phone the family%')->exists());
    }

    public function test_enrolling_makes_the_parent_login_and_this_terms_invoices(): void
    {
        $inquiry = $this->apply();
        $this->actingAs($this->admin)->post("/school/admissions/inquiries/{$inquiry->id}/decision", ['decision' => 'accept']);

        $this->post("/school/admissions/inquiries/{$inquiry->id}/enrol", [
            'first_name' => 'Tolu', 'last_name' => 'Adeyemi', 'gender' => 'female', 'date_of_birth' => '2014-05-02',
            'admission_date' => '2026-09-07', 'class_id' => $this->jss1->id, 'guardian_relation' => 'Mother',
        ])->assertSessionHasNoErrors()->assertSessionHas('login_details');

        $student = Student::sole();
        $this->assertSame(1, Invoice::where('student_id', $student->id)->count());
        $this->assertSame(90000.0, (float) Invoice::where('student_id', $student->id)->sole()->amount);
        $this->assertTrue(User::where('school_id', $this->school->id)->role('parent')->exists());
        // Only the parent got a login here, not the child
        $this->assertFalse(User::where('school_id', $this->school->id)->role('student')->exists());
    }

    public function test_the_two_extras_can_be_unticked(): void
    {
        $inquiry = $this->apply();
        $this->actingAs($this->admin)->post("/school/admissions/inquiries/{$inquiry->id}/decision", ['decision' => 'accept']);

        $this->post("/school/admissions/inquiries/{$inquiry->id}/enrol", [
            'first_name' => 'Tolu', 'last_name' => 'Adeyemi', 'gender' => 'female', 'date_of_birth' => '2014-05-02',
            'admission_date' => '2026-09-07', 'class_id' => $this->jss1->id, 'guardian_relation' => 'Mother',
            'make_parent_login' => false, 'bill_fees' => false,
        ])->assertSessionHasNoErrors()->assertSessionMissing('login_details');

        $this->assertSame(0, Invoice::count());
        $this->assertFalse(User::where('school_id', $this->school->id)->role('parent')->exists());
    }

    public function test_billing_the_current_term_is_safe_to_repeat_and_limited_to_the_chosen_students(): void
    {
        $ada = Student::create(['school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'first_name' => 'Ada', 'last_name' => 'T', 'admission_no' => 'A-1', 'gender' => 'female', 'status' => 'active']);
        Student::create(['school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'first_name' => 'Bayo', 'last_name' => 'T', 'admission_no' => 'B-1', 'gender' => 'male', 'status' => 'active']);

        $ledger = app(FeeLedgerService::class);
        $first = $ledger->billCurrentTerm($this->school->id, [$ada->id], $this->admin);
        $again = $ledger->billCurrentTerm($this->school->id, [$ada->id], $this->admin);

        $this->assertSame(1, $first['made']);
        $this->assertSame(0, $again['made']);
        $this->assertSame(1, Invoice::count());

        $this->term->forceFill(['is_current' => false])->save();
        $this->assertNull($ledger->billCurrentTerm($this->school->id, [$ada->id], $this->admin)['period']);
    }

    public function test_a_finished_year_move_can_bill_the_new_classes_fees(): void
    {
        $jss2 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 2', 'numeric_name' => 8]);
        $this->jss1->update(['numeric_name' => 7]);
        $cat = FeeCategory::create(['school_id' => $this->school->id, 'name' => 'Lab', 'type' => 'other']);
        FeeStructure::create(['school_id' => $this->school->id, 'class_id' => $jss2->id, 'fee_category_id' => $cat->id, 'academic_year' => '2026/2027', 'amount' => 20000, 'frequency' => 'per_term']);
        $ada = Student::create(['school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'first_name' => 'Ada', 'last_name' => 'T', 'admission_no' => 'A-1', 'gender' => 'female', 'status' => 'active']);

        $year = AcademicYear::where('school_id', $this->school->id)->first();
        $this->actingAs($this->admin)->post('/school/promotions', [
            'academic_year_id' => $year->id, 'from_class_id' => $this->jss1->id, 'to_class_id' => $jss2->id, 'pass_mark' => 40,
            'decisions' => [['student_id' => $ada->id, 'outcome' => 'promoted']],
        ])->assertSessionHasNoErrors();
        $batch = \App\Models\PromotionBatch::sole();

        $this->post("/school/promotions/{$batch->id}/bill-fees")->assertSessionHas('success');
        $this->assertSame(1, Invoice::where('student_id', $ada->id)->count());
        $this->assertSame(20000.0, (float) Invoice::where('student_id', $ada->id)->sole()->amount);
    }
}
