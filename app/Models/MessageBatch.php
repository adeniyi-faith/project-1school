<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One SMS or email blast: what was sent, to whom (in words) and by whom */
class MessageBatch extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = ['school_id', 'channel', 'audience', 'subject', 'body', 'sent_by'];

    public function deliveries(): HasMany
    {
        return $this->hasMany(MessageDelivery::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
