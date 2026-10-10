<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'school_id', 'name', 'numeric_name', 'capacity', 'class_teacher_id',
        'assessment_scheme_id', 'grading_scheme_id', 'report_card_design_id',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'class_id');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'class_id');
    }

    /** Empty means the class uses the school's default score setup */
    public function assessmentScheme(): BelongsTo
    {
        return $this->belongsTo(AssessmentScheme::class);
    }

    /** Empty means the class uses the school's default grade scale */
    public function gradingScheme(): BelongsTo
    {
        return $this->belongsTo(GradingScheme::class);
    }
}
