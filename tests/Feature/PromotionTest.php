<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\PromotionBatch;
use App\Models\ResultSheet;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentPromotion;
use App\Models\TermResultSummary;
use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;

/** Year-end moves: promote a class, keep some back with a reason, graduate the top class, undo. */
class PromotionTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private AcademicYear $year;
    private SchoolClass $jss1;
    private SchoolClass $jss2;
    private SchoolClass $ss3;
    private Section $jss1a;
    private Section $jss2a;
    private Student $ada;
    private Student $bayo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = $this->makeSchool('Promotion School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);

        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2025/2026', 'start_date' => '2025-09-08', 'end_date' => '2026-07-24', 'is_current' => true]);
        $this->jss1 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        $this->jss2 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 2', 'numeric_name' => 8]);
        $this->ss3 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'SS 3', 'numeric_name' => 12]);
        $this->jss1a = Section::create(['school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'name' => 'A']);
        $this->jss2a = Section::create(['school_id' => $this->school->id, 'class_id' => $this->jss2->id, 'name' => 'A']);

        $this->ada = $this->student('Ada', $this->jss1, $this->jss1a);
        $this->bayo = $this->student('Bayo', $this->jss1, $this->jss1a);
    }

    private function student(string $name, SchoolClass $class, ?Section $section = null): Student
    {
        return Student::create([
            'school_id' => $this->school->id, 'class_id' => $class->id, 'section_id' => $section?->id,
            'first_name' => $name, 'last_name' => 'Test', 'gender' => 'female', 'status' => 'active',
        ]);
    }

    /** Give each student a checked term result per term average listed */
    private function results(SchoolClass $class, array $averages, string $status = 'published'): void
    {
        foreach ($this->year->terms()->get()->values() as $i => $term) {
            $sheet = ResultSheet::create(['school_id' => $this->school->id, 'term_id' => $term->id, 'class_id' => $class->id, 'status' => $status]);
            foreach ($averages as $studentId => $perTerm) {
                if (isset($perTerm[$i])) {
                    TermResultSummary::create(['school_id' => $this->school->id, 'result_sheet_id' => $sheet->id, 'student_id' => $studentId, 'average' => $perTerm[$i], 'total_score' => $perTerm[$i] * 8, 'subjects_count' => 8, 'class_size' => count($averages)]);
                }
            }
        }
    }

    private function move(SchoolClass $from, ?SchoolClass $to, array $decisions)
    {
        return $this->post('/school/promotions', [
            'academic_year_id' => $this->year->id, 'from_class_id' => $from->id, 'to_class_id' => $to?->id,
            'pass_mark' => 40, 'decisions' => $decisions,
        ]);
    }

    public function test_the_page_suggests_the_next_class_and_flags_students_below_the_pass_mark(): void
    {
        $this->results($this->jss1, [$this->ada->id => [70, 65, 80], $this->bayo->id => [35, 30.5, 41]]);

        $this->get("/school/promotions?class_id={$this->jss1->id}", ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.selected.next_class_id', $this->jss2->id)
            ->assertJsonPath('props.selected.is_top', false)
            ->assertJsonPath('props.selected.students.0.name', 'Ada Test')
            ->assertJsonPath('props.selected.students.0.year_average', 71.67)
            ->assertJsonPath('props.selected.students.0.terms_counted', 3)
            ->assertJsonPath('props.selected.students.0.suggested', 'promoted')
            ->assertJsonPath('props.selected.students.1.year_average', 35.5)
            ->assertJsonPath('props.selected.students.1.suggested', 'repeated');

        // Top class: the suggestion is to graduate
        $this->student('Chidi', $this->ss3);
        $this->get("/school/promotions?class_id={$this->ss3->id}", ['X-Inertia' => 'true'])
            ->assertJsonPath('props.selected.is_top', true)
            ->assertJsonPath('props.selected.students.0.suggested', 'graduated');
    }

    public function test_draft_results_do_not_count_towards_the_year_average(): void
    {
        $this->results($this->jss1, [$this->bayo->id => [20, 20, 20]], 'draft');

        $this->get("/school/promotions?class_id={$this->jss1->id}", ['X-Inertia' => 'true'])
            ->assertJsonPath('props.selected.students.1.year_average', null)
            ->assertJsonPath('props.selected.students.1.suggested', 'promoted');
    }

    public function test_moving_a_class_up_with_one_repeat(): void
    {
        $this->results($this->jss1, [$this->bayo->id => [35, 30, 40]]);

        $this->move($this->jss1, $this->jss2, [
            ['student_id' => $this->ada->id, 'outcome' => 'promoted', 'section_id' => $this->jss2a->id],
            ['student_id' => $this->bayo->id, 'outcome' => 'repeated', 'reason' => 'Below pass mark; parents agreed'],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame([$this->jss2->id, $this->jss2a->id, 'active'], [$this->ada->fresh()->class_id, $this->ada->fresh()->section_id, $this->ada->fresh()->status]);
        $this->assertSame([$this->jss1->id, $this->jss1a->id], [$this->bayo->fresh()->class_id, $this->bayo->fresh()->section_id]);

        $batch = PromotionBatch::sole();
        $this->assertSame([1, 1, 0, $this->admin->id], [$batch->promoted_count, $batch->repeated_count, $batch->graduated_count, $batch->performed_by]);
        $repeat = StudentPromotion::where('student_id', $this->bayo->id)->sole();
        $this->assertSame(['repeated', 'Below pass mark; parents agreed', 35.0], [$repeat->outcome, $repeat->reason, $repeat->year_average]);

        // The student's profile shows the history
        $this->get("/school/students/{$this->bayo->id}", ['X-Inertia' => 'true'])
            ->assertJsonPath('props.classHistory.0.outcome', 'repeated')
            ->assertJsonPath('props.classHistory.0.year', '2025/2026')
            ->assertJsonPath('props.classHistory.0.reason', 'Below pass mark; parents agreed');
    }

    public function test_a_repeat_needs_a_reason(): void
    {
        $this->move($this->jss1, $this->jss2, [['student_id' => $this->bayo->id, 'outcome' => 'repeated', 'reason' => ' ']])
            ->assertSessionHasErrors('decisions.0.reason');
        $this->assertSame(0, PromotionBatch::count());
        $this->assertSame($this->jss1->id, $this->bayo->fresh()->class_id);
    }

    public function test_nobody_is_moved_twice_in_one_year(): void
    {
        $this->move($this->jss1, $this->jss2, [['student_id' => $this->ada->id, 'outcome' => 'promoted']])->assertSessionHasNoErrors();

        // Ada is now in JSS 2; moving JSS 2 up for the same year must not pick her up again
        $this->get("/school/promotions?class_id={$this->jss2->id}", ['X-Inertia' => 'true'])
            ->assertJsonCount(0, 'props.selected.students');
        $this->move($this->jss2, $this->ss3, [['student_id' => $this->ada->id, 'outcome' => 'promoted']])
            ->assertSessionHasErrors('decisions');

        // Sending the same JSS 1 form again (a double click) does nothing either
        $this->move($this->jss1, $this->jss2, [['student_id' => $this->ada->id, 'outcome' => 'promoted']])
            ->assertSessionHasErrors('decisions');

        $this->assertSame($this->jss2->id, $this->ada->fresh()->class_id);
        $this->assertSame(1, PromotionBatch::count());

        // Bayo was left out, so he is still waiting and can be moved later
        $this->get("/school/promotions?class_id={$this->jss1->id}", ['X-Inertia' => 'true'])
            ->assertJsonCount(1, 'props.selected.students')
            ->assertJsonPath('props.selected.students.0.id', $this->bayo->id);
    }

    public function test_graduating_the_top_class_makes_students_alumni(): void
    {
        $chidi = $this->student('Chidi', $this->ss3);

        $this->move($this->ss3, null, [['student_id' => $chidi->id, 'outcome' => 'graduated']])->assertSessionHasNoErrors();

        $this->assertSame(['alumni', $this->ss3->id], [$chidi->fresh()->status, $chidi->fresh()->class_id]);

        // Moving up needs a class to move to
        $dayo = $this->student('Dayo', $this->ss3);
        $this->move($this->ss3, null, [['student_id' => $dayo->id, 'outcome' => 'promoted']])->assertSessionHasErrors('to_class_id');
    }

    public function test_a_section_from_another_class_is_refused(): void
    {
        $this->move($this->jss1, $this->jss2, [['student_id' => $this->ada->id, 'outcome' => 'promoted', 'section_id' => $this->jss1a->id]])
            ->assertSessionHasErrors('decisions');
        $this->assertSame($this->jss1->id, $this->ada->fresh()->class_id);
    }

    public function test_undo_puts_everyone_back_unless_someone_was_changed_since(): void
    {
        $chidi = $this->student('Chidi', $this->ss3);
        $this->move($this->ss3, null, [['student_id' => $chidi->id, 'outcome' => 'graduated']]);
        $this->move($this->jss1, $this->jss2, [
            ['student_id' => $this->ada->id, 'outcome' => 'promoted', 'section_id' => $this->jss2a->id],
            ['student_id' => $this->bayo->id, 'outcome' => 'promoted'],
        ]);
        [$graduation, $jss1Move] = PromotionBatch::orderBy('id')->get()->all();

        $this->post("/school/promotions/{$graduation->id}/undo")->assertSessionHasNoErrors();
        $this->assertSame('active', $chidi->fresh()->status);

        // Bayo was moved again by hand after the batch, so undoing it is refused and nobody changes
        $this->bayo->update(['class_id' => $this->ss3->id]);
        $this->post("/school/promotions/{$jss1Move->id}/undo")->assertSessionHasErrors('batch');
        $this->assertSame($this->jss2->id, $this->ada->fresh()->class_id);

        $this->bayo->update(['class_id' => $this->jss2->id]);
        $this->post("/school/promotions/{$jss1Move->id}/undo")->assertSessionHasNoErrors();
        $this->assertSame([$this->jss1->id, $this->jss1a->id], [$this->ada->fresh()->class_id, $this->ada->fresh()->section_id]);
        $this->assertNotNull($jss1Move->fresh()->undone_at);

        // Undone moves drop out of the history, and the students can be moved again
        $this->get("/school/students/{$this->ada->id}", ['X-Inertia' => 'true'])->assertJsonCount(0, 'props.classHistory');
        $this->get("/school/promotions?class_id={$this->jss1->id}", ['X-Inertia' => 'true'])->assertJsonCount(2, 'props.selected.students');
        $this->post("/school/promotions/{$jss1Move->id}/undo")->assertSessionHasErrors('batch'); // not twice
    }

    public function test_only_staff_with_the_promote_permission_can_move_students_and_only_in_their_school(): void
    {
        $teacher = $this->makeUser($this->school, 'teacher');
        $this->actingAs($teacher)->get('/school/promotions')->assertForbidden();
        $this->actingAs($teacher)->post('/school/promotions', [])->assertForbidden();

        $other = $this->makeSchool('Other School');
        $otherAdmin = $this->makeUser($other, 'school-admin');
        $this->actingAs($otherAdmin);
        $this->move($this->jss1, $this->jss2, [['student_id' => $this->ada->id, 'outcome' => 'promoted']])->assertNotFound();
        $this->assertSame($this->jss1->id, $this->ada->fresh()->class_id);

        $this->actingAs($this->admin);
        $this->move($this->jss1, $this->jss2, [['student_id' => $this->ada->id, 'outcome' => 'promoted']]);
        $batchId = PromotionBatch::sole()->id;
        $this->actingAs($otherAdmin)->post("/school/promotions/{$batchId}/undo")->assertNotFound();
    }
}
