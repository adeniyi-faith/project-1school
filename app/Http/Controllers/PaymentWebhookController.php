<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\OnlinePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Payment providers tell us here when a payment goes through. No login: each message is checked
 * (a signature made with the school's secret key, where the provider signs), and in every case the
 * payment is then confirmed by asking the provider directly.
 */
class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, OnlinePaymentService $payments, string $provider, int $school): Response
    {
        abort_unless(isset(OnlinePaymentService::GATEWAYS[$provider]) && School::whereKey($school)->exists(), 404);

        return response('', $payments->webhook($provider, $school, $request) ? 200 : 401);
    }
}
