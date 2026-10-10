<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentScheme;
use App\Models\BehaviourTrait;
use App\Models\Exam;
use App\Models\Guardian;
use App\Models\Mark;
use App\Models\ResultSheet;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\TermResultSummary;
use App\Models\User;
use App\Services\TermResultService;
use Illuminate\Support\Collection;
use Tests\Feature\Security\SecurityTestCase;

/** Part scores → stored results, positions, the approval steps, and what families can see. */
class TermResultsTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private SchoolClass $class;
    private Term $term;
    private Subject $maths;
    private Subject $english;
    /** @var array<string, int> component id by short name */
    private array $part;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = $this->makeSchool('Results School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);

        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $this->term = $year->terms()->first();
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1']);
        $this->maths = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'Mathematics', 'code' => 'MTH']);
        $this->english = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'English', 'code' => 'ENG']);
        $this->part = AssessmentScheme::where('is_default', true)->sole()->components->pluck('id', 'short_name')->all();
    }

    private function student(string $first, ?School $school = null, ?SchoolClass $class = null): Student
    {
        $school ??= $this->school;

        return Student::withoutGlobalScopes()->create([
            'school_id' => $school->id, 'class_id' => ($class ?? $this->class)->id, 'first_name' => $first, 'last_name' => 'Test',
            'admission_no' => $first . '-' . $school->id, 'gender' => 'female', 'status' => 'active',
        ]);
    }

    private function sheet(): ResultSheet
    {
        $this->get('/school/results', ['X-Inertia' => 'true'])->assertOk();

        return ResultSheet::where('term_id', $this->term->id)->where('class_id', $this->class->id)->sole();
    }

    /** @param array<int, array{0: Student, 1: float, 2: float, 3: float}> $rows [student, CA1, CA2, Exam] */
    private function enter(ResultSheet $sheet, Subject $subject, array $rows)
    {
        $scores = [];
        foreach ($rows as [$student, $ca1, $ca2, $exam]) {
            foreach (['CA1' => $ca1, 'CA2' => $ca2, 'Exam' => $exam] as $short => $score) {
                $scores[] = ['student_id' => $student->id, 'component_id' => $this->part[$short], 'score' => $score];
            }
        }

        return $this->post("/school/results/{$sheet->id}/scores", ['subject_id' => $subject->id, 'scores' => $scores]);
    }

    public function test_a_subject_score_is_the_sum_of_its_parts_and_is_graded_and_stored(): void
    {
        $ada = $this->student('Ada');
        $sheet = $this->sheet();

        $this->enter($sheet, $this->maths, [[$ada, 18, 15.5, 42]])->assertSessionHasNoErrors();

        $result = TermResult::where('student_id', $ada->id)->where('subject_id', $this->maths->id)->sole();
        $this->assertSame(75.5, $result->total);
        $this->assertSame('A1', $result->grade);
        $this->assertSame('Excellent', $result->remarks);
        $this->assertSame(1, $sheet->fresh()->version);
    }

    public function test_a_score_above_the_part_maximum_is_refused(): void
    {
        $ada = $this->student('Ada');
        $sheet = $this->sheet();

        $this->enter($sheet, $this->maths, [[$ada, 25, 10, 40]])->assertSessionHasErrors('scores.0.score');
        $this->assertSame(0, TermResult::count());
    }

    public function test_positions_and_averages_are_stored_and_ties_share_a_place(): void
    {
        [$ada, $bayo, $chidi, $dupe] = [$this->student('Ada'), $this->student('Bayo'), $this->student('Chidi'), $this->student('Dupe')];
        $sheet = $this->sheet();

        // Maths totals: Ada 90, Bayo 70, Chidi 70, Dupe 50
        $this->enter($sheet, $this->maths, [[$ada, 18, 18, 54], [$bayo, 14, 14, 42], [$chidi, 10, 20, 40], [$dupe, 10, 10, 30]]);
        // English totals: Ada 60, Bayo 80, Chidi 80, Dupe 40
        $this->enter($sheet, $this->english, [[$ada, 12, 12, 36], [$bayo, 16, 16, 48], [$chidi, 16, 16, 48], [$dupe, 8, 8, 24]]);

        $maths = TermResult::where('subject_id', $this->maths->id)->get()->keyBy('student_id');
        $this->assertSame([1, 2, 2, 4], [$maths[$ada->id]->subject_position, $maths[$bayo->id]->subject_position, $maths[$chidi->id]->subject_position, $maths[$dupe->id]->subject_position]);
        $this->assertSame(70.0, $maths[$ada->id]->subject_average);
        $this->assertSame(90.0, $maths[$ada->id]->subject_highest);
        $this->assertSame(50.0, $maths[$ada->id]->subject_lowest);

        // Averages: Ada 75, Bayo 75, Chidi 75, Dupe 45 → three share 1st, Dupe is 4th
        $summary = TermResultSummary::get()->keyBy('student_id');
        $this->assertSame(75.0, $summary[$bayo->id]->average);
        $this->assertSame([1, 1, 1, 4], [$summary[$ada->id]->position, $summary[$bayo->id]->position, $summary[$chidi->id]->position, $summary[$dupe->id]->position]);
        $this->assertSame(4, $summary[$ada->id]->class_size);
        $this->assertSame(67.5, $sheet->fresh()->class_average);
        $this->assertSame(2, $sheet->fresh()->version, 'worked out again after each save');
    }

    public function test_position_helper_handles_ties(): void
    {
        $scores = new Collection([90, 70, 70, 50]);
        $this->assertSame(1, TermResultService::position(90, $scores));
        $this->assertSame(2, TermResultService::position(70, $scores));
        $this->assertSame(4, TermResultService::position(50, $scores));
    }

    public function test_results_move_through_the_steps_and_scores_freeze_after_submitting(): void
    {
        $ada = $this->student('Ada');
        $sheet = $this->sheet();
        $teacher = $this->makeUser($this->school, 'teacher');
        $principal = $this->makeUser($this->school, 'principal');

        // Nothing to submit yet
        $this->actingAs($teacher)->post("/school/results/{$sheet->id}/status", ['action' => 'submit'])->assertSessionHas('error');

        $this->actingAs($teacher);
        $this->enter($sheet, $this->maths, [[$ada, 15, 15, 45]])->assertSessionHasNoErrors();

        // A teacher can submit but not approve or publish
        $this->post("/school/results/{$sheet->id}/status", ['action' => 'submit'])->assertSessionHasNoErrors();
        $this->assertSame('submitted', $sheet->fresh()->status);
        $this->assertSame($teacher->id, $sheet->fresh()->submitted_by);
        $this->post("/school/results/{$sheet->id}/status", ['action' => 'approve'])->assertSessionHas('error');
        $this->assertSame('submitted', $sheet->fresh()->status);

        // Scores are frozen once submitted
        $this->enter($sheet, $this->maths, [[$ada, 20, 20, 60]])->assertSessionHasErrors('scores');
        $this->assertSame(75.0, TermResult::sole()->total);

        // The principal sends it back, the teacher fixes it and resubmits
        $this->actingAs($principal)->post("/school/results/{$sheet->id}/status", ['action' => 'return']);
        $this->assertSame('draft', $sheet->fresh()->status);
        $this->actingAs($teacher);
        $this->enter($sheet, $this->maths, [[$ada, 16, 15, 45]])->assertSessionHasNoErrors();
        $this->post("/school/results/{$sheet->id}/status", ['action' => 'submit']);

        $this->actingAs($principal);
        foreach (['approve' => 'approved', 'publish' => 'published', 'lock' => 'locked'] as $action => $status) {
            $this->post("/school/results/{$sheet->id}/status", ['action' => $action])->assertSessionHasNoErrors();
            $this->assertSame($status, $sheet->fresh()->status);
        }
        $this->assertNotNull($sheet->fresh()->locked_at);

        // Skipping a step is refused
        $this->post("/school/results/{$sheet->id}/status", ['action' => 'approve'])->assertSessionHas('error');
        $this->assertSame('locked', $sheet->fresh()->status);
    }

    public function test_parents_and_students_only_see_published_results(): void
    {
        $ada = $this->student('Ada');
        $sheet = $this->sheet();
        $this->enter($sheet, $this->maths, [[$ada, 15, 15, 45]]);

        $studentUser = User::factory()->create(['school_id' => $this->school->id, 'status' => 'active']);
        $studentUser->assignRole('student');
        $ada->update(['user_id' => $studentUser->id]);

        $parentUser = User::factory()->create(['school_id' => $this->school->id, 'status' => 'active']);
        $parentUser->assignRole('parent');
        $guardian = Guardian::withoutGlobalScopes()->create(['school_id' => $this->school->id, 'user_id' => $parentUser->id, 'name' => 'Mrs Test', 'phone' => '08030000000']);
        $ada->update(['guardian_id' => $guardian->id]);

        $this->actingAs($studentUser)->get('/school/student/results', ['X-Inertia' => 'true'])->assertOk()->assertJsonCount(0, 'props.reports');
        $this->actingAs($parentUser)->get('/school/parent/results', ['X-Inertia' => 'true'])->assertOk()->assertJsonCount(0, 'props.children.0.reports');

        $sheet->forceFill(['status' => 'published'])->save();

        $this->actingAs($studentUser)->get('/school/student/results', ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.reports.0.position', 1)
            ->assertJsonPath('props.reports.0.subjects.0.subject', 'Mathematics')
            ->assertJsonPath('props.reports.0.subjects.0.grade', 'A1');
        $this->actingAs($parentUser)->get('/school/parent/results', ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.children.0.reports.0.average', 75);
    }

    public function test_marks_from_unpublished_exams_are_hidden_from_families(): void
    {
        $ada = $this->student('Ada');
        $studentUser = User::factory()->create(['school_id' => $this->school->id, 'status' => 'active']);
        $studentUser->assignRole('student');
        $ada->update(['user_id' => $studentUser->id]);

        foreach (['draft' => 40, 'published' => 70] as $status => $score) {
            $exam = Exam::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => ucfirst($status) . ' exam', 'type' => 'final', 'status' => $status]);
            Mark::create(['school_id' => $this->school->id, 'exam_id' => $exam->id, 'student_id' => $ada->id, 'subject_id' => $this->maths->id, 'marks_obtained' => $score]);
        }

        $this->actingAs($studentUser)->get('/school/student/results', ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonCount(1, 'props.exams')
            ->assertJsonPath('props.exams.0.name', 'Published exam');
    }

    public function test_behaviour_ratings_are_saved_and_must_be_one_to_five(): void
    {
        $ada = $this->student('Ada');
        $sheet = $this->sheet();
        $neatness = BehaviourTrait::where('name', 'Neatness')->sole();

        $this->post("/school/results/{$sheet->id}/ratings", ['ratings' => [['student_id' => $ada->id, 'trait_id' => $neatness->id, 'rating' => 6]]])
            ->assertSessionHasErrors('ratings.0.rating');

        $this->post("/school/results/{$sheet->id}/ratings", ['ratings' => [['student_id' => $ada->id, 'trait_id' => $neatness->id, 'rating' => 4]]])
            ->assertSessionHasNoErrors();
        $this->assertSame(4, $sheet->ratings()->sole()->rating);

        $this->post("/school/results/{$sheet->id}/ratings", ['ratings' => [['student_id' => $ada->id, 'trait_id' => $neatness->id, 'rating' => null]]]);
        $this->assertSame(0, $sheet->ratings()->count());
    }

    public function test_a_new_school_gets_behaviour_and_skills_to_rate(): void
    {
        $this->assertSame(8, BehaviourTrait::where('domain', 'affective')->count());
        $this->assertSame(6, BehaviourTrait::where('domain', 'psychomotor')->count());
    }

    public function test_one_school_cannot_open_or_write_to_another_schools_results(): void
    {
        $other = $this->makeSchool('Other Results School');
        $otherAdmin = $this->makeUser($other, 'school-admin');
        $sheet = $this->sheet();

        $this->actingAs($otherAdmin);
        $this->get("/school/results/{$sheet->id}", ['X-Inertia' => 'true'])->assertNotFound();
        $this->post("/school/results/{$sheet->id}/scores", ['subject_id' => $this->maths->id, 'scores' => []])->assertNotFound();
        $this->post("/school/results/{$sheet->id}/status", ['action' => 'submit'])->assertNotFound();

        // Nor can it slip its own student into this school's sheet
        $this->actingAs($this->admin);
        $outsider = $this->student('Outsider', $other, SchoolClass::withoutGlobalScopes()->create(['school_id' => $other->id, 'name' => 'JSS 1']));
        $this->post("/school/results/{$sheet->id}/scores", ['subject_id' => $this->maths->id, 'scores' => [
            ['student_id' => $outsider->id, 'component_id' => $this->part['CA1'], 'score' => 10],
        ]])->assertSessionHasErrors('scores.0.score');
        $this->assertSame(0, TermResult::withoutGlobalScopes()->count());
    }

    public function test_the_sheet_page_shows_the_parts_for_the_chosen_subject(): void
    {
        $this->student('Ada');
        $sheet = $this->sheet();

        $this->get("/school/results/{$sheet->id}?subject_id={$this->english->id}", ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.subjectId', $this->english->id)
            ->assertJsonPath('props.components.0.short_name', 'CA1')
            ->assertJsonPath('props.components.2.short_name', 'Exam')
            ->assertJsonPath('props.canEnter', true)
            ->assertJsonPath('props.actions', ['submit']);
    }
}
