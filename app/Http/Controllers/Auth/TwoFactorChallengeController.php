<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PlatformAudit;
use App\Support\PlatformSecurityRules;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** The second sign-in step for platform people: the 6-digit code from their authenticator app, or a recovery code */
class TwoFactorChallengeController extends Controller
{
    /** The password was right this many minutes ago at most */
    private const WINDOW_MINUTES = 10;

    public function show(Request $request): Response|RedirectResponse
    {
        if (! $this->pending($request)) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactor');
    }

    public function store(Request $request, LoginController $login): RedirectResponse
    {
        $user = $this->pending($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'That sign-in took too long. Please start again.']);
        }

        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);

        // Five wrong codes and the person must start again with their password
        $key = 'two-factor:'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $request->session()->forget('two_factor');
            throw ValidationException::withMessages(['code' => 'Too many wrong codes. Please sign in again in a minute.']);
        }

        if (! $this->accept($user, $data['code'])) {
            RateLimiter::hit($key, 60);
            PlatformAudit::record('login_failed', 'Wrong second-step code', ['user' => $user, 'school_id' => null, 'status' => 422], $request);
            throw ValidationException::withMessages(['code' => 'That code is not right. Check your authenticator app and try again.']);
        }

        RateLimiter::clear($key);
        $remember = (bool) ($request->session()->get('two_factor.remember'));
        $request->session()->forget('two_factor');

        return $login->complete($request, $user, $remember);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget('two_factor');

        return redirect()->route('login');
    }

    /** A 6-digit code (each works once) or a recovery code (each works once) */
    private function accept(User $user, string $input): bool
    {
        $security = PlatformSecurityRules::security($user);

        if (preg_match('/^\s*\d[\d\s]*$/', $input)) {
            $step = Totp::verify((string) $security->totp_secret, $input, $security->totp_last_step);
            if ($step === null) {
                return false;
            }
            // Only the first request to use this step wins, so a watched-over shoulder cannot reuse it
            return $security->newQuery()->whereKey($security->id)
                ->where(fn ($q) => $q->whereNull('totp_last_step')->orWhere('totp_last_step', '<', $step))
                ->update(['totp_last_step' => $step]) === 1;
        }

        return PlatformSecurityRules::useRecoveryCode($user, $input);
    }

    private function pending(Request $request): ?User
    {
        $pending = $request->session()->get('two_factor');
        if (! is_array($pending) || (time() - ($pending['at'] ?? 0)) > self::WINDOW_MINUTES * 60) {
            $request->session()->forget('two_factor');

            return null;
        }

        $user = User::find($pending['id'] ?? 0);

        return $user && ! in_array($user->status, ['suspended', 'inactive'], true) ? $user : null;
    }
}
