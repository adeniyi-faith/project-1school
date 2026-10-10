<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mark extends Model
{
    use BelongsToSchool, LogsActivity;

    protected $fillable = [
        'school_id', 'exam_id', 'student_id', 'subject_id',
        'marks_obtained', 'grade', 'gpa', 'is_absent', 'remarks',
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'gpa'            => 'decimal:2',
        'is_absent'      => 'boolean',
    ];

    /**
     * The three values below are worked out from the subject's full and pass
     * marks (they are not stored). Load the `subject` relation first to
     * avoid one extra query per mark.
     */
    public function getTotalMarksAttribute(): ?int
    {
        return $this->subject?->full_marks;
    }

    public function getPercentageAttribute(): float
    {
        $full = (float) ($this->subject?->full_marks ?? 0);

        if ($this->is_absent || $this->marks_obtained === null || $full <= 0) {
            return 0.0;
        }

        return round(((float) $this->marks_obtained / $full) * 100, 2);
    }

    public function getIsPassAttribute(): bool
    {
        if ($this->is_absent || $this->marks_obtained === null || $this->subject === null) {
            return false;
        }

        return (float) $this->marks_obtained >= (float) $this->subject->pass_marks;
    }

    public function exam(): BelongsTo    { return $this->belongsTo(Exam::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['exam_id', 'student_id', 'subject_id', 'marks_obtained', 'grade', 'gpa', 'is_absent', 'remarks'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('results')
            ->setDescriptionForEvent(fn (string $event) => "Mark {$event}");
    }
}
