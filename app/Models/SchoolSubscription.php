<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSubscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id', 'package_id', 'coupon_id', 'start_date', 'end_date',
        'status', 'is_trial', 'trial_ends_at', 'amount_paid', 'payment_method', 'notes',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'end_date'      => 'date',
        'trial_ends_at' => 'date',
        'is_trial'      => 'boolean',
        'amount_paid'   => 'decimal:2',
    ];

    public function school(): BelongsTo   { return $this->belongsTo(School::class); }
    public function package(): BelongsTo  { return $this->belongsTo(Package::class); }
    public function coupon(): BelongsTo   { return $this->belongsTo(Coupon::class); }
}
