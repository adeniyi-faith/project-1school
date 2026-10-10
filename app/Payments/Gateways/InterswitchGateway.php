<?php

namespace App\Payments\Gateways;

use App\Models\OnlinePayment;
use App\Payments\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** Interswitch Quickteller Business web checkout (Verve, Mastercard, Visa, bank transfer, USSD) */
class InterswitchGateway implements Gateway
{
    public function label(): string
    {
        return 'Interswitch';
    }

    public function fields(): array
    {
        return [
            'mode' => ['label' => 'Account type', 'options' => ['live' => 'Live (real money)', 'test' => 'Sandbox (trying it out)']],
            'merchant_code' => ['label' => 'Merchant code', 'pattern' => '/^[A-Za-z0-9]{4,20}$/', 'placeholder' => 'e.g. MX12345'],
            'pay_item_id' => ['label' => 'Pay item ID', 'pattern' => '/^[A-Za-z0-9_]{3,40}$/', 'placeholder' => 'e.g. Default_Payable_MX12345'],
        ];
    }

    public function setupHelp(): string
    {
        return 'In Quickteller Business, open Developer settings (or Settings, then API keys) and copy the merchant code and pay item ID. Interswitch cannot check these until a payment is made, so try a small payment first.';
    }

    private function live(array $creds): bool
    {
        return ($creds['mode'] ?? 'live') !== 'test';
    }

    public function checkCredentials(array $creds): ?bool
    {
        return null;
    }

    public function start(OnlinePayment $payment, array $creds, string $returnUrl, string $payerName): array
    {
        return ['form' => [
            'action' => $this->live($creds) ? 'https://newwebpay.interswitchng.com/collections/w/pay' : 'https://sandbox.interswitchng.com/collections/w/pay',
            'fields' => [
                'merchant_code' => $creds['merchant_code'], 'pay_item_id' => $creds['pay_item_id'], 'txn_ref' => $payment->reference,
                'amount' => (string) (int) round($payment->amount * 100), 'currency' => '566', 'site_redirect_url' => $returnUrl,
                'cust_email' => $payment->email, 'cust_name' => $payerName, 'pay_item_name' => 'School fees',
            ],
        ]];
    }

    public function verify(OnlinePayment $payment, array $creds): array
    {
        $url = ($this->live($creds) ? 'https://webpay.interswitchng.com' : 'https://sandbox.interswitchng.com').'/collections/api/v1/gettransaction.json';
        $response = Http::acceptJson()->timeout(20)->get($url, [
            'merchantcode' => $creds['merchant_code'], 'transactionreference' => $payment->reference, 'amount' => (int) round($payment->amount * 100),
        ]);
        $data = $response->json();
        if (! $response->successful() || ! is_array($data) || ! isset($data['ResponseCode'])) {
            return ['state' => 'pending'];
        }
        $code = (string) $data['ResponseCode'];

        return [
            // 00 is approved; Z0/Z1/09 mean still in progress; anything else did not go through
            'state' => $code === '00' ? 'paid' : (in_array($code, ['Z0', 'Z1', '09', '10011'], true) ? 'pending' : 'failed'),
            'amount' => isset($data['Amount']) ? $data['Amount'] / 100 : null,
            'currency' => 'NGN',
            'channel' => null,
            'message' => $data['ResponseDescription'] ?? null,
        ];
    }

    public function webhookReference(Request $request, array $creds): ?string
    {
        // Interswitch's notification only tells us which payment to check with Interswitch directly
        return (string) ($request->input('data.merchantReference') ?? $request->input('txnref', ''));
    }
}
