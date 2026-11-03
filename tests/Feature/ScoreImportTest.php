<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentScheme;
use App\Models\ResultSheet;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectScore;
use App\Models\User;
use App\Support\SimpleXlsx;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Security\SecurityTestCase;

/** Scores for a whole class and subject uploaded from an Excel sheet */
class ScoreImportTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private ResultSheet $sheet;
    private Subject $maths;
    private Student $ada;
    private Student $bayo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('Import School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);

        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1']);
        $this->maths = Subject::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'name' => 'Mathematics', 'code' => 'MTH', 'full_marks' => 100]);
        foreach (['ada' => 'Ada', 'bayo' => 'Bayo'] as $key => $name) {
            $this->{$key} = Student::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'first_name' => $name, 'last_name' => 'Test', 'gender' => 'female', 'status' => 'active', 'admission_no' => 'ADM-'.strtoupper($name)]);
        }
        $this->sheet = ResultSheet::create(['school_id' => $this->school->id, 'term_id' => $year->terms()->first()->id, 'class_id' => $class->id]);
    }

    private function part(string $short): int
    {
        return AssessmentScheme::where('is_default', true)->sole()->components->firstWhere('short_name', $short)->id;
    }

    private function score(Student $s, string $short): ?float
    {
        return SubjectScore::where('student_id', $s->id)->where('subject_id', $this->maths->id)->where('assessment_component_id', $this->part($short))->value('score');
    }

    private function upload(array $rows): \Illuminate\Testing\TestResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'sc').'.xlsx';
        file_put_contents($path, (new SimpleXlsx())->sheet('Scores', $rows)->bytes());

        return $this->post("/school/results/{$this->sheet->id}/scores/import", [
            'subject_id' => $this->maths->id,
            'file' => new UploadedFile($path, 'scores.xlsx', null, null, true),
        ]);
    }

    public function test_template_lists_the_class_with_a_column_per_score_part(): void
    {
        $res = $this->get("/school/results/{$this->sheet->id}/scores/template?subject_id={$this->maths->id}")->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'tp').'.xlsx';
        file_put_contents($path, $res->getContent());
        $rows = \App\Support\SimpleXlsxReader::rows($path);

        $this->assertSame(['Admission No', 'Student'], array_slice($rows[0], 0, 2));
        $this->assertStringStartsWith('CA1 (', $rows[0][2]);
        $this->assertCount(3, $rows);                          // heading + two students
        $this->assertSame('ADM-ADA', $rows[1][0]);
    }

    public function test_filled_sheet_saves_scores_and_works_out_results(): void
    {
        $this->upload([
            ['Admission No', 'Student', 'CA1 (20)', 'Exam (60)'],
            ['ADM-ADA', 'Ada Test', '18', '50.5'],
            ['adm-bayo', 'Bayo Test', '10', ''],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(18.0, $this->score($this->ada, 'CA1'));
        $this->assertSame(50.5, $this->score($this->ada, 'Exam'));
        $this->assertSame(10.0, $this->score($this->bayo, 'CA1'));
        $this->assertNull($this->score($this->bayo, 'Exam'));
        $this->assertSame(2, \App\Models\TermResult::where('result_sheet_id', $this->sheet->id)->count());
    }

    public function test_empty_cells_keep_scores_already_saved(): void
    {
        SubjectScore::create(['school_id' => $this->school->id, 'result_sheet_id' => $this->sheet->id, 'student_id' => $this->ada->id, 'subject_id' => $this->maths->id, 'assessment_component_id' => $this->part('CA1'), 'score' => 15]);

        $this->upload([['Admission No', 'CA1', 'Exam'], ['ADM-ADA', '', '40']])->assertSessionHasNoErrors();

        $this->assertSame(15.0, $this->score($this->ada, 'CA1'));
        $this->assertSame(40.0, $this->score($this->ada, 'Exam'));
    }

    public function test_one_bad_row_saves_nothing_and_lists_every_problem(): void
    {
        $this->upload([
            ['Admission No', 'CA1', 'Exam'],
            ['ADM-ADA', '19', '40'],
            ['ADM-BAYO', '25', 'abc'],      // over the maximum, not a number
            ['ADM-NOPE', '5', '5'],         // not in this class
            ['ADM-ADA', '1', '1'],          // twice
        ])->assertSessionHasErrors(['import.0', 'import.1', 'import.2', 'import.3'])->assertSessionHas('error');

        $this->assertSame(0, SubjectScore::count());
    }

    public function test_file_without_the_admission_heading_or_matching_parts_is_refused(): void
    {
        $this->upload([['Name', 'CA1'], ['Ada', '5']])->assertSessionHasErrors('import.0');
        $this->upload([['Admission No', 'Quiz'], ['ADM-ADA', '5']])->assertSessionHasErrors('import.0');
        $this->assertSame(0, SubjectScore::count());
    }

    public function test_submitted_results_cannot_be_uploaded_into(): void
    {
        $this->sheet->update(['status' => 'submitted']);

        $this->upload([['Admission No', 'CA1'], ['ADM-ADA', '5']])->assertSessionHasErrors('scores');
        $this->assertSame(0, SubjectScore::count());
    }

    public function test_only_staff_who_enter_marks_can_upload_and_only_in_their_school(): void
    {
        $this->actingAs($this->makeUser($this->school, 'accountant'));
        $this->upload([['Admission No', 'CA1'], ['ADM-ADA', '5']])->assertForbidden();
        $this->get("/school/results/{$this->sheet->id}/scores/template?subject_id={$this->maths->id}")->assertForbidden();

        $this->actingAs($this->makeUser($this->makeSchool('Other School'), 'school-admin'));
        $this->upload([['Admission No', 'CA1'], ['ADM-ADA', '5']])->assertNotFound();
        $this->assertSame(0, SubjectScore::count());
    }
}
