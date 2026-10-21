<?php

namespace App\Services;

use App\Models\ScheduledTaskRun;
use Cron\CronExpression;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event as Events;

/** Remembers every run of the scheduler's tasks (what ran, when, how long, failures) and answers "is the cron job alive?" */
class SchedulerMonitor
{
    /** The tiny task that proves the server's cron job is calling the scheduler */
    public const HEARTBEAT = 'Scheduler heartbeat';
    public const BEAT_KEY = 'platform.scheduler.last_beat';
    private const KEEP_DAYS = 14;

    public static function listen(): void
    {
        Events::listen(ScheduledTaskFinished::class, fn (ScheduledTaskFinished $e) => self::record($e->task, (int) round($e->runtime * 1000), ($e->task->exitCode ?? 0) === 0 ? 'ok' : 'failed', null));
        Events::listen(ScheduledTaskFailed::class, fn (ScheduledTaskFailed $e) => self::record($e->task, null, 'failed', $e->exception->getMessage()));
    }

    public static function nameOf(Event $task): string
    {
        return $task->description ?: trim(preg_replace('/^.*artisan[\'"]?\s+/', '', $task->getSummaryForDisplay())) ?: 'Task';
    }

    private static function record(Event $task, ?int $ms, string $status, ?string $error): void
    {
        $name = self::nameOf($task);
        if ($name === self::HEARTBEAT) {
            return; // it only proves the scheduler ran; no need to fill the history with it
        }

        try {
            $output = $error;
            if ($output === null && $task->output && is_file($task->output) && filesize($task->output) < 65536) {
                $output = trim((string) file_get_contents($task->output)) ?: null;
            }
            ScheduledTaskRun::create([
                'name' => mb_substr($name, 0, 255), 'expression' => $task->expression, 'started_at' => now()->subMilliseconds($ms ?? 0),
                'finished_at' => now(), 'duration_ms' => $ms, 'status' => $status, 'output' => $output ? mb_substr($output, 0, 4000) : null,
            ]);
            if (random_int(1, 50) === 1) {
                ScheduledTaskRun::where('created_at', '<', now()->subDays(self::KEEP_DAYS))->delete();
            }
        } catch (\Throwable) {
            // a monitor must never break the task it is watching (for example before the table exists)
        }
    }

    public static function beat(): void
    {
        Cache::put(self::BEAT_KEY, now()->timestamp, 86400 * 3);
    }

    /** @return array{heartbeat: ?string, alive: bool, tasks: array<int, array>} */
    public function overview(): array
    {
        $beat = Cache::get(self::BEAT_KEY);
        $tasks = collect(app(Schedule::class)->events())
            ->reject(fn (Event $e) => self::nameOf($e) === self::HEARTBEAT)
            ->map(function (Event $e) {
                $name = self::nameOf($e);
                $last = ScheduledTaskRun::where('name', mb_substr($name, 0, 255))->latest('started_at')->first();

                return [
                    'name' => $name, 'expression' => $e->expression,
                    'next_due' => CronExpression::isValidExpression($e->expression) ? (new CronExpression($e->expression))->getNextRunDate()->format('c') : null,
                    'last_run' => $last?->started_at?->toIso8601String(), 'last_status' => $last?->status, 'last_ms' => $last?->duration_ms,
                ];
            })->values()->all();

        return ['heartbeat' => $beat ? now()->setTimestamp((int) $beat)->toIso8601String() : null, 'alive' => $beat && now()->timestamp - $beat < 900, 'tasks' => $tasks];
    }
}
