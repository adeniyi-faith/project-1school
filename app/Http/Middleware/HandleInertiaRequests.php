<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use App\Support\DemoSchool;
use App\Support\NavAccess;
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
                    'role'      => $request->user()->hasRole('super-admin') ? 'super-admin' : ($request->user()->roles()->first()?->name ?? null),
                    'school_id' => $request->user()->school_id ?? null,
                ] : null,
                // What this person may do: page buttons check `permissions`, the menu checks `paths`
                'permissions' => fn () => $request->user() ? NavAccess::permissions($request->user()) : [],
                'paths' => fn () => $request->user() ? NavAccess::paths($request->user()) : [],
            ],
            // Set while the platform owner is working inside one school
            'acting_school' => fn () => ($id = \App\Support\ActingSchool::id())
                ? ['id' => $id, 'name' => \App\Models\School::whereKey($id)->value('name')]
                : null,
            // Set while someone is looking around the public demo: the bar on top offers to switch account type
            'demo' => fn () => DemoSchool::isDemoUser($request->user())
                ? ['label' => DemoSchool::ACCOUNTS[DemoSchool::keyOf($request->user())][1]]
                : null,
            'flash' => [
                'success' => fn () => session('success'),
                'error'   => fn () => session('error'),
                'info'    => fn () => session('info'),
            ],
            'faviconUrl' => fn () => once(function () {
                $path = PlatformSetting::get('platform_favicon');
                return $path ? asset('storage/' . $path) : null;
            }),
        ];
    }
}
