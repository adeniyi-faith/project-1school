<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentScheme;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\ResultSheet;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Scholarship;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\TermResultSummary;
use App\Support\SchoolDefaults;
use Tests\Feature\Security\SecurityTestCase;

/**
 * The two sums where a silent bug costs someone directly: a student's grade and a family's balance.
 * These check the edges: decimal scores that land on a grade boundary, blank parts, kobo amounts
 * paid in pieces, percentage discounts that do not divide evenly, and corrections.
 */
class CalculationsTest extends SecurityTestCase
{
    private School $school;
    private SchoolClass $class;
    private Term $term;
    /** @var array<string, int> */
    private array $part;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = $this->makeSchool('Sums School');
        $this->actingAs($this->makeUser($this->school, 'school-admin'));
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $this->term = $year->terms()->first();
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 3']);
        $this->part = AssessmentScheme::where('is_default', true)->sole()->components->pluck('id', 'short_name')->all();
    }

    private function student(string $first): Student
    {
        return Student::create([
            'school_id' => $this->school->id, 'class_id' => $this->class->id, 'first_name' => $first, 'last_name' => 'Sum',
            'admission_no' => strtoupper($first), 'gender' => 'female', 'status' => 'active',
        ]);
    }

    private function subject(string $name): Subject
    {
        return Subject::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => $name, 'code' => strtoupper(substr($name, 0, 3))]);
    }

    /** @param array<int, array{0: Student, 1: ?float, 2: ?float, 3: ?float}> $rows [student, CA1, CA2, Exam] */
    private function enter(Subject $subject, array $rows): void
    {
        $this->get('/school/results', ['X-Inertia' => 'true'])->assertOk();
        $sheet = ResultSheet::where('term_id', $this->term->id)->where('class_id', $this->class->id)->sole();

        $scores = [];
        foreach ($rows as [$student, $ca1, $ca2, $exam]) {
            foreach (['CA1' => $ca1, 'CA2' => $ca2, 'Exam' => $exam] as $short => $score) {
                $scores[] = ['student_id' => $student->id, 'component_id' => $this->part[$short], 'score' => $score];
            }
        }
        $this->post("/school/results/{$sheet->id}/scores", ['subject_id' => $subject->id, 'scores' => $scores])->assertSessionHasNoErrors();
    }

    private function termResult(Student $student, Subject $subject): TermResult
    {
        return TermResult::where('student_id', $student->id)->where('subject_id', $subject->id)->sole();
    }

    // ───────────── grades ─────────────

    public function test_decimal_part_scores_land_on_the_right_side_of_a_grade_boundary(): void
    {
        $maths = $this->subject('Mathematics');
        [$ada, $bayo, $chidi, $dupe] = [$this->student('Ada'), $this->student('Bayo'), $this->student('Chidi'), $this->student('Dupe')];

        $this->enter($maths, [
            [$ada, 10.1, 19.9, 45],    // 75 exactly, even though 10.1 + 19.9 is not exact in binary → A1
            [$bayo, 14.5, 10, 50],     // 74.5: below 75 → B2, not rounded up into A1
            [$chidi, 19.95, 19.95, 0], // 39.9: below 40 → F9
            [$dupe, 0.1, 0.2, 39.7],   // 40 exactly → E8
        ]);

        $this->assertSame([75.0, 'A1'], [$this->termResult($ada, $maths)->total, $this->termResult($ada, $maths)->grade]);
        $this->assertSame([74.5, 'B2'], [$this->termResult($bayo, $maths)->total, $this->termResult($bayo, $maths)->grade]);
        $this->assertSame([39.9, 'F9'], [$this->termResult($chidi, $maths)->total, $this->termResult($chidi, $maths)->grade]);
        $this->assertSame([40.0, 'E8'], [$this->termResult($dupe, $maths)->total, $this->termResult($dupe, $maths)->grade]);
    }

    public function test_a_blank_part_counts_as_not_entered_and_a_subject_with_no_scores_is_left_out(): void
    {
        $maths = $this->subject('Mathematics');
        $english = $this->subject('English');
        $ada = $this->student('Ada');

        $this->enter($maths, [[$ada, 18, null, 50]]);   // CA2 not entered yet: total is 68
        $this->enter($english, [[$ada, null, null, null]]);

        $this->assertSame(68.0, $this->termResult($ada, $maths)->total);
        $this->assertSame(0, TermResult::where('subject_id', $english->id)->count(), 'an empty subject does not drag the average down');
        $summary = TermResultSummary::where('student_id', $ada->id)->sole();
        $this->assertSame(1, $summary->subjects_count);
        $this->assertSame(68.0, $summary->average);
    }

    public function test_the_average_is_rounded_to_two_places_only_at_the_end(): void
    {
        $ada = $this->student('Ada');
        foreach ([[20, 20, 35], [14.5, 10, 50], [12, 12, 36]] as $i => [$ca1, $ca2, $exam]) {
            $this->enter($this->subject("Subject {$i}"), [[$ada, $ca1, $ca2, $exam]]);
        }

        // (75 + 74.5 + 60) / 3 = 69.8333…
        $summary = TermResultSummary::where('student_id', $ada->id)->sole();
        $this->assertSame(209.5, $summary->total_score);
        $this->assertSame(69.83, $summary->average);
    }

    public function test_a_class_on_the_simple_scale_is_graded_on_that_scale(): void
    {
        $this->class->update(['grading_scheme_id' => SchoolDefaults::createGradingScheme($this->school->id, 'simple')]);
        $maths = $this->subject('Mathematics');
        $ada = $this->student('Ada');

        $this->enter($maths, [[$ada, 14, 14, 42]]); // 70: A on the simple scale, B2 on WAEC

        $this->assertSame('A', $this->termResult($ada, $maths)->grade);
    }

    // ───────────── fee balances ─────────────

    private function invoice(float $amount, ?Scholarship $scholarship = null): Invoice
    {
        $cat = FeeCategory::create(['school_id' => $this->school->id, 'name' => 'Tuition', 'type' => 'tuition']);
        $fee = FeeStructure::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'fee_category_id' => $cat->id, 'academic_year' => '2026/2027', 'amount' => $amount, 'frequency' => 'one_time']);
        $this->student('Ada');
        if ($scholarship) {
            $this->post('/school/fees/scholarships/awards', ['admission_no' => 'ADA', 'scholarship_id' => $scholarship->id, 'note' => 'Test'])->assertSessionHasNoErrors();
        }
        $this->post('/school/fees/invoices', ['fee_structure_ids' => [$fee->id], 'term_id' => $this->term->id])->assertSessionHasNoErrors();

        return Invoice::sole();
    }

    private function pay(Invoice $invoice, float $amount)
    {
        return $this->post("/school/fees/invoices/{$invoice->id}/payments", ['amount' => $amount, 'method' => 'cash', 'entry_date' => now()->toDateString()]);
    }

    public function test_kobo_payments_in_pieces_settle_the_invoice_exactly(): void
    {
        $invoice = $this->invoice(100000);

        $this->pay($invoice, 33333.33)->assertSessionHasNoErrors();
        $this->pay($invoice, 33333.33)->assertSessionHasNoErrors();
        $this->assertSame(33333.34, $invoice->fresh()->balance);
        $this->pay($invoice, 33333.35)->assertSessionHasErrors('amount'); // one kobo too many
        $this->pay($invoice, 33333.34)->assertSessionHasNoErrors();

        $this->assertSame(0.0, $invoice->fresh()->balance);
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_a_percentage_discount_is_rounded_to_the_nearest_kobo(): void
    {
        $eighth = Scholarship::create(['school_id' => $this->school->id, 'name' => 'Eighth off', 'type' => 'percent', 'value' => 12.5]);
        $invoice = $this->invoice(33333.33, $eighth);

        // 12.5% of 33,333.33 is 4,166.66625 → 4,166.67
        $this->assertSame(-4166.67, LedgerEntry::where('type', 'discount')->sole()->amount);
        $this->assertSame(29166.66, $invoice->balance);
    }

    public function test_reversing_a_discount_puts_the_full_fee_back(): void
    {
        $half = Scholarship::create(['school_id' => $this->school->id, 'name' => 'Half', 'type' => 'percent', 'value' => 50]);
        $invoice = $this->invoice(80000, $half);
        $this->assertSame(40000.0, $invoice->balance);

        $discount = LedgerEntry::where('type', 'discount')->sole();
        $this->post("/school/fees/invoices/{$invoice->id}/entries/{$discount->id}/reverse", ['reason' => 'Given by mistake'])->assertSessionHasNoErrors();

        $this->assertSame(80000.0, $invoice->fresh()->balance);
    }

    public function test_a_fine_on_a_paid_invoice_reopens_it(): void
    {
        $invoice = $this->invoice(50000);
        $this->pay($invoice, 50000);
        $this->assertSame('paid', $invoice->fresh()->status);

        $this->post("/school/fees/invoices/{$invoice->id}/fines", ['amount' => 2500, 'note' => 'Lost library book'])->assertSessionHasNoErrors();

        $this->assertSame(2500.0, $invoice->fresh()->balance);
        $this->assertSame('partial', $invoice->fresh()->status);
    }

    public function test_the_stored_balance_always_equals_the_sum_of_the_ledger(): void
    {
        $invoice = $this->invoice(123456.78);
        $this->pay($invoice, 0.01);
        $this->post("/school/fees/invoices/{$invoice->id}/fines", ['amount' => 0.02, 'note' => 'x']);
        $this->pay($invoice, 100000.5);
        $payment = LedgerEntry::where('type', 'payment')->latest('id')->first();
        $this->post("/school/fees/invoices/{$invoice->id}/entries/{$payment->id}/reverse", ['reason' => 'x']);

        $sum = round((float) LedgerEntry::where('invoice_id', $invoice->id)->sum('amount'), 2);
        $this->assertSame(123456.79, $sum);
        $this->assertSame($sum, $invoice->fresh()->balance);
    }
}
