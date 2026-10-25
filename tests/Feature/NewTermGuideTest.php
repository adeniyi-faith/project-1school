<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use Tests\Feature\Security\SecurityTestCase;

/** The "Start a new term" checklist page. */
class NewTermGuideTest extends SecurityTestCase
{
    public function test_steps_are_listed_and_a_manual_step_can_be_ticked(): void
    {
        $school = $this->makeSchool('Term School');
        $admin = $this->makeUser($school, 'school-admin');
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $year->terms()->orderBy('sequence')->first()->makeCurrent();

        $this->actingAs($admin)->get('/school/new-term')->assertOk()->assertInertia(fn ($p) => $p
            ->where('term.name', fn ($n) => is_string($n))
            ->where('steps.0.key', 'term')
            ->where('steps.1.key', 'classes')
            ->where('steps.1.done', false));

        $this->post('/school/new-term/tick', ['step' => 'classes'])->assertRedirect();

        $this->get('/school/new-term')->assertInertia(fn ($p) => $p->where('steps.1.done', true));

        $this->post('/school/new-term/tick', ['step' => 'classes']);
        $this->get('/school/new-term')->assertInertia(fn ($p) => $p->where('steps.1.done', false));
    }

    public function test_a_step_the_system_checks_itself_cannot_be_ticked_by_hand(): void
    {
        $school = $this->makeSchool('Term School');
        $admin = $this->makeUser($school, 'school-admin');

        $this->actingAs($admin)->post('/school/new-term/tick', ['step' => 'invoices'])->assertSessionHasErrors('step');
    }
}
