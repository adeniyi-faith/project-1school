<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'name', 'max_days_per_year', 'is_paid', 'description', 'is_active',
    ];

    protected $casts = [
        'is_paid'   => 'boolean',
        'is_active' => 'boolean',
    ];

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
