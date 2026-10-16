<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\SchoolSubscription;
use App\Website\Blocks;
use App\Website\Extensions;
use App\Website\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Feature\Security\SecurityTestCase;

/** The Extensions screen, the plan switches, and the safety kit every public form uses */
class WebsiteExtensionsTest extends SecurityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Extensions::$extra = ['demo' => ['name' => 'Demo extension', 'ready' => true, 'needs' => null, 'description' => 'A working extension used by the tests.', 'blocks' => ['demo_block']]];
    }

    protected function tearDown(): void
    {
        Extensions::$extra = [];
        parent::tearDown();
    }

    private function package(string $name = 'Standard'): Package
    {
        return Package::create(['name' => $name, 'slug' => str($name)->slug(), 'price_monthly' => 1000, 'price_yearly' => 10000, 'max_students' => 100, 'max_staff' => 10, 'storage_gb' => 1, 'is_active' => true]);
    }

    private function subscribe(object $school, Package $package): void
    {
        SchoolSubscription::create(['school_id' => $school->id, 'package_id' => $package->id, 'start_date' => now()->subDay(), 'end_date' => now()->addYear(), 'status' => 'active']);
    }

    public function test_the_extensions_screen_lists_every_extension_and_starts_with_all_of_them_off(): void
    {
        $school = $this->makeSchool('Ext One');
        $this->actingAs($this->makeUser($school, 'school-admin'))->get('/school/website/extensions')->assertOk()
            ->assertInertia(fn ($p) => $p->component('SchoolAdmin/Website/Extensions', false)
                ->has('extensions', count(Extensions::all()))
                ->where('extensions.0.key', 'demo')->where('extensions.0.on', false));
        $this->assertFalse(Extensions::enabled($school->id, 'demo'));
    }

    public function test_a_school_can_switch_an_extension_on_and_off_and_other_schools_are_not_affected(): void
    {
        $a = $this->makeSchool('Ext A');
        $b = $this->makeSchool('Ext B');
        $this->actingAs($this->makeUser($a, 'school-admin'))->post('/school/website/extensions/demo', ['on' => true])->assertSessionHasNoErrors();
        $this->assertTrue(Extensions::enabled($a->id, 'demo'));
        $this->assertFalse(Extensions::enabled($b->id, 'demo'));

        $this->post('/school/website/extensions/demo', ['on' => false]);
        $this->assertFalse(Extensions::enabled($a->id, 'demo'));
    }

    public function test_an_extension_that_is_not_built_yet_cannot_be_switched_on(): void
    {
        $school = $this->makeSchool('Ext Soon');
        $this->actingAs($this->makeUser($school, 'school-admin'))->post('/school/website/extensions/forms', ['on' => true])->assertSessionHasErrors('extension');
        $this->assertFalse(Extensions::enabled($school->id, 'forms'));
        $this->post('/school/website/extensions/nonsense', ['on' => true])->assertNotFound();
    }

    public function test_only_people_who_can_edit_settings_can_switch_extensions(): void
    {
        $school = $this->makeSchool('Ext Teacher');
        $this->actingAs($this->makeUser($school, 'teacher'))->post('/school/website/extensions/demo', ['on' => true])->assertForbidden();
        $this->assertFalse(Extensions::enabled($school->id, 'demo'));
    }

    public function test_a_plan_can_hide_an_extension_and_that_switches_it_off_for_schools_on_the_plan(): void
    {
        $school = $this->makeSchool('Ext Plan');
        $basic = $this->package('Basic');
        $pro = $this->package('Pro');
        $this->subscribe($school, $basic);
        $admin = $this->makeUser($school, 'school-admin');
        $this->actingAs($admin)->post('/school/website/extensions/demo', ['on' => true]);
        $this->assertTrue(Extensions::enabled($school->id, 'demo'));

        $owner = $this->makeUser($this->makeSchool('Platform Owner'), 'super-admin');
        $this->actingAs($owner)->post('/super-admin/extensions', ['hidden' => ['demo' => [$basic->id]]])->assertSessionHasNoErrors();

        $this->assertTrue(Extensions::hiddenByPlan($school->id, 'demo'));
        $this->assertFalse(Extensions::enabled($school->id, 'demo'), 'on, but hidden by the plan');
        $this->actingAs($admin)->post('/school/website/extensions/demo', ['on' => true])->assertSessionHasErrors('extension');

        // moving to a plan that does not hide it brings the school's own choice back
        SchoolSubscription::where('school_id', $school->id)->delete();
        $this->subscribe($school, $pro);
        $this->assertTrue(Extensions::enabled($school->id, 'demo'));
    }

    public function test_only_the_platform_owner_can_change_which_plans_see_which_extensions(): void
    {
        $school = $this->makeSchool('Ext NotOwner');
        $this->actingAs($this->makeUser($school, 'school-admin'))->post('/super-admin/extensions', ['hidden' => []])->assertForbidden();
        $this->get('/super-admin/extensions')->assertForbidden();
    }

    public function test_a_section_that_belongs_to_a_switched_off_extension_is_not_offered_but_a_plain_section_always_is(): void
    {
        $school = $this->makeSchool('Ext Blocks');
        $this->assertSame('demo', Extensions::ownerOf('demo_block'));
        $this->assertNull(Extensions::ownerOf('hero'));
        $this->assertFalse(Extensions::blockAllowed('demo_block', $school->id));
        $this->assertTrue(Extensions::blockAllowed('hero', $school->id));

        $offered = collect(Blocks::catalogue($school->id))->pluck('type');
        $this->assertTrue($offered->contains('hero'));
        $this->assertFalse($offered->contains('demo_block'));
    }

    // ---- the safety kit ----

    private function visit(object $school, array $extra = [], string $ip = '10.0.0.1', ?string $ticket = null): Request
    {
        $r = Request::create('/x', 'POST', $extra + [Visitor::TICKET => $ticket ?? Visitor::ticket($school->id, 'demo', Carbon::now()->timestamp - 5)], [], [], ['REMOTE_ADDR' => $ip]);

        return $r;
    }

    public function test_a_normal_post_passes_the_safety_kit(): void
    {
        $school = $this->makeSchool('Kit Ok');
        $this->assertSame(Visitor::OK, Visitor::inspect($this->visit($school), $school->id, 'demo'));
    }

    public function test_a_robot_that_fills_the_hidden_box_is_caught(): void
    {
        $school = $this->makeSchool('Kit Trap');
        $this->assertSame(Visitor::ROBOT, Visitor::inspect($this->visit($school, [Visitor::TRAP => 'http://spam']), $school->id, 'demo'));
    }

    public function test_a_post_without_a_ticket_or_with_a_made_up_one_is_refused(): void
    {
        $school = $this->makeSchool('Kit Ticket');
        $none = Request::create('/x', 'POST', [], [], [], ['REMOTE_ADDR' => '10.0.0.2']);
        $this->assertSame(Visitor::RELOAD, Visitor::inspect($none, $school->id, 'demo'));
        $this->assertSame(Visitor::RELOAD, Visitor::inspect($this->visit($school, [], '10.0.0.2', 'made-up'), $school->id, 'demo'));
    }

    public function test_a_ticket_only_works_for_the_school_and_form_it_was_made_for(): void
    {
        $a = $this->makeSchool('Kit A');
        $b = $this->makeSchool('Kit B');
        $old = Carbon::now()->timestamp - 10;
        $this->assertSame(Visitor::RELOAD, Visitor::inspect($this->visit($a, [], '10.0.0.3', Visitor::ticket($b->id, 'demo', $old)), $a->id, 'demo'), 'another school');
        $this->assertSame(Visitor::RELOAD, Visitor::inspect($this->visit($a, [], '10.0.0.3', Visitor::ticket($a->id, 'other-form', $old)), $a->id, 'demo'), 'another form');
    }

    public function test_a_post_sent_faster_than_a_person_or_from_a_page_left_open_too_long_is_refused(): void
    {
        $school = $this->makeSchool('Kit Time');
        $now = Carbon::now()->timestamp;
        $this->assertSame(Visitor::RELOAD, Visitor::inspect($this->visit($school, [], '10.0.0.4', Visitor::ticket($school->id, 'demo', $now)), $school->id, 'demo'), 'instant');
        $this->assertSame(Visitor::RELOAD, Visitor::inspect($this->visit($school, [], '10.0.0.4', Visitor::ticket($school->id, 'demo', $now - Visitor::MAX_AGE - 5)), $school->id, 'demo'), 'too old');
    }

    public function test_one_visitor_cannot_post_over_and_over(): void
    {
        $school = $this->makeSchool('Kit Rate');
        RateLimiter::clear('visitor:demo:'.$school->id.':10.0.0.5');
        for ($i = 0; $i < 3; $i++) {
            $this->assertSame(Visitor::OK, Visitor::inspect($this->visit($school, [], '10.0.0.5'), $school->id, 'demo', 3));
        }
        $this->assertSame(Visitor::SLOW_DOWN, Visitor::inspect($this->visit($school, [], '10.0.0.5'), $school->id, 'demo', 3));
        $this->assertSame(Visitor::OK, Visitor::inspect($this->visit($school, [], '10.0.0.6'), $school->id, 'demo', 3), 'a different visitor is not affected');
    }

    public function test_a_school_has_a_daily_limit_for_each_kind_of_form(): void
    {
        $school = $this->makeSchool('Kit Day');
        Cache::flush();
        $this->assertSame(Visitor::OK, Visitor::inspect($this->visit($school, [], '10.0.1.1'), $school->id, 'demo', 99, 2));
        $this->assertSame(Visitor::OK, Visitor::inspect($this->visit($school, [], '10.0.1.2'), $school->id, 'demo', 99, 2));
        $this->assertSame(Visitor::BUSY, Visitor::inspect($this->visit($school, [], '10.0.1.3'), $school->id, 'demo', 99, 2));

        $this->travel(1)->days(); // a new day starts again
        $this->assertSame(Visitor::OK, Visitor::inspect($this->visit($school, [], '10.0.1.4'), $school->id, 'demo', 99, 2));
    }
}
