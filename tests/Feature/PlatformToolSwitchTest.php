<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;

/** A developer tool switched off in the environment has no address and no menu item */
class PlatformToolSwitchTest extends SecurityTestCase
{
    protected function setUp(): void
    {
        // routes are decided when the app starts, so the switch is set before it starts
        putenv('PLATFORM_TOOL_ERROR_LOG=false');
        parent::setUp();

        // the menu's route list is remembered between tests; forget it so it is built from this app
        $rules = new \ReflectionProperty(\App\Support\NavAccess::class, 'rules');
        $rules->setValue(null, null);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('PLATFORM_TOOL_ERROR_LOG');
        (new \ReflectionProperty(\App\Support\NavAccess::class, 'rules'))->setValue(null, null);
    }

    public function test_switched_off_tool_is_gone_but_the_others_remain(): void
    {
        $owner = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $owner->assignRole('super-admin');

        $this->actingAs($owner)->get('/super-admin/error-log')->assertNotFound();
        $this->assertNotContains('/super-admin/error-log', \App\Support\NavAccess::paths($owner));
        $this->assertContains('/super-admin/failed-jobs', \App\Support\NavAccess::paths($owner));
        $this->get('/super-admin/failed-jobs')->assertOk();
    }
}
