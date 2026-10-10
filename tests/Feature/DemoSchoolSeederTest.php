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

        $this->get('/demo')->assertRedirect(route('dashboard'));

        foreach ([
            'school.reports.dashboard', 'school.students.index', 'school.staff.index', 'school.classes.index',
            'school.attendance.index', 'school.exams.index', 'school.fees.payments.index', 'school.fees.structures.index',
            'school.timetable.index', 'school.homework.index', 'school.library.books.index', 'school.holidays.index',
        ] as $route) {
            $this->get(route($route), ['X-Inertia' => 'true'])->assertOk();
        }
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
}
