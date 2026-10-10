<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** An applicant down to sit an entrance exam, with the exam number and PIN printed on their slip */
class CbtCandidate extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'cbt_test_id', 'inquiry_id', 'admission_assessment_id', 'exam_number', 'pin'];

    protected $casts = ['pin' => 'encrypted'];

    protected $hidden = ['pin'];

    public function test(): BelongsTo
    {
        return $this->belongsTo(CbtTest::class, 'cbt_test_id');
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(AdmissionInquiry::class, 'inquiry_id');
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(AdmissionAssessment::class, 'admission_assessment_id');
    }

    public function attempt(): HasOne
    {
        return $this->hasOne(CbtAttempt::class);
    }
}
