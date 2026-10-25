<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use App\Support\DemoSchool;
use App\Support\NavAccess;
use App\Support\PlatformAccess;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * The role the menu is built for. Platform staff get the platform menu ('platform-staff'), or, while they are
     * inside a school, the whole school menu (shown as the owner's).
     */
    private function roleFor(\App\Models\User $user): ?string
    {
        if (PlatformAccess::isOwner($user)) {
            return 'super-admin';
        }
        if (PlatformAccess::isStaff($user)) {
            return PlatformAccess::staffInsideSchool($user) ? 'super-admin' : 'platform-staff';
        }

        return $user->roles()->first()?->name;
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id'        => $request->user()->id,
                    'name'      => $request->user()->name,
                    'email'     => $request->user()->email,
                    'avatar'    => $request->user()->avatar ?? null,
                    'role'      => $this->roleFor($request->user()),
                    'school_id' => $request->user()->school_id ?? null,
                ] : null,
                // What this person may do: page buttons check `permissions`, the menu checks `paths`
                'permissions' => fn () => $request->user() ? NavAccess::permissions($request->user()) : [],
                'paths' => fn () => $request->user() ? NavAccess::paths($request->user()) : [],
                // Numbers shown beside menu items that have something waiting (page address => count)
                'badges' => fn () => $request->user() ? \App\Support\NavBadges::for($request->user()) : [],
            ],
            // The school this person works in (shown at the top of the side menu)
            'school' => fn () => ($id = \App\Support\ActingSchool::id() ?? $request->user()?->school_id)
                ? \App\Models\School::withoutGlobalScopes()->whereKey($id)->first(['id', 'name', 'logo'])?->only(['id', 'name', 'logo_url'])
                : null,
            // Set while the platform owner is working inside one school
            'acting_school' => fn () => ($id = \App\Support\ActingSchool::id())
                ? [
                    'id' => $id,
                    'name' => \App\Models\School::whereKey($id)->value('name'),
                    // Who is inside ("Super Admin", "Support" ...) and whether they may change things there
                    'as' => PlatformAccess::roleLabel($request->user()->roles->pluck('name')->first(fn ($r) => PlatformAccess::isPlatformRoleName($r))),
                    'read_only' => PlatformAccess::isStaff($request->user()) && ! PlatformAccess::can($request->user(), 'platform.schools.act'),
                ]
                : null,
            // Set for members of the platform team (not the owner): their role and whether they are limited to some schools
            'platform' => fn () => PlatformAccess::isStaff($request->user())
                ? ['role' => PlatformAccess::roleLabel($request->user()->roles->pluck('name')->first(fn ($r) => PlatformAccess::isPlatformRoleName($r))), 'scoped' => PlatformAccess::isScoped($request->user())]
                : null,
            // Set for platform people: do staff's risky actions wait for a second person?
            'risky' => fn () => PlatformAccess::isStaff($request->user())
                ? ['approval' => \App\Support\RiskyActions::enabled() && \App\Support\RiskyActions::secondApprovalOn()]
                : null,
            // Set while someone is looking around the public demo: the bar on top offers to switch account type
            'demo' => fn () => DemoSchool::isDemoUser($request->user())
                ? ['label' => DemoSchool::ACCOUNTS[DemoSchool::keyOf($request->user())][1]]
                : null,
            'flash' => [
                'success' => fn () => session('success'),
                'error'   => fn () => session('error'),
                'info'    => fn () => session('info'),
                // New login details shown once on screen (never stored); see LoginDetailsDialog
                'login_details' => fn () => session('login_details'),
            ],
            'faviconUrl' => fn () => once(function () {
                $path = PlatformSetting::get('platform_favicon');
                return $path ? asset('storage/' . $path) : null;
            }),
        ];
    }
}
