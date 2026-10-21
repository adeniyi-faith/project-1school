<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ScheduledTaskRun;
use App\Services\SchedulerMonitor;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Read-only: what the scheduler is set to run, and what actually ran (when, how long, failures) */
class ScheduledTasksController extends Controller
{
    public function index(Request $request, SchedulerMonitor $monitor)
    {
        $data = $monitor->overview() + [
            'runs' => ScheduledTaskRun::latest('started_at')->limit(60)->get(['id', 'name', 'started_at', 'duration_ms', 'status', 'output']),
            'failed_week' => ScheduledTaskRun::where('status', 'failed')->where('started_at', '>=', now()->subDays(7))->count(),
        ];

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json($data);
        }

        return Inertia::render('SuperAdmin/ScheduledTasks/Index', $data);
    }
}
