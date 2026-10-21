<?php

namespace App\Services;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

/** Writes down database questions slower than the set limit, so the platform owner can see which ones to fix. Only the question's shape is kept, never the values in it. */
class SlowQueryRecorder
{
    private const KEEP_DAYS = 7;
    private static bool $busy = false;

    public static function listen(): void
    {
        DB::listen(function (QueryExecuted $q) {
            $limit = (int) config('platform_tools.slow_query_ms');
            if ($limit <= 0 || $q->time < $limit || self::$busy || str_contains($q->sql, 'slow_queries')) {
                return;
            }

            self::$busy = true;
            try {
                // collapse the lists of "?, ?, ?" so the same question groups together
                $shape = mb_substr(preg_replace('/\(\s*\?(?:\s*,\s*\?)+\s*\)/', '(?...)', $q->sql), 0, 1500);
                DB::table('slow_queries')->insert([
                    'hash' => md5($shape), 'sql' => $shape, 'time_ms' => (int) round($q->time),
                    'path' => app()->runningInConsole() ? 'console' : mb_substr('/'.ltrim(request()->path(), '/'), 0, 200), 'created_at' => now(),
                ]);
                if (random_int(1, 100) === 1) {
                    DB::table('slow_queries')->where('created_at', '<', now()->subDays(self::KEEP_DAYS))->delete();
                }
            } catch (\Throwable) {
                // watching must never break the page being watched (for example before the table exists)
            } finally {
                self::$busy = false;
            }
        });
    }
}
