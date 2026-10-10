<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionInquiry extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'student_name', 'class_interested', 'guardian_name',
        'guardian_phone', 'guardian_email', 'status', 'notes',
        'next_followup_date', 'source', 'converted_student_id',
    ];

    /** Steps an inquiry moves through. "accepted" and "admitted" are only reached through their actions. */
    public const STATUSES = ['new', 'follow_up', 'accepted', 'admitted', 'dropped'];

    protected $casts = [
        'next_followup_date' => 'date',
        'decided_at' => 'datetime',
        'enrolled_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function followups(): HasMany
    {
        return $this->hasMany(InquiryFollowup::class, 'inquiry_id')->latest();
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(AdmissionAssessment::class, 'inquiry_id')->orderBy('id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function enroller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    public function convertedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'converted_student_id');
    }
}
