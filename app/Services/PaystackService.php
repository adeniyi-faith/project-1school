<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\OnlinePayment;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Online fee payments through Paystack, with each school's own Paystack account.
 *
 * A parent starts a payment here, pays on Paystack's page, and comes back. The money only counts
 * once Paystack confirms it: either when the parent returns, through Paystack's webhook (a message
 * Paystack's server sends to ours), or when staff press "Check with Paystack". Whichever comes
 * first writes the ledger payment; the others find it already done.
 */
class PaystackService
{
    private const API = 'https://api.paystack.co';

    /** Paystack's smallest charge is ₦50; this keeps tiny part-payments out */
    public const MIN_AMOUNT = 100;

    public function __construct(private FeeLedgerService $ledger) {}

    /** The school's keys, or null when online payment is not set up */
    public function keys(int $schoolId): ?array
    {
        $public = SchoolSetting::get($schoolId, 'paystack_public_key');
        $secret = SchoolSetting::get($schoolId, 'paystack_secret_key');
        if (! $public || ! $secret) {
            return null;
        }
        try {
            $secret = Crypt::decryptString($secret);
        } catch (\Throwable) {
            return null;
        }

        return ['public' => $public, 'secret' => $secret];
    }

    public function enabled(int $schoolId): bool
    {
        return $this->keys($schoolId) !== null && SchoolSetting::get($schoolId, 'paystack_enabled', '0') === '1';
    }

    public function saveKeys(int $schoolId, string $public, ?string $secret, bool $enabled): void
    {
        SchoolSetting::set($schoolId, 'paystack_public_key', trim($public), 'payments');
        if ($secret !== null && $secret !== '') {
            SchoolSetting::set($schoolId, 'paystack_secret_key', Crypt::encryptString(trim($secret)), 'payments');
        }
        SchoolSetting::set($schoolId, 'paystack_enabled', $enabled ? '1' : '0', 'payments');
    }

    /** True when Paystack accepts the secret key */
    public function checkKeys(int $schoolId): bool
    {
        $keys = $this->keys($schoolId);

        return $keys && Http::withToken($keys['secret'])->timeout(15)->get(self::API.'/balance')->successful();
    }

    /**
     * Start a payment and return Paystack's page to send the parent to.
     */
    public function start(Invoice $invoice, float $amount, User $payer, string $callbackUrl): array
    {
        $keys = $this->keys($invoice->school_id);
        if (! $keys || ! $this->enabled($invoice->school_id)) {
            throw ValidationException::withMessages(['amount' => 'The school has not switched on online payment yet.']);
        }
        $amount = round($amount, 2);
        if (! $invoice->isOpen()) {
            throw ValidationException::withMessages(['amount' => 'This invoice has nothing left to pay.']);
        }
        if ($amount > $invoice->balance + 0.001) {
            throw ValidationException::withMessages(['amount' => 'This is more than the ₦'.number_format($invoice->balance, 2).' still owed.']);
        }
        if ($amount < min(self::MIN_AMOUNT, $invoice->balance)) {
            throw ValidationException::withMessages(['amount' => 'Pay at least ₦'.number_format(self::MIN_AMOUNT).'.']);
        }

        $payment = OnlinePayment::create([
            'school_id' => $invoice->school_id, 'invoice_id' => $invoice->id, 'student_id' => $invoice->student_id, 'paid_by' => $payer->id,
            'reference' => 'SR-'.$invoice->school_id.'-'.now()->format('ymd').'-'.strtoupper(Str::random(10)),
            'amount' => $amount, 'email' => $payer->email, 'status' => 'pending',
        ]);

        $response = Http::withToken($keys['secret'])->timeout(20)->post(self::API.'/transaction/initialize', [
            'email' => $payer->email,
            'amount' => (int) round($amount * 100),          // kobo
            'currency' => 'NGN',
            'reference' => $payment->reference,
            'callback_url' => $callbackUrl,
            'metadata' => ['invoice_no' => $invoice->invoice_no, 'student_id' => $invoice->student_id, 'school_id' => $invoice->school_id],
        ]);
        $url = $response->json('data.authorization_url');
        if (! $response->successful() || ! $url) {
            $payment->update(['status' => 'failed', 'problem' => 'Paystack did not start the payment: '.Str::limit((string) $response->json('message'), 150)]);
            throw ValidationException::withMessages(['amount' => 'Paystack could not start the payment just now. Please try again in a few minutes.']);
        }

        return ['payment' => $payment, 'url' => $url];
    }

    /** Ask Paystack how a payment went, and settle it */
    public function check(OnlinePayment $payment): OnlinePayment
    {
        if ($payment->status === 'success') {
            return $payment;
        }
        $keys = $this->keys($payment->school_id);
        if (! $keys) {
            return $payment;
        }
        $response = Http::withToken($keys['secret'])->timeout(20)->get(self::API.'/transaction/verify/'.rawurlencode($payment->reference));
        $payment->update(['checked_at' => now()]);
        if (! $response->successful() || ! is_array($response->json('data'))) {
            // Not found yet, or Paystack is down: leave it pending to check again later
            return $payment;
        }

        return $this->settle($payment, $response->json('data'));
    }

    /**
     * A webhook from Paystack. The signature (a code made from the message and the school's secret key)
     * proves it came from Paystack and was not changed on the way. Returns false when the signature is wrong.
     */
    public function webhook(int $schoolId, string $body, ?string $signature): bool
    {
        $keys = $this->keys($schoolId);
        if (! $keys || ! $signature || ! hash_equals(hash_hmac('sha512', $body, $keys['secret']), $signature)) {
            return false;
        }
        $event = json_decode($body, true);
        if (($event['event'] ?? null) !== 'charge.success' || ! is_array($event['data'] ?? null)) {
            return true;                                      // a message we do not need; accepted so Paystack stops sending it
        }
        $payment = OnlinePayment::withoutGlobalScopes()->where('school_id', $schoolId)->where('reference', (string) ($event['data']['reference'] ?? ''))->first();
        if ($payment) {
            // Ask Paystack directly rather than trusting the message's figures
            $this->check($payment);
        }

        return true;
    }

    /** Turn Paystack's answer into the payment's outcome; a confirmed payment becomes a ledger line once only */
    public function settle(OnlinePayment $payment, array $data): OnlinePayment
    {
        return DB::transaction(function () use ($payment, $data) {
            $payment = OnlinePayment::withoutGlobalScopes()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->status === 'success') {
                return $payment;
            }
            $status = $data['status'] ?? null;
            if ($status !== 'success') {
                // abandoned, failed, reversed ... or still ongoing (bank transfers can take a while)
                if (in_array($status, ['failed', 'reversed', 'abandoned'], true)) {
                    $payment->update(['status' => 'failed', 'channel' => $data['channel'] ?? null, 'problem' => $data['gateway_response'] ?? $status]);
                }

                return $payment;
            }
            if ((int) ($data['amount'] ?? 0) !== (int) round($payment->amount * 100) || ($data['currency'] ?? 'NGN') !== 'NGN' || ($data['reference'] ?? '') !== $payment->reference) {
                $payment->update(['status' => 'needs_review', 'channel' => $data['channel'] ?? null, 'problem' => 'Paystack confirmed a different amount from the one asked for.']);
                Log::warning('Paystack amount mismatch', ['reference' => $payment->reference]);

                return $payment;
            }

            $paidAt = isset($data['paid_at']) ? \Illuminate\Support\Carbon::parse($data['paid_at']) : now();
            $invoice = Invoice::withoutGlobalScopes()->findOrFail($payment->invoice_id);
            $payer = User::find($payment->paid_by) ?? User::find($invoice->issued_by);
            try {
                if (! $payer) {
                    throw ValidationException::withMessages(['amount' => 'the parent\'s account no longer exists.']);
                }
                $entry = $this->ledger->recordPayment(
                    $invoice, $payment->amount, 'online', $paidAt->toDateString(), $payment->reference,
                    'Paid online with Paystack'.(! empty($data['channel']) ? ' ('.str_replace('_', ' ', $data['channel']).')' : ''),
                    $payer,
                );
            } catch (ValidationException $e) {
                // Money arrived but the invoice cannot take it, e.g. it was paid in cash meanwhile: staff refund or move it
                $payment->update(['status' => 'needs_review', 'channel' => $data['channel'] ?? null, 'paid_at' => $paidAt,
                    'problem' => 'Paid on Paystack, but not added to the invoice: '.collect($e->errors())->flatten()->first()]);

                return $payment;
            }
            $payment->update(['status' => 'success', 'channel' => $data['channel'] ?? null, 'paid_at' => $paidAt, 'ledger_entry_id' => $entry->id, 'problem' => null]);

            return $payment;
        });
    }
}
