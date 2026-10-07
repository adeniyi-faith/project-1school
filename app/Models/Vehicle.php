<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'registration_no', 'name', 'type', 'capacity',
        'driver_name', 'driver_phone', 'helper_name',
        'last_lat', 'last_lng', 'last_location_at', 'status',
    ];

    protected $casts = [
        'last_lat'          => 'decimal:7',
        'last_lng'          => 'decimal:7',
        'last_location_at'  => 'datetime',
    ];

    public function routes(): HasMany
    {
        return $this->hasMany(TransportRoute::class);
    }
}
