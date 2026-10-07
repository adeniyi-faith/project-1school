<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HostelRoom extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $table = 'hostel_rooms';

    protected $fillable = [
        'school_id', 'hostel_id', 'room_no', 'floor', 'type',
        'capacity', 'occupied', 'ac', 'monthly_fee', 'status',
    ];

    protected $casts = [
        'ac'          => 'boolean',
        'monthly_fee' => 'decimal:2',
    ];

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(HostelAllocation::class, 'room_id');
    }

    public function getAvailableBedsAttribute(): int
    {
        return max(0, $this->capacity - $this->occupied);
    }
}
