<?php

namespace App\Http\Controllers;

use App\Services\StaffAccountService;
use App\Services\SupabaseAuthService;
use App\Support\PlatformAccess;
use App\Support\PlatformSecurityRules;
use App\Support\Totp;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "My security" for people on the platform team: the authenticator app (second sign-in step), recovery codes,
 * open sessions, and choosing a new password. Open to any signed-in platform person, even before they have finished
 * setting up (that is where the setup happens).
 */
class SecurityController extends Controller implements HasMiddleware
{
    /** School logins have their own password page; this one is only for the platform team */
    public static function middleware(): array
    {
        return [
            new Middleware(function (Request $request, \Closure $next) {
                abort_unless(PlatformAccess::isPlatform($request->user()), 403);

                return $next($request);
            }),
        ];
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $security = PlatformSecurityRules::security($user);
        $enrolled = $security->isEnrolled();
        $setup = null;

        if (! $enrolled) {
            if (! $security->totp_secret) {
                $security->update(['totp_secret' => Totp::secret()]);
            }
            $uri = Totp::uri((string) $security->totp_secret, $user->email);
            $setup = ['secret' => trim(chunk_split((string) $security->totp_secret, 4, ' ')), 'qr' => $this->qr($uri)];
        }

        return Inertia::render('Security/Index', [
            'enrolled' => $enrolled,
            'required' => PlatformSecurityRules::twoFactorRequired(),
            'setup' => $setup,
            'recoveryLeft' => $enrolled ? PlatformSecurityRules::recoveryLeft($user) : 0,
            'recoveryCodes' => session()->pull('recovery_codes'),
            'mustChangePassword' => (bool) $security->must_change_password,
            'passwordChangedAt' => $security->password_changed_at?->toIso8601String(),
            'sessionsListable' => PlatformSecurityRules::sessionsListable(),
            'sessions' => PlatformSecurityRules::sessionsFor($user, $request->session()->getId()),
        ]);
    }

    /** First code typed correctly = the app is set up. Shows the recovery codes once. */
    public function confirm(Request $request): RedirectResponse
    {
        $user = $request->user();
        $security = PlatformSecurityRules::security($user);
        abort_if($security->isEnrolled() || ! $security->totp_secret, 403);

        $data = $request->validate(['code' => 'required|string|max:20']);
        $step = Totp::verify((string) $security->totp_secret, $data['code']);
        if ($step === null) {
            throw ValidationException::withMessages(['code' => 'That code is not right. Check your authenticator app and try again.']);
        }

        $security->update(['totp_confirmed_at' => now(), 'totp_last_step' => $step]);
        session()->flash('recovery_codes', PlatformSecurityRules::makeRecoveryCodes($user));

        return redirect()->route('security.index')->with('success', 'Your authenticator app is set up. Keep the recovery codes somewhere safe.');
    }

    /** New recovery codes (the old ones stop working). Needs a fresh code from the app. */
    public function recoveryCodes(Request $request): RedirectResponse
    {
        $this->requireCode($request);
        session()->flash('recovery_codes', PlatformSecurityRules::makeRecoveryCodes($request->user()));

        return redirect()->route('security.index')->with('success', 'New recovery codes made. The old ones no longer work.');
    }

    /** Turn the second step off. Refused while the platform requires it. */
    public function disable(Request $request): RedirectResponse
    {
        abort_if(PlatformSecurityRules::twoFactorRequired(), 403, 'The platform requires the second sign-in step.');
        $this->requireCode($request);
        PlatformSecurityRules::resetTwoFactor($request->user());

        return redirect()->route('security.index')->with('success', 'The second sign-in step is switched off.');
    }

    public function password(Request $request, StaffAccountService $accounts, SupabaseAuthService $supabase): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'string', 'min:10', 'max:100', 'confirmed', 'different:current_password'],
        ], [
            'password.min' => 'Use at least 10 characters.',
            'password.different' => 'Choose a password you have not used just now.',
            'password.confirmed' => 'The two new passwords are not the same.',
        ]);

        $right = config('app.login_driver') === 'supabase'
            ? (bool) $supabase->signIn($user->email, $data['current_password'])
            : Hash::check($data['current_password'], (string) $user->password);
        if (! $right) {
            throw ValidationException::withMessages(['current_password' => 'That is not your current password.']);
        }

        $accounts->resetPassword($user, $data['password']);
        PlatformSecurityRules::forcePasswordChange($user, false);
        PlatformSecurityRules::security($user)->update(['password_changed_at' => now()]);
        // Anyone else who had this account open is signed out; this browser stays
        PlatformSecurityRules::signOutEverywhere($user, $request->session()->getId());

        return redirect()->route('security.index')->with('success', 'Your password is changed. Other devices were signed out.');
    }

    public function endSession(Request $request, string $session): RedirectResponse
    {
        abort_if($session === $request->session()->getId(), 422, 'Use Sign out to end this session.');
        abort_unless(PlatformSecurityRules::endSession($request->user(), $session), 404);

        return back()->with('success', 'That device was signed out.');
    }

    public function endOthers(Request $request): RedirectResponse
    {
        $count = PlatformSecurityRules::signOutEverywhere($request->user(), $request->session()->getId());

        return back()->with('success', $count ? "Signed out of {$count} other ".($count === 1 ? 'device' : 'devices').'.' : 'There was nothing else to sign out.');
    }

    private function requireCode(Request $request): void
    {
        $security = PlatformSecurityRules::security($request->user());
        abort_unless($security->isEnrolled(), 403);
        $data = $request->validate(['code' => 'required|string|max:20']);
        $step = Totp::verify((string) $security->totp_secret, $data['code'], $security->totp_last_step);
        if ($step === null) {
            throw ValidationException::withMessages(['code' => 'That code is not right. Check your authenticator app and try again.']);
        }
        $security->update(['totp_last_step' => $step]);
    }

    private function qr(string $text): ?string
    {
        try {
            return (new QRCode(new QROptions([
                'outputType' => QROutputInterface::GDIMAGE_PNG, 'outputBase64' => true, 'scale' => 6, 'quietzoneSize' => 2,
            ])))->render($text);
        } catch (\Throwable) {
            return null;
        }
    }
}
