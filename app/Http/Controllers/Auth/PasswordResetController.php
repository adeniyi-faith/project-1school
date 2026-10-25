<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SupabaseAuthService;
use App\Support\DemoSchool;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Forgot password": a link is emailed, it opens a page to choose a new password.
 * The answer to "send me a link" is always the same, so nobody can use it to find out
 * which emails have accounts.
 */
class PasswordResetController extends Controller
{
    public function forgot(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $data['email'])->first();

        // Demo accounts are shared and read-only, and switched-off accounts cannot sign in anyway
        if ($user && ! DemoSchool::isDemoUser($user) && ! in_array($user->status, ['suspended', 'inactive'], true)) {
            Password::sendResetLink(['email' => $data['email']]);
        }

        return back()->with('success', 'If that email has an account, a link to choose a new password is on its way. It works for 60 minutes.');
    }

    public function reset(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function update(Request $request, SupabaseAuthService $supabaseAuth): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $data,
            function (User $user, string $password) use ($request, $supabaseAuth) {
                // With the Supabase login, Supabase holds the password, so change it there first
                if (config('app.login_driver') === 'supabase' && $user->supabase_id) {
                    try {
                        $supabaseAuth->setPassword($user->supabase_id, $password);
                    } catch (\RuntimeException) {
                        throw ValidationException::withMessages(['email' => 'The password could not be changed right now. Please try again in a few minutes.']);
                    }
                }

                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

                activity()->causedBy($user)->performedOn($user)
                    ->withProperties(['ip' => $request->ip()])
                    ->log('Password changed with a reset link');

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'This link is not valid any more. Ask for a new one.']);
        }

        return redirect()->route('login')->with('success', 'Your password has been changed. You can log in now.');
    }
}
