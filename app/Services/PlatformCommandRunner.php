<?php

namespace App\Services;

use App\Models\PlatformCommandRun;
use App\Models\User;
use App\Support\PlatformAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * The only commands the platform owner can run from the browser. Nothing is typed in: each one is a fixed command
 * with fixed options, listed here, so the page can never be used to run anything else.
 */
class PlatformCommandRunner
{
    private const MAX_OUTPUT = 20000;

    /**
     * changes = false means it only looks. A command that changes something needs a written reason.
     *
     * @return array<string, array{label:string, what:string, command:string, options:array, changes:bool, note:?string}>
     */
    public static function catalogue(): array
    {
        return [
            'clear-caches' => [
                'label' => 'Clear caches', 'command' => 'optimize:clear', 'options' => [], 'changes' => true,
                'what' => 'Forgets saved copies of settings, routes, pages and cached data so the app reads everything fresh. Nobody is signed out and no school data is touched.',
                'note' => 'The site may feel slightly slower for a minute while it rebuilds.',
            ],
            'reseed-roles' => [
                'label' => 'Re-create roles and permissions', 'command' => 'db:seed', 'options' => ['--class' => 'RolePermissionSeeder', '--force' => true], 'changes' => true,
                'what' => 'Makes sure every role and permission the app knows about exists. Run it after a release that added a permission and a role cannot see a new page. A deploy already does this.',
                'note' => 'Safe to repeat. It adds what is missing; it does not remove roles or people.',
            ],
            'refill-demo' => [
                'label' => 'Refresh the demo school', 'command' => 'demo:fill', 'options' => [], 'changes' => true,
                'what' => 'Tops up the public demo school with sample data. If the demo already has students it only refreshes the extras (hostels, buses, lessons, tests and so on).',
                'note' => 'Does nothing unless the demo is turned on. It never wipes the demo.',
            ],
            'fees-check' => [
                'label' => 'Check old fee payments (look only)', 'command' => 'fees:copy-old-payments', 'options' => ['--dry-run' => true], 'changes' => false,
                'what' => 'Shows how many old fee payments would be copied into invoices, without copying anything.',
                'note' => null,
            ],
            'migration-status' => [
                'label' => 'Show database updates (look only)', 'command' => 'migrate:status', 'options' => [], 'changes' => false,
                'what' => 'Lists every database update and whether it has been applied.',
                'note' => null,
            ],
        ];
    }

    /** What the page lists: everything in the catalogue, marked as usable or not in this environment */
    public static function available(): array
    {
        $canChange = (bool) config('platform_tools.commands_change_data');

        return collect(self::catalogue())->map(fn ($c, $key) => ['key' => $key] + collect($c)->only(['label', 'what', 'changes', 'note'])->all() + ['enabled' => ! $c['changes'] || $canChange])->values()->all();
    }

    public function run(string $key, ?string $reason, Request $request): PlatformCommandRun
    {
        $c = self::catalogue()[$key] ?? abort(404);

        // One at a time per command, so a double click cannot run it twice
        $lock = Cache::lock("platform-command-$key", 300);
        if (! $lock->get()) {
            throw new \RuntimeException('This command is already running. Wait for it to finish.');
        }

        $started = microtime(true);
        $output = new BufferedOutput();
        try {
            @set_time_limit(120);
            $code = Artisan::call($c['command'], $c['options'], $output);
            $status = $code === 0 ? 'ok' : 'failed';
            $text = $output->fetch();
        } catch (\Throwable $e) {
            $status = 'failed';
            $text = trim($output->fetch()."\n".$e->getMessage());
        } finally {
            $lock->release();
        }

        $text = trim(preg_replace('/\e\[[\d;]*m/', '', $text)); // colour codes
        $text = mb_strlen($text) > self::MAX_OUTPUT ? mb_substr($text, 0, self::MAX_OUTPUT)."\n… (cut: too long to keep)" : $text;

        $run = PlatformCommandRun::create([
            'command' => $key, 'user_id' => $request->user()?->id, 'reason' => $reason, 'status' => $status,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000), 'output' => $text !== '' ? $text : null, 'ip' => $request->ip(),
        ]);
        PlatformAudit::record("ran command: {$c['label']}", ['command' => $key, 'status' => $status], $reason, $request);

        return $run;
    }

    public static function needsReason(string $key): bool
    {
        return (self::catalogue()[$key]['changes'] ?? true) === true;
    }
}
