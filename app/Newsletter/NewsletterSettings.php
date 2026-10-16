<?php

namespace App\Newsletter;

use App\Models\PlatformSetting;
use App\Models\School;
use App\Models\SchoolSetting;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Where the newsletter's sending settings live. Both the school's and the platform's are stored as one encrypted
 * block, so a service key is never readable in the database, and `masked()` is all a screen ever gets back.
 */
class NewsletterSettings
{
    public const SCHOOL = 'newsletter_settings';
    public const PLATFORM = 'newsletter_platform';

    public const SCHOOL_DEFAULTS = [
        'mode' => 'platform', 'provider' => 'brevo', 'config' => [], 'from_name' => '', 'from_email' => '', 'reply_to' => '',
        'daily_limit' => '', 'header_text' => '', 'footer_text' => '', 'colour' => '',
    ];

    /** `access`: who may use the platform's service. mode 'all', or 'selected' = the listed schools and the schools on a listed plan */
    public const PLATFORM_DEFAULTS = ['provider' => 'mailbox', 'from_email' => '', 'brevo_key' => '', 'daily_limit' => '', 'access' => ['mode' => 'all', 'plans' => [], 'schools' => []]];

    private static function read(?string $raw, array $defaults): array
    {
        if (! $raw) {
            return $defaults;
        }
        try {
            $data = json_decode(Crypt::decryptString($raw), true);
        } catch (Throwable) {
            return $defaults;
        }

        return is_array($data) ? array_replace($defaults, $data) : $defaults;
    }

    public static function school(int $schoolId): array
    {
        return self::read(SchoolSetting::get($schoolId, self::SCHOOL), self::SCHOOL_DEFAULTS);
    }

    public static function saveSchool(int $schoolId, array $data): void
    {
        SchoolSetting::set($schoolId, self::SCHOOL, Crypt::encryptString(json_encode($data)), 'newsletter');
    }

    public static function platform(): array
    {
        return self::read(PlatformSetting::get(self::PLATFORM), self::PLATFORM_DEFAULTS);
    }

    public static function savePlatform(array $data): void
    {
        PlatformSetting::set(self::PLATFORM, Crypt::encryptString(json_encode($data)), 'newsletter');
    }

    /** What a screen may show of a secret: whether it is set and its last four characters, never the whole thing */
    public static function mask(?string $secret): ?string
    {
        $secret = (string) $secret;

        return $secret === '' ? null : '••••'.mb_substr($secret, -4);
    }

    /** May this school send through the platform's service? Decided by the platform owner: everyone, chosen schools, or chosen plans. */
    public static function platformAllowed(int $schoolId): bool
    {
        $a = array_replace(self::PLATFORM_DEFAULTS['access'], (array) (self::platform()['access'] ?? []));
        if ($a['mode'] !== 'selected') {
            return true;
        }
        if (in_array($schoolId, array_map('intval', (array) $a['schools']), true)) {
            return true;
        }
        $package = \App\Website\Extensions::packageId($schoolId);

        return $package !== null && in_array($package, array_map('intval', (array) $a['plans']), true);
    }

    /** The default address mail goes out from when the platform owner has not set one: the system's own mailbox address */
    public static function platformFromEmail(array $platform): string
    {
        return trim((string) ($platform['from_email'] ?: config('mail.from.address')));
    }

    public static function defaultLimit(string $provider): int
    {
        return $provider === 'brevo' ? 300 : 100;
    }

    /** True when every box the provider asks for has an answer */
    public static function complete(string $class, array $config): bool
    {
        foreach ($class::fields() as $f) {
            if (trim((string) ($config[$f['key']] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }

    /** The sender this school will use now, or null when nothing usable is set up */
    public static function resolve(int $schoolId): ?Sender
    {
        $school = School::withoutGlobalScopes()->find($schoolId);
        if (! $school) {
            return null;
        }
        $s = self::school($schoolId);
        $fromName = trim((string) $s['from_name']) ?: $school->name;
        $replyTo = filter_var($s['reply_to'], FILTER_VALIDATE_EMAIL) ?: (filter_var($school->email, FILTER_VALIDATE_EMAIL) ?: null);

        if ($s['mode'] === 'own') {
            $provider = Providers::make((string) $s['provider']);
            if (! $provider || $provider instanceof MailboxProvider) {
                return null;
            }
            $config = (array) ($s['config'][$provider::key()] ?? []);
            if (! self::complete($provider::class, $config) || ! filter_var($s['from_email'], FILTER_VALIDATE_EMAIL)) {
                return null;
            }

            return new Sender($provider, $config, $s['from_email'], $fromName, $replyTo, (int) $s['daily_limit'] ?: self::defaultLimit($provider::key()), 'own');
        }

        if (! self::platformAllowed($schoolId)) {
            return null;
        }
        $p = self::platform();
        $provider = Providers::make((string) $p['provider']);
        $from = self::platformFromEmail($p);
        if (! $provider || ! filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        $config = $provider instanceof BrevoProvider ? ['api_key' => (string) $p['brevo_key']] : [];
        if ($provider instanceof BrevoProvider && $config['api_key'] === '') {
            return null;
        }

        return new Sender($provider, $config, $from, $fromName, $replyTo, (int) $p['daily_limit'] ?: self::defaultLimit($provider::key()), 'platform');
    }
}
