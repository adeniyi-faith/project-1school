<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentScheme;
use App\Models\Attendance;
use App\Models\BehaviourRating;
use App\Models\BehaviourTrait;
use App\Models\Guardian;
use App\Models\ReportCardComment;
use App\Models\ResultSheet;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\ReportCardService;
use Tests\Feature\Security\SecurityTestCase;

/** Term and full-year report cards: what is printed, comments, and who may download them. */
class ReportCardTest extends SecurityTestCase
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
    /** @var ResultSheet[] one per term, in order */
    private array $sheets;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = $this->makeSchool('Report School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);

        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2025/2026', 'start_date' => '2025-09-08', 'end_date' => '2026-07-24', 'is_current' => true]);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        $this->maths = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'Mathematics', 'code' => 'MTH']);
        $this->english = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'English', 'code' => 'ENG']);
        $this->part = AssessmentScheme::where('is_default', true)->sole()->components->pluck('id', 'short_name')->all();
        $this->ada = $this->student('Ada');
        $this->bayo = $this->student('Bayo');

        foreach ($this->year->terms()->get() as $term) {
            $this->sheets[] = ResultSheet::create(['school_id' => $this->school->id, 'term_id' => $term->id, 'class_id' => $this->class->id]);
        }
    }

    private function student(string $first): Student
    {
        return Student::create([
            'school_id' => $this->school->id, 'class_id' => $this->class->id, 'first_name' => $first, 'last_name' => 'Test',
            'gender' => 'female', 'status' => 'active', 'date_of_birth' => '2014-01-01',
        ]);
    }

    /** Enter [student, CA1, CA2, Exam] rows for a subject on a sheet */
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

    private function firstTerm(): ResultSheet
    {
        $sheet = $this->sheets[0];
        $this->enter($sheet, $this->maths, [[$this->ada, 18, 17, 50], [$this->bayo, 10, 8, 30.5]]);
        $this->enter($sheet, $this->english, [[$this->ada, 15, 15, 40], [$this->bayo, 12, 14, 44]]);

        return $sheet;
    }

    public function test_the_term_card_shows_part_scores_totals_positions_and_extras(): void
    {
        $sheet = $this->firstTerm();
        $trait = BehaviourTrait::where('domain', 'affective')->first();
        BehaviourRating::create(['school_id' => $this->school->id, 'result_sheet_id' => $sheet->id, 'student_id' => $this->ada->id, 'behaviour_trait_id' => $trait->id, 'rating' => 5]);
        $term = $sheet->term;
        $term->update(['start_date' => '2025-09-08', 'end_date' => '2025-12-12']);
        foreach (['2025-09-08' => 'present', '2025-09-09' => 'late', '2025-09-10' => 'absent', '2026-01-20' => 'present'] as $date => $status) {
            Attendance::create(['school_id' => $this->school->id, 'date' => $date, 'attendable_type' => Student::class, 'attendable_id' => $this->ada->id, 'status' => $status]);
        }
        $this->sheets[1]->term->update(['start_date' => '2026-01-05']);
        ReportCardComment::create(['school_id' => $this->school->id, 'result_sheet_id' => $sheet->id, 'student_id' => $this->ada->id, 'teacher_comment' => 'A hardworking pupil.']);

        $cards = app(ReportCardService::class)->termCards($sheet->fresh());

        $this->assertCount(2, $cards);
        $ada = $cards[0];
        $this->assertSame('Ada Test', $ada['student']['name']);
        $this->assertSame(['CA1', 'CA2', 'Exam'], array_column($ada['columns'], 'name'));
        $this->assertSame(['English', 'Mathematics'], array_column($ada['rows'], 'subject'));
        $maths = $ada['rows'][1];
        $this->assertEquals(['CA1' => 18, 'CA2' => 17, 'Exam' => 50], $maths['parts']);
        $this->assertEquals([85, 1, 66.75, 85, 48.5], [$maths['total'], $maths['position'], $maths['average'], $maths['highest'], $maths['lowest']]);
        $this->assertEquals([155, 77.5, 1, 2], [$ada['summary']['total'], $ada['summary']['average'], $ada['summary']['position'], $ada['summary']['class_size']]);
        $this->assertSame([['name' => $trait->name, 'rating' => 5]], $ada['ratings']['affective']);
        // Late counts as present; the January day is outside the term
        $this->assertEquals(['present' => 2, 'absent' => 1, 'marked' => 3], $ada['attendance']);
        $this->assertSame('A hardworking pupil.', $ada['teacher_comment']);
        $this->assertSame('5 January 2026', $ada['next_term_begins']);
        $this->assertTrue($ada['preview'], 'draft results are marked as a preview');
        $this->assertNotEmpty($ada['grade_key']);
    }

    public function test_the_full_year_card_averages_the_terms_and_shows_the_decision(): void
    {
        $this->firstTerm();
        $this->enter($this->sheets[1], $this->maths, [[$this->ada, 20, 20, 50], [$this->bayo, 15, 15, 45]]);
        $this->enter($this->sheets[1], $this->english, [[$this->ada, 10, 10, 40], [$this->bayo, 20, 20, 50]]);

        $this->post('/school/promotions', [
            'academic_year_id' => $this->year->id, 'from_class_id' => $this->class->id,
            'decisions' => [['student_id' => $this->bayo->id, 'outcome' => 'repeated', 'reason' => 'Parents asked']],
        ])->assertSessionHasNoErrors();

        $cards = app(ReportCardService::class)->sessionCards($this->year, $this->class->id);
        [$ada, $bayo] = $cards;

        $this->assertSame(['First Term', 'Second Term', 'Third Term'], $ada['term_names']);
        $this->assertSame('Mathematics', $ada['rows'][1]['subject']);
        $this->assertEquals([85, 90, null], $ada['rows'][1]['terms']);
        $this->assertEquals(87.5, $ada['rows'][1]['average']);
        // Ada: term 1 77.5, term 2 75 → 76.25. Bayo: term 1 59.25, term 2 82.5 → 70.88
        $this->assertEquals([77.5, 75, null], $ada['term_averages']);
        $this->assertEquals([76.25, 1, 2], [$ada['summary']['average'], $ada['summary']['position'], $ada['summary']['class_size']]);
        $this->assertEquals([70.88, 2], [$bayo['summary']['average'], $bayo['summary']['position']]);
        $this->assertNull($ada['decision']);
        $this->assertSame('To repeat JSS 1', $bayo['decision']);
    }

    public function test_staff_download_pdfs_for_one_student_or_the_whole_class(): void
    {
        $sheet = $this->firstTerm();

        $this->get("/school/results/{$sheet->id}/report-cards/term?student={$this->ada->id}")
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get("/school/results/{$sheet->id}/report-cards/term")->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get("/school/results/{$sheet->id}/report-cards/session")->assertOk()->assertHeader('content-type', 'application/pdf');

        // No results yet on the second term
        $this->get("/school/results/{$this->sheets[1]->id}/report-cards/term")->assertNotFound();

        $this->get("/school/results/{$sheet->id}/report-cards", ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonCount(2, 'props.students')
            ->assertJsonPath('props.can.teacher', true)
            ->assertJsonPath('props.can.principal', true);
    }

    public function test_teacher_and_principal_comments_are_saved_separately(): void
    {
        $sheet = $this->firstTerm();
        $teacher = $this->makeUser($this->school, 'teacher');
        $principal = $this->makeUser($this->school, 'principal');

        $this->actingAs($teacher)->post("/school/results/{$sheet->id}/comments/teacher", ['comments' => [
            ['student_id' => $this->ada->id, 'comment' => ' Excellent work. '],
            ['student_id' => $this->bayo->id, 'comment' => ''],
        ]])->assertSessionHasNoErrors();
        // A teacher cannot write the principal's comment, and a principal cannot write the teacher's
        $this->actingAs($teacher)->post("/school/results/{$sheet->id}/comments/principal", ['comments' => []])->assertForbidden();
        $this->actingAs($principal)->post("/school/results/{$sheet->id}/comments/teacher", ['comments' => []])->assertForbidden();

        $this->actingAs($principal)->post("/school/results/{$sheet->id}/comments/principal", ['comments' => [
            ['student_id' => $this->ada->id, 'comment' => 'Keep it up.'],
        ]])->assertSessionHasNoErrors();

        $comment = ReportCardComment::sole();
        $this->assertSame(['Excellent work.', $teacher->id, 'Keep it up.', $principal->id],
            [$comment->teacher_comment, $comment->teacher_by, $comment->principal_comment, $comment->principal_by]);

        // Locked results: comments can no longer change
        $sheet->forceFill(['status' => 'locked'])->save();
        $this->post("/school/results/{$sheet->id}/comments/principal", ['comments' => [['student_id' => $this->ada->id, 'comment' => 'Changed']]])
            ->assertSessionHasErrors('comments');
        $this->assertSame('Keep it up.', $comment->fresh()->principal_comment);
    }

    public function test_families_only_download_their_own_published_cards(): void
    {
        $sheet = $this->firstTerm();

        $studentUser = User::factory()->create(['school_id' => $this->school->id, 'status' => 'active']);
        $studentUser->assignRole('student');
        $this->ada->update(['user_id' => $studentUser->id]);
        $parentUser = User::factory()->create(['school_id' => $this->school->id, 'status' => 'active']);
        $parentUser->assignRole('parent');
        $guardian = Guardian::create(['school_id' => $this->school->id, 'user_id' => $parentUser->id, 'name' => 'Mrs Test', 'phone' => '08030000000']);
        $this->ada->update(['guardian_id' => $guardian->id]);

        // Not published yet
        $this->actingAs($studentUser)->get("/school/student/report-cards/{$sheet->id}")->assertNotFound();
        $this->actingAs($parentUser)->get("/school/parent/report-cards/{$this->ada->id}/{$sheet->id}")->assertNotFound();

        $sheet->forceFill(['status' => 'published'])->save();
        $this->actingAs($studentUser)->get("/school/student/report-cards/{$sheet->id}")->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($studentUser)->get("/school/student/report-cards/{$sheet->id}/session")->assertOk();
        $this->actingAs($parentUser)->get("/school/parent/report-cards/{$this->ada->id}/{$sheet->id}")->assertOk();
        $this->actingAs($parentUser)->get("/school/parent/report-cards/{$this->ada->id}/{$sheet->id}/session")->assertOk();

        // Not someone else's child
        $this->actingAs($parentUser)->get("/school/parent/report-cards/{$this->bayo->id}/{$sheet->id}")->assertNotFound();
        // Students and parents cannot use the staff downloads
        $this->actingAs($studentUser)->get("/school/results/{$sheet->id}/report-cards/term")->assertForbidden();
    }

    public function test_the_full_year_card_for_families_only_counts_published_terms(): void
    {
        $this->firstTerm();
        $this->enter($this->sheets[1], $this->maths, [[$this->ada, 20, 20, 50]]);
        $this->sheets[0]->forceFill(['status' => 'published'])->save();

        $cards = app(ReportCardService::class)->sessionCards($this->year, $this->class->id, [$this->ada->id], releasedOnly: true);

        $this->assertSame(['First Term'], $cards[0]['term_names']);
        $this->assertFalse($cards[0]['preview']);
    }

    public function test_another_school_cannot_print_these_cards(): void
    {
        $sheet = $this->firstTerm();
        $other = $this->makeUser($this->makeSchool('Other'), 'school-admin');

        $this->actingAs($other)->get("/school/results/{$sheet->id}/report-cards/term")->assertNotFound();
        $this->actingAs($other)->post("/school/results/{$sheet->id}/comments/teacher", ['comments' => []])->assertNotFound();
    }
}
