<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\ErrorLogReader;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Read-only: the app's own error log, searchable and grouped so repeats count as one line */
class ErrorLogController extends Controller
{
    public function index(Request $request, ErrorLogReader $log)
    {
        $data = $request->validate(['q' => 'nullable|string|max:100', 'level' => 'nullable|in:problems,errors,warnings,all']);
        $level = $data['level'] ?? 'problems';
        $report = $log->read($data['q'] ?? null, $level);

        $tracker = (string) config('platform_tools.error_tracker_url');

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json($report);
        }

        return Inertia::render('SuperAdmin/ErrorLog/Index', $report + [
            'filters' => ['q' => $data['q'] ?? '', 'level' => $level],
            // Only a plain https address is ever offered as a link
            'trackerUrl' => preg_match('#^https://#i', $tracker) ? $tracker : null,
        ]);
    }
}
