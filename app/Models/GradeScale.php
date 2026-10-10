<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeScale extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'grading_scheme_id', 'grade', 'gpa', 'min_marks', 'max_marks', 'remarks', 'sort_order',
    ];

    protected $casts = [
        'gpa' => 'decimal:2', 'min_marks' => 'decimal:2', 'max_marks' => 'decimal:2',
    ];

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(GradingScheme::class, 'grading_scheme_id');
    }
}
