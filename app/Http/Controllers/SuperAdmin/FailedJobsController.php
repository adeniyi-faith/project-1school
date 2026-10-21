<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Support\PlatformAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;

/** Background jobs that failed: see why, send one back to try again, or delete it (a reason is required and logged) */
class FailedJobsController extends Controller
{
    private function table(): string
    {
        return (string) config('queue.failed.table', 'failed_jobs');
    }

    public function index(Request $request)
    {
        $table = $this->table();
        $ready = Schema::hasTable($table);
        $jobs = $ready
            ? DB::table($table)->orderByDesc('id')->paginate(20)->through(function ($row) {
                $payload = json_decode((string) $row->payload, true) ?: [];

                return [
                    'uuid' => $row->uuid, 'job' => $payload['displayName'] ?? $payload['job'] ?? 'Unknown job', 'queue' => $row->queue, 'connection' => $row->connection,
                    'failed_at' => $row->failed_at, 'attempts' => $payload['attempts'] ?? null,
                    'summary' => Str::limit($this->hideSecrets(strtok((string) $row->exception, "\n") ?: ''), 300),
                    'exception' => Str::limit($this->hideSecrets((string) $row->exception), 3000),
                ];
            })
            : null;

        $data = ['ready' => $ready, 'jobs' => $jobs, 'driver' => config('queue.default')];
        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json($data);
        }

        return Inertia::render('SuperAdmin/FailedJobs/Index', $data);
    }

    public function retry(Request $request, string $uuid)
    {
        abort_unless(Str::isUuid($uuid) && DB::table($this->table())->where('uuid', $uuid)->exists(), 404);

        PlatformAudit::record('failed job sent back to try again', ['uuid' => $uuid], null, $request);
        Artisan::call('queue:retry', ['id' => [$uuid]]);

        return back()->with('success', 'The job was sent back. If the queue is not running (it runs inside the page when set to "sync"), it has already tried again; refresh to see whether it failed again.');
    }

    public function retryAll(Request $request)
    {
        $count = DB::table($this->table())->count();
        PlatformAudit::record('all failed jobs sent back to try again', ['count' => $count], null, $request);
        Artisan::call('queue:retry', ['id' => ['all']]);

        return back()->with('success', "$count failed job(s) were sent back to try again.");
    }

    public function destroy(Request $request, string $uuid)
    {
        $data = $request->validate(['reason' => 'required|string|min:5|max:300']);
        abort_unless(Str::isUuid($uuid) && DB::table($this->table())->where('uuid', $uuid)->exists(), 404);

        PlatformAudit::record('failed job deleted', ['uuid' => $uuid], $data['reason'], $request);
        Artisan::call('queue:forget', ['id' => $uuid]);

        return back()->with('success', 'The failed job was deleted.');
    }

    public function flush(Request $request)
    {
        $data = $request->validate(['reason' => 'required|string|min:5|max:300']);
        $count = DB::table($this->table())->count();

        PlatformAudit::record('all failed jobs deleted', ['count' => $count], $data['reason'], $request);
        Artisan::call('queue:flush');

        return back()->with('success', "$count failed job(s) were deleted.");
    }

    private function hideSecrets(string $text): string
    {
        return preg_replace('/(password|passwd|secret|token|api[_-]?key|authorization|bearer)(["\']?\s*[:=]\s*["\']?|\s+)[^\s"\',}]{4,}/i', '$1$2[hidden]', str_replace(base_path().'/', '', $text));
    }
}
