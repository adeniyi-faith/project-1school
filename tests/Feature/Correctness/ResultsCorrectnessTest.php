<?php

namespace Tests\Feature\Correctness;

use App\Models\AcademicYear;
use App\Models\AssessmentScheme;
use App\Models\Attendance;
use App\Models\ResultSheet;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\TermResultSummary;
use App\Services\ReportCardService;
use Tests\Feature\Security\SecurityTestCase;

/**
 * Right-answer checks for scores, grades, positions, report card figures and attendance.
 * Every expected number is worked out by hand from the WAEC grade bands (A1 75-100, B2 70-74, B3 65-69, C4 60-64,
 * C5 55-59, C6 50-54, D7 45-49, E8 40-44, F9 0-39) and the score parts CA1 /20, CA2 /20, Exam /60.
 */
class ResultsCorrectnessTest extends SecurityTestCase
{
    private $school;
    private $admin;
    private SchoolClass $class;
    private Term $term;
    /** @var array<string,int> */
    private array $part;
    private array $subject = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('Results Right School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $this->term = $year->terms()->first();
        $this->term->update(['start_date' => '2026-09-07', 'end_date' => '2026-12-18']);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1']);
        foreach (['Mathematics' => 'MTH', 'English' => 'ENG', 'Science' => 'SCI'] as $name => $code) {
            $this->subject[$name] = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => $name, 'code' => $code]);
        }
        $this->part = AssessmentScheme::where('is_default', true)->sole()->components->pluck('id', 'short_name')->all();
    }

    private function pupil(string $first): Student
    {
        return Student::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'first_name' => $first, 'last_name' => 'Pupil', 'gender' => 'female', 'status' => 'active']);
    }

    private function sheet(): ResultSheet
    {
        $this->get('/school/results', ['X-Inertia' => 'true'])->assertOk();

        return ResultSheet::where('term_id', $this->term->id)->where('class_id', $this->class->id)->sole();
    }

    /** Split a subject total into CA1 (max 20), CA2 (max 20) and Exam (the rest, max 60) */
    private function split(float $total): array
    {
        $ca1 = min(20, $total);
        $ca2 = min(20, round($total - $ca1, 2));
        $exam = round($total - $ca1 - $ca2, 2);

        return [$ca1, $ca2, $exam];
    }

    private function enter(ResultSheet $sheet, string $subject, array $totalsByStudentId)
    {
        $scores = [];
        foreach ($totalsByStudentId as $studentId => $total) {
            [$ca1, $ca2, $exam] = $this->split($total);
            foreach (['CA1' => $ca1, 'CA2' => $ca2, 'Exam' => $exam] as $short => $score) {
                $scores[] = ['student_id' => $studentId, 'component_id' => $this->part[$short], 'score' => $score];
            }
        }

        return $this->post("/school/results/{$sheet->id}/scores", ['subject_id' => $this->subject[$subject]->id, 'scores' => $scores]);
    }

    public function test_every_grade_boundary_gives_the_right_letter(): void
    {
        $expected = [
            100 => 'A1', 75 => 'A1', 74.99 => 'B2', 70 => 'B2', 69.99 => 'B3', 65 => 'B3', 64.99 => 'C4', 60 => 'C4',
            59.99 => 'C5', 55 => 'C5', 54.99 => 'C6', 50 => 'C6', 49.99 => 'D7', 45 => 'D7', 44.99 => 'E8', 40 => 'E8', 39.99 => 'F9', 0 => 'F9',
        ];
        $sheet = $this->sheet();
        $byStudent = [];
        $pupils = [];
        foreach (array_keys($expected) as $i => $total) {
            $p = $this->pupil("P$i");
            $pupils[(string) $total] = $p;
            $byStudent[$p->id] = (float) $total;
        }
        $this->enter($sheet, 'Mathematics', $byStudent)->assertSessionHasNoErrors();

        foreach ($expected as $total => $grade) {
            $r = TermResult::where('student_id', $pupils[(string) $total]->id)->where('subject_id', $this->subject['Mathematics']->id)->sole();
            $this->assertEquals((float) $total, (float) $r->total, "total for $total");
            $this->assertSame($grade, $r->grade, "$total should be $grade");
        }
    }

    public function test_totals_positions_averages_and_class_average_for_four_pupils_in_three_subjects(): void
    {
        [$ada, $bayo, $chidi, $dupe] = [$this->pupil('Ada'), $this->pupil('Bayo'), $this->pupil('Chidi'), $this->pupil('Dupe')];
        $sheet = $this->sheet();
        $this->enter($sheet, 'Mathematics', [$ada->id => 90, $bayo->id => 85, $chidi->id => 60, $dupe->id => 50])->assertSessionHasNoErrors();
        $this->enter($sheet, 'English', [$ada->id => 80, $bayo->id => 85, $chidi->id => 55.5, $dupe->id => 50])->assertSessionHasNoErrors();
        $this->enter($sheet, 'Science', [$ada->id => 70, $bayo->id => 70, $chidi->id => 70, $dupe->id => 50])->assertSessionHasNoErrors();

        $pos = fn (string $subject) => TermResult::where('subject_id', $this->subject[$subject]->id)->get()->keyBy('student_id')->map->subject_position;
        // Maths 90/85/60/50 -> 1,2,3,4. English 80/85/55.5/50 -> Ada 2, Bayo 1, Chidi 3, Dupe 4. Science 70/70/70/50 -> 1,1,1,4
        $this->assertEquals([1, 2, 3, 4], [$pos('Mathematics')[$ada->id], $pos('Mathematics')[$bayo->id], $pos('Mathematics')[$chidi->id], $pos('Mathematics')[$dupe->id]]);
        $this->assertEquals([2, 1, 3, 4], [$pos('English')[$ada->id], $pos('English')[$bayo->id], $pos('English')[$chidi->id], $pos('English')[$dupe->id]]);
        $this->assertEquals([1, 1, 1, 4], [$pos('Science')[$ada->id], $pos('Science')[$bayo->id], $pos('Science')[$chidi->id], $pos('Science')[$dupe->id]]);

        // Subject figures for Maths: average (90+85+60+50)/4 = 71.25, highest 90, lowest 50
        $m = TermResult::where('subject_id', $this->subject['Mathematics']->id)->first();
        $this->assertEquals(71.25, (float) $m->subject_average);
        $this->assertEquals(90.0, (float) $m->subject_highest);
        $this->assertEquals(50.0, (float) $m->subject_lowest);

        // Overall: Ada (90+80+70)/3 = 80.00 ; Bayo (85+85+70)/3 = 80.00 ; Chidi (60+55.5+70)/3 = 61.83 ; Dupe 50.00
        $s = TermResultSummary::get()->keyBy('student_id');
        $this->assertEquals([240.0, 240.0, 185.5, 150.0], [(float) $s[$ada->id]->total_score, (float) $s[$bayo->id]->total_score, (float) $s[$chidi->id]->total_score, (float) $s[$dupe->id]->total_score]);
        $this->assertEquals([80.0, 80.0, 61.83, 50.0], [(float) $s[$ada->id]->average, (float) $s[$bayo->id]->average, (float) $s[$chidi->id]->average, (float) $s[$dupe->id]->average]);
        // Ada and Bayo tie for 1st, so Chidi is 3rd and Dupe 4th
        $this->assertEquals([1, 1, 3, 4], [$s[$ada->id]->position, $s[$bayo->id]->position, $s[$chidi->id]->position, $s[$dupe->id]->position]);
        // Class average = (80 + 80 + 61.83 + 50) / 4 = 67.96
        $this->assertEquals(67.96, (float) $sheet->fresh()->class_average);

        // The printed report card carries the same numbers
        $card = collect(app(ReportCardService::class)->termCards($sheet->fresh()))->firstWhere('student.name', 'Chidi Pupil');
        $this->assertNotNull($card);
        $this->assertEquals(61.83, (float) $card['summary']['average']);
        $this->assertSame('C4', $card['summary']['grade']); // 61.83 is in the C4 band
        $this->assertEquals(3, $card['summary']['position']);
        $this->assertEquals(4, $card['summary']['class_size']);
        $english = collect($card['rows'])->firstWhere('subject', 'English');
        $this->assertEquals(55.5, (float) $english['total']);
        $this->assertSame('C5', $english['grade']);
        $this->assertEquals([20.0, 20.0, 15.5], [(float) $english['parts']['CA1'], (float) $english['parts']['CA2'], (float) $english['parts']['Exam']]);
    }

    public function test_changing_or_clearing_a_score_works_everything_out_again(): void
    {
        [$ada, $bayo] = [$this->pupil('Ada'), $this->pupil('Bayo')];
        $sheet = $this->sheet();
        $this->enter($sheet, 'Mathematics', [$ada->id => 60, $bayo->id => 70]);
        $this->assertEquals(1, TermResult::where('student_id', $bayo->id)->value('subject_position'));

        // Ada is corrected to 80: she is now first and the class average moves from 65 to 75
        $this->enter($sheet, 'Mathematics', [$ada->id => 80, $bayo->id => 70]);
        $this->assertEquals(1, TermResult::where('student_id', $ada->id)->value('subject_position'));
        $this->assertEquals(2, TermResult::where('student_id', $bayo->id)->value('subject_position'));
        $this->assertEquals(75.0, (float) $sheet->fresh()->class_average);

        // Blank boxes remove Bayo's score completely
        $blank = [];
        foreach (['CA1', 'CA2', 'Exam'] as $short) {
            $blank[] = ['student_id' => $bayo->id, 'component_id' => $this->part[$short], 'score' => null];
        }
        $this->post("/school/results/{$sheet->id}/scores", ['subject_id' => $this->subject['Mathematics']->id, 'scores' => $blank])->assertSessionHasNoErrors();
        $this->assertSame(0, TermResult::where('student_id', $bayo->id)->count());
        $this->assertEquals(80.0, (float) $sheet->fresh()->class_average);
    }

    public function test_bad_scores_are_refused_and_nothing_is_saved(): void
    {
        $ada = $this->pupil('Ada');
        $sheet = $this->sheet();
        $bad = fn ($ca1, $ca2, $exam) => $this->post("/school/results/{$sheet->id}/scores", ['subject_id' => $this->subject['Mathematics']->id, 'scores' => [
            ['student_id' => $ada->id, 'component_id' => $this->part['CA1'], 'score' => $ca1],
            ['student_id' => $ada->id, 'component_id' => $this->part['CA2'], 'score' => $ca2],
            ['student_id' => $ada->id, 'component_id' => $this->part['Exam'], 'score' => $exam],
        ]]);

        $bad(20.01, 10, 10)->assertSessionHasErrors();      // CA1 is out of 20
        $bad(10, 10, 60.01)->assertSessionHasErrors();      // Exam is out of 60
        $bad(-1, 10, 10)->assertSessionHasErrors();         // below zero
        $bad('abc', 10, 10)->assertSessionHasErrors();      // not a number
        $this->assertSame(0, TermResult::count());

        // The top mark in every part is fine and adds to exactly 100
        $bad(20, 20, 60)->assertSessionHasNoErrors();
        $this->assertEquals(100.0, (float) TermResult::sole()->total);
        $this->assertSame('A1', TermResult::sole()->grade);
    }

    public function test_a_score_for_a_pupil_in_another_school_or_class_is_refused(): void
    {
        $sheet = $this->sheet();
        $other = $this->makeSchool('Elsewhere');
        $otherClass = SchoolClass::create(['school_id' => $other->id, 'name' => 'X']);
        $stranger = Student::withoutGlobalScopes()->create(['school_id' => $other->id, 'class_id' => $otherClass->id, 'first_name' => 'Zed', 'last_name' => 'Far', 'gender' => 'male', 'status' => 'active']);
        $this->enter($sheet, 'Mathematics', [$stranger->id => 50])->assertSessionHasErrors();
        $this->assertSame(0, TermResult::withoutGlobalScopes()->count());
    }

    public function test_scores_are_frozen_once_the_sheet_is_submitted(): void
    {
        $ada = $this->pupil('Ada');
        $sheet = $this->sheet();
        $this->enter($sheet, 'Mathematics', [$ada->id => 60]);
        $this->post("/school/results/{$sheet->id}/status", ["action" => "submit"])->assertSessionHas("success");
        $this->enter($sheet, 'Mathematics', [$ada->id => 99]);
        $this->assertEquals(60.0, (float) TermResult::sole()->total);
    }

    public function test_attendance_marking_counts_late_as_present_and_half_days_as_half(): void
    {
        $ada = $this->pupil('Ada');
        $days = ['2026-09-07' => 'present', '2026-09-08' => 'present', '2026-09-09' => 'present', '2026-09-10' => 'late', '2026-09-11' => 'half_day', '2026-09-14' => 'absent'];
        foreach ($days as $date => $status) {
            $this->post('/school/attendance', ['date' => $date, 'class_id' => $this->class->id, 'records' => [['student_id' => $ada->id, 'status' => $status]]])->assertSessionHasNoErrors();
        }
        $this->assertSame(6, Attendance::count());

        // Marking the same day again changes the mark; it never adds a second row
        $this->post('/school/attendance', ['date' => '2026-09-14', 'class_id' => $this->class->id, 'records' => [['student_id' => $ada->id, 'status' => 'present']]]);
        $this->assertSame(6, Attendance::count());
        $this->post('/school/attendance', ['date' => '2026-09-14', 'class_id' => $this->class->id, 'records' => [['student_id' => $ada->id, 'status' => 'absent']]]);

        // Hand count for the term card: present 3 + late 1 + half 0.5 = 4.5 days, absent 1, marked 6
        $sheet = $this->sheet();
        $this->enter($sheet, 'Mathematics', [$ada->id => 70]);
        $card = app(ReportCardService::class)->termCards($sheet->fresh())[0];
        $this->assertEquals(4.5, (float) $card['attendance']['present']);
        $this->assertEquals(1, $card['attendance']['absent']);
        $this->assertEquals(6, $card['attendance']['marked']);
    }

    public function test_bad_attendance_input_is_refused(): void
    {
        $ada = $this->pupil('Ada');
        $post = fn ($over) => $this->post('/school/attendance', $over + ['date' => '2026-09-07', 'class_id' => $this->class->id, 'records' => [['student_id' => $ada->id, 'status' => 'present']]]);
        $post(['records' => [['student_id' => $ada->id, 'status' => 'sleeping']]])->assertSessionHasErrors('records.0.status');
        $post(['date' => 'yesterday-ish'])->assertSessionHasErrors('date');
        $post(['records' => []])->assertSessionHasErrors('records');
        $post(['class_id' => 999999])->assertSessionHasErrors('class_id');
        $this->assertSame(0, Attendance::count());
    }

    public function test_attendance_cannot_be_marked_for_a_pupil_in_another_school(): void
    {
        $other = $this->makeSchool('Elsewhere');
        $c = SchoolClass::create(['school_id' => $other->id, 'name' => 'X']);
        $stranger = Student::withoutGlobalScopes()->create(['school_id' => $other->id, 'class_id' => $c->id, 'first_name' => 'Zed', 'last_name' => 'Far', 'gender' => 'male', 'status' => 'active']);
        $this->post('/school/attendance', ['date' => '2026-09-07', 'class_id' => $this->class->id, 'records' => [['student_id' => $stranger->id, 'status' => 'present']]])->assertSessionHasErrors('records.0.student_id');
        $this->assertSame(0, Attendance::withoutGlobalScopes()->count());
    }

    public function test_the_dashboard_counts_only_active_pupils_and_treats_late_as_present(): void
    {
        $a = $this->pupil('A');
        $b = $this->pupil('B');
        $c = $this->pupil('C');
        $d = $this->pupil('D');
        $this->pupil('Gone')->update(['status' => 'alumni']);
        $this->pupil('Deleted')->delete();
        // Today: 2 present, 1 late, 1 absent -> (2 + 1) / 4 = 75%
        $today = now()->toDateString();
        foreach ([[$a, 'present'], [$b, 'present'], [$c, 'late'], [$d, 'absent']] as [$s, $st]) {
            $this->post('/school/attendance', ['date' => $today, 'class_id' => $this->class->id, 'records' => [['student_id' => $s->id, 'status' => $st]]])->assertSessionHasNoErrors();
        }
        $props = $this->get('/school/reports/dashboard', ['X-Inertia' => 'true'])->assertOk()->json('props');
        $this->assertSame(4, $props['totalStudents'], 'only active, undeleted pupils');
        $this->assertEquals(75.0, (float) $props['attendancePct']);
    }
}
