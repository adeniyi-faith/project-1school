<?php

namespace App\Services;

use App\Ai\AiFailure;
use App\Models\PlatformSetting;
use App\Models\SchoolSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * The outside services the app depends on, whether each is set up, and a "test connection" for each.
 * A test only asks the service a harmless question, never changes anything there, and the answer shown is
 * our own plain wording (never the service's raw reply, which could echo a key).
 */
class IntegrationChecker
{
    private const TIMEOUT = 8;

    /** Payment providers are set per school; the test only checks the provider's website can be reached from this server */
    private const PAYMENT_HOSTS = [
        'paystack' => ['Paystack', 'https://api.paystack.co'],
        'flutterwave' => ['Flutterwave', 'https://api.flutterwave.com'],
        'monnify' => ['Monnify', 'https://api.monnify.com'],
        'remita' => ['Remita', 'https://login.remita.net'],
        'interswitch' => ['Interswitch', 'https://webpay.interswitchng.com'],
    ];

    public function __construct(private AiService $ai) {}

    /** @return array<string, array{label:string, about:string}> */
    public function catalogue(): array
    {
        $list = [
            'supabase-login' => ['label' => 'Supabase login', 'about' => 'Checks every password at sign-in'],
            'supabase-storage' => ['label' => 'Supabase file storage', 'about' => 'Online home for uploaded files, when set up'],
            'gemini' => ['label' => 'Google Gemini (AI)', 'about' => 'Writes comments, lesson notes and questions'],
            'mail' => ['label' => 'Email', 'about' => 'Sends the platform\'s emails and school message blasts'],
            'sms' => ['label' => 'SMS', 'about' => 'Texts to parents and staff'],
        ];
        foreach (self::PAYMENT_HOSTS as $key => [$label]) {
            $list["pay-$key"] = ['label' => $label, 'about' => 'Online fee payments (each school connects its own account)'];
        }
        $list['cpanel'] = ['label' => 'cPanel', 'about' => 'The hosting control panel (domains and mailboxes, later)'];
        $list['spaceship'] = ['label' => 'Spaceship', 'about' => 'Domain registrar (buying domains, later)'];

        return $list;
    }

    /** What the page shows: set-up state and the last remembered test, without running any test */
    public function board(): array
    {
        $memory = $this->memory();

        return collect($this->catalogue())->map(function ($c, $key) use ($memory) {
            [$state, $detail, $testable] = $this->state($key);
            $m = $memory[$key] ?? [];

            return ['key' => $key] + $c + [
                'state' => $state, 'detail' => $detail, 'testable' => $testable,
                'last_ok' => $m['last_ok_at'] ?? null, 'last_tested' => $m['at'] ?? null, 'last_result' => $m['ok'] ?? null, 'last_message' => $m['message'] ?? null,
            ];
        })->values()->all();
    }

    /** Run one test now, remember the answer, and return it */
    public function test(string $key): array
    {
        abort_unless(isset($this->catalogue()[$key]), 404);

        $started = microtime(true);
        try {
            [$ok, $message] = $this->probe($key);
        } catch (\Throwable $e) {
            [$ok, $message] = [false, 'The test could not run: '.class_basename($e).'.'];
        }
        $ms = (int) round((microtime(true) - $started) * 1000);

        $memory = $this->memory();
        $prev = $memory[$key] ?? [];
        $memory[$key] = ['ok' => $ok, 'message' => $message, 'ms' => $ms, 'at' => now()->toIso8601String(), 'last_ok_at' => $ok ? now()->toIso8601String() : ($prev['last_ok_at'] ?? null)];
        PlatformSetting::set('integration_tests', json_encode($memory), 'integrations');

        return ['ok' => $ok, 'message' => $message, 'ms' => $ms];
    }

    private function memory(): array
    {
        return json_decode((string) PlatformSetting::get('integration_tests', '{}'), true) ?: [];
    }

    /** @return array{0: string, 1: string, 2: bool} state (ready|missing|warn), a plain sentence, whether a test exists */
    private function state(string $key): array
    {
        $placeholder = fn (?string $v) => ! $v || str_contains($v, 'your-') || str_contains($v, 'example');

        switch ($key) {
            case 'supabase-login':
                $ok = ! $placeholder(config('services.supabase.url')) && ! $placeholder(config('services.supabase.anon_key'));

                return $ok ? ['ready', 'Address and keys are set.', true] : ['missing', 'The address or keys are missing or still the example values. Nobody can sign in.', false];
            case 'supabase-storage':
                return config('filesystems.disks.supabase.bucket') ? ['ready', 'A storage bucket is set.', true] : ['missing', 'Not set up. Files are kept on this server\'s own disk.', false];
            case 'gemini':
                $enabled = (bool) ($this->ai->settings()['enabled'] ?? false);

                return $this->ai->key() ? ['ready', $enabled ? 'A key is saved and AI is on.' : 'A key is saved but AI is switched off.', true] : ['missing', 'No key saved yet (Super Admin > AI).', false];
            case 'mail':
                $m = (string) config('mail.default');

                return in_array($m, ['log', 'array'], true) ? ['warn', "Mail is set to \"$m\": emails are written to a log file and never sent.", false] : ['ready', "Sending through \"$m\".", $m === 'smtp'];
            case 'sms':
                return ['missing', 'No SMS provider is connected yet. Every text is marked failed.', false];
            case 'cpanel':
                $set = config('services.cpanel.host') && config('services.cpanel.username') && config('services.cpanel.token');

                return $set ? ['ready', 'Host, user name and API token are set.', true] : ['missing', 'Not connected. Set CPANEL_HOST, CPANEL_USERNAME and CPANEL_API_TOKEN to enable.', false];
            case 'spaceship':
                $set = config('services.spaceship.key') && config('services.spaceship.secret');

                return $set ? ['ready', 'API key and secret are set.', true] : ['missing', 'Not connected. Set SPACESHIP_API_KEY and SPACESHIP_API_SECRET to enable.', false];
        }

        $provider = substr($key, 4);
        $schools = $this->schoolsUsing($provider);

        return [$schools ? 'ready' : 'missing', $schools ? "$schools school(s) take payments through it." : 'No school has chosen it yet.', true];
    }

    private function schoolsUsing(string $provider): int
    {
        return SchoolSetting::query()->where('key', 'payment_provider')->where('value', $provider)
            ->whereIn('school_id', SchoolSetting::query()->where('key', 'payment_enabled')->where('value', '1')->select('school_id'))
            ->count();
    }

    /** @return array{0: bool, 1: string} */
    private function probe(string $key): array
    {
        switch ($key) {
            case 'supabase-login':
                $r = Http::timeout(self::TIMEOUT)->withHeaders(['apikey' => (string) config('services.supabase.anon_key')])->get(rtrim((string) config('services.supabase.url'), '/').'/auth/v1/settings');

                return $r->successful() ? [true, 'Supabase answered and accepted the key.'] : [false, in_array($r->status(), [401, 403], true) ? 'Supabase answered but refused the key (check SUPABASE_ANON_KEY).' : 'Supabase answered with an error (code '.$r->status().').'];
            case 'supabase-storage':
                Storage::disk('supabase')->exists('platform-connection-test');

                return [true, 'The storage bucket answered.'];
            case 'gemini':
                $key = $this->ai->key() ?? throw new \RuntimeException('no key');
                try {
                    $this->ai->check($key, $this->ai->settings()['models']['quick']);
                } catch (AiFailure $e) {
                    return [false, 'Google did not accept the request: '.mb_substr(preg_replace('/AIza[\w-]+/', '[key]', $e->getMessage()), 0, 160)];
                }

                return [true, 'Google answered and the key works.'];
            case 'mail':
                $host = (string) config('mail.mailers.smtp.host');
                $port = (int) config('mail.mailers.smtp.port');
                $socket = @fsockopen($host, $port, $errno, $err, 6);
                if (! $socket) {
                    return [false, "Could not reach the mail server $host on port $port."];
                }
                fclose($socket);

                return [true, "Reached the mail server $host on port $port. (No email was sent.)"];
            case 'cpanel':
                $host = preg_replace('#^https?://#', '', rtrim((string) config('services.cpanel.host'), '/'));
                $r = Http::timeout(self::TIMEOUT)->withHeaders(['Authorization' => 'cpanel '.config('services.cpanel.username').':'.config('services.cpanel.token')])
                    ->get("https://$host:2083/execute/Variables/get_user_information");

                return $r->successful() && $r->json('status') === 1 ? [true, 'cPanel answered and accepted the token.'] : [false, 'cPanel did not accept the request (code '.$r->status().').'];
            case 'spaceship':
                $r = Http::timeout(self::TIMEOUT)->withHeaders(['X-Api-Key' => (string) config('services.spaceship.key'), 'X-Api-Secret' => (string) config('services.spaceship.secret')])
                    ->get('https://spaceship.dev/api/v1/domains', ['take' => 1, 'skip' => 0]);

                return $r->successful() ? [true, 'Spaceship answered and accepted the key.'] : [false, 'Spaceship did not accept the request (code '.$r->status().').'];
        }

        // Payment providers: any answer from their website means we can reach them
        [$label, $url] = self::PAYMENT_HOSTS[substr($key, 4)];
        $r = Http::timeout(self::TIMEOUT)->get($url);

        return $r->serverError() && $r->status() >= 502 ? [false, "$label's website answered with an error (code {$r->status()})."] : [true, "$label's website can be reached from this server. (Keys belong to each school and are not tested here.)"];
    }
}
