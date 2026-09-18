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

    protected $casts = [
        'next_followup_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function followups(): HasMany
    {
        return $this->hasMany(InquiryFollowup::class, 'inquiry_id')->latest();
    }

    public function convertedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'converted_student_id');
    }
}
