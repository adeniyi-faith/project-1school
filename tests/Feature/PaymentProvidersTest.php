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
use App\Payments\Gateways\RemitaGateway;
use App\Services\OnlinePaymentService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Security\SecurityTestCase;

/**
 * Flutterwave, Monnify, Remita and Interswitch: each school picks one. Their servers are faked here,
 * shaped like their published APIs, so no real calls or money.
 */
class PaymentProvidersTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private User $parentUser;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('Providers School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true]);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'SS 1']);
        $cat = FeeCategory::create(['school_id' => $this->school->id, 'name' => 'Tuition', 'type' => 'tuition']);
        $fee = FeeStructure::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'fee_category_id' => $cat->id, 'academic_year' => '2026-2027', 'amount' => 80000, 'frequency' => 'one_time']);
        $this->parentUser = $this->makeUser($this->school, 'parent');
        $guardian = Guardian::create(['school_id' => $this->school->id, 'user_id' => $this->parentUser->id, 'name' => 'Mr Ade', 'relation' => 'father', 'phone' => '0803']);
        Student::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'guardian_id' => $guardian->id, 'first_name' => 'Tobi', 'last_name' => 'Ade', 'admission_no' => 'T1', 'gender' => 'male', 'status' => 'active']);
        $this->post('/school/fees/invoices', ['fee_structure_ids' => [$fee->id], 'term_id' => $year->terms()->first()->id])->assertSessionHasNoErrors();
        $this->invoice = Invoice::sole();
    }

    private function connect(string $provider, array $keys): void
    {
        $this->actingAs($this->admin);
        $this->post('/school/fees/online-payments/setup', ['provider' => $provider, 'keys' => $keys, 'enabled' => true])->assertSessionHasNoErrors();
        $this->assertTrue(app(OnlinePaymentService::class)->enabled($this->school->id));
    }

    /** The parent pays, comes back (as the provider sends them), and sees the result */
    private function payAndReturn(float $amount, string $method = 'get'): OnlinePayment
    {
        $this->actingAs($this->parentUser);
        $this->post("/school/parent/fees/invoices/{$this->invoice->id}/pay", ['amount' => $amount], ['X-Inertia' => 'true'])->assertStatus(409);
        $payment = OnlinePayment::latest('id')->first();
        $this->call(strtoupper($method), "/payments/return/{$payment->reference}")->assertRedirect("/school/parent/fees/pay/{$payment->reference}/result");
        $this->get("/school/parent/fees/pay/{$payment->reference}/result")->assertRedirect('/school/parent/fees');

        return $payment->fresh();
    }

    public function test_flutterwave(): void
    {
        Http::fake([
            'api.flutterwave.com/v3/balances' => Http::response(['status' => 'success', 'data' => []]),
            'api.flutterwave.com/v3/payments' => Http::response(['status' => 'success', 'data' => ['link' => 'https://checkout.flutterwave.com/v3/hosted/pay/abc']]),
            'api.flutterwave.com/v3/transactions/verify_by_reference*' => fn (HttpRequest $r) => Http::response(['status' => 'success', 'data' => [
                'status' => 'successful', 'amount' => 30000, 'currency' => 'NGN', 'tx_ref' => $r['tx_ref'], 'payment_type' => 'bank_transfer',
            ]]),
        ]);
        $this->connect('flutterwave', ['public_key' => 'FLWPUBK_TEST-abc123-X', 'secret_key' => 'FLWSECK_TEST-def456-X', 'secret_hash' => 'our-long-hash']);

        $payment = $this->payAndReturn(30000);
        Http::assertSent(fn (HttpRequest $r) => $r->url() === 'https://api.flutterwave.com/v3/payments' && $r['tx_ref'] === $payment->reference && $r['amount'] == 30000
            && $r['redirect_url'] === route('payments.return', $payment->reference) && $r->hasHeader('Authorization', 'Bearer FLWSECK_TEST-def456-X'));
        $this->assertSame('success', $payment->status);
        $this->assertSame('bank transfer', $payment->channel);
        $this->assertSame(50000.0, $this->invoice->fresh()->balance);

        // A webhook without the school's secret hash is refused
        $this->postJson("/webhooks/payments/flutterwave/{$this->school->id}", ['data' => ['tx_ref' => $payment->reference]], ['verif-hash' => 'guess'])->assertStatus(401);
        $this->postJson("/webhooks/payments/flutterwave/{$this->school->id}", ['data' => ['tx_ref' => $payment->reference]], ['verif-hash' => 'our-long-hash'])->assertOk();
        $this->assertSame(1, $this->invoice->entries()->where('type', 'payment')->count());
    }

    public function test_monnify_with_a_signed_webhook_and_a_short_bank_transfer(): void
    {
        $paid = ['status' => 'PENDING', 'amount' => 0];
        Http::fake([
            'sandbox.monnify.com/api/v1/auth/login' => Http::response(['requestSuccessful' => true, 'responseBody' => ['accessToken' => 'tok']]),
            'sandbox.monnify.com/api/v1/merchant/transactions/init-transaction' => Http::response(['requestSuccessful' => true, 'responseBody' => [
                'checkoutUrl' => 'https://sandbox.sdk.monnify.com/checkout/MNFY123', 'transactionReference' => 'MNFY|123',
            ]]),
            'sandbox.monnify.com/api/v2/merchant/transactions/query*' => function (HttpRequest $r) use (&$paid) {
                return Http::response(['requestSuccessful' => true, 'responseBody' => [
                    'paymentReference' => $r['paymentReference'], 'paymentStatus' => $paid['status'], 'amountPaid' => $paid['amount'], 'currencyCode' => 'NGN', 'paymentMethod' => 'ACCOUNT_TRANSFER',
                ]]);
            },
        ]);
        $this->connect('monnify', ['mode' => 'test', 'api_key' => 'MK_TEST_ABC123', 'secret_key' => 'monnifysecret', 'contract_code' => '1234567890']);

        // Not paid yet when the parent comes back: stays waiting
        $payment = $this->payAndReturn(80000);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('MNFY|123', $payment->gateway_reference);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'init-transaction') && $r['contractCode'] === '1234567890' && $r['paymentReference'] === $payment->reference && $r->hasHeader('Authorization', 'Bearer tok'));

        // The parent transferred less than asked: Monnify's signed webhook arrives, and it goes to the bursar instead of the invoice
        $paid = ['status' => 'PARTIALLY_PAID', 'amount' => 50000];
        $body = json_encode(['eventType' => 'SUCCESSFUL_TRANSACTION', 'eventData' => ['paymentReference' => $payment->reference]]);
        $this->call('POST', "/webhooks/payments/monnify/{$this->school->id}", [], [], [], ['HTTP_MONNIFY_SIGNATURE' => 'bad', 'CONTENT_TYPE' => 'application/json'], $body)->assertStatus(401);
        $this->call('POST', "/webhooks/payments/monnify/{$this->school->id}", [], [], [], ['HTTP_MONNIFY_SIGNATURE' => hash_hmac('sha512', $body, 'monnifysecret'), 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();
        $this->assertSame('needs_review', $payment->fresh()->status);
        $this->assertSame(80000.0, $this->invoice->fresh()->balance);
    }

    public function test_remita_gives_an_rrr_and_takes_a_form_post(): void
    {
        $status = '021';
        Http::fake([
            'demo.remita.net/remita/exapp/api/v1/send/api/echannelsvc/merchant/api/paymentinit' => Http::response('jsonp ({"statuscode":"025","RRR":"280007021192","status":"Payment Reference generated"})'),
            'demo.remita.net/remita/exapp/api/v1/send/api/echannelsvc/2547916/280007021192/*' => function () use (&$status) {
                return Http::response(['amount' => 80000, 'RRR' => '280007021192', 'status' => $status, 'message' => $status === '00' ? 'Successful' : 'Pending']);
            },
        ]);
        $this->connect('remita', ['mode' => 'test', 'merchant_id' => '2547916', 'service_type_id' => '4430731', 'api_key' => '1946']);

        $this->actingAs($this->parentUser);
        $this->post("/school/parent/fees/invoices/{$this->invoice->id}/pay", ['amount' => 80000], ['X-Inertia' => 'true'])->assertStatus(409);
        $payment = OnlinePayment::sole();
        $this->assertSame('280007021192', $payment->gateway_reference);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'paymentinit')
            && $r->hasHeader('Authorization', 'remitaConsumerKey=2547916,remitaConsumerToken='.hash('sha512', '2547916'.'4430731'.$payment->reference.'80000.00'.'1946')));

        // The page that posts the form to Remita, once only
        $this->get("/school/parent/fees/pay/{$payment->reference}/go")->assertOk()
            ->assertSee('https://demo.remita.net/remita/ecomm/finalize.reg', false)->assertSee('280007021192', false)
            ->assertSee(hash('sha512', '2547916'.'280007021192'.'1946'), false);
        $this->get("/school/parent/fees/pay/{$payment->reference}/go")->assertRedirect('/school/parent/fees');

        // Remita posts the parent back from its own site: no CSRF token and no login with it, and the session is left alone
        auth()->logout();
        $back = $this->post("/payments/return/{$payment->reference}", ['RRR' => '280007021192', 'orderID' => $payment->reference]);
        $back->assertRedirect("/school/parent/fees/pay/{$payment->reference}/result");
        $this->assertEmpty($back->headers->getCookies(), 'the return link must not start a new session');

        $this->actingAs($this->parentUser);
        $this->get("/school/parent/fees/pay/{$payment->reference}/result")->assertSessionHas('info');
        $this->assertSame('pending', $payment->fresh()->status);
        $status = '00';
        // Remita's (unsigned) notification only makes us ask Remita again
        $this->postJson("/webhooks/payments/remita/{$this->school->id}", [['rrr' => '280007021192', 'orderRef' => $payment->reference]])->assertOk();
        $this->assertSame('success', $payment->fresh()->status);
        $this->assertSame('paid', $this->invoice->fresh()->status);
        $this->assertStringContainsString('Remita ref 280007021192', $payment->fresh()->ledgerEntry->note);
    }

    public function test_interswitch_requery_decides_not_the_post_back(): void
    {
        $code = 'Z6';
        Http::fake([
            'sandbox.interswitchng.com/collections/api/v1/gettransaction.json*' => function (HttpRequest $r) use (&$code) {
                return Http::response(['Amount' => $r['amount'], 'MerchantReference' => $r['transactionreference'], 'ResponseCode' => $code, 'ResponseDescription' => $code === '00' ? 'Approved by Financial Institution' : 'Customer Cancellation']);
            },
        ]);
        $this->connect('interswitch', ['mode' => 'test', 'merchant_code' => 'MX6072', 'pay_item_id' => '9405967']);

        $this->actingAs($this->parentUser);
        $this->post("/school/parent/fees/invoices/{$this->invoice->id}/pay", ['amount' => 25000], ['X-Inertia' => 'true'])->assertStatus(409);
        $payment = OnlinePayment::sole();
        $this->get("/school/parent/fees/pay/{$payment->reference}/go")->assertOk()
            ->assertSee('https://sandbox.interswitchng.com/collections/w/pay', false)->assertSee('value="2500000"', false)->assertSee('value="566"', false);

        // The post-back claims success, but Interswitch's own record says cancelled
        $this->post("/payments/return/{$payment->reference}", ['txnref' => $payment->reference, 'resp' => '00']);
        $this->get("/school/parent/fees/pay/{$payment->reference}/result")->assertSessionHas('error');
        $this->assertSame('failed', $payment->fresh()->status);

        $this->post("/school/parent/fees/invoices/{$this->invoice->id}/pay", ['amount' => 25000]);
        $code = '00';
        $second = OnlinePayment::latest('id')->first();
        $this->get("/school/parent/fees/pay/{$second->reference}/result")->assertSessionHas('success');
        $this->assertSame(55000.0, $this->invoice->fresh()->balance);
    }

    public function test_remita_answers_wrapped_as_jsonp_are_read(): void
    {
        $this->assertSame(['RRR' => '1'], RemitaGateway::json('jsonp ({"RRR":"1"})'));
        $this->assertSame(['RRR' => '1'], RemitaGateway::json('{"RRR":"1"}'));
        $this->assertNull(RemitaGateway::json('<html>error</html>'));
    }

    public function test_a_school_can_switch_provider_and_keys_are_checked_per_provider(): void
    {
        Http::fake(['api.flutterwave.com/v3/balances' => Http::response(['status' => 'error'], 401)]);
        $this->actingAs($this->admin);
        // Keys that do not look right are refused before asking the provider
        $this->post('/school/fees/online-payments/setup', ['provider' => 'flutterwave', 'keys' => ['public_key' => 'pk_test_x', 'secret_key' => 'sk_test_y'], 'enabled' => true])
            ->assertSessionHasErrors(['keys.public_key', 'keys.secret_key']);
        // Keys the provider refuses leave online payment off
        $this->post('/school/fees/online-payments/setup', ['provider' => 'flutterwave', 'keys' => ['public_key' => 'FLWPUBK-abc-X', 'secret_key' => 'FLWSECK-def-X'], 'enabled' => true])
            ->assertSessionHasErrors('keys');
        $this->assertFalse(app(OnlinePaymentService::class)->enabled($this->school->id));

        // Remita and Interswitch cannot be checked without a payment, so they are saved with a warning
        $this->post('/school/fees/online-payments/setup', ['provider' => 'interswitch', 'keys' => ['mode' => 'live', 'merchant_code' => 'MX1', 'pay_item_id' => '101']])
            ->assertSessionHasErrors('keys.merchant_code');
        $this->post('/school/fees/online-payments/setup', ['provider' => 'interswitch', 'keys' => ['mode' => 'live', 'merchant_code' => 'MX6072', 'pay_item_id' => '101'], 'enabled' => true])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'try a small payment first'));
        $this->assertSame('interswitch', app(OnlinePaymentService::class)->provider($this->school->id));

        // Each school's keys stay its own
        $other = $this->makeUser($this->makeSchool('Other'), 'school-admin');
        $this->actingAs($other);
        $this->get('/school/fees/online-payments')->assertInertia(fn ($p) => $p->where('setup.provider', null)->where('setup.enabled', false));
        $this->post('/school/fees/online-payments/setup', ['provider' => 'unknown', 'keys' => []])->assertSessionHasErrors('provider');
    }
}
