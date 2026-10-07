<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonPlan extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'class_id', 'subject_id', 'teacher_id',
        'title', 'objectives', 'content', 'teaching_methods', 'resources',
        'week_start', 'status', 'reviewer_feedback', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'week_start'  => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function subject(): BelongsTo     { return $this->belongsTo(Subject::class); }
    public function teacher(): BelongsTo     { return $this->belongsTo(Staff::class, 'teacher_id'); }
    public function reviewer(): BelongsTo    { return $this->belongsTo(User::class, 'reviewed_by'); }
}
