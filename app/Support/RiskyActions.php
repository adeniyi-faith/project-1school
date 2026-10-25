<?php

namespace App\Support;

use App\Models\Package;
use App\Models\PlatformApproval;
use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * The platform actions that are hard to undo. For these, the `risky` middleware (ConfirmRiskyAction) asks for a
 * written reason, for the worst ones the typed name of what is being removed, and, when second approval is on,
 * a go-ahead from another person. To protect another action, add its route name here (and a plain sentence in
 * PlatformAudit::WORDS).
 */
class RiskyActions
{
    public const MIN_REASON = 10;

    /**
     * route name => what to call it, the route parameter that names the target, a short type, whether the
     * person must type the name, and whether it needs a second person's approval when that is switched on.
     */
    public const ACTIONS = [
        'super-admin.schools.destroy'       => ['label' => 'Delete a school',            'param' => 'school',       'type' => 'school',       'type_name' => true,  'second' => true],
        'super-admin.users.destroy'         => ['label' => 'Remove a login',             'param' => 'user',         'type' => 'user',         'type_name' => true,  'second' => true],
        'super-admin.staff.destroy'         => ['label' => 'Remove a platform staff member', 'param' => 'user',     'type' => 'staff',        'type_name' => true,  'second' => true],
        'super-admin.packages.destroy'      => ['label' => 'Delete a package',           'param' => 'package',      'type' => 'package',      'type_name' => false, 'second' => false],
        'super-admin.subscriptions.destroy' => ['label' => 'Remove a school plan',       'param' => 'subscription', 'type' => 'subscription', 'type_name' => false, 'second' => false],
    ];

    public static function enabled(): bool
    {
        return (bool) config('platform.risky_actions', true);
    }

    public static function secondApprovalOn(): bool
    {
        return (bool) config('platform.second_approval', false);
    }

    /** The rule for the request's route, or null when the action is not a risky one */
    public static function rule(?string $routeName): ?array
    {
        return $routeName !== null ? (self::ACTIONS[$routeName] ?? null) : null;
    }

    /** Must this person get a go-ahead from someone else? The owner never waits for anyone. */
    public static function needsApproval(array $rule, ?User $user): bool
    {
        return self::secondApprovalOn() && $rule['second'] && PlatformAccess::isStaff($user);
    }

    /** @return array{id: ?int, name: string} what the request is about */
    public static function target(array $rule, Request $request): array
    {
        $model = $request->route($rule['param']);

        return match (true) {
            $model instanceof School => ['id' => $model->id, 'name' => $model->name],
            $model instanceof User => ['id' => $model->id, 'name' => $model->name],
            $model instanceof Package => ['id' => $model->id, 'name' => $model->name],
            $model instanceof SchoolSubscription => ['id' => $model->id, 'name' => trim(($model->school?->name ?? 'A school').' plan')],
            default => ['id' => null, 'name' => ''],
        };
    }

    /** Typed names match without caring about capitals or extra spaces */
    public static function sameName(string $typed, string $name): bool
    {
        $clean = fn (string $s) => mb_strtolower(preg_replace('/\s+/', ' ', trim($s)));

        return $clean($typed) !== '' && $clean($typed) === $clean($name);
    }

    /** Can this approval still be used by this person for this target? Uses it up when it can. */
    public static function consume(User $user, string $action, ?int $targetId): ?PlatformApproval
    {
        $approval = PlatformApproval::where('requested_by', $user->id)->where('action', $action)
            ->where('target_id', $targetId)->where('status', 'approved')
            ->where('expires_at', '>', now())->latest('id')->first();

        if (! $approval) {
            return null;
        }

        // Only one request can take it
        $taken = PlatformApproval::whereKey($approval->id)->where('status', 'approved')->update(['status' => 'used', 'used_at' => now()]);

        return $taken ? $approval : null;
    }
}
