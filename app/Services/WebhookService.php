<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolWebhook;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Tells a school's own system (a spreadsheet, a CRM, an automation tool) when something happens. Each message is
 * signed with the school's secret so the receiver can check it came from here. A slow or broken receiver never
 * stops the work that caused the message: the result is only recorded.
 */
class WebhookService
{
    /** Tests replace this to avoid real DNS lookups: fn (string $host): list<string> of IP addresses */
    public static $resolver = null;

    /** Only a public https address is accepted, so a school cannot aim us at a machine inside our own network */
    public static function urlProblem(string $url): ?string
    {
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['port']) && ! in_array((int) $parts['port'], [443, 8443], true)) {
            return 'Use a full https:// address.';
        }
        $ips = self::$resolver ? (self::$resolver)($parts['host']) : (filter_var($parts['host'], FILTER_VALIDATE_IP) ? [$parts['host']] : (gethostbynamel($parts['host']) ?: []));
        if ($ips === []) {
            return 'That address could not be found.';
        }
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return 'That address points to a private network, which is not allowed.';
            }
        }

        return null;
    }

    public function dispatch(int $schoolId, string $event, array $data): void
    {
        try {
            $hook = SchoolWebhook::withoutGlobalScopes()->where('school_id', $schoolId)->where('is_active', true)->first();
            if (! $hook || ! in_array($event, (array) $hook->events, true)) {
                return;
            }
            $this->send($hook, $event, $data);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** One delivery, also used by the "Send a test" button. Returns [ok, message]. */
    public function send(SchoolWebhook $hook, string $event, array $data): array
    {
        if ($problem = self::urlProblem($hook->url)) {
            $hook->forceFill(['last_status' => null, 'last_error' => $problem, 'last_sent_at' => now()])->save();

            return [false, $problem];
        }

        $school = School::find($hook->school_id);
        $body = json_encode(['event' => $event, 'sent_at' => now()->toIso8601String(), 'school' => ['id' => $school?->id, 'name' => $school?->name], 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = 'sha256='.hash_hmac('sha256', $body, $hook->secret);

        try {
            $response = Http::withoutRedirecting()->timeout(5)->withHeaders([
                'Content-Type' => 'application/json', 'X-SchoolRuns-Event' => $event, 'X-SchoolRuns-Signature' => $signature,
            ])->withBody($body, 'application/json')->post($hook->url);
            $ok = $response->successful();
            $hook->forceFill(['last_status' => $response->status(), 'last_error' => $ok ? null : 'The receiver answered '.$response->status().'.', 'last_sent_at' => now()])->save();

            return [$ok, $ok ? 'Delivered.' : 'The receiver answered '.$response->status().'.'];
        } catch (Throwable $e) {
            $hook->forceFill(['last_status' => null, 'last_error' => 'Could not reach the receiver.', 'last_sent_at' => now()])->save();

            return [false, 'Could not reach the receiver.'];
        }
    }
}
