<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A request to do a risky platform action. Belongs to the platform (it has no school_id on purpose).
 * Pending -> approved or denied by a second person; an approved one is used up by the single action it allows.
 */
class PlatformApproval extends Model
{
    /** A request nobody answers is forgotten after this many days; an approval can be used for this many hours */
    public const PENDING_DAYS = 7;
    public const APPROVED_HOURS = 24;

    protected $fillable = [
        'action', 'label', 'target_type', 'target_id', 'target_name', 'reason', 'requested_by', 'requested_by_name',
        'status', 'decided_by', 'decided_by_name', 'decided_at', 'decision_note', 'expires_at', 'used_at',
    ];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime', 'expires_at' => 'datetime', 'used_at' => 'datetime'];
    }

    public function isStale(): bool
    {
        return $this->status === 'pending'
            ? $this->created_at->lt(now()->subDays(self::PENDING_DAYS))
            : ($this->status === 'approved' && $this->expires_at && $this->expires_at->isPast());
    }
}
