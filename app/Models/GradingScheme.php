<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A named grade scale, e.g. "WAEC (A1 – F9)". Its bands are GradeScale rows. */
class GradingScheme extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = ['school_id', 'name', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];

    public function bands(): HasMany
    {
        return $this->hasMany(GradeScale::class)->orderByDesc('min_marks');
    }
}
