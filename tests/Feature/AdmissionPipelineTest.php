<?php

namespace Tests\Feature;

use App\Models\AdmissionAssessment;
use App\Models\AdmissionInquiry;
use App\Models\Guardian;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;

/** An admission inquiry → entrance exam / interview → accept → one-step enrolment. */
class AdmissionPipelineTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private SchoolClass $jss1;
    private AdmissionInquiry $inquiry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = $this->makeSchool('Admissions School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);

        $this->jss1 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1']);
        $this->inquiry = AdmissionInquiry::create([
            'school_id' => $this->school->id, 'student_name' => 'Tolu Adeyemi', 'class_interested' => 'JSS 1',
            'guardian_name' => 'Mrs Adeyemi', 'guardian_phone' => '08031112222', 'guardian_email' => 'adeyemi@example.test',
            'source' => 'online',
        ]);
    }

    private function url(string $path = ''): string
    {
        return "/school/admissions/inquiries/{$this->inquiry->id}{$path}";
    }

    private function enrolData(array $overrides = []): array
    {
        return $overrides + [
            'first_name' => 'Tolu', 'last_name' => 'Adeyemi', 'gender' => 'female', 'date_of_birth' => '2014-05-02',
            'admission_date' => '2026-09-07', 'class_id' => $this->jss1->id, 'guardian_relation' => 'Mother',
        ];
    }

    public function test_an_entrance_exam_and_interview_are_scheduled_then_marked(): void
    {
        $this->post($this->url('/assessments'), ['type' => 'exam', 'scheduled_at' => '2026-07-04 09:00', 'venue' => 'Main hall', 'outcome' => 'pending'])
            ->assertSessionHasNoErrors();
        $exam = AdmissionAssessment::sole();
        $this->assertSame('follow_up', $this->inquiry->fresh()->status, 'booking a test moves a new inquiry along');

        $this->put($this->url("/assessments/{$exam->id}"), ['score' => 78, 'max_score' => 100, 'outcome' => 'passed', 'remarks' => 'Strong in Maths'])
            ->assertSessionHasNoErrors();
        $this->assertSame([78.0, 'passed', $this->admin->id], [$exam->fresh()->score, $exam->fresh()->outcome, $exam->fresh()->recorded_by]);

        // A score above the total is refused
        $this->put($this->url("/assessments/{$exam->id}"), ['score' => 120, 'max_score' => 100, 'outcome' => 'passed'])->assertSessionHasErrors('score');

        $this->post($this->url('/assessments'), ['type' => 'interview', 'outcome' => 'passed', 'remarks' => 'Confident, polite'])->assertSessionHasNoErrors();

        $this->get($this->url(), ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonCount(2, 'props.assessments')
            ->assertJsonPath('props.assessments.0.type', 'exam')
            ->assertJsonPath('props.assessments.0.score', 78);
    }

    public function test_accepting_then_enrolling_makes_the_student_in_one_step(): void
    {
        $this->post($this->url('/enrol'), $this->enrolData())->assertSessionHasErrors('inquiry'); // not accepted yet

        $this->post($this->url('/decision'), ['decision' => 'accept', 'note' => 'Passed both'])->assertSessionHasNoErrors();
        $this->assertSame('accepted', $this->inquiry->fresh()->status);
        $this->assertSame($this->admin->id, $this->inquiry->fresh()->decided_by);

        $section = Section::create(['school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'name' => 'Gold']);
        $response = $this->post($this->url('/enrol'), $this->enrolData(['section_id' => $section->id]));

        $student = Student::sole();
        $response->assertRedirect("/school/students/{$student->id}");
        $this->assertSame(['Tolu', 'Adeyemi', $this->jss1->id, $section->id, 'active'], [$student->first_name, $student->last_name, $student->class_id, $student->section_id, $student->status]);
        $this->assertNotEmpty($student->admission_no);

        $guardian = $student->guardian;
        $this->assertSame(['Mrs Adeyemi', '08031112222', 'Mother'], [$guardian->name, $guardian->phone, $guardian->relation]);

        $inquiry = $this->inquiry->fresh();
        $this->assertSame('admitted', $inquiry->status);
        $this->assertSame($student->id, $inquiry->converted_student_id);
        $this->assertSame($this->admin->id, $inquiry->enrolled_by);

        // Enrolling again, or booking more tests, is refused
        $this->post($this->url('/enrol'), $this->enrolData())->assertSessionHasErrors('inquiry');
        $this->assertSame(1, Student::count());
        $this->post($this->url('/assessments'), ['type' => 'exam', 'outcome' => 'pending'])->assertSessionHasErrors('inquiry');
    }

    public function test_a_parent_already_on_file_is_reused_so_siblings_are_linked(): void
    {
        $mum = Guardian::create(['school_id' => $this->school->id, 'name' => 'Mrs Adeyemi', 'phone' => '08031112222', 'relation' => 'Mother']);
        Student::create(['school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'guardian_id' => $mum->id, 'first_name' => 'Kemi', 'last_name' => 'Adeyemi', 'gender' => 'female', 'status' => 'active']);

        $this->get($this->url(), ['X-Inertia' => 'true'])
            ->assertJsonPath('props.matchingGuardians.0.id', $mum->id)
            ->assertJsonPath('props.matchingGuardians.0.children.0', 'Kemi Adeyemi');

        $this->post($this->url('/decision'), ['decision' => 'accept']);
        $this->post($this->url('/enrol'), $this->enrolData(['guardian_id' => $mum->id, 'guardian_relation' => null]))->assertSessionHasNoErrors();

        $this->assertSame(1, Guardian::count());
        $this->assertSame(2, $mum->students()->count());
    }

    public function test_declining_needs_a_reason_and_closes_the_application(): void
    {
        $this->post($this->url('/decision'), ['decision' => 'decline'])->assertSessionHasErrors('note');

        $this->post($this->url('/decision'), ['decision' => 'decline', 'note' => 'No space in JSS 1 this year'])->assertSessionHasNoErrors();

        $this->assertSame('dropped', $this->inquiry->fresh()->status);
        $this->post($this->url('/decision'), ['decision' => 'accept'])->assertSessionHasErrors('inquiry');
    }

    public function test_editing_contact_details_cannot_mark_a_child_admitted_or_undo_an_enrolment(): void
    {
        $base = ['student_name' => 'Tolu Adeyemi', 'class_interested' => 'JSS 1', 'guardian_name' => 'Mrs Adeyemi', 'guardian_phone' => '08031112222'];

        // The old shortcut of picking "Admitted" from a list is gone: admitted means a student record exists
        $this->put($this->url(), $base + ['status' => 'admitted'])->assertSessionHasErrors('status');

        $this->post($this->url('/decision'), ['decision' => 'accept']);
        $this->post($this->url('/enrol'), $this->enrolData());
        $this->put($this->url(), array_merge($base, ['guardian_phone' => '08039998888', 'status' => 'new']))->assertSessionHasNoErrors();

        $this->assertSame('admitted', $this->inquiry->fresh()->status);
        $this->assertSame('08039998888', $this->inquiry->fresh()->guardian_phone);
    }

    public function test_class_section_and_guardian_must_belong_to_this_school(): void
    {
        $other = $this->makeSchool('Other Admissions School');
        $theirClass = SchoolClass::withoutGlobalScopes()->create(['school_id' => $other->id, 'name' => 'JSS 1']);
        $theirGuardian = Guardian::withoutGlobalScopes()->create(['school_id' => $other->id, 'name' => 'Stranger', 'phone' => '08030000000', 'relation' => 'Father']);
        $mySectionInOtherClass = Section::create(['school_id' => $this->school->id, 'class_id' => SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 2'])->id, 'name' => 'Blue']);

        $this->post($this->url('/decision'), ['decision' => 'accept']);
        $this->post($this->url('/enrol'), $this->enrolData(['class_id' => $theirClass->id]))->assertSessionHasErrors('class_id');
        $this->post($this->url('/enrol'), $this->enrolData(['guardian_id' => $theirGuardian->id]))->assertSessionHasErrors('guardian_id');
        $this->post($this->url('/enrol'), $this->enrolData(['section_id' => $mySectionInOtherClass->id]))->assertSessionHasErrors('section_id');

        $this->assertSame(0, Student::withoutGlobalScopes()->count());
    }

    public function test_another_school_cannot_open_or_act_on_this_inquiry(): void
    {
        $this->actingAs($this->makeUser($this->makeSchool('Nosy School'), 'school-admin'));

        $this->get($this->url(), ['X-Inertia' => 'true'])->assertNotFound();
        $this->post($this->url('/decision'), ['decision' => 'accept'])->assertNotFound();
        $this->post($this->url('/enrol'), $this->enrolData())->assertNotFound();
        $this->assertSame('new', $this->inquiry->fresh()->status);
    }

    public function test_who_can_do_what(): void
    {
        $principal = $this->makeUser($this->school, 'principal');
        $teacher = $this->makeUser($this->school, 'teacher');

        // Principals run exams and interviews and decide, but do not create student records
        $this->actingAs($principal)->post($this->url('/assessments'), ['type' => 'interview', 'outcome' => 'passed'])->assertSessionHasNoErrors();
        $this->actingAs($principal)->post($this->url('/decision'), ['decision' => 'accept'])->assertSessionHasNoErrors();
        $this->actingAs($principal)->get($this->url(), ['X-Inertia' => 'true'])->assertJsonPath('props.can.enrol', false);
        $this->actingAs($principal)->post($this->url('/enrol'), $this->enrolData())->assertForbidden();

        // Teachers can see inquiries but not change their course
        $this->actingAs($teacher)->post($this->url('/decision'), ['decision' => 'decline', 'note' => 'x'])->assertForbidden();
        $this->actingAs($teacher)->post($this->url('/assessments'), ['type' => 'exam', 'outcome' => 'pending'])->assertForbidden();

        $this->assertSame('accepted', $this->inquiry->fresh()->status);
    }
}
