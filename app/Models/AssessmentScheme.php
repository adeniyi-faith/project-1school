<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A named set of score parts, e.g. "CA1 20 + CA2 20 + Exam 60". The parts add up to 100. */
class AssessmentScheme extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = ['school_id', 'name', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];

    public function components(): HasMany
    {
        return $this->hasMany(AssessmentComponent::class)->orderBy('sort_order');
    }

    /**
     * The score setup a subject uses in a class: the class's own for that subject, else the subject's, else the class's,
     * else the school default. Without a class, the class the subject was first made for.
     */
    public static function forSubject(Subject $subject, ?int $classId = null): ?self
    {
        $classId ??= $subject->class_id;
        $id = $subject->lineFor($classId)?->assessment_scheme_id
            ?? $subject->assessment_scheme_id
            ?? ($classId ? SchoolClass::withoutGlobalScopes()->whereKey($classId)->value('assessment_scheme_id') : null);

        return ($id ? static::find($id) : null)
            ?? static::where('school_id', $subject->school_id)->where('is_default', true)->first();
    }
}
