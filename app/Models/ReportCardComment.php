<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The class teacher's and principal's comments on one student's term report card. */
class ReportCardComment extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'result_sheet_id', 'student_id', 'teacher_comment', 'teacher_by', 'principal_comment', 'principal_by'];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(ResultSheet::class, 'result_sheet_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
