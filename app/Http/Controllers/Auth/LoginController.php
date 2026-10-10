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
use Illuminate\Support\Facades\Hash;
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
            'demoEnabled' => (bool) config('app.demo_enabled'),
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

$user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

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

    /**
     * Local sign-in: the password is checked against the hashed password
     * stored in this app's own database.
     */
    private function checkLocally(array $credentials): ?User
    {
        $user = User::where('email', $credentials['email'])->first();

        // Always hash-check, even for unknown emails, so the response time
        // does not reveal which emails have accounts.
        $hash = $user?->password ?? '$2y$10$YCWdLwYqB6O0Utp55QXmeuMimyCnuRbzU34zvj3HTU/Naaju.fxq2';

        try {
            $matches = Hash::check($credentials['password'], $hash);
        } catch (\RuntimeException) {
            // Stored value is not a real hash (e.g. a placeholder): never a match
            $matches = false;
        }

        return $user && $matches ? $user : null;
    }

    /**
     * Supabase sign-in: Supabase confirms the password; Laravel still
     * decides whether that person has an account here.
     */
    private function checkWithSupabase(array $credentials, SupabaseAuthService $supabaseAuth): ?User
    {
        $supabaseUser = $supabaseAuth->signIn($credentials['email'], $credentials['password']);

        if (! $supabaseUser) {
            return null;
        }

        $user = User::where('email', $credentials['email'])->first();

        if ($user && $user->supabase_id !== $supabaseUser['id']) {
            $user->supabase_id = $supabaseUser['id'];
        }

        return $user;
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
