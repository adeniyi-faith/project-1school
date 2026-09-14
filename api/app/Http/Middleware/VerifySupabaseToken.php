<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class VerifySupabaseToken
{
    /**
     * Checks the login pass (JWT) that Supabase handed the browser when
     * the user signed in, against Supabase's own public signing keys.
     * If it's valid, we know who's making the request; the request's
     * own rules (which school, which role) are still Laravel's job.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $decoded = JWT::decode($token, $this->supabaseKeys());
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        $request->attributes->set('supabase_user_id', $decoded->sub);
        $request->attributes->set('supabase_user_email', $decoded->email ?? null);

        return $next($request);
    }

    /**
     * Supabase's public signing keys (it signs login tokens with a
     * private key, not a shared secret). Cached briefly so we're not
     * fetching them on every single request.
     *
     * @return array<string, Key>
     */
    private function supabaseKeys(): array
    {
        $jwksUrl = rtrim((string) config('services.supabase.url'), '/').'/auth/v1/.well-known/jwks.json';

        $jwks = Cache::remember(
            'supabase_jwks:'.md5($jwksUrl),
            now()->addMinutes(10),
            fn () => Http::get($jwksUrl)->throw()->json(),
        );

        return JWK::parseKeySet($jwks);
    }
}
