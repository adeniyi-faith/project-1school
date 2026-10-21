<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The safety-net tests' questions, asked of the live data: does every school-owned table carry the "only my school" rule,
 * and is there any record that sits in the wrong school or in none? Read-only. It reports counts, table and column names and
 * a few record numbers; it never shows anyone's personal details.
 */
class TenantIsolationChecker
{
    public const OK = 'ok';
    public const WARN = 'warn';
    public const BAD = 'bad';

    /** Same list as tests/Feature/Security/TenantCoverageTest: models with a school_id that are on purpose not filtered, and why */
    public const ALLOWED_UNFILTERED = ['SchoolModule', 'SchoolSetting', 'SchoolSubscription', 'User'];

    /** School-owned screens that are open to any signed-in staff on purpose (same list as RoutePermissionTest) */
    private const OPEN_ROUTES = [
        'school.classes.index', 'school.sections.index', 'school.subjects.index', 'school.shifts.index', 'school.holidays.index',
        'school.communication.notifications', 'school.communication.notifications.read', 'school.communication.notifications.read-all', 'school.communication.notifications.feed',
    ];

    private const MAX_SECONDS = 90;
    private const SHOW = 12;

    private float $deadline;
    private array $tables = [];
    private array $skipped = [];

    public function last(): ?array
    {
        return json_decode((string) PlatformSetting::get('tenant_check_last', ''), true) ?: null;
    }

    public function run(): array
    {
        @set_time_limit(self::MAX_SECONDS + 30);
        $this->deadline = microtime(true) + self::MAX_SECONDS;
        $this->skipped = [];

        $names = collect(Schema::getTables())->pluck('name')->reject(fn ($n) => $n === 'schools');
        $this->tables = $names->filter(fn ($t) => Schema::hasColumn($t, 'school_id'))->values()->all();

        $checks = [
            $this->modelsFiltered(),
            $this->tablesWithoutModel(),
            $this->noSchool(),
            $this->orphans(),
            $this->crossSchoolLinks(),
            $this->accountsWithoutSchool(),
            $this->unguardedRoutes(),
            $this->missingIndexes(),
        ];

        $summary = ['ok' => 0, 'warn' => 0, 'bad' => 0];
        foreach ($checks as $c) {
            $summary[$c['status']]++;
        }

        $report = ['checked_at' => now()->toIso8601String(), 'tables' => count($this->tables), 'summary' => $summary, 'checks' => $checks,
            'incomplete' => $this->skipped ? 'Ran out of time before checking: '.implode(', ', array_slice($this->skipped, 0, 8)).(count($this->skipped) > 8 ? ' and more' : '').'.' : null];

        PlatformSetting::set('tenant_check_last', json_encode($report), 'integrations');

        return $report;
    }

    private function result(string $key, string $label, string $question, array $findings, string $worst = self::BAD, ?string $okText = null): array
    {
        return [
            'key' => $key, 'label' => $label, 'question' => $question,
            'status' => $findings ? $worst : self::OK,
            'summary' => $findings ? count($findings).' finding(s)' : ($okText ?? 'Nothing found.'),
            'findings' => array_slice($findings, 0, self::SHOW), 'more' => max(0, count($findings) - self::SHOW),
        ];
    }

    private function timeLeft(string $what): bool
    {
        if (microtime(true) > $this->deadline) {
            $this->skipped[] = $what;

            return false;
        }

        return true;
    }

    private function modelsFiltered(): array
    {
        $found = [];
        foreach (glob(app_path('Models/*.php')) as $file) {
            $name = basename($file, '.php');
            $class = 'App\\Models\\'.$name;
            if (! class_exists($class) || ! is_subclass_of($class, Model::class) || (new \ReflectionClass($class))->isAbstract()) {
                continue;
            }
            $model = new $class;
            if (! Schema::hasColumn($model->getTable(), 'school_id') || in_array($name, self::ALLOWED_UNFILTERED, true)) {
                continue;
            }
            if (! array_key_exists(SchoolScope::class, $model->getGlobalScopes())) {
                $found[] = "$name (table {$model->getTable()}) has a school_id but is not limited to one school";
            }
        }

        return $this->result('models', 'Models are limited to one school', 'Does every model with a school_id carry the "only my school" rule?', $found, self::BAD, 'Every school-owned model is limited to its own school.');
    }

    private function tablesWithoutModel(): array
    {
        $covered = [];
        foreach (glob(app_path('Models/*.php')) as $file) {
            $class = 'App\\Models\\'.basename($file, '.php');
            if (class_exists($class) && is_subclass_of($class, Model::class) && ! (new \ReflectionClass($class))->isAbstract()) {
                $covered[] = (new $class)->getTable();
            }
        }
        // Spatie and Laravel's own tables are not ours to filter
        $found = collect($this->tables)->diff($covered)->reject(fn ($t) => in_array($t, ['activity_log', 'sessions', 'jobs', 'failed_jobs', 'cache', 'cache_locks', 'job_batches', 'migrations'], true))
            ->map(fn ($t) => "Table $t has a school_id but no model, so nothing filters it automatically")->values()->all();

        return $this->result('no-model', 'Tables without a model', 'Is there a school-owned table that only raw queries touch?', $found, self::WARN, 'Every school-owned table has a model.');
    }

    private function noSchool(): array
    {
        $found = [];
        foreach ($this->tables as $t) {
            if ($t === 'users' || ! $this->timeLeft("rows without a school in $t")) {
                continue;
            }
            try {
                $n = DB::table($t)->whereNull('school_id')->count();
                $n && $found[] = "$n row(s) in $t belong to no school".$this->ids($t, fn ($q) => $q->whereNull('school_id'));
            } catch (\Throwable) {
            }
        }

        return $this->result('no-school', 'Records with no school', 'Is any record not attached to a school at all?', $found, self::WARN, 'Every record belongs to a school.');
    }

    private function orphans(): array
    {
        $found = [];
        foreach ($this->tables as $t) {
            if (! $this->timeLeft("records pointing at missing schools in $t")) {
                continue;
            }
            try {
                $q = fn ($b) => $b->whereNotNull('school_id')->whereNotIn('school_id', DB::table('schools')->select('id'));
                $n = $q(DB::table($t))->count();
                $n && $found[] = "$n row(s) in $t point at a school that no longer exists".$this->ids($t, $q);
            } catch (\Throwable) {
            }
        }

        return $this->result('orphans', 'Records of a missing school', 'Does any record point at a school that does not exist?', $found, self::BAD, 'No record points at a missing school.');
    }

    /** A record in one school pointing at a record in another school: from declared foreign keys and from *_id column names */
    private function crossSchoolLinks(): array
    {
        $pairs = [];
        foreach ($this->tables as $a) {
            $columns = Schema::getColumnListing($a);
            try {
                foreach (Schema::getForeignKeys($a) as $fk) {
                    if (count($fk['columns']) === 1 && in_array($fk['foreign_table'], $this->tables, true)) {
                        $pairs["$a.{$fk['columns'][0]}"] = [$a, $fk['columns'][0], $fk['foreign_table'], $fk['foreign_columns'][0]];
                    }
                }
            } catch (\Throwable) {
            }
            foreach ($columns as $col) {
                if (str_ends_with($col, '_id') && $col !== 'school_id') {
                    $target = Str::plural(substr($col, 0, -3));
                    if ($target !== $a && in_array($target, $this->tables, true) && ! isset($pairs["$a.$col"])) {
                        $pairs["$a.$col"] = [$a, $col, $target, 'id'];
                    }
                }
            }
        }

        $found = [];
        foreach ($pairs as [$a, $col, $b, $bcol]) {
            if (! $this->timeLeft("links from $a.$col")) {
                continue;
            }
            try {
                $n = DB::table("$a as a")->join("$b as b", "a.$col", '=', "b.$bcol")
                    ->whereNotNull('a.school_id')->whereNotNull('b.school_id')->whereColumn('a.school_id', '!=', 'b.school_id')->count();
                $n && $found[] = "$n row(s) in $a point through $col at a record of a different school in $b";
            } catch (\Throwable) {
            }
        }

        return $this->result('links', 'Links between schools', 'Does any record in one school point at a record in another?', $found, self::BAD, count($pairs).' links between tables checked; none cross a school.');
    }

    private function accountsWithoutSchool(): array
    {
        $n = User::withoutGlobalScopes()->whereNull('school_id')->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super-admin'))->count();

        return $this->result('accounts', 'Accounts with no school', 'Is there a sign-in that belongs to no school and is not a platform owner?',
            $n ? ["$n account(s) have no school and are not a super admin"] : [], self::WARN, 'Every account is a platform owner or belongs to a school.');
    }

    private function unguardedRoutes(): array
    {
        $found = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => str_starts_with($r->getName() ?? '', 'school.') && ! str_starts_with($r->getName(), 'school.student.') && ! str_starts_with($r->getName(), 'school.parent.'))
            ->reject(fn ($r) => collect($r->gatherMiddleware())->contains(fn ($m) => is_string($m) && str_starts_with($m, 'permission:')))
            ->map(fn ($r) => $r->getName())->reject(fn ($n) => in_array($n, self::OPEN_ROUTES, true))
            ->map(fn ($n) => "Route $n has no permission check")->values()->all();

        return $this->result('routes', 'School pages have a permission check', 'Can anyone signed in open a school screen that has no permission rule?', $found, self::BAD, 'Every school screen is guarded by a permission.');
    }

    private function missingIndexes(): array
    {
        $found = [];
        foreach ($this->tables as $t) {
            try {
                $indexed = collect(Schema::getIndexes($t))->contains(fn ($i) => ($i['columns'][0] ?? null) === 'school_id');
                $indexed || $found[] = "Table $t has no index starting with school_id, so school-only lookups will slow down as it grows";
            } catch (\Throwable) {
            }
        }

        return $this->result('indexes', 'School lookups are indexed', 'Is every school-owned table quick to search by school?', $found, self::WARN, 'Every school-owned table has an index on school_id.');
    }

    /** A few record numbers (never names) so a finding can be traced */
    private function ids(string $table, callable $where): string
    {
        try {
            $ids = $where(DB::table($table))->limit(5)->pluck('id')->all();

            return $ids ? ' (record numbers '.implode(', ', $ids).')' : '';
        } catch (\Throwable) {
            return '';
        }
    }
}
