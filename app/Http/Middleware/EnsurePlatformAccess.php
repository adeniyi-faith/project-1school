<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Models\User;
use App\Support\PlatformAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards every page under /super-admin. The owner passes. A member of the platform team passes only when their
 * role (or an extra given to them) allows that page (see PlatformAccess::PAGES), and, when they are limited to some
 * schools, only for those schools. Anything not listed there is for the owner alone.
 */
class EnsurePlatformAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless(PlatformAccess::isPlatform($user), 403);

        if (PlatformAccess::isOwner($user)) {
            return $next($request);
        }

        $rule = PlatformAccess::requirement($request->route()?->getName());
        abort_if($rule === null, 403);
        [$permission, $scopedOk] = $rule;

        abort_unless($permission === null || PlatformAccess::can($user, $permission), 403);

        if (PlatformAccess::isScoped($user)) {
            // A person limited to some schools can only use pages that are written to show just those schools
            abort_unless($scopedOk, 403);
            $school = $request->route('school');
            if ($school !== null) {
                abort_unless(PlatformAccess::canSeeSchool($user, $school instanceof School ? $school : (int) $school), 403);
            }
            $target = $request->route('user');
            if ($target instanceof User) {
                abort_unless($target->school_id && PlatformAccess::canSeeSchool($user, (int) $target->school_id), 403);
            }
        }

        return $next($request);
    }
}
