<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportRoute extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $table = 'routes';

    protected $fillable = [
        'school_id', 'vehicle_id', 'name', 'start_point', 'end_point',
        'stops', 'monthly_fee', 'is_active',
    ];

    protected $casts = [
        'stops'       => 'array',
        'monthly_fee' => 'decimal:2',
        'is_active'   => 'boolean',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function students(): BelongsToMany
    {
        // Explicit FK because model name 'TransportRoute' would auto-generate 'transport_route_id'
        // but the actual pivot column is 'route_id'
        return $this->belongsToMany(Student::class, 'student_route', 'route_id', 'student_id')
            ->withPivot('stop', 'fee_linked')
            ->withTimestamps();
    }
}
