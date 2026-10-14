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
    public function test_a_new_site_is_on_by_default_and_hidden_once_the_school_switches_it_off(): void
    {
        $school = $this->makeSchool('Bright Stars');
        $this->actingAs($this->makeUser($school, 'school-admin'));
        $this->get('/school/website')->assertOk(); // first visit writes a starter site, already switched on
        auth()->logout();

        $this->get('/s/bright-stars')->assertOk()->assertSee('Where every child is known')->assertSee('Apply online');
        $this->get('/s/bright-stars/about')->assertOk();
        $this->get('/s/bright-stars/nope')->assertNotFound();

        $this->actingAs($this->makeUser($school, 'school-admin'))->put('/school/website', ['enabled' => false])->assertSessionHasNoErrors();
        auth()->logout();

        $this->get('/s/bright-stars')->assertNotFound();
    }

    public function test_only_everyone_announcements_and_published_pages_are_shown_and_only_for_that_school(): void
    {
        $a = $this->makeSchool('School A');
        $b = $this->makeSchool('School B');
        foreach ([$a, $b] as $s) {
            $this->actingAs($this->makeUser($s, 'school-admin'));
            $this->get('/school/website');
            $this->put('/school/website', ['enabled' => true]);
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
        $this->put('/school/website', ['enabled' => true]);
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

        $this->put('/school/website', ['enabled' => true]);
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
        $this->post('/school/website/pages', ['title' => 'Fees', 'template' => 'fees'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('school_site_pages', ['school_id' => $a->id, 'slug' => 'fees', 'is_published' => false]);
        $this->assertNotEmpty(SchoolSitePage::where('slug', 'fees')->first()->blocks);
        $this->put("/school/website/pages/{$bPage->id}", ['title' => 'Hacked', 'slug' => 'x', 'is_published' => true, 'is_home' => false, 'show_in_menu' => true, 'blocks' => []])->assertNotFound();

        $teacher = $this->makeUser($a, 'teacher');
        $this->actingAs($teacher)->put('/school/website', ['enabled' => true])->assertForbidden();
        $this->actingAs($teacher)->put('/school/website/themes', ['theme' => 'modern'])->assertForbidden();
        $this->actingAs($teacher)->post('/school/website/posts', ['title' => 'x', 'category' => 'news', 'status' => 'published'])->assertForbidden();
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
        $this->put('/school/website', ['enabled' => true]);
        $this->put('/school/website/themes', ['theme' => $theme])->assertSessionHasNoErrors();

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

        $this->put('/school/website', ['social' => ['facebook' => 'javascript:alert(1)']])->assertSessionHasErrors('social.facebook');
        $this->put('/school/website/themes', ['theme' => 'modern', 'color' => 'red'])->assertSessionHasErrors('color');
        $this->put('/school/website/themes', ['theme' => 'modern', 'color' => '#aa0000', 'color2' => '#ffcc00'])->assertSessionHasNoErrors();
        $this->put('/school/website', ['social' => ['facebook' => 'https://facebook.com/socialschool', 'tiktok' => 'https://tiktok.com/@socialschool']])->assertSessionHasNoErrors();
        auth()->logout();

        $this->get('/s/social-school')->assertSee('https://facebook.com/socialschool', false)->assertSee('https://tiktok.com/@socialschool', false)->assertSee('--brand:#aa0000', false);
    }

    private function postData(array $over = []): array
    {
        return $over + ['title' => 'Our pupils win the state spelling bee', 'category' => 'achievements', 'excerpt' => 'A proud day.', 'body' => "## The final\nIt was **close**.\n\n- Ada\n- Tunde", 'status' => 'published', 'is_featured' => false];
    }

    public function test_stories_get_their_own_page_news_list_feed_and_sitemap_lines(): void
    {
        [$school] = $this->liveSite('News School');
        $this->post('/school/website/posts', $this->postData())->assertSessionHasNoErrors();
        $this->post('/school/website/posts', $this->postData(['title' => 'Secret draft', 'status' => 'draft']))->assertSessionHasNoErrors();
        $this->post('/school/website/posts', $this->postData(['title' => 'Coming soon', 'published_at' => now()->addWeek()->toDateTimeString()]))->assertSessionHasNoErrors();
        $this->post('/school/website/posts', $this->postData(['title' => 'Open day', 'category' => 'events', 'event_date' => now()->addMonth()->toDateString(), 'event_place' => 'School hall']))->assertSessionHasNoErrors();
        auth()->logout();

        $this->get('/s/news-school/news')->assertOk()->assertSee('Our pupils win the state spelling bee')->assertSee('Open day')->assertDontSee('Secret draft')->assertDontSee('Coming soon');
        $this->get('/s/news-school/news?category=events')->assertOk()->assertSee('Open day')->assertDontSee('spelling bee');
        $this->get('/s/news-school')->assertSee('Our pupils win the state spelling bee')->assertSee('href="'.url('/s/news-school/news').'"', false);

        $html = $this->get('/s/news-school/news/our-pupils-win-the-state-spelling-bee')->assertOk()
            ->assertSee('<strong>close</strong>', false)->assertSee('NewsArticle', false)->getContent();
        $this->assertSame(1, substr_count($html, '<h1'));
        $this->get('/s/news-school/news/open-day')->assertOk()->assertSee('School hall')->assertSee('"Event"', false);
        $this->get('/s/news-school/news/secret-draft')->assertNotFound();
        $this->get('/s/news-school/news/coming-soon')->assertNotFound();

        $this->get('/s/news-school/feed.xml')->assertOk()->assertSee('spelling bee')->assertDontSee('Secret draft');
        $xml = $this->get('/s/news-school/sitemap.xml')->getContent();
        $this->assertStringContainsString(url('/s/news-school/news/open-day'), $xml);
        $this->assertStringNotContainsString('secret-draft', $xml);
    }

    public function test_story_text_is_escaped_and_slugs_stay_unique_per_school(): void
    {
        [$school] = $this->liveSite('Escape School');
        $this->post('/school/website/posts', $this->postData(['title' => 'Same title', 'body' => '<script>alert(1)</script> [bad](javascript:alert(1))']))->assertSessionHasNoErrors();
        $this->post('/school/website/posts', $this->postData(['title' => 'Same title']))->assertSessionHasNoErrors();
        $this->assertSame(['same-title', 'same-title-2'], \App\Models\SchoolSitePost::orderBy('id')->pluck('slug')->all());
        auth()->logout();

        $html = $this->get('/s/escape-school/news/same-title')->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);

        [$other] = $this->liveSite('Other Escape');
        $this->post('/school/website/posts', $this->postData(['title' => 'Same title']))->assertSessionHasNoErrors();
        $this->assertSame('same-title', \App\Models\SchoolSitePost::orderByDesc('id')->first()->slug);
        $first = \App\Models\SchoolSitePost::withoutGlobalScopes()->where('school_id', $school->id)->first();
        $this->get("/school/website/posts/{$first->id}")->assertNotFound();
        $this->put("/school/website/posts/{$first->id}", $this->postData())->assertNotFound();
    }

    public function test_page_addresses_used_by_the_website_itself_are_refused(): void
    {
        $this->liveSite('Reserved School');
        $about = SchoolSitePage::where('slug', 'about')->first();
        $this->put("/school/website/pages/{$about->id}", ['title' => 'News', 'slug' => 'news', 'is_published' => true, 'is_home' => false, 'show_in_menu' => true, 'blocks' => []])->assertSessionHasErrors('slug');
        $this->post('/school/website/pages', ['title' => 'News', 'template' => 'blank'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('school_site_pages', ['slug' => 'news-page']);
    }

    public function test_pages_can_be_copied_reordered_and_switched(): void
    {
        $this->liveSite('Order School');
        $pages = SchoolSitePage::orderBy('position')->get();
        $about = $pages->firstWhere('slug', 'about');

        $this->post("/school/website/pages/{$about->id}/copy")->assertRedirect();
        $copy = SchoolSitePage::where('slug', 'about-copy')->first();
        $this->assertFalse($copy->is_published);
        $this->assertSame($about->blocks, $copy->blocks);

        $ids = SchoolSitePage::orderBy('position')->pluck('id')->reverse()->values()->all();
        $this->put('/school/website/pages/order', ['ids' => $ids])->assertSessionHasNoErrors();
        $this->assertSame($ids, SchoolSitePage::orderBy('position')->pluck('id')->all());

        $this->patch("/school/website/pages/{$about->id}", ['show_in_menu' => false])->assertSessionHasNoErrors();
        $this->assertFalse($about->fresh()->show_in_menu);
        $home = SchoolSitePage::where('is_home', true)->first();
        $this->patch("/school/website/pages/{$home->id}", ['is_published' => false]);
        $this->assertTrue($home->fresh()->is_published);
    }

    public function test_the_picture_library_keeps_pictures_in_use_and_other_schools_out(): void
    {
        Storage::fake('public');
        [$a] = $this->liveSite('Library School');
        $res = $this->post('/school/website/media', ['image' => UploadedFile::fake()->image('lab.jpg', 800, 600)])->assertOk()->json();
        $this->assertDatabaseHas('school_site_media', ['id' => $res['id'], 'school_id' => $a->id, 'name' => 'lab']);

        $this->post('/school/website/posts', $this->postData(['cover_image' => $res['path']]))->assertSessionHasNoErrors();
        $this->delete("/school/website/media/{$res['id']}")->assertSessionHasErrors('media');
        $this->put("/school/website/media/{$res['id']}", ['name' => 'Science lab', 'alt' => 'Pupils at work'])->assertSessionHasNoErrors();

        $spare = $this->post('/school/website/media', ['image' => UploadedFile::fake()->image('spare.png', 200, 200)])->json();
        $this->delete("/school/website/media/{$spare['id']}")->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($spare['path']);

        $this->liveSite('Library Other');
        $this->put("/school/website/media/{$res['id']}", ['name' => 'Mine now'])->assertNotFound();
        $this->get('/school/website/media')->assertOk()->assertDontSee('Science lab');
    }

    public function test_theme_pictures_draw_the_school_site_without_saving(): void
    {
        [$school] = $this->liveSite('Sample School', 'heritage');
        $this->get('/school/website/themes/modern/sample?color=%23123456')->assertOk()->assertSee('data-theme="modern"', false)->assertSee('--brand:#123456', false)->assertSee('Where every child is known');
        $this->get('/school/website/themes/current/sample')->assertOk()->assertSee('data-theme="heritage"', false);
        $this->get('/school/website/themes/nope/sample')->assertNotFound();
        $this->assertSame('heritage', \App\Models\SchoolSite::first()->theme);

        foreach (['', '/themes', '/pages', '/posts', '/posts/create', '/media', '/settings'] as $screen) {
            $this->get('/school/website'.$screen)->assertOk();
        }
        $page = SchoolSitePage::first();
        $this->get("/school/website/pages/{$page->id}")->assertOk();
    }

    public function test_story_preview_saves_nothing(): void
    {
        $this->liveSite('Story Preview');
        $this->post('/school/website/posts/preview', $this->postData(['title' => 'Draft headline']))->assertOk()->assertSee('id="sheet"', false)->assertSee('Draft headline');
        $this->assertSame(0, \App\Models\SchoolSitePost::count());
    }

    public function test_a_failed_save_comes_back_as_a_message_not_a_blank_popup(): void
    {
        config(['app.debug' => false]);
        \Illuminate\Support\Facades\Route::middleware('web')->put('/__boom', fn () => throw new \RuntimeException('database said no'));
        $this->actingAs($this->makeUser($this->makeSchool('Boom School'), 'school-admin'));

        $this->from('/school/website')->put('/__boom', [], ['X-Inertia' => 'true'])
            ->assertStatus(303)->assertRedirect('/school/website')->assertSessionHas('error'); // 303, or the browser would repeat the PUT and show a blank pop-up
    }

    public function test_a_save_sent_as_post_with_the_method_override_works_like_a_put(): void
    {
        $school = $this->makeSchool('Override School');
        $this->actingAs($this->makeUser($school, 'school-admin'));
        $this->get('/school/website');

        // the live web server refuses real PUT requests, so the browser sends POST plus this header and a _method field
        $this->postJson('/school/website', ['_method' => 'PUT', 'enabled' => false, 'tagline' => 'Sent as a post'], ['X-Inertia' => 'true', 'X-HTTP-Method-Override' => 'PUT'])
            ->assertStatus(303);
        $this->assertSame('Sent as a post', \App\Models\SchoolSite::withoutGlobalScopes()->where('school_id', $school->id)->value('tagline'));

        $this->post('/school/website', ['enabled' => true, 'tagline' => 'Header only'], ['X-Inertia' => 'true', 'X-HTTP-Method-Override' => 'PUT'])->assertStatus(303);
        $this->assertSame('Header only', \App\Models\SchoolSite::withoutGlobalScopes()->where('school_id', $school->id)->value('tagline'));
    }

    public function test_text_stays_readable_on_any_colour(): void
    {
        $this->assertSame('#ffffff', Themes::ink('#990000'));
        $this->assertSame('#1c1917', Themes::ink('#ffcc00'));
        $this->assertSame('#1c1917', Themes::vars('heritage', '#ffffff', '#fefefe')['brand2-ink']);
        $this->assertSame('#ffffff', Themes::vars('heritage', '#000000', '#111111')['brand2-ink']);
    }

    public function test_the_site_footer_credits_schoolruns_and_links_to_sign_in(): void
    {
        [$school] = $this->liveSite('Foot School');
        auth()->logout();
        $this->get('/s/foot-school')->assertOk()->assertSee('School platform powered by', false)->assertSee('SchoolRuns')->assertSee('/s/foot-school/sign-in', false);
    }

    public function test_the_sign_in_page_uses_the_site_look_and_posts_to_the_normal_login(): void
    {
        [$school] = $this->liveSite('Sign School', 'modern');
        auth()->logout();
        $this->get('/s/sign-school/sign-in')->assertOk()->assertSee('data-theme="modern"', false)->assertSee('action="'.url('/login').'"', false)->assertSee('Parent')->assertSee('Staff');
        $this->put('/school/website', ['enabled' => false]);
    }

    public function test_the_apply_form_uses_the_site_look_saves_inquiries_and_ignores_robots(): void
    {
        [$school] = $this->liveSite('Apply School', 'warm');
        auth()->logout();
        $this->get('/apply/'.$school->id)->assertOk()->assertSee('data-theme="warm"', false)->assertSee('Admission form');

        $form = ['student_name' => 'Ada Obi', 'class_interested' => 'JSS 1', 'guardian_name' => 'Mr Obi', 'guardian_phone' => '08011112222'];
        $this->post('/apply/'.$school->id, $form)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('admission_inquiries', ['school_id' => $school->id, 'student_name' => 'Ada Obi', 'source' => 'online']);

        $this->post('/apply/'.$school->id, ['student_name' => 'Robot', 'company_site' => 'x'] + $form)->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('admission_inquiries', ['student_name' => 'Robot']);

        $this->post('/apply/'.$school->id, ['student_name' => ''])->assertSessionHasErrors('student_name');
    }

    public function test_starter_pages_nobody_edited_get_the_richer_text_and_edited_ones_are_kept(): void
    {
        $school = $this->makeSchool('Fresh School');
        $site = \App\Website\SiteStarter::ensure($school);
        $home = SchoolSitePage::withoutGlobalScopes()->where('school_id', $school->id)->where('slug', 'home')->first();
        $about = SchoolSitePage::withoutGlobalScopes()->where('school_id', $school->id)->where('slug', 'about')->first();
        $old = [['type' => 'text', 'heading' => 'Old text', 'body' => 'x']];
        SchoolSitePage::withoutGlobalScopes()->whereKey($home->id)->update(['blocks' => json_encode($old), 'updated_at' => $home->created_at]);
        SchoolSitePage::withoutGlobalScopes()->whereKey($about->id)->update(['blocks' => json_encode($old), 'updated_at' => $about->created_at->copy()->addHour()]);

        $this->assertSame(4, \App\Website\SiteStarter::refreshUntouched($school));
        $this->assertSame('hero', SchoolSitePage::withoutGlobalScopes()->find($home->id)->blocks[0]['type']);
        $this->assertSame('Old text', SchoolSitePage::withoutGlobalScopes()->find($about->id)->blocks[0]['heading']);
    }

    public function test_each_theme_has_its_own_layout_not_just_its_own_colours(): void
    {
        [$school] = $this->liveSite('Look School');
        auth()->logout();
        $marks = ['heritage' => '<section class="hh hh--', 'modern' => '<section class="hero hero--modern', 'warm' => '<section class="hero hero--warm', 'statement' => '<section class="hero hero--statement'];
        $this->assertSame(array_keys($marks), array_keys(Themes::ALL));
        foreach ($marks as $theme => $mark) {
            \App\Models\SchoolSite::withoutGlobalScopes()->where('school_id', $school->id)->update(['theme' => $theme]);
            $html = $this->get('/s/look-school')->assertOk()->assertSee('data-theme="'.$theme.'"', false)->getContent();
            $this->assertStringContainsString($mark, $html, $theme);
            foreach (array_diff($marks, [$theme => $mark]) as $other) {
                $this->assertStringNotContainsString($other, $html, "$theme should not use the $other layout");
            }
        }
        $this->assertSame('<em>cared for</em>', substr(\App\Website\Prose::accent('Known and *cared for*'), -18));
        $this->assertStringContainsString('<em>', \App\Website\Prose::accent('Where every child is known and cared for', true));
        $this->assertStringNotContainsString('<em>', \App\Website\Prose::accent('Short title'));
    }

    public function test_the_gallery_has_albums_a_public_page_and_keeps_schools_apart(): void
    {
        [$school, $admin] = $this->liveSite('Gal School');
        $other = $this->makeSchool('Other Gal');
        Storage::fake('public');

        $this->actingAs($admin)->get('/school/website/gallery')->assertOk();
        $this->post('/school/website/gallery', ['title' => 'Sports Day 2026', 'description' => 'Inter-house', 'event_date' => '2026-05-02'])->assertRedirect();
        $album = \App\Models\SchoolSiteAlbum::where('title', 'Sports Day 2026')->firstOrFail();
        $this->assertSame('sports-day-2026', $album->slug);

        // nothing public until it holds a photo
        auth()->logout();
        $this->get('/s/gal-school/gallery')->assertOk()->assertSee('No photos yet');
        $this->get('/s/gal-school/gallery/sports-day-2026')->assertNotFound();

        $this->actingAs($admin);
        $a = $this->post('/school/website/media', ['image' => UploadedFile::fake()->image('a.jpg', 800, 600)])->json();
        $b = $this->post('/school/website/media', ['image' => UploadedFile::fake()->image('b.jpg', 800, 600)])->json();
        $theirs = \App\Models\SchoolSiteMedia::withoutGlobalScopes()->create(['school_id' => $other->id, 'path' => 'schools/'.$other->id.'/website/x.jpg', 'name' => 'x']);

        $this->post("/school/website/gallery/{$album->id}/photos", ['media_ids' => [$a['id'], $b['id'], $theirs->id]])->assertSessionHasNoErrors();
        $this->assertSame(2, \App\Models\SchoolSiteAlbumPhoto::where('album_id', $album->id)->count()); // the other school's picture is refused
        $this->assertSame($a['id'], $album->fresh()->cover_media_id);

        $photo = \App\Models\SchoolSiteAlbumPhoto::where('album_id', $album->id)->where('media_id', $b['id'])->first();
        $this->put("/school/website/gallery/{$album->id}/photos/{$photo->id}", ['caption' => 'Relay finals'])->assertSessionHasNoErrors();
        $this->put("/school/website/gallery/{$album->id}/cover", ['media_id' => $b['id']]);
        $this->assertSame($b['id'], $album->fresh()->cover_media_id);

        // a picture inside an album cannot be deleted from the library
        $this->delete('/school/website/media/'.$a['id'])->assertSessionHasErrors('media');

        auth()->logout();
        $this->get('/s/gal-school')->assertOk()->assertSee('/s/gal-school/gallery', false)->assertSee('Life at Gal School');
        $this->get('/s/gal-school/gallery')->assertOk()->assertSee('Sports Day 2026')->assertSee('2 photos');
        $this->get('/s/gal-school/gallery/sports-day-2026')->assertOk()->assertSee('Relay finals')->assertSee('data-lightbox', false);
        $this->get('/s/gal-school/sitemap.xml')->assertOk()->assertSee('/gallery/sports-day-2026', false);

        // another school cannot open or change it
        $this->actingAs($this->makeUser($other, 'school-admin'));
        $this->put("/school/website/gallery/{$album->id}", ['title' => 'Hijacked'])->assertNotFound();
        $this->assertSame('Sports Day 2026', $album->fresh()->title);

        // hidden albums disappear from the site
        $this->actingAs($admin)->put("/school/website/gallery/{$album->id}", ['title' => 'Sports Day 2026', 'is_published' => false]);
        auth()->logout();
        $this->get('/s/gal-school/gallery/sports-day-2026')->assertNotFound();
    }

    public function test_the_heritage_theme_has_its_own_header_banner_footer_and_inner_pages(): void
    {
        [$school, $admin] = $this->liveSite('Gold School', 'heritage');
        $home = SchoolSitePage::where('is_home', true)->first();
        $home->blocks = Blocks::clean([
            ['type' => 'hero', 'heading' => 'Lifelong and Worldwide', 'subheading' => 'A family.', 'button' => 'apply', 'button2' => 'login'],
            ['type' => 'news', 'heading' => 'Top Headlines', 'count' => 3],
            ['type' => 'apply', 'heading' => 'Thinking about us?', 'button' => 'apply', 'button2' => 'contact'],
        ], $school->id);
        $home->save();
        \App\Models\SchoolSitePost::create(['school_id' => $school->id, 'title' => 'Open day', 'slug' => 'open-day', 'category' => 'news', 'body' => 'Come.', 'status' => 'published', 'published_at' => now()->subDay()]);
        auth()->logout();

        $this->get('/s/gold-school')->assertOk()
            ->assertSee('Crimson+Pro', false)
            ->assertSee('<section class="hh hh--plain">', false)
            ->assertSee('class="menu-main"', false)
            ->assertSee('class="menu-util"', false)
            ->assertSee('sec sec--headlines', false)
            ->assertSee('Visit News', false)
            ->assertSee('<footer class="ftr ftr--heritage">', false)
            ->assertSee('School platform powered by', false);

        $this->get('/s/gold-school/sign-in')->assertOk()->assertSee('class="hp-tabs"', false)->assertDontSee('class="pagehero"', false);
        $this->get('/apply/'.$school->id)->assertOk()->assertSee('class="hp-form"', false)->assertDontSee('class="pagehero"', false);
        $this->get('/s/gold-school/news')->assertOk()->assertSee('hp-filter', false)->assertSee('news--lead', false);
        $this->get('/s/gold-school/news/open-day')->assertOk()->assertSee('hp-post', false);

        // another theme keeps the shared inner pages
        $this->actingAs($admin)->post('/school/website/theme', ['theme' => 'modern']);
        auth()->logout();
    }

    public function test_the_site_search_finds_pages_and_stories_of_this_school_only(): void
    {
        [$school, $admin] = $this->liveSite('Search School', 'heritage');
        $home = SchoolSitePage::where('is_home', true)->first();
        $home->blocks = Blocks::clean([['type' => 'text', 'heading' => 'Our robotics club', 'body' => 'Pupils build robots every Friday.']], $school->id);
        $home->save();
        \App\Models\SchoolSitePost::create(['school_id' => $school->id, 'title' => 'Robot contest won', 'slug' => 'robot-contest', 'category' => 'news', 'body' => "## Result\n\nOur team won the <b>robot</b> contest.", 'status' => 'published', 'published_at' => now()->subDay()]);
        \App\Models\SchoolSitePost::create(['school_id' => $school->id, 'title' => 'Draft about robots', 'slug' => 'draft-robots', 'category' => 'news', 'body' => 'Secret robots.', 'status' => 'draft']);
        [$other] = $this->liveSite('Other School', 'heritage');
        auth()->logout();

        $this->get('/s/search-school/search?q=robot')->assertOk()
            ->assertSee('Robot contest won')->assertSee('Pupils build <mark>robot</mark>s', false)
            ->assertSee('<mark>robot</mark>', false)
            ->assertDontSee('Draft about robots')
            ->assertDontSee('<b>robot</b>', false)
            ->assertSee('noindex', false);
        $this->get('/s/search-school/search?q=zzzzqq')->assertOk()->assertSee('Nothing found');
        $this->get('/s/search-school/search?q=a')->assertOk()->assertSee('at least two letters');
        $this->get('/s/search-school/search')->assertOk()->assertSee('Type a few words');
        $this->get('/s/search-school')->assertOk()->assertSee('data-search-toggle', false)->assertSee('id="sbar"', false);
        $this->get('/s/other-school/search?q=robot')->assertOk()->assertDontSee('Robot contest won');
    }

    public function test_the_statement_theme_draws_the_civic_look_from_the_new_blocks(): void
    {
        [$school, $admin] = $this->liveSite('Civic School', 'statement');
        $this->actingAs($admin)->put('/school/website', ['announcement' => 'Open day on 5 August']);
        $home = SchoolSitePage::where('is_home', true)->first();
        $home->blocks = Blocks::clean([
            ['type' => 'hero', 'heading' => 'Beyond *the Crumbs*', 'eyebrow' => 'Summit 2026', 'lead' => 'A seat at the table', 'subheading' => 'A civic platform.', 'foot' => 'One voice.',
                'facts' => [['label' => 'Date', 'value' => 'Wednesday,|5 August 2026'], ['label' => 'Venue', 'value' => 'Town <b>Hall</b>']], 'button' => 'apply', 'button2' => 'contact'],
            ['type' => 'stats', 'items' => [['number' => '60+', 'label' => 'Villages']]],
            ['type' => 'quote', 'text' => 'It buys a day; it costs a generation.', 'who' => 'Founding statement'],
            ['type' => 'steps', 'band' => 'dark', 'heading' => 'Five commitments', 'items' => [['title' => 'Reframe it', 'body' => 'together.']]],
            ['type' => 'features', 'style' => 'pillars', 'tag' => 'Pillar', 'heading' => 'Pillars', 'items' => [['title' => 'Education', 'body' => 'Schools.'], ['title' => 'Jobs', 'body' => 'Work.']]],
            ['type' => 'features', 'style' => 'panels', 'heading' => 'Aims', 'items' => [['title' => 'Vision', 'body' => 'A future.']]],
            ['type' => 'profile', 'eyebrow' => 'The Convener', 'name' => 'Ada Obi', 'role' => 'Lawyer', 'body' => 'Born in town.'],
        ], $school->id);
        $home->save();
        auth()->logout();

        $this->get("/s/civic-school")->assertOk()
            ->assertSee('Open day on 5 August')
            ->assertSee('fonts.googleapis.com', false)
            ->assertSee('Newsreader', false)
            ->assertSee('<section class="hero hero--statement', false)
            ->assertSee('class="hero-card"', false)
            ->assertSee('Wednesday,<br>5 August 2026', false)
            ->assertSee('Town &lt;b&gt;Hall&lt;/b&gt;', false)
            ->assertSee('<small>+</small>', false)
            ->assertSee('class="quote"', false)
            ->assertSee('sec--dark', false)
            ->assertSee('class="step-t"', false)
            ->assertSee('Pillar I', false)
            ->assertSee('Pillar II', false)
            ->assertSee('panel-card', false)
            ->assertSee('class="profile"', false)
            ->assertSee('<footer class="ftr ftr--statement">', false);
        $this->get('/s/civic-school')->assertSee('School platform powered by', false);

        $this->actingAs($admin)->put('/school/website', ['announcement' => '']);
        auth()->logout();
        $this->get('/s/civic-school')->assertDontSee('class="notice"', false);
    }

    public function test_other_themes_show_the_announcement_bar_and_keep_their_own_footer(): void
    {
        [$school, $admin] = $this->liveSite('Bar School', 'modern');
        $this->actingAs($admin)->put('/school/website', ['announcement' => 'School is closed on Friday']);
        auth()->logout();
        $this->get('/s/bar-school')->assertOk()->assertSee('School is closed on Friday')->assertSee('<footer class="ftr">', false)->assertDontSee('<footer class="ftr ftr--statement">', false);
    }
}
