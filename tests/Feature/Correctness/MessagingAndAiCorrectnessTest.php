<?php

namespace Tests\Feature\Correctness;

use App\Models\AiUsage;
use App\Models\Guardian;
use App\Models\MessageBatch;
use App\Models\MessageDelivery;
use App\Models\PlatformSetting;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Staff;
use App\Models\Student;
use App\Services\AiService;
use Carbon\Carbon;
use Tests\Feature\Security\SecurityTestCase;

/**
 * Right-answer checks for text and email blasts (who is on the list, and the counts in the sent log)
 * and for the AI's cost and monthly allowance. Every figure is worked out by hand.
 */
class MessagingAndAiCorrectnessTest extends SecurityTestCase
{
    private $school;
    private SchoolClass $jss1;
    private SchoolClass $jss2;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.default' => 'array']);
        $this->school = $this->makeSchool('Blast School');
        $this->jss1 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1']);
        $this->jss2 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 2']);

        $parent = fn (string $name, ?string $phone, ?string $email) => Guardian::create(['school_id' => $this->school->id, 'name' => $name, 'relation' => 'Parent', 'phone' => $phone, 'email' => $email]);
        $pupil = fn (string $first, SchoolClass $c, ?Guardian $g, string $status = 'active') => Student::create(['school_id' => $this->school->id, 'class_id' => $c->id, 'guardian_id' => $g?->id, 'first_name' => $first, 'last_name' => 'Pupil', 'gender' => 'female', 'status' => $status]);

        $obi = $parent('Mrs Obi', '08031112222', 'obi@example.test');
        $eze = $parent('Mr Eze', '0803 111 2222', 'eze@example.test');       // the same number as Mrs Obi, typed with spaces
        $bad = $parent('Mr Bad', '0803', 'not-an-email');                       // number too short, address not an address
        $none = $parent('Mrs None', null, null);
        $gone = $parent('Mr Gone', '08099998888', 'gone@example.test');
        $pupil('Ada', $this->jss1, $obi);
        $pupil('Bayo', $this->jss1, $obi);      // a second child of Mrs Obi: she is still one person on the list
        $pupil('Chidi', $this->jss2, $eze);
        $pupil('Dupe', $this->jss2, $bad);
        $pupil('Efe', $this->jss1, $none);
        $pupil('Femi', $this->jss2, null);      // no parent on record at all
        $pupil('Gbenga', $this->jss2, $gone, 'transferred'); // left the school: never messaged

        // Another school's people must never appear
        $other = $this->makeSchool('Other Blast School');
        $oc = SchoolClass::create(['school_id' => $other->id, 'name' => 'X']);
        $og = Guardian::create(['school_id' => $other->id, 'name' => 'Far Parent', 'relation' => 'Parent', 'phone' => '08077776666', 'email' => 'far@example.test']);
        Student::withoutGlobalScopes()->create(['school_id' => $other->id, 'class_id' => $oc->id, 'guardian_id' => $og->id, 'first_name' => 'Zed', 'last_name' => 'Far', 'gender' => 'male', 'status' => 'active']);

        foreach ([['Active One', 'active'], ['Resigned', 'resigned'], ['Fired', 'terminated']] as $i => [$name, $status]) {
            Staff::create(['school_id' => $this->school->id, 'emp_id' => "E$i", 'first_name' => $name, 'last_name' => 'Staff', 'gender' => 'female', 'status' => $status, 'phone' => '0803000000'.$i, 'email' => "staff$i@example.test", 'joining_date' => '2024-01-01']);
        }
        $this->actingAs($this->makeUser($this->school, 'school-admin'));
    }

    private function blast(string $channel, string $audience, array $over = [])
    {
        return $this->post('/school/communication/blast', $over + ['channel' => $channel, 'audience' => $audience, 'subject' => 'Notice', 'message' => 'School closes early on Friday.']);
    }

    private function counts(MessageBatch $b): array
    {
        return $b->deliveries()->selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status')->all() + ['sent' => 0, 'failed' => 0, 'not_sent' => 0, 'queued' => 0];
    }

    public function test_an_email_to_all_parents_goes_once_to_each_parent_and_lists_the_gaps(): void
    {
        $this->blast('email', 'all_parents')->assertSessionHasNoErrors();
        $b = MessageBatch::sole();
        // Mrs Obi once (two children), Mr Eze, Mr Bad (bad address), Mrs None (no address), Femi (no parent) = 5 lines.
        // Not listed: Mr Gone (his child left), the other school's parent.
        $this->assertSame(5, $b->deliveries()->count());
        $c = $this->counts($b);
        $this->assertSame(2, $c['sent']);
        $this->assertSame(3, $c['not_sent']);
        $this->assertSame(0, $c['failed']);
        $this->assertEqualsCanonicalizing(['obi@example.test', 'eze@example.test'], $b->deliveries()->where('status', 'sent')->pluck('to')->all());
        $this->assertSame('The email address on record does not look right.', $b->deliveries()->where('recipient_name', 'like', 'Mr Bad%')->value('error'));
        $this->assertStringContainsString('parent of Ada and Bayo', $b->deliveries()->where('recipient_name', 'like', 'Mrs Obi%')->value('recipient_name'));
        $this->assertSame(0, $b->deliveries()->where('recipient_name', 'like', '%Far%')->count());
        $this->assertSame(0, $b->deliveries()->where('recipient_name', 'like', '%Gone%')->count());
    }

    public function test_a_text_to_all_parents_sends_one_text_per_phone_number_however_it_is_typed(): void
    {
        $this->blast('sms', 'all_parents')->assertSessionHasNoErrors();
        $b = MessageBatch::sole();
        // 08031112222 and "0803 111 2222" are the same phone: one text only. Then Mr Bad (too short), Mrs None, Femi.
        // No text provider yet, so the one real number is marked failed, not sent.
        $this->assertSame(4, $b->deliveries()->count(), 'the same phone number got two texts');
        $c = $this->counts($b);
        $this->assertSame(1, $c['failed']);
        $this->assertSame(3, $c['not_sent']);
        $this->assertSame(0, $c['sent']);
        $this->assertSame('The phone number on record is too short.', $b->deliveries()->where('recipient_name', 'like', 'Mr Bad%')->value('error'));
        $this->assertSame('No phone number on record.', $b->deliveries()->where('recipient_name', 'like', 'Mrs None%')->value('error'));
    }

    public function test_the_class_audience_staff_audience_and_student_audience_have_the_right_lists(): void
    {
        $this->blast('email', 'class', ['class_id' => $this->jss1->id])->assertSessionHasNoErrors();
        // JSS 1: Ada and Bayo share Mrs Obi, Efe has Mrs None = 2 lines
        $this->assertSame(2, MessageBatch::orderByDesc('id')->first()->deliveries()->count());
        $this->assertSame('Parents of JSS 1', MessageBatch::orderByDesc('id')->first()->audience);

        $this->blast('email', 'all_students')->assertSessionHasNoErrors();
        // Six pupils are on roll (the transferred one is not): one line each
        $this->assertSame(6, MessageBatch::orderByDesc('id')->first()->deliveries()->count());

        $this->blast('email', 'all_staff')->assertSessionHasNoErrors();
        // Active One only: the staff member who resigned and the one who was dismissed are left out
        $staffBatch = MessageBatch::orderByDesc('id')->first();
        $this->assertSame(1, $staffBatch->deliveries()->count());
        $this->assertSame('staff0@example.test', $staffBatch->deliveries()->value('to'));
        $this->assertSame(1, $this->counts($staffBatch)['sent']);
    }

    public function test_a_class_from_another_school_and_a_missing_class_are_refused_and_nothing_is_sent(): void
    {
        $other = $this->makeSchool('Elsewhere');
        $oc = SchoolClass::create(['school_id' => $other->id, 'name' => 'Far class']);
        $this->blast('email', 'class', ['class_id' => $oc->id])->assertSessionHasErrors('class_id');
        $this->blast('email', 'class')->assertSessionHasErrors('class_id');
        $this->blast('sms', 'all_parents', ['message' => ''])->assertSessionHasErrors('message');
        $this->blast('sms', 'all_parents', ['message' => str_repeat('a', 1601)])->assertSessionHasErrors('message');
        $this->blast('email', 'all_parents', ['subject' => ''])->assertSessionHasErrors('subject');
        $this->blast('carrier-pigeon', 'all_parents')->assertSessionHasErrors('channel');
        $this->assertSame(0, MessageBatch::count());
        $this->assertSame(0, MessageDelivery::count());
    }

    public function test_trying_the_failed_ones_again_counts_attempts_and_leaves_the_rest_alone(): void
    {
        $this->blast('sms', 'all_parents')->assertSessionHasNoErrors();
        $b = MessageBatch::sole();
        $this->post("/school/communication/sent/{$b->id}/retry")->assertSessionHas('success');
        $line = $b->deliveries()->where('status', 'failed')->sole();
        $this->assertSame(2, $line->attempts);                       // tried once at the start, once again
        $this->assertSame(1, $b->deliveries()->where('status', 'failed')->count());
        $this->assertSame(3, $b->deliveries()->where('status', 'not_sent')->count());
        $this->assertSame(0, $b->deliveries()->where('status', 'not_sent')->where('attempts', '>', 0)->count(), 'a line with no number was tried');
        $this->assertSame(4, $b->deliveries()->count());
    }

    public function test_only_people_allowed_to_send_can_blast_and_an_other_schools_log_is_out_of_reach(): void
    {
        $this->blast('email', 'all_parents');
        $b = MessageBatch::sole();
        $this->actingAs($this->makeUser($this->school, 'teacher'));
        $this->blast('email', 'all_parents')->assertForbidden();
        $this->get("/school/communication/sent/{$b->id}")->assertForbidden();
        $this->assertSame(1, MessageBatch::count());

        $other = $this->makeSchool('Spy School');
        $this->actingAs($this->makeUser($other, 'school-admin'));
        $this->get("/school/communication/sent/{$b->id}")->assertNotFound();
        $this->post("/school/communication/sent/{$b->id}/retry")->assertNotFound();
    }

    // ------------------------------------------------------------------ AI

    public function test_the_ai_cost_is_worked_out_from_tokens_prices_and_the_exchange_rate(): void
    {
        $ai = app(AiService::class);
        // 1,000,000 tokens in at $0.75 and 500,000 out at $3.75 per million = $0.75 + $1.875 = $2.625, at 1,600 naira = 4,200
        $this->assertEquals(4200.0, $ai->cost(['in' => 0.75, 'out' => 3.75], ['input_tokens' => 1_000_000, 'output_tokens' => 500_000], 1600));
        // A small report-card comment: 2,000 in and 300 out at $0.30 / $2.50 = 0.0006 + 0.00075 = $0.00135, at 1,600 = 2.16
        $this->assertEquals(2.16, $ai->cost(['in' => 0.30, 'out' => 2.50], ['input_tokens' => 2000, 'output_tokens' => 300], 1600));
        // Nothing used costs nothing
        $this->assertEquals(0.0, $ai->cost(['in' => 0.75, 'out' => 3.75], ['input_tokens' => 0, 'output_tokens' => 0], 1600));
    }

    public function test_a_school_is_stopped_exactly_when_its_monthly_allowance_is_used_and_starts_again_next_month(): void
    {
        Carbon::setTestNow('2026-10-20 10:00:00');
        $ai = app(AiService::class);
        PlatformSetting::set('ai_enabled', '1', 'ai');
        $ai->saveKey('AIzaTestKey-1234567890-abcdefghij');
        SchoolSetting::create(['school_id' => $this->school->id, 'key' => 'ai_limit', 'value' => '100']);
        $use = fn (float $naira, string $when) => AiUsage::withoutGlobalScopes()->create(['school_id' => $this->school->id, 'user_id' => null, 'feature' => 'questions', 'model' => 'm', 'input_tokens' => 1, 'output_tokens' => 1, 'cost_naira' => $naira, 'ok' => true])->forceFill(['created_at' => $when])->saveQuietly();

        $use(40.50, '2026-10-02 09:00:00');
        $use(59.49, '2026-10-19 09:00:00');
        $use(500, '2026-09-30 23:59:00');   // last month's spending does not count
        $this->assertEqualsWithDelta(99.99, $ai->usedThisMonth($this->school->id), 0.001);
        $this->assertNull($ai->unavailable($this->school->id, 'questions'), '99.99 of 100 is still allowed');

        $use(0.01, '2026-10-20 08:00:00');  // exactly 100.00 used
        $this->assertNotNull($ai->unavailable($this->school->id, 'questions'));
        $this->assertStringContainsString('allowance', $ai->unavailable($this->school->id, 'questions'));

        Carbon::setTestNow('2026-11-01 00:05:00');
        $this->assertEquals(0.0, $ai->usedThisMonth($this->school->id));
        $this->assertNull($ai->unavailable($this->school->id, 'questions'), 'the allowance did not start again on the 1st');

        // A zero allowance means switched off
        SchoolSetting::where('school_id', $this->school->id)->where('key', 'ai_limit')->update(['value' => '0']);
        $this->assertNotNull($ai->unavailable($this->school->id, 'questions'));
    }
}
