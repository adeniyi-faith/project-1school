<?php

namespace App\Http\Middleware;

use App\Support\DemoSchool;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public demo accounts (admin, teacher, parent, student ...) may look at everything but change nothing. Any
 * request that would save, edit or delete is turned back with a message.
 * Signing out and switching to another demo account are the only actions allowed, plus the two design "try it" screens, which show a sample
 * on screen (including pictures just picked) and save nothing.
 */
class DemoReadOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ! $request->isMethodSafe()
            && DemoSchool::isDemoUser($user)
            && ! $request->routeIs('logout', 'demo.enter', 'school.report-card-designs.try', 'school.certificate-designs.try', 'school.website.preview', 'school.website.posts.preview')
        ) {
            if ($request->header('X-Inertia') || ! $request->expectsJson()) {
                return back()->with('error', 'This is a read-only demo. Register your school to make changes.');
            }

            abort(403, 'This is a read-only demo.');
        }

        return $next($request);
    }
}
