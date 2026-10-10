<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Hash;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SupabaseAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Demo accounts, shown only when SHOW_DEMO_ACCOUNTS=true
     */
    private array $demoAccounts = [
        [
            'role' => 'Super Admin',
            'email' => 'admin@genius-sms.test',
            'password' => 'password',
            'color' => 'indigo',
        ],
        [
            'role' => 'School Admin',
            'email' => 'school-admin@genius-sms.test',
            'password' => 'password',
            'color' => 'violet',
        ],
        [
            'role' => 'Principal',
            'email' => 'principal@genius-sms.test',
            'password' => 'password',
            'color' => 'blue',
        ],
        [
            'role' => 'Teacher',
            'email' => 'teacher@genius-sms.test',
            'password' => 'password',
            'color' => 'sky',
        ],
        [
            'role' => 'Accountant',
            'email' => 'accountant@genius-sms.test',
            'password' => 'password',
            'color' => 'emerald',
        ],
        [
            'role' => 'Student',
            'email' => 'student@genius-sms.test',
            'password' => 'password',
            'color' => 'amber',
        ],
        [
            'role' => 'Parent',
            'email' => 'parent@genius-sms.test',
            'password' => 'password',
            'color' => 'orange',
        ],
    ];

    public function create(Request $request): Response
    {
        $showDemo = (bool) config('app.show_demo_accounts');

        return Inertia::render('Auth/Login', [
            'showDemo' => $showDemo,
            'demoAccounts' => $showDemo ? $this->demoAccounts : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Block password guessing: 5 wrong tries per email + IP, then wait.
        $throttleKey = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            event(new Lockout($request));
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        // Native Database Authentication
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $user->update(['last_login_at' => now()]);

        if (function_exists('activity')) {
            activity()
                ->causedBy($user)
                ->withProperties(['ip' => $request->ip(), 'user_agent' => $request->userAgent()])
                ->log('User logged in');
        }

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
