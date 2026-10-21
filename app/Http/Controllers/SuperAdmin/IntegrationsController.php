<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\IntegrationChecker;
use App\Support\PlatformAudit;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** The outside services the app relies on, whether each is set up, and a button to test the connection */
class IntegrationsController extends Controller
{
    public function __construct(private IntegrationChecker $checker) {}

    public function index(Request $request)
    {
        $data = ['integrations' => $this->checker->board()];

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json($data);
        }

        return Inertia::render('SuperAdmin/Integrations/Index', $data);
    }

    public function test(Request $request, string $integration)
    {
        $result = $this->checker->test($integration);
        PlatformAudit::record('tested connection: '.$integration, ['ok' => $result['ok']], null, $request);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
