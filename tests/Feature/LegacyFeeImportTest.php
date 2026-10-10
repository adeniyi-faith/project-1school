<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Services\LegacyFeeImporter;
use Tests\Feature\Security\SecurityTestCase;

/** Copying the old fee_payments rows into invoices, undoing that, and what reads the new data. */
class LegacyFeeImportTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private SchoolClass $class;
    private Term $term;
    private FeeStructure $tuition;
    private Student $ade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = $this->makeSchool('Old Fees School');
        $this->admin = $this->makeUser($this->school, 'school-admin');

        $year = AcademicYear::withoutGlobalScopes()->create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $this->term = $year->terms()->withoutGlobalScopes()->orderBy('sequence')->first();
        $this->class = SchoolClass::withoutGlobalScopes()->create(['school_id' => $this->school->id, 'name' => 'SS 1']);
        $cat = FeeCategory::withoutGlobalScopes()->create(['school_id' => $this->school->id, 'name' => 'Tuition', 'type' => 'tuition']);
        $this->tuition = FeeStructure::withoutGlobalScopes()->create([
            'school_id' => $this->school->id, 'class_id' => $this->class->id, 'fee_category_id' => $cat->id,
            'academic_year' => '2026/2027', 'amount' => 200000, 'frequency' => 'quarterly', 'due_date' => '2026-09-21',
        ]);
        $this->ade = Student::withoutGlobalScopes()->create([
            'school_id' => $this->school->id, 'class_id' => $this->class->id, 'first_name' => 'Ade', 'last_name' => 'Old',
            'admission_no' => 'OLD-1', 'gender' => 'male', 'status' => 'active',
        ]);
    }

    private function oldRow(array $values): FeePayment
    {
        return FeePayment::withoutGlobalScopes()->create($values + [
            'school_id' => $this->school->id, 'student_id' => $this->ade->id, 'fee_structure_id' => $this->tuition->id,
            'amount_due' => 200000, 'amount_paid' => 0, 'discount' => 0, 'fine' => 0,
            'month_year' => '2026-T1', 'method' => 'cash', 'status' => 'pending',
        ]);
    }

    private function importer(): LegacyFeeImporter
    {
        return app(LegacyFeeImporter::class);
    }

    public function test_instalments_become_one_invoice_with_the_same_balance_as_before(): void
    {
        $this->oldRow(['receipt_no' => 'RCP-A', 'amount_paid' => 80000, 'discount' => 10000, 'payment_date' => '2026-09-10', 'method' => 'bank_transfer']);
        $this->oldRow(['receipt_no' => 'RCP-B', 'amount_paid' => 50000, 'discount' => 10000, 'fine' => 5000, 'payment_date' => '2026-10-02', 'method' => 'pos']);

        $report = $this->importer()->copy();

        $this->assertSame(2, $report['rows']);
        $this->assertSame(1, $report['invoices_made']);
        $invoice = Invoice::withoutGlobalScopes()->sole();
        $this->assertSame('2026/2027 · First Term', $invoice->period, 'old "2026-T1" lands on the same term label the Invoices screen uses');
        $this->assertSame($this->term->id, $invoice->term_id);
        $this->assertSame(200000.0, $invoice->amount);
        // 200,000 fee − 10,000 discount (typed on both rows, counted once) + 5,000 fine − 130,000 paid
        $this->assertSame(65000.0, $invoice->balance);
        $this->assertSame('partial', $invoice->status);

        $payments = LedgerEntry::withoutGlobalScopes()->where('type', 'payment')->orderBy('id')->get();
        $this->assertSame(['RCP-A', 'RCP-B'], $payments->pluck('reference')->all(), 'old receipt numbers are kept');
        $this->assertSame(['bank_transfer', 'pos'], $payments->pluck('method')->all());
        $this->assertSame('2026-10-02', $payments[1]->entry_date->toDateString());

        // Billing First Term on the new screen afterwards does not bill Ade twice
        $this->actingAs($this->admin)->post('/school/fees/invoices', ['fee_structure_ids' => [$this->tuition->id], 'term_id' => $this->term->id]);
        $this->assertSame(1, Invoice::withoutGlobalScopes()->count());
    }

    public function test_running_the_copy_again_copies_nothing_twice_and_old_rows_are_untouched(): void
    {
        $old = $this->oldRow(['receipt_no' => 'RCP-A', 'amount_paid' => 200000, 'status' => 'paid', 'payment_date' => '2026-09-10']);

        $this->importer()->copy();
        $again = $this->importer()->copy();

        $this->assertSame(0, $again['rows']);
        $this->assertSame(1, Invoice::withoutGlobalScopes()->count());
        $this->assertSame(2, LedgerEntry::withoutGlobalScopes()->count());
        $this->assertSame('paid', Invoice::withoutGlobalScopes()->sole()->status);
        $this->assertSame('200000.00', $old->fresh()->amount_paid, 'the old row itself is never changed');
    }

    public function test_a_dry_run_changes_nothing(): void
    {
        $this->oldRow(['receipt_no' => 'RCP-A', 'amount_paid' => 1000, 'payment_date' => '2026-09-10']);

        $this->artisan('fees:copy-old-payments', ['--dry-run' => true])
            ->expectsOutputToContain('Would copy 1 old payment rows')
            ->assertSuccessful();

        $this->assertSame(0, Invoice::withoutGlobalScopes()->count());
    }

    public function test_old_payments_are_added_to_an_invoice_already_made_on_the_new_screen(): void
    {
        $this->actingAs($this->admin)->post('/school/fees/invoices', ['fee_structure_ids' => [$this->tuition->id], 'term_id' => $this->term->id]);
        $this->oldRow(['receipt_no' => 'RCP-A', 'amount_paid' => 120000, 'payment_date' => '2026-09-10']);

        $report = $this->importer()->copy();

        $this->assertSame(1, $report['added_to_existing']);
        $invoice = Invoice::withoutGlobalScopes()->sole();
        $this->assertSame(1, LedgerEntry::withoutGlobalScopes()->where('type', 'charge')->count(), 'no second charge');
        $this->assertSame(80000.0, $invoice->balance);

        // Undo takes just the copied payment back out
        $this->importer()->undo();
        $this->assertSame(200000.0, $invoice->fresh()->balance);
        $this->assertSame(['charge'], LedgerEntry::withoutGlobalScopes()->pluck('type')->all());
    }

    public function test_undo_removes_copies_but_keeps_an_invoice_staff_have_since_added_to(): void
    {
        $this->oldRow(['receipt_no' => 'RCP-A', 'amount_paid' => 50000, 'payment_date' => '2026-09-10']);
        $bus = FeeStructure::withoutGlobalScopes()->create([
            'school_id' => $this->school->id, 'class_id' => $this->class->id, 'fee_category_id' => $this->tuition->fee_category_id,
            'academic_year' => '2026/2027', 'amount' => 40000, 'frequency' => 'quarterly',
        ]);
        $this->oldRow(['receipt_no' => 'RCP-B', 'fee_structure_id' => $bus->id, 'amount_due' => 40000, 'amount_paid' => 10000, 'payment_date' => '2026-09-10']);
        $this->importer()->copy();

        // A payment taken on the new screen after the copy
        $tuitionInvoice = Invoice::withoutGlobalScopes()->where('fee_structure_id', $this->tuition->id)->sole();
        $this->actingAs($this->admin)->post("/school/fees/invoices/{$tuitionInvoice->id}/payments", ['amount' => 30000, 'method' => 'cash', 'entry_date' => now()->toDateString()])
            ->assertSessionHasNoErrors();

        $this->artisan('fees:copy-old-payments', ['--undo' => true])
            ->expectsOutputToContain('Removed 1 copied invoices')
            ->expectsOutputToContain($tuitionInvoice->invoice_no)
            ->assertSuccessful();

        $this->assertSame([$tuitionInvoice->id], Invoice::withoutGlobalScopes()->pluck('id')->all(), 'the invoice with new money on it stays');
        $this->assertSame(120000.0, $tuitionInvoice->fresh()->balance);
    }

    public function test_rows_whose_fee_or_student_belongs_to_another_school_are_not_copied(): void
    {
        $other = $this->makeSchool('Other School');
        FeePayment::withoutGlobalScopes()->create([
            'school_id' => $other->id, 'student_id' => $this->ade->id, 'fee_structure_id' => $this->tuition->id, 'receipt_no' => 'RCP-X',
            'amount_due' => 100, 'amount_paid' => 100, 'discount' => 0, 'fine' => 0, 'method' => 'cash', 'status' => 'paid',
        ]);

        $report = $this->importer()->copy();

        $this->assertSame(0, Invoice::withoutGlobalScopes()->count());
        $this->assertCount(1, $report['skipped']);
    }

    public function test_the_old_collect_screen_now_sends_staff_to_invoices(): void
    {
        $this->actingAs($this->admin);

        $this->get('/school/fees/payments/collect?student_id=OLD-1')->assertRedirect('/school/fees/invoices?search=OLD-1');
        $this->get('/school/fees/outstanding')->assertRedirect('/school/fees/invoices?status=open');
        $this->post('/school/fees/payments', [
            'student_id' => $this->ade->id, 'fee_structure_id' => $this->tuition->id, 'amount_due' => 1, 'amount_paid' => 1,
            'payment_date' => '2026-09-10', 'method' => 'cash',
        ])->assertRedirect('/school/fees/invoices');

        $this->assertSame(0, FeePayment::withoutGlobalScopes()->count());
        // Old records can still be looked at
        $this->get('/school/fees/payments', ['X-Inertia' => 'true'])->assertOk();
    }

    public function test_families_see_their_invoices_with_discounts_and_fines_counted(): void
    {
        $this->oldRow(['receipt_no' => 'RCP-A', 'amount_paid' => 100000, 'discount' => 20000, 'fine' => 5000, 'payment_date' => '2026-09-10']);
        $this->importer()->copy();

        $studentUser = $this->makeUser($this->school, 'student');
        $parentUser = $this->makeUser($this->school, 'parent');
        $guardian = Guardian::withoutGlobalScopes()->create(['school_id' => $this->school->id, 'user_id' => $parentUser->id, 'name' => 'Mr Old', 'phone' => '08030000001']);
        $this->ade->update(['user_id' => $studentUser->id, 'guardian_id' => $guardian->id]);

        // Before this change the portals showed 200,000 − 100,000 = 100,000, ignoring the discount and fine
        $this->actingAs($studentUser)->get('/school/student/fees', ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.summary.balance', 85000)
            ->assertJsonPath('props.summary.total_due', 185000)
            ->assertJsonPath('props.payments.0.month', 'Tuition · 2026/2027 · First Term')
            ->assertJsonPath('props.payments.0.status', 'partial');
        $this->actingAs($parentUser)->get('/school/parent/fees', ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.children.0.balance', 85000)
            ->assertJsonPath('props.children.0.total_paid', 100000);
        $this->actingAs($parentUser)->get('/school/parent/dashboard', ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.children.0.fees.balance', 85000);
    }

    public function test_finance_report_counts_money_in_from_the_ledger_and_leaves_out_reversed_payments(): void
    {
        $this->actingAs($this->admin)->post('/school/fees/invoices', ['fee_structure_ids' => [$this->tuition->id], 'term_id' => $this->term->id]);
        $invoice = Invoice::withoutGlobalScopes()->sole();
        $today = now()->toDateString();
        $this->post("/school/fees/invoices/{$invoice->id}/payments", ['amount' => 70000, 'method' => 'cash', 'entry_date' => $today]);
        $this->post("/school/fees/invoices/{$invoice->id}/payments", ['amount' => 7000, 'method' => 'cash', 'entry_date' => $today]);
        $wrong = LedgerEntry::withoutGlobalScopes()->where('type', 'payment')->latest('id')->first();
        $this->post("/school/fees/invoices/{$invoice->id}/entries/{$wrong->id}/reverse", ['reason' => 'Typed twice']);

        $this->get("/school/reports/finance?from_date={$today}&to_date={$today}", ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.collected', 70000)
            ->assertJsonPath('props.outstanding', 130000)
            ->assertJsonPath('props.payments.total', 2)
            ->assertJsonPath('props.dailyChart.0.amount', 70000);

        $this->get('/school/reports/dashboard', ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.monthFees', 70000)
            ->assertJsonPath('props.pendingFees', 130000);
    }
}
