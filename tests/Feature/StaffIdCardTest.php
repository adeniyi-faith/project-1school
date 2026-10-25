<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Staff;
use App\Models\StaffIdCard;
use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;

class StaffIdCardTest extends SecurityTestCase
{
    private School $school;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('Cards School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);
    }

    private function staff(string $first, string $status = 'active'): Staff
    {
        return Staff::create(['school_id' => $this->school->id, 'emp_id' => 'EMP-'.$first, 'first_name' => $first, 'last_name' => 'Test', 'gender' => 'male', 'status' => $status]);
    }

    public function test_make_missing_cards_numbers_active_staff_once_each(): void
    {
        $this->staff('Ade');
        $this->staff('Bola');
        $this->staff('Gone', 'inactive');

        $this->post('/school/id-cards/staff/issue-missing')->assertSessionHasNoErrors();
        $this->assertSame(2, StaffIdCard::count());
        $this->assertSame(['STF/'.now()->year.'/0001', 'STF/'.now()->year.'/0002'], StaffIdCard::orderBy('number')->pluck('card_no')->all());

        // Running it again makes nothing new
        $this->post('/school/id-cards/staff/issue-missing');
        $this->assertSame(2, StaffIdCard::count());
    }

    public function test_replacing_a_card_needs_a_reason_and_stops_the_old_one(): void
    {
        $ade = $this->staff('Ade');
        $this->post("/school/id-cards/staff/{$ade->id}/issue")->assertSessionHasNoErrors();
        $old = StaffIdCard::sole();

        $this->post("/school/id-cards/staff/{$ade->id}/issue")->assertSessionHasErrors('reason');
        $this->post("/school/id-cards/staff/{$ade->id}/issue", ['reason' => 'Lost'])->assertSessionHasNoErrors();

        $this->assertSame('replaced', $old->fresh()->status);
        $this->assertSame(1, StaffIdCard::where('status', 'active')->count());
    }

    public function test_a_card_prints_as_a_pdf(): void
    {
        $this->staff('Ade');

        $response = $this->get('/school/id-cards/staff/print');

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertSame(1, StaffIdCard::sole()->prints);
    }

    public function test_the_qr_page_shows_only_the_basics_to_a_stranger(): void
    {
        $ade = $this->staff('Ade');
        $this->post("/school/id-cards/staff/{$ade->id}/issue");
        $card = StaffIdCard::sole();
        auth()->logout();

        $this->get('/id/'.$card->token)->assertOk()->assertSee('Ade Test')->assertDontSee('EMP-Ade');
        $this->get('/id/'.str_repeat('z', 40))->assertNotFound();
    }

    public function test_scanning_a_staff_card_shows_the_person_and_can_mark_them_present(): void
    {
        $ade = $this->staff('Ade');
        $this->post("/school/id-cards/staff/{$ade->id}/issue");
        $card = StaffIdCard::sole();

        $this->postJson('/school/id-cards/lookup', ['code' => url('/id/'.$card->token), 'mark' => 'present'])
            ->assertOk()->assertJsonPath('kind', 'staff')->assertJsonPath('student.name', 'Ade Test')->assertJsonPath('marked', 'marked');
        $this->postJson('/school/id-cards/lookup', ['code' => $card->card_no, 'mark' => 'present'])->assertJsonPath('marked', 'already');
    }

    public function test_other_schools_cards_do_not_scan_and_teachers_cannot_make_staff_cards(): void
    {
        $ade = $this->staff('Ade');
        $this->post("/school/id-cards/staff/{$ade->id}/issue");
        $card = StaffIdCard::sole();

        $other = $this->makeSchool('Other School');
        $this->actingAs($this->makeUser($other, 'school-admin'));
        $this->postJson('/school/id-cards/lookup', ['code' => $card->card_no])->assertNotFound();

        $this->actingAs($this->makeUser($this->school, 'teacher'));
        $this->get('/school/id-cards/staff')->assertForbidden();
    }
}
