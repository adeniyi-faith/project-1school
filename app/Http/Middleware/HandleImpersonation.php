<?php

namespace App\Http\Middleware;

use App\Support\Impersonation;
use App\Support\PlatformAccess;
use App\Support\PlatformAudit;
use App\Support\PlatformSecurityRules;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * While a platform person is signed in as a school login: ends it when the time is up, the real person is switched
 * off, or their network is no longer allowed; turns back changes in look-only mode (and password or profile changes
 * always); and writes every page and change to the staff activity log under the real person's name.
 */
class HandleImpersonation
{
    /** Never allowed while signed in as someone else, even when changes are allowed */
    private const NEVER = ['profile.update', 'profile.password', 'password.change', 'security.*'];

    public function handle(Request $request, Closure $next): Response
    {
        $data = Impersonation::current();
        if (! $data) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user || $user->id !== (int) $data['user']) {
            $request->session()->forget(Impersonation::KEY);

            return $next($request);
        }

        $by = User::find($data['by']);
        if (! $by || ! PlatformAccess::isPlatform($by) || $by->status !== 'active' || ! PlatformSecurityRules::ipAllowed($by, $request->ip())) {
            $request->session()->forget(Impersonation::KEY);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        if ($request->routeIs('impersonation.stop')) {
            return $next($request);
        }

        if (now()->getTimestamp() >= (int) $data["until"] || $request->routeIs('logout')) {
            $timeUp = now()->getTimestamp() >= (int) $data["until"];
            $to = Impersonation::stop($request, $timeUp ? 'Time ran out while signed in as someone else' : 'Stopped signing in as someone else');

            return redirect($to)->with($timeUp ? 'info' : 'success', $timeUp ? 'Your time signed in as that person ran out, so you are back in your own account.' : 'You are back in your own account.');
        }

        $blocked = $request->routeIs(...self::NEVER) && ! $request->isMethodSafe()
            || (! $request->isMethodSafe() && $data['read_only']);
        if ($blocked || $request->routeIs('security.*')) {
            PlatformAudit::record('refused', 'As '.$user->name.': turned back '.$request->method().' /'.$request->path(),
                ['user' => $by, 'school_id' => $user->school_id, 'status' => 403], $request);
            $message = $data['read_only'] ? 'You are only looking. Nothing can be changed while you are signed in as this person.' : 'Passwords and security settings cannot be changed while you are signed in as someone else.';
            if ($request->header('X-Inertia') || ! $request->expectsJson()) {
                return back(303)->with('error', $message);
            }
            abort(403, $message);
        }

        $response = $next($request);

        try {
            if (! $request->isMethodSafe()) {
                PlatformAudit::record('changed', 'As '.$user->name.': '.PlatformAudit::describe($request),
                    ['user' => $by, 'school_id' => $user->school_id, 'status' => $response->getStatusCode(), 'details' => array_filter(['fields' => PlatformAudit::fieldNames($request)]) ?: null], $request);
            } elseif (! $request->expectsJson() && ! str_ends_with($request->path(), '/feed')) {
                PlatformAudit::record('viewed', 'As '.$user->name.': opened /'.ltrim($request->path(), '/'),
                    ['user' => $by, 'school_id' => $user->school_id, 'status' => $response->getStatusCode()], $request);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $response;
    }
}
