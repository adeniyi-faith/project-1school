<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Route;

/**
 * Every screen under /school must be guarded by a specific permission,
 * not just "you have one of six roles".
 */
class RoutePermissionTest extends SecurityTestCase
{
    public function test_every_school_route_has_a_permission_check_except_the_few_open_ones(): void
    {
        $open = [
            'school.classes.index', 'school.sections.index', 'school.subjects.index',
            'school.shifts.index', 'school.holidays.index',
            'school.communication.notifications', 'school.communication.notifications.read',
            'school.communication.notifications.read-all',
        ];

        $unguarded = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => str_starts_with($r->getName() ?? '', 'school.')
                && ! str_starts_with($r->getName(), 'school.student.')
                && ! str_starts_with($r->getName(), 'school.parent.'))
            ->reject(fn ($r) => collect($r->gatherMiddleware())->contains(fn ($m) => is_string($m) && str_starts_with($m, 'permission:')))
            ->map(fn ($r) => $r->getName())
            ->reject(fn ($n) => in_array($n, $open, true))
            ->values()->all();

        $this->assertSame([], $unguarded, 'These routes have no permission check');
    }

    /** @dataProvider blockedCases */
    #[\PHPUnit\Framework\Attributes\DataProvider('blockedCases')]
    public function test_role_is_blocked_from_screens_it_has_no_permission_for(string $role, string $method, string $routeName, array $params): void
    {
        $user = $this->makeUser($this->makeSchool('School A'), $role);

        $response = $this->actingAs($user)->call($method, route($routeName, $params), [], [], [], ['HTTP_X-Inertia' => 'true']);

        $response->assertForbidden();
    }

    public static function blockedCases(): array
    {
        return [
            'teacher cannot collect fees'        => ['teacher', 'POST', 'school.fees.payments.store', []],
            'teacher cannot see fee payments'    => ['teacher', 'GET', 'school.fees.payments.index', []],
            'teacher cannot see payroll'         => ['teacher', 'GET', 'school.hr.payroll.index', []],
            'teacher cannot generate payroll'    => ['teacher', 'POST', 'school.hr.payroll.generate', []],
            'teacher cannot change settings'     => ['teacher', 'POST', 'school.settings.general', []],
            'teacher cannot see integrations'    => ['teacher', 'GET', 'school.settings.integrations', []],
            'teacher cannot see audit log'       => ['teacher', 'GET', 'school.reports.audit-log', []],
            'teacher cannot add a student'       => ['teacher', 'POST', 'school.students.store', []],
            'teacher cannot add staff'           => ['teacher', 'POST', 'school.staff.store', []],
            'teacher cannot add a class'         => ['teacher', 'POST', 'school.classes.store', []],
            'accountant cannot create an exam'   => ['accountant', 'POST', 'school.exams.store', []],
            'accountant cannot mark attendance'  => ['accountant', 'POST', 'school.attendance.store', []],
            'accountant cannot change settings'  => ['accountant', 'POST', 'school.settings.general', []],
            'librarian cannot collect fees'      => ['librarian', 'POST', 'school.fees.payments.store', []],
            'librarian cannot see payroll'       => ['librarian', 'GET', 'school.hr.payroll.index', []],
            'principal cannot collect fees'      => ['principal', 'POST', 'school.fees.payments.store', []],
            'principal cannot change settings'   => ['principal', 'POST', 'school.settings.general', []],
            'principal cannot generate payroll'  => ['principal', 'POST', 'school.hr.payroll.generate', []],
        ];
    }

    /** @dataProvider allowedCases */
    #[\PHPUnit\Framework\Attributes\DataProvider('allowedCases')]
    public function test_role_can_still_reach_the_screens_it_needs(string $role, string $method, string $routeName): void
    {
        $user = $this->makeUser($this->makeSchool('School A'), $role);

        $response = $this->actingAs($user)->call($method, route($routeName), [], [], [], ['HTTP_X-Inertia' => 'true']);

        $this->assertNotSame(403, $response->getStatusCode());
    }

    public static function allowedCases(): array
    {
        return [
            'teacher marks attendance'      => ['teacher', 'GET', 'school.attendance.index'],
            'teacher opens homework'        => ['teacher', 'GET', 'school.homework.index'],
            'teacher opens exams'           => ['teacher', 'GET', 'school.exams.index'],
            'teacher opens students'        => ['teacher', 'GET', 'school.students.index'],
            'teacher opens leave'           => ['teacher', 'GET', 'school.hr.leaves.index'],
            'accountant opens fee payments' => ['accountant', 'GET', 'school.fees.payments.index'],
            'accountant opens payroll'      => ['accountant', 'GET', 'school.hr.payroll.index'],
            'librarian opens books'         => ['librarian', 'GET', 'school.library.books.index'],
            'principal opens reports'       => ['principal', 'GET', 'school.reports.dashboard'],
            'principal opens fee payments'  => ['principal', 'GET', 'school.fees.payments.index'],
            'school admin opens settings'   => ['school-admin', 'GET', 'school.settings.index'],
            'school admin opens payroll'    => ['school-admin', 'GET', 'school.hr.payroll.index'],
            'school admin opens fee collect' => ['school-admin', 'GET', 'school.fees.payments.create'],
        ];
    }
}
