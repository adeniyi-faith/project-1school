<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentScheme;
use App\Models\ReportCardCommentBank;
use App\Models\ReportCardDesign;
use App\Models\ReportCardRemark;
use App\Models\ResultSheet;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;

/** The comment bank: saved comments by average range, filling one class, and one signer across many classes. */
class CommentBankTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private AcademicYear $year;
    private array $part;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = $this->makeSchool('Bank School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);
        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2025/2026', 'start_date' => '2025-09-08', 'end_date' => '2026-07-24', 'is_current' => true]);
        $this->part = AssessmentScheme::where('is_default', true)->sole()->components->pluck('id', 'short_name')->all();
    }

    /** A class with results for the first term: each [first name, gender, CA1, CA2, Exam] in one subject */
    private function classWithResults(string $name, array $students): ResultSheet
    {
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => $name, 'numeric_name' => 7]);
        $subject = Subject::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'name' => 'Mathematics', 'code' => 'MTH'.$class->id]);
        $sheet = ResultSheet::create(['school_id' => $this->school->id, 'term_id' => $this->year->terms()->orderBy('sequence')->first()->id, 'class_id' => $class->id]);

        $scores = [];
        foreach ($students as [$first, $gender, $ca1, $ca2, $exam]) {
            $student = Student::create([
                'school_id' => $this->school->id, 'class_id' => $class->id, 'first_name' => $first, 'last_name' => 'Test',
                'gender' => $gender, 'status' => 'active', 'date_of_birth' => '2014-01-01',
            ]);
            foreach (['CA1' => $ca1, 'CA2' => $ca2, 'Exam' => $exam] as $short => $score) {
                $scores[] = ['student_id' => $student->id, 'component_id' => $this->part[$short], 'score' => $score];
            }
        }
        $this->post("/school/results/{$sheet->id}/scores", ['subject_id' => $subject->id, 'scores' => $scores])->assertSessionHasNoErrors();

        return $sheet;
    }

    private function signer(ResultSheet $sheet, string $label)
    {
        return ReportCardDesign::forClass($this->school->id, $sheet->class_id)->signers()->where('label', $label)->sole();
    }

    private function remark(ResultSheet $sheet, string $first, $signer): ?string
    {
        $student = Student::where('first_name', $first)->where('class_id', $sheet->class_id)->sole();

        return ReportCardRemark::where('result_sheet_id', $sheet->id)->where('student_id', $student->id)->where('report_card_signer_id', $signer->id)->value('comment');
    }

    private function bank(string $writer, float $min, float $max, string $comment): void
    {
        $this->post('/school/results/comment-bank', ['writer_permission' => $writer, 'min_average' => $min, 'max_average' => $max, 'comment' => $comment])
            ->assertSessionHasNoErrors();
    }

    public function test_filling_a_class_picks_the_comment_for_each_average_and_fills_the_blanks(): void
    {
        // Ada 85, Bayo 45
        $sheet = $this->classWithResults('JSS 1', [['Ada', 'female', 18, 17, 50], ['Bayo', 'male', 10, 5, 30]]);
        $this->bank('marks.entry', 70, 100, '{name} did very well. {he_she} came {position} with {average}.');
        $this->bank('marks.entry', 0, 69.99, '{name} must work harder. Help {him_her} with {his_her} revision.');
        $teacher = $this->signer($sheet, 'Class Teacher');

        $this->post("/school/results/{$sheet->id}/comments/fill", ['signer_id' => $teacher->id])->assertSessionHasNoErrors();

        $this->assertSame('Ada did very well. She came 1st of 2 with 85%.', $this->remark($sheet, 'Ada', $teacher));
        $this->assertSame('Bayo must work harder. Help him with his revision.', $this->remark($sheet, 'Bayo', $teacher));
    }

    public function test_filling_keeps_comments_already_written_unless_told_to_replace_them(): void
    {
        $sheet = $this->classWithResults('JSS 1', [['Ada', 'female', 18, 17, 50]]);
        $this->bank('marks.entry', 0, 100, 'From the bank.');
        $teacher = $this->signer($sheet, 'Class Teacher');
        $ada = Student::where('first_name', 'Ada')->sole();
        $this->post("/school/results/{$sheet->id}/comments", ['signer_id' => $teacher->id, 'comments' => [['student_id' => $ada->id, 'comment' => 'Typed by hand.']]]);

        $this->post("/school/results/{$sheet->id}/comments/fill", ['signer_id' => $teacher->id]);
        $this->assertSame('Typed by hand.', $this->remark($sheet, 'Ada', $teacher));

        $this->post("/school/results/{$sheet->id}/comments/fill", ['signer_id' => $teacher->id, 'overwrite' => true]);
        $this->assertSame('From the bank.', $this->remark($sheet, 'Ada', $teacher));
    }

    public function test_classmates_in_the_same_range_get_different_wording(): void
    {
        $sheet = $this->classWithResults('JSS 1', [['Ada', 'female', 18, 17, 50], ['Bola', 'female', 18, 16, 50]]);
        $this->bank('marks.entry', 70, 100, 'First wording.');
        $this->bank('marks.entry', 70, 100, 'Second wording.');
        $teacher = $this->signer($sheet, 'Class Teacher');

        $this->post("/school/results/{$sheet->id}/comments/fill", ['signer_id' => $teacher->id]);

        $this->assertEqualsCanonicalizing(['First wording.', 'Second wording.'], [$this->remark($sheet, 'Ada', $teacher), $this->remark($sheet, 'Bola', $teacher)]);
    }

    public function test_a_principal_writes_once_for_many_classes_and_locked_classes_are_skipped(): void
    {
        $jss1 = $this->classWithResults('JSS 1', [['Ada', 'female', 18, 17, 50]]);
        $jss2 = $this->classWithResults('JSS 2', [['Chidi', 'male', 10, 5, 30]]);
        $jss3 = $this->classWithResults('JSS 3', [['Dayo', 'male', 15, 15, 40]]);
        $jss3->forceFill(['status' => 'locked'])->save();
        $principal = ReportCardDesign::forClass($this->school->id, null)->signers()->where('label', 'Principal')->sole();

        $this->post('/school/results/comment-bank/apply', [
            'term_id' => $jss1->term_id, 'sheet_ids' => [$jss1->id, $jss2->id, $jss3->id],
            'signer_label' => 'principal', 'mode' => 'same', 'text' => 'Well done, {name}. Enjoy the holiday.',
        ])->assertSessionHasNoErrors()->assertSessionHas('error');

        $this->assertSame('Well done, Ada. Enjoy the holiday.', $this->remark($jss1, 'Ada', $principal));
        $this->assertSame('Well done, Chidi. Enjoy the holiday.', $this->remark($jss2, 'Chidi', $principal));
        $this->assertNull($this->remark($jss3, 'Dayo', $principal), 'locked results are not changed');
    }

    public function test_many_classes_with_their_own_designs_are_matched_by_the_signer_title(): void
    {
        $jss1 = $this->classWithResults('JSS 1', [['Ada', 'female', 18, 17, 50]]);
        $jss2 = $this->classWithResults('JSS 2', [['Chidi', 'male', 10, 5, 30]]);
        // JSS 2 uses its own design, which has its own Principal signer
        $this->post('/school/academics/report-card-designs', ['name' => 'Senior'])->assertSessionHasNoErrors();
        $senior = ReportCardDesign::where('name', 'Senior')->sole();
        $this->post("/school/academics/report-card-designs/{$senior->id}/classes", ['class_ids' => [$jss2->class_id]]);
        $this->bank('results.publish', 70, 100, 'Excellent.');
        $this->bank('results.publish', 0, 69.99, 'Work harder.');

        $this->post('/school/results/comment-bank/apply', [
            'term_id' => $jss1->term_id, 'sheet_ids' => [$jss1->id, $jss2->id], 'signer_label' => 'Principal', 'mode' => 'bank',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Excellent.', $this->remark($jss1, 'Ada', $this->signer($jss1, 'Principal')));
        $seniorPrincipal = $senior->signers()->where('label', 'Principal')->sole();
        $this->assertSame('Work harder.', $this->remark($jss2, 'Chidi', $seniorPrincipal));
    }

    public function test_teachers_cannot_change_the_heads_bank_or_write_the_principals_comments(): void
    {
        $sheet = $this->classWithResults('JSS 1', [['Ada', 'female', 18, 17, 50]]);
        $this->bank('results.publish', 0, 100, 'Heads only.');
        $entry = ReportCardCommentBank::sole();
        $teacher = $this->makeUser($this->school, 'teacher');
        $this->actingAs($teacher);

        $this->post('/school/results/comment-bank', ['writer_permission' => 'results.publish', 'min_average' => 0, 'max_average' => 100, 'comment' => 'x'])->assertForbidden();
        $this->delete("/school/results/comment-bank/{$entry->id}")->assertForbidden();
        $this->post('/school/results/comment-bank/apply', [
            'term_id' => $sheet->term_id, 'sheet_ids' => [$sheet->id], 'signer_label' => 'Principal', 'mode' => 'same', 'text' => 'Hijack',
        ]);
        $this->assertNull($this->remark($sheet, 'Ada', $this->signer($sheet, 'Principal')));

        // The teachers' own bank is theirs to use
        $this->post('/school/results/comment-bank', ['writer_permission' => 'marks.entry', 'min_average' => 0, 'max_average' => 100, 'comment' => 'Good work.'])->assertSessionHasNoErrors();
        $this->get('/school/results/comment-bank')->assertOk();
    }

    public function test_ready_made_comments_are_added_once_and_banks_stay_inside_their_school(): void
    {
        $this->post('/school/results/comment-bank/starters', ['writer_permission' => 'marks.entry'])->assertSessionHasNoErrors();
        $this->post('/school/results/comment-bank/starters', ['writer_permission' => 'marks.entry']);
        $this->assertSame(6, ReportCardCommentBank::count());

        $other = $this->makeSchool('Other School');
        $this->actingAs($this->makeUser($other, 'school-admin'));
        $entry = ReportCardCommentBank::withoutGlobalScopes()->first();
        $this->assertSame(0, ReportCardCommentBank::count());
        $this->delete("/school/results/comment-bank/{$entry->id}")->assertNotFound();
    }
}
