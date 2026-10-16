<?php

namespace Tests\Feature\Correctness;

use App\Models\SchoolSitePost;
use Carbon\Carbon;
use Tests\Feature\Security\SecurityTestCase;

/**
 * Right-answer checks for the public website: which stories appear, in what order, on which page,
 * and that nothing unpublished, scheduled or from another school leaks. Counts are worked out by hand.
 */
class WebsiteCorrectnessTest extends SecurityTestCase
{
    private $school;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 12:00:00');
        $this->school = $this->makeSchool('Story School');
        $this->actingAs($this->makeUser($this->school, 'school-admin'))->get('/school/website');
        $this->put('/school/website', ['enabled' => true]);
    }

    private function story(string $title, array $over = [])
    {
        return $this->post('/school/website/posts', $over + ['title' => $title, 'category' => 'news', 'excerpt' => 'x', 'body' => 'Body of '.$title, 'status' => 'published', 'is_featured' => false]);
    }

    private function titlesOn(string $url): array
    {
        preg_match_all('/<h[23][^>]*>\s*(?:<a[^>]*>)?\s*(Story \d+)/', $this->get($url)->assertOk()->getContent(), $m);

        return $m[1];
    }

    public function test_the_news_list_shows_twelve_to_a_page_newest_first_and_only_what_is_published(): void
    {
        // 13 published stories, one a day apart: Story 1 is the oldest (1 Oct), Story 13 the newest (13 Oct)
        foreach (range(1, 13) as $n) {
            $this->story("Story $n", ['published_at' => Carbon::parse('2026-10-01 09:00')->addDays($n - 1)->toDateTimeString()])->assertSessionHasNoErrors();
        }
        $this->story('Hidden draft', ['status' => 'draft'])->assertSessionHasNoErrors();
        $this->story('Next week', ['published_at' => '2026-10-27 09:00:00'])->assertSessionHasNoErrors();
        $this->story('Later today', ['published_at' => '2026-10-20 12:00:01'])->assertSessionHasNoErrors();   // one second from now
        $this->story('Right now', ['published_at' => '2026-10-20 12:00:00', 'category' => 'events'])->assertSessionHasNoErrors(); // this very second: shown
        auth()->logout();

        $page1 = $this->get('/s/story-school/news')->assertOk()->getContent();
        preg_match_all('/Story (\d+)/', $page1, $m1);
        // Page 1: "Right now" (20 Oct) first, then Story 13 down to Story 3 = 12 stories. Story 2 and Story 1 are on page 2.
        $this->assertStringContainsString('Right now', $page1);
        $this->assertStringContainsString('Story 13', $page1);
        $this->assertStringContainsString('Story 3', $page1);
        $this->assertStringNotContainsString('Story 2<', $page1);
        $this->assertStringNotContainsString('Hidden draft', $page1);
        $this->assertStringNotContainsString('Next week', $page1);
        $this->assertStringNotContainsString('Later today', $page1);
        $this->assertLessThan(strpos($page1, 'Story 13'), strpos($page1, 'Right now'), 'newest story should come first');
        $this->assertLessThan(strpos($page1, 'Story 12'), strpos($page1, 'Story 13'));
        // 12 stories on the page: "Right now" plus Story 13 down to Story 3 (11 of them)
        $this->assertSame(11, count(array_unique($m1[1])), 'page one should hold Story 13 down to Story 3');

        $page2 = $this->get('/s/story-school/news?page=2')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/Story 2\b/', $page2);
        $this->assertMatchesRegularExpression('/Story 1\b/', $page2);
        $this->assertDoesNotMatchRegularExpression('/Story 3\b/', $page2);
        $this->assertStringNotContainsString('Hidden draft', $page2);

        // One category only
        $events = $this->get('/s/story-school/news?category=events')->assertOk()->getContent();
        $this->assertStringContainsString('Right now', $events);
        $this->assertStringNotContainsString('Story 13', $events);

        // The sitemap lists exactly the 14 shown stories (13 + Right now); the feed carries the newest 20 (here all 14)
        $sitemap = $this->get('/s/story-school/sitemap.xml')->getContent();
        $this->assertSame(14, substr_count($sitemap, '/news/'), 'sitemap should have one line for each of the 14 live stories');
        $this->assertStringNotContainsString('hidden-draft', $sitemap);
        $this->assertStringNotContainsString('next-week', $sitemap);
        $this->assertStringNotContainsString('later-today', $sitemap);
        $feed = $this->get('/s/story-school/feed.xml')->getContent();
        $this->assertSame(14, substr_count($feed, '<item>'));

        // A scheduled story's own address is not found until its time, then it is
        $this->get('/s/story-school/news/later-today')->assertNotFound();
        Carbon::setTestNow('2026-10-20 12:00:02');
        $this->get('/s/story-school/news/later-today')->assertOk();
    }

    public function test_a_school_cannot_see_anothers_stories_and_unknown_pages_are_not_found(): void
    {
        $this->story('Our secret plan')->assertSessionHasNoErrors();
        $other = $this->makeSchool('Rival School');
        $this->actingAs($this->makeUser($other, 'school-admin'))->get('/school/website');
        $this->put('/school/website', ['enabled' => true]);
        $this->story('Rival news')->assertSessionHasNoErrors();
        auth()->logout();

        $this->get('/s/rival-school/news')->assertOk()->assertSee('Rival news')->assertDontSee('Our secret plan');
        $this->get('/s/story-school/news')->assertOk()->assertSee('Our secret plan')->assertDontSee('Rival news');
        $this->get('/s/rival-school/news/our-secret-plan')->assertNotFound();
        $this->get('/s/no-such-school')->assertNotFound();
        $this->get('/s/story-school/no-such-page')->assertNotFound();
        $this->assertSame(1, SchoolSitePost::withoutGlobalScopes()->where('school_id', $other->id)->count());
    }

    public function test_hidden_pages_stay_out_of_the_menu_and_the_sitemap_and_markup_in_titles_is_escaped(): void
    {
        $this->story('<script>alert("x")</script> Big news')->assertSessionHasNoErrors();
        $about = \App\Models\SchoolSitePage::where('slug', 'about')->first();
        $this->put("/school/website/pages/{$about->id}", ['title' => 'About', 'slug' => 'about', 'is_published' => false, 'is_home' => false, 'show_in_menu' => true, 'blocks' => []])->assertSessionHasNoErrors();
        auth()->logout();

        $this->get('/s/story-school/about')->assertNotFound();
        $this->assertStringNotContainsString('/s/story-school/about<', $this->get('/s/story-school/sitemap.xml')->getContent());
        $this->assertStringNotContainsString('href="'.url('/s/story-school/about').'"', $this->get('/s/story-school')->getContent());
        $html = $this->get('/s/story-school/news')->getContent();
        $this->assertStringNotContainsString('<script>alert("x")</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $feed = $this->get('/s/story-school/feed.xml')->getContent();
        $this->assertStringNotContainsString('<script>alert', $feed);
    }
}
