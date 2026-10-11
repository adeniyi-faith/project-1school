<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentScheme;
use App\Models\GradingScheme;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\Term;
use App\Support\SchoolDefaults;
use App\Support\SchoolTypes;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Security\SecurityTestCase;

/** A school picks its kind (Nigerian, British, American, Montessori) and gets matching classes, subjects, terms and grades, without losing anything */
class SchoolTypeTest extends SecurityTestCase
{
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('Type School');
        $this->actingAs($this->makeUser($this->school, 'school-admin'));
    }

    private function pick(string $type, array $parts = ['classes', 'subjects', 'terms', 'grading'])
    {
        return $this->post('/school/settings/school-type', ['type' => $type, 'parts' => $parts]);
    }

    public function test_a_british_school_gets_year_groups_subjects_terms_and_gcse_grades(): void
    {
        AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);

        $this->pick('british')->assertSessionHasNoErrors();

        $names = SchoolClass::orderBy('numeric_name')->pluck('name')->all();
        $this->assertSame(['Nursery', 'Reception', 'Year 1'], array_slice($names, 0, 3));
        $this->assertSame('Year 13', end($names));
        $this->assertSame('senior', SchoolClass::where('name', 'Year 12')->value('level'));
        $this->assertSame('primary', SchoolClass::where('name', 'Year 4')->value('level'));

        $this->assertTrue(Subject::where('name', 'Further Mathematics')->exists());
        $maths = Subject::where('name', 'Mathematics')->sole();
        $this->assertTrue(Subject::offeredIn(SchoolClass::where('name', 'Year 3')->value('id'))->whereKey($maths->id)->exists());
        $this->assertTrue(Subject::offeredIn(SchoolClass::where('name', 'Year 13')->value('id'))->whereKey($maths->id)->exists());

        $this->assertSame(['Autumn Term', 'Spring Term', 'Summer Term'], Term::orderBy('sequence')->pluck('name')->all());
        $this->assertSame('british', SchoolTypes::current($this->school->id));

        // GCSE scale for the school, A-level scale only for Years 12 and 13
        $default = GradingScheme::where('is_default', true)->sole();
        $this->assertSame('GCSE / IGCSE (9 – 1)', $default->name);
        $alevel = GradingScheme::where('name', 'A-level (A* – U)')->sole();
        $this->assertSame($alevel->id, SchoolClass::where('name', 'Year 12')->value('grading_scheme_id'));
        $this->assertNull(SchoolClass::where('name', 'Year 5')->value('grading_scheme_id'));
        $this->assertSame('Classwork + Assessment + Exam (20/20/60)', AssessmentScheme::where('is_default', true)->sole()->name);
    }

    public function test_applying_a_type_never_changes_what_the_school_already_has_and_twice_adds_nothing_more(): void
    {
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $existing = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'primary 1', 'numeric_name' => 1, 'level' => null]);
        $maths = Subject::create(['school_id' => $this->school->id, 'class_id' => $existing->id, 'name' => 'mathematics', 'type' => 'theory', 'full_marks' => 100, 'pass_marks' => 40]);
        // First Term already has results, so its name must stay
        $first = Term::where('academic_year_id', $year->id)->where('sequence', 1)->sole();
        DB::table('result_sheets')->insert(['school_id' => $this->school->id, 'term_id' => $first->id, 'class_id' => $existing->id, 'status' => 'draft', 'version' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $this->pick('nigerian')->assertSessionHasNoErrors();

        $this->assertSame(1, SchoolClass::whereRaw('lower(name) = ?', ['primary 1'])->count());
        $this->assertSame('primary 1', $existing->fresh()->name);
        $this->assertSame(1, Subject::whereRaw('lower(name) = ?', ['mathematics'])->count());
        $this->assertTrue(Subject::offeredIn($existing->id)->whereKey($maths->id)->exists());
        $this->assertSame('First Term', $first->fresh()->name);

        $classes = SchoolClass::count();
        $subjects = Subject::count();
        $this->pick('nigerian')->assertSessionHasNoErrors();
        $this->assertSame($classes, SchoolClass::count());
        $this->assertSame($subjects, Subject::count());
    }

    public function test_the_american_and_montessori_types_set_their_own_terms_and_grading(): void
    {
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);

        $this->pick('american')->assertSessionHasNoErrors();
        $this->assertSame(['Quarter 1', 'Quarter 2', 'Quarter 3', 'Quarter 4'], Term::where('academic_year_id', $year->id)->orderBy('sequence')->pluck('name')->all());
        $this->assertTrue(SchoolClass::where('name', 'Kindergarten')->exists());
        $this->assertTrue(Subject::where('name', 'Algebra I')->exists());
        $this->assertSame('US letter grades (A – F, GPA 4.0)', GradingScheme::where('is_default', true)->sole()->name);

        // A school year made later uses the same term names
        $next = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2027/2028', 'start_date' => '2027-09-06', 'end_date' => '2028-07-21', 'is_current' => false]);
        $this->assertSame(['Quarter 1', 'Quarter 2', 'Quarter 3', 'Quarter 4'], Term::where('academic_year_id', $next->id)->orderBy('sequence')->pluck('name')->all());

        $this->pick('montessori', ['classes', 'grading'])->assertSessionHasNoErrors();
        $this->assertTrue(SchoolClass::where('name', "Children's House")->exists());
        $this->assertSame('Montessori progress (Introduced, Practising, Mastered)', GradingScheme::where('is_default', true)->sole()->name);
        $this->assertSame('Teacher observation (100)', AssessmentScheme::where('is_default', true)->sole()->name);
        // Terms and subjects were not ticked, so the quarters stay and Practical Life was not added
        $this->assertSame('Quarter 1', Term::where('academic_year_id', $year->id)->where('sequence', 1)->value('name'));
        $this->assertFalse(Subject::where('name', 'Practical Life')->exists());
    }

    public function test_choosing_something_else_adds_nothing_and_only_settings_editors_can_choose(): void
    {
        $this->pick('custom', [])->assertSessionHasNoErrors();
        $this->assertSame(0, SchoolClass::count());
        $this->assertSame('custom', SchoolSetting::get($this->school->id, 'school_type'));

        $this->pick('martian')->assertSessionHasErrors('type');

        $teacher = $this->makeUser($this->school, 'teacher');
        $this->actingAs($teacher)->get('/school/settings/school-type')->assertForbidden();
        $this->post('/school/settings/school-type', ['type' => 'british'])->assertForbidden();
    }

    public function test_the_page_opens_for_a_school_admin_and_every_preset_adds_up(): void
    {
        $this->get('/school/settings/school-type', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.types.1.key', 'british');

        foreach (SchoolDefaults::ASSESSMENT_PRESETS as $key => $p) {
            $this->assertSame(100, array_sum(array_column($p['components'], 2)), $key);
        }
        foreach (SchoolDefaults::GRADING_PRESETS as $key => $p) {
            $bands = $p['bands'];
            $this->assertSame(0, min(array_column($bands, 1)), $key);
            $this->assertSame(100, max(array_column($bands, 2)), $key);
        }
    }
}
