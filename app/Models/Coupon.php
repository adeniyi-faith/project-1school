<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'type', 'value', 'expires_at',
        'max_uses', 'used_count', 'is_active', 'description',
    ];

    protected $casts = [
        'expires_at' => 'date',
        'is_active'  => 'boolean',
        'value'      => 'decimal:2',
    ];

    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_uses > 0 && $this->used_count >= $this->max_uses) return false;
        return true;
    }
}
