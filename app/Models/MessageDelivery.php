<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One person in a blast and whether their message went */
class MessageDelivery extends Model
{
    use BelongsToSchool;

    /** queued: waiting; sent: handed over; failed: tried and refused (can be tried again); not_sent: could not be tried */
    public const STATUSES = ['queued', 'sent', 'failed', 'not_sent'];

    protected $fillable = ['school_id', 'message_batch_id', 'recipient_type', 'recipient_id', 'recipient_name', 'to', 'status', 'error', 'attempts', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(MessageBatch::class, 'message_batch_id');
    }
}
