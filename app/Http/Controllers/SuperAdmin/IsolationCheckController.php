<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\TenantIsolationChecker;
use App\Support\PlatformAudit;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Asks the live data the same questions the safety-net tests ask: is every school's information kept apart? Read-only. */
class IsolationCheckController extends Controller
{
    public function __construct(private TenantIsolationChecker $checker) {}

    public function index(Request $request)
    {
        $data = ['report' => $this->checker->last()];

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json($data);
        }

        return Inertia::render('SuperAdmin/IsolationCheck/Index', $data);
    }

    public function run(Request $request)
    {
        $report = $this->checker->run();
        PlatformAudit::record('ran the school-separation self-check', ['problems' => $report['summary']['bad'], 'warnings' => $report['summary']['warn']], null, $request);

        return back()->with($report['summary']['bad'] ? 'error' : 'success', $report['summary']['bad']
            ? $report['summary']['bad'].' check(s) found something that needs fixing.' : 'The check finished with nothing that needs fixing.');
    }
}
