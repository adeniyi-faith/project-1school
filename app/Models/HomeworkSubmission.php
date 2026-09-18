<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeworkSubmission extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'homework_id', 'student_id',
        'file', 'text_response', 'status', 'teacher_remarks',
    ];

    public function homework(): BelongsTo { return $this->belongsTo(Homework::class); }
    public function student(): BelongsTo  { return $this->belongsTo(Student::class); }
}
