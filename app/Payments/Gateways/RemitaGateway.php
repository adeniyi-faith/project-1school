<?php

namespace App\Payments\Gateways;

use App\Models\OnlinePayment;
use App\Payments\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Remita (remita.net, by SystemSpecs). A payment first gets a Remita Retrieval Reference (RRR);
 * the parent then pays it on Remita's page, or later at any bank with the RRR.
 */
class RemitaGateway implements Gateway
{
    public function label(): string
    {
        return 'Remita';
    }

    public function fields(): array
    {
        return [
            'mode' => ['label' => 'Account type', 'options' => ['live' => 'Live (real money)', 'test' => 'Demo (trying it out)']],
            'merchant_id' => ['label' => 'Merchant ID', 'pattern' => '/^[0-9]{4,20}$/', 'placeholder' => 'e.g. 2547916'],
            'service_type_id' => ['label' => 'Service type ID', 'pattern' => '/^[0-9]{4,20}$/', 'placeholder' => 'The ID of your "School fees" service'],
            'api_key' => ['label' => 'API key', 'secret' => true, 'placeholder' => 'From Remita'],
        ];
    }

    public function setupHelp(): string
    {
        return 'Remita gives the merchant ID, API key and service type ID when your school\'s merchant account is set up (ask your Remita account officer for a "School fees" service type). Remita cannot check keys until a payment is made, so try a small payment first.';
    }

    private function base(array $creds): string
    {
        return ($creds['mode'] ?? 'live') === 'test' ? 'https://demo.remita.net' : 'https://login.remita.net';
    }

    public function checkCredentials(array $creds): ?bool
    {
        return null;
    }

    public function start(OnlinePayment $payment, array $creds, string $returnUrl, string $payerName): array
    {
        $amount = number_format($payment->amount, 2, '.', '');
        $hash = hash('sha512', $creds['merchant_id'].$creds['service_type_id'].$payment->reference.$amount.$creds['api_key']);
        $response = Http::withHeaders(['Authorization' => "remitaConsumerKey={$creds['merchant_id']},remitaConsumerToken={$hash}"])->timeout(20)
            ->post($this->base($creds).'/remita/exapp/api/v1/send/api/echannelsvc/merchant/api/paymentinit', [
                'serviceTypeId' => $creds['service_type_id'], 'amount' => $amount, 'orderId' => $payment->reference,
                'payerName' => $payerName, 'payerEmail' => $payment->email, 'payerPhone' => '', 'description' => 'School fees',
            ]);
        $data = self::json($response->body());
        $rrr = $data['RRR'] ?? null;
        if (! $rrr) {
            return ['url' => null, 'error' => $data['status'] ?? 'No RRR'];
        }

        return [
            'gateway_reference' => (string) $rrr,
            'form' => ['action' => $this->base($creds).'/remita/ecomm/finalize.reg', 'fields' => [
                'merchantId' => $creds['merchant_id'], 'hash' => hash('sha512', $creds['merchant_id'].$rrr.$creds['api_key']),
                'rrr' => (string) $rrr, 'responseurl' => $returnUrl,
            ]],
        ];
    }

    public function verify(OnlinePayment $payment, array $creds): array
    {
        $rrr = $payment->gateway_reference;
        if (! $rrr) {
            return ['state' => 'pending'];
        }
        $hash = hash('sha512', $rrr.$creds['api_key'].$creds['merchant_id']);
        $response = Http::withHeaders(['Authorization' => "remitaConsumerKey={$creds['merchant_id']},remitaConsumerToken={$hash}"])->timeout(20)
            ->get($this->base($creds)."/remita/exapp/api/v1/send/api/echannelsvc/{$creds['merchant_id']}/{$rrr}/{$hash}/status.reg");
        $data = self::json($response->body());
        if (! $response->successful() || ! $data || (string) ($data['RRR'] ?? $rrr) !== (string) $rrr) {
            return ['state' => 'pending'];
        }
        $status = (string) ($data['status'] ?? '');

        return [
            'state' => in_array($status, ['00', '01'], true) ? 'paid' : (in_array($status, ['02', '022', '012'], true) ? 'failed' : 'pending'),
            'amount' => isset($data['amount']) ? (float) $data['amount'] : null,
            'currency' => 'NGN',
            'channel' => $data['paymentMethod'] ?? null,
            'message' => $data['message'] ?? null,
        ];
    }

    public function webhookReference(Request $request, array $creds): ?string
    {
        // Remita's notification is not signed; it only tells us which payment to check with Remita directly
        $items = $request->json()->all();
        $first = array_is_list($items) ? ($items[0] ?? []) : $items;

        return (string) ($first['orderRef'] ?? $first['orderId'] ?? '');
    }

    /** Remita sometimes wraps its answer as jsonp ({...}) */
    public static function json(string $body): ?array
    {
        $body = trim($body);
        if (preg_match('/^\w+\s*\((.*)\)\s*;?$/s', $body, $m)) {
            $body = $m[1];
        }
        $data = json_decode($body, true);

        return is_array($data) ? $data : null;
    }
}
