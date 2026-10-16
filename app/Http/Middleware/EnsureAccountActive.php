<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * If an account is suspended or switched off while its owner is signed in, sign them out on their next click,
 * so "Suspend" takes effect straight away and not only at the next sign-in.
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && in_array($user->status, ['suspended', 'inactive'], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'This account has been switched off. Please contact your school.']);
        }

        return $next($request);
    }
}
