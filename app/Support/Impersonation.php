<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * "Log in as this user": a platform person sees the app exactly as one school login sees it, for a short time.
 * The browser is signed in as that login; who really sits behind it is kept in the session, together with when the
 * time runs out and whether changes are allowed. Read-only unless the person chose otherwise and may make changes
 * inside schools. Every start, stop, page and change is written to the staff activity log under the real person.
 */
class Impersonation
{
    public const KEY = 'impersonation';

    public const MINUTES = [15, 30, 60];

    /** @return array{by: int, user: int, until: int, read_only: bool, reason: string, back: ?string}|null */
    public static function current(): ?array
    {
        if (! app()->bound('session.store')) {
            return null;
        }
        $data = session(self::KEY);

        return is_array($data) && isset($data['by'], $data['user'], $data['until']) ? $data : null;
    }

    public static function active(): bool
    {
        return self::current() !== null;
    }

    public static function realUser(): ?User
    {
        $data = self::current();

        return $data ? User::find($data['by']) : null;
    }

    /** May this platform person sign in as this login? Returns the reason it is not allowed, or null. */
    public static function refusal(User $by, User $target): ?string
    {
        return match (true) {
            ! PlatformAccess::can($by, 'platform.users.impersonate') => 'You are not allowed to sign in as other people.',
            $target->id === $by->id => 'That is your own account.',
            PlatformAccess::isPlatform($target) => 'You cannot sign in as a member of the platform team.',
            ! $target->school_id => 'Only school logins can be opened this way.',
            ! PlatformAccess::canSeeSchool($by, (int) $target->school_id) => 'That login belongs to a school you do not look after.',
            $target->status !== 'active' => 'That login is switched off.',
            default => null,
        };
    }

    /** Whether this person may make changes while signed in as someone else (owner, or staff who may act inside schools) */
    public static function mayAct(User $by): bool
    {
        return PlatformAccess::isOwner($by) || PlatformAccess::can($by, 'platform.schools.act');
    }

    public static function start(Request $request, User $by, User $target, int $minutes, bool $readOnly, string $reason): void
    {
        $back = url()->previous();
        Auth::login($target);
        $request->session()->put(self::KEY, [
            'by' => $by->id, 'user' => $target->id, 'until' => now()->addMinutes($minutes)->getTimestamp(),
            'read_only' => $readOnly, 'reason' => $reason, 'back' => $back,
        ]);
    }

    /** Signs the real person back in. Returns where to send them. */
    public static function stop(Request $request, string $why = 'Stopped signing in as someone else'): string
    {
        $data = self::current();
        $request->session()->forget(self::KEY);
        $by = $data ? User::find($data['by']) : null;
        $target = $data ? User::find($data['user']) : null;

        if (! $by || ! PlatformAccess::isPlatform($by) || $by->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return route('login');
        }

        Auth::login($by);
        PlatformAudit::record('impersonate_stop', $why.($target ? ": {$target->name} ({$target->email})" : ''),
            ['user' => $by, 'school_id' => $target?->school_id, 'details' => ['target_user_id' => $target?->id]], $request);

        $back = $data['back'] ?? null;

        return $back && str_contains($back, '/super-admin') ? $back : route('super-admin.users.index');
    }
}
