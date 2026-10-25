<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The second step of signing in for people who turned on two-step sign-in: the password was right,
 * now a code from the authenticator app (or one of the saved recovery codes) is needed.
 */
class TwoFactorChallengeController extends Controller
{
    /** How long after the password the code can still be entered */
    private const WINDOW_MINUTES = 10;

    public function create(Request $request): Response|RedirectResponse
    {
        return $this->pendingUser($request) ? Inertia::render('Auth/TwoFactorChallenge') : redirect()->route('login');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->with('error', 'Your sign-in took too long. Please log in again.');
        }

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:40'],
            'recovery_code' => ['nullable', 'string', 'max:40'],
        ]);
        if (blank($data['code'] ?? null) && blank($data['recovery_code'] ?? null)) {
            throw ValidationException::withMessages(['code' => 'Type the 6-digit code from your authenticator app.']);
        }

        $key = '2fa|'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['code' => 'Too many tries. Please wait '.RateLimiter::availableIn($key).' seconds.']);
        }

        $ok = filled($data['code'] ?? null)
            ? $this->codeIsGood($user, $data['code'])
            : $this->useRecoveryCode($user, $data['recovery_code']);

        if (! $ok) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([filled($data['code'] ?? null) ? 'code' : 'recovery_code' => 'That code is not right. Check the code in your app and try again.']);
        }

        RateLimiter::clear($key);
        $remember = (bool) $request->session()->pull('two_factor.remember', false);
        $request->session()->forget(['two_factor.id', 'two_factor.at']);

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        activity()->causedBy($user)
            ->withProperties(['ip' => $request->ip(), 'user_agent' => $request->userAgent(), 'two_factor' => filled($data['code'] ?? null) ? 'app' : 'recovery code'])
            ->log('User logged in');

        return redirect()->route('dashboard');
    }

    /** The person who typed the right password a moment ago, if that is still fresh */
    private function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get('two_factor.id');
        $at = (int) $request->session()->get('two_factor.at', 0);
        if (! $id || $at < now()->subMinutes(self::WINDOW_MINUTES)->timestamp) {
            return null;
        }
        $user = User::find($id);

        return $user && $user->hasTwoFactor() ? $user : null;
    }

    /** A correct code, used for the first time. The same code cannot be used twice. */
    private function codeIsGood(User $user, string $code): bool
    {
        $step = Totp::verify((string) $user->two_factor_secret, $code);

        return $step !== null && Cache::add('2fa-used|'.$user->id.'|'.$step, true, now()->addMinutes(3));
    }

    private function useRecoveryCode(User $user, string $input): bool
    {
        $input = strtoupper(trim($input));
        $codes = $user->two_factor_recovery_codes ?? [];
        foreach ($codes as $i => $hash) {
            if (Hash::check($input, $hash)) {
                unset($codes[$i]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }
}
