<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'isbn', 'title', 'author', 'category', 'publisher',
        'publication_year', 'location', 'total_copies', 'available_copies',
        'cover', 'description', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function issues(): HasMany
    {
        return $this->hasMany(BookIssue::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(BookReservation::class);
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->available_copies > 0;
    }
}
