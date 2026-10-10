<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\OnlinePayment;
use App\Services\OnlinePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/** Parents paying their children's fees online */
class ParentPaymentController extends Controller
{
    public function __construct(private OnlinePaymentService $payments) {}

    /** Send the parent to the provider's payment page */
    public function pay(Request $request, Invoice $invoice): Response
    {
        $this->ownChild($request, $invoice);
        $amount = (float) $request->validate(['amount' => 'required|numeric|min:1|max:100000000'])['amount'];
        $started = $this->payments->start($invoice, $amount, $request->user(), fn (OnlinePayment $p) => route('payments.return', $p->reference));

        if (! empty($started['form'])) {
            // Remita and Interswitch take a form post: a small page posts it for the parent
            $request->session()->put('payment_form.'.$started['payment']->reference, $started['form']);

            return Inertia::location(route('parent.fees.pay.go', $started['payment']->reference));
        }

        // A full page visit to the provider (not an Inertia page)
        return Inertia::location($started['url']);
    }

    /** The page that sends the parent on to a provider that takes a form post */
    public function go(Request $request, string $reference): View|RedirectResponse
    {
        $form = $request->session()->pull('payment_form.'.$reference);
        if (! $form) {
            return redirect('/school/parent/fees');
        }

        return view('payments.redirect', ['form' => $form]);
    }

    /** Providers send the parent back here (sometimes as a form post from their site, which has no login with it) */
    public function returned(string $reference): RedirectResponse
    {
        return redirect()->route('parent.fees.pay.result', $reference, 303);
    }

    /** Check the payment with the provider and tell the parent how it went */
    public function result(Request $request, string $reference): RedirectResponse
    {
        $payment = OnlinePayment::where('reference', $reference)->where('paid_by', $request->user()->id)->first();
        if (! $payment) {
            return redirect('/school/parent/fees')->with('error', 'We could not find that payment. If money left your account, tell the school\'s bursar.');
        }
        $payment = $this->payments->check($payment);
        $ref = $payment->gateway_reference ? "{$payment->reference} (RRR {$payment->gateway_reference})" : $payment->reference;

        return redirect('/school/parent/fees')->with(...match ($payment->status) {
            'success' => ['success', 'Payment received: ₦'.number_format($payment->amount, 2).'. Thank you. Your receipt number is '.$payment->reference.'.'],
            'failed' => ['error', 'The payment did not go through, so nothing was taken. You can try again.'],
            'needs_review' => ['info', 'Your payment reached the school, and the bursar will confirm it on the invoice shortly. Reference '.$ref.'.'],
            default => ['info', 'The payment has not been confirmed yet (bank transfers can take a few minutes). It will show here once it is. Reference '.$ref.'.'],
        });
    }

    /** The invoice must be for one of this parent's children */
    private function ownChild(Request $request, Invoice $invoice): void
    {
        $user = $request->user();
        $guardian = Guardian::with('students:id,guardian_id')->where('school_id', $user->school_id)->where('user_id', $user->id)->first();
        abort_unless($guardian && $guardian->students->contains('id', $invoice->student_id), 404);
    }
}
