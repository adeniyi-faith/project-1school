<?php

namespace Tests\Feature\Correctness;

use App\Models\AdmissionInquiry;
use App\Models\Department;
use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use Carbon\Carbon;
use Tests\Feature\Security\SecurityTestCase;

/** Right-answer checks for admitting students, enrolling from an inquiry, and registering staff. */
class PeopleCorrectnessTest extends SecurityTestCase
{
    private $school;
    private $admin;
    private SchoolClass $jss1;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 10:00:00');
        $this->school = $this->makeSchool('People Right School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);
        $this->jss1 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1']);
    }

    private function studentForm(array $over = []): array
    {
        return array_replace_recursive([
            'first_name' => 'Ada', 'last_name' => 'Obi', 'gender' => 'female', 'category' => 'general', 'status' => 'active',
            'class_id' => $this->jss1->id,
            'guardian' => ['name' => 'Mrs Obi', 'relation' => 'Mother', 'phone' => '08031234567'],
        ], $over);
    }

    public function test_admitting_a_student_saves_exactly_what_was_typed_and_numbers_them_in_order(): void
    {
        $this->post('/school/students', $this->studentForm())->assertSessionHasNoErrors();
        $this->post('/school/students', $this->studentForm(['first_name' => 'Bola']))->assertSessionHasNoErrors();
        $this->post('/school/students', $this->studentForm(['first_name' => 'Chidi']))->assertSessionHasNoErrors();

        // Numbers run 0001, 0002, 0003 for this year, with no gaps or repeats
        $nos = Student::orderBy('id')->pluck('admission_no')->all();
        $this->assertSame(['ADM-2026-0001', 'ADM-2026-0002', 'ADM-2026-0003'], $nos);

        $ada = Student::where('first_name', 'Ada')->first();
        $this->assertSame('Obi', $ada->last_name);
        $this->assertSame($this->jss1->id, $ada->class_id);
        $this->assertSame('Mrs Obi', $ada->guardian->name);
        $this->assertSame('08031234567', $ada->guardian->phone);
        $this->assertSame($this->school->id, $ada->school_id);
        $this->assertSame($this->school->id, $ada->guardian->school_id);
    }

    public function test_each_school_counts_its_own_admission_numbers(): void
    {
        $this->post('/school/students', $this->studentForm())->assertSessionHasNoErrors();
        $other = $this->makeSchool('Another School');
        $this->actingAs($this->makeUser($other, 'school-admin'));
        $c = SchoolClass::create(['school_id' => $other->id, 'name' => 'Primary 1']);
        $this->post('/school/students', $this->studentForm(['class_id' => $c->id]))->assertSessionHasNoErrors();

        $this->assertSame('ADM-2026-0001', Student::withoutGlobalScopes()->where('school_id', $other->id)->value('admission_no'));
    }

    public function test_names_with_apostrophes_accents_and_markup_are_kept_as_plain_text(): void
    {
        $names = ["O'Brien", 'Ọlá-Adébáyọ̀', 'Chukwuemeka Ñ', '<b>Bold</b>', '"Quote"', 'Muhammad bin Abdullah bin Abdul Aziz bin Saud'];
        foreach ($names as $n) {
            $this->post('/school/students', $this->studentForm(['first_name' => $n]))->assertSessionHasNoErrors();
        }
        foreach ($names as $n) {
            $this->assertSame(1, Student::where('first_name', $n)->count(), "name '$n' was not saved exactly");
        }
        // The list page finds them by search
        $this->get('/school/students?search=' . urlencode("O'Brien"))->assertOk()->assertSee("O'Brien", false);
    }

    public function test_wrong_student_inputs_are_refused_and_nothing_is_saved(): void
    {
        $bad = [
            ['first_name' => ''],
            ['first_name' => str_repeat('a', 101)],
            ['gender' => 'robot'],
            ['status' => 'banana'],
            ['category' => 'vip'],
            ['class_id' => 999999],
            ['email' => 'not-an-email'],
            ['date_of_birth' => 'tomorrow-ish'],
            ['guardian' => ['name' => '']],
            ['guardian' => ['relation' => '']],
        ];
        foreach ($bad as $n => $over) {
            $this->post('/school/students', $this->studentForm($over))->assertSessionHasErrors();
        }
        $this->assertSame(0, Student::count());
        $this->assertSame(0, Guardian::count());
    }

    public function test_a_student_cannot_be_put_in_another_schools_class_or_arm(): void
    {
        $other = $this->makeSchool('Rival School');
        $theirClass = SchoolClass::create(['school_id' => $other->id, 'name' => 'Rival JSS 1']);
        $theirArm = Section::create(['school_id' => $other->id, 'class_id' => $theirClass->id, 'name' => 'A']);

        $this->post('/school/students', $this->studentForm(['class_id' => $theirClass->id]))->assertSessionHasErrors('class_id');
        $this->post('/school/students', $this->studentForm(['section_id' => $theirArm->id]))->assertSessionHasErrors('section_id');
        $this->assertSame(0, Student::withoutGlobalScopes()->count());

        // same check when editing an existing student
        $this->post('/school/students', $this->studentForm())->assertSessionHasNoErrors();
        $s = Student::first();
        $this->put("/school/students/{$s->id}", $this->studentForm(['class_id' => $theirClass->id]))->assertSessionHasErrors('class_id');
        $this->assertSame($this->jss1->id, $s->fresh()->class_id);
    }

    public function test_an_arm_must_belong_to_the_chosen_class(): void
    {
        $jss2 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 2']);
        $armOfJss2 = Section::create(['school_id' => $this->school->id, 'class_id' => $jss2->id, 'name' => 'A']);
        $this->post('/school/students', $this->studentForm(['section_id' => $armOfJss2->id]))->assertSessionHasErrors('section_id');
    }

    public function test_a_birth_date_in_the_future_is_refused(): void
    {
        $this->post('/school/students', $this->studentForm(['date_of_birth' => '2027-01-01']))->assertSessionHasErrors('date_of_birth');
        $this->assertSame(0, Student::count());
    }

    public function test_only_the_right_roles_can_admit_or_edit_students(): void
    {
        foreach (['teacher', 'parent', 'student', 'librarian'] as $role) {
            $this->actingAs($this->makeUser($this->school, $role));
            $this->post('/school/students', $this->studentForm())->assertForbidden();
        }
        $this->assertSame(0, Student::count());
    }

    public function test_enrolling_from_an_inquiry_makes_one_student_and_one_parent_with_the_right_details(): void
    {
        $inq = AdmissionInquiry::create(['school_id' => $this->school->id, 'student_name' => 'Tolu Adeyemi', 'class_interested' => 'JSS 1', 'guardian_name' => 'Mrs Adeyemi', 'guardian_phone' => '08031112222', 'guardian_email' => 'adeyemi@example.test', 'source' => 'online']);

        // Not accepted yet: cannot enrol
        $form = ['first_name' => 'Tolu', 'last_name' => 'Adeyemi', 'gender' => 'female', 'admission_date' => '2026-09-07', 'class_id' => $this->jss1->id, 'guardian_relation' => 'Mother'];
        $this->post("/school/admissions/inquiries/{$inq->id}/enrol", $form)->assertSessionHasErrors('inquiry');
        $this->assertSame(0, Student::count());

        $this->post("/school/admissions/inquiries/{$inq->id}/decision", ['decision' => 'accept'])->assertSessionHasNoErrors();
        $this->post("/school/admissions/inquiries/{$inq->id}/enrol", $form)->assertSessionHasNoErrors();

        $s = Student::sole();
        $this->assertSame('Tolu', $s->first_name);
        $this->assertSame($this->jss1->id, $s->class_id);
        $this->assertSame('active', $s->status);
        $this->assertSame('ADM-2026-0001', $s->admission_no);
        $g = Guardian::sole();
        $this->assertSame('Mrs Adeyemi', $g->name);
        $this->assertSame('08031112222', $g->phone);
        $this->assertSame($g->id, $s->guardian_id);
        $inq = $inq->fresh();
        $this->assertSame('admitted', $inq->status);
        $this->assertSame($s->id, $inq->converted_student_id);

        // A second click (or a second tab) cannot create a twin
        $this->post("/school/admissions/inquiries/{$inq->id}/enrol", $form)->assertSessionHasErrors('inquiry');
        $this->assertSame(1, Student::count());
        $this->assertSame(1, Guardian::count());
    }

    public function test_declined_inquiry_cannot_be_enrolled_and_needs_a_reason(): void
    {
        $inq = AdmissionInquiry::create(['school_id' => $this->school->id, 'student_name' => 'Kemi', 'class_interested' => 'JSS 1', 'guardian_name' => 'Mr K', 'guardian_phone' => '08030000001', 'source' => 'walk_in']);
        $this->post("/school/admissions/inquiries/{$inq->id}/decision", ['decision' => 'decline'])->assertSessionHasErrors('note');
        $this->post("/school/admissions/inquiries/{$inq->id}/decision", ['decision' => 'decline', 'note' => 'No space'])->assertSessionHasNoErrors();
        $this->post("/school/admissions/inquiries/{$inq->id}/enrol", ['first_name' => 'Kemi', 'gender' => 'female', 'admission_date' => '2026-09-07', 'class_id' => $this->jss1->id, 'guardian_relation' => 'Father'])->assertSessionHasErrors();
        $this->assertSame(0, Student::count());
    }

    public function test_the_public_application_form_saves_a_new_inquiry_for_the_right_school_only(): void
    {
        auth()->logout();
        $this->post("/apply/{$this->school->id}", ['student_name' => 'Zainab Bello', 'class_interested' => 'JSS 1', 'guardian_name' => 'Mr Bello', 'guardian_phone' => '08090000000'])->assertSessionHasNoErrors();
        $i = AdmissionInquiry::withoutGlobalScopes()->sole();
        $this->assertSame($this->school->id, $i->school_id);
        $this->assertSame('new', $i->status);
        $this->assertSame('online', $i->source);

        // Missing fields are refused
        $this->post("/apply/{$this->school->id}", ['student_name' => ''])->assertSessionHasErrors(['student_name', 'class_interested', 'guardian_name', 'guardian_phone']);
        $this->assertSame(1, AdmissionInquiry::withoutGlobalScopes()->count());
    }

    public function test_the_public_application_form_slows_down_a_flood(): void
    {
        auth()->logout();
        $codes = [];
        for ($n = 0; $n < 40; $n++) {
            $codes[] = $this->post("/apply/{$this->school->id}", ['student_name' => "Spam $n", 'class_interested' => 'JSS 1', 'guardian_name' => 'Bot', 'guardian_phone' => '0800000000'])->status();
        }
        $this->assertContains(429, $codes, 'forty forms in a minute from one place should be slowed down');
    }

    public function test_registering_staff_numbers_them_and_refuses_bad_money_values(): void
    {
        $base = ['first_name' => 'Ngozi', 'last_name' => 'Eze', 'gender' => 'female', 'salary_type' => 'fixed', 'salary' => 185000.50, 'status' => 'active'];
        $this->post('/school/staff', $base)->assertSessionHasNoErrors();
        $this->post('/school/staff', array_merge($base, ['first_name' => 'Tunde']))->assertSessionHasNoErrors();
        $this->assertSame(['EMP-2026-0001', 'EMP-2026-0002'], Staff::orderBy('id')->pluck('emp_id')->all());
        $this->assertEquals(185000.50, (float) Staff::first()->salary);

        $this->post('/school/staff', array_merge($base, ['salary' => -1]))->assertSessionHasErrors('salary');
        $this->post('/school/staff', array_merge($base, ['salary' => 'lots']))->assertSessionHasErrors('salary');
        $this->post('/school/staff', array_merge($base, ['salary_type' => 'weekly']))->assertSessionHasErrors('salary_type');
        $this->post('/school/staff', array_merge($base, ['email' => 'nope']))->assertSessionHasErrors('email');
        $this->post('/school/staff', array_merge($base, ['first_name' => '']))->assertSessionHasErrors('first_name');
        $this->assertSame(2, Staff::count());
    }

    public function test_staff_cannot_be_put_in_another_schools_department(): void
    {
        $other = $this->makeSchool('Rival School');
        $theirs = Department::create(['school_id' => $other->id, 'name' => 'Science']);
        $this->post('/school/staff', ['first_name' => 'Ngozi', 'gender' => 'female', 'salary_type' => 'fixed', 'status' => 'active', 'department_id' => $theirs->id])
            ->assertSessionHasErrors('department_id');
        $this->assertSame(0, Staff::count());
    }

    public function test_only_the_right_roles_can_register_staff(): void
    {
        foreach (['teacher', 'parent', 'student', 'accountant', 'librarian'] as $role) {
            $this->actingAs($this->makeUser($this->school, $role));
            $this->post('/school/staff', ['first_name' => 'X', 'gender' => 'male', 'salary_type' => 'fixed', 'status' => 'active'])->assertForbidden();
        }
        $this->assertSame(0, Staff::count());
    }
}
