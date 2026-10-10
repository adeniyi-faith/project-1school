<?php

namespace App\Payments\Gateways;

use App\Models\OnlinePayment;
use App\Payments\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** Paystack (paystack.com). Test keys start pk_test_ / sk_test_. */
class PaystackGateway implements Gateway
{
    private const API = 'https://api.paystack.co';

    public function label(): string
    {
        return 'Paystack';
    }

    public function fields(): array
    {
        return [
            'public_key' => ['label' => 'Public key', 'pattern' => '/^pk_(test|live)_[A-Za-z0-9]+$/', 'placeholder' => 'pk_live_...'],
            'secret_key' => ['label' => 'Secret key', 'secret' => true, 'pattern' => '/^sk_(test|live)_[A-Za-z0-9]+$/', 'placeholder' => 'sk_live_...'],
        ];
    }

    public function setupHelp(): string
    {
        return 'In Paystack, open Settings, then API Keys & Webhooks. Copy the public and secret keys, and paste the webhook link below into "Live Webhook URL".';
    }

    public function checkCredentials(array $creds): ?bool
    {
        return Http::withToken($creds['secret_key'])->timeout(15)->get(self::API.'/balance')->successful();
    }

    public function start(OnlinePayment $payment, array $creds, string $returnUrl, string $payerName): array
    {
        $response = Http::withToken($creds['secret_key'])->timeout(20)->post(self::API.'/transaction/initialize', [
            'email' => $payment->email,
            'amount' => (int) round($payment->amount * 100),      // kobo
            'currency' => 'NGN',
            'reference' => $payment->reference,
            'callback_url' => $returnUrl,
            'metadata' => ['invoice_id' => $payment->invoice_id, 'student_id' => $payment->student_id],
        ]);

        return ['url' => $response->successful() ? $response->json('data.authorization_url') : null, 'error' => $response->json('message')];
    }

    public function verify(OnlinePayment $payment, array $creds): array
    {
        $response = Http::withToken($creds['secret_key'])->timeout(20)->get(self::API.'/transaction/verify/'.rawurlencode($payment->reference));
        $data = $response->json('data');
        if (! $response->successful() || ! is_array($data) || ($data['reference'] ?? '') !== $payment->reference) {
            return ['state' => 'pending'];
        }

        return [
            'state' => match ($data['status'] ?? '') { 'success' => 'paid', 'failed', 'reversed', 'abandoned' => 'failed', default => 'pending' },
            'amount' => isset($data['amount']) ? $data['amount'] / 100 : null,
            'currency' => $data['currency'] ?? null,
            'channel' => $data['channel'] ?? null,
            'message' => $data['gateway_response'] ?? null,
        ];
    }

    public function webhookReference(Request $request, array $creds): ?string
    {
        $signature = (string) $request->header('x-paystack-signature');
        if ($signature === '' || ! hash_equals(hash_hmac('sha512', $request->getContent(), $creds['secret_key']), $signature)) {
            return null;
        }

        return $request->input('event') === 'charge.success' ? (string) $request->input('data.reference', '') : '';
    }
}
