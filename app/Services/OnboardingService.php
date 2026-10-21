<?php

namespace App\Services;

use App\Models\OnboardingNudge;
use App\Models\School;
use App\Models\SchoolNotification;
use App\Models\User;
use App\Support\DemoSchool;
use App\Support\SetupChecklist;
use Illuminate\Mail\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Which schools have stalled while setting up, why, and the nudge that asks them if they need a hand */
class OnboardingService
{
    public const QUIET_DAYS = 7;
    public const NEVER_SIGNED_IN_DAYS = 3;
    public const INCOMPLETE_DAYS = 14;
    public const NUDGE_GAP_DAYS = 3;

    public const REASONS = [
        'never_signed_in' => 'Nobody has signed in yet',
        'quiet'           => 'No sign-in for 7 days',
        'incomplete'      => 'Setup still incomplete after 2 weeks',
    ];

    /** One row per active school (the public demo school is left out) */
    public function rows(): Collection
    {
        $schools = School::where('status', 'active')->where('slug', '!=', DemoSchool::SLUG)->orderBy('name')->get();
        $lastLogin = User::whereIn('school_id', $schools->pluck('id'))->whereNotNull('last_login_at')->selectRaw('school_id, max(last_login_at) as last_at')->groupBy('school_id')->pluck('last_at', 'school_id');
        $lastNudge = OnboardingNudge::withoutGlobalScopes()->selectRaw('school_id, max(created_at) as at')->groupBy('school_id')->pluck('at', 'school_id');

        return $schools->map(function (School $s) use ($lastLogin, $lastNudge) {
            // The same list the school's own dashboard shows, so both sides agree on what is left
            $list = SetupChecklist::for($s->id);
            $missing = collect($list['items'])->reject(fn ($i) => $i['done'])->pluck('title')->values()->all();
            $age = (int) $s->created_at->diffInDays(now());
            $last = isset($lastLogin[$s->id]) ? \Carbon\Carbon::parse($lastLogin[$s->id]) : null;
            $nudged = isset($lastNudge[$s->id]) ? \Carbon\Carbon::parse($lastNudge[$s->id]) : null;

            $reasons = [];
            if (! $last && $age >= self::NEVER_SIGNED_IN_DAYS) {
                $reasons[] = 'never_signed_in';
            } elseif ($last && $age >= self::QUIET_DAYS && $last->lt(now()->subDays(self::QUIET_DAYS))) {
                $reasons[] = 'quiet';
            }
            if ($age >= self::INCOMPLETE_DAYS && $missing !== []) {
                $reasons[] = 'incomplete';
            }

            return [
                'id' => $s->id, 'name' => $s->name, 'age_days' => $age,
                'done' => $list['done'], 'total' => $list['total'],
                'missing' => $missing,
                'last_login_at' => $last?->toIso8601String(),
                'reasons' => $reasons, 'stuck' => $reasons !== [],
                'nudged_at' => $nudged?->toIso8601String(),
                'can_nudge' => ! $nudged || $nudged->lt(now()->subDays(self::NUDGE_GAP_DAYS)),
            ];
        });
    }

    public function defaultMessage(School $school, array $missing): string
    {
        $text = "Hello! We noticed {$school->name} has not finished setting up on the platform, and we would be glad to help.";
        if ($missing) {
            $text .= ' Still to do: '.implode('; ', array_map('lcfirst', array_slice($missing, 0, 5))).'.';
        }

        return $text.' Open Help and support in your menu and tell us where you are stuck, or reply to this email.';
    }

    /** Bell notice and email to the school's admins and principals */
    public function nudge(School $school, User $by, string $message): OnboardingNudge
    {
        $people = User::where('school_id', $school->id)->where('status', 'active')->role(['school-admin', 'principal'])->get(['id', 'school_id', 'email']);
        $bell = $mail = $failed = 0;

        foreach ($people as $u) {
            SchoolNotification::withoutGlobalScopes()->create([
                'school_id' => $school->id, 'user_id' => $u->id, 'type' => 'platform',
                'title' => 'Need a hand getting set up?', 'body' => $message, 'channel' => 'in-app',
            ]);
            $bell++;
        }

        foreach ($people->filter(fn (User $u) => filled($u->email))->unique('email') as $u) {
            try {
                Mail::raw($message, fn (Message $m) => $m->to($u->email)->subject('Need a hand getting set up?'));
                $mail++;
            } catch (Throwable) {
                $failed++;
            }
        }

        return OnboardingNudge::withoutGlobalScopes()->create([
            'school_id' => $school->id, 'sent_by' => $by->id, 'message' => $message,
            'bell_count' => $bell, 'email_count' => $mail, 'email_failed' => $failed,
        ]);
    }
}
