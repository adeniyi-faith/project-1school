<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A saved way of splitting a fee into parts, e.g. 60% at resumption and 40% by mid-term */
class PaymentPlan extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = ['school_id', 'name', 'description', 'parts', 'is_active'];

    protected $casts = [
        'parts' => 'array',
        'is_active' => 'boolean',
    ];
}
