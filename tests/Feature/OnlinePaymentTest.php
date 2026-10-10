<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\OnlinePayment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\OnlinePaymentService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Security\SecurityTestCase;

/** Parents paying fees online through the school's chosen provider. The providers are faked: no real calls or money. */
class OnlinePaymentTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private User $parentUser;
    private Student $child;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('Pay School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);

        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 2']);
        $cat = FeeCategory::create(['school_id' => $this->school->id, 'name' => 'Tuition', 'type' => 'tuition']);
        $fee = FeeStructure::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'fee_category_id' => $cat->id, 'academic_year' => '2026-2027', 'amount' => 150000, 'frequency' => 'one_time']);

        $this->parentUser = $this->makeUser($this->school, 'parent');
        $guardian = Guardian::create(['school_id' => $this->school->id, 'user_id' => $this->parentUser->id, 'name' => 'Mrs Obi', 'relation' => 'mother', 'phone' => '08030000000']);
        $this->child = Student::create([
            'school_id' => $this->school->id, 'class_id' => $class->id, 'guardian_id' => $guardian->id, 'first_name' => 'Ada', 'last_name' => 'Obi',
            'admission_no' => 'A1', 'gender' => 'female', 'status' => 'active',
        ]);
        $this->post('/school/fees/invoices', ['fee_structure_ids' => [$fee->id], 'term_id' => $year->terms()->first()->id])->assertSessionHasNoErrors();
        $this->invoice = Invoice::sole();
    }

    /** What fake Paystack answers: the amount it confirms (kobo), the payment's status, and whether it accepts the secret key */
    private int $kobo = 0;
    private string $outcome = 'success';
    private bool $keyOk = true;
    private bool $faked = false;

    private function connect(): void
    {
        $this->fakePaystack(0);
        $this->actingAs($this->admin);
        $this->post('/school/fees/online-payments/setup', ['provider' => 'paystack', 'keys' => ['public_key' => 'pk_test_abc123', 'secret_key' => 'sk_test_secret987'], 'enabled' => true])->assertSessionHasNoErrors();
    }

    /** Fake Paystack: starting a payment gives a page link; checking it says it was paid (or not). No real calls. */
    private function fakePaystack(int $kobo, string $status = 'success'): void
    {
        $this->kobo = $kobo;
        $this->outcome = $status;
        if ($this->faked) {
            return;
        }
        $this->faked = true;
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/abc', 'reference' => 'x']]),
            'api.paystack.co/transaction/verify/*' => fn (HttpRequest $r) => Http::response(['status' => true, 'data' => [
                'status' => $this->outcome, 'amount' => $this->kobo, 'currency' => 'NGN', 'channel' => 'card', 'paid_at' => '2026-10-11T09:00:00Z',
                'reference' => rawurldecode(basename(parse_url($r->url(), PHP_URL_PATH))), 'gateway_response' => $this->outcome === 'success' ? 'Approved' : 'Declined',
            ]]),
            'api.paystack.co/balance' => fn () => $this->keyOk ? Http::response(['status' => true]) : Http::response(['status' => false], 401),
        ]);
    }

    public function test_a_parent_pays_part_of_an_invoice_online_and_it_goes_on_the_ledger_once(): void
    {
        $this->connect();
        $this->fakePaystack(5000000);

        $this->actingAs($this->parentUser);
        $this->get('/school/parent/fees')->assertInertia(fn ($p) => $p->where('payOnline', true));
        $this->post("/school/parent/fees/invoices/{$this->invoice->id}/pay", ['amount' => 50000], ['X-Inertia' => 'true'])
            ->assertStatus(409)->assertHeader('X-Inertia-Location', 'https://checkout.paystack.com/abc');
        $payment = OnlinePayment::sole();
        $this->assertSame('pending', $payment->status);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'initialize') && $r['amount'] === 5000000 && $r['email'] === $this->parentUser->email
            && $r->hasHeader('Authorization', 'Bearer sk_test_secret987'));

        // Back from Paystack: confirmed, so the invoice drops by ₦50,000
        $this->get('/school/parent/fees/pay/'.$payment->reference.'/result')->assertRedirect('/school/parent/fees')->assertSessionHas('success');
        $payment->refresh();
        $this->assertSame('success', $payment->status);
        $this->assertSame(100000.0, $this->invoice->fresh()->balance);
        $this->assertSame('partial', $this->invoice->fresh()->status);
        $this->assertSame('online', $payment->ledgerEntry->method);
        $this->assertSame($payment->reference, $payment->ledgerEntry->reference);

        // The webhook arriving afterwards changes nothing
        $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => $payment->reference]]);
        $this->call('POST', "/webhooks/payments/paystack/{$this->school->id}", [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, 'sk_test_secret987'), 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();
        $this->assertSame(100000.0, $this->invoice->fresh()->balance);
        $this->assertSame(1, $this->invoice->entries()->where('type', 'payment')->count());
    }

    public function test_the_webhook_confirms_a_payment_when_the_parent_never_comes_back(): void
    {
        $this->connect();
        $this->fakePaystack(15000000);
        $this->actingAs($this->parentUser);
        $this->post("/school/parent/fees/invoices/{$this->invoice->id}/pay", ['amount' => 150000]);
        $payment = OnlinePayment::sole();
        auth()->logout();

        // A forged message (wrong signature) is refused
        $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => $payment->reference]]);
        $this->call('POST', "/webhooks/payments/paystack/{$this->school->id}", [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, 'wrong'), 'CONTENT_TYPE' => 'application/json'], $body)->assertStatus(401);
        $this->assertSame('pending', $payment->fresh()->status);

        $this->call('POST', "/webhooks/payments/paystack/{$this->school->id}", [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, 'sk_test_secret987'), 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();
        $this->assertSame('success', $payment->fresh()->status);
        $this->assertSame('paid', $this->invoice->fresh()->status);
        $this->assertSame(0.0, $this->invoice->fresh()->balance);
    }

    public function test_wrong_amounts_failed_payments_and_money_the_invoice_cannot_take_are_never_counted(): void
    {
        $this->connect();

        // Paystack confirms less than asked: needs checking, nothing on the invoice
        $this->fakePaystack(100);
        $this->actingAs($this->parentUser);
        $this->post("/school/parent/fees/invoices/{$this->invoice->id}/pay", ['amount' => 20000]);
        $this->get('/school/parent/fees/pay/'.OnlinePayment::latest('id')->first()->reference.'/result');
        $this->assertSame('needs_review', OnlinePayment::latest('id')->first()->status);
        $this->assertSame(150000.0, $this->invoice->fresh()->balance);

        // Declined card: failed
        $this->fakePaystack(2000000, 'failed');
        $this->post("/school/parent/fees/invoices/{$this->invoice->id}/pay", ['amount' => 20000]);
        $this->get('/school/parent/fees/pay/'.OnlinePayment::latest('id')->first()->reference.'/result')->assertSessionHas('error');
        $this->assertSame('failed', OnlinePayment::latest('id')->first()->status);

        // Paid online, but the bursar took cash for the whole fee meanwhile: flagged, not overpaid
        $this->fakePaystack(2000000);
        $this->post("/school/parent/fees/invoices/{$this->invoice->id}/pay", ['amount' => 20000]);
        $late = OnlinePayment::latest('id')->first();
        $this->actingAs($this->admin);
        $this->post("/school/fees/invoices/{$this->invoice->id}/payments", ['amount' => 150000, 'method' => 'cash', 'entry_date' => now()->toDateString()])->assertSessionHasNoErrors();
        $this->post("/school/fees/online-payments/{$late->id}/check")->assertSessionHas('info');
        $this->assertSame('needs_review', $late->fresh()->status);
        $this->assertStringContainsString('not added to the invoice', $late->fresh()->problem);
        $this->assertSame(0.0, $this->invoice->fresh()->balance);

        $this->get('/school/fees/online-payments')->assertOk()->assertInertia(fn ($p) => $p->has('payments.data', 3)->where('counts.needs_review', 2)
            ->where('setup.provider', 'paystack')->where('setup.providers.0.fields.1.saved_hint', '••••t987')->where('setup.providers.0.fields.1.value', ''));
    }

    public function test_parents_only_pay_their_own_childrens_open_invoices_and_only_when_switched_on(): void
    {
        // Not switched on yet: no Pay button, and starting a payment is refused
        $this->actingAs($this->parentUser);
        $this->get('/school/parent/fees')->assertInertia(fn ($p) => $p->where('payOnline', false));
        $this->post("/school/parent/fees/invoices/{$this->invoice->id}/pay", ['amount' => 1000])->assertSessionHasErrors('amount');

        $this->connect();
        $this->fakePaystack(100000);
        $this->actingAs($this->parentUser);
        // More than owed, or too little
        $this->post("/school/parent/fees/invoices/{$this->invoice->id}/pay", ['amount' => 150001])->assertSessionHasErrors('amount');
        $this->post("/school/parent/fees/invoices/{$this->invoice->id}/pay", ['amount' => 20])->assertSessionHasErrors('amount');

        // Another family's invoice
        $otherParent = $this->makeUser($this->school, 'parent');
        Guardian::create(['school_id' => $this->school->id, 'user_id' => $otherParent->id, 'name' => 'Mr Bello', 'relation' => 'father', 'phone' => '0809']);
        $this->actingAs($otherParent);
        $this->post("/school/parent/fees/invoices/{$this->invoice->id}/pay", ['amount' => 1000])->assertNotFound();
        $this->assertSame(0, OnlinePayment::count());

        // Someone else's payment reference cannot be used to look at it
        $this->actingAs($this->parentUser);
        $this->post("/school/parent/fees/invoices/{$this->invoice->id}/pay", ['amount' => 1000]);
        $this->actingAs($otherParent);
        $this->get('/school/parent/fees/pay/'.OnlinePayment::sole()->reference.'/result')->assertSessionHas('error');
        $this->assertSame('pending', OnlinePayment::sole()->status);

        // A key Paystack refuses is not switched on; teachers cannot change the set-up
        $this->keyOk = false;
        $this->actingAs($this->admin);
        $this->post('/school/fees/online-payments/setup', ['provider' => 'paystack', 'keys' => ['public_key' => 'pk_test_abc', 'secret_key' => 'sk_test_bad'], 'enabled' => true])->assertSessionHasErrors('keys');
        $this->assertFalse(app(OnlinePaymentService::class)->enabled($this->school->id));
        $this->actingAs($this->makeUser($this->school, 'teacher'));
        $this->post('/school/fees/online-payments/setup', ['provider' => 'paystack', 'keys' => ['public_key' => 'pk_test_abc', 'secret_key' => 'sk_test_x']])->assertForbidden();
    }
}
