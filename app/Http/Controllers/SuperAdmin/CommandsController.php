<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformCommandRun;
use App\Services\PlatformCommandRunner;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** A short, fixed list of safe maintenance commands the platform owner can run from the browser, each one logged */
class CommandsController extends Controller
{
    public function index(Request $request)
    {
        $data = [
            'commands' => PlatformCommandRunner::available(),
            'runs' => PlatformCommandRun::with('user:id,name')->latest('id')->limit(25)->get()->map(fn ($r) => [
                'id' => $r->id, 'command' => $r->command, 'label' => PlatformCommandRunner::catalogue()[$r->command]['label'] ?? $r->command,
                'by' => $r->user?->name ?? 'Unknown', 'reason' => $r->reason, 'status' => $r->status, 'duration_ms' => $r->duration_ms,
                'output' => $r->output, 'at' => $r->created_at?->toIso8601String(),
            ]),
        ];

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json($data);
        }

        return Inertia::render('SuperAdmin/Commands/Index', $data);
    }

    public function run(Request $request, string $command, PlatformCommandRunner $runner)
    {
        abort_unless(isset(PlatformCommandRunner::catalogue()[$command]), 404);
        abort_if(PlatformCommandRunner::catalogue()[$command]['changes'] && ! config('platform_tools.commands_change_data'), 403, 'Commands that change data are switched off in this environment.');

        $rules = PlatformCommandRunner::needsReason($command) ? ['required', 'string', 'min:5', 'max:300'] : ['nullable', 'string', 'max:300'];
        $data = $request->validate(['reason' => $rules]);

        try {
            $run = $runner->run($command, $data['reason'] ?? null, $request);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with($run->status === 'ok' ? 'success' : 'error', $run->status === 'ok' ? 'The command finished. The output is under "Recent runs".' : 'The command failed. The output is under "Recent runs".');
    }
}
