<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Support\ActingSchool;
use App\Support\PlatformAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * While a member of the platform team is working inside a school, every school page sees them as that school's person,
 * so all of the app's "my school" logic works without change. Without a chosen school, school pages send them to pick one.
 * The school id is set in memory only and is marked unchanged, so it can never be saved onto the account.
 *
 * The owner may enter any school and change things there. Other platform staff may enter only schools they are
 * assigned to and only if their role allows it, and they can change nothing unless they hold `platform.schools.act`.
 */
class ActAsSchool
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! PlatformAccess::isPlatform($user)) {
            return $next($request);
        }

        $id = ActingSchool::id();
        if ($id && ! School::whereKey($id)->exists()) {
            ActingSchool::leave();
            $id = null;
        }

        $staff = PlatformAccess::isStaff($user);

        // Staff lose the school the moment their permission or their school list no longer covers it
        if ($id && $staff && (! PlatformAccess::can($user, 'platform.schools.enter') || ! PlatformAccess::canSeeSchool($user, $id))) {
            ActingSchool::leave();
            $id = null;
            if ($request->isMethod('GET') && $request->is('school', 'school/*')) {
                return redirect()->route(PlatformAccess::homeRoute($user))->with('error', 'You can no longer work inside that school.');
            }
        }

        if ($id) {
            $user->setAttribute('school_id', $id);
            $user->syncOriginalAttribute('school_id');

            // Looking is always allowed; changing needs the extra permission
            if ($staff && ! $request->isMethodSafe() && ! PlatformAccess::can($user, 'platform.schools.act')
                && ! $request->routeIs('super-admin.schools.leave', 'logout')) {
                if ($request->expectsJson() && ! $request->header('X-Inertia')) {
                    $request->attributes->set('audit_refused', true);
                    abort(403, 'You can look around this school but not change anything.');
                }

                $request->attributes->set('audit_refused', true);

                return back(303)->with('error', 'You are looking around this school. Your role does not allow changing anything here.');
            }

            return $next($request);
        }

        // No school chosen: school pages have nothing to show, so go and choose one
        if ($request->isMethod('GET') && $request->is('school', 'school/*') && ! $request->expectsJson()) {
            return redirect()->route($staff ? PlatformAccess::homeRoute($user) : 'super-admin.schools.index')->with('info', 'Choose a school to work in first.');
        }

        // Staff may never save anything in a school they have not entered
        abort_if($staff && ! $request->isMethodSafe() && $request->is('school', 'school/*'), 403);

        return $next($request);
    }
}
