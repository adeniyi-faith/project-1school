<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\SchoolSitePage;
use App\Website\Blocks;
use Tests\Feature\Security\SecurityTestCase;

/** Each school gets a public website made of pages and blocks; only switched-on sites show, and nothing leaks between schools */
class WebsiteEngineTest extends SecurityTestCase
{
    public function test_a_site_is_hidden_until_the_school_switches_it_on(): void
    {
        $school = $this->makeSchool('Bright Stars');
        $this->actingAs($this->makeUser($school, 'school-admin'));
        $this->get('/school/website')->assertOk(); // first visit writes a starter site
        auth()->logout();

        $this->get('/s/bright-stars')->assertNotFound();

        $this->actingAs($this->makeUser($school, 'school-admin'))->put('/school/website', ['enabled' => true, 'theme' => 'warm'])->assertSessionHasNoErrors();
        auth()->logout();

        $this->get('/s/bright-stars')->assertOk()->assertSee('Welcome to Bright Stars')->assertSee('Apply online');
        $this->get('/s/bright-stars/about')->assertOk();
        $this->get('/s/bright-stars/nope')->assertNotFound();
    }

    public function test_only_everyone_announcements_and_published_pages_are_shown_and_only_for_that_school(): void
    {
        $a = $this->makeSchool('School A');
        $b = $this->makeSchool('School B');
        foreach ([$a, $b] as $s) {
            $this->actingAs($this->makeUser($s, 'school-admin'));
            $this->get('/school/website');
            $this->put('/school/website', ['enabled' => true, 'theme' => 'classic']);
            auth()->logout();
        }
        $make = fn ($school, $title, $audience) => Announcement::withoutGlobalScopes()->create(['school_id' => $school->id, 'author_id' => $this->makeUser($school, 'school-admin')->id, 'title' => $title, 'body' => 'x', 'audience' => $audience, 'published_at' => now()->subHour()]);
        $make($a, 'Open day for A', 'all');
        $make($a, 'Staff only memo', 'role');
        $make($b, 'Open day for B', 'all');
        SchoolSitePage::withoutGlobalScopes()->where('school_id', $a->id)->where('slug', 'about')->update(['is_published' => false]);

        $this->get('/s/school-a')->assertSee('Open day for A')->assertDontSee('Staff only memo')->assertDontSee('Open day for B');
        $this->get('/s/school-a/about')->assertNotFound();
    }

    public function test_a_suspended_school_site_is_not_shown(): void
    {
        $school = $this->makeSchool('Gone School');
        $this->actingAs($this->makeUser($school, 'school-admin'))->get('/school/website');
        $this->put('/school/website', ['enabled' => true, 'theme' => 'classic']);
        auth()->logout();
        $school->update(['status' => 'suspended']);

        $this->get('/s/gone-school')->assertNotFound();
    }

    public function test_blocks_are_cleaned_and_page_text_is_escaped(): void
    {
        $school = $this->makeSchool('Safe School');
        $this->actingAs($this->makeUser($school, 'school-admin'))->get('/school/website');
        $page = SchoolSitePage::where('slug', 'about')->first();

        $this->put("/school/website/pages/{$page->id}", [
            'title' => 'About', 'slug' => 'about', 'is_published' => true, 'is_home' => false, 'show_in_menu' => true,
            'blocks' => [
                ['type' => 'text', 'heading' => '<script>alert(1)</script>', 'body' => "Line one\nLine two", 'evil' => 'x'],
                ['type' => 'made-up', 'heading' => 'dropped'],
                ['type' => 'news', 'heading' => 'News', 'count' => 99],
            ],
        ])->assertSessionHasNoErrors();

        $stored = $page->fresh()->blocks;
        $this->assertCount(2, $stored);
        $this->assertArrayNotHasKey('evil', $stored[0]);
        $this->assertSame(10, $stored[1]['count']);

        $this->put('/school/website', ['enabled' => true, 'theme' => 'classic']);
        auth()->logout();
        $this->get('/s/safe-school/about')->assertDontSee('<script>alert(1)</script>', false)->assertSee('Line one');
    }

    public function test_the_home_page_cannot_be_deleted_or_unset_and_a_new_home_replaces_it(): void
    {
        $school = $this->makeSchool('Home School');
        $this->actingAs($this->makeUser($school, 'school-admin'))->get('/school/website');
        $home = SchoolSitePage::where('is_home', true)->first();
        $about = SchoolSitePage::where('slug', 'about')->first();

        $this->delete("/school/website/pages/{$home->id}")->assertSessionHasErrors('page');
        $this->put("/school/website/pages/{$home->id}", ['title' => 'Home', 'slug' => 'home', 'is_published' => true, 'is_home' => false, 'show_in_menu' => true, 'blocks' => []])->assertSessionHasErrors('is_home');

        $this->put("/school/website/pages/{$about->id}", ['title' => 'About', 'slug' => 'about', 'is_published' => true, 'is_home' => true, 'show_in_menu' => true, 'blocks' => []])->assertSessionHasNoErrors();
        $this->assertTrue($about->fresh()->is_home);
        $this->assertFalse($home->fresh()->is_home);
    }

    public function test_pages_can_be_added_but_teachers_cannot_edit_and_other_schools_pages_are_out_of_reach(): void
    {
        $a = $this->makeSchool('Alpha');
        $b = $this->makeSchool('Beta');
        $this->actingAs($this->makeUser($b, 'school-admin'))->get('/school/website');
        $bPage = SchoolSitePage::withoutGlobalScopes()->where('school_id', $b->id)->first();

        $this->actingAs($this->makeUser($a, 'school-admin'))->get('/school/website');
        $this->post('/school/website/pages', ['title' => 'Admissions'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('school_site_pages', ['school_id' => $a->id, 'slug' => 'admissions', 'is_published' => false]);
        $this->put("/school/website/pages/{$bPage->id}", ['title' => 'Hacked', 'slug' => 'x', 'is_published' => true, 'is_home' => false, 'show_in_menu' => true, 'blocks' => []])->assertNotFound();

        $this->actingAs($this->makeUser($a, 'teacher'))->put('/school/website', ['enabled' => true, 'theme' => 'classic'])->assertForbidden();
    }

    public function test_every_block_type_has_a_view_and_a_valid_starting_shape(): void
    {
        foreach (array_keys(Blocks::types()) as $type) {
            $this->assertTrue(view()->exists("website.blocks.{$type}"), "{$type} has no view");
            $this->assertSame($type, Blocks::clean([Blocks::fresh($type)])[0]['type']);
        }
    }
}
