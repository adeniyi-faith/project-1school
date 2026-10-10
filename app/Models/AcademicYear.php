<?php

namespace App\Models;

use App\Support\SchoolDefaults;
use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicYear extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id', 'name', 'start_date', 'end_date', 'is_current',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_current' => 'boolean',
    ];

    protected static function booted(): void
    {
        // A new school year comes with its terms (three unless the school set otherwise)
        static::created(fn (AcademicYear $year) => SchoolDefaults::createTermsFor($year->school_id, $year->id));
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class)->orderBy('sequence');
    }

    /**
     * When setting a year as current, unset all others for the same school.
     */
    public function makeCurrent(): void
    {
        static::where('school_id', $this->school_id)
            ->where('id', '!=', $this->id)
            ->update(['is_current' => false]);

        $this->update(['is_current' => true]);
    }
}
