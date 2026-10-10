<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/** One score for one score part, e.g. a student's CA1 in Mathematics this term. */
class SubjectScore extends Model
{
    use BelongsToSchool, LogsActivity;

    protected $fillable = ['school_id', 'result_sheet_id', 'student_id', 'subject_id', 'assessment_component_id', 'score'];

    protected $casts = ['score' => 'float'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('results')->logOnly(['score'])->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(ResultSheet::class, 'result_sheet_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(AssessmentComponent::class, 'assessment_component_id');
    }
}
