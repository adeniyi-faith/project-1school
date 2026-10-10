<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DemoController extends Controller
{
    /**
     * Sign a visitor in as the demo school's admin, with no password.
     * The demo user is created on first use. The DemoReadOnly middleware
     * stops this user from changing anything.
     */
    public function enter(Request $request): RedirectResponse
    {
        abort_unless(config('app.demo_enabled'), 404);

        $school = School::firstOrCreate(
            ['slug' => 'demo-school'],
            ['name' => 'Demo School', 'email' => 'demo-school@example.test', 'status' => 'active'],
        );

        $user = User::firstOrCreate(
            ['email' => config('app.demo_email')],
            [
                'school_id' => $school->id,
                'name' => 'Demo Admin',
                'status' => 'active',
                // Nobody signs in with this: demo entry skips the password
                'password' => Str::random(64),
            ],
        );

        if (! $user->hasRole('school-admin')) {
            $user->assignRole(Role::findOrCreate('school-admin', 'web'));
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
