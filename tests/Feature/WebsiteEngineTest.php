<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\SchoolSitePage;
use App\Website\Blocks;
use App\Website\Themes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            $this->put('/school/website', ['enabled' => true, 'theme' => 'modern']);
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
        $this->put('/school/website', ['enabled' => true, 'theme' => 'modern']);
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

        $this->put('/school/website', ['enabled' => true, 'theme' => 'modern']);
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
        $this->post('/school/website/pages', ['title' => 'Fees'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('school_site_pages', ['school_id' => $a->id, 'slug' => 'fees', 'is_published' => false]);
        $this->put("/school/website/pages/{$bPage->id}", ['title' => 'Hacked', 'slug' => 'x', 'is_published' => true, 'is_home' => false, 'show_in_menu' => true, 'blocks' => []])->assertNotFound();

        $this->actingAs($this->makeUser($a, 'teacher'))->put('/school/website', ['enabled' => true, 'theme' => 'modern'])->assertForbidden();
    }

    public function test_every_block_type_has_a_view_and_a_valid_starting_shape(): void
    {
        foreach (array_keys(Blocks::types()) as $type) {
            $this->assertTrue(view()->exists("website.blocks.{$type}"), "{$type} has no view");
            $this->assertSame($type, Blocks::clean([Blocks::fresh($type)])[0]['type']);
        }
    }

    private function liveSite(string $name, string $theme = 'heritage')
    {
        $school = $this->makeSchool($name);
        $admin = $this->makeUser($school, 'school-admin');
        $this->actingAs($admin)->get('/school/website');
        $this->put('/school/website', ['enabled' => true, 'theme' => $theme]);

        return [$school, $admin];
    }

    public function test_every_theme_draws_the_whole_site_with_search_engine_tags(): void
    {
        foreach (array_keys(Themes::ALL) as $theme) {
            $name = "Theme {$theme} School";
            [$school] = $this->liveSite($name, $theme);
            auth()->logout();
            $slug = $school->slug;

            $html = $this->get("/s/{$slug}")->assertOk()
                ->assertSee('data-theme="'.$theme.'"', false)
                ->assertSee('<link rel="canonical" href="'.url("/s/{$slug}").'">', false)
                ->assertSee('application/ld+json', false)
                ->assertSee('EducationalOrganization', false)
                ->assertSee('property="og:title"', false)
                ->assertSee('name="viewport"', false)
                ->getContent();

            $this->assertSame(1, substr_count($html, '<h1'), "{$theme}: exactly one main heading");
            $this->get("/s/{$slug}/admissions")->assertOk()->assertSee('FAQPage', false)->assertSee('BreadcrumbList', false);
        }
    }

    public function test_own_title_description_and_sitemap_are_used(): void
    {
        [$school] = $this->liveSite('Seo School');
        $about = SchoolSitePage::where('slug', 'about')->first();
        $this->put("/school/website/pages/{$about->id}", [
            'title' => 'About', 'slug' => 'about', 'seo_title' => 'Best primary school in Ikeja', 'seo_description' => 'A caring school in Ikeja.',
            'is_published' => true, 'is_home' => false, 'show_in_menu' => true, 'blocks' => [['type' => 'text', 'heading' => 'Hello', 'body' => 'Body']],
        ])->assertSessionHasNoErrors();
        auth()->logout();

        $this->get('/s/seo-school/about')->assertSee('<title>Best primary school in Ikeja</title>', false)->assertSee('content="A caring school in Ikeja."', false);

        $xml = $this->get('/s/seo-school/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
        $this->assertStringContainsString(url('/s/seo-school/about'), $xml);
        $this->assertStringContainsString('<loc>'.url('/s/seo-school').'</loc>', $xml);
    }

    public function test_hidden_sections_are_not_shown_and_the_page_still_has_one_heading(): void
    {
        [$school] = $this->liveSite('Hidden School');
        $about = SchoolSitePage::where('slug', 'about')->first();
        $this->put("/school/website/pages/{$about->id}", [
            'title' => 'About', 'slug' => 'about', 'is_published' => true, 'is_home' => false, 'show_in_menu' => true,
            'blocks' => [['type' => 'text', 'hidden' => true, 'heading' => 'Secret plan', 'body' => 'x'], ['type' => 'text', 'heading' => '', 'body' => 'Just words']],
        ]);
        auth()->logout();

        $html = $this->get('/s/hidden-school/about')->assertOk()->assertDontSee('Secret plan')->assertSee('Just words')->getContent();
        $this->assertSame(1, substr_count($html, '<h1'));
    }

    public function test_pictures_must_be_ones_the_school_uploaded_to_its_own_website_folder(): void
    {
        Storage::fake('public');
        [$a, $adminA] = $this->liveSite('Picture School');
        $b = $this->makeSchool('Other Pictures');

        $path = $this->post('/school/website/images', ['image' => UploadedFile::fake()->image('banner.jpg', 1600, 900)])->assertOk()->json('path');
        $this->assertStringStartsWith("schools/{$a->id}/website/", $path);
        Storage::disk('public')->assertExists($path);
        $this->post('/school/website/images', ['image' => UploadedFile::fake()->create('virus.php', 5)])->assertSessionHasErrors('image');

        $cleaned = Blocks::clean([['type' => 'hero', 'heading' => 'x', 'image' => $path], ['type' => 'hero', 'heading' => 'y', 'image' => "schools/{$b->id}/website/theirs.jpg"], ['type' => 'hero', 'heading' => 'z', 'image' => '../../.env']], $a->id);
        $this->assertSame($path, $cleaned[0]['image']);
        $this->assertSame('', $cleaned[1]['image']);
        $this->assertSame('', $cleaned[2]['image']);
    }

    public function test_preview_shows_unsaved_changes_and_saves_nothing(): void
    {
        [$school] = $this->liveSite('Preview School');
        $home = SchoolSitePage::where('is_home', true)->first();

        $this->post('/school/website/preview', [
            'page_id' => $home->id,
            'site' => ['theme' => 'warm', 'color' => '#112233', 'tagline' => 'Draft motto'],
            'page' => ['title' => 'Home', 'has_blocks' => 1, 'blocks' => [['type' => 'hero', 'heading' => 'Unsaved headline', 'button' => 'none', 'button2' => 'none']]],
        ])->assertOk()->assertSee('id="sheet"', false)->assertSee('Unsaved headline')->assertSee('data-theme="warm"', false)->assertSee('--brand:#112233', false);

        $this->assertNotSame('warm', \App\Models\SchoolSite::first()->theme);
        $this->assertStringNotContainsString('Unsaved headline', json_encode($home->fresh()->blocks));
    }

    public function test_social_links_must_be_secure_urls_and_colours_must_be_hex(): void
    {
        [$school] = $this->liveSite('Social School');

        $this->put('/school/website', ['enabled' => true, 'theme' => 'modern', 'social' => ['facebook' => 'javascript:alert(1)']])->assertSessionHasErrors('social.facebook');
        $this->put('/school/website', ['enabled' => true, 'theme' => 'modern', 'color' => 'red'])->assertSessionHasErrors('color');
        $this->put('/school/website', ['enabled' => true, 'theme' => 'modern', 'color' => '#aa0000', 'color2' => '#ffcc00', 'social' => ['facebook' => 'https://facebook.com/socialschool']])->assertSessionHasNoErrors();
        auth()->logout();

        $this->get('/s/social-school')->assertSee('https://facebook.com/socialschool', false)->assertSee('--brand:#aa0000', false);
    }

    public function test_text_stays_readable_on_any_colour(): void
    {
        $this->assertSame('#ffffff', Themes::ink('#990000'));
        $this->assertSame('#1c1917', Themes::ink('#ffcc00'));
        $this->assertSame('#1c1917', Themes::vars('heritage', '#ffffff', '#fefefe')['brand2-ink']);
        $this->assertSame('#ffffff', Themes::vars('heritage', '#000000', '#111111')['brand2-ink']);
    }
}
