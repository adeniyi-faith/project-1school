<?php

namespace Tests\Feature;

use App\Models\SchoolApiToken;
use App\Models\SchoolSitePage;
use App\Models\SchoolWebhook;
use App\Services\WebhookService;
use App\Website\Blocks;
use App\Website\SafeCss;
use App\Website\Themes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Security\SecurityTestCase;

/** The developer kit: guides, data links, safe CSS, webhooks, keys, export/import, and folders for themes and sections */
class DeveloperKitTest extends SecurityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        WebhookService::$resolver = fn () => ['93.184.216.34'];
    }

    protected function tearDown(): void
    {
        WebhookService::$resolver = null;
        File::deleteDirectory(resource_path('website-blocks'));
        File::deleteDirectory(resource_path('website-themes'));
        // the real folder is kept in git (.gitkeep); put it back as it was
        File::ensureDirectoryExists(resource_path('website-blocks'));
        File::put(resource_path('website-blocks/.gitkeep'), '');
        Blocks::flush();
        Themes::flush();
        parent::tearDown();
    }

    private function site(string $name = 'Dev School', string $theme = 'modern'): array
    {
        $school = $this->makeSchool($name);
        $admin = $this->makeUser($school, 'school-admin');
        $this->actingAs($admin)->get('/school/website');
        $this->put('/school/website', ['enabled' => true]);
        $this->put('/school/website/themes', ['theme' => $theme])->assertSessionHasNoErrors();

        return [$school, $admin];
    }

    public function test_the_guides_are_public_and_the_front_page_links_to_them(): void
    {
        $this->get('/docs')->assertOk()->assertSee('Developer overview')->assertSee('Getting started');
        $this->get('/docs/dev-webhooks')->assertOk()->assertSee('X-SchoolRuns-Signature');
        $this->get('/docs/nope')->assertNotFound();
        $this->get('/')->assertOk()->assertSee(route('docs.index'), false);

        // a signed-in person can read them too
        [, $admin] = $this->site();
        $this->actingAs($admin)->get('/docs/website')->assertOk();
    }

    public function test_every_guide_in_the_list_has_a_file(): void
    {
        foreach (\App\Support\Docs::groups() as $pages) {
            foreach ($pages as $p) {
                $this->assertFileExists(resource_path("docs/{$p['slug']}.md"), $p['slug']);
            }
        }
    }

    public function test_site_and_news_data_are_public_open_to_other_sites_and_keep_schools_apart(): void
    {
        [$school] = $this->site('Data School');
        auth()->logout();

        $this->getJson('/s/data-school/site.json')->assertOk()->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertJsonPath('school.name', 'Data School')->assertJsonPath('theme.key', 'modern')->assertJsonStructure(['pages', 'live' => ['fees', 'terms', 'holidays', 'team', 'events']]);
        $this->getJson('/s/data-school/news.json')->assertOk()->assertJsonStructure(['posts']);
        $this->getJson('/s/other-school/site.json')->assertNotFound();
    }

    public function test_extra_css_is_cleaned_when_saved_and_drawn(): void
    {
        $css = SafeCss::clean('a{background:url(javascript:alert(1))} @import "x.css"; p{color:blue} i{width:expression(1)}');
        $this->assertStringNotContainsString('javascript', $css);
        $this->assertStringNotContainsString('@import', $css);
        $this->assertStringNotContainsString('expression', $css);
        $this->assertStringContainsString('p{color:blue}', $css);
        $this->assertStringNotContainsString('-moz-bind', SafeCss::clean('a{-moz-bind:url(https://x.test/y.xml#z)}'));
        $this->assertStringNotContainsString('<', SafeCss::clean('</sty/**/le><img src=x onerror=alert(1)>'));
        $this->assertStringContainsString('url("https://fonts.example/x.png")', SafeCss::clean('a{background:url(https://fonts.example/x.png)}'));

        [$school] = $this->site('Css School');
        $this->put('/school/website/developer/settings', ['custom_css' => '.btn{border-radius:99px} </style><script>alert(1)</script>', 'ga_id' => 'G-ABC123XYZ', 'verify_google' => 'abcdefghijklmnopqrstuvwx'])->assertSessionHasNoErrors();
        auth()->logout();
        $this->get('/s/css-school')->assertOk()->assertSee('.btn{border-radius:99px}', false)->assertDontSee('<script>alert(1)', false)
            ->assertSee('googletagmanager.com/gtag/js?id=G-ABC123XYZ', false)->assertSee('name="google-site-verification"', false);

        $this->actingAs($this->makeUser($school, 'school-admin'))->put('/school/website/developer/settings', ['ga_id' => '<script>'])->assertSessionHasErrors('ga_id');
    }

    public function test_a_webhook_is_signed_only_goes_to_public_https_and_tells_the_receiver(): void
    {
        [$school] = $this->site('Hook School');
        Http::fake(['https://hooks.example.com/*' => Http::response('ok', 200)]);

        $this->post('/school/website/developer/webhook', ['url' => 'http://hooks.example.com/x', 'events' => ['inquiry.created']])->assertSessionHasErrors('url');
        WebhookService::$resolver = fn () => ['10.0.0.5'];
        $this->post('/school/website/developer/webhook', ['url' => 'https://internal.example.com/x', 'events' => ['inquiry.created']])->assertSessionHasErrors('url');
        WebhookService::$resolver = fn () => ['93.184.216.34'];

        $this->post('/school/website/developer/webhook', ['url' => 'https://hooks.example.com/in', 'events' => ['inquiry.created']])->assertSessionHas('dev_secret');
        $hook = SchoolWebhook::sole();
        $this->assertNotSame('', $hook->secret);

        auth()->logout();
        $this->post('/apply/'.$school->id, ['student_name' => 'Ada', 'class_interested' => 'JSS 1', 'guardian_name' => 'Mr Obi', 'guardian_phone' => '08011112222'])->assertSessionHasNoErrors();

        Http::assertSent(function ($request) use ($hook) {
            return $request->url() === 'https://hooks.example.com/in'
                && $request->header('X-SchoolRuns-Event')[0] === 'inquiry.created'
                && $request->header('X-SchoolRuns-Signature')[0] === 'sha256='.hash_hmac('sha256', $request->body(), $hook->secret)
                && json_decode($request->body(), true)['data']['student_name'] === 'Ada';
        });
        $this->assertSame(200, $hook->fresh()->last_status);
    }

    public function test_a_broken_receiver_never_stops_the_school_work(): void
    {
        [$school] = $this->site('Broken Hook School');
        SchoolWebhook::create(['school_id' => $school->id, 'url' => 'https://down.example.com/in', 'secret' => 's', 'events' => ['inquiry.created'], 'is_active' => true]);
        Http::fake(['https://down.example.com/*' => Http::response('no', 500)]);
        auth()->logout();

        $this->post('/apply/'.$school->id, ['student_name' => 'Ada', 'class_interested' => 'JSS 1', 'guardian_name' => 'Mr Obi', 'guardian_phone' => '08011112222'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('admission_inquiries', ['student_name' => 'Ada']);
        $this->assertSame(500, SchoolWebhook::withoutGlobalScopes()->first()->last_status);
    }

    public function test_the_read_only_key_works_is_shown_once_and_only_reads_its_own_school(): void
    {
        [$school] = $this->site('Key School');
        $other = $this->makeSchool('Other Key School');
        \App\Models\AdmissionInquiry::create(['school_id' => $school->id, 'student_name' => 'Mine', 'class_interested' => 'JSS 1', 'guardian_name' => 'G', 'guardian_phone' => '08011112222', 'source' => 'online']);
        \App\Models\AdmissionInquiry::create(['school_id' => $other->id, 'student_name' => 'Theirs', 'class_interested' => 'JSS 1', 'guardian_name' => 'G', 'guardian_phone' => '08011112222', 'source' => 'online']);

        $this->post('/school/website/developer/tokens', ['name' => 'Sheet'])->assertSessionHas('dev_token');
        $key = session('dev_token');
        $this->assertStringStartsWith('sr_', $key);
        $this->assertSame(hash('sha256', $key), SchoolApiToken::sole()->token_hash);
        auth()->logout();

        $this->getJson('/api/school/inquiries')->assertUnauthorized();
        $this->getJson('/api/school/inquiries', ['Authorization' => 'Bearer wrong'])->assertUnauthorized();
        $this->getJson('/api/school/inquiries', ['Authorization' => 'Bearer '.$key])->assertOk()->assertJsonCount(1, 'inquiries')->assertJsonPath('inquiries.0.student_name', 'Mine');
        $this->getJson('/api/school/me', ['Authorization' => 'Bearer '.$key])->assertOk()->assertJsonPath('school.name', 'Key School');

        $this->actingAs($this->makeUser($school, 'school-admin'))->post('/school/website/developer/tokens/'.SchoolApiToken::sole()->id.'/revoke');
        auth()->logout();
        $this->getJson('/api/school/inquiries', ['Authorization' => 'Bearer '.$key])->assertUnauthorized();
    }

    public function test_a_website_can_be_exported_and_imported_safely(): void
    {
        [$school] = $this->site('Export School');
        $home = SchoolSitePage::where('is_home', true)->first();
        $this->put("/school/website/pages/{$home->id}", ['title' => 'Home', 'slug' => $home->slug, 'is_published' => true, 'is_home' => true, 'show_in_menu' => true,
            'blocks' => [['type' => 'text', 'heading' => 'Welcome home', 'body' => 'Hello', 'image' => 'schools/999/website/steal.jpg']]]);

        $file = $this->get('/school/website/export')->assertOk()->streamedContent();
        $json = json_decode($file, true);
        $this->assertSame('schoolruns-site', $json['format']);
        $this->assertSame('modern', $json['site']['theme']);

        // into a different school
        $b = $this->makeSchool('Import School');
        $this->actingAs($this->makeUser($b, 'school-admin'))->get('/school/website');
        $upload = fn ($content) => UploadedFile::fake()->createWithContent('site.json', $content);

        $this->post('/school/website/import', ['file' => $upload('not json')])->assertSessionHasErrors('file');

        $json['pages'][] = ['title' => 'Extra', 'slug' => 'extra', 'is_published' => true, 'show_in_menu' => true, 'blocks' => [['type' => 'evil', 'x' => 1], ['type' => 'text', 'heading' => 'Hi', 'body' => 'x']]];
        $this->post('/school/website/import', ['file' => $upload(json_encode($json)), 'apply_settings' => true])->assertSessionHas('success');

        $extra = SchoolSitePage::where('slug', 'extra')->sole();
        $this->assertSame($b->id, $extra->school_id);
        $this->assertCount(1, $extra->blocks, 'unknown sections are dropped');
        $this->assertSame('modern', \App\Models\SchoolSite::withoutGlobalScopes()->where('school_id', $b->id)->value('theme'));
        $this->assertStringNotContainsString('steal.jpg', json_encode(SchoolSitePage::withoutGlobalScopes()->where('school_id', $b->id)->pluck('blocks')), 'another school\'s pictures are left out');

        // the same file again adds nothing unless replace is ticked
        $before = SchoolSitePage::count();
        $this->post('/school/website/import', ['file' => $upload(json_encode($json))]);
        $this->assertSame($before, SchoolSitePage::count());
    }

    public function test_a_developer_can_add_a_section_and_a_theme_with_folders(): void
    {
        $this->artisan('site:make-block', ['name' => 'Price list'])->assertSuccessful();
        $this->artisan('site:make-theme', ['name' => 'Sunrise', '--base' => 'modern'])->assertSuccessful();
        Blocks::flush();
        Themes::flush();

        $this->assertArrayHasKey('price_list', Blocks::types());
        $this->assertArrayHasKey('sunrise', Themes::all());
        $this->assertSame('modern', Themes::key('sunrise'));
        $this->artisan('site:make-block', ['name' => 'Price list'])->assertFailed();

        [$school] = $this->site('Folder School', 'sunrise');
        $home = SchoolSitePage::where('is_home', true)->first();
        $this->put("/school/website/pages/{$home->id}", ['title' => 'Home', 'slug' => $home->slug, 'is_published' => true, 'is_home' => true, 'show_in_menu' => true,
            'blocks' => [['type' => 'price_list', 'heading' => 'Our prices', 'body' => 'Fair', 'items' => [['title' => 'Nursery', 'text' => 'Cheap']]]]])->assertSessionHasNoErrors();
        auth()->logout();

        $this->get('/s/folder-school')->assertOk()->assertSee('data-theme="modern"', false)->assertSee('data-site-theme="sunrise"', false)
            ->assertSee('Our prices')->assertSee('Nursery')->assertSee('body[data-site-theme="sunrise"]', false);
    }

    public function test_bad_folders_are_ignored(): void
    {
        File::ensureDirectoryExists(resource_path('website-themes/broken'));
        File::put(resource_path('website-themes/broken/theme.json'), '{"base":"nothing"}');
        File::ensureDirectoryExists(resource_path('website-blocks/hero'));
        File::put(resource_path('website-blocks/hero/block.json'), '{"label":"x","fields":[]}');
        File::put(resource_path('website-blocks/hero/view.blade.php'), 'x');
        Blocks::flush();
        Themes::flush();

        $this->assertArrayNotHasKey('broken', Themes::all());
        $this->assertArrayNotHasKey('custom', Blocks::types()['hero'], 'a folder cannot replace a built-in section');
    }
}
