<?php

namespace Tests\Feature;

use App\Models\School;
use Database\Seeders\DemoSchoolSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoSchoolSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_demo_school_is_filled_with_a_believable_nigerian_school(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config(['app.demo_email' => 'demo@schoolruns.demo']);

        $this->seed(DemoSchoolSeeder::class);

        $school = School::where('slug', 'demo-school')->firstOrFail();
        $this->assertSame('NGN', $school->currency);

        $classes = DB::table('classes')->where('school_id', $school->id)->orderBy('numeric_name')->pluck('name')->all();
        $this->assertSame('Creche', $classes[0]);
        $this->assertSame('SS 3', end($classes));
        $this->assertCount(16, $classes);

        $students = DB::table('students')->where('school_id', $school->id);
        $this->assertGreaterThan(300, $students->count());
        $this->assertSame(0, DB::table('students')->where('school_id', $school->id)->where('nationality', '!=', 'Nigerian')->count());

        // every student has a guardian, a class, attendance and results
        $this->assertSame(0, DB::table('students')->where('school_id', $school->id)->whereNull('guardian_id')->count());
        $this->assertGreaterThan(5000, DB::table('attendances')->where('school_id', $school->id)->count());
        $this->assertGreaterThan(5000, DB::table('marks')->where('school_id', $school->id)->count());
        $this->assertGreaterThan(1000, DB::table('fee_payments')->where('school_id', $school->id)->count());
        $this->assertGreaterThan(500, DB::table('timetables')->where('school_id', $school->id)->count());
        $this->assertGreaterThan(40, DB::table('staff')->where('school_id', $school->id)->count());

        // last session's Third Term results are published with positions; this term is still in draft
        $this->assertSame(16, DB::table('result_sheets')->where('school_id', $school->id)->where('status', 'locked')->count());
        $this->assertSame(16, DB::table('result_sheets')->where('school_id', $school->id)->where('status', 'draft')->count());
        $this->assertGreaterThan(300, DB::table('term_result_summaries')->where('school_id', $school->id)->whereNotNull('position')->count());
        $this->assertGreaterThan(1000, DB::table('behaviour_ratings')->where('school_id', $school->id)->count());

        // some of the names are really Nigerian, from several parts of the country
        $surnames = DB::table('students')->where('school_id', $school->id)->pluck('last_name')->unique()->all();
        $this->assertGreaterThan(60, count($surnames));
    }

    public function test_running_it_again_replaces_the_demo_data_and_never_touches_another_school(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config(['app.demo_email' => 'demo@schoolruns.demo']);

        $other = School::create(['name' => 'Real School', 'slug' => 'real-school', 'email' => 'r@example.test', 'status' => 'active']);
        $classId = DB::table('classes')->insertGetId(['school_id' => $other->id, 'name' => 'Real Class', 'created_at' => now(), 'updated_at' => now()]);

        $this->seed(DemoSchoolSeeder::class);
        $first = DB::table('students')->count();
        $this->seed(DemoSchoolSeeder::class);

        $this->assertSame($first, DB::table('students')->count());
        $this->assertTrue(DB::table('classes')->where('id', $classId)->exists());
    }

    public function test_the_main_screens_open_for_the_demo_admin_once_filled(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config(['app.demo_enabled' => true, 'app.demo_email' => 'demo@schoolruns.demo']);
        $this->seed(DemoSchoolSeeder::class);

        $this->post('/demo/admin')->assertRedirect(route('dashboard'));

        foreach ([
            'school.reports.dashboard', 'school.students.index', 'school.staff.index', 'school.classes.index',
            'school.attendance.index', 'school.exams.index', 'school.fees.payments.index', 'school.fees.structures.index',
            'school.timetable.index', 'school.homework.index', 'school.library.books.index', 'school.holidays.index',
        ] as $route) {
            $this->get(route($route), ['X-Inertia' => 'true'])->assertOk();
        }
    }

    public function test_every_demo_account_type_opens_its_own_home_and_stays_read_only(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config(['app.demo_enabled' => true, 'app.demo_email' => 'demo@schoolruns.demo']);
        $this->seed(DemoSchoolSeeder::class);

        // The picker lists every account type, with parent and student available once pupils exist
        $this->get('/demo')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Auth/DemoPick', false)
            ->has('accounts', count(\App\Support\DemoSchool::ACCOUNTS))
            ->where('accounts.4.key', 'parent')->where('accounts.4.available', true)
            ->where('accounts.5.key', 'student')->where('accounts.5.available', true));

        foreach (array_keys(\App\Support\DemoSchool::ACCOUNTS) as $key) {
            // Someone already in the demo switches straight to the next account type
            $this->post("/demo/{$key}")->assertRedirect(route('dashboard'));
            $user = auth()->user();
            $this->assertSame(\App\Support\DemoSchool::ACCOUNTS[$key][0], $user->roles()->value('name'));
            $this->followingRedirects()->get('/dashboard')->assertOk()
                ->assertInertia(fn ($page) => $page->where('demo.label', \App\Support\DemoSchool::ACCOUNTS[$key][1]));
        }

        // The student account sees one real pupil, and the parent account is that pupil's parent
        $student = \App\Models\Student::withoutGlobalScopes()->where('user_id', \App\Models\User::where('email', 'demo-student@schoolruns.demo')->value('id'))->sole();
        $guardian = \App\Models\Guardian::withoutGlobalScopes()->find($student->guardian_id);
        $this->assertSame(\App\Models\User::where('email', 'demo-parent@schoolruns.demo')->value('id'), $guardian->user_id);

        $this->post('/demo/student');
        $this->get(route('student.dashboard'), ['X-Inertia' => 'true'])->assertOk();
        $this->from('/profile')->put('/profile', ['name' => 'Changed'])->assertSessionHas('error');
        $this->assertSame($student->full_name, auth()->user()->fresh()->name);

        // Coming back does not link a second pupil
        $this->post('/demo/student');
        $this->assertSame(1, \App\Models\Student::withoutGlobalScopes()->where('user_id', auth()->id())->count());
    }

    public function test_demo_fill_does_nothing_when_the_demo_is_off_and_only_fills_once_when_on(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config(['app.demo_email' => 'demo@schoolruns.demo', 'app.demo_enabled' => false]);

        $this->artisan('demo:fill')->assertSuccessful();
        $this->assertSame(0, DB::table('students')->count());

        config(['app.demo_enabled' => true]);
        $this->artisan('demo:fill')->assertSuccessful();
        $first = DB::table('students')->count();
        $this->assertGreaterThan(300, $first);

        // a second run keeps the same data instead of rebuilding it
        $firstId = DB::table('students')->min('id');
        $this->artisan('demo:fill')->assertSuccessful();
        $this->assertSame($firstId, DB::table('students')->min('id'));
    }

    public function test_demo_fill_also_fills_the_side_departments_and_every_bell_has_notices(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config(['app.demo_email' => 'demo@schoolruns.demo', 'app.demo_enabled' => true]);

        $this->artisan('demo:fill')->assertSuccessful();

        foreach (['hostels', 'hostel_allocations', 'vehicles', 'routes', 'student_route', 'book_issues', 'inventory_items', 'assets', 'visitor_logs', 'admission_inquiries'] as $table) {
            $this->assertGreaterThan(0, DB::table($table)->count(), "{$table} is empty");
        }
        $this->assertGreaterThan(0, DB::table('inventory_items')->whereColumn('current_stock', '<=', 'minimum_stock')->count());
        $this->assertSame(4, DB::table('vehicles')->count());

        // running it again (as every deploy does) does not double anything
        $this->artisan('demo:fill')->assertSuccessful();
        $this->assertSame(4, DB::table('vehicles')->count());

        foreach (array_keys(\App\Support\DemoSchool::ACCOUNTS) as $key) {
            $this->post("/demo/{$key}")->assertRedirect(route('dashboard'));
            $url = match ($key) {
                'student' => '/school/student/notifications/feed',
                'parent' => '/school/parent/notifications/feed',
                default => '/school/communication/notifications/feed',
            };
            $items = $this->getJson($url)->assertOk()->json('items');
            $this->assertNotEmpty($items, "{$key} has an empty bell");
        }
    }
}
