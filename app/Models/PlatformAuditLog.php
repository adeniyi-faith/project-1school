<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One thing a member of the platform team did. Append-only: a line can never be changed or removed. */
class PlatformAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Audit lines cannot be changed.'));
        static::deleting(fn () => throw new \LogicException('Audit lines cannot be removed.'));
    }
}
