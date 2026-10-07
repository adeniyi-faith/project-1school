<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'registration_no', 'name', 'type', 'capacity',
        'driver_name', 'driver_phone', 'helper_name',
        'last_lat', 'last_lng', 'last_location_at', 'status', 'tracking_token',
    ];

    // The GPS secret must never be sent to the browser
    protected $hidden = ['tracking_token'];

    protected static function booted(): void
    {
        static::creating(function (Vehicle $vehicle) {
            $vehicle->tracking_token ??= Str::random(40);
        });
    }

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
