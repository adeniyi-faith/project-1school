<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A named discount policy, e.g. "Staff child – 50% of tuition" or "Sibling discount – ₦20,000". */
class Scholarship extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = ['school_id', 'name', 'type', 'value', 'fee_category_id', 'description', 'is_active'];

    protected $casts = ['value' => 'float', 'is_active' => 'boolean'];

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }

    public function awards(): HasMany
    {
        return $this->hasMany(StudentScholarship::class);
    }

    /** Does this scholarship cover this fee? Empty category means every fee. */
    public function covers(FeeStructure $structure): bool
    {
        return $this->fee_category_id === null || $this->fee_category_id === $structure->fee_category_id;
    }

    /** The discount on an invoice of this amount, never more than the amount itself. */
    public function discountOn(float $amount): float
    {
        $discount = $this->type === 'percent' ? $amount * $this->value / 100 : $this->value;

        return round(min($discount, $amount), 2);
    }
}
