<?php

namespace App\Support;

use App\Models\PlatformAuditLog;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The record of what the platform team does. `record()` writes one line; the `AuditPlatformActions` middleware writes
 * one for every change a platform person makes and every page staff open inside a school.
 * Only field NAMES are kept for what a person typed (never passwords or keys); the values that models change are
 * kept from the activity log, which never holds passwords either.
 */
class PlatformAudit
{
    /** Field names that are never written down, even as names */
    private const SECRET = '/pass|token|secret|_key$|^key$|otp|code$/i';

    /** @var array<int, array{what: string, fields: array<string, array{from: ?string, to: ?string}>}> */
    private static array $changes = [];

    public static function enabled(): bool
    {
        return (bool) config('platform.audit', true);
    }

    /** Plain words for the routes platform people use. A route not listed reads "Changed something (route name)". */
    public const WORDS = [
        'super-admin.schools.enter'          => 'Entered a school',
        'super-admin.schools.leave'          => 'Left the school',
        'super-admin.schools.store'          => 'Created a school',
        'super-admin.schools.update'         => 'Edited a school',
        'super-admin.schools.destroy'        => 'Deleted a school',
        'super-admin.schools.suspend'        => 'Suspended a school',
        'super-admin.schools.activate'       => 'Reactivated a school',
        'super-admin.schools.admins.add'     => 'Added a school administrator',
        'super-admin.users.store'            => 'Created a login',
        'super-admin.users.update'           => 'Changed a login',
        'super-admin.users.destroy'          => 'Removed a login',
        'super-admin.users.suspend'          => 'Switched off a login',
        'super-admin.users.activate'         => 'Switched on a login',
        'super-admin.users.reset-password'   => 'Reset a password',
        'super-admin.staff.store'            => 'Added a platform staff member',
        'super-admin.staff.update'           => 'Changed a platform staff member',
        'super-admin.staff.suspend'          => 'Switched off a platform staff member',
        'super-admin.staff.activate'         => 'Switched on a platform staff member',
        'super-admin.staff.reset-password'   => 'Reset a platform staff password',
        'super-admin.staff.destroy'          => 'Removed a platform staff member',
        'super-admin.packages.store'         => 'Created a package',
        'super-admin.packages.update'        => 'Edited a package',
        'super-admin.packages.destroy'       => 'Deleted a package',
        'super-admin.subscriptions.store'    => 'Gave a school a plan',
        'super-admin.subscriptions.update'   => 'Changed a school plan',
        'super-admin.subscriptions.destroy'  => 'Removed a school plan',
        'super-admin.coupons.store'          => 'Created a coupon',
        'super-admin.coupons.update'         => 'Edited a coupon',
        'super-admin.coupons.destroy'        => 'Deleted a coupon',
        'super-admin.approvals.approve'      => 'Approved a risky action',
        'super-admin.approvals.deny'         => 'Turned down a risky action',
        'super-admin.module-manager.toggle'  => 'Switched a module',
        'super-admin.module-manager.bulk'    => 'Changed a school\'s modules',
    ];

    /** Write one line. Does nothing when auditing is switched off for this environment. */
    public static function record(string $action, string $description, array $extra = [], ?Request $request = null): ?PlatformAuditLog
    {
        if (! self::enabled()) {
            return null;
        }

        $request ??= request();
        $user = $extra['user'] ?? $request->user();
        $schoolId = $extra['school_id'] ?? ($user ? ActingSchool::id() : null);

        return PlatformAuditLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'role' => $user ? self::roleOf($user) : null,
            'action' => $action,
            'description' => Str::limit($description, 480, ''),
            'acting_school_id' => $schoolId,
            'acting_school_name' => $schoolId ? School::whereKey($schoolId)->value('name') : null,
            'method' => $request->method(),
            'route' => $request->route()?->getName(),
            'path' => Str::limit('/'.ltrim($request->path(), '/'), 480, ''),
            'status' => $extra['status'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'details' => $extra['details'] ?? null,
        ]);
    }

    public static function roleOf(User $user): string
    {
        $name = $user->roles->pluck('name')->first(fn ($r) => PlatformAccess::isPlatformRoleName($r)) ?? 'platform';

        return PlatformAccess::roleLabel($name);
    }

    /** Names of the fields in a request, without anything that looks like a secret */
    public static function fieldNames(Request $request): array
    {
        return collect(array_keys($request->except(['_token', '_method'])))
            ->reject(fn ($k) => preg_match(self::SECRET, (string) $k))
            ->take(40)->values()->all();
    }

    /** The activity log tells us what a model changed during this request; called for each new entry */
    public static function noteChange(object $activity): void
    {
        if (! self::enabled() || ! PlatformAccess::isPlatform(auth()->user())) {
            return;
        }

        $props = $activity->properties?->toArray() ?? [];
        $new = $props['attributes'] ?? [];
        $old = $props['old'] ?? [];
        $fields = [];
        foreach ($new as $key => $value) {
            if (preg_match(self::SECRET, (string) $key)) {
                continue;
            }
            $fields[$key] = ['from' => self::short($old[$key] ?? null), 'to' => self::short($value)];
        }

        if (count(self::$changes) < 40) {
            self::$changes[] = ['what' => (string) $activity->description, 'fields' => array_slice($fields, 0, 25, true)];
        }
    }

    /** Take what was collected during this request (and forget it) */
    public static function takeChanges(): array
    {
        $out = self::$changes;
        self::$changes = [];

        return $out;
    }

    private static function short(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Str::limit(is_scalar($value) ? (string) $value : json_encode($value), 120);
    }

    /** The sentence for a request: a plain-words name for the route, with the school or login it touched, and the reason when one was given */
    public static function describe(Request $request): string
    {
        $name = $request->route()?->getName();
        $base = self::WORDS[$name] ?? null;
        $school = $request->route('school');
        $target = $request->route('user');
        $named = (string) $request->attributes->get('audit_target', '');
        $reason = trim((string) $request->attributes->get('audit_reason', ''));
        $approvedBy = (string) $request->attributes->get('audit_approved_by', '');

        if ($base) {
            $text = match (true) {
                $school instanceof School => "{$base}: {$school->name}",
                $target instanceof User => "{$base}: {$target->name}",
                $named !== '' => "{$base}: {$named}",
                default => $base,
            };
        } else {
            $what = $name ?: $request->path();
            $text = $request->isMethodSafe() ? "Opened {$what}" : "Changed something ({$what})";
        }

        if ($request->attributes->get('audit_action') === 'approval_requested') {
            $text = 'Asked for approval to do this: '.$text;
        }
        if ($approvedBy !== '') {
            $text .= " (approved by {$approvedBy})";
        }
        if ($reason !== '') {
            $text .= ' — reason: '.Str::limit($reason, 200);
        }

        return $text;
    }
}
