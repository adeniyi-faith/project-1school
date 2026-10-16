<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** One person's copy of one newsletter. The unique (campaign, subscriber) pair is what stops a double send. */
class NewsletterDelivery extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'campaign_id', 'subscriber_id', 'email', 'status', 'error', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];
}
