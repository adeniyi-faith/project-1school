<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupabaseAuthService
{
    /**
     * Ask Supabase to check an email/password pair. Supabase is the only
     * place the password itself is checked; Laravel never sees or stores
     * the real password. Returns Supabase's own id and email for that
     * person when the password is correct, or null when it isn't.
     *
     * @return array{id: string, email: string}|null
     */
    public function signIn(string $email, string $password): ?array
    {
        $url = rtrim((string) config('services.supabase.url'), '/').'/auth/v1/token?grant_type=password';

        $response = Http::withHeaders([
            'apikey' => config('services.supabase.anon_key'),
            'Content-Type' => 'application/json',
        ])->post($url, [
            'email' => $email,
            'password' => $password,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $user = $response->json('user');

        if (! is_array($user) || ! isset($user['id'], $user['email'])) {
            return null;
        }

        return [
            'id' => $user['id'],
            'email' => $user['email'],
        ];
    }
}
