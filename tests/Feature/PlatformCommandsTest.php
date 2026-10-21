<?php

namespace Tests\Feature;

use App\Models\PlatformCommandRun;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\Security\SecurityTestCase;

/** The one-click commands: a fixed list, owner only, a reason for anything that changes data, every run recorded */
class PlatformCommandsTest extends SecurityTestCase
{
    private function owner(): User
    {
        $owner = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $owner->assignRole('super-admin');

        return $owner;
    }

    public function test_only_the_owner_can_see_or_run_them(): void
    {
        $this->actingAs($this->makeUser($this->makeSchool('A'), 'school-admin'));
        $this->get('/super-admin/commands')->assertForbidden();
        $this->post('/super-admin/commands/clear-caches/run', ['reason' => 'just testing'])->assertForbidden();
        $this->assertSame(0, PlatformCommandRun::count());
    }

    public function test_a_command_that_changes_things_needs_a_reason(): void
    {
        $this->actingAs($this->owner());
        $this->post('/super-admin/commands/clear-caches/run')->assertSessionHasErrors('reason');
        $this->post('/super-admin/commands/clear-caches/run', ['reason' => 'abc'])->assertSessionHasErrors('reason');
        $this->assertSame(0, PlatformCommandRun::count());
    }

    public function test_a_run_is_recorded_with_who_why_and_output_and_audited(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->post('/super-admin/commands/reseed-roles/run', ['reason' => 'new permission missing'])->assertSessionHasNoErrors();

        $run = PlatformCommandRun::first();
        $this->assertSame('reseed-roles', $run->command);
        $this->assertSame($owner->id, $run->user_id);
        $this->assertSame('new permission missing', $run->reason);
        $this->assertSame('ok', $run->status);

        $log = Activity::where('log_name', 'platform')->latest('id')->first();
        $this->assertSame('new permission missing', $log->properties['reason']);
        $this->assertSame('reseed-roles', $log->properties['command']);
    }

    public function test_look_only_commands_need_no_reason_and_change_nothing(): void
    {
        $this->actingAs($this->owner())->post('/super-admin/commands/migration-status/run')->assertSessionHasNoErrors();
        $this->post('/super-admin/commands/fees-check/run')->assertSessionHasNoErrors();

        $runs = PlatformCommandRun::all();
        $this->assertCount(2, $runs);
        $this->assertStringContainsString('create_scheduled_task_runs', $runs->firstWhere('command', 'migration-status')->output);
    }

    public function test_nothing_outside_the_list_can_be_run(): void
    {
        $this->actingAs($this->owner())->post('/super-admin/commands/migrate:fresh/run', ['reason' => 'wipe everything'])->assertNotFound();
        $this->post('/super-admin/commands/db:wipe/run', ['reason' => 'wipe everything'])->assertNotFound();
        $this->assertSame(0, PlatformCommandRun::count());
    }

    public function test_data_changing_commands_can_be_switched_off(): void
    {
        config(['platform_tools.commands_change_data' => false]);
        $this->actingAs($this->owner());

        $this->post('/super-admin/commands/clear-caches/run', ['reason' => 'should be refused'])->assertForbidden();
        $this->post('/super-admin/commands/migration-status/run')->assertSessionHasNoErrors();
        $this->getJson('/super-admin/commands')->assertJsonPath('commands.0.enabled', false);
    }
}
