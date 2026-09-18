<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorLog extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'name', 'phone', 'purpose',
        'person_to_meet', 'time_in', 'time_out', 'remarks',
    ];

    protected $casts = [
        'time_in'  => 'datetime',
        'time_out' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
