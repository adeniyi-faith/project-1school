<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * When the platform owner suspends a school, nobody who belongs to it can keep using the app.
 * They are signed out and told why. The platform owner is never affected.
 */
class BlockSuspendedSchool
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->hasRole('super-admin') || ! $user->school_id) {
            return $next($request);
        }

        if (School::whereKey($user->school_id)->where('status', 'suspended')->exists()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'This school\'s account has been suspended. Please contact SchoolRuns support.']);
        }

        return $next($request);
    }
}
