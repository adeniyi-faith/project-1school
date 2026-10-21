<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A request a school raised with the platform team */
class SupportTicket extends Model
{
    use BelongsToSchool;

    public const CATEGORIES = ['question' => 'A question', 'problem' => 'Something is not working', 'billing' => 'Billing or plan', 'request' => 'A new feature or change'];
    public const STATUSES = ['open', 'answered', 'closed'];

    protected $fillable = ['school_id', 'user_id', 'subject', 'category', 'priority', 'status', 'assigned_to', 'last_reply_at', 'closed_at'];

    protected $casts = ['last_reply_at' => 'datetime', 'closed_at' => 'datetime'];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->oldest();
    }
}
