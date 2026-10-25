<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSecurity;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Support\PlatformAccess;
use App\Support\PlatformSecurityRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The owner's view of platform security: who has set up their second sign-in step, who must still change a password,
 * the list of networks staff may sign in from, and the buttons to help someone who lost their phone or must be
 * signed out at once. Owner only (it is not listed in PlatformAccess::PAGES).
 */
class PlatformSecurityController extends Controller
{
    public function index(Request $request): Response
    {
        $people = User::whereHas('roles', fn ($r) => $r->whereIn('name', PlatformAccess::roleNames()))->orderBy('name')->get();
        $security = PlatformSecurity::whereIn('user_id', $people->pluck('id'))->get()->keyBy('user_id');
        $sessions = PlatformSecurityRules::sessionsListable()
            ? DB::table(config('session.table', 'sessions'))->whereIn('user_id', $people->pluck('id'))->selectRaw('user_id, count(*) as n')->groupBy('user_id')->pluck('n', 'user_id')
            : collect();

        return Inertia::render('SuperAdmin/Security/Index', [
            'required' => PlatformSecurityRules::twoFactorRequired(),
            'allowList' => implode("\n", PlatformSecurityRules::allowList()),
            'yourIp' => $request->ip(),
            'sessionsListable' => PlatformSecurityRules::sessionsListable(),
            'people' => $people->map(fn (User $u) => [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'status' => $u->status,
                'last_login_at' => $u->last_login_at?->toIso8601String(),
                'two_factor' => (bool) $security->get($u->id)?->isEnrolled(),
                'must_change_password' => (bool) $security->get($u->id)?->must_change_password,
                'sessions' => (int) $sessions->get($u->id, 0),
            ])->values(),
        ]);
    }

    /** Save the list of networks. Empty = anywhere. The owner is never held back by it. */
    public function saveNetworks(Request $request): RedirectResponse
    {
        $lines = array_values(array_unique(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) $request->input('allow_list', '')) ?: []))));
        if (count($lines) > 100) {
            throw ValidationException::withMessages(['allow_list' => 'Please keep the list to 100 lines.']);
        }
        if ($bad = PlatformSecurityRules::badLines($lines)) {
            throw ValidationException::withMessages(['allow_list' => 'These are not addresses: '.implode(', ', array_slice($bad, 0, 5)).'. Use an address like 41.58.10.5 or a range like 41.58.0.0/16.']);
        }

        PlatformSetting::set(PlatformSecurityRules::ALLOWLIST_KEY, implode("\n", $lines), 'security');
        activity('platform')->causedBy($request->user())->withProperties(['entries' => $lines])->log('Allowed networks for platform staff changed');

        return back()->with('success', $lines ? 'Platform staff can now sign in only from those networks.' : 'The network list is empty, so staff can sign in from anywhere.');
    }

    public function resetTwoFactor(Request $request, User $user): RedirectResponse
    {
        $this->guard($user);
        PlatformSecurityRules::resetTwoFactor($user);
        PlatformSecurityRules::signOutEverywhere($user);
        activity('platform')->causedBy($request->user())->performedOn($user)->log('Platform staff second sign-in step reset');

        return back()->with('success', "{$user->name} was signed out everywhere and will set up their authenticator app again at their next sign-in.");
    }

    public function signOutEverywhere(Request $request, User $user): RedirectResponse
    {
        $this->guard($user);
        $count = PlatformSecurityRules::signOutEverywhere($user);
        activity('platform')->causedBy($request->user())->performedOn($user)->log('Platform staff signed out everywhere');

        return back()->with('success', "{$user->name} was signed out everywhere".($count ? " ({$count} ".($count === 1 ? 'device' : 'devices').').' : '.'));
    }

    public function forcePassword(Request $request, User $user): RedirectResponse
    {
        $this->guard($user);
        PlatformSecurityRules::forcePasswordChange($user);
        activity('platform')->causedBy($request->user())->performedOn($user)->log('Platform staff must choose a new password');

        return back()->with('success', "{$user->name} must choose a new password at their next click.");
    }

    private function guard(User $user): void
    {
        abort_unless($user->hasAnyRole(PlatformAccess::roleNames()) && ! PlatformAccess::isOwner($user), 404);
    }
}
