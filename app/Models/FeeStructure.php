<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStructure extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'class_id', 'fee_category_id', 'academic_year',
        'amount', 'due_date', 'frequency', 'description', 'is_active',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'due_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }
}
