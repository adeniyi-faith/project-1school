<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A student's overall result for a term: total, average and position in class. */
class TermResultSummary extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'result_sheet_id', 'student_id', 'subjects_count', 'total_score', 'average', 'position', 'class_size', 'version'];

    protected $casts = [
        'total_score' => 'float', 'average' => 'float', 'position' => 'integer',
        'subjects_count' => 'integer', 'class_size' => 'integer', 'version' => 'integer',
    ];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(ResultSheet::class, 'result_sheet_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
