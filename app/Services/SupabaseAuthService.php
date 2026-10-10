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

    /**
     * Create a Supabase login for a brand-new school admin. Uses the
     * service role key (server only) and marks the email as confirmed so
     * they can sign in straight away. Returns Supabase's id for the person,
     * or throws if Supabase refuses (for example, the email already exists).
     */
    public function createUser(string $email, string $password): string
    {
        $url = rtrim((string) config('services.supabase.url'), '/').'/auth/v1/admin/users';
        $key = config('services.supabase.service_role_key');

        $response = Http::withHeaders([
            'apikey' => $key,
            'Authorization' => 'Bearer '.$key,
        ])->post($url, [
            'email' => $email,
            'password' => $password,
            'email_confirm' => true,
        ]);

        $id = $response->json('id');

        if (! $response->successful() || ! is_string($id)) {
            throw new \RuntimeException('Supabase could not create this account.');
        }

        return $id;
    }

    /** Remove a Supabase login again, used to undo a half-finished sign-up. */
    public function deleteUser(string $supabaseId): void
    {
        $url = rtrim((string) config('services.supabase.url'), '/').'/auth/v1/admin/users/'.$supabaseId;
        $key = config('services.supabase.service_role_key');

        Http::withHeaders(['apikey' => $key, 'Authorization' => 'Bearer '.$key])->delete($url);
    }
}
