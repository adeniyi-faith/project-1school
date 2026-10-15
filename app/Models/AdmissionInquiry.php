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
        'student_gender', 'student_dob',
    ];

    /** Steps an inquiry moves through. "accepted" and "admitted" are only reached through their actions. */
    public const STATUSES = ['new', 'follow_up', 'accepted', 'admitted', 'dropped'];

    protected $casts = [
        'next_followup_date' => 'date',
        'decided_at' => 'datetime',
        'enrolled_at' => 'datetime',
        'student_dob' => 'date',
        'family_accepted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        parent::booted();

        // A short code the family can quote, e.g. APP-2026-0042 (the id makes it unique)
        static::created(function (AdmissionInquiry $inquiry) {
            if (! $inquiry->reference) {
                $inquiry->forceFill(['reference' => 'APP-'.now()->format('Y').'-'.str_pad((string) $inquiry->id, 4, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /**
     * Where the application stands, in the words a parent reads.
     *
     * @return array{label: string, text: string, tone: string}
     */
    public function familyStage(string $schoolName): array
    {
        return match ($this->status) {
            'accepted' => $this->family_accepted_at
                ? ['label' => 'Offer accepted', 'text' => "Thank you. You have accepted the place at {$schoolName}. The school will contact you about starting.", 'tone' => 'good']
                : ['label' => 'Place offered', 'text' => "Good news: {$schoolName} has offered your child a place. Please accept it below so the school can plan.", 'tone' => 'good'],
            'admitted' => ['label' => 'Enrolled', 'text' => "Welcome! Your child is now a pupil at {$schoolName}.", 'tone' => 'good'],
            'dropped' => ['label' => 'Not offered', 'text' => "Thank you for applying. Unfortunately {$schoolName} cannot offer a place at this time. Please contact the school if you would like to talk about it.", 'tone' => 'plain'],
            'follow_up' => ['label' => 'Being reviewed', 'text' => 'The admissions office is looking at your application and may contact you to arrange a visit or a short assessment.', 'tone' => 'plain'],
            default => ['label' => 'Received', 'text' => 'We have your application. The admissions office will contact you soon.', 'tone' => 'plain'],
        };
    }

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
