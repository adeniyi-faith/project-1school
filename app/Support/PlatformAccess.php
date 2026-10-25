<?php

namespace App\Support;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The ONE place that answers "what may this member of the platform team do?".
 *
 * Two kinds of people work on the platform side:
 *  - the owner (role `super-admin`): may do everything, in every school;
 *  - platform staff (roles `platform-support`, `platform-finance` ...): each role holds a list of `platform.*`
 *    permissions, can be given extras one person at a time, and can be limited to chosen schools.
 *
 * New platform pages should use the `platform:<permission>` route middleware (it calls this class) and, when a
 * page lists or changes schools, `PlatformAccess::schools($user)` / `canSeeSchool()` so a person limited to some
 * schools never sees the rest.
 */
class PlatformAccess
{
    public const OWNER = 'super-admin';

    /**
     * Every platform permission, with a plain label and the group it is shown under on the staff page.
     * Add a new permission here and in a role below, then run the RolePermissionSeeder (deploys do).
     */
    public const PERMISSIONS = [
        'platform.dashboard.view' => ['Overview', 'See the platform dashboard (school counts, growth, revenue)'],
        'platform.schools.view'   => ['Schools', 'See schools and open a school\'s page'],
        'platform.schools.manage' => ['Schools', 'Create schools, edit them, suspend or reactivate them, add a school administrator'],
        'platform.schools.delete' => ['Schools', 'Delete a school'],
        'platform.schools.enter'  => ['Schools', 'Step inside a school to look around (nothing can be changed)'],
        'platform.schools.act'    => ['Schools', 'Make changes while inside a school'],
        'platform.users.view'     => ['Users', 'See the logins of schools'],
        'platform.users.manage'   => ['Users', 'Create school logins, reset passwords, suspend or remove them'],
        'platform.billing.view'   => ['Money', 'See packages, subscriptions and coupons'],
        'platform.billing.manage' => ['Money', 'Change packages, subscriptions and coupons'],
        'platform.modules.manage' => ['Platform', 'Switch modules on or off for schools'],
        'platform.settings.view'  => ['Platform', 'See platform settings'],
        'platform.settings.manage' => ['Platform', 'Change platform settings and AI settings'],
        'platform.content.manage' => ['Content', 'Newsletter email and website extensions'],
        'platform.staff.manage'   => ['Team', 'Add and change platform staff (owner only)'],
        'platform.audit.view'     => ['Team', 'See and export the log of what platform staff did'],
    ];

    /** The platform roles: label, what the role is for, and the permissions it starts with */
    public const ROLES = [
        'platform-support' => [
            'label' => 'Support',
            'about' => 'Helps schools. Sees schools and their logins, can look inside a school, can reset a password.',
            'permissions' => ['platform.dashboard.view', 'platform.schools.view', 'platform.schools.enter', 'platform.users.view', 'platform.users.manage'],
        ],
        'platform-finance' => [
            'label' => 'Finance',
            'about' => 'Looks after money. Sees schools and revenue, manages packages, subscriptions and coupons.',
            'permissions' => ['platform.dashboard.view', 'platform.schools.view', 'platform.billing.view', 'platform.billing.manage'],
        ],
        'platform-sales' => [
            'label' => 'Sales',
            'about' => 'Signs up schools. Can create and edit schools and see the packages on offer.',
            'permissions' => ['platform.dashboard.view', 'platform.schools.view', 'platform.schools.manage', 'platform.billing.view'],
        ],
        'platform-developer' => [
            'label' => 'Developer',
            'about' => 'Keeps the system healthy. Can look inside schools, switch modules, and see settings.',
            'permissions' => ['platform.dashboard.view', 'platform.schools.view', 'platform.schools.enter', 'platform.users.view', 'platform.modules.manage', 'platform.settings.view', 'platform.audit.view'],
        ],
        'platform-content' => [
            'label' => 'Content',
            'about' => 'Writes for the platform. Looks after newsletter email and website extensions.',
            'permissions' => ['platform.dashboard.view', 'platform.content.manage'],
        ],
    ];

    /**
     * What each platform page needs. The first matching route name wins. `scoped` pages work for a person limited to
     * some schools (the page itself must then show only their schools); every other page needs all-schools access.
     * A page not listed here is for the owner alone, so a new page is safe until someone decides who may use it.
     * A null permission means "any member of the platform team".
     *
     * @var array<string, array{0: ?string, 1: bool}>
     */
    public const PAGES = [
        'super-admin.dashboard'         => ['platform.dashboard.view', false],
        'super-admin.schools.index'     => ['platform.schools.view', true],
        'super-admin.schools.show'      => ['platform.schools.view', true],
        'super-admin.schools.enter'     => ['platform.schools.enter', true],
        'super-admin.schools.leave'     => [null, true],
        'super-admin.schools.destroy'   => ['platform.schools.delete', true],
        'super-admin.schools.create'    => ['platform.schools.manage', false],
        'super-admin.schools.store'     => ['platform.schools.manage', false],
        'super-admin.schools.*'         => ['platform.schools.manage', true],
        'super-admin.users.index'       => ['platform.users.view', true],
        'super-admin.users.*'           => ['platform.users.manage', true],
        'super-admin.packages.index'    => ['platform.billing.view', false],
        'super-admin.subscriptions.index' => ['platform.billing.view', false],
        'super-admin.coupons.index'     => ['platform.billing.view', false],
        'super-admin.packages.*'        => ['platform.billing.manage', false],
        'super-admin.subscriptions.*'   => ['platform.billing.manage', false],
        'super-admin.coupons.*'         => ['platform.billing.manage', false],
        'super-admin.module-manager.*'  => ['platform.modules.manage', false],
        'super-admin.settings.index'    => ['platform.settings.view', false],
        'super-admin.ai.index'          => ['platform.settings.view', false],
        'super-admin.settings.*'        => ['platform.settings.manage', false],
        'super-admin.ai.*'              => ['platform.settings.manage', false],
        'super-admin.newsletter.*'      => ['platform.content.manage', false],
        'super-admin.extensions.*'      => ['platform.content.manage', false],
        'super-admin.staff.*'           => ['platform.staff.manage', false],
        'super-admin.audit.*'           => ['platform.audit.view', false],
    ];

    /** What the page with this route name needs: [permission or null, works for scoped staff]. Null = owner only. */
    public static function requirement(?string $routeName): ?array
    {
        foreach (self::PAGES as $pattern => $rule) {
            if ($routeName !== null && \Illuminate\Support\Str::is($pattern, $routeName)) {
                return $rule;
            }
        }

        return null;
    }

    /** What a school-level role can be given from the Users page (platform and owner roles are managed on the Platform Staff page) */
    public static function isPlatformRoleName(?string $role): bool
    {
        return $role === self::OWNER || isset(self::ROLES[$role]);
    }

    public static function roleNames(): array
    {
        return array_keys(self::ROLES);
    }

    /** The owner of the platform */
    public static function isOwner(?User $user): bool
    {
        return $user !== null && $user->hasRole(self::OWNER);
    }

    /** A member of the platform team who is not the owner */
    public static function isStaff(?User $user): bool
    {
        return $user !== null && ! self::isOwner($user) && $user->hasAnyRole(self::roleNames());
    }

    /** The owner or a member of the platform team (anyone who may use the /super-admin side) */
    public static function isPlatform(?User $user): bool
    {
        return self::isOwner($user) || self::isStaff($user);
    }

    /** May this person do this platform thing? The owner may do everything. */
    public static function can(?User $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        return self::isOwner($user) || (self::isStaff($user) && $user->hasPermissionTo($permission));
    }

    /** True when the person can reach only the schools they were assigned (never the owner) */
    public static function isScoped(?User $user): bool
    {
        return self::isStaff($user) && $user->platformStaff?->school_scope === 'assigned';
    }

    /** The ids of the schools this person may reach, or null for "every school" */
    public static function schoolIds(User $user): ?array
    {
        if (! self::isScoped($user)) {
            return null;
        }

        return \DB::table('platform_staff_schools')->where('user_id', $user->id)->pluck('school_id')->map(fn ($id) => (int) $id)->all();
    }

    public static function canSeeSchool(User $user, School|int $school): bool
    {
        $ids = self::schoolIds($user);

        return $ids === null || in_array($school instanceof School ? $school->id : $school, $ids, true);
    }

    /** Schools this person may reach, as a query (all of them for the owner and for unlimited staff) */
    public static function schools(User $user): Builder
    {
        $ids = self::schoolIds($user);

        return School::query()->when($ids !== null, fn (Builder $q) => $q->whereIn('schools.id', $ids));
    }

    /** True while platform staff (not the owner) are inside a school */
    public static function staffInsideSchool(?User $user): bool
    {
        return self::isStaff($user) && ActingSchool::id() !== null;
    }

    /** Where this person lands after signing in */
    public static function homeRoute(User $user): string
    {
        return match (true) {
            self::can($user, 'platform.dashboard.view') && ! self::isScoped($user) => 'super-admin.dashboard',
            self::can($user, 'platform.schools.view') => 'super-admin.schools.index',
            self::can($user, 'platform.billing.view') => 'super-admin.subscriptions.index',
            self::can($user, 'platform.content.manage') => 'super-admin.newsletter.index',
            default => 'profile',
        };
    }

    /** "Support" for platform-support, "Super Admin" for the owner; used in banners and the staff list */
    public static function roleLabel(?string $role): string
    {
        return match (true) {
            $role === self::OWNER => 'Super Admin',
            isset(self::ROLES[$role]) => self::ROLES[$role]['label'],
            default => ucfirst(str_replace('-', ' ', (string) $role)),
        };
    }
}
