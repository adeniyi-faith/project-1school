<?php

namespace App\Models;

use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * The next available school code, e.g. "001", "002".
     */
    public static function nextCode(): string
    {
        $lastCode = static::query()->orderByDesc('id')->value('code');

        return str_pad((int) $lastCode + 1, 3, '0', STR_PAD_LEFT);
    }
}
