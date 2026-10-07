<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class School extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'logo', 'email', 'phone',
        'address', 'city', 'state', 'country',
        'timezone', 'currency', 'language',
        'settings', 'status',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (School $school) {
            if (empty($school->slug)) {
                $school->slug = Str::slug($school->name);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function currentAcademicYear()
    {
        return $this->academicYears()->where('is_current', true)->first();
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
