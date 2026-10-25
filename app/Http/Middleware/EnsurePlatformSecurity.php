<?php

namespace App\Http\Middleware;

use App\Support\PlatformAccess;
use App\Support\PlatformAudit;
use App\Support\PlatformSecurityRules;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Holds platform people to the platform's sign-in rules on every click, not only at sign-in:
 *  1. staff may use the platform only from the approved networks (if the owner set any);
 *  2. a new or reset password must be changed first;
 *  3. the second sign-in step (authenticator app) must be set up.
 * The pages where a person fixes these (and signing out) are always open to them.
 */
class EnsurePlatformSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! PlatformAccess::isPlatform($user)) {
            return $next($request);
        }

        if (! PlatformSecurityRules::ipAllowed($user, $request->ip())) {
            PlatformAudit::record('refused', 'Signed out: not on an allowed network', ['user' => $user, 'school_id' => null, 'status' => 403], $request);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'This account can only be used from the platform team\'s approved networks.']);
        }

        if ($request->routeIs('logout', 'security.*')) {
            return $next($request);
        }

        $to = null;
        if (PlatformSecurityRules::mustChangePassword($user)) {
            $to = ['security.index', 'Please choose a new password before you carry on.'];
        } elseif (PlatformSecurityRules::twoFactorRequired() && ! PlatformSecurityRules::isEnrolled($user)) {
            $to = ['security.index', 'Please set up your authenticator app before you carry on.'];
        }

        if ($to) {
            if ($request->expectsJson() && ! $request->header('X-Inertia')) {
                abort(403, $to[1]);
            }

            return redirect()->route($to[0])->with('error', $to[1]);
        }

        return $next($request);
    }
}
