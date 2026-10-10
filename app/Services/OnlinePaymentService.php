<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\OnlinePayment;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Payments\Gateway;
use App\Payments\Gateways\FlutterwaveGateway;
use App\Payments\Gateways\InterswitchGateway;
use App\Payments\Gateways\MonnifyGateway;
use App\Payments\Gateways\PaystackGateway;
use App\Payments\Gateways\RemitaGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Online fee payments. Each school picks one provider and connects its own account, so the money goes
 * straight to the school.
 *
 * A parent starts a payment here, pays on the provider's page, and comes back. The money only counts
 * once the provider confirms it: when the parent returns, through the provider's webhook (a message its
 * server sends to ours), or when staff press "Check". Whichever comes first writes the ledger payment;
 * the others find it already done.
 */
class OnlinePaymentService
{
    /** The providers a school can choose from */
    public const GATEWAYS = [
        'paystack' => PaystackGateway::class,
        'flutterwave' => FlutterwaveGateway::class,
        'monnify' => MonnifyGateway::class,
        'remita' => RemitaGateway::class,
        'interswitch' => InterswitchGateway::class,
    ];

    /** Keeps tiny part-payments out (providers' own minimums are around ₦50 to ₦100) */
    public const MIN_AMOUNT = 100;

    public function __construct(private FeeLedgerService $ledger) {}

    public function gateway(string $key): Gateway
    {
        abort_unless(isset(self::GATEWAYS[$key]), 404);

        return app(self::GATEWAYS[$key]);
    }

    /** The provider the school chose, or null */
    public function provider(int $schoolId): ?string
    {
        $key = SchoolSetting::get($schoolId, 'payment_provider');

        return isset(self::GATEWAYS[$key]) ? $key : null;
    }

    /** A provider's saved keys for the school, or null */
    public function credentials(int $schoolId, string $provider): ?array
    {
        $saved = SchoolSetting::get($schoolId, "payment_keys_{$provider}");
        if (! $saved) {
            return null;
        }
        try {
            $creds = json_decode(Crypt::decryptString($saved), true);
        } catch (\Throwable) {
            return null;
        }

        return is_array($creds) ? $creds : null;
    }

    public function enabled(int $schoolId): bool
    {
        $provider = $this->provider($schoolId);

        return $provider && SchoolSetting::get($schoolId, 'payment_enabled', '0') === '1' && $this->credentials($schoolId, $provider) !== null;
    }

    /**
     * Save a provider's keys (blank secret fields keep what was saved) and make it the school's provider.
     * Returns what the provider said about the keys: true, false, or null when it cannot check them.
     */
    public function connect(int $schoolId, string $provider, array $input, bool $enabled): ?bool
    {
        $gateway = $this->gateway($provider);
        $old = $this->credentials($schoolId, $provider) ?? [];
        $creds = [];
        foreach ($gateway->fields() as $name => $field) {
            $value = trim((string) ($input[$name] ?? ''));
            $creds[$name] = $value === '' && ! empty($field['secret']) ? ($old[$name] ?? '') : $value;
        }
        $ok = $gateway->checkCredentials($creds);
        SchoolSetting::set($schoolId, "payment_keys_{$provider}", Crypt::encryptString(json_encode($creds)), 'payments');
        SchoolSetting::set($schoolId, 'payment_provider', $provider, 'payments');
        SchoolSetting::set($schoolId, 'payment_enabled', $enabled && $ok !== false ? '1' : '0', 'payments');

        return $ok;
    }

    /** What staff see of the set-up: never the secret values, only their last four characters */
    public function setupFor(int $schoolId): array
    {
        $providers = [];
        foreach (self::GATEWAYS as $key => $class) {
            $gateway = $this->gateway($key);
            $creds = $this->credentials($schoolId, $key) ?? [];
            $providers[] = [
                'key' => $key, 'label' => $gateway->label(), 'help' => $gateway->setupHelp(),
                'webhook_url' => route('webhooks.payments', ['provider' => $key, 'school' => $schoolId]),
                'fields' => collect($gateway->fields())->map(fn ($f, $name) => [
                    'name' => $name, 'label' => $f['label'], 'secret' => ! empty($f['secret']), 'optional' => ! empty($f['optional']),
                    'placeholder' => $f['placeholder'] ?? '', 'help' => $f['help'] ?? null,
                    'options' => isset($f['options']) ? collect($f['options'])->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values() : null,
                    'value' => empty($f['secret']) ? ($creds[$name] ?? '') : '',
                    'saved_hint' => ! empty($f['secret']) && ! empty($creds[$name]) ? '••••'.substr($creds[$name], -4) : null,
                ])->values(),
            ];
        }

        return ['provider' => $this->provider($schoolId), 'enabled' => $this->enabled($schoolId), 'providers' => $providers];
    }

    /**
     * Start a payment. Returns the payment and where to send the parent: ['url' => ...] or ['form' => ...].
     */
    public function start(Invoice $invoice, float $amount, User $payer, callable $returnUrl): array
    {
        $provider = $this->provider($invoice->school_id);
        if (! $provider || ! $this->enabled($invoice->school_id)) {
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
            'gateway' => $provider, 'reference' => 'SR'.$invoice->school_id.now()->format('ymd').strtoupper(Str::random(10)),
            'amount' => $amount, 'email' => $payer->email, 'status' => 'pending',
        ]);
        $gateway = $this->gateway($provider);
        try {
            $started = $gateway->start($payment, $this->credentials($invoice->school_id, $provider), $returnUrl($payment), $payer->name ?: 'Parent');
        } catch (\Throwable $e) {
            report($e);
            $started = ['error' => 'could not be reached'];
        }
        if (empty($started['url']) && empty($started['form'])) {
            $payment->update(['status' => 'failed', 'problem' => $gateway->label().' did not start the payment: '.Str::limit((string) ($started['error'] ?? ''), 150)]);
            throw ValidationException::withMessages(['amount' => $gateway->label().' could not start the payment just now. Please try again in a few minutes.']);
        }
        if (! empty($started['gateway_reference'])) {
            $payment->update(['gateway_reference' => (string) $started['gateway_reference']]);
        }

        return ['payment' => $payment] + $started;
    }

    /** Ask the provider how a payment went, and settle it */
    public function check(OnlinePayment $payment): OnlinePayment
    {
        if ($payment->status === 'success') {
            return $payment;
        }
        $creds = $this->credentials($payment->school_id, $payment->gateway);
        if (! $creds) {
            return $payment;
        }
        try {
            $result = $this->gateway($payment->gateway)->verify($payment, $creds);
        } catch (\Throwable $e) {
            report($e);
            $result = ['state' => 'pending'];
        }
        $payment->update(['checked_at' => now()]);

        return $this->settle($payment, $result);
    }

    /**
     * A webhook from a provider. Returns false when the message is not genuine. A genuine one only
     * names the payment; it is then checked with the provider directly rather than trusting its figures.
     */
    public function webhook(string $provider, int $schoolId, Request $request): bool
    {
        $creds = $this->credentials($schoolId, $provider);
        if (! $creds) {
            return false;
        }
        $reference = $this->gateway($provider)->webhookReference($request, $creds);
        if ($reference === null) {
            return false;
        }
        if ($reference !== '') {
            $payment = OnlinePayment::withoutGlobalScopes()->where('school_id', $schoolId)->where('gateway', $provider)->where('reference', $reference)->first();
            if ($payment) {
                $this->check($payment);
            }
        }

        return true;
    }

    /** Turn the provider's answer into the payment's outcome; a confirmed payment becomes a ledger line once only */
    public function settle(OnlinePayment $payment, array $result): OnlinePayment
    {
        return DB::transaction(function () use ($payment, $result) {
            $payment = OnlinePayment::withoutGlobalScopes()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->status === 'success') {
                return $payment;
            }
            $channel = isset($result['channel']) ? Str::limit(str_replace('_', ' ', strtolower((string) $result['channel'])), 30, '') : null;
            if ($result['state'] === 'failed') {
                $payment->update(['status' => 'failed', 'channel' => $channel, 'problem' => Str::limit((string) ($result['message'] ?? 'Did not go through'), 250)]);

                return $payment;
            }
            if ($result['state'] !== 'paid') {
                return $payment;                              // still waiting (bank transfers can take a while)
            }
            if (! isset($result['amount']) || abs((float) $result['amount'] - $payment->amount) > 0.001 || strtoupper((string) ($result['currency'] ?? 'NGN')) !== 'NGN') {
                $payment->update(['status' => 'needs_review', 'channel' => $channel, 'paid_at' => now(),
                    'problem' => 'The provider confirmed ₦'.number_format((float) ($result['amount'] ?? 0), 2).', not the ₦'.number_format($payment->amount, 2).' asked for.']);
                Log::warning('Online payment amount mismatch', ['reference' => $payment->reference]);

                return $payment;
            }

            $invoice = Invoice::withoutGlobalScopes()->findOrFail($payment->invoice_id);
            $payer = User::find($payment->paid_by) ?? User::find($invoice->issued_by);
            $label = $this->gateway($payment->gateway)->label();
            try {
                if (! $payer) {
                    throw ValidationException::withMessages(['amount' => 'the parent\'s account no longer exists.']);
                }
                $entry = $this->ledger->recordPayment(
                    $invoice, $payment->amount, 'online', now()->toDateString(), $payment->reference,
                    "Paid online with {$label}".($channel ? " ({$channel})" : '').($payment->gateway_reference ? ", {$label} ref {$payment->gateway_reference}" : ''),
                    $payer,
                );
            } catch (ValidationException $e) {
                // Money arrived but the invoice cannot take it, e.g. it was paid in cash meanwhile: staff refund or move it
                $payment->update(['status' => 'needs_review', 'channel' => $channel, 'paid_at' => now(),
                    'problem' => "Paid on {$label}, but not added to the invoice: ".collect($e->errors())->flatten()->first()]);

                return $payment;
            }
            $payment->update(['status' => 'success', 'channel' => $channel, 'paid_at' => now(), 'ledger_entry_id' => $entry->id, 'problem' => null]);

            return $payment;
        });
    }
}
