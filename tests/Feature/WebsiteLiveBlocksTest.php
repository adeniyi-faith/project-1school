<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Holiday;
use App\Models\SchoolClass;
use App\Models\SchoolSitePage;
use App\Models\SchoolSitePost;
use App\Models\Staff;
use App\Website\Themes;
use Tests\Feature\Security\SecurityTestCase;

/** Website sections that read the school's own records, the message form and the WhatsApp button */
class WebsiteLiveBlocksTest extends SecurityTestCase
{
    private function site(string $name, string $theme = 'heritage'): array
    {
        $school = $this->makeSchool($name);
        $admin = $this->makeUser($school, 'school-admin');
        $this->actingAs($admin)->get('/school/website');
        $this->put('/school/website', ['enabled' => true]);
        $this->put('/school/website/themes', ['theme' => $theme])->assertSessionHasNoErrors();

        return [$school, $admin];
    }

    private function page(object $school, array $blocks): string
    {
        $home = SchoolSitePage::withoutGlobalScopes()->where('school_id', $school->id)->where('is_home', true)->first();
        $this->put("/school/website/pages/{$home->id}", ['title' => 'Home', 'slug' => $home->slug, 'is_published' => true, 'is_home' => true, 'show_in_menu' => true, 'blocks' => $blocks])->assertSessionHasNoErrors();
        auth()->logout();

        return $this->get('/s/'.$school->slug)->assertOk()->getContent();
    }

    public function test_fees_dates_team_events_and_downloads_read_the_schools_records_in_every_theme(): void
    {
        foreach (array_keys(Themes::ALL) as $theme) {
            [$school, $admin] = $this->site("Live {$theme} School", $theme);
            $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026/2027', 'start_date' => now()->subMonth(), 'end_date' => now()->addMonths(9), 'is_current' => true]);
            $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary 4']);
            $cat = FeeCategory::create(['school_id' => $school->id, 'name' => 'Tuition', 'type' => 'tuition']);
            FeeStructure::create(['school_id' => $school->id, 'class_id' => $class->id, 'fee_category_id' => $cat->id, 'academic_year' => '2026/2027', 'amount' => 85000, 'frequency' => 'per_term']);
            Holiday::create(['school_id' => $school->id, 'name' => 'Founders Day', 'date' => now()->addDays(20)]);
            Staff::create(['school_id' => $school->id, 'first_name' => 'Ngozi', 'last_name' => 'Eze', 'gender' => 'female', 'salary_type' => 'fixed', 'status' => 'active', 'show_on_website' => true]);
            Staff::create(['school_id' => $school->id, 'first_name' => 'Hidden', 'last_name' => 'Person', 'gender' => 'male', 'salary_type' => 'fixed', 'status' => 'active']);
            SchoolSitePost::create(['school_id' => $school->id, 'title' => 'Sports Day', 'slug' => 'sports-day', 'category' => 'events', 'body' => 'Races.', 'status' => 'published', 'published_at' => now()->subDay(), 'event_date' => now()->addDays(5)]);
            SchoolSitePost::create(['school_id' => $school->id, 'title' => 'Old Fair', 'slug' => 'old-fair', 'category' => 'events', 'body' => 'Past.', 'status' => 'published', 'published_at' => now()->subMonth(), 'event_date' => now()->subDays(5)]);

            $html = $this->page($school, [
                ['type' => 'live_fees', 'heading' => 'Our fees', 'intro' => '', 'note' => ''],
                ['type' => 'live_dates', 'heading' => 'Term dates'],
                ['type' => 'team', 'heading' => 'Meet the team', 'intro' => ''],
                ['type' => 'events', 'heading' => 'Coming up', 'count' => 6],
                ['type' => 'downloads', 'heading' => 'Downloads', 'items' => [['title' => 'Prospectus', 'link' => 'https://example.com/p.pdf'], ['title' => 'Bad', 'link' => 'javascript:alert(1)']]],
            ]);

            $this->assertStringContainsString('Primary 4', $html, $theme);
            $this->assertStringContainsString('85,000', $html, $theme);
            $this->assertStringContainsString('Founders Day', $html, $theme);
            $this->assertStringContainsString('Ngozi Eze', $html, $theme);
            $this->assertStringNotContainsString('Hidden Person', $html, $theme);
            $this->assertStringContainsString('Sports Day', $html, $theme);
            $this->assertStringNotContainsString('Old Fair', $html, $theme);
            $this->assertStringContainsString('https://example.com/p.pdf', $html, $theme);
            $this->assertStringNotContainsString('javascript:alert', $html, $theme);
        }
    }

    public function test_live_sections_hide_themselves_when_there_is_nothing_to_show(): void
    {
        [$school] = $this->site('Empty Live School');
        $html = $this->page($school, [['type' => 'live_fees', 'heading' => 'Hidden fees', 'intro' => '', 'note' => ''], ['type' => 'team', 'heading' => 'Hidden team', 'intro' => '']]);

        $this->assertStringNotContainsString('Hidden fees', $html);
        $this->assertStringNotContainsString('Hidden team', $html);
    }

    public function test_the_message_form_lands_in_inquiries_rings_the_bell_and_ignores_robots(): void
    {
        [$school, $admin] = $this->site('Form School');
        $this->page($school, [['type' => 'contact_form', 'heading' => 'Write to us', 'intro' => '']]);
        $this->get('/s/'.$school->slug)->assertSee('Write to us')->assertSee('name="message"', false);

        $form = ['name' => 'Mr Okafor', 'phone' => '08031234567', 'message' => 'Do you have space in JSS 2?'];
        $this->post('/s/'.$school->slug.'/contact', $form)->assertSessionHas('contacted');
        $this->assertDatabaseHas('admission_inquiries', ['school_id' => $school->id, 'guardian_name' => 'Mr Okafor', 'source' => 'website', 'notes' => 'Do you have space in JSS 2?']);
        $this->assertDatabaseHas('school_notifications', ['user_id' => $admin->id, 'type' => 'admission_inquiry']);

        $this->post('/s/'.$school->slug.'/contact', ['name' => 'Robot', 'company_site' => 'x'] + $form)->assertSessionHas('contacted');
        $this->assertDatabaseMissing('admission_inquiries', ['guardian_name' => 'Robot']);

        $this->post('/s/'.$school->slug.'/contact', ['name' => ''] + $form)->assertSessionHasErrors('name');
    }

    public function test_the_whatsapp_button_shows_only_when_a_number_is_set(): void
    {
        [$school] = $this->site('Chat School');
        auth()->logout();
        $this->get('/s/'.$school->slug)->assertDontSee('wa.me');

        $this->actingAs($this->makeUser($school, 'school-admin'))->put('/school/website', ['whatsapp' => '0803 123 4567', 'whatsapp_text' => 'Hi there'])->assertSessionHasNoErrors();
        auth()->logout();
        $this->get('/s/'.$school->slug)->assertSee('https://wa.me/2348031234567?text=Hi%20there', false);
    }

    public function test_staff_can_be_shown_or_hidden_from_the_staff_page(): void
    {
        [$school, $admin] = $this->site('Toggle School');
        $staff = Staff::create(['school_id' => $school->id, 'first_name' => 'Ada', 'last_name' => 'Obi', 'gender' => 'female', 'salary_type' => 'fixed', 'status' => 'active']);

        $this->post("/school/staff/{$staff->id}/website", ['show' => true])->assertSessionHas('success');
        $this->assertTrue($staff->fresh()->show_on_website);
        $this->post("/school/staff/{$staff->id}/website", ['show' => false]);
        $this->assertFalse($staff->fresh()->show_on_website);
    }
}
