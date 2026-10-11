<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'name', 'asset_code', 'category',
        'purchase_date', 'purchase_price', 'current_value',
        'depreciation_method', 'depreciation_rate',
        'location', 'assigned_to', 'status', 'description',
    ];

    protected $casts = [
        'purchase_date'    => 'date',
        'purchase_price'   => 'decimal:2',
        'current_value'    => 'decimal:2',
        'depreciation_rate'=> 'decimal:2',
    ];

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(AssetMaintenanceLog::class);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Asset $asset) {
            if (empty($asset->asset_code)) {
                $asset->asset_code = NumberSequence::next($asset->school_id, 'AST-'.now()->format('Y').'-', 4,
                    static::withoutGlobalScopes()->where('school_id', $asset->school_id), 'asset_code');
            }
            if ($asset->current_value == 0) {
                $asset->current_value = $asset->purchase_price;
            }
        });
    }
}
