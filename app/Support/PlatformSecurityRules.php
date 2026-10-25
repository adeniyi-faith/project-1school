<?php

namespace App\Support;

use App\Models\PlatformSecurity;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Everything about how platform people prove who they are: the second sign-in step, the list of networks they may
 * sign in from, forced password changes and the list of open sessions. The owner follows the two-step rule too, but
 * is never held back by the network list (so a typo there can never lock the owner out).
 */
class PlatformSecurityRules
{
    public const ALLOWLIST_KEY = 'platform_ip_allowlist';

    public static function twoFactorRequired(): bool
    {
        return (bool) config('platform.require_2fa', true);
    }

    public static function security(User $user): PlatformSecurity
    {
        return PlatformSecurity::for($user);
    }

    public static function isEnrolled(User $user): bool
    {
        return PlatformSecurity::where('user_id', $user->id)->whereNotNull('totp_confirmed_at')->exists();
    }

    public static function mustChangePassword(User $user): bool
    {
        return (bool) PlatformSecurity::where('user_id', $user->id)->value('must_change_password');
    }

    public static function forcePasswordChange(User $user, bool $on = true): void
    {
        PlatformSecurity::for($user)->update(['must_change_password' => $on]);
    }

    // ----- allowed networks -------------------------------------------------------------------------------------

    /** The lines the owner typed: single addresses ("41.58.10.5") or ranges ("41.58.0.0/16"), one per line */
    public static function allowList(): array
    {
        $text = (string) PlatformSetting::get(self::ALLOWLIST_KEY, '');

        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $text) ?: [])));
    }

    /** Returns the lines that are not a real address or range (empty when all are fine) */
    public static function badLines(array $lines): array
    {
        return array_values(array_filter($lines, fn ($l) => ! self::validEntry($l)));
    }

    public static function validEntry(string $entry): bool
    {
        [$ip, $bits] = array_pad(explode('/', $entry, 2), 2, null);
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        if ($bits === null) {
            return true;
        }
        $max = str_contains($ip, ':') ? 128 : 32;

        return ctype_digit($bits) && (int) $bits <= $max;
    }

    public static function ipMatches(string $ip, string $entry): bool
    {
        [$net, $bits] = array_pad(explode('/', $entry, 2), 2, null);
        $a = @inet_pton($ip);
        $b = @inet_pton($net);
        if ($a === false || $b === false || strlen($a) !== strlen($b)) {
            return false;
        }
        if ($bits === null) {
            return $a === $b;
        }
        $bits = (int) $bits;
        $full = intdiv($bits, 8);
        if (substr($a, 0, $full) !== substr($b, 0, $full)) {
            return false;
        }
        $rest = $bits % 8;
        if ($rest === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $rest)) & 0xFF;

        return (ord($a[$full]) & $mask) === (ord($b[$full]) & $mask);
    }

    /** May this person use the platform from this address? An empty list means "from anywhere". */
    public static function ipAllowed(User $user, ?string $ip): bool
    {
        if (PlatformAccess::isOwner($user)) {
            return true;
        }
        $list = self::allowList();
        if ($list === []) {
            return true;
        }
        foreach ($list as $entry) {
            if ($ip && self::ipMatches($ip, $entry)) {
                return true;
            }
        }

        return false;
    }

    // ----- recovery codes ---------------------------------------------------------------------------------------

    /** Makes 10 new one-time codes, keeps only scrambled copies, and returns the readable ones (shown once) */
    public static function makeRecoveryCodes(User $user): array
    {
        $codes = collect(range(1, 10))->map(fn () => self::chunk().'-'.self::chunk())->all();
        PlatformSecurity::for($user)->update(['recovery_codes' => array_map([self::class, 'scramble'], $codes)]);

        return $codes;
    }

    /** Five letters and numbers that cannot be mistaken for each other when copied by hand (no 0/o, 1/l/i) */
    private static function chunk(): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
        $out = '';
        for ($i = 0; $i < 5; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $out;
    }

    public static function recoveryLeft(User $user): int
    {
        return count(PlatformSecurity::where('user_id', $user->id)->first()?->recovery_codes ?? []);
    }

    /** Uses up one recovery code if it is right. Each works once. */
    public static function useRecoveryCode(User $user, string $input): bool
    {
        $input = strtolower(trim($input));
        if (! preg_match('/^[a-z0-9]{5}-[a-z0-9]{5}$/i', $input)) {
            return false;
        }

        return DB::transaction(function () use ($user, $input) {
            $row = PlatformSecurity::where('user_id', $user->id)->lockForUpdate()->first();
            $left = $row?->recovery_codes ?? [];
            $hash = self::scramble($input);
            $index = array_search($hash, $left, true);
            if ($index === false) {
                return false;
            }
            unset($left[$index]);
            $row->update(['recovery_codes' => array_values($left)]);

            return true;
        });
    }

    private static function scramble(string $code): string
    {
        return hash_hmac('sha256', strtolower($code), (string) config('app.key'));
    }

    /** Removes someone's second step (used by the owner, or the command line, when a phone is lost) */
    public static function resetTwoFactor(User $user): void
    {
        PlatformSecurity::where('user_id', $user->id)->update([
            'totp_secret' => null, 'totp_confirmed_at' => null, 'totp_last_step' => null, 'recovery_codes' => null,
        ]);
    }

    // ----- sessions ---------------------------------------------------------------------------------------------

    /** Sessions can be listed only when they are kept in the database */
    public static function sessionsListable(): bool
    {
        return config('session.driver') === 'database' && Schema::hasTable(config('session.table', 'sessions'));
    }

    /** @return array<int, array{id: string, device: string, ip: ?string, last_active: string, current: bool}> */
    public static function sessionsFor(User $user, ?string $currentId): array
    {
        if (! self::sessionsListable()) {
            return [];
        }

        return DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->orderByDesc('last_activity')->limit(50)->get()
            ->map(fn ($s) => [
                'id' => $s->id, 'device' => self::device($s->user_agent), 'ip' => $s->ip_address,
                'last_active' => date('c', (int) $s->last_activity), 'current' => $s->id === $currentId,
            ])->all();
    }

    /** Ends every session of a person, except the one named. Returns how many were ended. */
    public static function signOutEverywhere(User $user, ?string $exceptId = null): int
    {
        // A "remember me" cookie must stop working too
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        if (! self::sessionsListable()) {
            return 0;
        }

        return DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->delete();
    }

    public static function endSession(User $user, string $sessionId): bool
    {
        if (! self::sessionsListable()) {
            return false;
        }

        return DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->where('id', $sessionId)->delete() > 0;
    }

    public static function device(?string $ua): string
    {
        $ua = (string) $ua;
        $browser = match (true) {
            str_contains($ua, 'Edg') => 'Edge', str_contains($ua, 'OPR') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Firefox') => 'Firefox', str_contains($ua, 'Chrome') => 'Chrome', str_contains($ua, 'Safari') => 'Safari',
            default => 'Browser',
        };
        $os = match (true) {
            str_contains($ua, 'Android') => 'Android', str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iPhone/iPad',
            str_contains($ua, 'Windows') => 'Windows', str_contains($ua, 'Mac OS') => 'Mac', str_contains($ua, 'Linux') => 'Linux',
            default => '',
        };

        return trim($browser.($os ? " on {$os}" : ''));
    }
}
