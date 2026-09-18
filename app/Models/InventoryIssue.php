<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryIssue extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'item_id', 'issued_to_type', 'issued_to_id', 'issued_to_name',
        'quantity', 'issue_date', 'return_date', 'purpose', 'status', 'notes',
    ];

    protected $casts = [
        'issue_date'  => 'date',
        'return_date' => 'date',
        'quantity'    => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
