<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\PlatformHealthService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Read-only page showing whether the app's own machinery (database, cache, queue, files, disk, PHP) is healthy */
class HealthController extends Controller
{
    public function __construct(private PlatformHealthService $health) {}

    public function index(Request $request)
    {
        $report = $this->health->report();

        // Same data as plain JSON for scripts and uptime monitors (still needs a signed-in super admin)
        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json($report);
        }

        return Inertia::render('SuperAdmin/Health/Index', ['report' => $report]);
    }
}
