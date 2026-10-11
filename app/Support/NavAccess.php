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
    /** @var array<string, array{permissions: string[], roles: string[]}>|null */
    private static ?array $rules = null;

    /** Every GET page without a placeholder in its address, with what it needs */
    public static function rules(): array
    {
        return self::$rules ??= collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => in_array('GET', $r->methods(), true) && ! str_contains($r->uri(), '{')
                && (str_starts_with($r->uri(), 'school') || str_starts_with($r->uri(), 'super-admin')))
            ->mapWithKeys(function ($r) {
                $permissions = $roles = [];
                foreach ($r->gatherMiddleware() as $m) {
                    if (! is_string($m)) {
                        continue;
                    }
                    if (str_starts_with($m, 'permission:')) {
                        $permissions[] = explode('|', substr($m, 11));
                    } elseif (str_starts_with($m, 'role:')) {
                        $roles[] = explode('|', substr($m, 5));
                    }
                }

                return ['/'.$r->uri() => ['permissions' => $permissions, 'roles' => $roles]];
            })->all();
    }

    /** The page addresses this person may open */
    public static function paths(User $user): array
    {
        $roles = $user->getRoleNames()->all();

        return collect(self::rules())
            ->filter(function (array $rule) use ($user, $roles) {
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

    /** The permissions this person holds, for showing or hiding buttons on a page */
    public static function permissions(User $user): array
    {
        return $user->hasRole('super-admin') ? ['*'] : $user->getAllPermissions()->pluck('name')->values()->all();
    }
}
