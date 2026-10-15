<?php

namespace Tests\Feature\Security;

use App\Models\Announcement;
use App\Models\Hostel;
use App\Models\HostelRoom;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\School;
use App\Models\TransportRoute;
use App\Models\Vehicle;
use App\Models\VisitorLog;

/** Front desk, drivers, wardens and store keepers reach their own screens, and nothing else */
class StaffRoleAccessTest extends SecurityTestCase
{
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('Staff School');
    }

    public static function roles(): array
    {
        return [
            'receptionist' => ['receptionist', 'front_desk', ['school.admissions.visitors', 'school.admissions.inquiries', 'school.students.index']],
            'driver' => ['driver', 'transport', ['school.transport.routes', 'school.transport.vehicles']],
            'warden' => ['warden', 'hostel', ['school.hostel.index', 'school.hostel.allocations']],
            'store manager' => ['store-manager', 'store', ['school.inventory.items', 'school.inventory.issues']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('roles')]
    public function test_each_role_lands_on_its_own_home_and_opens_its_screens_but_not_others(string $role, string $panel, array $screens): void
    {
        $this->actingAs($this->makeUser($this->school, $role));

        $this->get('/dashboard')->assertRedirect(route('school.home'));
        $this->get('/school/home')->assertOk()->assertInertia(fn ($p) => $p
            ->component('SchoolAdmin/StaffHome', false)
            ->has("panels.{$panel}")
            ->where('panels', fn ($panels) => count($panels) === 1));

        foreach ($screens as $name) {
            $this->get(route($name))->assertOk();
        }
        foreach (['school.fees.invoices.index', 'school.reports.dashboard', 'school.hr.payroll.index', 'school.settings.index'] as $name) {
            $this->get(route($name))->assertForbidden();
        }
        $this->get('/school/communication/notifications')->assertOk();
    }

    public function test_the_home_page_shows_this_schools_work_only(): void
    {
        $other = $this->makeSchool('Other School');
        foreach ([$this->school, $other] as $school) {
            VisitorLog::withoutGlobalScopes()->create(['school_id' => $school->id, 'name' => 'Visitor '.$school->id, 'phone' => '080', 'purpose' => 'Meeting', 'person_to_meet' => 'Principal', 'time_in' => now()]);
            $bus = Vehicle::withoutGlobalScopes()->create(['school_id' => $school->id, 'registration_no' => 'LAG-'.$school->id, 'name' => 'Bus 1', 'driver_name' => 'Mr Bello']);
            TransportRoute::withoutGlobalScopes()->create(['school_id' => $school->id, 'vehicle_id' => $bus->id, 'name' => 'Route '.$school->id, 'start_point' => 'Ikeja', 'end_point' => 'School']);
            $hostel = Hostel::withoutGlobalScopes()->create(['school_id' => $school->id, 'name' => 'Hall '.$school->id]);
            HostelRoom::withoutGlobalScopes()->create(['school_id' => $school->id, 'hostel_id' => $hostel->id, 'room_no' => '1', 'capacity' => 4, 'occupied' => 3]);
            $cat = InventoryCategory::withoutGlobalScopes()->create(['school_id' => $school->id, 'name' => 'Stationery']);
            InventoryItem::withoutGlobalScopes()->create(['school_id' => $school->id, 'category_id' => $cat->id, 'name' => 'Chalk '.$school->id, 'current_stock' => 2, 'minimum_stock' => 5]);
        }
        $admin = $this->makeUser($this->school, 'school-admin');
        Announcement::withoutGlobalScopes()->create(['school_id' => $this->school->id, 'author_id' => $admin->id, 'title' => 'Sports day', 'body' => 'Friday', 'audience' => 'all', 'published_at' => now()->subHour()]);
        Announcement::withoutGlobalScopes()->create(['school_id' => $this->school->id, 'author_id' => $admin->id, 'title' => 'Teachers only', 'body' => 'x', 'audience' => 'role', 'target_role' => 'teacher', 'published_at' => now()->subHour()]);

        $this->actingAs($this->makeUser($this->school, 'receptionist'));
        $this->get('/school/home')->assertInertia(fn ($p) => $p
            ->where('panels.front_desk.visitors_today', 1)->where('panels.front_desk.inside.0.name', 'Visitor '.$this->school->id)
            ->has('announcements', 1)->where('announcements.0.title', 'Sports day'));

        $this->actingAs($this->makeUser($this->school, 'driver'));
        $this->get('/school/home')->assertInertia(fn ($p) => $p->has('panels.transport.routes', 1)->where('panels.transport.routes.0.driver', 'Mr Bello'));
        // Drivers read announcements but cannot post or remove them
        $this->get('/school/communication/announcements')->assertOk()->assertInertia(fn ($p) => $p->where('can.create', false)->where('can.delete', false));

        $this->actingAs($this->makeUser($this->school, 'warden'));
        $this->get('/school/home')->assertInertia(fn ($p) => $p->where('panels.hostel.beds', 4)->where('panels.hostel.occupied', 3));

        $this->actingAs($this->makeUser($this->school, 'store-manager'));
        $this->get('/school/home')->assertInertia(fn ($p) => $p->where('panels.store.low_count', 1)->where('panels.store.low.0.name', 'Chalk '.$this->school->id));
    }

    public function test_staff_with_the_dashboard_still_go_there(): void
    {
        $this->actingAs($this->makeUser($this->school, 'principal'));
        $this->get('/dashboard')->assertRedirect(route('school.reports.dashboard'));
    }

    public function test_teachers_start_on_their_own_classes_page(): void
    {
        $this->actingAs($this->makeUser($this->school, 'teacher'));
        $this->get('/dashboard')->assertRedirect(route('school.my-classes'));
    }
}
