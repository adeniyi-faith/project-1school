<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Syllabus extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $table = 'syllabi';

    protected $fillable = [
        'school_id', 'class_id', 'subject_id', 'academic_year',
        'title', 'file', 'topics', 'completion_percent',
    ];

    protected $casts = [
        'topics'             => 'array',
        'completion_percent' => 'decimal:2',
    ];

    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function subject(): BelongsTo     { return $this->belongsTo(Subject::class); }

    public function recalculateCompletion(): void
    {
        $topics = $this->topics ?? [];
        if (empty($topics)) { $this->completion_percent = 0; return; }
        $covered = collect($topics)->where('covered', true)->count();
        $this->completion_percent = round(($covered / count($topics)) * 100, 2);
    }
}
