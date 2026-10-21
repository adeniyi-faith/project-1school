<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** How big the database is and where the rows are: per table, per school, and the slowest questions it was asked */
class DatabaseFacts
{
    private const CACHE_KEY = 'platform.database.counts';
    private const MINUTES = 10;

    /** @return array{at:string, tables: array<int, array{name:string, rows:int, size:?int, per_school:bool}>, by_school: array<int, int>} */
    public function counts(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, now()->addMinutes(self::MINUTES), function () {
            $tables = [];
            $bySchool = [];
            foreach (Schema::getTables() as $t) {
                $name = $t['name'];
                $hasSchool = Schema::hasColumn($name, 'school_id');
                try {
                    $rows = DB::table($name)->count();
                    if ($hasSchool && $name !== 'users') {
                        foreach (DB::table($name)->whereNotNull('school_id')->selectRaw('school_id, count(*) as n')->groupBy('school_id')->pluck('n', 'school_id') as $id => $n) {
                            $bySchool[$id] = ($bySchool[$id] ?? 0) + (int) $n;
                        }
                    }
                } catch (\Throwable) {
                    $rows = 0;
                }
                $tables[] = ['name' => $name, 'rows' => $rows, 'size' => isset($t['size']) ? (int) $t['size'] : null, 'per_school' => $hasSchool];
            }
            usort($tables, fn ($a, $b) => $b['rows'] <=> $a['rows']);
            arsort($bySchool);

            return ['at' => now()->toIso8601String(), 'tables' => $tables, 'by_school' => $bySchool];
        });
    }

    /** The biggest schools by number of records, with their names */
    public function schools(array $bySchool, int $limit = 15): array
    {
        $top = array_slice($bySchool, 0, $limit, true);
        $names = School::whereIn('id', array_keys($top))->pluck('name', 'id');

        return collect($top)->map(fn ($rows, $id) => ['id' => $id, 'name' => $names[$id] ?? "School $id", 'rows' => $rows])->values()->all();
    }

    /** One school's records, table by table */
    public function forSchool(int $schoolId): array
    {
        $out = [];
        foreach ($this->counts()['tables'] as $t) {
            if (! $t['per_school'] || $t['name'] === 'users') {
                continue;
            }
            try {
                $n = DB::table($t['name'])->where('school_id', $schoolId)->count();
                $n && $out[] = ['name' => $t['name'], 'rows' => $n];
            } catch (\Throwable) {
            }
        }
        usort($out, fn ($a, $b) => $b['rows'] <=> $a['rows']);

        return $out;
    }

    /** Slow questions grouped by shape, worst first */
    public function slowQueries(): array
    {
        try {
            return DB::table('slow_queries')->where('created_at', '>=', now()->subDays(7))
                ->selectRaw('hash, min(sql) as sql, count(*) as times, max(time_ms) as worst_ms, avg(time_ms) as avg_ms, max(created_at) as last_seen, max(path) as path')
                ->groupBy('hash')->orderByDesc('worst_ms')->limit(25)->get()
                ->map(fn ($r) => ['hash' => $r->hash, 'sql' => $r->sql, 'times' => (int) $r->times, 'worst_ms' => (int) $r->worst_ms, 'avg_ms' => (int) round($r->avg_ms), 'last_seen' => $r->last_seen, 'path' => $r->path])->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
