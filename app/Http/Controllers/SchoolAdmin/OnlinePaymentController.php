<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\OnlinePayment;
use App\Services\OnlinePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Staff view of payments parents made online, and choosing and connecting the school's payment provider */
class OnlinePaymentController extends Controller
{
    public function __construct(private OnlinePaymentService $payments) {}

    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $sid = $request->user()->school_id;
        $payments = OnlinePayment::with('student:id,first_name,last_name,admission_no', 'invoice:id,invoice_no', 'payer:id,name')
            ->when(in_array($status, OnlinePayment::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->latest('id')->paginate(30)->withQueryString();
        $rows = $payments->getCollection()->map(fn (OnlinePayment $p) => [
            'id' => $p->id, 'reference' => $p->reference, 'gateway' => $p->gateway, 'gateway_reference' => $p->gateway_reference, 'amount' => $p->amount, 'status' => $p->status, 'channel' => $p->channel, 'problem' => $p->problem,
            'student' => $p->student ? trim($p->student->first_name.' '.$p->student->last_name) : null, 'admission_no' => $p->student?->admission_no,
            'invoice_id' => $p->invoice_id, 'invoice_no' => $p->invoice?->invoice_no, 'payer' => $p->payer?->name, 'email' => $p->email,
            'started_at' => $p->created_at?->toIso8601String(), 'paid_at' => $p->paid_at?->toIso8601String(),
        ]);

        return Inertia::render('SchoolAdmin/Fees/OnlinePayments', [
            'payments' => [
                'data' => $rows,
                'meta' => ['total' => $payments->total(), 'from' => $payments->firstItem(), 'to' => $payments->lastItem()],
                'links' => ['prev' => $payments->previousPageUrl(), 'next' => $payments->nextPageUrl()],
            ],
            'status' => $status,
            'counts' => OnlinePayment::query()->get(['status'])->countBy('status'),
            'setup' => $sid ? $this->payments->setupFor($sid) : ['provider' => null, 'enabled' => false, 'providers' => []],
            'canSetup' => $request->user()->can('settings.edit'),
            'canCheck' => $request->user()->can('fees.collect'),
        ]);
    }

    /** Ask Paystack again about a payment that is still waiting */
    public function check(OnlinePayment $payment): RedirectResponse
    {
        $payment = $this->payments->check($payment);

        return back()->with($payment->status === 'success' ? 'success' : 'info', match ($payment->status) {
            'success' => 'Confirmed. The payment is now on the invoice.',
            'failed' => 'The provider says this payment did not go through.',
            'needs_review' => 'The provider confirmed money, but it could not go on the invoice: '.$payment->problem,
            default => 'The provider has not confirmed this payment yet.',
        });
    }

    public function saveSetup(Request $request): RedirectResponse
    {
        $sid = $request->user()->school_id;
        abort_unless($sid, 404);
        $provider = $request->validate(['provider' => ['required', Rule::in(array_keys(OnlinePaymentService::GATEWAYS))]])['provider'];
        $gateway = $this->payments->gateway($provider);
        $saved = $this->payments->credentials($sid, $provider) ?? [];

        $rules = ['enabled' => 'boolean'];
        $messages = [];
        foreach ($gateway->fields() as $name => $field) {
            $needed = empty($field['optional']) && ! (! empty($field['secret']) && ! empty($saved[$name]));
            $rules["keys.{$name}"] = array_filter([
                $needed ? 'required' : 'nullable', 'string', 'max:200',
                isset($field['pattern']) ? 'regex:'.$field['pattern'] : null,
                isset($field['options']) ? Rule::in(array_keys($field['options'])) : null,
            ]);
            $messages["keys.{$name}.required"] = "Enter the {$field['label']}.";
            $messages["keys.{$name}.regex"] = "This does not look like a {$gateway->label()} {$field['label']}. Copy it again from {$gateway->label()}.";
        }
        $data = $request->validate($rules, $messages);
        $enabled = (bool) ($data['enabled'] ?? false);
        $ok = $this->payments->connect($sid, $provider, $data['keys'] ?? [], $enabled);

        if ($ok === false) {
            return back()->withErrors(['keys' => "{$gateway->label()} did not accept these keys, so online payment is off. Copy them again from your {$gateway->label()} dashboard."]);
        }

        return back()->with('success', match (true) {
            ! $enabled => "{$gateway->label()} keys saved. Online payment is off until you switch it on.",
            $ok === null => "{$gateway->label()} is set up and parents can pay online. {$gateway->label()} cannot check the keys until a payment is made, so try a small payment first.",
            default => "{$gateway->label()} is connected. Parents can now pay invoices online from their portal.",
        });
    }
}
