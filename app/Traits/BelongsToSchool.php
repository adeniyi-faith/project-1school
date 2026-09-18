<?php

namespace App\Traits;

use App\Models\School;
use App\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Apply to every school-scoped model.
 * Automatically:
 *   - Adds a global WHERE school_id = ? scope (bypassed for super-admin)
 *   - Sets school_id on create from the authenticated user
 *   - Provides a ->school() relation
 */
trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        static::addGlobalScope(new SchoolScope());

        static::creating(function ($model) {
            if (empty($model->school_id) && auth()->check()) {
                $model->school_id = auth()->user()->school_id;
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
