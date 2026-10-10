<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public demo user may look at everything but change nothing. Any
 * request that would save, edit or delete is turned back with a message.
 * Signing out is the only action allowed.
 */
class DemoReadOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && config('app.demo_email')
            && strcasecmp($user->email, (string) config('app.demo_email')) === 0
            && ! $request->isMethodSafe()
            && ! $request->routeIs('logout')
        ) {
            if ($request->header('X-Inertia') || ! $request->expectsJson()) {
                return back()->with('error', 'This is a read-only demo. Register your school to make changes.');
            }

            abort(403, 'This is a read-only demo.');
        }

        return $next($request);
    }
}
