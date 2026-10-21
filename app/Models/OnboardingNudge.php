<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** One nudge the platform team sent a school that looked stuck */
class OnboardingNudge extends Model
{
    use BelongsToSchool;

    public const UPDATED_AT = null;

    protected $fillable = ['school_id', 'sent_by', 'message', 'bell_count', 'email_count', 'email_failed'];
}
