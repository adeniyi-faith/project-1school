<?php

namespace App\Payments;

use App\Models\OnlinePayment;
use Illuminate\Http\Request;

/**
 * One payment provider (Paystack, Flutterwave ...). Each school uses its own account with the provider;
 * $creds are that school's saved keys. Amounts are in naira.
 */
interface Gateway
{
    /** Name shown to staff */
    public function label(): string;

    /**
     * The keys a school must enter: name => [label, secret (kept encrypted, never shown again), pattern (optional regex), placeholder, help].
     *
     * @return array<string, array{label: string, secret?: bool, pattern?: string, placeholder?: string, help?: string, optional?: bool, options?: array<string,string>}>
     */
    public function fields(): array;

    /** Where staff find the keys in the provider's dashboard */
    public function setupHelp(): string;

    /** True when the provider accepts the keys, false when it refuses them, null when they cannot be checked without a real payment */
    public function checkCredentials(array $creds): ?bool;

    /**
     * Start a payment. Returns either ['url' => page to send the parent to] or
     * ['form' => ['action' => url, 'fields' => [...]]] for providers that take a form post,
     * plus an optional 'gateway_reference' (the provider's own number for it).
     */
    public function start(OnlinePayment $payment, array $creds, string $returnUrl, string $payerName): array;

    /**
     * Ask the provider how a payment went.
     *
     * @return array{state: 'paid'|'failed'|'pending', amount?: float|null, currency?: string|null, channel?: string|null, message?: string|null}
     */
    public function verify(OnlinePayment $payment, array $creds): array;

    /**
     * Read a webhook (a message the provider's server sends to ours). Returns null when it is not genuine,
     * otherwise our payment reference it is about ('' when it is about nothing we need).
     * Its figures are never trusted: the payment is then checked with verify().
     */
    public function webhookReference(Request $request, array $creds): ?string;
}
