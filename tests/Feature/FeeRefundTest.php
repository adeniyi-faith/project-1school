<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\FeeLedgerService;
use Tests\Feature\Security\SecurityTestCase;

/** Giving money back: the ledger stays honest and the totals follow. */
class FeeRefundTest extends SecurityTestCase
{
    private School $school;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = $this->makeSchool('Refund School');
        $this->actingAs($this->makeUser($this->school, 'school-admin'));

        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 2']);
        $cat = FeeCategory::create(['school_id' => $this->school->id, 'name' => 'Tuition', 'type' => 'tuition']);
        $fee = FeeStructure::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'fee_category_id' => $cat->id, 'academic_year' => '2026-2027', 'amount' => 100000, 'frequency' => 'one_time']);
        Student::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'first_name' => 'Ade', 'last_name' => 'Test', 'admission_no' => 'ADE-1', 'gender' => 'male', 'status' => 'active']);

        $this->post('/school/fees/invoices', ['fee_structure_ids' => [$fee->id], 'term_id' => $year->terms()->first()->id]);
        $this->invoice = Invoice::sole();
    }

    private function pay(float $amount): void
    {
        $this->post("/school/fees/invoices/{$this->invoice->id}/payments", ['amount' => $amount, 'method' => 'bank_transfer', 'entry_date' => now()->toDateString()])->assertSessionHasNoErrors();
    }

    private function refund(array $over = []): \Illuminate\Testing\TestResponse
    {
        return $this->post("/school/fees/invoices/{$this->invoice->id}/refunds", $over + [
            'amount' => 30000, 'method' => 'bank_transfer', 'entry_date' => now()->toDateString(), 'reason' => 'Child withdrawn',
        ]);
    }

    public function test_a_refund_returns_money_and_lowers_the_fee_by_default(): void
    {
        $this->pay(100000);
        $this->refund()->assertSessionHasNoErrors();

        $invoice = $this->invoice->fresh();
        $this->assertSame(0.0, $invoice->balance);
        $this->assertSame('paid', $invoice->status);
        $this->assertSame(['charge', 'payment', 'credit', 'refund'], LedgerEntry::where('invoice_id', $invoice->id)->orderBy('id')->pluck('type')->all());
        $this->assertSame(70000.0, app(FeeLedgerService::class)->netPaid($invoice));
        // Money received goes down by what was handed back
        $this->assertSame(70000.0, FeeLedgerService::collected($this->school->id));
        $this->assertStringStartsWith('RFD-', LedgerEntry::where('type', 'refund')->sole()->reference);
    }

    public function test_a_refund_without_lowering_the_fee_makes_the_family_owe_it_again(): void
    {
        $this->pay(100000);
        $this->refund(['lower_fee' => false])->assertSessionHasNoErrors();

        $this->assertSame(30000.0, $this->invoice->fresh()->balance);
        $this->assertSame('partial', $this->invoice->fresh()->status);
        $this->assertSame(0, LedgerEntry::where('type', 'credit')->count());
    }

    public function test_more_than_was_paid_is_refused_and_nothing_is_written(): void
    {
        $this->pay(20000);
        $before = LedgerEntry::count();

        $this->refund(['amount' => 25000])->assertSessionHasErrors('amount');
        $this->assertSame($before, LedgerEntry::count());

        // Earlier refunds count against the limit
        $this->refund(['amount' => 15000])->assertSessionHasNoErrors();
        $this->refund(['amount' => 10000])->assertSessionHasErrors('amount');
    }

    public function test_a_refund_cannot_be_reversed(): void
    {
        $this->pay(50000);
        $this->refund(['amount' => 10000]);
        $refund = LedgerEntry::where('type', 'refund')->sole();

        $this->post("/school/fees/invoices/{$this->invoice->id}/entries/{$refund->id}/reverse", ['reason' => 'oops'])->assertSessionHasErrors('entry');
    }

    public function test_only_people_who_may_waive_fees_can_refund(): void
    {
        $this->pay(50000);
        $this->actingAs($this->makeUser($this->school, 'teacher'));

        $this->refund()->assertForbidden();
    }
}
