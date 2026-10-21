<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\DatabaseFacts;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Read-only: how big the database is, where the records are, and which questions to it were slow */
class DatabaseFactsController extends Controller
{
    public function index(Request $request, DatabaseFacts $facts)
    {
        $data = $request->validate(['school' => 'nullable|integer|min:1', 'refresh' => 'nullable|boolean']);
        $counts = $facts->counts((bool) ($data['refresh'] ?? false));
        $school = isset($data['school']) ? School::find($data['school']) : null;

        $payload = [
            'counted_at' => $counts['at'],
            'totals' => ['tables' => count($counts['tables']), 'rows' => array_sum(array_column($counts['tables'], 'rows')), 'size' => array_sum(array_map(fn ($t) => $t['size'] ?? 0, $counts['tables']))],
            'tables' => array_slice($counts['tables'], 0, 25),
            'schools' => $facts->schools($counts['by_school']),
            'chosen' => $school ? ['id' => $school->id, 'name' => $school->name, 'tables' => $facts->forSchool($school->id)] : null,
            'slow' => $facts->slowQueries(),
            'slow_limit_ms' => (int) config('platform_tools.slow_query_ms'),
        ];

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json($payload);
        }

        return Inertia::render('SuperAdmin/DatabaseFacts/Index', $payload);
    }
}
