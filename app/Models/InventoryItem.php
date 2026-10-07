<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'category_id', 'name', 'unit',
        'current_stock', 'minimum_stock', 'description', 'is_active',
    ];

    protected $casts = [
        'current_stock' => 'decimal:2',
        'minimum_stock' => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(InventoryPurchase::class, 'item_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(InventoryIssue::class, 'item_id');
    }

    public function isLowStock(): bool
    {
        return (float) $this->current_stock <= (float) $this->minimum_stock;
    }
}
