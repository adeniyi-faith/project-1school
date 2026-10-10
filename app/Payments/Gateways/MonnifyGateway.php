<?php

namespace App\Payments\Gateways;

use App\Models\OnlinePayment;
use App\Payments\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** Monnify by Moniepoint (monnify.com): card, bank transfer and USSD through its hosted checkout */
class MonnifyGateway implements Gateway
{
    public function label(): string
    {
        return 'Monnify';
    }

    public function fields(): array
    {
        return [
            'mode' => ['label' => 'Account type', 'options' => ['live' => 'Live (real money)', 'test' => 'Sandbox (trying it out)']],
            'api_key' => ['label' => 'API key', 'pattern' => '/^MK_(TEST|PROD)_[A-Z0-9]+$/', 'placeholder' => 'MK_PROD_...'],
            'secret_key' => ['label' => 'Secret key', 'secret' => true, 'placeholder' => 'From the same page'],
            'contract_code' => ['label' => 'Contract code', 'pattern' => '/^[0-9]{6,20}$/', 'placeholder' => 'e.g. 626609763141'],
        ];
    }

    public function setupHelp(): string
    {
        return 'In Monnify, open Developer, then API Keys & Contracts. Copy the API key, secret key and contract code. Under Developer, Webhook URLs, paste the link below as the transaction completion webhook.';
    }

    private function base(array $creds): string
    {
        return ($creds['mode'] ?? 'live') === 'test' ? 'https://sandbox.monnify.com' : 'https://api.monnify.com';
    }

    private function token(array $creds): ?string
    {
        $response = Http::withBasicAuth($creds['api_key'], $creds['secret_key'])->timeout(15)->post($this->base($creds).'/api/v1/auth/login');

        return $response->successful() ? $response->json('responseBody.accessToken') : null;
    }

    public function checkCredentials(array $creds): ?bool
    {
        return $this->token($creds) !== null;
    }

    public function start(OnlinePayment $payment, array $creds, string $returnUrl, string $payerName): array
    {
        $token = $this->token($creds);
        if (! $token) {
            return ['url' => null, 'error' => 'Monnify refused the school\'s keys.'];
        }
        $response = Http::withToken($token)->timeout(20)->post($this->base($creds).'/api/v1/merchant/transactions/init-transaction', [
            'amount' => round($payment->amount, 2),
            'customerName' => $payerName,
            'customerEmail' => $payment->email,
            'paymentReference' => $payment->reference,
            'paymentDescription' => 'School fees',
            'currencyCode' => 'NGN',
            'contractCode' => $creds['contract_code'],
            'redirectUrl' => $returnUrl,
            'paymentMethods' => ['CARD', 'ACCOUNT_TRANSFER', 'USSD'],
        ]);

        return [
            'url' => $response->successful() ? $response->json('responseBody.checkoutUrl') : null,
            'gateway_reference' => $response->json('responseBody.transactionReference'),
            'error' => $response->json('responseMessage'),
        ];
    }

    public function verify(OnlinePayment $payment, array $creds): array
    {
        $token = $this->token($creds);
        if (! $token) {
            return ['state' => 'pending'];
        }
        $response = Http::withToken($token)->timeout(20)->get($this->base($creds).'/api/v2/merchant/transactions/query', ['paymentReference' => $payment->reference]);
        $data = $response->json('responseBody');
        if (! $response->successful() || ! is_array($data) || ($data['paymentReference'] ?? '') !== $payment->reference) {
            return ['state' => 'pending'];
        }
        $status = (string) ($data['paymentStatus'] ?? '');

        return [
            // A part or over payment by bank transfer is not the amount asked for, so it goes to the bursar
            'state' => match ($status) { 'PAID', 'OVERPAID', 'PARTIALLY_PAID' => 'paid', 'FAILED', 'EXPIRED', 'REVERSED', 'CANCELLED' => 'failed', default => 'pending' },
            'amount' => isset($data['amountPaid']) ? (float) $data['amountPaid'] : null,
            'currency' => $data['currencyCode'] ?? $data['currency'] ?? 'NGN',
            'channel' => isset($data['paymentMethod']) ? strtolower((string) $data['paymentMethod']) : null,
            'message' => $status,
        ];
    }

    public function webhookReference(Request $request, array $creds): ?string
    {
        $signature = (string) $request->header('monnify-signature');
        if ($signature === '' || ! hash_equals(hash_hmac('sha512', $request->getContent(), $creds['secret_key']), $signature)) {
            return null;
        }

        return (string) $request->input('eventData.paymentReference', '');
    }
}
