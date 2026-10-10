<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentScheme;
use App\Models\ReportCardExport;
use App\Models\ResultSheet;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\ReportCardExportService;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Security\SecurityTestCase;
use ZipArchive;

/** Bulk report card downloads: planned in steps, built into one ZIP, kept inside the school. */
class ReportCardExportTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private AcademicYear $year;
    private array $part;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->school = $this->makeSchool('Print School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);
        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2025/2026', 'start_date' => '2025-09-08', 'end_date' => '2026-07-24', 'is_current' => true]);
        $this->part = AssessmentScheme::where('is_default', true)->sole()->components->pluck('id', 'short_name')->all();
    }

    /** A class with first-term results for the given students (admission number => first name) */
    private function classWithResults(string $name, int $numeric, array $students): ResultSheet
    {
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => $name, 'numeric_name' => $numeric]);
        $sheet = ResultSheet::create(['school_id' => $this->school->id, 'term_id' => $this->year->terms()->orderBy('sequence')->first()->id, 'class_id' => $class->id]);
        if (! $students) {
            return $sheet;
        }

        $subject = Subject::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'name' => 'Mathematics', 'code' => 'M'.$class->id]);
        $scores = [];
        foreach ($students as $admission => $first) {
            $student = Student::create([
                'school_id' => $this->school->id, 'class_id' => $class->id, 'first_name' => $first, 'last_name' => 'Test', 'admission_no' => $admission,
                'gender' => 'female', 'status' => 'active', 'date_of_birth' => '2014-01-01',
            ]);
            foreach (['CA1' => 15, 'CA2' => 15, 'Exam' => 40] as $short => $score) {
                $scores[] = ['student_id' => $student->id, 'component_id' => $this->part[$short], 'score' => $score];
            }
        }
        $this->post("/school/results/{$sheet->id}/scores", ['subject_id' => $subject->id, 'scores' => $scores])->assertSessionHasNoErrors();

        return $sheet;
    }

    /** Ask for steps until the export is finished, like the page does */
    private function runSteps(ReportCardExport $export): ReportCardExport
    {
        for ($i = 0; $i < 50 && $export->fresh()->status === 'running'; $i++) {
            $this->post("/school/results/print/{$export->id}/step")->assertRedirect();
        }

        return $export->fresh();
    }

    private function zipNames(ReportCardExport $export): array
    {
        $zip = new ZipArchive();
        $this->assertTrue($zip->open(Storage::disk('local')->path($export->file_path)));
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();
        sort($names);

        return $names;
    }

    public function test_the_whole_school_in_one_zip_with_one_pdf_per_class(): void
    {
        $jss1 = $this->classWithResults('JSS 1', 7, ['A1' => 'Ada', 'A2' => 'Bola']);
        $jss2 = $this->classWithResults('JSS 2', 8, ['B1' => 'Chidi']);
        $empty = $this->classWithResults('JSS 3', 9, []);

        $this->post('/school/results/print', ['term_id' => $jss1->term_id, 'sheet_ids' => [$jss1->id, $jss2->id, $empty->id], 'type' => 'term', 'layout' => 'class'])
            ->assertSessionHasNoErrors();
        $export = ReportCardExport::sole();
        $this->assertSame(2, $export->totalUnits(), 'the class with no results is left out');

        $export = $this->runSteps($export);

        $this->assertSame('ready', $export->status);
        $this->assertSame(3, $export->cards);
        $this->assertSame(['JSS 1.pdf', 'JSS 2.pdf'], $this->zipNames($export));
        $this->get("/school/results/print/{$export->id}/download")->assertOk()->assertHeader('content-type', 'application/zip');
    }

    public function test_one_pdf_per_student_in_a_folder_per_class_in_small_steps(): void
    {
        $students = [];
        foreach (range(1, ReportCardExportService::STUDENTS_PER_STEP + 2) as $n) {
            $students["J{$n}"] = "Pupil{$n}";
        }
        $sheet = $this->classWithResults('JSS 1/A', 7, $students);

        $this->post('/school/results/print', ['term_id' => $sheet->term_id, 'sheet_ids' => [$sheet->id], 'type' => 'session', 'layout' => 'student']);
        $export = ReportCardExport::sole();
        $this->assertSame(2, $export->totalUnits(), 'twelve students are made in two steps');

        $export = $this->runSteps($export);

        $names = $this->zipNames($export);
        $this->assertCount(12, $names);
        // The slash in the class name can't be part of a folder name
        $this->assertContains('JSS 1-A/J1 Pupil1 Test.pdf', $names);
    }

    public function test_nothing_to_print_is_reported_and_other_schools_cannot_reach_a_download(): void
    {
        $empty = $this->classWithResults('JSS 3', 9, []);
        $this->post('/school/results/print', ['term_id' => $empty->term_id, 'sheet_ids' => [$empty->id], 'type' => 'term', 'layout' => 'class'])
            ->assertSessionHas('error');
        $this->assertSame('failed', ReportCardExport::sole()->status);

        $sheet = $this->classWithResults('JSS 1', 7, ['A1' => 'Ada']);
        $this->post('/school/results/print', ['term_id' => $sheet->term_id, 'sheet_ids' => [$sheet->id], 'type' => 'term', 'layout' => 'class']);
        $export = $this->runSteps(ReportCardExport::latest('id')->first());

        $this->actingAs($this->makeUser($this->makeSchool('Other School'), 'school-admin'));
        $this->get("/school/results/print/{$export->id}/download")->assertNotFound();
        $this->post("/school/results/print/{$export->id}/step")->assertNotFound();
        $this->post('/school/results/print', ['term_id' => $sheet->term_id, 'sheet_ids' => [$sheet->id], 'type' => 'term', 'layout' => 'class'])
            ->assertSessionHasErrors('term_id');
    }

    public function test_old_downloads_are_removed(): void
    {
        $sheet = $this->classWithResults('JSS 1', 7, ['A1' => 'Ada']);
        $this->post('/school/results/print', ['term_id' => $sheet->term_id, 'sheet_ids' => [$sheet->id], 'type' => 'term', 'layout' => 'class']);
        $old = $this->runSteps(ReportCardExport::sole());
        $old->forceFill(['created_at' => now()->subDays(ReportCardExportService::KEEP_DAYS + 1)])->save();

        $this->post('/school/results/print', ['term_id' => $sheet->term_id, 'sheet_ids' => [$sheet->id], 'type' => 'term', 'layout' => 'class']);

        $this->assertNull(ReportCardExport::find($old->id));
        Storage::disk('local')->assertMissing($old->file_path);
    }
}
