<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Scholarship;
use App\Models\Student;
use App\Models\StudentScholarship;
use App\Models\Term;
use App\Models\User;
use LogicException;
use Tests\Feature\Security\SecurityTestCase;

/** Invoices, the append-only ledger, and scholarships: the places where a bug costs someone money. */
class FeeLedgerTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private SchoolClass $class;
    private Term $term;
    private FeeStructure $tuition;
    private FeeStructure $bus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = $this->makeSchool('Fees School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);

        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $this->term = $year->terms()->first();
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 2']);

        $tuition = FeeCategory::create(['school_id' => $this->school->id, 'name' => 'Tuition', 'type' => 'tuition']);
        $bus = FeeCategory::create(['school_id' => $this->school->id, 'name' => 'School bus', 'type' => 'transport']);
        $this->tuition = FeeStructure::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'fee_category_id' => $tuition->id, 'academic_year' => '2026-2027', 'amount' => 150000, 'frequency' => 'one_time']);
        $this->bus = FeeStructure::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'fee_category_id' => $bus->id, 'academic_year' => '2026-2027', 'amount' => 40000, 'frequency' => 'one_time']);
    }

    private function student(string $first, string $status = 'active'): Student
    {
        return Student::create([
            'school_id' => $this->school->id, 'class_id' => $this->class->id, 'first_name' => $first, 'last_name' => 'Test',
            'admission_no' => strtoupper($first) . '-1', 'gender' => 'male', 'status' => $status,
        ]);
    }

    private function issue(array $structures): \Illuminate\Testing\TestResponse
    {
        return $this->post('/school/fees/invoices', ['fee_structure_ids' => array_map(fn ($s) => $s->id, $structures), 'term_id' => $this->term->id]);
    }

    private function invoiceFor(Student $student, FeeStructure $fee): Invoice
    {
        return Invoice::where('student_id', $student->id)->where('fee_structure_id', $fee->id)->sole();
    }

    public function test_billing_a_class_makes_one_invoice_per_active_student_per_fee_and_never_twice(): void
    {
        $this->student('Ade');
        $this->student('Bola');
        $this->student('Gone', 'inactive');

        $this->issue([$this->tuition, $this->bus])->assertSessionHasNoErrors();
        $this->assertSame(4, Invoice::count());

        // Running it again for the same term makes nothing new
        $this->issue([$this->tuition, $this->bus])->assertSessionHas('success', 'No new invoices: every student already has these for this period.');
        $this->assertSame(4, Invoice::count());

        $invoice = Invoice::where('fee_structure_id', $this->tuition->id)->first();
        $this->assertSame('2026/2027 · First Term', $invoice->period);
        $this->assertSame('unpaid', $invoice->status);
        $this->assertSame(150000.0, $invoice->balance);
        $this->assertMatchesRegularExpression('/^INV-\d{6}$/', $invoice->invoice_no);
        $this->assertSame(['charge'], LedgerEntry::where('invoice_id', $invoice->id)->pluck('type')->all());
    }

    public function test_part_payments_then_full_payment_settle_the_balance(): void
    {
        $ade = $this->student('Ade');
        $this->issue([$this->tuition]);
        $invoice = $this->invoiceFor($ade, $this->tuition);

        $this->post("/school/fees/invoices/{$invoice->id}/payments", ['amount' => 100000, 'method' => 'bank_transfer', 'entry_date' => now()->toDateString()])
            ->assertSessionHasNoErrors();
        $this->assertSame('partial', $invoice->fresh()->status);
        $this->assertSame(50000.0, $invoice->fresh()->balance);

        // Paying more than is owed is refused
        $this->post("/school/fees/invoices/{$invoice->id}/payments", ['amount' => 60000, 'method' => 'cash', 'entry_date' => now()->toDateString()])
            ->assertSessionHasErrors('amount');

        $this->post("/school/fees/invoices/{$invoice->id}/payments", ['amount' => 50000, 'method' => 'pos', 'entry_date' => now()->toDateString()]);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(0.0, $invoice->fresh()->balance);

        $payment = LedgerEntry::where('invoice_id', $invoice->id)->where('type', 'payment')->first();
        $this->assertSame(-100000.0, $payment->amount);
        $this->assertStringStartsWith('RCP-', $payment->reference);
        $this->assertSame($this->admin->id, $payment->recorded_by);
    }

    public function test_ledger_lines_can_never_be_edited_or_deleted(): void
    {
        $ade = $this->student('Ade');
        $this->issue([$this->tuition]);
        $line = LedgerEntry::where('invoice_id', $this->invoiceFor($ade, $this->tuition)->id)->first();

        try {
            $line->update(['amount' => 1]);
            $this->fail('A ledger line was changed.');
        } catch (LogicException) {
        }
        try {
            $line->delete();
            $this->fail('A ledger line was deleted.');
        } catch (LogicException) {
        }

        $this->assertSame(150000.0, $line->fresh()->amount);
    }

    public function test_a_wrong_payment_is_fixed_by_a_reversal_and_both_lines_stay(): void
    {
        $ade = $this->student('Ade');
        $this->issue([$this->tuition]);
        $invoice = $this->invoiceFor($ade, $this->tuition);
        $this->post("/school/fees/invoices/{$invoice->id}/payments", ['amount' => 15000, 'method' => 'cash', 'entry_date' => now()->toDateString()]);
        $wrong = LedgerEntry::where('type', 'payment')->sole();

        $this->post("/school/fees/invoices/{$invoice->id}/entries/{$wrong->id}/reverse", ['reason' => 'Typed 15,000 instead of 150,000'])
            ->assertSessionHasNoErrors();

        $this->assertSame(150000.0, $invoice->fresh()->balance);
        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertSame(['charge', 'payment', 'reversal'], LedgerEntry::where('invoice_id', $invoice->id)->orderBy('id')->pluck('type')->all());
        $this->assertSame(15000.0, LedgerEntry::where('type', 'reversal')->sole()->amount);

        // The same line cannot be reversed twice, and a reversal cannot be reversed
        $this->post("/school/fees/invoices/{$invoice->id}/entries/{$wrong->id}/reverse", ['reason' => 'again'])->assertSessionHasErrors('entry');
        $reversal = LedgerEntry::where('type', 'reversal')->sole();
        $this->post("/school/fees/invoices/{$invoice->id}/entries/{$reversal->id}/reverse", ['reason' => 'undo'])->assertSessionHasErrors('entry');
        $this->assertSame(150000.0, $invoice->fresh()->balance);
    }

    public function test_a_fine_adds_to_what_is_owed(): void
    {
        $ade = $this->student('Ade');
        $this->issue([$this->bus]);
        $invoice = $this->invoiceFor($ade, $this->bus);

        $this->post("/school/fees/invoices/{$invoice->id}/fines", ['amount' => 5000, 'note' => 'Paid after the due date'])->assertSessionHasNoErrors();

        $this->assertSame(45000.0, $invoice->fresh()->balance);
    }

    public function test_a_percentage_scholarship_comes_off_only_the_fee_it_covers(): void
    {
        $ade = $this->student('Ade');
        $staffChild = Scholarship::create(['school_id' => $this->school->id, 'name' => 'Staff child', 'type' => 'percent', 'value' => 50, 'fee_category_id' => $this->tuition->fee_category_id]);

        $this->post('/school/fees/scholarships/awards', ['admission_no' => 'ADE-1', 'scholarship_id' => $staffChild->id, 'note' => 'Mother teaches Maths'])
            ->assertSessionHasNoErrors();
        $award = StudentScholarship::sole();
        $this->assertSame($this->admin->id, $award->approved_by);

        $this->issue([$this->tuition, $this->bus]);

        $tuition = $this->invoiceFor($ade, $this->tuition);
        $this->assertSame(75000.0, $tuition->balance);
        $discount = LedgerEntry::where('invoice_id', $tuition->id)->where('type', 'discount')->sole();
        $this->assertSame('Staff child', $discount->note);
        $this->assertSame($award->id, $discount->student_scholarship_id);

        $this->assertSame(40000.0, $this->invoiceFor($ade, $this->bus)->balance, 'the bus fee is not covered');
    }

    public function test_a_scholarship_given_later_comes_off_open_invoices_once_and_never_below_zero(): void
    {
        $ade = $this->student('Ade');
        $this->issue([$this->bus]);
        $sibling = Scholarship::create(['school_id' => $this->school->id, 'name' => 'Sibling discount', 'type' => 'fixed', 'value' => 60000]);

        $this->post('/school/fees/scholarships/awards', ['admission_no' => 'ADE-1', 'scholarship_id' => $sibling->id, 'note' => 'Two siblings in school'])
            ->assertSessionHasNoErrors();

        $invoice = $this->invoiceFor($ade, $this->bus);
        $this->assertSame(0.0, $invoice->balance, 'a ₦60,000 discount on a ₦40,000 fee stops at zero');
        $this->assertSame('paid', $invoice->status);

        // Giving the same scholarship again is refused
        $this->post('/school/fees/scholarships/awards', ['admission_no' => 'ADE-1', 'scholarship_id' => $sibling->id, 'note' => 'again'])
            ->assertSessionHasErrors('scholarship_id');
        $this->assertSame(1, LedgerEntry::where('type', 'discount')->count());
    }

    public function test_a_revoked_scholarship_does_not_apply_to_new_invoices(): void
    {
        $ade = $this->student('Ade');
        $sch = Scholarship::create(['school_id' => $this->school->id, 'name' => 'Merit', 'type' => 'percent', 'value' => 10]);
        $this->post('/school/fees/scholarships/awards', ['admission_no' => 'ADE-1', 'scholarship_id' => $sch->id, 'note' => 'Top of class']);
        $award = StudentScholarship::sole();

        $this->post("/school/fees/scholarships/awards/{$award->id}/revoke")->assertSessionHasNoErrors();
        $this->issue([$this->tuition]);

        $this->assertSame(150000.0, $this->invoiceFor($ade, $this->tuition)->balance);
    }

    public function test_an_invoice_can_be_cancelled_only_while_nothing_is_paid(): void
    {
        $ade = $this->student('Ade');
        $this->issue([$this->tuition, $this->bus]);
        $tuition = $this->invoiceFor($ade, $this->tuition);
        $bus = $this->invoiceFor($ade, $this->bus);

        $this->post("/school/fees/invoices/{$tuition->id}/payments", ['amount' => 1000, 'method' => 'cash', 'entry_date' => now()->toDateString()]);
        $this->post("/school/fees/invoices/{$tuition->id}/void", ['reason' => 'Billed by mistake'])->assertSessionHasErrors('invoice');
        $this->assertSame('partial', $tuition->fresh()->status);

        $this->post("/school/fees/invoices/{$bus->id}/void", ['reason' => 'Does not take the bus'])->assertSessionHasNoErrors();
        $this->assertSame('void', $bus->fresh()->status);
        $this->assertSame(0.0, $bus->fresh()->balance);
        $this->assertSame(2, LedgerEntry::where('invoice_id', $bus->id)->count(), 'the charge and its reversal both stay');

        $this->post("/school/fees/invoices/{$bus->id}/payments", ['amount' => 100, 'method' => 'cash', 'entry_date' => now()->toDateString()])
            ->assertSessionHasErrors('amount');
    }

    public function test_who_can_do_what(): void
    {
        $ade = $this->student('Ade');
        $this->issue([$this->tuition]);
        $invoice = $this->invoiceFor($ade, $this->tuition);
        $pay = ['amount' => 1000, 'method' => 'cash', 'entry_date' => now()->toDateString()];

        $accountant = $this->makeUser($this->school, 'accountant');
        $this->actingAs($accountant)->post("/school/fees/invoices/{$invoice->id}/payments", $pay)->assertSessionHasNoErrors();

        $principal = $this->makeUser($this->school, 'principal');
        $this->actingAs($principal)->get('/school/fees/invoices', ['X-Inertia' => 'true'])->assertOk();
        $this->actingAs($principal)->post("/school/fees/invoices/{$invoice->id}/payments", $pay)->assertForbidden();
        $this->actingAs($principal)->post("/school/fees/invoices/{$invoice->id}/void", ['reason' => 'x'])->assertForbidden();

        $teacher = $this->makeUser($this->school, 'teacher');
        $this->actingAs($teacher)->get('/school/fees/invoices')->assertForbidden();
        $this->actingAs($teacher)->post('/school/fees/scholarships', ['name' => 'X', 'type' => 'percent', 'value' => 100])->assertForbidden();

        $this->assertSame(149000.0, $invoice->fresh()->balance);
    }

    public function test_one_school_cannot_see_or_touch_another_schools_invoices(): void
    {
        $ade = $this->student('Ade');
        $this->issue([$this->tuition]);
        $invoice = $this->invoiceFor($ade, $this->tuition);

        $other = $this->makeSchool('Other Fees School');
        $this->actingAs($this->makeUser($other, 'school-admin'));

        $this->get("/school/fees/invoices/{$invoice->id}", ['X-Inertia' => 'true'])->assertNotFound();
        $this->post("/school/fees/invoices/{$invoice->id}/payments", ['amount' => 1, 'method' => 'cash', 'entry_date' => now()->toDateString()])->assertNotFound();
        $this->post('/school/fees/invoices', ['fee_structure_ids' => [$this->tuition->id], 'term_id' => $this->term->id])
            ->assertSessionHasErrors(['fee_structure_ids.0', 'term_id']);
        $this->get('/school/fees/invoices', ['X-Inertia' => 'true'])->assertJsonCount(0, 'props.invoices.data');

        // Nor can it give a scholarship to this school's student
        $theirs = Scholarship::create(['school_id' => $other->id, 'name' => 'Theirs', 'type' => 'percent', 'value' => 100]);
        $this->post('/school/fees/scholarships/awards', ['admission_no' => 'ADE-1', 'scholarship_id' => $theirs->id, 'note' => 'x'])
            ->assertSessionHasErrors('admission_no');
        $this->assertSame(150000.0, $invoice->fresh()->balance);
    }

    public function test_the_invoice_page_shows_every_ledger_line(): void
    {
        $ade = $this->student('Ade');
        $this->issue([$this->tuition]);
        $invoice = $this->invoiceFor($ade, $this->tuition);
        $this->post("/school/fees/invoices/{$invoice->id}/payments", ['amount' => 50000, 'method' => 'ussd', 'entry_date' => now()->toDateString()]);

        $this->get("/school/fees/invoices/{$invoice->id}", ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonCount(2, 'props.entries')
            ->assertJsonPath('props.entries.1.type', 'payment')
            ->assertJsonPath('props.entries.1.method', 'ussd')
            ->assertJsonPath('props.invoice.balance', 100000)
            ->assertJsonPath('props.invoice.net_paid', 50000)
            ->assertJsonPath('props.can.collect', true);
    }
}
