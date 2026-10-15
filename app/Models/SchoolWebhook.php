<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** Where a school wants to be told when something happens (a new inquiry, an enrolment, a payment) */
class SchoolWebhook extends Model
{
    use BelongsToSchool;

    public const EVENTS = [
        'inquiry.created' => 'A new inquiry or website message',
        'student.enrolled' => 'A student is enrolled',
        'payment.received' => 'A fee payment is recorded',
    ];

    protected $fillable = ['school_id', 'url', 'secret', 'events', 'is_active', 'last_status', 'last_error', 'last_sent_at'];

    protected $hidden = ['secret'];

    protected $casts = ['secret' => 'encrypted', 'events' => 'array', 'is_active' => 'boolean', 'last_sent_at' => 'datetime'];
}
