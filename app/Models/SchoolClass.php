<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'school_id', 'name', 'numeric_name', 'level', 'capacity', 'class_teacher_id',
        'assessment_scheme_id', 'grading_scheme_id', 'report_card_design_id',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'class_id');
    }

    /** Early years, primary, junior secondary, senior secondary: classes at one level usually share a list of subjects */
    public const LEVELS = ['early_years' => 'Early years (Creche, Nursery)', 'primary' => 'Primary', 'junior' => 'Junior secondary (JSS)', 'senior' => 'Senior secondary (SSS)'];

    /** Every subject the class offers */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'class_subjects', 'class_id', 'subject_id')
            ->withPivot(['departments', 'assessment_scheme_id'])->withTimestamps();
    }

    public function subjectLines(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'class_id');
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
