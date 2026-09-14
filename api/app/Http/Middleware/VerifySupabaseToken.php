<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySupabaseToken
{
    /**
     * Checks the login pass (JWT) that Supabase handed the browser when
     * the user signed in. If it's valid, we know who's making the
     * request; the request's own rules (which school, which role) are
     * still Laravel's job.
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
            $decoded = JWT::decode(
                $token,
                new Key(config('services.supabase.jwt_secret'), 'HS256'),
            );
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        $request->attributes->set('supabase_user_id', $decoded->sub);
        $request->attributes->set('supabase_user_email', $decoded->email ?? null);

        return $next($request);
    }
}
