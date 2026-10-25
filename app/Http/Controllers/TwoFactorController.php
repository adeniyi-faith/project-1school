<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CertificateService;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Turning two-step sign-in on and off, from "My profile". Nobody is forced; each person chooses. */
class TwoFactorController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();
        $setup = null;
        if (! $user->hasTwoFactor() && $user->two_factor_secret) {
            $uri = Totp::uri($user->two_factor_secret, $user->email, $this->issuer());
            $setup = ['secret' => $user->two_factor_secret, 'qr' => CertificateService::qr($uri)];
        }

        return Inertia::render('Security', [
            'enabled' => $user->hasTwoFactor(),
            'recovery_left' => $user->hasTwoFactor() ? count($user->two_factor_recovery_codes ?? []) : 0,
            'setup' => $setup,
            // Shown once, right after they are made
            'new_codes' => $request->session()->get('new_recovery_codes'),
        ]);
    }

    /** Make a secret and show the QR code. Nothing changes at sign-in until a code from the app is confirmed. */
    public function start(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->hasTwoFactor()) {
            $user->forceFill(['two_factor_secret' => Totp::generateSecret(), 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
        }

        return back();
    }

    public function confirm(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate(['code' => 'required|string|max:20']);
        if ($user->hasTwoFactor() || ! $user->two_factor_secret) {
            return back();
        }
        if (Totp::verify($user->two_factor_secret, $data['code']) === null) {
            throw ValidationException::withMessages(['code' => 'That code is not right. Check the code in your app and try again.']);
        }

        $codes = $this->newCodes();
        $user->forceFill(['two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => array_map(fn ($c) => Hash::make($c), $codes)])->save();
        activity()->causedBy($user)->performedOn($user)->log('Two-step sign-in turned on');

        return back()->with('new_recovery_codes', $codes);
    }

    public function regenerate(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->validate(['password' => ['required', 'current_password']]);
        abort_unless($user->hasTwoFactor(), 404);

        $codes = $this->newCodes();
        $user->forceFill(['two_factor_recovery_codes' => array_map(fn ($c) => Hash::make($c), $codes)])->save();

        return back()->with('new_recovery_codes', $codes);
    }

    public function disable(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->validate(['password' => ['required', 'current_password']]);
        self::switchOff($user);
        activity()->causedBy($user)->performedOn($user)->log('Two-step sign-in turned off');

        return back()->with('success', 'Two-step sign-in is off.');
    }

    /** Also used when the platform owner helps someone who lost their phone */
    public static function switchOff(User $user): void
    {
        $user->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
    }

    private function issuer(): string
    {
        return (string) (config('app.name') ?: 'Adino School Systems');
    }

    /** Eight one-use codes like AB12-CD34, to keep somewhere safe in case the phone is lost */
    private function newCodes(): array
    {
        return collect(range(1, 8))->map(fn () => strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4)))->all();
    }
}
