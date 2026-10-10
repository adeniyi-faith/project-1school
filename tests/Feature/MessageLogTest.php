<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\MessageBatch;
use App\Models\MessageDelivery;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Security\SecurityTestCase;

/** The sent-messages log: who a blast goes to, a line per person, failures and trying again. */
class MessageLogTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private SchoolClass $jss1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = $this->makeSchool('Msg School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);
        $this->jss1 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        $jss2 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 2', 'numeric_name' => 8]);

        $mum = Guardian::create(['school_id' => $this->school->id, 'name' => 'Mrs Obi', 'phone' => '08031234567', 'email' => 'obi@example.test']);
        $dad = Guardian::create(['school_id' => $this->school->id, 'name' => 'Mr Bello', 'phone' => '0803', 'email' => null]);
        $this->student('Ada', $this->jss1, $mum, 'ada@example.test');
        $this->student('Chidi', $jss2, $mum); // Mrs Obi has two children
        $this->student('Bayo', $this->jss1, $dad);
        $this->student('Femi', $this->jss1, null);
        $this->student('Gone', $this->jss1, $mum, 'gone@example.test', 'alumni');

        Staff::create(['school_id' => $this->school->id, 'emp_id' => 'E1', 'first_name' => 'Tola', 'last_name' => 'Ade', 'email' => 'tola@example.test', 'phone' => '08020000000', 'status' => 'active', 'joining_date' => '2024-01-01', 'gender' => 'female']);
    }

    private function student(string $name, SchoolClass $class, ?Guardian $guardian, ?string $email = null, string $status = 'active'): Student
    {
        return Student::create([
            'school_id' => $this->school->id, 'class_id' => $class->id, 'guardian_id' => $guardian?->id, 'first_name' => $name, 'last_name' => 'Test',
            'admission_no' => 'A-'.$name, 'gender' => 'female', 'status' => $status, 'date_of_birth' => '2014-01-01', 'email' => $email,
        ]);
    }

    private function blast(array $data)
    {
        return $this->post('/school/communication/blast', $data + ['subject' => 'Resumption', 'message' => 'School resumes on Monday.']);
    }

    private function lines(): array
    {
        return MessageDelivery::orderBy('id')->get()->mapWithKeys(fn ($d) => [$d->recipient_name => $d->status])->all();
    }

    public function test_an_email_to_all_parents_reaches_each_parent_once_and_records_the_gaps(): void
    {
        Mail::fake();
        $this->blast(['channel' => 'email', 'audience' => 'all_parents'])->assertRedirect('/school/communication/sent/'.MessageBatch::sole()->id);

        $this->assertSame([
            'Mrs Obi (parent of Ada and Chidi)' => 'sent',
            'Mr Bello (parent of Bayo)' => 'not_sent',
            'Parent of Femi Test (none on record)' => 'not_sent',
        ], $this->lines());
        $this->assertSame('No email address on record.', MessageDelivery::where('recipient_name', 'like', 'Mr Bello%')->value('error'));
        $this->assertNotNull(MessageDelivery::where('status', 'sent')->value('sent_at'));
    }

    public function test_a_class_blast_goes_to_that_class_only(): void
    {
        Mail::fake();
        $this->blast(['channel' => 'email', 'audience' => 'class', 'class_id' => $this->jss1->id])->assertSessionHasNoErrors();

        $this->assertSame('Parents of JSS 1', MessageBatch::sole()->audience);
        $this->assertSame(['Mrs Obi (parent of Ada)', 'Mr Bello (parent of Bayo)', 'Parent of Femi Test (none on record)'], array_keys($this->lines()));
        $this->blast(['channel' => 'email', 'audience' => 'class'])->assertSessionHasErrors('class_id');
    }

    public function test_sms_without_a_provider_shows_as_failed_and_can_be_tried_again(): void
    {
        $this->blast(['channel' => 'sms', 'audience' => 'all_parents'])->assertSessionHasNoErrors();
        $batch = MessageBatch::sole();

        $obi = MessageDelivery::where('recipient_name', 'like', 'Mrs Obi%')->sole();
        $this->assertSame('failed', $obi->status);
        $this->assertSame(MessageService::NO_SMS_PROVIDER, $obi->error);
        $this->assertSame('The phone number on record is too short.', MessageDelivery::where('recipient_name', 'like', 'Mr Bello%')->value('error'));

        $this->get("/school/communication/sent/{$batch->id}", ['X-Inertia' => 'true'])
            ->assertJsonPath('props.batch.counts.failed', 1)
            ->assertJsonPath('props.batch.counts.not_sent', 2)
            ->assertJsonPath('props.batch.total', 3);

        $this->post("/school/communication/sent/{$batch->id}/retry")->assertSessionHas('success');
        $this->assertSame(2, $obi->fresh()->attempts, 'only the failed line was tried again');
        $this->assertSame(0, MessageDelivery::where('recipient_name', 'like', 'Mr Bello%')->value('attempts'));
    }

    public function test_an_email_the_server_refuses_is_marked_failed(): void
    {
        Mail::shouldReceive('raw')->andThrow(new \RuntimeException('Connection refused'));
        $this->blast(['channel' => 'email', 'audience' => 'all_staff'])->assertSessionHasNoErrors();

        $line = MessageDelivery::sole();
        $this->assertSame('Tola Ade', $line->recipient_name);
        $this->assertSame('failed', $line->status);
        $this->assertStringContainsString('Connection refused', $line->error);
    }

    public function test_students_and_the_list_page(): void
    {
        Mail::fake();
        $this->blast(['channel' => 'email', 'audience' => 'all_students']);
        $this->assertSame(['Ada Test' => 'sent', 'Bayo Test' => 'not_sent', 'Chidi Test' => 'not_sent', 'Femi Test' => 'not_sent'], $this->lines(), 'students who left are not included');

        $this->get('/school/communication/sent', ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.batches.data.0.audience', 'All students')
            ->assertJsonPath('props.batches.data.0.counts.sent', 1);
        $this->get('/school/communication/sent?channel=sms', ['X-Inertia' => 'true'])->assertJsonCount(0, 'props.batches.data');
    }

    public function test_who_may_see_and_other_schools(): void
    {
        Mail::fake();
        $this->blast(['channel' => 'email', 'audience' => 'all_parents']);
        $batch = MessageBatch::sole();

        $this->actingAs($this->makeUser($this->school, 'teacher'));
        $this->get('/school/communication/sent')->assertForbidden();
        $this->blast(['channel' => 'email', 'audience' => 'all_parents'])->assertForbidden();

        $this->actingAs($this->makeUser($this->makeSchool('Other'), 'school-admin'));
        $this->get("/school/communication/sent/{$batch->id}")->assertNotFound();
        $this->post("/school/communication/sent/{$batch->id}/retry")->assertNotFound();
        $this->get('/school/communication/sent', ['X-Inertia' => 'true'])->assertJsonCount(0, 'props.batches.data');
        $this->blast(['channel' => 'email', 'audience' => 'class', 'class_id' => $this->jss1->id])->assertSessionHasErrors('class_id');
    }
}
