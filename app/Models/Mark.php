<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mark extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'exam_id', 'student_id', 'subject_id',
        'marks_obtained', 'grade', 'gpa', 'is_absent', 'remarks',
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'gpa'            => 'decimal:2',
        'is_absent'      => 'boolean',
    ];

    public function exam(): BelongsTo    { return $this->belongsTo(Exam::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
}
