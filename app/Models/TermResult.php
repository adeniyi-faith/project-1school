<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A student's stored result in one subject for one term. Worked out by TermResultService. */
class TermResult extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'result_sheet_id', 'student_id', 'subject_id', 'total', 'grade', 'gpa', 'remarks',
        'subject_position', 'subject_average', 'subject_highest', 'subject_lowest', 'version',
    ];

    protected $casts = [
        'total' => 'float', 'gpa' => 'float', 'subject_average' => 'float',
        'subject_highest' => 'float', 'subject_lowest' => 'float',
        'subject_position' => 'integer', 'version' => 'integer',
    ];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(ResultSheet::class, 'result_sheet_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
