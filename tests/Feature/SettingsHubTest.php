<?php

namespace Tests\Feature;

use Tests\Feature\Security\SecurityTestCase;

/** The Settings front page: one card per settings screen. */
class SettingsHubTest extends SecurityTestCase
{
    public function test_school_admin_can_open_the_settings_hub(): void
    {
        $admin = $this->makeUser($this->makeSchool('Hub School'), 'school-admin');

        $this->actingAs($admin)->get('/school/settings/all')->assertOk()->assertInertia(fn ($p) => $p->component('SchoolAdmin/Settings/Hub'));
    }

    public function test_people_without_settings_access_cannot_open_it(): void
    {
        $driver = $this->makeUser($this->makeSchool('Hub School'), 'driver');

        $this->actingAs($driver)->get('/school/settings/all')->assertForbidden();
    }
}
