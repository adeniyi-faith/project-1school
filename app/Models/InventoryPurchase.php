<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryPurchase extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'item_id', 'vendor', 'purchase_date',
        'quantity', 'unit_price', 'total_price', 'invoice_no', 'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'quantity'      => 'decimal:2',
        'unit_price'    => 'decimal:2',
        'total_price'   => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
