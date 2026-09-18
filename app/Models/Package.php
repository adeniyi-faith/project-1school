<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'price_monthly', 'price_yearly',
        'max_students', 'max_staff', 'storage_gb', 'is_active', 'features',
    ];

    protected $casts = [
        'features'      => 'array',
        'is_active'     => 'boolean',
        'price_monthly' => 'decimal:2',
        'price_yearly'  => 'decimal:2',
    ];

    public function modules(): HasMany
    {
        return $this->hasMany(PackageModule::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(SchoolSubscription::class);
    }
}
