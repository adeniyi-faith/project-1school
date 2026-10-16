<?php

namespace App\Website;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * The safety kit for anything a stranger can post on a school's website (sign-up forms now, other forms and the chat
 * later). Written once so every public form behaves the same:
 *
 *  1. a hidden box real people never see (a robot that fills it in is thanked and ignored),
 *  2. a signed ticket the form carries, so a post only works if the form was loaded from our page a moment ago,
 *  3. a limit per visitor address per hour,
 *  4. a limit per school per day, so one school can never be flooded.
 */
class Visitor
{
    public const TRAP = 'company_site';
    public const TICKET = 'form_ticket';

    /** The answers of inspect() */
    public const OK = 'ok';
    public const ROBOT = 'robot';          // trap filled in: pretend all is well, do nothing
    public const RELOAD = 'reload';        // no ticket, a forged or old one, or posted faster than a person can
    public const SLOW_DOWN = 'slow_down';  // this visitor has posted too often
    public const BUSY = 'busy';            // this school has reached its daily limit

    /** A person needs a moment to fill a form; faster than this is a robot. Older than MAX_AGE means the page sat open too long. */
    public const MIN_AGE = 2;
    public const MAX_AGE = 7200;

    /** A ticket the form carries, signed so it cannot be made up. `$issuedAt` is only for tests. */
    public static function ticket(int $schoolId, string $kind, ?int $issuedAt = null): string
    {
        return Crypt::encryptString(json_encode(['s' => $schoolId, 'k' => $kind, 't' => $issuedAt ?? Carbon::now()->timestamp]));
    }

    /**
     * Decide what to do with a post. Always call this before doing any work for a visitor.
     *
     * @param int $perVisitorPerHour how many times one address may post this kind of form in an hour
     * @param int $schoolPerDay how many posts of this kind one school accepts in a day
     */
    public static function inspect(Request $request, int $schoolId, string $kind, int $perVisitorPerHour = 5, int $schoolPerDay = 300): string
    {
        if ($request->filled(self::TRAP)) {
            return self::ROBOT;
        }

        if (! self::ticketOk((string) $request->input(self::TICKET), $schoolId, $kind)) {
            return self::RELOAD;
        }

        $key = "visitor:{$kind}:{$schoolId}:".$request->ip();
        if (RateLimiter::tooManyAttempts($key, $perVisitorPerHour)) {
            return self::SLOW_DOWN;
        }
        RateLimiter::hit($key, 3600);

        $dayKey = "visitor-day:{$kind}:{$schoolId}:".Carbon::now()->toDateString();
        Cache::add($dayKey, 0, now()->addDay());
        if (Cache::increment($dayKey) > $schoolPerDay) {
            return self::BUSY;
        }

        return self::OK;
    }

    /** What to tell the visitor for each answer other than OK and ROBOT */
    public static function message(string $verdict): string
    {
        return match ($verdict) {
            self::SLOW_DOWN => 'You have tried several times. Please wait a little and try again later.',
            self::BUSY => 'We are getting a lot of messages today. Please try again tomorrow.',
            default => 'This page was open for too long, or was sent too quickly. Please try again.',
        };
    }

    private static function ticketOk(string $ticket, int $schoolId, string $kind): bool
    {
        if ($ticket === '') {
            return false;
        }
        try {
            $data = json_decode(Crypt::decryptString($ticket), true);
        } catch (Throwable) {
            return false;
        }
        if (! is_array($data) || ($data['s'] ?? null) !== $schoolId || ($data['k'] ?? null) !== $kind) {
            return false;
        }
        $age = Carbon::now()->timestamp - (int) ($data['t'] ?? 0);

        return $age >= self::MIN_AGE && $age <= self::MAX_AGE;
    }
}
