<?php

namespace Tests\Feature\Correctness;

use App\Models\AcademicYear;
use App\Models\AssessmentScheme;
use App\Models\BehaviourTrait;
use App\Models\ClassSubject;
use App\Models\ResultSheet;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectScore;
use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;

/** Who may change scores: teachers only their own subjects in their own classes; everyone else with the permission, all. */
class ScoreAccessTest extends SecurityTestCase
{
    private $school;
    private SchoolClass $class;
    private Subject $maths;
    private Subject $english;
    private User $mathsTeacher;
    private User $classTeacher;
    private Student $ada;
    private ResultSheet $sheet;
    private array $part;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('Access School');
        $admin = $this->makeUser($this->school, 'school-admin');
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1']);
        $this->maths = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'Mathematics', 'code' => 'MTH']);
        $this->english = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'English', 'code' => 'ENG']);
        $this->part = AssessmentScheme::where('is_default', true)->sole()->components->pluck('id', 'short_name')->all();
        $this->ada = Student::withoutGlobalScopes()->create([
            'school_id' => $this->school->id, 'class_id' => $this->class->id, 'first_name' => 'Ada', 'last_name' => 'Test',
            'admission_no' => 'A1', 'gender' => 'female', 'status' => 'active',
        ]);
        BehaviourTrait::create(['school_id' => $this->school->id, 'name' => 'Punctuality', 'domain' => 'affective', 'sort_order' => 1]);

        $this->mathsTeacher = $this->makeUser($this->school, 'teacher');
        $this->classTeacher = $this->makeUser($this->school, 'teacher');
        $mt = $this->staffFor($this->mathsTeacher, 'E1');
        $ct = $this->staffFor($this->classTeacher, 'E2');
        ClassSubject::where('class_id', $this->class->id)->where('subject_id', $this->maths->id)->update(['teacher_id' => $mt->id]);
        $this->class->update(['class_teacher_staff_id' => $ct->id]);

        $this->actingAs($admin);
        $this->get('/school/results', ['X-Inertia' => 'true'])->assertOk();
        $this->sheet = ResultSheet::where('term_id', $year->terms()->first()->id)->where('class_id', $this->class->id)->sole();
    }

    private function staffFor(User $user, string $emp): Staff
    {
        return Staff::withoutGlobalScopes()->create([
            'school_id' => $this->school->id, 'user_id' => $user->id, 'emp_id' => $emp, 'first_name' => 'T',
            'last_name' => $emp, 'status' => 'active', 'salary_type' => 'fixed', 'gender' => 'male', 'email' => $user->email,
        ]);
    }

    private function scoresFor(Subject $subject): array
    {
        return ['subject_id' => $subject->id, 'scores' => [['student_id' => $this->ada->id, 'component_id' => $this->part['CA1'], 'score' => 10]]];
    }

    public function test_a_teacher_can_enter_scores_only_for_the_subject_they_teach(): void
    {
        $this->actingAs($this->mathsTeacher);
        $this->post("/school/results/{$this->sheet->id}/scores", $this->scoresFor($this->maths))->assertSessionHasNoErrors();
        $this->assertSame(1, SubjectScore::where('subject_id', $this->maths->id)->count());

        $this->post("/school/results/{$this->sheet->id}/scores", $this->scoresFor($this->english))->assertForbidden();
        $this->assertSame(0, SubjectScore::where('subject_id', $this->english->id)->count());
    }

    public function test_a_class_teacher_can_look_at_every_subject_but_not_change_one_they_do_not_teach(): void
    {
        $this->actingAs($this->classTeacher);
        $this->get("/school/results/{$this->sheet->id}?subject_id={$this->english->id}", ['X-Inertia' => 'true'])
            ->assertOk()->assertJsonPath('props.canEnter', false)->assertJsonPath('props.canRate', true);
        $this->post("/school/results/{$this->sheet->id}/scores", $this->scoresFor($this->english))->assertForbidden();
    }

    public function test_only_the_class_teacher_or_an_admin_rates_behaviour(): void
    {
        $trait = BehaviourTrait::first();
        $ratings = ['ratings' => [['student_id' => $this->ada->id, 'trait_id' => $trait->id, 'rating' => 4]]];

        $this->actingAs($this->mathsTeacher);
        $this->post("/school/results/{$this->sheet->id}/ratings", $ratings)->assertForbidden();

        $this->actingAs($this->classTeacher);
        $this->post("/school/results/{$this->sheet->id}/ratings", $ratings)->assertSessionHasNoErrors();
    }

    public function test_admins_and_principals_change_everything(): void
    {
        foreach (['school-admin', 'principal'] as $role) {
            $this->actingAs($this->makeUser($this->school, $role));
            $this->post("/school/results/{$this->sheet->id}/scores", $this->scoresFor($this->english))->assertSessionHasNoErrors();
        }
    }

    public function test_the_screen_says_view_only_for_a_subject_the_teacher_does_not_teach(): void
    {
        $this->actingAs($this->mathsTeacher);
        $this->get("/school/results/{$this->sheet->id}?subject_id={$this->english->id}", ['X-Inertia' => 'true'])
            ->assertJsonPath('props.canEnter', false)
            ->assertJsonPath('props.viewOnlyWhy', fn ($why) => str_contains($why, 'View only'));
        $this->get("/school/results/{$this->sheet->id}?subject_id={$this->maths->id}", ['X-Inertia' => 'true'])
            ->assertJsonPath('props.canEnter', true)->assertJsonPath('props.viewOnlyWhy', null);
    }
}
