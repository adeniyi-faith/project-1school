<?php

namespace App\Payments\Gateways;

use App\Models\OnlinePayment;
use App\Payments\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** Flutterwave (flutterwave.com), "Flutterwave Standard" hosted checkout, API v3 */
class FlutterwaveGateway implements Gateway
{
    private const API = 'https://api.flutterwave.com/v3';

    public function label(): string
    {
        return 'Flutterwave';
    }

    public function fields(): array
    {
        return [
            'public_key' => ['label' => 'Public key', 'pattern' => '/^FLWPUBK(_TEST)?-[A-Za-z0-9]+-X$/', 'placeholder' => 'FLWPUBK-...-X'],
            'secret_key' => ['label' => 'Secret key', 'secret' => true, 'pattern' => '/^FLWSECK(_TEST)?-[A-Za-z0-9]+-X$/', 'placeholder' => 'FLWSECK-...-X'],
            'secret_hash' => ['label' => 'Webhook secret hash', 'secret' => true, 'optional' => true, 'placeholder' => 'Any long password you choose',
                'help' => 'Type the same words into "Secret hash" on Flutterwave\'s webhook settings.'],
        ];
    }

    public function setupHelp(): string
    {
        return 'In Flutterwave, open Settings, then API Keys, and copy the public and secret keys. Under Settings, Webhooks, paste the webhook link below and choose a secret hash.';
    }

    public function checkCredentials(array $creds): ?bool
    {
        return Http::withToken($creds['secret_key'])->timeout(15)->get(self::API.'/balances')->successful();
    }

    public function start(OnlinePayment $payment, array $creds, string $returnUrl, string $payerName): array
    {
        $response = Http::withToken($creds['secret_key'])->timeout(20)->post(self::API.'/payments', [
            'tx_ref' => $payment->reference,
            'amount' => round($payment->amount, 2),
            'currency' => 'NGN',
            'redirect_url' => $returnUrl,
            'customer' => ['email' => $payment->email, 'name' => $payerName],
            'customizations' => ['title' => 'School fees'],
            'meta' => ['invoice_id' => $payment->invoice_id, 'student_id' => $payment->student_id],
        ]);

        return ['url' => $response->successful() ? $response->json('data.link') : null, 'error' => $response->json('message')];
    }

    public function verify(OnlinePayment $payment, array $creds): array
    {
        $response = Http::withToken($creds['secret_key'])->timeout(20)->get(self::API.'/transactions/verify_by_reference', ['tx_ref' => $payment->reference]);
        $data = $response->json('data');
        if (! $response->successful() || ! is_array($data) || ($data['tx_ref'] ?? '') !== $payment->reference) {
            return ['state' => 'pending'];
        }

        return [
            'state' => match (strtolower((string) ($data['status'] ?? ''))) { 'successful' => 'paid', 'failed', 'cancelled' => 'failed', default => 'pending' },
            'amount' => isset($data['amount']) ? (float) $data['amount'] : null,
            'currency' => $data['currency'] ?? null,
            'channel' => $data['payment_type'] ?? null,
            'message' => $data['processor_response'] ?? null,
        ];
    }

    public function webhookReference(Request $request, array $creds): ?string
    {
        // Flutterwave sends back the secret hash the school chose, as it is
        $hash = (string) $request->header('verif-hash');
        if (empty($creds['secret_hash']) || $hash === '' || ! hash_equals($creds['secret_hash'], $hash)) {
            return null;
        }

        return (string) $request->input('data.tx_ref', $request->input('txRef', ''));
    }
}
