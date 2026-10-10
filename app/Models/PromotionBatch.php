<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One "move this class up for the year" action. Undoing it puts the students back. */
class PromotionBatch extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'academic_year_id', 'from_class_id', 'to_class_id', 'pass_mark',
        'promoted_count', 'repeated_count', 'graduated_count', 'performed_by', 'undone_at', 'undone_by',
    ];

    protected $casts = [
        'pass_mark' => 'float',
        'promoted_count' => 'integer',
        'repeated_count' => 'integer',
        'graduated_count' => 'integer',
        'undone_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(StudentPromotion::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function fromClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'from_class_id');
    }

    public function toClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'to_class_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function undoer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'undone_by');
    }
}
