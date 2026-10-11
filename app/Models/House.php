<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A sports house. Every student can belong to one; houses compete for points. */
class House extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = ['school_id', 'name', 'colour', 'motto', 'patron_id', 'captain_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function points(): HasMany
    {
        return $this->hasMany(HousePoint::class);
    }

    public function patron(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'patron_id');
    }

    public function captain(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'captain_id');
    }
}
