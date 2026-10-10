<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An entrance exam or interview for one admission inquiry, and how the child did. */
class AdmissionAssessment extends Model
{
    use BelongsToSchool;

    public const TYPES = ['exam', 'interview'];
    public const OUTCOMES = ['pending', 'passed', 'failed', 'absent'];

    protected $fillable = [
        'school_id', 'inquiry_id', 'type', 'scheduled_at', 'venue', 'score', 'max_score', 'outcome', 'remarks', 'recorded_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'score' => 'float',
        'max_score' => 'float',
    ];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(AdmissionInquiry::class, 'inquiry_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
