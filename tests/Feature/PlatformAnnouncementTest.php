<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\PlatformAnnouncement;
use App\Models\SchoolNotification;
use App\Models\SchoolSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Security\SecurityTestCase;

/** Platform announcements: banner, bell and email to the right schools, and maintenance warnings */
class PlatformAnnouncementTest extends SecurityTestCase
{
    private function owner(): User
    {
        $owner = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $owner->assignRole('super-admin');

        return $owner;
    }

    private function make(array $over = []): array
    {
        return array_merge(['title' => 'New feature', 'body' => 'Report cards got faster', 'kind' => 'notice', 'audience' => 'all', 'show_banner' => true, 'send_bell' => false, 'send_email' => false], $over);
    }

    public function test_a_draft_sends_nothing_and_shows_nowhere(): void
    {
        $a = $this->makeSchool('School A');
        $admin = $this->makeUser($a, 'school-admin');
        $this->actingAs($this->owner())->post('/super-admin/announcements', $this->make(['send_bell' => true]))->assertSessionHasNoErrors();

        $this->assertSame('draft', PlatformAnnouncement::first()->status);
        $this->assertSame(0, SchoolNotification::withoutGlobalScopes()->count());
        $this->actingAs($admin)->get('/school/reports/dashboard')->assertInertia(fn ($p) => $p->where('platform_notices', []));
    }

    public function test_publishing_shows_a_banner_and_sends_bell_notices_and_emails_to_admins_only(): void
    {
        Mail::fake();
        $a = $this->makeSchool('School A');
        $admin = $this->makeUser($a, 'school-admin');
        $this->makeUser($a, 'teacher');

        $this->actingAs($this->owner())->post('/super-admin/announcements', $this->make(['send_bell' => true, 'send_email' => true, 'publish' => true]))->assertSessionHas('success');

        $row = PlatformAnnouncement::first();
        $this->assertSame('published', $row->status);
        $this->assertSame(1, $row->bell_count);
        $this->assertSame(1, $row->email_count);
        $this->assertSame([$admin->id], SchoolNotification::withoutGlobalScopes()->pluck('user_id')->all());

        $this->actingAs($admin)->get('/school/reports/dashboard')->assertInertia(fn ($p) => $p->where('platform_notices.0.title', 'New feature'));
    }

    public function test_it_only_reaches_the_chosen_schools_or_plan(): void
    {
        $a = $this->makeSchool('School A');
        $b = $this->makeSchool('School B');
        $adminA = $this->makeUser($a, 'school-admin');
        $adminB = $this->makeUser($b, 'school-admin');
        $owner = $this->owner();

        $this->actingAs($owner)->post('/super-admin/announcements', $this->make(['audience' => 'schools', 'school_ids' => [$a->id], 'publish' => true]))->assertSessionHas('success');
        $this->actingAs($adminA)->get('/school/reports/dashboard')->assertInertia(fn ($p) => $p->has('platform_notices', 1));
        $this->actingAs($adminB)->get('/school/reports/dashboard')->assertInertia(fn ($p) => $p->where('platform_notices', []));

        $package = Package::create(['name' => 'Pro', 'slug' => 'pro', 'price_monthly' => 1, 'price_yearly' => 1, 'max_students' => 1, 'max_staff' => 1, 'storage_gb' => 1, 'is_active' => true]);
        SchoolSubscription::create(['school_id' => $b->id, 'package_id' => $package->id, 'start_date' => now(), 'end_date' => now()->addYear(), 'status' => 'active', 'amount_paid' => 0]);
        $this->actingAs($owner)->post('/super-admin/announcements', $this->make(['title' => 'Pro only', 'audience' => 'package', 'package_id' => $package->id, 'publish' => true]))->assertSessionHas('success');
        $this->actingAs($adminB)->get('/school/reports/dashboard')->assertInertia(fn ($p) => $p->where('platform_notices.0.title', 'Pro only'));
        $this->actingAs($adminA)->get('/school/reports/dashboard')->assertInertia(fn ($p) => $p->has('platform_notices', 1));
    }

    public function test_maintenance_needs_a_window_and_shows_ahead_of_it_then_ends_with_it(): void
    {
        $a = $this->makeSchool('School A');
        $admin = $this->makeUser($a, 'school-admin');
        $owner = $this->owner();

        $this->actingAs($owner)->post('/super-admin/announcements', $this->make(['kind' => 'maintenance']))->assertSessionHasErrors('starts_at');
        $this->post('/super-admin/announcements', $this->make(['kind' => 'maintenance', 'starts_at' => '2026-12-02 22:00', 'ends_at' => '2026-12-02 21:00']))->assertSessionHasErrors('ends_at');

        $this->post('/super-admin/announcements', $this->make(['kind' => 'maintenance', 'title' => 'Upgrade', 'starts_at' => now()->addDays(2)->format('Y-m-d H:i'), 'ends_at' => now()->addDays(2)->addHour()->format('Y-m-d H:i'), 'publish' => true]))->assertSessionHas('success');
        $this->actingAs($admin)->get('/school/reports/dashboard')->assertInertia(fn ($p) => $p->where('platform_notices.0.kind', 'maintenance'));

        // Once the work window is over the banner goes by itself
        PlatformAnnouncement::first()->update(['ends_at' => now()->subMinute()]);
        $this->get('/school/reports/dashboard')->assertInertia(fn ($p) => $p->where('platform_notices', []));
    }

    public function test_published_notices_cannot_be_edited_and_ending_removes_the_banner(): void
    {
        $a = $this->makeSchool('School A');
        $admin = $this->makeUser($a, 'school-admin');
        $owner = $this->owner();
        $this->actingAs($owner)->post('/super-admin/announcements', $this->make(['publish' => true]));
        $row = PlatformAnnouncement::first();

        $this->put("/super-admin/announcements/{$row->id}", $this->make(['title' => 'Changed']))->assertSessionHas('error');
        $this->assertSame('New feature', $row->fresh()->title);
        $this->post("/super-admin/announcements/{$row->id}/publish")->assertSessionHas('error');
        $this->delete("/super-admin/announcements/{$row->id}")->assertSessionHas('error');

        $this->patch("/super-admin/announcements/{$row->id}/end")->assertSessionHas('success');
        $this->actingAs($admin)->get('/school/reports/dashboard')->assertInertia(fn ($p) => $p->where('platform_notices', []));
    }

    public function test_only_the_platform_team_can_manage_announcements(): void
    {
        $a = $this->makeSchool('School A');
        $this->actingAs($this->makeUser($a, 'school-admin'))->get('/super-admin/announcements')->assertForbidden();
        $this->post('/super-admin/announcements', $this->make())->assertForbidden();
    }

    public function test_it_cannot_be_sent_by_no_channel_or_to_nobody(): void
    {
        $this->makeSchool('School A');
        $owner = $this->owner();
        $this->actingAs($owner)->post('/super-admin/announcements', $this->make(['show_banner' => false, 'publish' => true]))->assertSessionHas('error');
        $this->assertSame('draft', PlatformAnnouncement::first()->status);

        $package = Package::create(['name' => 'Empty', 'slug' => 'empty', 'price_monthly' => 1, 'price_yearly' => 1, 'max_students' => 1, 'max_staff' => 1, 'storage_gb' => 1, 'is_active' => true]);
        $this->post('/super-admin/announcements', $this->make(['audience' => 'package', 'package_id' => $package->id, 'publish' => true]))->assertSessionHas('error');
    }
}
