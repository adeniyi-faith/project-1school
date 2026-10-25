<?php

namespace App\Support;

/**
 * Time-based one-time codes (RFC 6238), the kind Google Authenticator, Microsoft Authenticator and Authy make.
 * Written here so no outside package is needed: HMAC-SHA1, 30-second steps, 6 digits.
 */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function secret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function code(string $secret, int $step, int $digits = 6): string
    {
        $hash = hash_hmac('sha1', pack('J', $step), self::base32Decode($secret), true);
        $offset = ord($hash[19]) & 0xF;
        $value = ((ord($hash[$offset]) & 0x7F) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);

        return str_pad((string) ($value % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    public static function step(?int $time = null): int
    {
        return intdiv($time ?? time(), 30);
    }

    /**
     * Is this code right for now (allowing one step either side for clock drift)? Returns the step it matched, so the
     * caller can refuse the same step twice, or null when it is wrong or was already used.
     */
    public static function verify(string $secret, string $input, ?int $lastUsedStep = null, ?int $time = null): ?int
    {
        $input = preg_replace('/\s+/', '', $input) ?? '';
        if (! preg_match('/^\d{6}$/', $input)) {
            return null;
        }

        $now = self::step($time);
        foreach ([0, -1, 1] as $drift) {
            $step = $now + $drift;
            if ($lastUsedStep !== null && $step <= $lastUsedStep) {
                continue;
            }
            if (hash_equals(self::code($secret, $step), $input)) {
                return $step;
            }
        }

        return null;
    }

    /** The address an authenticator app reads from the QR code */
    public static function uri(string $secret, string $account, string $issuer = 'Adino School Systems'): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$account).'?secret='.$secret.'&issuer='.rawurlencode($issuer).'&algorithm=SHA1&digits=6&period=30';
    }

    public static function base32Encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $out;
    }

    public static function base32Decode(string $text): string
    {
        $bits = '';
        foreach (str_split(strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $text) ?? '')) as $c) {
            $bits .= str_pad(decbin((int) strpos(self::ALPHABET, $c)), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }
}
