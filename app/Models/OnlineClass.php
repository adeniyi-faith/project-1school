<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnlineClass extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'class_id', 'subject_id', 'teacher_id',
        'title', 'platform', 'meeting_url', 'meeting_id', 'passcode',
        'scheduled_at', 'duration_minutes', 'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function subject(): BelongsTo     { return $this->belongsTo(Subject::class); }
    public function teacher(): BelongsTo     { return $this->belongsTo(Staff::class, 'teacher_id'); }
}
