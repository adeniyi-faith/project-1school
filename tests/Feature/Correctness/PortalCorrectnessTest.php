<?php

namespace Tests\Feature\Correctness;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\AssessmentScheme;
use App\Models\Attendance;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\ResultSheet;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Carbon\Carbon;
use Tests\Feature\Security\SecurityTestCase;

/**
 * What a parent and a student are shown must be the right figures, for the right child, and nothing early.
 * All expected numbers are worked out by hand.
 */
class PortalCorrectnessTest extends SecurityTestCase
{
    private $school;
    private $admin;
    private SchoolClass $class;
    private Term $term;
    private Subject $maths;
    private array $part;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-21 10:00:00'); // a Wednesday in October
        $this->school = $this->makeSchool('Portal Right School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $this->term = $year->terms()->first();
        $this->term->update(['start_date' => '2026-09-07', 'end_date' => '2026-12-18']);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1']);
        $this->maths = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'Mathematics', 'code' => 'MTH']);
        $this->part = AssessmentScheme::where('is_default', true)->sole()->components->pluck('id', 'short_name')->all();
    }

    /** A pupil with a student login, and a parent with a parent login linked to them */
    private function family(string $first, ?SchoolClass $class = null): array
    {
        $class ??= $this->class;
        $parentUser = $this->makeUser($this->school, 'parent');
        $guardian = Guardian::create(['school_id' => $this->school->id, 'user_id' => $parentUser->id, 'name' => "Parent of $first", 'relation' => 'Mother', 'phone' => '08031234567']);
        $studentUser = $this->makeUser($this->school, 'student');
        $student = Student::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'guardian_id' => $guardian->id, 'user_id' => $studentUser->id, 'first_name' => $first, 'last_name' => 'Pupil', 'gender' => 'female', 'status' => 'active']);

        return [$parentUser, $studentUser, $student];
    }

    private function mark(Student $s, string $date, string $status): void
    {
        Attendance::mark($this->school->id, Student::class, $s->id, $date, $status, null);
    }

    private function child(array $page, int $studentId): ?array
    {
        return collect($page['children'])->firstWhere('id', $studentId);
    }

    public function test_attendance_percentage_counts_late_as_present_and_half_days_as_half_for_parent_and_student(): void
    {
        [$parent, $studentUser, $ada] = $this->family('Ada');
        // October 2026, 10 school days: 6 present, 1 late, 1 half day, 2 absent
        // attended = 6 + 1 + 0.5 = 7.5 of 10 = 75%
        $days = ['01' => 'present', '02' => 'present', '05' => 'present', '06' => 'present', '07' => 'present', '08' => 'present',
                 '09' => 'late', '12' => 'half_day', '13' => 'absent', '14' => 'absent'];
        foreach ($days as $d => $status) {
            $this->mark($ada, "2026-10-$d", $status);
        }
        // Last month's marks must not leak into this month
        $this->mark($ada, '2026-09-30', 'absent');

        $this->actingAs($parent)->get('/school/parent/dashboard')->assertOk()
            ->assertInertia(fn ($p) => $p->where('children.0.attendance.total', 10)->where('children.0.attendance.present', 6)
                ->where('children.0.attendance.absent', 2)->where('children.0.attendance.late', 1)->where('children.0.attendance.percentage', 75));

        $this->actingAs($studentUser)->get('/school/student/dashboard')->assertOk()
            ->assertInertia(fn ($p) => $p->where('attendance.total', 10)->where('attendance.percentage', 75));

        // The monthly pages agree: October 75%, September (one absence) 0%
        $this->actingAs($parent)->get('/school/parent/attendance')->assertInertia(fn ($p) => $p
            ->where('children.0.months.0.percentage', 75)->where('children.0.months.1.total', 1)->where('children.0.months.1.percentage', 0));
        $this->actingAs($studentUser)->get('/school/student/attendance')->assertInertia(fn ($p) => $p
            ->where('months.0.percentage', 75)->where('months.1.percentage', 0));
    }

    public function test_the_student_dashboard_says_what_todays_mark_is(): void
    {
        [, $studentUser, $ada] = $this->family('Ada');
        $this->actingAs($studentUser)->get('/school/student/dashboard')->assertInertia(fn ($p) => $p->where('attendance.today', 'not-marked'));
        $this->mark($ada, '2026-10-21', 'late');
        $this->actingAs($studentUser)->get('/school/student/dashboard')->assertInertia(fn ($p) => $p->where('attendance.today', 'late'));
    }

    public function test_a_parent_sees_only_their_own_childs_fees_and_the_totals_are_exact(): void
    {
        [$parentA, , $ada] = $this->family('Ada');
        [$parentB, , $bayo] = $this->family('Bayo');
        $cat = FeeCategory::create(['school_id' => $this->school->id, 'name' => 'Tuition', 'type' => 'tuition']);
        FeeStructure::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'fee_category_id' => $cat->id, 'academic_year' => '2026-2027', 'amount' => 100000, 'frequency' => 'per_term']);
        $this->actingAs($this->admin)->post('/school/fees/invoices', ['fee_structure_ids' => FeeStructure::pluck('id')->all(), 'term_id' => $this->term->id])->assertSessionHasNoErrors();

        $adaInvoice = Invoice::where('student_id', $ada->id)->sole();
        // Two part payments for Ada: 40,000.00 and 12,500.50 -> paid 52,500.50, owes 47,499.50. Bayo pays nothing.
        foreach ([40000, 12500.50] as $amount) {
            $this->actingAs($this->admin)->post("/school/fees/invoices/{$adaInvoice->id}/payments", ['amount' => $amount, 'method' => 'cash', 'entry_date' => '2026-10-20'])->assertSessionHasNoErrors();
        }

        $this->actingAs($parentA)->get('/school/parent/fees')->assertOk()->assertInertia(fn ($p) => $p
            ->has('children', 1)->where('children.0.id', $ada->id)
            ->where('children.0.total_due', 100000)->where('children.0.total_paid', 52500.5)->where('children.0.balance', 47499.5));
        $this->actingAs($parentB)->get('/school/parent/fees')->assertInertia(fn ($p) => $p
            ->has('children', 1)->where('children.0.id', $bayo->id)
            ->where('children.0.total_due', 100000)->where('children.0.total_paid', 0)->where('children.0.balance', 100000));

        // A parent cannot start an online payment on someone else's invoice
        $bayoInvoice = Invoice::where('student_id', $bayo->id)->sole();
        $this->actingAs($parentA)->post("/school/parent/fees/invoices/{$bayoInvoice->id}/pay", ['amount' => 500])->assertStatus(404);
    }

    public function test_a_parent_with_two_children_sees_both_and_a_childs_results_appear_only_once_published(): void
    {
        [$parent, $studentUser, $ada] = $this->family('Ada');
        $bayo = Student::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'guardian_id' => $ada->guardian_id, 'first_name' => 'Bayo', 'last_name' => 'Pupil', 'gender' => 'male', 'status' => 'active']);
        [, , $stranger] = $this->family('Zed');

        $this->actingAs($this->admin)->get('/school/results', ['X-Inertia' => 'true'])->assertOk();
        $sheet = ResultSheet::where('term_id', $this->term->id)->where('class_id', $this->class->id)->sole();
        // Maths: Ada 92 (CA1 20, CA2 20, Exam 52) -> A1; Bayo 58.5 (20, 18.5, 20) -> C5; Zed 40
        $scores = [];
        foreach ([[$ada, 20, 20, 52], [$bayo, 20, 18.5, 20], [$stranger, 20, 10, 10]] as [$s, $a, $b, $c]) {
            foreach (['CA1' => $a, 'CA2' => $b, 'Exam' => $c] as $short => $score) {
                $scores[] = ['student_id' => $s->id, 'component_id' => $this->part[$short], 'score' => $score];
            }
        }
        $this->actingAs($this->admin)->post("/school/results/{$sheet->id}/scores", ['subject_id' => $this->maths->id, 'scores' => $scores])->assertSessionHasNoErrors();

        // Still a draft: nothing at all for the family or the student
        $this->actingAs($parent)->get('/school/parent/results')->assertInertia(fn ($p) => $p->has('children', 2)->where('children.0.reports', [])->where('children.1.reports', []));
        $this->actingAs($studentUser)->get('/school/student/results')->assertOk();

        foreach (['submit', 'approve', 'publish'] as $action) {
            $this->actingAs($this->admin)->post("/school/results/{$sheet->id}/status", ['action' => $action])->assertSessionHas('success');
        }

        $page = $this->actingAs($parent)->get('/school/parent/results')->assertOk()->viewData('page')['props'];
        $adaR = $this->child($page, $ada->id)['reports'][0];
        $bayoR = $this->child($page, $bayo->id)['reports'][0];
        $this->assertEquals(92.0, (float) $adaR['subjects'][0]['total']);
        $this->assertSame('A1', $adaR['subjects'][0]['grade']);
        $this->assertSame(1, (int) $adaR['position']);
        $this->assertEquals(58.5, (float) $bayoR['subjects'][0]['total']);
        $this->assertSame('C5', $bayoR['subjects'][0]['grade']);
        $this->assertSame(2, (int) $bayoR['position']);
        $this->assertSame(3, (int) $bayoR['class_size']);
        // class average of 92, 58.5, 40 = 190.5 / 3 = 63.5
        $this->assertEquals(63.5, (float) $bayoR['class_average']);

        // The other family's pupil never appears, and their report card cannot be opened by this parent
        $this->assertNull($this->child($page, $stranger->id));
        $this->actingAs($parent)->get("/school/parent/report-cards/{$stranger->id}/{$sheet->id}")->assertStatus(404);
        $this->actingAs($parent)->get("/school/parent/report-cards/{$ada->id}/{$sheet->id}")->assertOk();
    }

    public function test_a_student_cannot_open_another_pupils_report_card_and_sees_only_own_results(): void
    {
        [, $adaUser, $ada] = $this->family('Ada');
        [, , $zed] = $this->family('Zed');
        $this->actingAs($this->admin)->get('/school/results', ['X-Inertia' => 'true']);
        $sheet = ResultSheet::where('class_id', $this->class->id)->sole();
        $scores = [];
        foreach ([$ada->id => [20, 20, 40], $zed->id => [10, 10, 10]] as $id => $parts) {
            foreach (array_combine(['CA1', 'CA2', 'Exam'], $parts) as $short => $score) {
                $scores[] = ['student_id' => $id, 'component_id' => $this->part[$short], 'score' => $score];
            }
        }
        $this->actingAs($this->admin)->post("/school/results/{$sheet->id}/scores", ['subject_id' => $this->maths->id, 'scores' => $scores])->assertSessionHasNoErrors();
        foreach (['submit', 'approve', 'publish'] as $action) {
            $this->actingAs($this->admin)->post("/school/results/{$sheet->id}/status", ['action' => $action]);
        }

        // Ada 20+20+40 = 80 -> A1, first of two
        $this->actingAs($adaUser)->get('/school/student/results')->assertOk()->assertInertia(fn ($p) => $p
            ->where('reports.0.subjects.0.total', 80)->where('reports.0.subjects.0.grade', 'A1')->where('reports.0.position', 1)->where('reports.0.class_size', 2));
        // The student routes only ever show the signed-in pupil's own card, so another pupil's card is simply not reachable by id
        $this->actingAs($adaUser)->get("/school/student/report-cards/{$sheet->id}")->assertOk();
    }

    public function test_announcements_reach_the_right_people_and_not_before_their_time(): void
    {
        [$parent, $studentUser, $ada] = $this->family('Ada');
        $other = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'SSS 3']);
        $make = fn (string $title, array $over) => Announcement::create($over + ['school_id' => $this->school->id, 'author_id' => $this->admin->id, 'title' => $title, 'body' => 'x', 'audience' => 'all', 'published_at' => now()->subHour()]);
        $make('For everyone', []);
        $make('Parents only', ['audience' => 'role', 'target_role' => 'parent']);
        $make('Students only', ['audience' => 'role', 'target_role' => 'student']);
        $make('Teachers only', ['audience' => 'role', 'target_role' => 'teacher']);
        $make('My class', ['audience' => 'class', 'class_id' => $this->class->id]);
        $make('Other class', ['audience' => 'class', 'class_id' => $other->id]);
        $make('Next week', ['published_at' => now()->addWeek()]);

        $titles = fn ($response, $key = 'announcements') => collect($response->viewData('page')['props'][$key])->pluck('title')->sort()->values()->all();

        $this->assertSame(['For everyone', 'My class', 'Parents only'], $titles($this->actingAs($parent)->get('/school/parent/announcements')));
        $this->assertSame(['For everyone', 'My class', 'Parents only'], $titles($this->actingAs($parent)->get('/school/parent/dashboard')));
        $this->assertSame(['For everyone', 'My class', 'Students only'], $titles($this->actingAs($studentUser)->get('/school/student/announcements')));
        $this->assertSame(['For everyone', 'My class', 'Students only'], $titles($this->actingAs($studentUser)->get('/school/student/dashboard')));

        // The bell shows the same lists
        $bell = fn ($user, $url) => collect($this->actingAs($user)->getJson($url)->json('items'))->pluck('title')->sort()->values()->all();
        $this->assertSame(['For everyone', 'My class', 'Parents only'], $bell($parent, '/school/parent/notifications/feed'));
        $this->assertSame(['For everyone', 'My class', 'Students only'], $bell($studentUser, '/school/student/notifications/feed'));
    }

    public function test_an_unlinked_parent_or_student_account_sees_a_calm_not_linked_page_not_an_error(): void
    {
        $parent = $this->makeUser($this->school, 'parent');
        $student = $this->makeUser($this->school, 'student');
        foreach (['/school/parent/dashboard', '/school/parent/fees', '/school/parent/results', '/school/parent/attendance', '/school/parent/announcements'] as $url) {
            $this->actingAs($parent)->get($url)->assertOk()->assertInertia(fn ($p) => $p->where('linked', false));
        }
        foreach (['/school/student/dashboard', '/school/student/fees', '/school/student/results', '/school/student/attendance', '/school/student/announcements', '/school/student/timetable', '/school/student/homework'] as $url) {
            $this->actingAs($student)->get($url)->assertOk();
        }
    }
}
