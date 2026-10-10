<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Paystack tells us here when a payment succeeds. No login: the message is signed with the school's secret key. */
class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaystackService $paystack, int $school): Response
    {
        abort_unless(School::whereKey($school)->exists(), 404);
        $ok = $paystack->webhook($school, $request->getContent(), $request->header('x-paystack-signature'));

        return response('', $ok ? 200 : 401);
    }
}
