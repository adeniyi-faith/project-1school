<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ResultSheet;
use App\Models\SchoolClass;
use Tests\Feature\Security\SecurityTestCase;

/** The "Waiting for you" box on the principal's and admin's dashboard. */
class WaitingForYouTest extends SecurityTestCase
{
    public function test_principal_sees_result_sheets_waiting_for_approval(): void
    {
        $school = $this->makeSchool('Waiting School');
        $principal = $this->makeUser($school, 'principal');
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2025/2026', 'start_date' => '2025-09-08', 'end_date' => '2026-07-24', 'is_current' => true]);
        $term = $year->terms()->orderBy('sequence')->first();
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        ResultSheet::create(['school_id' => $school->id, 'term_id' => $term->id, 'class_id' => $class->id, 'status' => 'submitted']);

        $this->actingAs($principal)->get('/school/reports/dashboard')->assertOk()->assertInertia(fn ($p) => $p
            ->where('waiting.0.label', 'Result sheets to approve')
            ->where('waiting.0.count', 1)
            ->has('waiting', 1));
    }

    public function test_nothing_is_listed_when_nothing_is_waiting(): void
    {
        $school = $this->makeSchool('Quiet School');
        $principal = $this->makeUser($school, 'principal');

        $this->actingAs($principal)->get('/school/reports/dashboard')->assertOk()->assertInertia(fn ($p) => $p->has('waiting', 0));
    }
}
