<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Student;
use Tests\Feature\Security\SecurityTestCase;

/** Shortcut buttons at the top of a student's page: Collect payment, Message parent, Print report card. */
class StudentQuickLinksTest extends SecurityTestCase
{
    private function student($school, ?int $guardianId = null): Student
    {
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);

        return Student::create([
            'school_id' => $school->id, 'class_id' => $class->id, 'guardian_id' => $guardianId, 'first_name' => 'Ada', 'last_name' => 'Obi',
            'admission_no' => 'ADM/001', 'gender' => 'female', 'status' => 'active', 'date_of_birth' => '2014-01-01',
        ]);
    }

    public function test_admin_gets_collect_and_message_links_but_no_report_card_before_results_exist(): void
    {
        $school = $this->makeSchool('Quick School');
        $admin = $this->makeUser($school, 'school-admin');
        $parentUser = $this->makeUser($school, 'parent');
        $guardian = Guardian::create(['school_id' => $school->id, 'user_id' => $parentUser->id, 'name' => 'Mr Obi', 'relation' => 'father', 'phone' => '08030000000']);
        $student = $this->student($school, $guardian->id);

        $this->actingAs($admin)->get("/school/students/{$student->id}")->assertOk()->assertInertia(fn ($p) => $p
            ->where('quick.collect', '/school/fees/invoices?status=open&search=ADM%2F001')
            ->where('quick.message', '/school/communication/messages?to=' . $parentUser->id)
            ->where('quick.reportCard', null));
    }

    public function test_message_link_is_missing_when_the_parent_has_no_account(): void
    {
        $school = $this->makeSchool('Quick School');
        $admin = $this->makeUser($school, 'school-admin');
        $student = $this->student($school);

        $this->actingAs($admin)->get("/school/students/{$student->id}")->assertInertia(fn ($p) => $p->where('quick.message', null));
    }
}
