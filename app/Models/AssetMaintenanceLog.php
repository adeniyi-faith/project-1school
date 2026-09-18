<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenanceLog extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'asset_id', 'date', 'description',
        'cost', 'vendor', 'next_maintenance_date',
    ];

    protected $casts = [
        'date'                 => 'date',
        'next_maintenance_date'=> 'date',
        'cost'                 => 'decimal:2',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
