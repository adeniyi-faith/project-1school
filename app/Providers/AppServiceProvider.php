<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // A server without PHP's fileinfo add-on would fail every upload; build the local disks without it
        \App\Support\SafeLocalDisk::register();

        // Sections that developers add as folders (resources/website-blocks/{type}/view.blade.php)
        \Illuminate\Support\Facades\View::addNamespace('siteblocks', resource_path('website-blocks'));

        // "exists:classes,id" and friends only accept the signed-in school's own rows (see TenantValidator)
        \Illuminate\Support\Facades\Validator::resolver(fn ($translator, $data, $rules, $messages, $attributes) => new \App\Support\TenantValidator($translator, $data, $rules, $messages, $attributes));

        // The platform owner can do everything
        // Stamp every audit-trail entry with the school it belongs to, so a school
        // only ever sees its own history.
        Activity::creating(function (Activity $activity) {
            if ($activity->properties?->has('school_id')) {
                return;
            }

            $schoolId = auth()->user()?->school_id
                ?? ($activity->subject instanceof Model ? ($activity->subject->school_id ?? null) : null);

            if ($schoolId) {
                $activity->properties = ($activity->properties ?? collect())->put('school_id', $schoolId);
            }
        });

        // The owner may do everything. Platform staff who are inside a school may do everything there (their own
        // `platform.*` powers never widen, and changes inside are blocked unless they hold `platform.schools.act`).
        Gate::before(function ($user, $ability) {
            if (\App\Support\PlatformAccess::isOwner($user)) {
                return true;
            }

            return is_string($ability) && ! str_starts_with($ability, 'platform.') && \App\Support\PlatformAccess::staffInsideSchool($user) ? true : null;
        });
    }
}
