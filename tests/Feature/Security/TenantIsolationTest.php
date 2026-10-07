<?php

namespace Tests\Feature\Security;

use App\Models\AcademicYear;
use App\Models\Staff;
use App\Models\StaffDocument;
use Illuminate\Support\Facades\Storage;

class TenantIsolationTest extends SecurityTestCase
{
    public function test_academic_years_only_show_the_logged_in_users_school(): void
    {
        $a = $this->makeSchool('School A');
        $b = $this->makeSchool('School B');

        AcademicYear::create(['school_id' => $a->id, 'name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31']);
        AcademicYear::create(['school_id' => $b->id, 'name' => 'B Year', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31']);

        $this->actingAs($this->makeUser($a, 'school-admin'));

        $this->assertSame(['2026/2027'], AcademicYear::pluck('name')->all());
    }

    public function test_staff_documents_only_show_the_logged_in_users_school(): void
    {
        $a = $this->makeSchool('School A');
        $b = $this->makeSchool('School B');

        $this->makeDocument($a, 'A doc');
        $this->makeDocument($b, 'B doc');

        $this->actingAs($this->makeUser($a, 'school-admin'));

        $this->assertSame(['A doc'], StaffDocument::pluck('title')->all());
    }

    public function test_super_admin_still_sees_every_school(): void
    {
        $a = $this->makeSchool('School A');
        $b = $this->makeSchool('School B');
        $this->makeDocument($a, 'A doc');
        $this->makeDocument($b, 'B doc');

        $super = \App\Models\User::factory()->create(['school_id' => null, 'status' => 'active']);
        $super->assignRole('super-admin');
        $this->actingAs($super);

        $this->assertCount(2, StaffDocument::all());
    }

    public function test_school_admin_cannot_delete_another_schools_staff_document(): void
    {
        $a = $this->makeSchool('School A');
        $b = $this->makeSchool('School B');
        $docB = $this->makeDocument($b, 'B doc');

        $this->actingAs($this->makeUser($a, 'school-admin'))
            ->delete(route('school.staff.documents.delete', $docB->id))
            ->assertNotFound();

        $this->assertDatabaseHas('staff_documents', ['id' => $docB->id]);
    }

    public function test_school_admin_can_delete_their_own_staff_document(): void
    {
        Storage::fake('private');
        $a = $this->makeSchool('School A');
        $docA = $this->makeDocument($a, 'A doc');

        $this->actingAs($this->makeUser($a, 'school-admin'))
            ->delete(route('school.staff.documents.delete', $docA->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('staff_documents', ['id' => $docA->id]);
    }

    private function makeDocument($school, string $title): StaffDocument
    {
        $staff = Staff::withoutGlobalScopes()->create([
            'school_id'  => $school->id,
            'emp_id'     => 'E' . $school->id,
            'first_name' => 'Test',
            'last_name'  => 'Staff',
            'status'     => 'active',
            'salary_type' => 'fixed',
            'gender' => 'male',
        ]);

        return StaffDocument::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'staff_id'  => $staff->id,
            'title'     => $title,
            'file_path' => "staff/{$staff->id}/documents/x.pdf",
            'file_type' => 'application/pdf',
            'file_size' => 10,
        ]);
    }
}
