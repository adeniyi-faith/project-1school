<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\DemoSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DemoController extends Controller
{
    /**
     * The "choose an account type" page. A visitor picks School Admin, Teacher,
     * Parent, Student ... and looks around as that person in the demo school.
     * Someone already in the demo can come back here to switch.
     */
    public function pick(Request $request): Response|RedirectResponse
    {
        abort_unless(config('app.demo_enabled'), 404);

        $user = $request->user();
        if ($user && ! DemoSchool::isDemoUser($user)) {
            return redirect()->route('dashboard');
        }

        $hasPupils = DemoSchool::hasPupils();

        return Inertia::render('Auth/DemoPick', [
            'accounts' => collect(DemoSchool::ACCOUNTS)->map(fn ($a, $key) => [
                'key' => $key,
                'label' => $a[1],
                'text' => $a[2],
                'available' => ! in_array($key, ['student', 'parent'], true) || $hasPupils,
            ])->values(),
            'current' => $user ? DemoSchool::keyOf($user) : null,
        ]);
    }

    /**
     * Sign the visitor in as the chosen demo account, with no password. Each
     * account is created on first use. The DemoReadOnly middleware stops every
     * demo account from changing anything.
     */
    public function enter(Request $request, string $account): RedirectResponse
    {
        abort_unless(config('app.demo_enabled'), 404);
        abort_unless(isset(DemoSchool::ACCOUNTS[$account]), 404);

        // A real account is never swapped for a demo one
        if ($request->user() && ! DemoSchool::isDemoUser($request->user())) {
            return redirect()->route('dashboard');
        }

        $user = DemoSchool::userFor($account);
        if (! $user) {
            return redirect()->route('demo')->with('error', 'This account type needs the sample pupils, which are not loaded yet. Try another one.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
