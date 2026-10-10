<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\DemoSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        ['user' => $user] = DemoSchool::ensure();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
