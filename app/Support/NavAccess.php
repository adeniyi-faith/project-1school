<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Which pages a person may open, worked out from the routes' own `permission:` and `role:` checks.
 * The side menu, the phone tab bar and page buttons use it, so they never offer a page that would answer 403.
 */
class NavAccess
{
    /** @var array<string, array{permissions: string[], roles: string[], platform: array|string|null}>|null */
    private static ?array $rules = null;

    /** Every GET page without a placeholder in its address, with what it needs */
    public static function rules(): array
    {
        return self::$rules ??= collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => in_array('GET', $r->methods(), true) && ! str_contains($r->uri(), '{')
                && (str_starts_with($r->uri(), 'school') || str_starts_with($r->uri(), 'super-admin')))
            ->mapWithKeys(function ($r) {
                $permissions = $roles = [];
                $platform = false;
                foreach ($r->gatherMiddleware() as $m) {
                    if (! is_string($m)) {
                        continue;
                    }
                    if ($m === 'platform') {
                        $platform = true;
                    }
                    if (str_starts_with($m, 'permission:')) {
                        $permissions[] = explode('|', substr($m, 11));
                    } elseif (str_starts_with($m, 'role:')) {
                        $roles[] = explode('|', substr($m, 5));
                    }
                }

                // Platform pages are decided by PlatformAccess::PAGES (a page not listed there is for the owner alone)
                return ['/'.$r->uri() => ['permissions' => $permissions, 'roles' => $roles, 'platform' => $platform ? (PlatformAccess::requirement($r->getName()) ?? 'owner') : null]];
            })->all();
    }

    /** The page addresses this person may open */
    public static function paths(User $user): array
    {
        $roles = $user->getRoleNames()->all();
        // Platform staff who are inside a school are treated as the owner there, so they get that school's whole menu
        if (PlatformAccess::staffInsideSchool($user)) {
            $roles[] = PlatformAccess::OWNER;
        }

        return collect(self::rules())
            ->filter(function (array $rule) use ($user, $roles) {
                if ($rule['platform'] !== null) {
                    return self::platformPageAllowed($user, $rule['platform']);
                }
                foreach ($rule['roles'] as $anyOf) {
                    if (! array_intersect($anyOf, $roles)) {
                        return false;
                    }
                }
                foreach ($rule['permissions'] as $anyOf) {
                    if (! collect($anyOf)->contains(fn ($p) => $user->can($p))) {
                        return false;
                    }
                }

                return true;
            })
            ->keys()->values()->all();
    }

    private static function platformPageAllowed(User $user, array|string $need): bool
    {
        if (PlatformAccess::isOwner($user)) {
            return true;
        }
        if ($need === 'owner' || ! PlatformAccess::isStaff($user)) {
            return false;
        }
        [$permission, $scopedOk] = $need;

        return ($permission === null || PlatformAccess::can($user, $permission)) && (! PlatformAccess::isScoped($user) || $scopedOk);
    }

    /** The permissions this person holds, for showing or hiding buttons on a page */
    public static function permissions(User $user): array
    {
        // The owner, and platform staff while inside a school, can do everything there
        if ($user->hasRole('super-admin') || PlatformAccess::staffInsideSchool($user)) {
            return ['*'];
        }

        return $user->getAllPermissions()->pluck('name')->values()->all();
    }
}
