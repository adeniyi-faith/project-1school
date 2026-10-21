<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One run of a one-click command: who ran it, why, how it ended and what it printed. Never edited. */
class PlatformCommandRun extends Model
{
    protected $fillable = ['command', 'user_id', 'reason', 'status', 'duration_ms', 'output', 'ip'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
