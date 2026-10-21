<?php

namespace App\Support;

/**
 * The settings the platform owner may look at, in plain groups. Only the names listed here are ever shown, and a secret
 * is never shown in full: only whether it is set and its last four characters. Reads config (not env()), so it works
 * when the settings are cached, as they are on the live server.
 */
class EnvironmentView
{
    /** [label, env name, config path, secret? (true shows the last four characters, 'hidden' shows nothing)] */
    private const GROUPS = [
        'The app' => [
            ['Name', 'APP_NAME', 'app.name'], ['Environment', 'APP_ENV', 'app.env'], ['Debug mode', 'APP_DEBUG', 'app.debug'],
            ['Address', 'APP_URL', 'app.url'], ['Time zone', 'APP_TIMEZONE', 'app.timezone'], ['App key (encrypts data)', 'APP_KEY', 'app.key', 'hidden'],
            ['Public demo', 'DEMO_ENABLED', 'app.demo_enabled'],
        ],
        'Database' => [
            ['Type', 'DB_CONNECTION', 'database.default'], ['Host', 'DB_HOST', 'database.connections.mysql.host'], ['Database name', 'DB_DATABASE', 'database.connections.mysql.database'],
            ['User', 'DB_USERNAME', 'database.connections.mysql.username'], ['Password', 'DB_PASSWORD', 'database.connections.mysql.password', true],
        ],
        'Speed and background work' => [
            ['Cache', 'CACHE_STORE', 'cache.default'], ['Queue', 'QUEUE_CONNECTION', 'queue.default'], ['Sessions', 'SESSION_DRIVER', 'session.driver'],
            ['Session length (minutes)', 'SESSION_LIFETIME', 'session.lifetime'], ['Log channel', 'LOG_CHANNEL', 'logging.default'], ['Log level', 'LOG_LEVEL', 'logging.channels.single.level'],
        ],
        'Email' => [
            ['Mailer', 'MAIL_MAILER', 'mail.default'], ['Host', 'MAIL_HOST', 'mail.mailers.smtp.host'], ['Port', 'MAIL_PORT', 'mail.mailers.smtp.port'],
            ['User', 'MAIL_USERNAME', 'mail.mailers.smtp.username'], ['Password', 'MAIL_PASSWORD', 'mail.mailers.smtp.password', true],
            ['Sent from', 'MAIL_FROM_ADDRESS', 'mail.from.address'],
        ],
        'Files' => [
            ['Default disk', 'FILESYSTEM_DISK', 'filesystems.default'], ['Supabase bucket', 'SUPABASE_STORAGE_BUCKET', 'filesystems.disks.supabase.bucket'],
            ['Supabase storage key', 'SUPABASE_STORAGE_ACCESS_KEY_ID', 'filesystems.disks.supabase.key', true], ['Supabase storage secret', 'SUPABASE_STORAGE_SECRET_ACCESS_KEY', 'filesystems.disks.supabase.secret', true],
        ],
        'Supabase login' => [
            ['Project address', 'SUPABASE_URL', 'services.supabase.url'], ['Anon key', 'SUPABASE_ANON_KEY', 'services.supabase.anon_key', true], ['Service role key', 'SUPABASE_SERVICE_ROLE_KEY', 'services.supabase.service_role_key', true],
        ],
        'Hosting and domains (optional)' => [
            ['cPanel host', 'CPANEL_HOST', 'services.cpanel.host'], ['cPanel user', 'CPANEL_USERNAME', 'services.cpanel.username'], ['cPanel API token', 'CPANEL_API_TOKEN', 'services.cpanel.token', true],
            ['Spaceship key', 'SPACESHIP_API_KEY', 'services.spaceship.key', true], ['Spaceship secret', 'SPACESHIP_API_SECRET', 'services.spaceship.secret', true],
        ],
        'Developer tool switches' => [],
    ];

    /** @return array<int, array{title:string, rows: array<int, array{label:string, env:string, value:string, state:string}>}> */
    public static function groups(): array
    {
        $out = [];
        foreach (self::GROUPS as $title => $rows) {
            if ($title === 'Developer tool switches') {
                $rows = collect(array_keys(array_filter(config('platform_tools'), 'is_bool')))
                    ->map(fn ($tool) => [ucfirst(str_replace('_', ' ', $tool)), 'PLATFORM_TOOL_'.strtoupper($tool), "platform_tools.$tool"])->all();
                $rows[] = ['Commands may change data', 'PLATFORM_COMMANDS_CHANGE_DATA', 'platform_tools.commands_change_data'];
                $rows[] = ['Error tracker address', 'PLATFORM_ERROR_TRACKER_URL', 'platform_tools.error_tracker_url'];
            }
            $out[] = ['title' => $title, 'rows' => array_map(fn ($r) => self::row($r[0], $r[1], $r[2], $r[3] ?? false), $rows)];
        }

        return $out;
    }

    private static function row(string $label, string $env, string $path, bool|string $secret): array
    {
        $raw = config($path);
        $text = is_bool($raw) ? ($raw ? 'on' : 'off') : (is_scalar($raw) ? (string) $raw : '');
        $state = 'set';

        if ($text === '') {
            $state = 'empty';
            $shown = 'not set';
        } elseif ($secret) {
            $shown = '•••• set (ends in '.substr($text, -4).')';
            // very short secrets would be nearly fully revealed by the last four, so show nothing of them
            if ($secret === 'hidden' || strlen($text) < 12) {
                $shown = '•••• set';
            }
        } else {
            $shown = mb_substr($text, 0, 200);
        }

        if ($text !== '' && (str_contains($text, 'your-') || str_contains($text, 'example.com') || str_contains($text, 'hello@example'))) {
            $state = 'example';
        }

        return ['label' => $label, 'env' => $env, 'value' => $shown, 'state' => $state];
    }
}
