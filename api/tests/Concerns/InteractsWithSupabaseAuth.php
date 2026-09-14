<?php

namespace Tests\Concerns;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;

/**
 * Signs test tokens the same way Supabase actually does: with a
 * private key (ES256), verified by the app against a public key it
 * fetches from Supabase's JWKS endpoint — not a shared secret.
 */
trait InteractsWithSupabaseAuth
{
    private function tokenFor(string $supabaseUserId, string $email): string
    {
        // A fresh, unique host per call so each token's fake JWKS
        // response can't collide with another token's in the same test.
        $host = 'https://test-'.bin2hex(random_bytes(4)).'.supabase.co';
        config(['services.supabase.url' => $host]);

        $keyPair = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        $details = openssl_pkey_get_details($keyPair);
        openssl_pkey_export($keyPair, $privateKeyPem);

        $kid = 'test-key-1';
        $jwks = [
            'keys' => [[
                'kty' => 'EC',
                'crv' => 'P-256',
                'alg' => 'ES256',
                'use' => 'sig',
                'kid' => $kid,
                'x' => JWT::urlsafeB64Encode(str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT)),
                'y' => JWT::urlsafeB64Encode(str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT)),
            ]],
        ];

        $jwksUrl = "{$host}/auth/v1/.well-known/jwks.json";
        Http::fake([$jwksUrl => Http::response($jwks)]);

        return JWT::encode([
            'sub' => $supabaseUserId,
            'email' => $email,
            'exp' => now()->addHour()->timestamp,
        ], $privateKeyPem, 'ES256', $kid);
    }
}
