<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\InvoiceInstalment;
use App\Models\PaymentPlan;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\Feature\Security\SecurityTestCase;

/** Paying fees in dated parts: plans, splitting invoices, payments filling the parts, and who is behind */
class InstalmentTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private Term $term;
    private FeeStructure $tuition;
    private Student $child;
    private User $parentUser;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 10:00:00');

        $this->school = $this->makeSchool('Parts School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);

        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $this->term = $year->terms()->first();
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 2']);
        $cat = FeeCategory::create(['school_id' => $this->school->id, 'name' => 'Tuition', 'type' => 'tuition']);
        $this->tuition = FeeStructure::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'fee_category_id' => $cat->id, 'academic_year' => '2026-2027', 'amount' => 150000, 'frequency' => 'one_time']);

        $this->parentUser = $this->makeUser($this->school, 'parent');
        $guardian = Guardian::create(['school_id' => $this->school->id, 'user_id' => $this->parentUser->id, 'name' => 'Mrs Obi', 'relation' => 'mother', 'phone' => '08030000000']);
        $this->child = Student::create([
            'school_id' => $this->school->id, 'class_id' => $class->id, 'guardian_id' => $guardian->id, 'first_name' => 'Ada', 'last_name' => 'Obi',
            'admission_no' => 'A1', 'gender' => 'female', 'status' => 'active',
        ]);
        Student::create([
            'school_id' => $this->school->id, 'class_id' => $class->id, 'first_name' => 'Bayo', 'last_name' => 'Ade',
            'admission_no' => 'A2', 'gender' => 'male', 'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function twoParts(string $first = '2026-10-15', string $second = '2026-11-15'): array
    {
        return [['label' => 'At resumption', 'percent' => 60, 'due_date' => $first], ['label' => 'By mid-term', 'percent' => 40, 'due_date' => $second]];
    }

    private function adaInvoice(): Invoice
    {
        return Invoice::where('student_id', $this->child->id)->sole();
    }

    private function pay(Invoice $invoice, float $amount): void
    {
        $this->post("/school/fees/invoices/{$invoice->id}/payments", ['amount' => $amount, 'method' => 'cash', 'entry_date' => '2026-10-20'])->assertSessionHasNoErrors();
    }

    public function test_a_plan_must_add_up_to_100_percent_and_have_at_least_two_parts(): void
    {
        $this->post('/school/fees/payment-plans', ['name' => 'Bad', 'parts' => [['label' => 'A', 'percent' => 60], ['label' => 'B', 'percent' => 30]]])
            ->assertSessionHasErrors('parts');
        $this->post('/school/fees/payment-plans', ['name' => 'One', 'parts' => [['label' => 'All', 'percent' => 100]]])->assertSessionHasErrors('parts');
        $this->assertSame(0, PaymentPlan::count());

        $this->post('/school/fees/payment-plans', ['name' => '60/40', 'parts' => [['label' => 'At resumption', 'percent' => 60], ['label' => '', 'percent' => 40]]])
            ->assertSessionHasNoErrors();
        $plan = PaymentPlan::sole();
        $this->assertSame([['label' => 'At resumption', 'percent' => 60], ['label' => 'Part 2', 'percent' => 40]], $plan->parts);

        // The principal sees plans but cannot change them
        $this->actingAs($this->makeUser($this->school, 'principal'));
        $this->get('/school/fees/payment-plans')->assertOk();
        $this->post('/school/fees/payment-plans', ['name' => 'x', 'parts' => []])->assertForbidden();
    }

    public function test_invoices_made_with_a_plan_get_dated_parts_and_payments_fill_them_in_order(): void
    {
        $this->post('/school/fees/payment-plans', ['name' => '60/40', 'parts' => [['label' => 'At resumption', 'percent' => 60], ['label' => 'By mid-term', 'percent' => 40]]]);
        $plan = PaymentPlan::sole();

        $this->post('/school/fees/invoices', [
            'fee_structure_ids' => [$this->tuition->id], 'term_id' => $this->term->id, 'payment_plan_id' => $plan->id, 'parts' => $this->twoParts(),
        ])->assertSessionHasNoErrors()->assertSessionHas('success', '2 invoices created for 2026/2027 · First Term. 2 set to be paid in parts.');

        $invoice = $this->adaInvoice();
        $this->assertSame($plan->id, $invoice->payment_plan_id);
        $this->assertSame('2026-11-15', $invoice->due_date->toDateString());     // due in full on the last part's date
        $this->assertSame([90000.0, 60000.0], $invoice->instalments->pluck('amount')->all());

        // The first part was due on 15 October; on the 20th the whole of it is overdue
        $this->get('/school/fees/invoices?status=behind')->assertInertia(fn ($p) => $p
            ->has('invoices.data', 2)->where('stats.overdue', 180000)->where('stats.behind_count', 2));

        $this->pay($invoice, 50000);
        $parts = $invoice->instalments()->get();
        $this->assertSame([50000.0, 0.0], $parts->pluck('paid')->all());
        $this->assertSame(['overdue', 'upcoming'], $parts->map->state()->all());

        // Paying the rest of part 1 (and a little of part 2) takes Ada off the "behind" list
        $this->pay($invoice, 45000);
        $this->assertSame([90000.0, 5000.0], $invoice->instalments()->pluck('paid')->all());
        $this->get('/school/fees/invoices?status=behind')->assertInertia(fn ($p) => $p
            ->has('invoices.data', 1)->where('invoices.data.0.student.name', 'Bayo Ade')->where('stats.overdue', 90000));

        // A fine changes every part by its share; reversing a payment takes it back out of the parts
        $this->post("/school/fees/invoices/{$invoice->id}/fines", ['amount' => 10000, 'note' => 'Late'])->assertSessionHasNoErrors();
        $this->assertSame([96000.0, 64000.0], $invoice->instalments()->pluck('amount')->all());
        $this->assertSame([95000.0, 0.0], $invoice->instalments()->pluck('paid')->all());
    }

    public function test_parts_can_be_put_on_invoices_already_made_and_kept_or_replaced(): void
    {
        $this->post('/school/fees/invoices', ['fee_structure_ids' => [$this->tuition->id], 'term_id' => $this->term->id])->assertSessionHasNoErrors();
        $bayo = Invoice::where('student_id', '!=', $this->child->id)->sole();
        $this->pay($bayo, 150000);                                    // paid invoices are left alone

        // One family agreed three parts
        $this->post("/school/fees/invoices/{$this->adaInvoice()->id}/plan", ['parts' => [
            ['label' => 'Now', 'percent' => 33.33, 'due_date' => '2026-10-25'],
            ['label' => 'Later', 'percent' => 33.33, 'due_date' => '2026-11-25'],
            ['label' => 'Last', 'percent' => 33.34, 'due_date' => '2026-12-05'],
        ]])->assertSessionHasNoErrors();
        $amounts = $this->adaInvoice()->instalments->pluck('amount');
        $this->assertSame([49995.0, 49995.0, 50010.0], $amounts->all());
        $this->assertEqualsWithDelta(150000, $amounts->sum(), 0.001);

        $this->post('/school/fees/invoices/plans', ['fee_structure_ids' => [$this->tuition->id], 'term_id' => $this->term->id, 'parts' => $this->twoParts()])
            ->assertSessionHas('success', 'Nothing changed: no open invoices for those fees and term without parts already.');
        $this->assertSame(3, $this->adaInvoice()->instalments()->count());
        $this->assertSame(0, $bayo->instalments()->count());

        $this->post('/school/fees/invoices/plans', ['fee_structure_ids' => [$this->tuition->id], 'term_id' => $this->term->id, 'parts' => $this->twoParts(), 'replace' => true])
            ->assertSessionHas('success', '1 invoice set to be paid in parts.');
        $this->assertSame(2, $this->adaInvoice()->instalments()->count());

        // Dates out of order are refused; removing the parts goes back to one payment
        $this->post("/school/fees/invoices/{$this->adaInvoice()->id}/plan", ['parts' => $this->twoParts('2026-12-01', '2026-11-01')])
            ->assertSessionHasErrors('parts.1.due_date');
        $this->delete("/school/fees/invoices/{$this->adaInvoice()->id}/plan")->assertSessionHasNoErrors();
        $this->assertSame(0, InvoiceInstalment::where('invoice_id', $this->adaInvoice()->id)->count());
        $this->assertNull($this->adaInvoice()->payment_plan_id);
    }

    public function test_parents_see_the_parts_and_staff_without_billing_rights_cannot_change_them(): void
    {
        $this->post('/school/fees/invoices', ['fee_structure_ids' => [$this->tuition->id], 'term_id' => $this->term->id, 'parts' => $this->twoParts()]);
        $invoice = $this->adaInvoice();
        $this->pay($invoice, 30000);

        $this->actingAs($this->parentUser);
        $this->get('/school/parent/fees')->assertInertia(fn ($p) => $p
            ->where('children.0.payments.0.instalments.due_now', 60000)
            ->where('children.0.payments.0.instalments.overdue', 60000)
            ->where('children.0.payments.0.instalments.next.label', 'At resumption')
            ->where('children.0.payments.0.instalments.parts.1.state', 'upcoming'));

        $this->actingAs($this->makeUser($this->school, 'principal'));
        $this->post("/school/fees/invoices/{$invoice->id}/plan", ['parts' => $this->twoParts()])->assertForbidden();
        $this->delete("/school/fees/invoices/{$invoice->id}/plan")->assertForbidden();
    }

    public function test_one_school_cannot_use_another_schools_plan_or_split_its_invoices(): void
    {
        $other = $this->makeSchool('Other School');
        $theirPlan = PaymentPlan::withoutGlobalScopes()->create(['school_id' => $other->id, 'name' => 'Theirs', 'parts' => [['label' => 'A', 'percent' => 50], ['label' => 'B', 'percent' => 50]]]);
        $this->post('/school/fees/invoices', ['fee_structure_ids' => [$this->tuition->id], 'term_id' => $this->term->id]);

        $this->post("/school/fees/invoices/{$this->adaInvoice()->id}/plan", ['payment_plan_id' => $theirPlan->id, 'parts' => $this->twoParts()])
            ->assertSessionHasErrors('payment_plan_id');

        $adaInvoice = $this->adaInvoice()->id;
        $this->actingAs($this->makeUser($other, 'school-admin'));
        $this->post("/school/fees/invoices/{$adaInvoice}/plan", ['parts' => $this->twoParts()])->assertNotFound();
        $this->put("/school/fees/payment-plans/{$theirPlan->id}", ['name' => 'Mine now', 'parts' => [['label' => 'A', 'percent' => 50], ['label' => 'B', 'percent' => 50]]])
            ->assertSessionHasNoErrors();
    }
}
