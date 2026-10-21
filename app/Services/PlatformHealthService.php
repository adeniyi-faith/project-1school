<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Looks at the machine the app runs on and reports plainly what is healthy. Every check is wrapped so that
 * one broken or blocked thing (common on shared hosting) shows as "could not check" instead of breaking the page.
 */
class PlatformHealthService
{
    public const OK = 'ok';
    public const WARN = 'warn';
    public const BAD = 'bad';
    public const UNKNOWN = 'unknown';

    /** PHP extensions the app really needs, with the job each one does */
    private const EXTENSIONS = [
        'pdo' => 'database access', 'mbstring' => 'names and text in any language', 'openssl' => 'encryption and secure calls',
        'json' => 'data exchange', 'ctype' => 'text checks', 'tokenizer' => 'framework core', 'xml' => 'PDF and Word files',
        'fileinfo' => 'checking uploaded files', 'curl' => 'calling Supabase, Google and payment providers',
        'zip' => 'reading Word schemes of work and building ZIP downloads', 'gd' => 'shrinking pictures and QR codes',
    ];

    /** @return array{checks: array<int, array>, summary: array{ok:int, warn:int, bad:int, unknown:int}, checked_at: string} */
    public function report(): array
    {
        $checks = [
            $this->guard('app', 'Application', fn () => $this->app()),
            $this->guard('database', 'Database', fn () => $this->database()),
            $this->guard('cache', 'Cache', fn () => $this->cache()),
            $this->guard('queue', 'Queue', fn () => $this->queue()),
            $this->guard('storage', 'File storage', fn () => $this->storage()),
            $this->guard('disk', 'Disk space', fn () => $this->disk()),
            $this->guard('deploy', 'Last deploy', fn () => $this->deploy()),
            $this->guard('migrations', 'Database updates', fn () => $this->migrations()),
            $this->guard('php', 'PHP', fn () => $this->php()),
        ];

        $summary = ['ok' => 0, 'warn' => 0, 'bad' => 0, 'unknown' => 0];
        foreach ($checks as $c) {
            $summary[$c['status']]++;
        }

        return ['checks' => $checks, 'summary' => $summary, 'checked_at' => now()->toIso8601String()];
    }

    private function guard(string $key, string $label, callable $check): array
    {
        try {
            return ['key' => $key, 'label' => $label] + $check();
        } catch (\Throwable $e) {
            return ['key' => $key, 'label' => $label] + $this->result(self::UNKNOWN, 'Could not check this: '.Str::limit($e->getMessage(), 160));
        }
    }

    /** @param array<string, string|int|float|null> $facts */
    private function result(string $status, string $summary, array $facts = [], array $notes = []): array
    {
        return ['status' => $status, 'summary' => $summary, 'facts' => collect($facts)->map(fn ($v, $k) => ['label' => $k, 'value' => (string) ($v ?? '—')])->values()->all(), 'notes' => $notes];
    }

    private function app(): array
    {
        $env = (string) config('app.env');
        $debugOnLive = config('app.debug') && $env === 'production';

        return $this->result(
            $debugOnLive ? self::BAD : self::OK,
            $debugOnLive ? 'Debug mode is ON in production. Error pages can show passwords and file paths to visitors.' : 'Running normally.',
            [
                'Environment' => $env,
                'Debug mode' => config('app.debug') ? 'on' : 'off',
                'Laravel version' => app()->version(),
                'Address' => config('app.url'),
                'Time zone' => config('app.timezone').' (server time now '.now()->format('d M Y H:i').')',
                'Maintenance mode' => app()->isDownForMaintenance() ? 'ON, visitors see the maintenance page' : 'off',
            ],
        );
    }

    private function database(): array
    {
        $connection = DB::connection();
        $started = microtime(true);
        $connection->select('select 1');
        $ms = round((microtime(true) - $started) * 1000, 1);
        $version = $connection->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);

        return $this->result(
            $ms > 500 ? self::WARN : self::OK,
            $ms > 500 ? "Connected, but slow ({$ms} ms to answer a simple question)." : "Connected ({$ms} ms to answer a simple question).",
            ['Type' => $connection->getDriverName(), 'Version' => $version, 'Database' => $connection->getDatabaseName(), 'Answer time' => "{$ms} ms"],
        );
    }

    private function cache(): array
    {
        $key = 'platform-health-probe-'.Str::random(8);
        Cache::put($key, 'ok', 30);
        $works = Cache::get($key) === 'ok';
        Cache::forget($key);

        return $this->result(
            $works ? self::OK : self::BAD,
            $works ? 'Saving and reading back works.' : 'Saved a value but could not read it back. Pages that rely on the cache may misbehave.',
            ['Driver' => config('cache.default')],
        );
    }

    private function queue(): array
    {
        $driver = (string) config('queue.default');
        $facts = ['Driver' => $driver];

        if ($driver === 'sync') {
            return $this->result(self::OK, 'Jobs run inside the page request (no background worker is needed or used).', $facts, [
                'Slow work such as message blasts runs while the person waits. To move it to the background, set QUEUE_CONNECTION=database and run a queue worker.',
            ]);
        }

        if ($driver !== 'database') {
            return $this->result(self::UNKNOWN, "Queue driver \"$driver\" is not inspected here.", $facts);
        }

        $table = config('queue.connections.database.table', 'jobs');
        $waiting = DB::table($table)->count();
        $oldest = DB::table($table)->min('available_at');
        $oldestAge = $oldest ? max(0, now()->timestamp - (int) $oldest) : 0;
        $failed = DB::table(config('queue.failed.table', 'failed_jobs'))->count();
        $facts += ['Waiting jobs' => $waiting, 'Oldest waiting' => $waiting ? $this->ago($oldestAge) : '—', 'Failed jobs' => $failed];

        if ($waiting && $oldestAge > 600) {
            return $this->result(self::BAD, 'Jobs have been waiting over 10 minutes. The queue worker is probably not running.', $facts);
        }
        if ($failed) {
            return $this->result(self::WARN, "$failed job(s) have failed.", $facts);
        }

        return $this->result(self::OK, $waiting ? "$waiting job(s) waiting, none stuck." : 'Nothing waiting, nothing failed.', $facts);
    }

    private function storage(): array
    {
        $facts = [];
        $bad = [];
        foreach (['local', 'public', 'private'] as $disk) {
            if (! config("filesystems.disks.$disk")) {
                continue;
            }
            try {
                $file = '.health-probe-'.Str::random(8);
                $storage = Storage::disk($disk);
                $storage->put($file, 'ok');
                $works = $storage->get($file) === 'ok';
                $storage->delete($file);
                $facts["Disk \"$disk\""] = $works ? 'can write and read' : 'wrote but could not read back';
                $works || $bad[] = $disk;
            } catch (\Throwable $e) {
                $facts["Disk \"$disk\""] = 'cannot write: '.Str::limit($e->getMessage(), 100);
                $bad[] = $disk;
            }
        }
        $facts['Default disk'] = config('filesystems.default');
        $facts['Online storage (Supabase)'] = config('filesystems.disks.supabase.bucket') ? 'set up (not tested here)' : 'not set up';
        $facts['Public link (storage:link)'] = is_link(public_path('storage')) || is_dir(public_path('storage')) ? 'in place' : 'missing: uploaded logos and pictures will not show';

        $missingLink = ! (is_link(public_path('storage')) || is_dir(public_path('storage')));

        return $this->result(
            $bad ? self::BAD : ($missingLink ? self::WARN : self::OK),
            $bad ? 'Cannot write to: '.implode(', ', $bad).'. Uploads to those places will fail.' : ($missingLink ? 'Files work, but the public link for logos is missing.' : 'Every local disk can save and read files.'),
            $facts,
        );
    }

    private function disk(): array
    {
        $path = base_path();
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);
        if (! $free || ! $total) {
            return $this->result(self::UNKNOWN, 'This server does not let the app read its disk space.');
        }
        $pct = round($free / $total * 100, 1);
        $facts = ['Free' => $this->bytes($free), 'Total' => $this->bytes($total), 'Free share' => "$pct%"];
        $status = $pct < 5 ? self::BAD : ($pct < 15 ? self::WARN : self::OK);

        return $this->result($status, $status === self::OK ? "$pct% of the disk is free." : "Only $pct% of the disk is free. Uploads and backups may soon fail.", $facts, [
            'On shared hosting this can be the whole server disk, not your own allowance. Check cPanel for your own limit.',
        ]);
    }

    private function deploy(): array
    {
        $git = base_path('.git');
        if (! is_dir($git)) {
            return $this->result(self::UNKNOWN, 'This copy of the code has no git history, so the deploy cannot be read.');
        }

        $sha = $this->git(['rev-parse', 'HEAD']) ?? $this->headFromFiles($git);
        $subject = $this->git(['log', '-1', '--format=%s']);
        $committed = $this->git(['log', '-1', '--format=%ct']);
        $branch = $this->git(['rev-parse', '--abbrev-ref', 'HEAD']) ?? $this->branchFromFiles($git);
        $moved = $this->lastHeadMove($git);

        $facts = [
            'Version' => $sha ? substr($sha, 0, 8) : null,
            'Branch' => $branch,
            'Last change' => $subject,
            'Written on' => $committed ? now()->setTimestamp((int) $committed)->format('d M Y H:i') : null,
            'Code last updated on this server' => $moved ? now()->setTimestamp($moved)->format('d M Y H:i').' ('.$this->ago(now()->timestamp - $moved).' ago)' : null,
        ];

        return $this->result($sha ? self::OK : self::UNKNOWN, $sha ? 'Running version '.substr($sha, 0, 8).($branch ? " of $branch" : '').'.' : 'Could not read the current version.', $facts);
    }

    /** Run a read-only git command. Shared hosting often blocks running programs, so a failure just means "no answer". */
    private function git(array $args): ?string
    {
        try {
            $process = new Process(['git', ...$args], base_path(), null, null, 5);
            $process->run();

            return $process->isSuccessful() ? (trim($process->getOutput()) ?: null) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function headFromFiles(string $git): ?string
    {
        $head = @file_get_contents("$git/HEAD");
        if ($head === false) {
            return null;
        }
        $head = trim($head);
        if (! str_starts_with($head, 'ref: ')) {
            return preg_match('/^[0-9a-f]{40}$/', $head) ? $head : null;
        }
        $ref = substr($head, 5);
        $sha = @file_get_contents("$git/$ref");
        if ($sha !== false && preg_match('/^[0-9a-f]{40}$/', trim($sha))) {
            return trim($sha);
        }
        foreach (@file("$git/packed-refs", FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (str_ends_with($line, " $ref")) {
                return substr($line, 0, 40);
            }
        }

        return null;
    }

    private function branchFromFiles(string $git): ?string
    {
        $head = trim((string) @file_get_contents("$git/HEAD"));

        return str_starts_with($head, 'ref: refs/heads/') ? substr($head, 16) : null;
    }

    /** When the checked-out code last moved (the deploy's "git reset"), from git's own log of HEAD */
    private function lastHeadMove(string $git): ?int
    {
        $lines = @file("$git/logs/HEAD", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! $lines) {
            return null;
        }

        return preg_match('/> (\d{9,11}) [+-]\d{4}\t/', end($lines), $m) ? (int) $m[1] : null;
    }

    private function migrations(): array
    {
        $migrator = app('migrator');
        $files = array_keys($migrator->getMigrationFiles($migrator->paths() + [database_path('migrations')]));
        $repository = $migrator->getRepository();

        if (! $repository->repositoryExists()) {
            return $this->result(self::BAD, 'The database has never been set up (no migrations table).', ['Update files in the code' => count($files)]);
        }
        $ran = $repository->getRan();
        $pending = array_values(array_diff($files, $ran));

        return $this->result(
            $pending ? self::WARN : self::OK,
            $pending ? count($pending).' database update(s) have not been applied yet. A deploy applies them by itself.' : 'The database is fully up to date.',
            ['Applied' => count($ran), 'Waiting to apply' => count($pending)],
            array_map(fn ($name) => "Waiting: $name", array_slice($pending, 0, 10)),
        );
    }

    private function php(): array
    {
        $missing = [];
        $facts = [];
        foreach (self::EXTENSIONS as $ext => $why) {
            if (! extension_loaded($ext)) {
                $missing[$ext] = $why;
            }
        }
        $facts['Version'] = PHP_VERSION;
        $facts['Memory per request'] = ini_get('memory_limit');
        $facts['Longest request'] = ini_get('max_execution_time') === '0' ? 'no limit' : ini_get('max_execution_time').' seconds';
        $facts['Biggest upload'] = ini_get('upload_max_filesize');
        $facts['Biggest form post'] = ini_get('post_max_size');
        $facts['Missing extensions'] = $missing ? implode(', ', array_keys($missing)) : 'none';

        $old = version_compare(PHP_VERSION, '8.4.0', '<');
        $notes = $old ? ['The project needs PHP 8.4 or newer. Some packages will fail on this version.'] : [];
        foreach ($missing as $ext => $why) {
            $notes[] = "Missing \"$ext\", needed for $why.";
        }

        $status = $missing || $old ? self::BAD : self::OK;

        return $this->result($status, $status === self::OK ? 'PHP '.PHP_VERSION.' with everything the app needs.' : 'PHP '.PHP_VERSION.': something the app needs is missing.', $facts, $notes);
    }

    private function bytes(float|int $b): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = $b > 0 ? min(count($units) - 1, (int) floor(log($b, 1024))) : 0;

        return round($b / 1024 ** $i, 1).' '.$units[$i];
    }

    private function ago(int $seconds): string
    {
        return match (true) {
            $seconds < 90 => $seconds.' seconds',
            $seconds < 5400 => round($seconds / 60).' minutes',
            $seconds < 129600 => round($seconds / 3600).' hours',
            default => round($seconds / 86400).' days',
        };
    }
}
