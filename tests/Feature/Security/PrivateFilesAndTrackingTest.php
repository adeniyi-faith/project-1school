<?php

namespace Tests\Feature\Security;

use App\Models\Staff;
use App\Models\StaffDocument;
use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PrivateFilesAndTrackingTest extends SecurityTestCase
{
    public function test_private_disk_is_configured(): void
    {
        $this->assertNotNull(config('filesystems.disks.private.driver'));
    }

    public function test_staff_document_can_be_uploaded_and_downloaded_by_its_own_school(): void
    {
        Storage::fake('private');
        $school = $this->makeSchool('School A');
        $admin = $this->makeUser($school, 'school-admin');
        $staff = $this->makeStaff($school);

        $this->actingAs($admin)
            ->post(route('school.staff.documents.upload', $staff->id), [
                'title' => 'Contract',
                'file'  => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
            ])->assertRedirect();

        $doc = StaffDocument::firstOrFail();
        Storage::disk('private')->assertExists($doc->file_path);

        $this->get($doc->file_url)->assertOk();
    }

    public function test_other_school_cannot_download_a_staff_document(): void
    {
        Storage::fake('private');
        $a = $this->makeSchool('School A');
        $b = $this->makeSchool('School B');

        $staff = $this->makeStaff($a);
        $doc = StaffDocument::withoutGlobalScopes()->create([
            'school_id' => $a->id, 'staff_id' => $staff->id, 'title' => 'Secret',
            'file_path' => 'staff/1/documents/s.pdf', 'file_type' => 'application/pdf', 'file_size' => 1,
        ]);
        Storage::disk('private')->put($doc->file_path, 'x');

        $this->actingAs($this->makeUser($b, 'school-admin'))
            ->get(route('school.staff.documents.download', $doc->id))
            ->assertNotFound();
    }

    public function test_gps_webhook_rejects_calls_without_the_right_token(): void
    {
        $school = $this->makeSchool('School A');
        $vehicle = $this->makeVehicle($school);

        $this->postJson(route('webhooks.vehicle-location', $vehicle->id), ['lat' => 6.5, 'lng' => 3.3])
            ->assertUnauthorized();

        $this->withToken('wrong-token')
            ->postJson(route('webhooks.vehicle-location', $vehicle->id), ['lat' => 6.5, 'lng' => 3.3])
            ->assertUnauthorized();

        $this->assertNull($vehicle->fresh()->last_lat);
    }

    public function test_gps_webhook_accepts_the_right_token_and_saves_the_location(): void
    {
        $school = $this->makeSchool('School A');
        $vehicle = $this->makeVehicle($school);

        $this->withToken($vehicle->tracking_token)
            ->postJson(route('webhooks.vehicle-location', $vehicle->id), ['lat' => 6.5, 'lng' => 3.3])
            ->assertOk();

        $this->assertEquals(6.5, (float) $vehicle->fresh()->last_lat);
    }

    public function test_one_vehicles_token_does_not_work_for_another_vehicle(): void
    {
        $school = $this->makeSchool('School A');
        $one = $this->makeVehicle($school);
        $two = $this->makeVehicle($school);

        $this->withToken($one->tracking_token)
            ->postJson(route('webhooks.vehicle-location', $two->id), ['lat' => 1, 'lng' => 1])
            ->assertUnauthorized();
    }

    public function test_vehicle_token_is_never_sent_to_the_browser(): void
    {
        $vehicle = $this->makeVehicle($this->makeSchool('School A'));

        $this->assertArrayNotHasKey('tracking_token', $vehicle->toArray());
    }

    private function makeStaff($school): Staff
    {
        return Staff::withoutGlobalScopes()->create([
            'school_id' => $school->id, 'emp_id' => 'E' . $school->id, 'first_name' => 'T',
            'last_name' => 'S', 'status' => 'active', 'salary_type' => 'fixed', 'gender' => 'male',
        ]);
    }

    private function makeVehicle($school): Vehicle
    {
        return Vehicle::withoutGlobalScopes()->create([
            'school_id' => $school->id, 'registration_no' => 'ABC-' . random_int(100, 999),
        ]);
    }
}
