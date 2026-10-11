<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\FeeCategory;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\NumberSequence;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Tests\Feature\Security\SecurityTestCase;

/** Admission numbers, staff IDs, asset codes and receipt numbers never repeat */
class NumberingTest extends SecurityTestCase
{
    private School $school;
    private SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 09:00:00');
        $this->school = $this->makeSchool('Count School');
        $this->actingAs($this->makeUser($this->school, 'school-admin'));
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function student(array $extra = [], ?School $school = null): Student
    {
        $school ??= $this->school;
        $class = $school->is($this->school) ? $this->class : SchoolClass::withoutGlobalScopes()->create(['school_id' => $school->id, 'name' => 'X']);

        return Student::withoutGlobalScopes()->create($extra + [
            'school_id' => $school->id, 'class_id' => $class->id, 'first_name' => 'Ada', 'last_name' => 'Obi', 'gender' => 'female', 'status' => 'active',
        ]);
    }

    public function test_admission_numbers_follow_on_and_are_never_reused_after_a_delete(): void
    {
        $a = $this->student();
        $b = $this->student();
        $this->assertSame(['ADM-2026-0001', 'ADM-2026-0002'], [$a->admission_no, $b->admission_no]);

        // the old way counted rows, so removing a student made the next one repeat a number
        $a->forceDelete();
        $this->assertSame('ADM-2026-0003', $this->student()->admission_no);
    }

    public function test_numbers_already_in_use_are_skipped(): void
    {
        // records from before the counter existed, and one typed by hand
        $this->student(['admission_no' => 'ADM-2026-0007']);
        $this->student(['admission_no' => 'OLD-15']);
        NumberSequence::query()->delete();

        $this->assertSame('ADM-2026-0008', $this->student()->admission_no);

        // a number typed later, just ahead of the counter, is stepped over too
        $this->student(['admission_no' => 'ADM-2026-0009']);
        $this->assertSame('ADM-2026-0010', $this->student()->admission_no);
    }

    public function test_each_school_and_year_has_its_own_series(): void
    {
        $other = $this->makeSchool('Other School');
        $this->assertSame('ADM-2026-0001', $this->student()->admission_no);
        $this->assertSame('ADM-2026-0001', $this->student([], $other)->admission_no);

        Carbon::setTestNow('2027-01-10');
        $this->assertSame('ADM-2027-0001', $this->student()->admission_no);
    }

    public function test_the_database_refuses_a_repeated_admission_number_in_one_school(): void
    {
        $this->student(['admission_no' => 'A1']);
        $this->student(['admission_no' => 'A1'], $this->makeSchool('Other School'));   // fine in another school

        $this->expectException(UniqueConstraintViolationException::class);
        $this->student(['admission_no' => 'A1']);
    }

    public function test_staff_ids_and_asset_codes_use_counters(): void
    {
        $staff = fn () => Staff::create(['school_id' => $this->school->id, 'first_name' => 'Tunde', 'gender' => 'male', 'salary_type' => 'fixed', 'status' => 'active']);
        $first = $staff();
        $first->forceDelete();
        $this->assertSame('EMP-2026-0002', $staff()->emp_id);

        $asset = Asset::create(['school_id' => $this->school->id, 'name' => 'Projector', 'purchase_price' => 100000]);
        $this->assertSame('AST-2026-0001', $asset->asset_code);
    }

    public function test_old_style_receipts_no_longer_clash_between_schools(): void
    {
        // receipt numbers are unique across every school, and both schools used to start at RCP-2026-00001
        $other = $this->makeSchool('Other School');
        $receipt = function (School $school) {
            $student = $this->student([], $school);
            $cat = FeeCategory::withoutGlobalScopes()->create(['school_id' => $school->id, 'name' => 'Tuition', 'type' => 'tuition']);
            $fee = FeeStructure::withoutGlobalScopes()->create(['school_id' => $school->id, 'class_id' => $student->class_id, 'fee_category_id' => $cat->id, 'academic_year' => '2026-2027', 'amount' => 1000, 'frequency' => 'one_time']);

            return FeePayment::withoutGlobalScopes()->create(['school_id' => $school->id, 'student_id' => $student->id, 'fee_structure_id' => $fee->id, 'amount_due' => 1000])->receipt_no;
        };

        $this->assertSame('RCP-2026-00001', $receipt($this->school));
        $this->assertSame('RCP-2026-00002', $receipt($other));
    }
}
