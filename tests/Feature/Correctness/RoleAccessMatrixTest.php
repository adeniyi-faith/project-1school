<?php

namespace Tests\Feature\Correctness;

use App\Models\School;
use Tests\Feature\Security\SecurityTestCase;

/**
 * Who may open what, written out by hand from the school's own rules (not read from the app):
 * every role is tried on every screen below and must get exactly the answer in the table.
 * A screen a role should NOT see must answer "not allowed" (403); one it should see must open.
 */
class RoleAccessMatrixTest extends SecurityTestCase
{
    private const SA = 'school-admin', PR = 'principal', TE = 'teacher', AC = 'accountant', LI = 'librarian',
        RE = 'receptionist', DR = 'driver', WA = 'warden', ST = 'store-manager';

    /** screen => the roles that may open it. Everyone not listed (including students and parents) must be refused. */
    private static function table(): array
    {
        return [
            '/school/students'                    => [self::SA, self::PR, self::TE, self::AC, self::LI, self::RE, self::WA],
            '/school/students/create'             => [self::SA, self::RE],
            '/school/admissions/inquiries'        => [self::SA, self::PR, self::TE, self::AC, self::LI, self::RE, self::WA],
            '/school/staff'                       => [self::SA, self::PR, self::LI],
            '/school/settings/admins'                     => [self::SA, self::PR],
            '/school/attendance'                  => [self::SA, self::PR, self::TE],
            '/school/exams'                       => [self::SA, self::PR, self::TE],
            '/school/results'                     => [self::SA, self::PR, self::TE],
            '/school/timetable'                   => [self::SA, self::PR, self::TE],
            '/school/homework'                    => [self::SA, self::PR, self::TE],
            '/school/lesson-notes'                => [self::SA, self::PR, self::TE],
            '/school/promotions'                  => [self::SA],
            '/school/fees/invoices'               => [self::SA, self::PR, self::AC],
            '/school/fees/payments/collect'       => [self::SA, self::AC],
            '/school/hr/payroll'                  => [self::SA, self::AC],
            '/school/settings'                    => [self::SA],
            '/school/reports/audit-log'           => [self::SA],
            '/school/reports/dashboard'           => [self::SA, self::PR, self::TE, self::AC, self::LI],
            '/school/library/books'               => [self::SA, self::LI],
            '/school/hostel'                      => [self::SA, self::WA],
            '/school/transport/routes'            => [self::SA, self::DR],
            '/school/inventory/items'             => [self::SA, self::ST],
            '/school/communication/announcements' => [self::SA, self::PR, self::TE, self::RE, self::DR, self::WA, self::ST],
            '/school/communication/blast'         => [self::SA],
        ];
    }

    private const ALL_ROLES = [self::SA, self::PR, self::TE, self::AC, self::LI, self::RE, self::DR, self::WA, self::ST, 'student', 'parent'];

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('Matrix School');
    }

    public static function roles(): array
    {
        return array_combine(self::ALL_ROLES, array_map(fn ($r) => [$r], self::ALL_ROLES));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('roles')]
    public function test_each_role_opens_exactly_the_screens_it_should_and_is_refused_the_rest(string $role): void
    {
        $this->actingAs($this->makeUser($this->school, $role));
        $wrong = [];

        foreach (self::table() as $path => $allowed) {
            $status = $this->get($path)->getStatusCode();
            $shouldOpen = in_array($role, $allowed, true);
            // 302 counts as open: the old Collect screen forwards to Invoices on purpose
            if ($shouldOpen && ! in_array($status, [200, 302], true)) {
                $wrong[] = "{$role} should open {$path} but got {$status}";
            }
            if (! $shouldOpen && $status !== 403) {
                $wrong[] = "{$role} should be refused {$path} but got {$status}";
            }
        }

        $this->assertSame([], $wrong);
    }

    public function test_the_refusal_is_a_plain_message_page_not_a_blank_popup(): void
    {
        $this->actingAs($this->makeUser($this->school, self::TE));

        // A normal page load shows the readable "not allowed" screen
        $this->get('/school/settings')->assertForbidden()->assertSee('You do not have permission to do that', false);

        // A click inside the app (Inertia) is refused too, with the same plain message and a 403
        $r = $this->get('/school/settings', ['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest']);
        $r->assertForbidden();
        $this->assertStringContainsString('do not have permission', $r->getContent());
    }

    public function test_students_and_parents_only_open_their_own_portal(): void
    {
        $portals = ['student' => '/school/student/dashboard', 'parent' => '/school/parent/dashboard'];
        foreach ($portals as $role => $own) {
            $other = $role === 'student' ? $portals['parent'] : $portals['student'];
            $this->actingAs($this->makeUser($this->school, $role));
            $this->assertNotSame(403, $this->get($own)->getStatusCode(), "{$role} must reach {$own}");
            $this->get($other)->assertForbidden();
        }

        // Staff cannot walk into either portal
        foreach ([self::SA, self::PR, self::TE, self::AC] as $role) {
            $this->actingAs($this->makeUser($this->school, $role));
            $this->get($portals['student'])->assertForbidden();
            $this->get($portals['parent'])->assertForbidden();
        }
    }

    public function test_someone_not_signed_in_is_sent_to_the_sign_in_page_for_every_screen(): void
    {
        $paths = [...array_keys(self::table()), '/school/student/dashboard', '/school/parent/dashboard', '/super-admin/dashboard', '/dashboard'];
        foreach ($paths as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_the_platform_owners_pages_are_closed_to_every_school_role(): void
    {
        foreach (self::ALL_ROLES as $role) {
            $this->actingAs($this->makeUser($this->school, $role));
            foreach (['/super-admin/dashboard', '/super-admin/schools', '/super-admin/packages', '/super-admin/ai'] as $path) {
                $this->get($path)->assertForbidden();
            }
        }
    }

    public function test_every_role_lands_on_the_right_start_page(): void
    {
        $landing = [
            self::SA => '/school/reports/dashboard', self::PR => '/school/reports/dashboard', self::TE => '/school/my-classes',
            self::AC => '/school/reports/dashboard', self::LI => '/school/reports/dashboard',
            self::RE => '/school/home', self::DR => '/school/home', self::WA => '/school/home', self::ST => '/school/home',
            'student' => '/school/student/dashboard', 'parent' => '/school/parent/dashboard',
        ];
        foreach ($landing as $role => $url) {
            $this->actingAs($this->makeUser($this->school, $role));
            $this->get('/dashboard')->assertRedirect($url);
        }
    }
}
