<?php

namespace Tests\Feature;

use App\Support\NavAccess;
use Tests\Feature\Security\SecurityTestCase;

/** The side menu and buttons only offer what a person can really open */
class NavAccessTest extends SecurityTestCase
{
    public function test_every_menu_link_is_a_real_page(): void
    {
        preg_match_all("/href: '([^']+)'/", file_get_contents(base_path('resources/js/components/layout/navigation.ts')), $m);
        $missing = array_values(array_diff(array_unique($m[1]), array_keys(NavAccess::rules())));

        $this->assertSame([], $missing, 'These menu links do not match any page, so they would never show');
    }

    public function test_a_teacher_is_offered_only_pages_a_teacher_may_open(): void
    {
        $teacher = $this->makeUser($this->makeSchool('Nav School'), 'teacher');
        $paths = NavAccess::paths($teacher);

        $this->assertContains('/school/attendance', $paths);
        $this->assertContains('/school/timetable', $paths);
        $this->assertNotContains('/school/staff', $paths);
        $this->assertNotContains('/school/hr/payroll', $paths);
        $this->assertNotContains('/school/fees/invoices', $paths);
        $this->assertNotContains('/super-admin/dashboard', $paths);
    }

    public function test_the_shared_data_lists_what_the_person_may_do(): void
    {
        $accountant = $this->makeUser($this->makeSchool('Nav School 2'), 'accountant');
        $permissions = NavAccess::permissions($accountant);

        $this->assertContains('fees.collect', $permissions);
        $this->assertNotContains('students.create', $permissions);
        $this->assertSame(['*'], NavAccess::permissions($this->makeUser($this->makeSchool('Owner School'), 'super-admin')));
    }

    public function test_a_menu_link_is_hidden_only_when_the_page_would_really_refuse_the_person(): void
    {
        preg_match_all("/href: '([^']+)'[^\n]*roles: \[([^\]]*)\]/", file_get_contents(base_path('resources/js/components/layout/navigation.ts')), $m, PREG_SET_ORDER);
        $school = $this->makeSchool('Role Nav School');
        $roles = [];
        foreach ($m as [, $href, $list]) {
            foreach (array_map(fn ($r) => trim($r, " '"), explode(',', $list)) as $role) {
                $roles[$role][] = $href;
            }
        }

        foreach ($roles as $role => $hrefs) {
            $user = $this->makeUser($school, $role);
            $paths = NavAccess::paths($user);
            foreach ($hrefs as $href) {
                $status = $this->actingAs($user)->get($href, ['X-Inertia' => 'true'])->getStatusCode();
                $this->assertSame(in_array($href, $paths, true), $status !== 403, "{$role} {$href} answered {$status}");
            }
        }
    }
}
