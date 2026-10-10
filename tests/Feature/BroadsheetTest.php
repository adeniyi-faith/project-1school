<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentScheme;
use App\Models\ResultSheet;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\BroadsheetService;
use Tests\Feature\Security\SecurityTestCase;
use ZipArchive;

/** Broadsheets: the numbers, the Excel and PDF files, and the whole-school workbook. */
class BroadsheetTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private AcademicYear $year;
    private SchoolClass $class;
    private Subject $maths;
    private Subject $english;
    private Student $ada;
    private Student $bayo;
    private array $part;
    /** @var ResultSheet[] */
    private array $sheets = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = $this->makeSchool('Broad School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);
        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2025/2026', 'start_date' => '2025-09-08', 'end_date' => '2026-07-24', 'is_current' => true]);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        $this->maths = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'Mathematics', 'code' => 'MTH']);
        $this->english = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'English', 'code' => 'ENG']);
        $this->part = AssessmentScheme::where('is_default', true)->sole()->components->pluck('id', 'short_name')->all();
        $this->ada = $this->student('Ada', 'female');
        $this->bayo = $this->student('Bayo', 'male');
        foreach ($this->year->terms()->orderBy('sequence')->get() as $term) {
            $this->sheets[] = ResultSheet::create(['school_id' => $this->school->id, 'term_id' => $term->id, 'class_id' => $this->class->id]);
        }

        // Term 1. Ada: maths 85, English 70 (avg 77.5). Bayo: maths 30, English 70 (avg 50).
        $this->enter($this->sheets[0], $this->maths, [[$this->ada, 18, 17, 50], [$this->bayo, 5, 5, 20]]);
        $this->enter($this->sheets[0], $this->english, [[$this->ada, 15, 15, 40], [$this->bayo, 12, 14, 44]]);
    }

    private function student(string $first, string $gender): Student
    {
        return Student::create([
            'school_id' => $this->school->id, 'class_id' => $this->class->id, 'first_name' => $first, 'last_name' => 'Test',
            'gender' => $gender, 'status' => 'active', 'date_of_birth' => '2014-01-01',
        ]);
    }

    private function enter(ResultSheet $sheet, Subject $subject, array $rows): void
    {
        $scores = [];
        foreach ($rows as [$student, $ca1, $ca2, $exam]) {
            foreach (['CA1' => $ca1, 'CA2' => $ca2, 'Exam' => $exam] as $short => $score) {
                $scores[] = ['student_id' => $student->id, 'component_id' => $this->part[$short], 'score' => $score];
            }
        }
        $this->post("/school/results/{$sheet->id}/scores", ['subject_id' => $subject->id, 'scores' => $scores])->assertSessionHasNoErrors();
    }

    /** The text of every cell in one worksheet of an Excel file */
    private function xlsxCells(string $bytes, int $sheet = 1): array
    {
        $path = tempnam(sys_get_temp_dir(), 'bs');
        file_put_contents($path, $bytes);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));
        $xml = simplexml_load_string($zip->getFromName("xl/worksheets/sheet{$sheet}.xml"));
        $zip->close();
        unlink($path);

        $cells = [];
        foreach ($xml->sheetData->row as $row) {
            foreach ($row->c as $c) {
                $cells[(string) $c['r']] = isset($c->is) ? (string) $c->is->t : (string) $c->v;
            }
        }

        return $cells;
    }

    public function test_the_term_broadsheet_ranks_students_and_sums_up_each_subject(): void
    {
        $b = app(BroadsheetService::class)->term($this->sheets[0]->fresh());

        $this->assertSame(['English', 'Mathematics'], array_column($b['subjects'], 'name'));
        $this->assertSame(['CA1', 'CA2', 'Exam'], $b['subjects'][1]['parts']);
        $this->assertSame(['Ada Test', 'Bayo Test'], array_column($b['rows'], 'name'), 'best first');
        [$ada, $bayo] = $b['rows'];
        $this->assertEquals([1, 155, 77.5], [$ada['position'], $ada['total'], $ada['average']]);
        $this->assertEquals(['CA1' => 18, 'CA2' => 17, 'Exam' => 50], $ada['scores'][$this->maths->id]['parts']);
        $this->assertSame('M', $bayo['gender'], 'sex is shown as one letter');
        $this->assertSame(array_sum($ada['grade_counts']), 2, 'each subject grade is counted once');

        // Maths: 85 and 30 → average 57.5, one of two passed the 40% mark
        $this->assertEquals(['entries' => 2, 'average' => 57.5, 'highest' => 85, 'lowest' => 30, 'passed' => 1, 'pass_rate' => 50.0], $b['footer'][$this->maths->id]);
        $this->assertEquals(2, $b['overall']['passed'], 'both averages are above the pass mark');
    }

    public function test_the_full_year_broadsheet_averages_the_terms(): void
    {
        $this->enter($this->sheets[1], $this->maths, [[$this->ada, 20, 20, 50], [$this->bayo, 10, 10, 30]]);
        $this->enter($this->sheets[1], $this->english, [[$this->ada, 10, 10, 40], [$this->bayo, 20, 20, 50]]);

        $b = app(BroadsheetService::class)->session($this->year, $this->class->id);

        $this->assertSame(['First Term', 'Second Term'], $b['term_names']);
        $ada = collect($b['rows'])->firstWhere('name', 'Ada Test');
        $this->assertEquals([85, 90], $ada['scores'][$this->maths->id]['terms']);
        $this->assertEquals(87.5, $ada['scores'][$this->maths->id]['average']);
        // Ada: 77.5 then 75 → 76.25. Bayo: 50 then 70 → 60. Maths: Ada 87.5, Bayo (30 + 50) / 2 = 40
        $this->assertEquals(76.25, $ada['average']);
        $this->assertSame(1, $ada['position']);
        $this->assertEquals(['entries' => 2, 'average' => 63.75, 'highest' => 87.5, 'lowest' => 40, 'passed' => 2, 'pass_rate' => 100.0], $b['footer'][$this->maths->id]);
    }

    public function test_class_broadsheets_download_as_excel_and_pdf(): void
    {
        $base = "/school/results/{$this->sheets[0]->id}/broadsheet";

        $excel = $this->get("{$base}?format=xlsx")->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $cells = $this->xlsxCells($excel->getContent());
        $this->assertSame('Pos', $cells['A1']);
        $this->assertSame('English', $cells['E1']);
        $this->assertSame('Ada Test', $cells['B3']);
        $this->assertContains('155', $cells, 'numbers are stored as numbers');

        $this->get($base)->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get("{$base}/session")->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get("{$base}/session?format=xlsx")->assertOk();
    }

    public function test_the_whole_school_workbook_has_a_tab_per_class_with_results(): void
    {
        $jss2 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 2', 'numeric_name' => 8]);
        $maths2 = Subject::create(['school_id' => $this->school->id, 'class_id' => $jss2->id, 'name' => 'Mathematics', 'code' => 'MTH2']);
        $chidi = Student::create(['school_id' => $this->school->id, 'class_id' => $jss2->id, 'first_name' => 'Chidi', 'last_name' => 'Test', 'gender' => 'male', 'status' => 'active']);
        $sheet2 = ResultSheet::create(['school_id' => $this->school->id, 'term_id' => $this->sheets[0]->term_id, 'class_id' => $jss2->id]);
        $this->enter($sheet2, $maths2, [[$chidi, 10, 10, 30]]);
        SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 3', 'numeric_name' => 9]);

        $response = $this->get("/school/results/broadsheets?term_id={$this->sheets[0]->term_id}")->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'bs');
        file_put_contents($path, $response->getContent());
        $zip = new ZipArchive();
        $zip->open($path);
        $workbook = $zip->getFromName('xl/workbook.xml');
        $zip->close();
        unlink($path);
        $this->assertStringContainsString('name="JSS 1"', $workbook);
        $this->assertStringContainsString('name="JSS 2"', $workbook);
        $this->assertStringNotContainsString('JSS 3', $workbook, 'classes with no results are left out');
    }

    public function test_other_schools_cannot_see_a_broadsheet(): void
    {
        $this->actingAs($this->makeUser($this->makeSchool('Other'), 'school-admin'));

        $this->get("/school/results/{$this->sheets[0]->id}/broadsheet")->assertNotFound();
        $this->get("/school/results/broadsheets?term_id={$this->sheets[0]->term_id}")->assertSessionHasErrors('term_id');
    }
}
