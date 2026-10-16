<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attendance extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'academic_year_id', 'date',
        'attendable_type', 'attendable_id', 'status', 'remarks',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function attendable(): MorphTo
    {
        return $this->morphTo();
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Set one person's mark for one day: changes the mark if the day was already marked, adds it if not.
     * The day is matched as a date, not as text, so a mark saved as "2026-03-30 00:00:00" is still found.
     */
    public static function mark(int $schoolId, string $type, int $id, string $date, string $status, ?string $remarks): self
    {
        $row = static::where(['school_id' => $schoolId, 'attendable_type' => $type, 'attendable_id' => $id])->whereDate('date', $date)->first()
            ?? new static(['school_id' => $schoolId, 'attendable_type' => $type, 'attendable_id' => $id, 'date' => $date]);
        $row->fill(['status' => $status, 'remarks' => $remarks])->save();

        return $row;
    }
}
