<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\IdCardDesign;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentIdCard;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\Feature\Security\SecurityTestCase;

/** Student ID cards: making them in bulk and one by one, replacing, printing, designs and scanning */
class IdCardTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private SchoolClass $jss1;
    private Student $ada;
    private Student $bayo;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 08:00:00');
        $this->school = $this->makeSchool('Card School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);
        AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $this->jss1 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        $this->ada = $this->student('A1', 'Ada');
        $this->bayo = $this->student('A2', 'Bayo');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function student(string $no, string $name, ?SchoolClass $class = null): Student
    {
        return Student::create([
            'school_id' => $this->school->id, 'class_id' => ($class ?? $this->jss1)->id, 'first_name' => $name, 'last_name' => 'Test',
            'admission_no' => $no, 'gender' => 'female', 'status' => 'active',
        ]);
    }

    private function card(Student $s): StudentIdCard
    {
        return StudentIdCard::where('student_id', $s->id)->where('status', 'active')->sole();
    }

    public function test_cards_are_made_for_everyone_then_only_for_new_students(): void
    {
        $this->post('/school/id-cards/issue-missing')->assertSessionHasNoErrors();
        $this->assertSame(2, StudentIdCard::count());
        $first = $this->card($this->ada);
        $this->assertSame('ID/2026/0001', $first->card_no);
        $this->assertSame('2027-07-23', $first->expires_on->toDateString());   // end of the school year
        $this->assertSame(40, strlen($first->token));

        $chi = $this->student('A3', 'Chi');
        $this->post('/school/id-cards/issue-missing')->assertSessionHasNoErrors();
        $this->assertSame(3, StudentIdCard::count());
        $this->assertSame($first->id, $this->card($this->ada)->id);
        $this->assertSame('ID/2026/0003', $this->card($chi)->card_no);

        $this->get('/school/id-cards')->assertOk()->assertInertia(fn ($p) => $p->component('SchoolAdmin/IdCards/Index', false)
            ->where('stats.with_card', 3)->where('stats.without_card', 0)->where('stats.without_photo', 3));
    }

    public function test_bulk_making_can_be_limited_to_chosen_classes(): void
    {
        $jss2 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 2', 'numeric_name' => 8]);
        $chi = $this->student('B1', 'Chi', $jss2);
        $this->post('/school/id-cards/issue-missing', ['class_ids' => [$jss2->id]])->assertSessionHasNoErrors();
        $this->assertSame([$chi->id], StudentIdCard::pluck('student_id')->all());
    }

    public function test_replacing_a_card_needs_a_reason_and_stops_the_old_one(): void
    {
        $this->post("/school/students/{$this->ada->id}/id-card")->assertSessionHasNoErrors();
        $old = $this->card($this->ada);

        $this->post("/school/students/{$this->ada->id}/id-card")->assertSessionHasErrors('reason');
        $this->post("/school/students/{$this->ada->id}/id-card", ['reason' => 'Lost'])->assertSessionHasNoErrors();

        $this->assertSame('replaced', $old->fresh()->status);
        $new = $this->card($this->ada);
        $this->assertNotSame($old->token, $new->token);

        // the old QR now shows as no longer valid, publicly and on the scan screen
        auth()->logout();
        $this->get('/id/'.$old->token)->assertOk()->assertSee('No longer valid');
        $this->get('/id/'.$new->token)->assertOk()->assertSee('Ada Test')->assertSee('Valid');
        $this->get('/id/'.str_repeat('x', 40))->assertNotFound();
    }

    public function test_staff_who_open_a_cards_link_go_to_the_scan_screen(): void
    {
        $this->post("/school/students/{$this->ada->id}/id-card");
        $card = $this->card($this->ada);
        $this->get('/id/'.$card->token)->assertRedirect(route('school.id-cards.scan', ['code' => $card->token]));

        // staff of another school only see the public page
        $other = $this->makeSchool('Other School');
        $this->actingAs($this->makeUser($other, 'school-admin'));
        $this->get('/id/'.$card->token)->assertOk()->assertSee('Ada Test');
    }

    public function test_scanning_marks_attendance_once_and_refuses_old_cards(): void
    {
        $this->post('/school/id-cards/issue-missing');
        $card = $this->card($this->ada);

        $this->postJson('/school/id-cards/lookup', ['code' => url('/id/'.$card->token), 'mark' => 'present'])
            ->assertOk()->assertJson(['found' => true, 'marked' => 'marked', 'student' => ['name' => 'Ada Test'], 'card' => ['state' => 'valid']]);
        $this->postJson('/school/id-cards/lookup', ['code' => $card->card_no, 'mark' => 'late'])
            ->assertOk()->assertJson(['marked' => 'already', 'attendance' => 'present']);
        $this->assertSame(1, Attendance::where('attendable_id', $this->ada->id)->count());

        $this->post("/school/students/{$this->bayo->id}/id-card", ['reason' => 'Damaged']);
        $old = StudentIdCard::where('student_id', $this->bayo->id)->where('status', 'replaced')->sole();
        $this->postJson('/school/id-cards/lookup', ['code' => $old->token, 'mark' => 'present'])
            ->assertOk()->assertJson(['marked' => 'refused', 'card' => ['state' => 'replaced']]);
        $this->assertSame(0, Attendance::where('attendable_id', $this->bayo->id)->count());

        $this->postJson('/school/id-cards/lookup', ['code' => 'nothing'])->assertNotFound()->assertJson(['found' => false]);
    }

    public function test_fees_show_only_to_staff_who_may_see_fees(): void
    {
        $year = AcademicYear::where('is_current', true)->sole();
        $cat = FeeCategory::create(['school_id' => $this->school->id, 'name' => 'Tuition', 'type' => 'tuition']);
        $fee = FeeStructure::create(['school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'fee_category_id' => $cat->id, 'academic_year' => '2026-2027', 'amount' => 50000, 'frequency' => 'one_time']);
        $this->post('/school/fees/invoices', ['fee_structure_ids' => [$fee->id], 'term_id' => $year->terms()->first()->id])->assertSessionHasNoErrors();
        $this->post('/school/id-cards/issue-missing');
        $code = $this->card($this->ada)->card_no;

        $this->postJson('/school/id-cards/lookup', ['code' => $code])->assertOk()->assertJsonPath('fees.owed', 50000)->assertJsonPath('fees.open_invoices', 1);

        $this->actingAs($this->makeUser($this->school, 'teacher'));
        $this->postJson('/school/id-cards/lookup', ['code' => $code])->assertOk()->assertJsonPath('fees', null);

        // a receptionist can check a card but not mark attendance
        $this->actingAs($this->makeUser($this->school, 'receptionist'));
        $this->postJson('/school/id-cards/lookup', ['code' => $code])->assertOk()->assertJsonPath('found', true);
        $this->postJson('/school/id-cards/lookup', ['code' => $code, 'mark' => 'present'])->assertForbidden();
    }

    public function test_another_schools_card_is_not_found(): void
    {
        $this->post('/school/id-cards/issue-missing');
        $code = $this->card($this->ada)->token;

        $this->actingAs($this->makeUser($this->makeSchool('Other School'), 'school-admin'));
        $this->postJson('/school/id-cards/lookup', ['code' => $code])->assertNotFound();
        $this->post("/school/students/{$this->ada->id}/id-card")->assertNotFound();
    }

    public function test_cards_print_as_pdf_and_count_prints(): void
    {
        $this->get('/school/id-cards/print?'.http_build_query(['class_ids' => [$this->jss1->id]]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame([1, 1], StudentIdCard::orderBy('id')->pluck('prints')->all());

        // "not printed yet" leaves nothing to print now
        $this->get('/school/id-cards/print?'.http_build_query(['class_ids' => [$this->jss1->id], 'not_printed' => 1]))
            ->assertStatus(422)->assertSee('no cards left to print', false);

        $this->get("/school/students/{$this->ada->id}/id-card")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get('/school/id-cards/print?'.http_build_query(['student_ids' => [$this->bayo->id], 'layout' => 'single']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame(2, $this->card($this->ada)->prints);
    }

    public function test_designs_save_preview_and_only_admins_change_them(): void
    {
        $this->get('/school/id-cards/designs')->assertOk()->assertInertia(fn ($p) => $p->component('SchoolAdmin/IdCards/Designs', false)->has('designs', 1)->where('designs.0.name', 'Standard'));

        $design = [
            'name' => 'Sideways', 'orientation' => 'landscape', 'template' => 'band', 'primary_color' => '#123456', 'title' => 'STUDENT ID',
            'show' => ['gender' => true, 'qr' => false], 'valid_months' => 12, 'is_default' => true,
        ];
        $this->post('/school/id-cards/designs/try', $design)->assertOk()->assertSee('STUDENT ID');
        $this->post('/school/id-cards/designs', $design)->assertSessionHasNoErrors();
        $this->assertSame(1, IdCardDesign::where('is_default', true)->count());
        $saved = IdCardDesign::where('name', 'Sideways')->sole();
        $this->assertTrue($saved->is_default);
        $this->assertTrue($saved->switches()['gender']);
        $this->assertFalse($saved->switches()['qr']);

        // a 12-month design sets each new card's expiry
        $this->post("/school/students/{$this->ada->id}/id-card");
        $this->assertSame('2027-10-20', $this->card($this->ada)->expires_on->toDateString());

        $this->actingAs($this->makeUser($this->school, 'principal'));
        $this->get('/school/id-cards/designs')->assertOk();
        $this->post('/school/id-cards/designs', $design)->assertForbidden();
    }

    public function test_student_page_offers_the_card_tab_only_to_card_makers(): void
    {
        $this->post("/school/students/{$this->ada->id}/id-card");
        $this->get("/school/students/{$this->ada->id}")->assertInertia(fn ($p) => $p->has('idCards', 1)->where('idCards.0.state', 'valid'));

        $this->actingAs($this->makeUser($this->school, 'teacher'));
        $this->get("/school/students/{$this->ada->id}")->assertInertia(fn ($p) => $p->where('idCards', null));
        $this->get('/school/id-cards')->assertForbidden();
        $this->get('/school/id-cards/scan')->assertOk();
    }
}
