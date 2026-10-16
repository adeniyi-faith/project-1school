<?php

namespace Tests\Feature\Correctness;

use App\Models\FeeCategory;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Facades\Validator;
use Tests\Feature\Security\SecurityTestCase;

/** A plain "exists" rule must never accept another school's row, anywhere in the app. */
class TenantValidationTest extends SecurityTestCase
{
    private function check(string $rule, $value): bool
    {
        return Validator::make(['x' => $value], ['x' => $rule])->passes();
    }

    public function test_exists_only_accepts_the_signed_in_schools_own_rows(): void
    {
        $mine = $this->makeSchool('Mine');
        $theirs = $this->makeSchool('Theirs');
        $myClass = SchoolClass::create(['school_id' => $mine->id, 'name' => 'JSS 1']);
        $theirClass = SchoolClass::create(['school_id' => $theirs->id, 'name' => 'JSS 1']);
        $this->actingAs($this->makeUser($mine, 'school-admin'));

        $this->assertTrue($this->check('exists:classes,id', $myClass->id));
        $this->assertFalse($this->check('exists:classes,id', $theirClass->id));
        $this->assertFalse($this->check('exists:classes,id', 999999));
        $this->assertFalse($this->check('required|exists:classes', $theirClass->id));
    }

    public function test_deleted_rows_are_not_accepted(): void
    {
        $mine = $this->makeSchool('Mine');
        $this->actingAs($this->makeUser($mine, 'school-admin'));
        $c = SchoolClass::create(['school_id' => $mine->id, 'name' => 'Old class']);
        $this->assertTrue($this->check('exists:classes,id', $c->id));
        $c->delete();
        $this->assertFalse($this->check('exists:classes,id', $c->id));
    }

    public function test_lists_of_ids_are_checked_one_by_one(): void
    {
        $mine = $this->makeSchool('Mine');
        $theirs = $this->makeSchool('Theirs');
        $a = FeeCategory::create(['school_id' => $mine->id, 'name' => 'Tuition', 'type' => 'tuition']);
        $b = FeeCategory::create(['school_id' => $theirs->id, 'name' => 'Tuition', 'type' => 'tuition']);
        $this->actingAs($this->makeUser($mine, 'school-admin'));

        $ok = Validator::make(['ids' => [$a->id]], ['ids.*' => 'exists:fee_categories,id'])->passes();
        $bad = Validator::make(['ids' => [$a->id, $b->id]], ['ids.*' => 'exists:fee_categories,id'])->passes();
        $this->assertTrue($ok);
        $this->assertFalse($bad);
    }

    public function test_tables_with_no_school_column_and_signed_out_visitors_are_not_limited(): void
    {
        $theirs = $this->makeSchool('Theirs');
        // schools has no school_id column: the platform owner's screens still work
        $this->actingAs($this->makeUser($this->makeSchool('Mine'), 'school-admin'));
        $this->assertTrue($this->check('exists:schools,id', $theirs->id));
        auth()->logout();
        $this->assertTrue($this->check('exists:schools,id', $theirs->id));
    }

    public function test_a_rule_that_already_names_the_school_is_left_alone(): void
    {
        $mine = $this->makeSchool('Mine');
        $c = SchoolClass::create(['school_id' => $mine->id, 'name' => 'JSS 1']);
        $this->actingAs($this->makeUser($mine, 'school-admin'));
        $this->assertTrue($this->check("exists:classes,id,school_id,{$mine->id}", $c->id));
    }
}
