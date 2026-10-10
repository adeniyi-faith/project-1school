<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Services\SupabaseAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Throwable;

class RegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * A school signs itself up: we make its Supabase login, the school
     * record and its first admin, then sign the admin straight in.
     */
    public function store(Request $request, SupabaseAuthService $supabaseAuth): RedirectResponse
    {
        $data = $request->validate([
            'school_name' => ['required', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ]);

        $useSupabase = config('app.login_driver') === 'supabase';
        $supabaseId = null;

        try {
            if ($useSupabase) {
                $supabaseId = $supabaseAuth->createUser($data['email'], $data['password']);
            }
        } catch (Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                'email' => 'We could not create this account. The email may already be in use, or sign-up is unavailable right now.',
            ]);
        }

        try {
            $user = DB::transaction(function () use ($data, $supabaseId, $useSupabase) {
                $school = School::create([
                    'name' => $data['school_name'],
                    'slug' => $this->uniqueSlug($data['school_name']),
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'status' => 'active',
                ]);

                // With Supabase, it checks the password and we keep a random
                // placeholder. Otherwise the password is stored hashed here.
                $user = User::create([
                    'school_id' => $school->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'supabase_id' => $supabaseId,
                    'status' => 'active',
                    'password' => $useSupabase ? Str::random(64) : $data['password'],
                ]);

                $user->assignRole(Role::findOrCreate('school-admin', 'web'));

                return $user;
            });
        } catch (Throwable $e) {
            report($e);

            // Undo the Supabase login (if any) so the person can try again cleanly
            if ($supabaseId) {
                $supabaseAuth->deleteUser($supabaseId);
            }

            throw ValidationException::withMessages([
                'email' => 'Something went wrong while setting up your school. Please try again.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        return redirect()->route('dashboard');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'school';
        $slug = $base;

        while (School::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(5));
        }

        return $slug;
    }
}
