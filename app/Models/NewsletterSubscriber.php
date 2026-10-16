<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** Someone who asked for a school's newsletter. Only `confirmed` people are ever sent one. */
class NewsletterSubscriber extends Model
{
    use BelongsToSchool;

    public const INTERESTS = ['news' => 'School news', 'events' => 'Events', 'admissions' => 'Admissions'];

    protected $fillable = [
        'school_id', 'email', 'name', 'status', 'interests', 'confirm_token', 'unsubscribe_token', 'consent_text', 'consented_at',
        'consent_ip', 'consent_page', 'confirmed_at', 'unsubscribed_at', 'confirm_sent_at',
    ];

    protected $hidden = ['confirm_token', 'unsubscribe_token'];

    protected $casts = [
        'interests' => 'array', 'consented_at' => 'datetime', 'confirmed_at' => 'datetime', 'unsubscribed_at' => 'datetime', 'confirm_sent_at' => 'datetime',
    ];
}
