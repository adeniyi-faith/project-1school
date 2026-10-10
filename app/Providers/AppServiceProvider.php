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

        Gate::before(fn ($user) => $user->hasRole('super-admin') ? true : null);
    }
}
