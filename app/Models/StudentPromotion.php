<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One student's end-of-year move: where they were, where they went, and why. */
class StudentPromotion extends Model
{
    use BelongsToSchool;

    public const OUTCOMES = ['promoted', 'repeated', 'graduated'];

    protected $fillable = [
        'school_id', 'promotion_batch_id', 'student_id', 'outcome',
        'from_class_id', 'from_section_id', 'from_status', 'to_class_id', 'to_section_id',
        'year_average', 'reason',
    ];

    protected $casts = ['year_average' => 'float'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PromotionBatch::class, 'promotion_batch_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'from_class_id');
    }

    public function toClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'to_class_id');
    }

    public function fromSection(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'from_section_id');
    }

    public function toSection(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'to_section_id');
    }
}
