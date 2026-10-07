<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SupabaseAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Demo accounts shown only on .test domains or xgenious.com
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
        $host = $request->getHost();
        $showDemo = str_ends_with($host, '.test') || str_contains($host, 'xgenious.com');

        return Inertia::render('Auth/Login', [
            'showDemo' => $showDemo,
            'demoAccounts' => $showDemo ? $this->demoAccounts : [],
        ]);
    }

    public function store(Request $request, SupabaseAuthService $supabaseAuth): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Supabase checks the password. Laravel never sees or stores it;
        // it only decides, once Supabase confirms who's signing in,
        // whether that person has an account here and what they can do.
        $supabaseUser = $supabaseAuth->signIn($credentials['email'], $credentials['password']);

        if (! $supabaseUser) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if ($user->supabase_id !== $supabaseUser['id']) {
            $user->supabase_id = $supabaseUser['id'];
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $user->update(['last_login_at' => now()]);

        activity()
            ->causedBy($user)
            ->withProperties(['ip' => $request->ip(), 'user_agent' => $request->userAgent()])
            ->log('User logged in');

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
