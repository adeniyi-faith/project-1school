<?php

namespace Tests\Feature;

use App\Models\School;
use Database\Seeders\DemoExtrasSeeder;
use Database\Seeders\DemoRichSeeder;
use Database\Seeders\DemoSchoolSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** The demo school's formerly empty screens (lessons, tests, houses, leave ...) are filled, and filling again changes nothing */
class DemoRichSeederTest extends TestCase
{
    use RefreshDatabase;

    private function filledDemo(): School
    {
        $this->seed(RolePermissionSeeder::class);
        config(['app.demo_enabled' => true, 'app.demo_email' => 'demo@schoolruns.demo']);
        $this->seed(DemoSchoolSeeder::class);
        $this->seed(DemoExtrasSeeder::class);
        $this->seed(DemoRichSeeder::class);

        return School::where('slug', 'demo-school')->firstOrFail();
    }

    public function test_the_demo_has_real_nigerian_subjects_and_every_listed_screen_has_rows(): void
    {
        $school = $this->filledDemo();
        $count = fn (string $table) => DB::table($table)->where('school_id', $school->id)->count();

        // Subjects from the real JSS 1 and Primary timetables
        $subjects = DB::table('subjects')->where('school_id', $school->id)->pluck('name')->all();
        foreach (['Intermediate Science', 'Digital Technologies', 'Social and Citizenship Studies', 'History', 'Business Studies', 'Phonics', 'Pre-vocational Studies', 'Basic Digital Literacy', 'Handwriting', 'Dictation', 'Fellowship', 'Verbal Reasoning', 'Quantitative Reasoning'] as $name) {
            $this->assertContains($name, $subjects, $name);
        }

        foreach (['shifts', 'leave_requests', 'payrolls', 'salary_structures', 'messages', 'message_deliveries', 'syllabi', 'online_classes', 'homework_submissions',
            'scholarships', 'student_scholarships', 'payment_plans', 'invoice_instalments', 'houses', 'house_points', 'clubs', 'club_members', 'schemes_of_work', 'lesson_plans',
            'cbt_banks', 'cbt_questions', 'cbt_tests', 'cbt_attempts', 'exam_papers', 'report_card_comment_bank', 'report_card_remarks', 'student_id_cards', 'student_certificates'] as $table) {
            $this->assertGreaterThan(0, $count($table), $table);
        }
        $this->assertGreaterThan(100, $count('cbt_questions'));
        $this->assertSame(0, DB::table('students')->where('school_id', $school->id)->whereNull('house_id')->count());

        // Finished tests carry scores; some theory answers still wait for marking
        $this->assertGreaterThan(0, DB::table('cbt_attempts')->where('school_id', $school->id)->where('status', 'submitted')->whereNotNull('score')->count());
        $this->assertGreaterThan(0, DB::table('cbt_attempts')->where('school_id', $school->id)->where('needs_marking', true)->count());
    }

    public function test_filling_again_does_not_double_anything_and_leaves_other_schools_alone(): void
    {
        $school = $this->filledDemo();
        $other = School::create(['name' => 'Real School', 'slug' => 'real-school', 'email' => 'r@example.test', 'status' => 'active']);
        $houseId = DB::table('houses')->insertGetId(['school_id' => $other->id, 'name' => 'Real House', 'colour' => '#000000', 'created_at' => now(), 'updated_at' => now()]);

        $tables = ['leave_requests', 'payrolls', 'messages', 'houses', 'club_members', 'lesson_plans', 'cbt_questions', 'cbt_tests', 'invoice_instalments', 'student_id_cards'];
        $before = array_map(fn ($t) => DB::table($t)->where('school_id', $school->id)->count(), $tables);

        $this->seed(DemoRichSeeder::class);

        $after = array_map(fn ($t) => DB::table($t)->where('school_id', $school->id)->count(), $tables);
        $this->assertSame(array_combine($tables, $before)['cbt_questions'], array_combine($tables, $after)['cbt_questions']);
        foreach (['leave_requests', 'payrolls', 'houses', 'lesson_plans', 'cbt_tests', 'student_id_cards'] as $t) {
            $this->assertSame(array_combine($tables, $before)[$t], array_combine($tables, $after)[$t], $t);
        }
        $this->assertTrue(DB::table('houses')->where('id', $houseId)->exists());
    }
}
