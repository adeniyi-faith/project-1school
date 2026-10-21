<?php

namespace Tests\Feature;

use App\Models\ScheduledTaskRun;
use App\Models\User;
use App\Services\ErrorLogReader;
use App\Services\SchedulerMonitor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\Security\SecurityTestCase;

/** Failed jobs, error log and scheduled tasks: owner only, read-only where promised, risky actions need a reason and are logged */
class PlatformOpsScreensTest extends SecurityTestCase
{
    private function owner(): User
    {
        $owner = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $owner->assignRole('super-admin');

        return $owner;
    }

    private function failedJob(): string
    {
        $uuid = (string) Str::uuid();
        DB::table('failed_jobs')->insert([
            'uuid' => $uuid, 'connection' => 'database', 'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\DeliverMessageBatch']),
            'exception' => "RuntimeException: boom with password=hunter2222 in file\n#0 stack", 'failed_at' => now(),
        ]);

        return $uuid;
    }

    public function test_only_the_owner_opens_these_pages(): void
    {
        $admin = $this->makeUser($this->makeSchool('A'), 'school-admin');
        $this->actingAs($admin);
        foreach (['failed-jobs', 'error-log', 'scheduled-tasks'] as $page) {
            $this->get("/super-admin/$page")->assertForbidden();
        }
        $this->actingAs($this->owner());
        foreach (['failed-jobs', 'error-log', 'scheduled-tasks'] as $page) {
            $this->get("/super-admin/$page")->assertOk();
        }
    }

    public function test_failed_jobs_list_hides_secrets_and_delete_needs_a_reason_and_is_logged(): void
    {
        $uuid = $this->failedJob();
        $this->actingAs($this->owner());

        $this->getJson('/super-admin/failed-jobs')->assertOk()
            ->assertJsonPath('jobs.total', 1)->assertJsonPath('jobs.data.0.job', 'App\\Jobs\\DeliverMessageBatch');
        $this->assertStringNotContainsString('hunter2222', $this->getJson('/super-admin/failed-jobs')->getContent());

        $this->delete("/super-admin/failed-jobs/$uuid")->assertSessionHasErrors('reason');
        $this->assertSame(1, DB::table('failed_jobs')->count());

        $this->delete("/super-admin/failed-jobs/$uuid", ['reason' => 'old test messages'])->assertSessionHasNoErrors();
        $this->assertSame(0, DB::table('failed_jobs')->count());
        $log = Activity::where('log_name', 'platform')->latest('id')->first();
        $this->assertSame('failed job deleted', $log->description);
        $this->assertSame('old test messages', $log->properties['reason']);
    }

    public function test_delete_all_needs_a_reason_and_unknown_ids_are_404(): void
    {
        $this->failedJob();
        $this->failedJob();
        $this->actingAs($this->owner());

        $this->post('/super-admin/failed-jobs/flush')->assertSessionHasErrors('reason');
        $this->delete('/super-admin/failed-jobs/not-a-uuid', ['reason' => 'because'])->assertNotFound();
        $this->post('/super-admin/failed-jobs/flush', ['reason' => 'clearing the test run'])->assertSessionHasNoErrors();
        $this->assertSame(0, DB::table('failed_jobs')->count());
    }

    public function test_the_error_log_groups_repeats_hides_secrets_and_filters(): void
    {
        $dir = storage_path('logs');
        @mkdir($dir, 0775, true);
        $file = "$dir/platform-test.log";
        file_put_contents($file, implode("\n", [
            '[2026-10-21 08:00:01] local.ERROR: Payment 101 failed token=abcdef123456 {"exception":"[object] (RuntimeException(code: 0): Payment 101 failed at /app/Pay.php:12)',
            '[stacktrace]',
            '#0 /app/Pay.php(12): run()',
            '[2026-10-21 08:05:01] local.ERROR: Payment 202 failed token=abcdef123456 {"exception":"[object] (RuntimeException(code: 0): Payment 202 failed at /app/Pay.php:12)',
            '[2026-10-21 08:06:00] local.INFO: just chatting',
            '',
        ]));

        try {
            $report = app(ErrorLogReader::class)->read(null, 'problems');
            $group = collect($report['groups'])->firstWhere('class', 'RuntimeException');
            $this->assertSame(2, $group['count']);
            $this->assertStringContainsString('Payment 202', $group['message']);
            $this->assertStringNotContainsString('abcdef123456', json_encode($report));
            $this->assertNull(collect($report['groups'])->first(fn ($g) => $g['level'] === 'info'));

            $this->assertNotEmpty(collect(app(ErrorLogReader::class)->read('just chatting', 'all')['groups']));
            $this->assertEmpty(app(ErrorLogReader::class)->read('no such text anywhere', 'all')['groups']);

            $this->actingAs($this->owner())->get('/super-admin/error-log?level=errors&q=Payment')->assertOk()
                ->assertInertia(fn ($p) => $p->component('SuperAdmin/ErrorLog/Index', false)->where('filters.q', 'Payment'));
        } finally {
            unlink($file);
        }
    }

    public function test_the_tracker_button_only_accepts_https(): void
    {
        $this->actingAs($this->owner());
        config(['platform_tools.error_tracker_url' => 'javascript:alert(1)']);
        $this->get('/super-admin/error-log')->assertInertia(fn ($p) => $p->where('trackerUrl', null));
        config(['platform_tools.error_tracker_url' => 'https://sentry.io/x']);
        $this->get('/super-admin/error-log')->assertInertia(fn ($p) => $p->where('trackerUrl', 'https://sentry.io/x'));
    }

    public function test_scheduled_tasks_show_the_cron_heartbeat(): void
    {
        $this->actingAs($this->owner());
        $this->getJson('/super-admin/scheduled-tasks')->assertOk()->assertJsonPath('alive', false);

        SchedulerMonitor::beat();
        ScheduledTaskRun::create(['name' => 'fees:remind', 'started_at' => now(), 'duration_ms' => 40, 'status' => 'failed', 'output' => 'oops']);
        $this->getJson('/super-admin/scheduled-tasks')->assertOk()->assertJsonPath('alive', true)->assertJsonPath('failed_week', 1)
            ->assertJsonPath('runs.0.name', 'fees:remind');
    }
}
