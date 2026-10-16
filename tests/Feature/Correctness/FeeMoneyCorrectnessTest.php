<?php

namespace Tests\Feature\Correctness;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Scholarship;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use Carbon\Carbon;
use Tests\Feature\Security\SecurityTestCase;

/**
 * Right-answer checks for money. Every expected figure below was worked out by hand
 * (with a calculator, in naira and kobo), NOT by copying the app's own formula.
 */
class FeeMoneyCorrectnessTest extends SecurityTestCase
{
    private $school;
    private $admin;
    private SchoolClass $class;
    private Term $term;
    private FeeCategory $tuitionCat;
    private FeeCategory $busCat;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 10:00:00');
        $this->school = $this->makeSchool('Money Right School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $this->term = $year->terms()->first();
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1']);
        $this->tuitionCat = FeeCategory::create(['school_id' => $this->school->id, 'name' => 'Tuition', 'type' => 'tuition']);
        $this->busCat = FeeCategory::create(['school_id' => $this->school->id, 'name' => 'Bus', 'type' => 'transport']);
    }

    private function fee(float $amount, ?FeeCategory $cat = null): FeeStructure
    {
        return FeeStructure::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'fee_category_id' => ($cat ?? $this->tuitionCat)->id, 'academic_year' => '2026-2027', 'amount' => $amount, 'frequency' => 'per_term']);
    }

    private function pupil(string $first, string $status = 'active'): Student
    {
        return Student::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'first_name' => $first, 'last_name' => 'Pupil', 'gender' => 'female', 'status' => $status]);
    }

    private function bill(FeeStructure ...$fees): void
    {
        $this->post('/school/fees/invoices', ['fee_structure_ids' => array_map(fn ($f) => $f->id, $fees), 'term_id' => $this->term->id])->assertSessionHasNoErrors();
    }

    private function pay(Invoice $i, $amount, string $method = 'cash', ?string $date = null)
    {
        return $this->post("/school/fees/invoices/{$i->id}/payments", ['amount' => $amount, 'method' => $method, 'entry_date' => $date ?? '2026-10-20']);
    }

    private function inv(Student $s): Invoice
    {
        return Invoice::where('student_id', $s->id)->firstOrFail()->fresh();
    }

    public function test_billing_a_class_charges_only_active_pupils_the_exact_fee_once(): void
    {
        $a = $this->pupil('Ada');
        $b = $this->pupil('Bola');
        $c = $this->pupil('Chidi');
        $gone = $this->pupil('Dayo', 'alumni');
        $left = $this->pupil('Efe', 'transferred');
        $fee = $this->fee(125750.75);

        $this->bill($fee);
        // 3 active pupils, each ₦125,750.75; alumni and transferred are not billed
        $this->assertSame(3, Invoice::count());
        foreach ([$a, $b, $c] as $s) {
            $i = $this->inv($s);
            $this->assertEquals(125750.75, (float) $i->amount);
            $this->assertEquals(125750.75, (float) $i->balance);
            $this->assertSame('unpaid', $i->status);
        }
        $this->assertSame(0, Invoice::where('student_id', $gone->id)->count());
        $this->assertSame(0, Invoice::where('student_id', $left->id)->count());

        // Billing the same fee and term again changes nothing (no double charging)
        $this->bill($fee);
        $this->assertSame(3, Invoice::count());
        $this->assertSame(3, LedgerEntry::where('type', 'charge')->count());
        $this->assertEquals(3 * 125750.75, (float) Invoice::sum('amount'));
    }

    public function test_a_new_pupil_added_later_gets_only_their_own_new_invoice(): void
    {
        $fee = $this->fee(80000);
        $this->pupil('First');
        $this->bill($fee);
        $this->pupil('Late');
        $this->bill($fee);
        $this->assertSame(2, Invoice::count());
    }

    public function test_part_payments_reduce_the_balance_exactly_and_settle_it(): void
    {
        $s = $this->pupil('Ada');
        $this->bill($this->fee(150000));
        $i = $this->inv($s);

        $this->pay($i, 50000.50)->assertSessionHasNoErrors();
        $i = $i->fresh();
        $this->assertEquals(99999.50, (float) $i->balance);
        $this->assertSame('partial', $i->status);

        $this->pay($i, 99999.50)->assertSessionHasNoErrors();
        $i = $i->fresh();
        $this->assertEquals(0.0, (float) $i->balance);
        $this->assertSame('paid', $i->status);

        // A settled invoice takes no more money, however small
        $this->pay($i, 0.01)->assertSessionHasErrors('amount');
        $this->assertSame(2, LedgerEntry::where('type', 'payment')->count());
    }

    public function test_overpaying_by_one_kobo_is_refused_but_paying_exactly_is_allowed(): void
    {
        $s = $this->pupil('Ada');
        $this->bill($this->fee(1000));
        $i = $this->inv($s);

        $this->pay($i, 1000.01)->assertSessionHasErrors('amount');
        $this->assertEquals(1000.0, (float) $i->fresh()->balance);
        $this->pay($i, 1000)->assertSessionHasNoErrors();
        $this->assertSame('paid', $i->fresh()->status);
    }

    public function test_three_equal_thirds_leave_exactly_one_kobo_which_can_then_be_paid(): void
    {
        $s = $this->pupil('Ada');
        $this->bill($this->fee(100000));
        $i = $this->inv($s);

        // 3 x 33,333.33 = 99,999.99, so ₦0.01 must remain
        foreach (range(1, 3) as $n) {
            $this->pay($i, 33333.33)->assertSessionHasNoErrors();
        }
        $i = $i->fresh();
        $this->assertEquals(0.01, (float) $i->balance);
        $this->assertSame('partial', $i->status);
        $this->pay($i, 0.01)->assertSessionHasNoErrors();
        $this->assertSame('paid', $i->fresh()->status);
        $this->assertEquals(0.0, (float) $i->fresh()->balance);
    }

    public function test_bad_payment_inputs_are_rejected_and_write_nothing(): void
    {
        $s = $this->pupil('Ada');
        $this->bill($this->fee(5000));
        $i = $this->inv($s);

        $this->pay($i, 0)->assertSessionHasErrors('amount');
        $this->pay($i, -500)->assertSessionHasErrors('amount');
        $this->pay($i, 'abc')->assertSessionHasErrors('amount');
        $this->pay($i, '')->assertSessionHasErrors('amount');
        $this->pay($i, 100, 'bitcoin')->assertSessionHasErrors('method');
        $this->pay($i, 100, 'cash', '2026-10-21')->assertSessionHasErrors('entry_date'); // tomorrow
        $this->pay($i, 100, 'cash', 'not-a-date')->assertSessionHasErrors('entry_date');
        $this->assertSame(0, LedgerEntry::where('type', 'payment')->count());
        $this->assertEquals(5000.0, (float) $i->fresh()->balance);
    }

    public function test_percent_scholarship_is_rounded_to_the_kobo(): void
    {
        $s = $this->pupil('Ada');
        $sch = Scholarship::create(['school_id' => $this->school->id, 'name' => 'Staff child 15%', 'type' => 'percent', 'value' => 15, 'is_active' => true]);
        $this->post('/school/fees/scholarships/awards', ['scholarship_id' => $sch->id, 'admission_no' => $s->admission_no, 'note' => 'Teacher\'s child'])->assertSessionHasNoErrors();
        $this->bill($this->fee(45555.55));

        // 15% of 45,555.55 = 6,833.3325 -> ₦6,833.33 ; 45,555.55 - 6,833.33 = ₦38,722.22
        $i = $this->inv($s);
        $this->assertEquals(38722.22, (float) $i->balance);
        $disc = LedgerEntry::where('invoice_id', $i->id)->where('type', 'discount')->first();
        $this->assertEquals(-6833.33, (float) $disc->amount);
    }

    public function test_fixed_scholarship_bigger_than_the_fee_never_goes_below_zero(): void
    {
        $s = $this->pupil('Ada');
        $sch = Scholarship::create(['school_id' => $this->school->id, 'name' => 'Big grant', 'type' => 'fixed', 'value' => 999999, 'is_active' => true]);
        $this->post('/school/fees/scholarships/awards', ['scholarship_id' => $sch->id, 'admission_no' => $s->admission_no, 'note' => 'x'])->assertSessionHasNoErrors();
        $this->bill($this->fee(30000));

        $i = $this->inv($s);
        $this->assertEquals(0.0, (float) $i->balance);
        $this->assertSame('paid', $i->status);
        $this->assertEquals(-30000.0, (float) LedgerEntry::where('invoice_id', $i->id)->where('type', 'discount')->value('amount'));
    }

    public function test_scholarship_for_one_fee_type_leaves_other_fees_untouched(): void
    {
        $s = $this->pupil('Ada');
        $sch = Scholarship::create(['school_id' => $this->school->id, 'name' => 'Half tuition', 'type' => 'percent', 'value' => 50, 'fee_category_id' => $this->tuitionCat->id, 'is_active' => true]);
        $this->post('/school/fees/scholarships/awards', ['scholarship_id' => $sch->id, 'admission_no' => $s->admission_no, 'note' => 'x'])->assertSessionHasNoErrors();
        $this->bill($this->fee(100000), $this->fee(20000, $this->busCat));

        $byFee = Invoice::where('student_id', $s->id)->with('feeStructure')->get()->keyBy(fn ($i) => $i->feeStructure->fee_category_id);
        $this->assertEquals(50000.0, (float) $byFee[$this->tuitionCat->id]->balance); // 100,000 - 50%
        $this->assertEquals(20000.0, (float) $byFee[$this->busCat->id]->balance);     // bus unchanged
    }

    public function test_a_fine_raises_the_balance_and_must_be_paid_too(): void
    {
        $s = $this->pupil('Ada');
        $this->bill($this->fee(10000));
        $i = $this->inv($s);

        $this->post("/school/fees/invoices/{$i->id}/fines", ['amount' => 2500.25, 'note' => 'Late'])->assertSessionHasNoErrors();
        $this->assertEquals(12500.25, (float) $i->fresh()->balance);
        $this->pay($i, 10000)->assertSessionHasNoErrors();
        $this->assertEquals(2500.25, (float) $i->fresh()->balance);
        $this->assertSame('partial', $i->fresh()->status);

        // A fine needs a reason and a positive amount
        $this->post("/school/fees/invoices/{$i->id}/fines", ['amount' => 100, 'note' => ''])->assertSessionHasErrors('note');
        $this->post("/school/fees/invoices/{$i->id}/fines", ['amount' => -100, 'note' => 'x'])->assertSessionHasErrors('amount');
    }

    public function test_reversing_a_payment_puts_the_money_back_owed_and_only_once(): void
    {
        $s = $this->pupil('Ada');
        $this->bill($this->fee(20000));
        $i = $this->inv($s);
        $this->pay($i, 20000);
        $this->assertSame('paid', $i->fresh()->status);

        $entry = LedgerEntry::where('invoice_id', $i->id)->where('type', 'payment')->first();
        $this->post("/school/fees/invoices/{$i->id}/entries/{$entry->id}/reverse", ['reason' => 'Bounced cheque'])->assertSessionHasNoErrors();
        $i = $i->fresh();
        $this->assertEquals(20000.0, (float) $i->balance);
        $this->assertSame('unpaid', $i->status);

        // second reversal of the same line, and reversing the reversal, are both refused
        $this->post("/school/fees/invoices/{$i->id}/entries/{$entry->id}/reverse", ['reason' => 'again'])->assertSessionHasErrors('entry');
        $rev = LedgerEntry::where('invoice_id', $i->id)->where('type', 'reversal')->first();
        $this->post("/school/fees/invoices/{$i->id}/entries/{$rev->id}/reverse", ['reason' => 'undo'])->assertSessionHasErrors('entry');
        $this->assertEquals(20000.0, (float) $i->fresh()->balance);

        // and the pupil can pay again afterwards
        $this->pay($i, 20000)->assertSessionHasNoErrors();
        $this->assertSame('paid', $i->fresh()->status);
    }

    public function test_reversing_the_charge_does_not_mark_an_unpaid_invoice_as_paid(): void
    {
        $s = $this->pupil('Ada');
        $this->bill($this->fee(20000));
        $i = $this->inv($s);
        $charge = LedgerEntry::where('invoice_id', $i->id)->where('type', 'charge')->first();

        $this->post("/school/fees/invoices/{$i->id}/entries/{$charge->id}/reverse", ['reason' => 'Billed by mistake'])->assertSessionHasErrors('entry');
        // Nothing was ever paid, so the invoice must never read "paid"
        $this->assertNotSame('paid', $i->fresh()->status);
        $this->assertEquals(0.0, $this->moneyCollected());
    }

    public function test_cancelling_an_invoice_is_refused_after_money_is_paid(): void
    {
        $s = $this->pupil('Ada');
        $this->bill($this->fee(20000));
        $i = $this->inv($s);
        $this->pay($i, 1000);

        $this->post("/school/fees/invoices/{$i->id}/void", ['reason' => 'oops'])->assertSessionHasErrors('invoice');
        $this->assertNotSame('void', $i->fresh()->status);

        // after reversing the payment, cancelling works and the balance is zero and nothing more can be paid
        $entry = LedgerEntry::where('invoice_id', $i->id)->where('type', 'payment')->first();
        $this->post("/school/fees/invoices/{$i->id}/entries/{$entry->id}/reverse", ['reason' => 'refund']);
        $this->post("/school/fees/invoices/{$i->id}/void", ['reason' => 'oops'])->assertSessionHasNoErrors();
        $i = $i->fresh();
        $this->assertSame('void', $i->status);
        $this->assertEquals(0.0, (float) $i->balance);
        $this->pay($i, 10)->assertSessionHasErrors('amount');
    }

    public function test_balance_always_equals_the_sum_of_the_ledger_lines(): void
    {
        $s = $this->pupil('Ada');
        $this->bill($this->fee(87654.32));
        $i = $this->inv($s);
        $this->post("/school/fees/invoices/{$i->id}/fines", ['amount' => 1234.56, 'note' => 'Late']);
        $this->pay($i, 10000.10);
        $this->pay($i, 777.77);

        // 87,654.32 + 1,234.56 - 10,000.10 - 777.77 = 78,111.01
        $this->assertEquals(78111.01, (float) $i->fresh()->balance);
        $this->assertEquals(78111.01, round((float) LedgerEntry::where('invoice_id', $i->id)->sum('amount'), 2));
    }

    public function test_school_totals_add_up_across_pupils(): void
    {
        $a = $this->pupil('Ada');
        $b = $this->pupil('Bola');
        $c = $this->pupil('Chidi');
        $this->bill($this->fee(50000));
        $this->pay($this->inv($a), 50000);   // paid in full
        $this->pay($this->inv($b), 12000.5); // part
        // c pays nothing.

        // collected = 50,000 + 12,000.50 = 62,000.50 ; owed = 0 + 37,999.50 + 50,000 = 87,999.50
        $this->assertEquals(62000.50, $this->moneyCollected());
        $this->assertEquals(87999.50, \App\Services\FeeLedgerService::outstanding($this->school->id));
        $this->assertEquals(87999.50, (float) Invoice::whereIn('status', ['unpaid', 'partial'])->sum('balance'));
    }

    public function test_payment_in_dated_parts_fills_part_one_first(): void
    {
        $s = $this->pupil('Ada');
        $this->bill($this->fee(100000));
        $i = $this->inv($s);
        $this->post("/school/fees/invoices/{$i->id}/plan", ['parts' => [
            ['label' => 'First', 'percent' => 50, 'due_date' => '2026-09-30'],
            ['label' => 'Second', 'percent' => 30, 'due_date' => '2026-11-15'],
            ['label' => 'Third', 'percent' => 20, 'due_date' => '2026-12-15'],
        ]])->assertSessionHasNoErrors();
        $this->pay($i, 60000);

        // Parts are 50,000 / 30,000 / 20,000. 60,000 paid fills part 1 (50,000) and 10,000 of part 2.
        $parts = $i->fresh()->instalments()->orderBy('position')->get();
        $this->assertEquals([50000.0, 30000.0, 20000.0], $parts->pluck('amount')->map(fn ($v) => (float) $v)->all());
        $this->assertEquals([50000.0, 10000.0, 0.0], $parts->pluck('paid')->map(fn ($v) => (float) $v)->all());
    }

    public function test_instalment_shares_that_do_not_add_to_100_or_go_backwards_in_date_are_refused(): void
    {
        $s = $this->pupil('Ada');
        $this->bill($this->fee(100000));
        $i = $this->inv($s);
        $this->post("/school/fees/invoices/{$i->id}/plan", ['parts' => [
            ['label' => 'A', 'percent' => 60, 'due_date' => '2026-11-01'],
            ['label' => 'B', 'percent' => 30, 'due_date' => '2026-12-01'],
        ]])->assertSessionHasErrors('parts');
        $this->post("/school/fees/invoices/{$i->id}/plan", ['parts' => [
            ['label' => 'A', 'percent' => 50, 'due_date' => '2026-12-01'],
            ['label' => 'B', 'percent' => 50, 'due_date' => '2026-11-01'],
        ]])->assertSessionHasErrors();
        $this->assertSame(0, $i->fresh()->instalments()->count());
    }

    public function test_a_parts_split_that_does_not_divide_evenly_still_adds_up_to_the_invoice(): void
    {
        $s = $this->pupil('Ada');
        $this->bill($this->fee(1000.01));
        $i = $this->inv($s);
        $this->post("/school/fees/invoices/{$i->id}/plan", ['parts' => [
            ['label' => 'A', 'percent' => 33.33, 'due_date' => '2026-11-01'],
            ['label' => 'B', 'percent' => 33.33, 'due_date' => '2026-12-01'],
            ['label' => 'C', 'percent' => 33.34, 'due_date' => '2027-01-01'],
        ]]);
        // 33.33% of 1000.01 = 333.3033 -> 333.30 ; the last part takes what is left: 1000.01 - 666.60 = 333.41
        $parts = $i->fresh()->instalments()->orderBy('position')->get()->pluck('amount')->map(fn ($v) => (float) $v)->all();
        $this->assertEquals([333.30, 333.30, 333.41], $parts);
        $this->assertEquals(1000.01, round(array_sum($parts), 2));
    }

    public function test_who_may_touch_money(): void
    {
        $s = $this->pupil('Ada');
        $this->bill($this->fee(5000));
        $i = $this->inv($s);

        foreach (['teacher', 'parent', 'student', 'librarian', 'receptionist'] as $role) {
            $this->actingAs($this->makeUser($this->school, $role));
            $this->post("/school/fees/invoices/{$i->id}/payments", ['amount' => 100, 'method' => 'cash', 'entry_date' => '2026-10-20'])->assertForbidden();
        }
        $this->assertSame(0, LedgerEntry::where('type', 'payment')->count());

        // the accountant can collect but cannot reverse or cancel (that needs the waiver permission)
        $acct = $this->makeUser($this->school, 'accountant');
        $this->actingAs($acct);
        $this->post("/school/fees/invoices/{$i->id}/payments", ['amount' => 100, 'method' => 'cash', 'entry_date' => '2026-10-20'])->assertSessionHasNoErrors();
        $entry = LedgerEntry::where('type', 'payment')->first();
        $reverse = $this->post("/school/fees/invoices/{$i->id}/entries/{$entry->id}/reverse", ['reason' => 'x']);
        $this->assertTrue($acct->can('fees.waiver') || $reverse->status() === 403);
    }

    public function test_one_school_cannot_pay_or_see_another_schools_invoice(): void
    {
        $s = $this->pupil('Ada');
        $this->bill($this->fee(5000));
        $i = $this->inv($s);

        $other = $this->makeSchool('Other Money School');
        $this->actingAs($this->makeUser($other, 'school-admin'));
        $this->post("/school/fees/invoices/{$i->id}/payments", ['amount' => 100, 'method' => 'cash', 'entry_date' => '2026-10-20'])->assertNotFound();
        $this->get("/school/fees/invoices/{$i->id}")->assertNotFound();
        $this->assertSame(0, LedgerEntry::where('type', 'payment')->count());
    }

    public function test_a_fee_cannot_be_set_for_another_schools_class_or_fee_type(): void
    {
        $other = $this->makeSchool('Rival Fee School');
        $theirClass = SchoolClass::create(['school_id' => $other->id, 'name' => 'Rival Class']);
        $theirCat = FeeCategory::create(['school_id' => $other->id, 'name' => 'Rival Tuition', 'type' => 'tuition']);

        $this->post('/school/fees/structures', ['class_id' => $theirClass->id, 'fee_category_id' => $this->tuitionCat->id, 'academic_year' => '2026-2027', 'amount' => 1000, 'frequency' => 'per_term'])->assertSessionHasErrors('class_id');
        $this->post('/school/fees/structures', ['class_id' => $this->class->id, 'fee_category_id' => $theirCat->id, 'academic_year' => '2026-2027', 'amount' => 1000, 'frequency' => 'per_term'])->assertSessionHasErrors('fee_category_id');
        $this->assertSame(0, FeeStructure::withoutGlobalScopes()->count());
    }

    private function moneyCollected(): float
    {
        return \App\Services\FeeLedgerService::collected($this->school->id);
    }
}
