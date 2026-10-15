<?php

namespace App\Http\Middleware;

use App\Models\SchoolApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Lets a school's own script in with the read-only key from Website > Developer. Only a fingerprint of the key is stored. */
class AuthenticateSchoolToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) $request->bearerToken();
        $token = $key !== '' ? SchoolApiToken::withoutGlobalScopes()->where('token_hash', hash('sha256', $key))->first() : null;

        if (! $token) {
            return response()->json(['error' => 'Send your key as "Authorization: Bearer <key>".'], 401);
        }

        // keep the "last used" time without a write on every call
        if (! $token->last_used_at || $token->last_used_at->lt(now()->subMinutes(5))) {
            $token->forceFill(['last_used_at' => now()])->save();
        }
        $request->attributes->set('school_id', (int) $token->school_id);

        return $next($request);
    }
}
