<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\OnlinePayment;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Staff view of payments parents made online, and the Paystack set-up */
class OnlinePaymentController extends Controller
{
    public function __construct(private PaystackService $paystack) {}

    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $sid = $request->user()->school_id;
        $payments = OnlinePayment::with('student:id,first_name,last_name,admission_no', 'invoice:id,invoice_no', 'payer:id,name')
            ->when(in_array($status, OnlinePayment::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->latest('id')->paginate(30)->withQueryString();
        $rows = $payments->getCollection()->map(fn (OnlinePayment $p) => [
            'id' => $p->id, 'reference' => $p->reference, 'amount' => $p->amount, 'status' => $p->status, 'channel' => $p->channel, 'problem' => $p->problem,
            'student' => $p->student ? trim($p->student->first_name.' '.$p->student->last_name) : null, 'admission_no' => $p->student?->admission_no,
            'invoice_id' => $p->invoice_id, 'invoice_no' => $p->invoice?->invoice_no, 'payer' => $p->payer?->name, 'email' => $p->email,
            'started_at' => $p->created_at?->toIso8601String(), 'paid_at' => $p->paid_at?->toIso8601String(),
        ]);
        $keys = $sid ? $this->paystack->keys($sid) : null;

        return Inertia::render('SchoolAdmin/Fees/OnlinePayments', [
            'payments' => [
                'data' => $rows,
                'meta' => ['total' => $payments->total(), 'from' => $payments->firstItem(), 'to' => $payments->lastItem()],
                'links' => ['prev' => $payments->previousPageUrl(), 'next' => $payments->nextPageUrl()],
            ],
            'status' => $status,
            'counts' => OnlinePayment::query()->get(['status'])->countBy('status'),
            'setup' => [
                'enabled' => $sid ? $this->paystack->enabled($sid) : false,
                'public_key' => $keys['public'] ?? '',
                'secret_hint' => $keys ? '••••'.substr($keys['secret'], -4) : null,
                'webhook_url' => $sid ? route('webhooks.paystack', $sid) : null,
            ],
            'canSetup' => $request->user()->can('settings.edit'),
            'canCheck' => $request->user()->can('fees.collect'),
        ]);
    }

    /** Ask Paystack again about a payment that is still waiting */
    public function check(OnlinePayment $payment): RedirectResponse
    {
        $payment = $this->paystack->check($payment);

        return back()->with($payment->status === 'success' ? 'success' : 'info', match ($payment->status) {
            'success' => 'Paystack confirmed it. The payment is now on the invoice.',
            'failed' => 'Paystack says this payment did not go through.',
            'needs_review' => 'Paystack confirmed the money, but it could not go on the invoice: '.$payment->problem,
            default => 'Paystack has not confirmed this payment yet.',
        });
    }

    public function saveSetup(Request $request): RedirectResponse
    {
        $sid = $request->user()->school_id;
        abort_unless($sid, 404);
        $has = $this->paystack->keys($sid) !== null;
        $data = $request->validate([
            'public_key' => ['required', 'string', 'max:120', 'regex:/^pk_(test|live)_[A-Za-z0-9]+$/'],
            'secret_key' => [Rule::requiredIf(! $has), 'nullable', 'string', 'max:120', 'regex:/^sk_(test|live)_[A-Za-z0-9]+$/'],
            'enabled' => 'boolean',
        ], [
            'public_key.regex' => 'This does not look like a Paystack public key. It starts with pk_live_ (or pk_test_ for trying it out).',
            'secret_key.regex' => 'This does not look like a Paystack secret key. It starts with sk_live_ (or sk_test_ for trying it out).',
        ]);
        if (! empty($data['secret_key']) && str_starts_with($data['public_key'], 'pk_live_') !== str_starts_with($data['secret_key'], 'sk_live_')) {
            return back()->withErrors(['secret_key' => 'Use a live public key with a live secret key, or test with test.']);
        }
        $this->paystack->saveKeys($sid, $data['public_key'], $data['secret_key'] ?? null, (bool) ($data['enabled'] ?? false));
        if (! $this->paystack->checkKeys($sid)) {
            $this->paystack->saveKeys($sid, $data['public_key'], null, false);

            return back()->withErrors(['secret_key' => 'Paystack did not accept this secret key, so online payment stays off. Copy it again from Paystack: Settings, API Keys & Webhooks.']);
        }

        return back()->with('success', ($data['enabled'] ?? false)
            ? 'Paystack is connected. Parents can now pay invoices online from their portal.'
            : 'Paystack keys saved. Online payment is off until you switch it on.');
    }
}
