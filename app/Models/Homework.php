<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Homework extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $table = 'homework';

    protected $fillable = [
        'school_id', 'class_id', 'subject_id', 'teacher_id',
        'title', 'description', 'due_date', 'attachment', 'is_active',
    ];

    protected $casts = [
        'due_date'  => 'date',
        'is_active' => 'boolean',
    ];

    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function subject(): BelongsTo     { return $this->belongsTo(Subject::class); }
    public function teacher(): BelongsTo     { return $this->belongsTo(Staff::class, 'teacher_id'); }
    public function submissions(): HasMany   { return $this->hasMany(HomeworkSubmission::class); }
}
