<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentScheme;
use App\Models\ClassSubject;
use App\Models\ResultSheet;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Security\SecurityTestCase;

/** One subject offered by many classes, departments on senior arms, and the merge of old per-class copies */
class SharedSubjectsTest extends SecurityTestCase
{
    private School $school;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('Shared School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);
    }

    private function makeClass(string $name, int $order, ?string $level = null): SchoolClass
    {
        return SchoolClass::create(['school_id' => $this->school->id, 'name' => $name, 'numeric_name' => $order, 'level' => $level]);
    }

    public function test_one_subject_is_offered_by_many_classes_and_a_second_with_the_same_name_is_refused(): void
    {
        $p1 = $this->makeClass('Primary 1', 1, 'primary');
        $p2 = $this->makeClass('Primary 2', 2, 'primary');
        $jss = $this->makeClass('JSS 1', 7, 'junior');

        $this->post('/school/subjects', [
            'name' => '  Mathematics ', 'code' => 'MTH', 'type' => 'theory', 'full_marks' => 100, 'pass_marks' => 40,
            'classes' => [['id' => $p1->id], ['id' => $p2->id], ['id' => $jss->id]],
        ])->assertSessionHasNoErrors();

        $maths = Subject::where('name', 'Mathematics')->sole();
        $this->assertEqualsCanonicalizing([$p1->id, $p2->id, $jss->id], $maths->classLines()->pluck('class_id')->all());
        $this->assertTrue(Subject::offeredIn($p2->id)->whereKey($maths->id)->exists());

        $this->post('/school/subjects', ['name' => 'mathematics', 'type' => 'theory', 'classes' => [['id' => $p1->id]]])
            ->assertSessionHasErrors('name');

        // Unticking a class takes it off that class only
        $this->put("/school/subjects/{$maths->id}", [
            'name' => 'Mathematics', 'code' => 'MTH', 'type' => 'theory', 'full_marks' => 100, 'pass_marks' => 40,
            'classes' => [['id' => $p1->id], ['id' => $jss->id]],
        ])->assertSessionHasNoErrors();
        $this->assertFalse(Subject::offeredIn($p2->id)->whereKey($maths->id)->exists());
        $this->assertTrue(Subject::offeredIn($jss->id)->whereKey($maths->id)->exists());
    }

    public function test_arms_are_added_in_one_go_and_a_department_keeps_score_entry_to_its_students(): void
    {
        $ss1 = $this->makeClass('SS 1', 10, 'senior');
        $this->post('/school/sections', ['class_id' => $ss1->id, 'names' => 'Gold, Silver'])->assertSessionHasNoErrors();
        $this->post('/school/sections', ['class_id' => $ss1->id, 'names' => 'Science', 'department' => 'Science'])->assertSessionHasNoErrors();
        $this->post('/school/sections', ['class_id' => $ss1->id, 'names' => 'gold'])->assertSessionHasErrors('names');
        $this->assertSame(['Gold', 'Science', 'Silver'], Section::where('class_id', $ss1->id)->orderBy('name')->pluck('name')->all());
        $science = Section::where('name', 'Science')->sole();
        $gold = Section::where('name', 'Gold')->sole();
        $this->assertSame('Science', $science->department);

        $this->post('/school/subjects', ['name' => 'Physics', 'type' => 'theory', 'classes' => [['id' => $ss1->id, 'departments' => ['Science']]]])->assertSessionHasNoErrors();
        $this->post('/school/subjects', ['name' => 'English Language', 'type' => 'theory', 'classes' => [['id' => $ss1->id]]])->assertSessionHasNoErrors();
        $physics = Subject::where('name', 'Physics')->sole();
        $english = Subject::where('name', 'English Language')->sole();

        $make = fn (string $first, Section $arm) => Student::create([
            'school_id' => $this->school->id, 'class_id' => $ss1->id, 'section_id' => $arm->id, 'first_name' => $first, 'last_name' => 'Test',
            'admission_no' => 'ADM-'.$first, 'gender' => 'male', 'status' => 'active',
        ]);
        $ada = $make('Ada', $science);
        $bayo = $make('Bayo', $gold);

        AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $this->get('/school/results', ['X-Inertia' => 'true'])->assertOk();
        $sheet = ResultSheet::where('class_id', $ss1->id)->firstOrFail();

        $names = fn (Subject $s) => collect($this->get("/school/results/{$sheet->id}?subject_id={$s->id}", ['X-Inertia' => 'true'])->assertOk()->json('props.students'))->pluck('name')->all();
        $this->assertSame(['Ada Test'], $names($physics));
        $this->assertSame(['Ada Test', 'Bayo Test'], $names($english));

        $exam = AssessmentScheme::forSubject($physics, $ss1->id)->components->firstWhere('short_name', 'Exam');
        $this->post("/school/results/{$sheet->id}/scores", ['subject_id' => $physics->id, 'scores' => [['student_id' => $bayo->id, 'component_id' => $exam->id, 'score' => 50]]])
            ->assertSessionHasErrors('scores.0.score');
        $this->post("/school/results/{$sheet->id}/scores", ['subject_id' => $physics->id, 'scores' => [['student_id' => $ada->id, 'component_id' => $exam->id, 'score' => 50]]])
            ->assertSessionHasNoErrors();
    }

    public function test_a_class_can_keep_its_own_score_setup_for_a_shared_subject(): void
    {
        $p1 = $this->makeClass('Primary 1', 1, 'primary');
        $p2 = $this->makeClass('Primary 2', 2, 'primary');
        $this->post('/school/subjects', ['name' => 'Mathematics', 'type' => 'theory', 'classes' => [['id' => $p1->id], ['id' => $p2->id]]])->assertSessionHasNoErrors();
        $maths = Subject::where('name', 'Mathematics')->sole();
        $default = AssessmentScheme::where('is_default', true)->sole();
        $own = AssessmentScheme::create(['school_id' => $this->school->id, 'name' => 'Primary 2 maths']);

        ClassSubject::where('class_id', $p2->id)->where('subject_id', $maths->id)->update(['assessment_scheme_id' => $own->id]);

        $this->assertSame($own->id, AssessmentScheme::forSubject($maths, $p2->id)->id);
        $this->assertSame($default->id, AssessmentScheme::forSubject($maths, $p1->id)->id);
    }

    public function test_the_upgrade_merges_same_named_copies_and_can_be_undone(): void
    {
        $migration = require database_path('migrations/2026_11_03_000001_share_subjects_across_classes.php');
        $migration->down();

        $sid = $this->school->id;
        $now = now();
        $class = fn (string $name, int $n) => DB::table('classes')->insertGetId(['school_id' => $sid, 'name' => $name, 'numeric_name' => $n, 'capacity' => 40, 'created_at' => $now, 'updated_at' => $now]);
        $p1 = $class('Primary 1', 1);
        $p2 = $class('Primary 2', 2);
        $ss1 = $class('SSS 1', 10);
        $subject = fn (int $classId, string $name, ?int $scheme = null) => DB::table('subjects')->insertGetId([
            'school_id' => $sid, 'class_id' => $classId, 'name' => $name, 'type' => 'theory', 'full_marks' => 100, 'pass_marks' => 40,
            'assessment_scheme_id' => $scheme, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $own = AssessmentScheme::create(['school_id' => $sid, 'name' => 'Primary 2 maths'])->id;
        $m1 = $subject($p1, 'Mathematics');
        $m2 = $subject($p2, 'mathematics ', $own);
        $twin = $subject($p2, 'Mathematics'); // a second copy in the same class stays apart
        $eng = $subject($p1, 'English');
        $syllabus = DB::table('syllabi')->insertGetId(['school_id' => $sid, 'class_id' => $p2, 'subject_id' => $m2, 'academic_year' => '2026', 'title' => 'P2 maths', 'created_at' => $now, 'updated_at' => $now]);

        $migration->up();

        $this->assertSame('primary', DB::table('classes')->where('id', $p1)->value('level'));
        $this->assertSame('senior', DB::table('classes')->where('id', $ss1)->value('level'));
        $this->assertNotNull(DB::table('subjects')->where('id', $m2)->value('deleted_at'));
        $this->assertNull(DB::table('subjects')->where('id', $twin)->value('deleted_at'));
        $this->assertNull(DB::table('subjects')->where('id', $eng)->value('deleted_at'));
        $this->assertSame($m1, (int) DB::table('syllabi')->where('id', $syllabus)->value('subject_id'));
        $this->assertEqualsCanonicalizing([$p1, $p2], DB::table('class_subjects')->where('subject_id', $m1)->pluck('class_id')->map(fn ($c) => (int) $c)->all());
        // Primary 2 keeps its own score setup on its line; Primary 1 uses the school's default
        $this->assertSame($own, (int) DB::table('class_subjects')->where('subject_id', $m1)->where('class_id', $p2)->value('assessment_scheme_id'));
        $this->assertNull(DB::table('class_subjects')->where('subject_id', $m1)->where('class_id', $p1)->value('assessment_scheme_id'));

        $migration->down();

        $this->assertNull(DB::table('subjects')->where('id', $m2)->value('deleted_at'));
        $this->assertSame($m2, (int) DB::table('syllabi')->where('id', $syllabus)->value('subject_id'));
        $this->assertSame($own, (int) DB::table('subjects')->where('id', $m2)->value('assessment_scheme_id'));

        $migration->up(); // leave the database as the other tests expect
    }

    public function test_the_level_is_guessed_from_common_class_names(): void
    {
        $migration = require database_path('migrations/2026_11_03_000001_share_subjects_across_classes.php');
        $expect = ['Creche' => 'early_years', 'Nursery 2' => 'early_years', 'KG 1' => 'early_years', 'Primary 4' => 'primary', 'Basic 5' => 'primary',
            'JSS 1' => 'junior', 'JS2' => 'junior', 'Basic 8' => 'junior', 'SS 3' => 'senior', 'SSS 1' => 'senior', 'Year 11' => 'senior', 'Grade 2' => 'primary', 'Class Lions' => null];
        foreach ($expect as $name => $level) {
            $this->assertSame($level, $migration::guessLevel($name), $name);
        }
        foreach (['Science' => 'Science', 'SS 1 Arts' => 'Arts', 'Commercial' => 'Commercial', 'A' => null, 'Emerald' => null, 'Partners' => null] as $name => $department) {
            $this->assertSame($department, $migration::guessDepartment($name), $name);
        }
    }
}
