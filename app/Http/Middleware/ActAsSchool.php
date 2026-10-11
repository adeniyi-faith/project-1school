<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Support\ActingSchool;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * While a super admin is working inside a school, every school page sees them as that school's person, so all of the
 * app's "my school" logic works without change. Without a chosen school, school pages send them to pick one.
 * The school id is set in memory only and is marked unchanged, so it can never be saved onto the account.
 */
class ActAsSchool
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->hasRole('super-admin')) {
            return $next($request);
        }

        $id = ActingSchool::id();
        if ($id && ! School::whereKey($id)->exists()) {
            ActingSchool::leave();
            $id = null;
        }

        if ($id) {
            $user->setAttribute('school_id', $id);
            $user->syncOriginalAttribute('school_id');

            return $next($request);
        }

        // No school chosen: school pages have nothing to show, so go and choose one
        if ($request->isMethod('GET') && $request->is('school', 'school/*') && ! $request->expectsJson()) {
            return redirect()->route('super-admin.schools.index')->with('info', 'Choose a school to work in first.');
        }

        return $next($request);
    }
}
