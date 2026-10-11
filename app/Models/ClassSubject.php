<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A class offers a subject. With departments (e.g. Science, Arts) only students in an arm of one of those
 * departments take it; empty means everyone in the class. A score setup here is this class's own for the subject.
 */
class ClassSubject extends Model
{
    use BelongsToSchool;

    protected $table = 'class_subjects';

    protected $fillable = ['school_id', 'class_id', 'subject_id', 'departments', 'assessment_scheme_id'];

    protected $casts = ['departments' => 'array'];

    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function subject(): BelongsTo     { return $this->belongsTo(Subject::class); }

    /** Whether a student in an arm of this department takes the subject */
    public function takenBy(?string $department): bool
    {
        $only = array_filter($this->departments ?? []);

        return $only === [] || ($department !== null && in_array(mb_strtolower($department), array_map('mb_strtolower', $only), true));
    }
}
