<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(): Response
    {
        $user = auth()->user();

        return Inertia::render('Profile', [
            'user' => array_merge($user->toArray(), [
                'role' => $user->getRoleNames()->first() ?? '',
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function changePasswordPage(): Response
    {
        return Inertia::render('ChangePassword');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        auth()->user()->update([
            'password' => Hash::make($data['password']),
        ]);

        return back()->with('success', 'Password changed successfully.');
    }
}
