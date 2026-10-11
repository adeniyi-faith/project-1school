<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentScheme;
use App\Models\Exam;
use App\Models\Mark;
use App\Models\ResultSheet;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectScore;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;

/** Marks from the older one-number-per-exam screen copied into the term results */
class OldMarksTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private Term $term;
    private SchoolClass $class;
    private Subject $maths;
    private Subject $english;
    private Student $ada;
    private Student $bayo;
    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('Marks School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);

        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $this->term = $year->terms()->first();
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1']);
        $this->maths = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'Mathematics', 'code' => 'MTH', 'full_marks' => 50]);
        $this->english = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'English', 'code' => 'ENG', 'full_marks' => 100]);
        foreach (['ada' => 'Ada', 'bayo' => 'Bayo'] as $key => $name) {
            $this->{$key} = Student::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'first_name' => $name, 'last_name' => 'Test', 'gender' => 'female', 'status' => 'active']);
        }
        $this->exam = Exam::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'term_id' => $this->term->id, 'name' => 'First Term Exam', 'type' => 'final', 'status' => 'completed']);
        $mark = fn ($s, $subj, $m, $absent = false) => Mark::create(['school_id' => $this->school->id, 'exam_id' => $this->exam->id, 'student_id' => $s->id, 'subject_id' => $subj->id, 'marks_obtained' => $m, 'is_absent' => $absent]);
        $mark($this->ada, $this->maths, 45);       // 45/50 → 54/60
        $mark($this->bayo, $this->maths, 20);      // 20/50 → 24/60
        $mark($this->ada, $this->english, 70);     // 70/100 → 42/60
        $mark($this->bayo, $this->english, 0, true);
    }

    private function part(string $short): int
    {
        return AssessmentScheme::where('is_default', true)->sole()->components->firstWhere('short_name', $short)->id;
    }

    private function score(Student $s, Subject $subj, string $short): ?float
    {
        return SubjectScore::where('student_id', $s->id)->where('subject_id', $subj->id)->where('assessment_component_id', $this->part($short))->value('score');
    }

    public function test_marks_are_scaled_into_the_chosen_part_and_results_are_worked_out(): void
    {
        $this->get('/school/exams')->assertInertia(fn ($p) => $p->where('parts.2.value', 'Exam'));

        $this->post("/school/exams/{$this->exam->id}/copy-to-results", ['term_id' => $this->term->id, 'part' => 'exam'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(54.0, $this->score($this->ada, $this->maths, 'Exam'));
        $this->assertSame(24.0, $this->score($this->bayo, $this->maths, 'Exam'));
        $this->assertSame(42.0, $this->score($this->ada, $this->english, 'Exam'));
        $this->assertNull($this->score($this->bayo, $this->english, 'Exam'));        // absent: nothing copied

        $sheet = ResultSheet::where('term_id', $this->term->id)->where('class_id', $this->class->id)->sole();
        $this->assertSame('draft', $sheet->status);
        $this->assertSame(54.0, (float) TermResult::where('result_sheet_id', $sheet->id)->where('student_id', $this->ada->id)->where('subject_id', $this->maths->id)->value('total'));
        $this->assertSame(4, Mark::count());                                          // old marks untouched
    }

    public function test_scores_already_typed_are_kept_unless_replace_is_ticked(): void
    {
        $this->post("/school/exams/{$this->exam->id}/copy-to-results", ['term_id' => $this->term->id, 'part' => 'CA1']);
        $this->assertSame(18.0, $this->score($this->ada, $this->maths, 'CA1'));     // 45/50 of 20
        SubjectScore::where('student_id', $this->ada->id)->where('subject_id', $this->maths->id)->update(['score' => 11]);

        $this->post("/school/exams/{$this->exam->id}/copy-to-results", ['term_id' => $this->term->id, 'part' => 'CA1']);
        $this->assertSame(11.0, $this->score($this->ada, $this->maths, 'CA1'));

        $this->post("/school/exams/{$this->exam->id}/copy-to-results", ['term_id' => $this->term->id, 'part' => 'CA1', 'replace' => true]);
        $this->assertSame(18.0, $this->score($this->ada, $this->maths, 'CA1'));
    }

    public function test_submitted_results_and_unknown_parts_are_refused(): void
    {
        $this->post("/school/exams/{$this->exam->id}/copy-to-results", ['term_id' => $this->term->id, 'part' => 'CA9'])
            ->assertSessionHas('error');
        $this->assertSame(0, SubjectScore::count());

        ResultSheet::where('class_id', $this->class->id)->update(['status' => 'submitted']);
        $this->post("/school/exams/{$this->exam->id}/copy-to-results", ['term_id' => $this->term->id, 'part' => 'Exam'])
            ->assertSessionHasErrors('part');
        $this->assertSame(0, SubjectScore::count());
    }

    public function test_only_staff_who_enter_marks_can_copy_and_only_in_their_school(): void
    {
        $this->actingAs($this->makeUser($this->school, 'accountant'));
        $this->post("/school/exams/{$this->exam->id}/copy-to-results", ['term_id' => $this->term->id, 'part' => 'Exam'])->assertForbidden();

        $this->actingAs($this->makeUser($this->makeSchool('Other School'), 'school-admin'));
        $this->post("/school/exams/{$this->exam->id}/copy-to-results", ['term_id' => $this->term->id, 'part' => 'Exam'])->assertNotFound();
        $this->assertSame(0, SubjectScore::count());
    }
}
